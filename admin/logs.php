<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php
require_once '../config.php';
require_once '../database.php';
require_once '../auth.php';

requireAdmin();

// Paginação
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$logs = getAllAccessLogs($perPage, $offset);

$db = getDB();
$stmt = $db->query("SELECT COUNT(*) FROM access_logs");
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);
?>

    <?php include 'nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>📋 Logs de Acesso</h1>
            <p>Total: <?php echo number_format($totalLogs); ?> registro(s)</p>
        </div>
        
        <div class="section">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Conteúdo</th>
                            <th>Tipo</th>
                            <th>Créditos</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    Nenhum log encontrado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($log['email']); ?>
                                        <?php if ($log['name']): ?>
                                            <br><small style="color: #999;"><?php echo htmlspecialchars($log['name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['content_name']); ?></td>
                                    <td>
                                        <?php if ($log['content_type'] === 'movie'): ?>
                                            🎬 Filme
                                        <?php elseif ($log['content_type'] === 'series'): ?>
                                            📺 Série
                                        <?php else: ?>
                                            📡 Live
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $log['credits_used']; ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = max(1, $page - 5); $i <= min($totalPages, $page + 5); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>📊 Estatísticas Rápidas</h2>
            <?php
            $stmt = $db->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as views,
                    SUM(credits_used) as credits
                FROM access_logs
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $weekStats = $stmt->fetchAll();
            ?>
            
            <?php if (!empty($weekStats)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Visualizações</th>
                            <th>Créditos Gastos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekStats as $stat): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($stat['date'])); ?></td>
                                <td><?php echo number_format($stat['views']); ?></td>
                                <td><strong><?php echo number_format($stat['credits']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999;">Nenhuma visualização nos últimos 7 dias</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
