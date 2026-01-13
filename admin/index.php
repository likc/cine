<?php
require_once '../config.php';
require_once '../database.php';
require_once '../auth.php';

// Apenas admins podem acessar
requireAdmin('../login_new.php');

$stats = getStats();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - <?php echo getSetting('site_name'); ?></title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

    <?php include 'nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>📊 Dashboard</h1>
            <p>Visão geral do sistema</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total de Usuários</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['active_users']); ?></h3>
                    <p>Usuários Ativos (30 dias)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💎</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_credits']); ?></h3>
                    <p>Créditos em Circulação</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📺</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_views']); ?></h3>
                    <p>Total de Visualizações</p>
                </div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-icon">🔥</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['views_today']); ?></h3>
                    <p>Visualizações Hoje</p>
                </div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['credits_spent_today']); ?></h3>
                    <p>Créditos Gastos Hoje</p>
                </div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-icon">🆕</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['new_users_today']); ?></h3>
                    <p>Novos Usuários Hoje</p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>🎬 Conteúdo Mais Assistido</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Visualizações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['top_content'])): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #999;">
                                    Nenhuma visualização ainda
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['top_content'] as $content): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($content['content_name']); ?></td>
                                    <td>
                                        <?php if ($content['content_type'] === 'movie'): ?>
                                            🎬 Filme
                                        <?php else: ?>
                                            📺 Série
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($content['views']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="section">
            <h2>⚡ Ações Rápidas</h2>
            <div class="quick-actions">
                <a href="users.php" class="action-btn">
                    <span>👥</span>
                    Gerenciar Usuários
                </a>
                <a href="credits.php" class="action-btn">
                    <span>💎</span>
                    Adicionar Créditos
                </a>
                <a href="settings.php" class="action-btn">
                    <span>⚙️</span>
                    Configurações
                </a>
                <a href="logs.php" class="action-btn">
                    <span>📋</span>
                    Ver Logs
                </a>
            </div>
        </div>
    </div>
</body>
</html>
