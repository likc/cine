<?php
/**
 * Página de Reprodução
 * Debita créditos, gera teste Odin e reproduz o conteúdo
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Verifica login
requireLogin('login_new.php');

$contentType = $_GET['type'] ?? 'movie'; // movie ou series
$contentId = $_GET['id'] ?? null;
$contentName = $_GET['name'] ?? 'Conteúdo';

if (!$contentId) {
    die('Conteúdo inválido');
}

$userId = getCurrentUserId();
$creditsNeeded = (int)getSetting('credits_per_view', 1);

// Verifica se tem créditos
if (!hasCredits($userId, $creditsNeeded)) {
    header('Location: index_new.php?error=no_credits');
    exit;
}

// ========================================
// GERA TESTE NA ODIN
// ========================================

$services = getServices();
$serviceIndex = (int)getSetting('odin_service_index', 0);
$selectedService = $services[$serviceIndex] ?? $services[0];

$testResult = generateTest($selectedService['url']);

if (!$testResult['success']) {
    // Mostra erro com mais detalhes para debug
    $errorMsg = 'Erro ao gerar acesso temporário.';
    if (isset($testResult['error'])) {
        $errorMsg .= '<br><br><strong>Detalhes:</strong> ' . htmlspecialchars($testResult['error']);
    }
    
    // Se é admin, mostra debug completo
    if (isAdmin()) {
        $errorMsg .= '<br><br><strong>Debug (Admin):</strong><br>';
        $errorMsg .= '<pre style="background:#f5f5f5;padding:10px;border-radius:5px;text-align:left;">';
        $errorMsg .= 'Serviço usado: ' . htmlspecialchars($selectedService['name']) . "\n";
        $errorMsg .= 'URL: ' . htmlspecialchars($selectedService['url']) . "\n\n";
        $errorMsg .= 'Resposta completa:' . "\n";
        $errorMsg .= print_r($testResult, true);
        $errorMsg .= '</pre>';
        $errorMsg .= '<br><a href="test_odin_services.php" style="color:#667eea;">🔧 Testar todos os serviços Odin</a>';
    } else {
        $errorMsg .= '<br><br>Entre em contato com o administrador.';
    }
    
    die('<html><head><meta charset="UTF-8"><title>Erro</title><style>body{font-family:Arial;padding:40px;background:#f5f5f5;}.error{background:white;padding:30px;border-radius:10px;max-width:800px;margin:0 auto;box-shadow:0 2px 10px rgba(0,0,0,0.1);}h1{color:#e50914;}</style></head><body><div class="error"><h1>⚠️ Erro</h1><p>' . $errorMsg . '</p><br><a href="index_new.php" style="color:#667eea;">← Voltar ao catálogo</a></div></body></html>');
}

$username = $testResult['username'];
$password = $testResult['password'];
$server = $testResult['server'];
$port = $testResult['port'];

// ========================================
// DEBITA CRÉDITOS
// ========================================

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Remove créditos
    removeCredits($userId, $creditsNeeded, "Assistiu: $contentName");
    
    // Registra log de acesso
    logAccess($userId, $contentType, $contentId, $contentName, $creditsNeeded, [
        'username' => $username,
        'password' => $password,
        'server' => $server
    ]);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    die('Erro ao processar. Tente novamente.');
}

// ========================================
// MONTA URL DO CONTEÚDO
// ========================================

if ($contentType === 'movie') {
    // Para filmes: /movie/{user}/{pass}/{stream_id}.{ext}
    $streamUrl = "http://{$server}:{$port}/movie/{$username}/{$password}/{$contentId}.mp4";
} else {
    // Para séries seria mais complexo (precisaria escolher temporada/episódio)
    // Por enquanto, vamos apenas montar a URL base
    $streamUrl = "http://{$server}:{$port}/series/{$username}/{$password}/{$contentId}.mp4";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .header {
            background: #1a1a1a;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            background: #e50914;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .btn:hover {
            background: #f40612;
        }
        
        .player-container {
            width: 100%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            background: #000;
            border-radius: 8px;
        }
        
        .video-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .info {
            background: #1a1a1a;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
        }
        
        .info h2 {
            margin-bottom: 10px;
            color: #e50914;
        }
        
        .info p {
            color: #999;
            line-height: 1.6;
        }
        
        .credits-info {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
    </style>
    
    <!-- Video.js para player melhor -->
    <link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
</head>
<body>
    <div class="header">
        <h1>🎬 <?php echo htmlspecialchars($contentName); ?></h1>
        <a href="index_new.php" class="btn">← Voltar ao Catálogo</a>
    </div>
    
    <div class="player-container">
        <div class="video-wrapper">
            <video
                id="my-video"
                class="video-js vjs-big-play-centered"
                controls
                preload="auto"
                data-setup='{}'
            >
                <source src="<?php echo htmlspecialchars($streamUrl); ?>" type="application/x-mpegURL">
                <p class="vjs-no-js">
                    Para assistir este vídeo, habilite JavaScript ou use um navegador que suporte HTML5.
                </p>
            </video>
        </div>
        
        <div class="info">
            <h2><?php echo htmlspecialchars($contentName); ?></h2>
            <p>
                Você gastou <strong><?php echo $creditsNeeded; ?> crédito(s)</strong> para assistir este conteúdo.<br>
                Aproveite!
            </p>
        </div>
        
        <?php
        $remainingCredits = getUserCredits($userId);
        if ($remainingCredits < 5):
        ?>
            <div class="credits-info">
                ⚠️ Você tem apenas <strong><?php echo $remainingCredits; ?> crédito(s)</strong> restante(s).<br>
                Entre em contato para adquirir mais créditos.
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Inicializa o player
        var player = videojs('my-video', {
            controls: true,
            autoplay: false,
            preload: 'auto',
            fluid: true,
            responsive: true
        });
        
        // Trata erros
        player.on('error', function() {
            var error = player.error();
            console.error('Erro no player:', error);
            alert('Erro ao carregar o vídeo. O teste pode ter expirado. Tente novamente.');
        });
        
        // Log quando começar a reproduzir
        player.on('play', function() {
            console.log('Reprodução iniciada');
        });
    </script>
</body>
</html>
