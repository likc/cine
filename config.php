<?php
/**
 * Configurações do Sistema IPTV
 * Este arquivo contém funções necessárias para gerar testes Odin
 */

// Não precisa de session_start aqui pois auth.php já faz isso

// ========================================
// CONFIGURAÇÕES IPTV (do sistema antigo)
// ========================================
define('REAL_IP', '89.187.173.212');
define('DOMAIN', 'cdn4k.cloud');
define('PORT', 80);

// ========================================
// LISTA DE SERVIÇOS ODIN
// ========================================
function getServices() {
    return [
        ['name' => 'Midgard - Parceiras',          'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/lze15veL5K'],
        ['name' => 'Midgard - TESTE +18 A',        'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/RYAWRNPWjl'],
        ['name' => 'Midgard - TESTE -18 A',        'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/o231qA214q'],
        ['name' => 'ODINPLAY - ODINPLAY +18',      'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/o231qzL4qz'],
        ['name' => 'ODINPLAY - ODINPLAY -18',      'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/z2BDvoWrkj'],
        ['name' => 'ODINPLAY - P2P BINSTREAM +18', 'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/aYB1wvBWvm'],
        ['name' => 'ODINPLAY - P2P BINSTREAM -18', 'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/z2BDv9rLrk'],
        ['name' => 'Ragnarok - Teste',             'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/DlrK1YpW8D'],
        ['name' => 'Ragnarok - Teste +18',         'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/02dLRyK1Wj'],
        ['name' => 'Ragnarok - Teste -18',         'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/2D3yK1Wjlr'],
        ['name' => 'Ragnarok - P2P +18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/LyK1WjD0d2'],
        ['name' => 'Ragnarok - P2P -18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/1WjD0d2LRy'],
        ['name' => 'Asgard - Teste',               'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/9rLrkz2BDv'],
        ['name' => 'Asgard - Teste +18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/rkz2BDv9rL'],
        ['name' => 'Asgard - Teste -18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/z2BDv9rLrk'],
        ['name' => 'Asgard - P2P +18',             'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/Bv9rLrkz2B'],
        ['name' => 'Asgard - P2P -18',             'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/9rLrkz2BDv'],
        ['name' => 'Valhalla - Teste',             'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/1wvBWvmaYB'],
        ['name' => 'Valhalla - Teste +18',         'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/WvmaYB1wvB'],
        ['name' => 'Valhalla - Teste -18',         'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/maYB1wvBWv'],
        ['name' => 'Valhalla - P2P +18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/B1wvBWvmaY'],
        ['name' => 'Valhalla - P2P -18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/wvBWvmaYB1'],
        ['name' => 'Best - Best +18',              'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/BV4D3V5Laq'],
        ['name' => 'Best - Best -18',              'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/3V5LaqBV4D'],
        ['name' => '7seven - 7seven +18',          'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/rlKWO6lDzo'],
        ['name' => '7seven - 7seven -18',          'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/6lDzorIKWO'],
        ['name' => '7seven - 7seven+18',           'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/IKWO6lDzor'],
        ['name' => '7seven - 7seven -18 (alt)',    'url' => 'https://superodinplay.shop/api/chatbot/7loL7yjWXM/rlKWO6lDzo'],
    ];
}

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

// Gera telefone brasileiro aleatório
function randomBrazilPhone(): string {
    $ddds = [11,12,13,14,15,16,17,18,19,21,22,24,27,28,31,32,33,34,35,37,38,41,42,43,44,45,46,47,48,49,51,53,54,55,61,62,64,63,65,66,67,68,69];
    $ddd = $ddds[array_rand($ddds)];
    $number = '9';
    for ($i = 0; $i < 8; $i++) {
        $number .= random_int(0, 9);
    }
    return '55' . $ddd . $number;
}

// Busca valor em array aninhado
function findFirst(array $arr, array $paths) {
    foreach ($paths as $path) {
        $parts = explode('.', $path);
        $cur = $arr;
        $ok = true;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
            } else {
                $ok = false;
                break;
            }
        }
        if ($ok && $cur !== null && $cur !== '' && $cur !== 'N/A') return $cur;
    }
    return null;
}

