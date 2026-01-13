<?php
/**
 * Configurar Serviço Odin
 * Define o serviço ODINPLAY -18 que está funcionando
 */

require_once 'database.php';

// Atualiza para usar o serviço que está funcionando
// ODINPLAY - ODINPLAY -18 (índice 4)
setSetting('odin_service_index', '4', 'Serviço ODINPLAY -18 (funcionando)');

echo "✅ Configurado para usar: ODINPLAY - ODINPLAY -18<br>";
echo "✅ Server: c.superodim.nl70.top:80<br><br>";
echo "Agora tente assistir um filme!<br><br>";
echo '<a href="index_new.php">← Voltar ao catálogo</a>';
?>
