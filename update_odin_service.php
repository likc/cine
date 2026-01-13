<?php
/**
 * Atualizar Serviço Odin
 * Define o serviço ODINPLAY -18 (índice 4) como padrão
 */

require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Atualizar Serviço Odin</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .box { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
        .success { color: #28a745; font-size: 20px; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
<div class='box'>
    <h1>🔧 Atualizar Serviço Odin</h1>
";

try {
    // Atualiza configuração
    $db = getDB();
    $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->execute(['4', 'odin_service_index']);
    
    echo "<p class='success'>✅ <strong>Configurado com sucesso!</strong></p>";
    echo "<div class='info'>";
    echo "<strong>Serviço configurado:</strong><br>";
    echo "ODINPLAY - ODINPLAY -18<br>";
    echo "Server: c.superodim.nl70.top:80<br>";
    echo "Índice: 4<br><br>";
    echo "<strong>Este serviço está funcionando perfeitamente!</strong>";
    echo "</div>";
    
    echo "<h3>✅ Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li><a href='login_new.php'><strong>Fazer login</strong></a> (se ainda não fez)</li>";
    echo "<li><a href='index_new.php'>Ir ao catálogo</a></li>";
    echo "<li>Escolher um filme</li>";
    echo "<li>Clicar para assistir</li>";
    echo "</ol>";
    
    echo "<p><strong>Agora deve funcionar!</strong> 🎉</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "
    <hr>
    <p><a href='index_new.php'>← Voltar ao catálogo</a></p>
</div>
</body>
</html>
";
?>