// ========================================
// GERA TESTE IPTV
// ========================================
function generateTest(string $apiUrl): array {
    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'cURL não disponível'];
    }

    $payload = json_encode([
        'appName' => 'com.whatsapp',
        'messageDateTime' => (string)time(),
        'devicePhone' => randomBrazilPhone(),
        'deviceName' => 'ChatBot',
        'senderPhone' => randomBrazilPhone(),
        'message' => 'teste iptv',
        'userAgent' => 'BotBot',
        'ip' => '127.0.0.1',
        'remoteIP' => '127.0.0.1',
        'clientIP' => '127.0.0.1'
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_USERAGENT => 'BotBot',
        CURLOPT_HTTPHEADER => [
            'User-Agent: BotBot',
            'X-Forwarded-For: 127.0.0.1',
            'X-Real-IP: 127.0.0.1',
            'Accept: application/json',
            'Content-Type: application/json',
            'Origin: https://botbot.chat',
            'Referer: https://botbot.chat/',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'Erro cURL: ' . $curlErr];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida (HTTP ' . $httpCode . ')'];
    }

    // Tenta múltiplos caminhos para encontrar os dados
    $username = findFirst($data, [
        'data.username','data.user','data.usuario','data.login',
        'username','user','usuario','login',
        'response.username','response.user',
        'result.username','result.user'
    ]);
    
    $password = findFirst($data, [
        'data.password','data.pass','data.senha','data.pwd',
        'password','pass','senha','pwd',
        'response.password','response.pass',
        'result.password','result.pass'
    ]);
    
    $m3u = findFirst($data, [
        'data.line','data.m3u','data.url','data.playlist','data.link',
        'line','m3u','url','playlist','link',
        'response.line','response.m3u','response.url',
        'result.line','result.m3u','result.url'
    ]);
    
    // Busca também por DNS/servidor
    $dns = findFirst($data, [
        'data.dns','data.host','data.server','data.domain',
        'dns','host','server','domain',
        'response.dns','response.host',
        'result.dns','result.host'
    ]);
    
    // Se não encontrou username/password, retorna com debug completo
    if (!$username || !$password) {
        return [
            'success' => false,
            'error' => 'Credenciais incompletas na resposta',
            'username' => $username ?? 'N/A',
            'password' => $password ?? 'N/A',
            'm3u' => $m3u ?? 'N/A',
            'dns' => $dns ?? 'N/A'
        ];
    }
    
    // Extrai servidor e porta do link M3U ou DNS
    $server = null;
    $port = 80;
    
    // Tenta extrair do M3U primeiro
    if ($m3u) {
        if (preg_match('#https?://([^:/]+)(?::(\d+))?#', $m3u, $matches)) {
            $server = $matches[1];
            if (isset($matches[2])) {
                $port = (int)$matches[2];
            }
        }
    }
    
    // Se não conseguiu do M3U, tenta do DNS
    if (!$server && $dns) {
        if (preg_match('#https?://([^:/]+)(?::(\d+))?#', $dns, $matches)) {
            $server = $matches[1];
            if (isset($matches[2])) {
                $port = (int)$matches[2];
            }
        } elseif (preg_match('#^([a-z0-9.-]+\.[a-z]{2,})#i', $dns, $matches)) {
            // DNS sem http://
            $server = $matches[1];
        }
    }
    
    // Se ainda não encontrou servidor, tenta no próprio M3U sem regex
    if (!$server && $m3u) {
        $parts = parse_url($m3u);
        if (isset($parts['host'])) {
            $server = $parts['host'];
            if (isset($parts['port'])) {
                $port = (int)$parts['port'];
            }
        }
    }
    
    // Se ainda não encontrou, usa valor padrão mas avisa
    if (!$server) {
        return [
            'success' => false,
            'error' => 'Não foi possível extrair servidor da resposta',
            'username' => $username,
            'password' => $password,
            'm3u' => $m3u ?? 'N/A',
            'dns' => $dns ?? 'N/A'
        ];
    }

    return [
        'success' => true,
        'username' => $username,
        'password' => $password,
        'server' => $server,
        'port' => $port,
        'm3u' => $m3u ?? null,
        'dns' => $dns ?? null
    ];
}
?>
