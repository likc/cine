<?php
require_once '../config.php';
require_once '../database.php';
require_once '../auth.php';

requireAdmin('../index_new.php');

$currentUser = getCurrentUser();

// Processa ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_movies'])) {
        $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../cache_manager.php?action=update&type=movies');
        $data = json_decode($result, true);
        
        if ($data && $data['success']) {
            $message = '✅ Cache de filmes atualizado! Total: ' . ($data['movies_count'] ?? 0) . ' filmes';
            $messageType = 'success';
        } else {
            $message = '❌ Erro ao atualizar filmes: ' . ($data['errors'][0] ?? 'Erro desconhecido');
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['update_series'])) {
        $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../cache_manager.php?action=update&type=series');
        $data = json_decode($result, true);
        
        if ($data && $data['success']) {
            $message = '✅ Cache de séries atualizado! Total: ' . ($data['series_count'] ?? 0) . ' séries';
            $messageType = 'success';
        } else {
            $message = '❌ Erro ao atualizar séries: ' . ($data['errors'][0] ?? 'Erro desconhecido');
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['update_all'])) {
        $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../cache_manager.php?action=update&type=all');
        $data = json_decode($result, true);
        
        if ($data && $data['success']) {
            $moviesCount = $data['movies_count'] ?? 0;
            $seriesCount = $data['series_count'] ?? 0;
            $message = "✅ Cache completo atualizado!<br>Filmes: $moviesCount | Séries: $seriesCount";
            $messageType = 'success';
        } else {
            $errors = implode('<br>', $data['errors'] ?? ['Erro desconhecido']);
            $message = '❌ Erro ao atualizar: ' . $errors;
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['clear_cache'])) {
        $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../cache_manager.php?action=clear');
        $data = json_decode($result, true);
        
        if ($data && $data['success']) {
            $message = '✅ Cache limpo com sucesso!';
            $messageType = 'success';
        } else {
            $message = '❌ Erro ao limpar cache';
            $messageType = 'error';
        }
    }
}

// Busca status do cache
$statusResult = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../cache_manager.php?action=status');
$cacheStatus = json_decode($statusResult, true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cache - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .cache-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .cache-card h3 {
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cache-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .info-value.small {
            font-size: 14px;
        }
        
        .buttons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .btn-cache {
            padding: 15px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cache:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-cache:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #333;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-empty {
            background: #fff3cd;
            color: #856404;
        }
        
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .loading.active {
            display: flex;
        }
        
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .note {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .note strong {
            color: #1976D2;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="header-section">
            <h1>🗄️ Gerenciar Cache</h1>
            <p>Atualize o cache de filmes e séries da API IPTV</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Status do Cache de Filmes -->
        <div class="cache-card">
            <h3>
                🎬 Cache de Filmes
                <?php if ($cacheStatus['movies']['exists']): ?>
                    <span class="status-badge status-ok">Ativo</span>
                <?php else: ?>
                    <span class="status-badge status-empty">Vazio</span>
                <?php endif; ?>
            </h3>
            
            <div class="cache-info">
                <div class="info-item">
                    <div class="info-label">Total de Filmes</div>
                    <div class="info-value"><?php echo number_format($cacheStatus['movies']['count'] ?? 0); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Tamanho do Arquivo</div>
                    <div class="info-value small">
                        <?php 
                        $size = $cacheStatus['movies']['size'] ?? 0;
                        echo $size > 0 ? round($size / 1024 / 1024, 2) . ' MB' : 'N/A';
                        ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Última Atualização</div>
                    <div class="info-value small">
                        <?php 
                        $updated = $cacheStatus['movies']['updated'] ?? null;
                        echo $updated ? date('d/m/Y H:i', strtotime($updated)) : 'Nunca';
                        ?>
                    </div>
                </div>
            </div>
            
            <form method="POST" onsubmit="showLoading()">
                <button type="submit" name="update_movies" class="btn-cache btn-primary">
                    🔄 Atualizar Cache de Filmes
                </button>
            </form>
        </div>
        
        <!-- Status do Cache de Séries -->
        <div class="cache-card">
            <h3>
                📺 Cache de Séries
                <?php if ($cacheStatus['series']['exists']): ?>
                    <span class="status-badge status-ok">Ativo</span>
                <?php else: ?>
                    <span class="status-badge status-empty">Vazio</span>
                <?php endif; ?>
            </h3>
            
            <div class="cache-info">
                <div class="info-item">
                    <div class="info-label">Total de Séries</div>
                    <div class="info-value"><?php echo number_format($cacheStatus['series']['count'] ?? 0); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Tamanho do Arquivo</div>
                    <div class="info-value small">
                        <?php 
                        $size = $cacheStatus['series']['size'] ?? 0;
                        echo $size > 0 ? round($size / 1024 / 1024, 2) . ' MB' : 'N/A';
                        ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Última Atualização</div>
                    <div class="info-value small">
                        <?php 
                        $updated = $cacheStatus['series']['updated'] ?? null;
                        echo $updated ? date('d/m/Y H:i', strtotime($updated)) : 'Nunca';
                        ?>
                    </div>
                </div>
            </div>
            
            <form method="POST" onsubmit="showLoading()">
                <button type="submit" name="update_series" class="btn-cache btn-success">
                    🔄 Atualizar Cache de Séries
                </button>
            </form>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="cache-card">
            <h3>⚡ Ações Rápidas</h3>
            
            <form method="POST" onsubmit="showLoading()">
                <div class="buttons-grid">
                    <button type="submit" name="update_all" class="btn-cache btn-warning">
                        🚀 Atualizar Tudo
                    </button>
                    
                    <button type="submit" name="clear_cache" class="btn-cache btn-danger" 
                            onclick="return confirm('Tem certeza que deseja limpar TODO o cache?\n\nIsso removerá todos os dados e será necessário atualizar novamente.')">
                        🗑️ Limpar Cache
                    </button>
                </div>
            </form>
            
            <div class="note">
                <strong>ℹ️ Nota:</strong> 
                A atualização do cache busca os dados mais recentes da API IPTV. 
                Este processo pode levar alguns minutos dependendo da quantidade de conteúdo.
                Recomenda-se atualizar o cache semanalmente ou quando houver novos conteúdos.
            </div>
        </div>
        
        <!-- Informação sobre Covers -->
        <div class="cache-card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3>⚠️ Sobre Covers de Filmes</h3>
            <p style="margin-bottom: 10px;">
                <strong>A API IPTV não fornece covers (capas) para filmes VOD.</strong><br>
                Apenas séries têm covers disponíveis.
            </p>
            <p style="color: #856404;">
                Para adicionar covers aos filmes, seria necessário integrar com uma API externa como TMDB (The Movie Database).
                Esta funcionalidade pode ser implementada no futuro.
            </p>
        </div>
    </div>
    
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>
    
    <script>
        function showLoading() {
            document.getElementById('loading').classList.add('active');
            return true;
        }
        
        // Auto-esconde mensagens depois de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
