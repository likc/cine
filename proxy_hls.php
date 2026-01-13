<?php
/**
 * PROXY HLS - Tunnel para Streams IPTV
 * Faz proxy dos streams do servidor IPTV para contornar problemas de token/expiração
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Ações que requerem autenticação
$protected_actions = ['movie', 'series', 'episode', 'stream', 'segment'];
$action = $_GET['action'] ?? 'test';

if (in_array($action, $protected_actions) && !isLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado. Faça login primeiro.']);
    exit;
}

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Função otimizada para streaming
function optimizedWriteFunction() {
    return function($ch, $data) {
        static $bytes_sent = 0;
        echo $data;
        if (ob_get_level() > 0) {
            ob_flush();
            flush();
        }
        $bytes_sent += strlen($data);
        
        // Limita a 10MB para evitar memory issues
        if ($bytes_sent > 10 * 1024 * 1024) {
            return 0;
        }
        
        // Verifica se cliente desconectou
        if (connection_aborted()) {
            return 0;
        }
        
        return strlen($data);
    };
}

// Roteamento
switch ($action) {
    case 'test':
        // Teste de conectividade
        header('Content-Type: application/json');
        echo json_encode([
            'status' => isLoggedIn() ? 'success' : 'error',
            'message' => isLoggedIn() ? 'Proxy OK' : 'Não autenticado',
            'logged_in' => isLoggedIn()
        ]);
        break;
        
    case 'movie':
        // Proxy de filme
        $movie_id = $_GET['id'] ?? null;
        if (!$movie_id) {
            http_response_code(400);
            exit('ID não fornecido');
        }
        
        // Busca informações do filme no cache
        $cache_file = __DIR__ . '/cache/movies.json';
        $extension = 'mp4';
        
        if (file_exists($cache_file)) {
            $movies = json_decode(file_get_contents($cache_file), true);
            if (is_array($movies)) {
                foreach ($movies as $movie) {
                    if ($movie['id'] == $movie_id) {
                        $extension = $movie['container'] ?? 'mp4';
                        break;
                    }
                }
            }
        }
        
        // Gera teste temporário na Odin
        $services = getServices();
        $serviceIndex = (int)getSetting('odin_service_index', 4); // Usa ODINPLAY -18 por padrão
        $selectedService = $services[$serviceIndex] ?? $services[4];
        
        $testResult = generateTest($selectedService['url']);
        
        if (!$testResult['success']) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Não foi possível gerar acesso temporário',
                'details' => $testResult['error'] ?? 'Erro desconhecido'
            ]);
            exit;
        }
        
        $username = $testResult['username'];
        $password = $testResult['password'];
        $server = $testResult['server'];
        $port = $testResult['port'];
        
        // Monta URL do filme
        $movie_url = "http://{$server}:{$port}/movie/{$username}/{$password}/{$movie_id}.{$extension}";
        
        error_log("Proxy movie: {$movie_url}");
        
        // Limpa buffers
        while (ob_get_level()) ob_end_clean();
        
        // Monta headers
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Accept: */*",
            "Connection: keep-alive"
        ];
        
        // Suporte a Range requests (importante para seek no vídeo)
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers[] = "Range: {$_SERVER['HTTP_RANGE']}";
        }
        
        // Faz proxy do filme
        $ch = curl_init($movie_url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 0, // Sem timeout para streaming
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_BUFFERSIZE => 131072, // 128KB buffer
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_BINARYTRANSFER => true,
            
            // Repassa headers importantes
            CURLOPT_HEADERFUNCTION => function($ch, $header) {
                $len = strlen($header);
                $header = trim($header);
                if (empty($header)) return $len;
                
                // Headers importantes para streaming
                $important = ['Content-Type', 'Content-Length', 'Content-Range', 'Accept-Ranges'];
                foreach ($important as $imp) {
                    if (stripos($header, $imp . ':') === 0) {
                        header($header);
                        break;
                    }
                }
                
                // HTTP status code
                if (preg_match('/^HTTP\/[\d.]+\s+(\d+)/', $header, $matches)) {
                    http_response_code((int)$matches[1]);
                }
                
                return $len;
            },
            
            // Stream direto para o cliente
            CURLOPT_WRITEFUNCTION => optimizedWriteFunction()
        ]);
        
        curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            error_log("Erro no proxy movie: {$error} (HTTP {$http_code})");
        }
        
        exit;
        break;
        
    case 'stream':
        // Proxy de stream HLS (manifest M3U8)
        $stream_id = $_GET['id'] ?? null;
        if (!$stream_id) {
            http_response_code(400);
            exit('ID não fornecido');
        }
        
        // Gera teste temporário
        $services = getServices();
        $serviceIndex = (int)getSetting('odin_service_index', 4);
        $selectedService = $services[$serviceIndex] ?? $services[4];
        
        $testResult = generateTest($selectedService['url']);
        
        if (!$testResult['success']) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Não foi possível gerar acesso']);
            exit;
        }
        
        $username = $testResult['username'];
        $password = $testResult['password'];
        $server = $testResult['server'];
        $port = $testResult['port'];
        
        // Busca manifest
        $manifest_url = "http://{$server}:{$port}/live/{$username}/{$password}/{$stream_id}.m3u8";
        
        $ch = curl_init($manifest_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Mozilla/5.0",
                "Accept: */*"
            ]
        ]);
        
        $manifest = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        if ($http_code !== 200 || !$manifest) {
            http_response_code(503);
            exit('Stream indisponível');
        }
        
        // Reescreve URLs do manifest para passar pelo nosso proxy
        $base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $lines = explode("\n", $manifest);
        $rewritten = "";
        $manifest_base = dirname($final_url);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || $line[0] === '#') {
                $rewritten .= $line . "\n";
                continue;
            }
            
            // Determina URL do segmento
            if (preg_match('#^https?://#', $line)) {
                $segment_url = $line;
            } else {
                $segment_url = $manifest_base . '/' . ltrim($line, '/');
            }
            
            // Reescreve para passar pelo proxy
            $encoded = base64_encode($segment_url);
            $rewritten .= "{$base_url}/proxy_hls.php?action=segment&url={$encoded}\n";
        }
        
        header('Content-Type: application/vnd.apple.mpegurl');
        header('Cache-Control: no-cache');
        echo $rewritten;
        exit;
        break;
        
    case 'segment':
        // Proxy de segmento TS individual
        $url = $_GET['url'] ?? null;
        if (!$url) {
            http_response_code(400);
            exit('URL não fornecida');
        }
        
        $url = base64_decode($url);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_BUFFERSIZE => 131072,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Mozilla/5.0",
                "Accept: */*"
            ],
            CURLOPT_RETURNTRANSFER => false,
            
            CURLOPT_HEADERFUNCTION => function($ch, $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    header('Content-Type: video/mp2t');
                }
                return strlen($header);
            },
            
            CURLOPT_WRITEFUNCTION => optimizedWriteFunction()
        ]);
        
        header('Content-Type: video/mp2t');
        header('Cache-Control: max-age=3600');
        
        curl_exec($ch);
        curl_close($ch);
        exit;
        break;
        
    case 'episode':
        // Proxy de episódio de série
        $episode_id = $_GET['id'] ?? null;
        if (!$episode_id) {
            http_response_code(400);
            exit('ID não fornecido');
        }
        
        // Gera teste temporário
        $services = getServices();
        $serviceIndex = (int)getSetting('odin_service_index', 4);
        $selectedService = $services[$serviceIndex] ?? $services[4];
        
        $testResult = generateTest($selectedService['url']);
        
        if (!$testResult['success']) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Não foi possível gerar acesso']);
            exit;
        }
        
        $username = $testResult['username'];
        $password = $testResult['password'];
        $server = $testResult['server'];
        $port = $testResult['port'];
        
        // URL do episódio (geralmente em format .mp4 ou .mkv)
        $extension = $_GET['ext'] ?? 'mp4';
        $episode_url = "http://{$server}:{$port}/series/{$username}/{$password}/{$episode_id}.{$extension}";
        
        error_log("Proxy episode: {$episode_url}");
        
        // Limpa buffers
        while (ob_get_level()) ob_end_clean();
        
        // Headers
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Accept: */*",
            "Connection: keep-alive"
        ];
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers[] = "Range: {$_SERVER['HTTP_RANGE']}";
        }
        
        // Faz proxy do episódio
        $ch = curl_init($episode_url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_BUFFERSIZE => 131072,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_BINARYTRANSFER => true,
            
            CURLOPT_HEADERFUNCTION => function($ch, $header) {
                $len = strlen($header);
                $header = trim($header);
                if (empty($header)) return $len;
                
                $important = ['Content-Type', 'Content-Length', 'Content-Range', 'Accept-Ranges'];
                foreach ($important as $imp) {
                    if (stripos($header, $imp . ':') === 0) {
                        header($header);
                        break;
                    }
                }
                
                if (preg_match('/^HTTP\/[\d.]+\s+(\d+)/', $header, $matches)) {
                    http_response_code((int)$matches[1]);
                }
                
                return $len;
            },
            
            CURLOPT_WRITEFUNCTION => optimizedWriteFunction()
        ]);
        
        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Erro no proxy episode: {$error}");
        }
        
        exit;
        break;
        
    case 'api':
        // Proxy para requisições da API (buscar categorias, info de séries, etc)
        $apiAction = $_GET['api_action'] ?? '';
        $seriesId = $_GET['series_id'] ?? '';
        
        if (!$apiAction) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'api_action não especificada']);
            exit;
        }
        
        // Gera novo teste para obter credenciais
        $services = getServices();
        $serviceIndex = (int)getSetting('odin_service_index', 4);
        $selectedService = $services[$serviceIndex] ?? $services[4];
        
        $testResult = generateTest($selectedService['url']);
        
        if (!$testResult['success']) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Não foi possível obter credenciais']);
            exit;
        }
        
        $username = $testResult['username'];
        $password = $testResult['password'];
        $server = $testResult['server'];
        $port = $testResult['port'];
        
        // Monta URL da API conforme a ação
        $apiUrl = "http://{$server}:{$port}/player_api.php?username={$username}&password={$password}&action={$apiAction}";
        
        if ($seriesId) {
            $apiUrl .= "&series_id={$seriesId}";
        }
        
        // Faz requisição
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        header('Content-Type: application/json');
        if ($httpCode === 200 && $response) {
            echo $response;
        } else {
            echo json_encode(['error' => 'Falha na API', 'http_code' => $httpCode]);
        }
        break;
        
    default:
        http_response_code(404);
        echo 'Ação inválida';
}
?>