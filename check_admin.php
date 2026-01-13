<?php
/**
 * Diagnóstico do Painel Admin
 * Verifica o que está causando a página em branco
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico do Admin</h1>";
echo "<hr>";

// 1. Verificar arquivos básicos
echo "<h2>1. Verificando arquivos necessários...</h2>";

$files = [
    'config.php',
    'database.php',
    'auth.php',
    'admin/index.php',
    'admin/nav.php',
    'admin/admin_style.css'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NÃO EXISTE!<br>";
    }
}

echo "<hr>";

// 2. Testar requires
echo "<h2>2. Testando carregamento de arquivos...</h2>";

try {
    echo "Carregando config.php... ";
    require_once 'config.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "<br>";
}

try {
    echo "Carregando database.php... ";
    require_once 'database.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "<br>";
}

try {
    echo "Carregando auth.php... ";
    require_once 'auth.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 3. Verificar banco de dados
echo "<h2>3. Verificando banco de dados...</h2>";

try {
    $db = getDB();
    echo "✅ Conexão com banco OK<br>";
    
    // Testar query
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "✅ Query funciona - Total de usuários: $count<br>";
    
} catch (Exception $e) {
    echo "❌ ERRO no banco: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 4. Verificar login
echo "<h2>4. Verificando autenticação...</h2>";

if (isLoggedIn()) {
    echo "✅ Você está logado<br>";
    echo "User ID: " . getCurrentUserId() . "<br>";
    
    if (isAdmin()) {
        echo "✅ Você é ADMIN<br>";
    } else {
        echo "❌ Você NÃO é admin<br>";
    }
} else {
    echo "❌ Você NÃO está logado<br>";
}

echo "<hr>";

// 5. Verificar funções
echo "<h2>5. Verificando funções necessárias...</h2>";

$functions = [
    'getSetting',
    'getStats',
    'isLoggedIn',
    'isAdmin',
    'getCurrentUser'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✅ $func() existe<br>";
    } else {
        echo "❌ $func() NÃO EXISTE!<br>";
    }
}

echo "<hr>";

// 6. Testar getSetting
echo "<h2>6. Testando configurações...</h2>";

try {
    $siteName = getSetting('site_name', 'IPTV Premium');
    echo "✅ getSetting funciona<br>";
    echo "Nome do site: $siteName<br>";
} catch (Exception $e) {
    echo "❌ ERRO em getSetting: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 7. Testar getStats
echo "<h2>7. Testando estatísticas...</h2>";

try {
    $stats = getStats();
    echo "✅ getStats funciona<br>";
    echo "Total de usuários: " . $stats['total_users'] . "<br>";
    echo "Total de visualizações: " . $stats['total_views'] . "<br>";
} catch (Exception $e) {
    echo "❌ ERRO em getStats: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 8. Resultado final
echo "<h2>✅ Conclusão</h2>";

if (file_exists('admin/index.php')) {
    echo "<p><strong>Se todos os testes acima passaram, o problema pode ser:</strong></p>";
    echo "<ul>";
    echo "<li>❌ Você não é admin (use make_admin.php)</li>";
    echo "<li>❌ Há erro de sintaxe no admin/index.php</li>";
    echo "<li>❌ Falta algum require no admin/index.php</li>";
    echo "</ul>";
    
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li><a href='make_admin.php'>Tornar-se admin</a></li>";
    echo "<li><a href='test_admin_simple.php'>Testar admin simplificado</a></li>";
    echo "<li><a href='admin/index.php'>Tentar acessar admin normal</a></li>";
    echo "</ol>";
} else {
    echo "<p style='color: red;'><strong>❌ O arquivo admin/index.php não existe!</strong></p>";
    echo "<p>Faça upload da pasta admin/ completa.</p>";
}

?>
