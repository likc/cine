<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Cache GLOBAL - compartilhado entre todos os usuários
// Não precisa estar logado para ler o cache, só para atualizar

$action = $_GET['action'] ?? 'status';

// Diretórios de cache GLOBAL
$CACHE_DIR = __DIR__ . '/cache';
$MOVIES_CACHE = $CACHE_DIR . '/movies.json';
$SERIES_CACHE = $CACHE_DIR . '/series.json';
$CACHE_INFO = $CACHE_DIR . '/cache_info.json';

// Cria diretório se não existir
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0755, true);
}

// Apenas ações de leitura são públicas
$public_actions = ['status', 'get'];
$protected_actions = ['update', 'clear'];

if (in_array($action, $protected_actions) && !isLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado. Faça login para atualizar o cache.']);
    exit;
}

// Pega credenciais da sessão (só para update)
if (isLoggedIn()) {
    $SERVER = $_SESSION['iptv_server'] ?? REAL_IP;
    $PORT = $_SESSION['iptv_port'] ?? PORT;
    $USERNAME = $_SESSION['iptv_username'] ?? '';
    $PASSWORD = $_SESSION['iptv_password'] ?? '';
}

// Função para buscar da API
function fetchFromAPI($action, $server, $port, $username, $password) {
    $url = "http://{$server}:{$port}/player_api.php?username={$username}&password={$password}&action={$action}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60, // Aumentado para 60 segundos
        CURLOPT_HTTPHEADER => [
            "User-Agent: Mozilla/5.0"
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $decoded = json_decode($response, true);
        return $decoded;
    }
    
    return null;
}

// Função para processar filmes
function processMovies($movies) {
    if (!is_array($movies)) return [];
    
    $processed = [];
    foreach ($movies as $movie) {
        $id = $movie['stream_id'] ?? $movie['num'] ?? null;
        if (!$id) continue;
        
        $processed[] = [
            'id' => $id,
            'name' => $movie['name'] ?? $movie['title'] ?? 'Sem título',
            'container' => $movie['container_extension'] ?? 'mp4',
            'rating' => $movie['rating'] ?? null,
            'year' => $movie['year'] ?? null,
            'category_id' => $movie['category_id'] ?? null,
            'added' => $movie['added'] ?? null
        ];
    }
    return $processed;
}

// Função para processar séries
function processSeries($series) {
    if (!is_array($series)) return [];
    
    $processed = [];
    foreach ($series as $serie) {
        $id = $serie['series_id'] ?? null;
        if (!$id) continue;
        
        $processed[] = [
            'id' => $id,
            'name' => $serie['name'] ?? $serie['title'] ?? 'Sem título',
            'cover' => $serie['cover'] ?? null,
            'rating' => $serie['rating'] ?? null,
            'year' => $serie['year'] ?? null,
            'category_id' => $serie['category_id'] ?? null,
            'last_modified' => $serie['last_modified'] ?? null
        ];
    }
    return $processed;
}

// Roteamento
$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'status':
        // Verifica status do cache
        $info = [
            'movies' => [
                'exists' => file_exists($MOVIES_CACHE),
                'size' => file_exists($MOVIES_CACHE) ? filesize($MOVIES_CACHE) : 0,
                'count' => 0,
                'updated' => null
            ],
            'series' => [
                'exists' => file_exists($SERIES_CACHE),
                'size' => file_exists($SERIES_CACHE) ? filesize($SERIES_CACHE) : 0,
                'count' => 0,
                'updated' => null
            ]
        ];
        
        if (file_exists($CACHE_INFO)) {
            $cache_info = json_decode(file_get_contents($CACHE_INFO), true);
            if ($cache_info) {
                $info['movies']['count'] = $cache_info['movies']['count'] ?? 0;
                $info['movies']['updated'] = $cache_info['movies']['updated'] ?? null;
                $info['series']['count'] = $cache_info['series']['count'] ?? 0;
                $info['series']['updated'] = $cache_info['series']['updated'] ?? null;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($info, JSON_PRETTY_PRINT);
        break;
        
    case 'update':
        // Atualiza cache GLOBAL
        $type = $_GET['type'] ?? 'all'; // all, movies, series
        $result = ['success' => true, 'updated' => [], 'errors' => []];
        
        if ($type === 'all' || $type === 'movies') {
            $movies = fetchFromAPI('get_vod_streams', $SERVER, $PORT, $USERNAME, $PASSWORD);
            
            if ($movies && is_array($movies)) {
                $processed = processMovies($movies);
                file_put_contents($MOVIES_CACHE, json_encode($processed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $result['updated'][] = 'movies';
                $result['movies_count'] = count($processed);
            } else {
                $result['errors'][] = 'Falha ao buscar filmes da API';
                error_log("Movies API failed. Response: " . print_r($movies, true));
            }
        }
        
        if ($type === 'all' || $type === 'series') {
            $series = fetchFromAPI('get_series', $SERVER, $PORT, $USERNAME, $PASSWORD);
            
            if ($series && is_array($series)) {
                $processed = processSeries($series);
                file_put_contents($SERIES_CACHE, json_encode($processed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $result['updated'][] = 'series';
                $result['series_count'] = count($processed);
            } else {
                $result['errors'][] = 'Falha ao buscar séries da API';
                error_log("Series API failed. Response: " . print_r($series, true));
            }
        }
        
        // Atualiza info do cache
        $cache_info = [];
        
        if (file_exists($MOVIES_CACHE)) {
            $movies_data = json_decode(file_get_contents($MOVIES_CACHE), true);
            $cache_info['movies'] = [
                'count' => count($movies_data ?? []),
                'updated' => date('Y-m-d H:i:s')
            ];
        }
        
        if (file_exists($SERIES_CACHE)) {
            $series_data = json_decode(file_get_contents($SERIES_CACHE), true);
            $cache_info['series'] = [
                'count' => count($series_data ?? []),
                'updated' => date('Y-m-d H:i:s')
            ];
        }
        
        file_put_contents($CACHE_INFO, json_encode($cache_info, JSON_PRETTY_PRINT));
        
        // Define sucesso se pelo menos um foi atualizado
        $result['success'] = count($result['updated']) > 0;
        
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
        break;
        
    case 'get':
        // Retorna conteúdo do cache
        $type = $_GET['type'] ?? 'movies'; // movies, series
        $search = $_GET['search'] ?? '';
        
        $file = $type === 'movies' ? $MOVIES_CACHE : $SERIES_CACHE;
        
        if (!file_exists($file)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Cache não encontrado. Execute update primeiro.']);
            exit;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Filtro de busca
        if ($search) {
            $data = array_filter($data, function($item) use ($search) {
                return stripos($item['name'], $search) !== false;
            });
            $data = array_values($data); // Reindex
        }
        
        header('Content-Type: application/json');
        echo json_encode($data);
        break;
        
    case 'clear':
        // Limpa cache
        $cleared = [];
        if (file_exists($MOVIES_CACHE)) {
            unlink($MOVIES_CACHE);
            $cleared[] = 'movies';
        }
        if (file_exists($SERIES_CACHE)) {
            unlink($SERIES_CACHE);
            $cleared[] = 'series';
        }
        if (file_exists($CACHE_INFO)) {
            unlink($CACHE_INFO);
            $cleared[] = 'info';
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cleared' => $cleared]);
        break;
        
    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida']);
}
?>
