#!/usr/bin/env php
<?php
/**
 * Script CLI para atualizar cache
 * 
 * Uso: php update_cache.php
 * 
 * Este script pode ser executado via cron para manter o cache atualizado
 * Exemplo cron (atualizar todo domingo às 3am):
 * 0 3 * * 0 /usr/bin/php /caminho/para/update_cache.php
 */

require_once __DIR__ . '/config.php';

// Credenciais fixas para atualização automática
// Configure com uma conta válida do ODINPLAY
$SERVER = 'c.superodim.nl70.top'; // Ajuste conforme necessário
$PORT = 80;
$USERNAME = ''; // Coloque um username válido
$PASSWORD = ''; // Coloque uma senha válida

if (empty($USERNAME) || empty($PASSWORD)) {
    echo "❌ ERRO: Configure USERNAME e PASSWORD neste script antes de usar\n";
    exit(1);
}

$CACHE_DIR = __DIR__ . '/cache';
$MOVIES_CACHE = $CACHE_DIR . '/movies.json';
$SERIES_CACHE = $CACHE_DIR . '/series.json';
$CACHE_INFO = $CACHE_DIR . '/cache_info.json';

// Cria diretório se não existir
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0755, true);
}

function fetchAPI($action, $server, $port, $username, $password) {
    $url = "http://{$server}:{$port}/player_api.php?username={$username}&password={$password}&action={$action}";
    
    echo "Buscando: {$action}...\n";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        return json_decode($response, true);
    }
    
    return null;
}

echo "🎬 Atualizando cache do ODINPLAY...\n\n";

// Busca filmes
echo "📥 Buscando filmes...\n";
$movies = fetchAPI('get_vod_streams', $SERVER, $PORT, $USERNAME, $PASSWORD);

if ($movies && is_array($movies)) {
    $processed = [];
    foreach ($movies as $movie) {
        $id = $movie['stream_id'] ?? $movie['num'] ?? null;
        if (!$id) continue;
        
        $processed[] = [
            'id' => $id,
            'name' => $movie['name'] ?? 'Sem título',
            'container' => $movie['container_extension'] ?? 'mp4',
            'rating' => $movie['rating'] ?? null,
            'year' => $movie['year'] ?? null,
            'category_id' => $movie['category_id'] ?? null
        ];
    }
    
    file_put_contents($MOVIES_CACHE, json_encode($processed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "✅ {$movie_count} filmes salvos em movies.json\n\n";
} else {
    echo "❌ Falha ao buscar filmes\n\n";
}

// Busca séries
echo "📥 Buscando séries...\n";
$series = fetchAPI('get_series', $SERVER, $PORT, $USERNAME, $PASSWORD);

if ($series && is_array($series)) {
    $processed = [];
    foreach ($series as $s) {
        $id = $s['series_id'] ?? null;
        if (!$id) continue;
        
        $processed[] = [
            'id' => $id,
            'name' => $s['name'] ?? 'Sem título',
            'cover' => $s['cover'] ?? null,
            'rating' => $s['rating'] ?? null,
            'year' => $s['year'] ?? null,
            'category_id' => $s['category_id'] ?? null
        ];
    }
    
    file_put_contents($SERIES_CACHE, json_encode($processed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "✅ " . count($processed) . " séries salvas em series.json\n\n";
} else {
    echo "❌ Falha ao buscar séries\n\n";
}

// Atualiza info
$cache_info = [
    'movies' => [
        'count' => count($movies ?? []),
        'updated' => date('Y-m-d H:i:s')
    ],
    'series' => [
        'count' => count($series ?? []),
        'updated' => date('Y-m-d H:i:s')
    ]
];

file_put_contents($CACHE_INFO, json_encode($cache_info, JSON_PRETTY_PRINT));

echo "✅ Cache atualizado com sucesso!\n";
echo "📊 Total: " . count($movies ?? []) . " filmes, " . count($series ?? []) . " séries\n";
?>
