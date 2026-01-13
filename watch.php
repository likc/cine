<?php
/**
 * Página de Reprodução - CORRIGIDO
 * Versão simplificada com player HTML5 nativo
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

requireLogin('login_new.php');

$contentType = $_GET['type'] ?? 'movie'; // movie ou episode
$contentId = $_GET['id'] ?? null;
$contentName = $_GET['name'] ?? 'Conteúdo';

if (!$contentId) die('Conteúdo inválido');

$userId = getCurrentUserId();
$creditsNeeded = (int)getSetting('credits_per_view', 1);

if (!hasCredits($userId, $creditsNeeded)) {
    header('Location: index_new.php?error=no_credits');
    exit;
}

if (!removeCredits($userId, $creditsNeeded, "Assistiu: $contentName")) {
    die('Erro ao processar pagamento.');
}

logAccess($userId, $contentType, $contentId, $contentName, $creditsNeeded, ['method' => 'proxy']);

// Define URL do proxy conforme o tipo
if ($contentType === 'episode') {
    $proxyUrl = "proxy_hls.php?action=episode&id={$contentId}";
} else {
    $proxyUrl = "proxy_hls.php?action=movie&id={$contentId}";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentName); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .header { background: #1a1a1a; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; }
        .btn { padding: 10px 20px; background: #e50914; color: white; text-decoration: none; border-radius: 5px; font-weight: 500; }
        .btn:hover { background: #f40612; }
        .player-container { width: 100%; max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; border-radius: 8px; }
        .video-wrapper video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .info { background: #1a1a1a; padding: 20px; margin-top: 20px; border-radius: 8px; }
        .info h2 { margin-bottom: 10px; color: #e50914; }
        .info p { color: #999; line-height: 1.6; }
        .credits-info { background: #667eea; color: white; padding: 15px 20px; border-radius: 5px; margin-top: 20px; text-align: center; }
        .status { background: #2a2a2a; padding: 10px 15px; border-radius: 5px; font-size: 14px; margin-top: 10px; }
        .status.loading { color: #fbbf24; }
        .status.ready { color: #10b981; }
        .status.error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎬 <?php echo htmlspecialchars($contentName); ?></h1>
        <a href="index_new.php" class="btn">← Voltar</a>
    </div>
    
    <div class="player-container">
        <div class="video-wrapper">
            <video id="video-player" controls preload="auto"></video>
        </div>
        
        <div class="status loading" id="status">⏳ Carregando...</div>
        
        <div class="info">
            <h2><?php echo htmlspecialchars($contentName); ?></h2>
            <p>Você gastou <strong><?php echo $creditsNeeded; ?> crédito(s)</strong> para assistir.</p>
        </div>
        
        <?php if (getUserCredits($userId) < 5): ?>
            <div class="credits-info">
                ⚠️ Você tem apenas <strong><?php echo getUserCredits($userId); ?> crédito(s)</strong> restante(s).
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const player = document.getElementById('video-player');
        const status = document.getElementById('status');
        
        player.src = '<?php echo $proxyUrl; ?>';
        
        player.addEventListener('loadstart', () => status.innerHTML = '⏳ Carregando...');
        player.addEventListener('canplay', () => { status.innerHTML = '✅ Pronto'; status.className = 'status ready'; });
        player.addEventListener('playing', () => { status.innerHTML = '▶️ Reproduzindo'; status.className = 'status ready'; });
        player.addEventListener('pause', () => status.innerHTML = '⏸ Pausado');
        player.addEventListener('waiting', () => status.innerHTML = '⏳ Buffering...');
        player.addEventListener('error', (e) => {
            const err = player.error;
            let msg = '❌ Erro ao carregar';
            if (err) {
                switch(err.code) {
                    case 1: msg = '❌ Download abortado'; break;
                    case 2: msg = '❌ Erro de rede'; break;
                    case 3: msg = '❌ Erro ao decodificar'; break;
                    case 4: msg = '❌ Formato não suportado - Tente outro navegador'; break;
                }
            }
            status.innerHTML = msg;
            status.className = 'status error';
            console.error('Player error:', err);
        });
        
        player.play().catch(() => status.innerHTML = '⏸ Clique em Play');
    </script>
</body>
</html>