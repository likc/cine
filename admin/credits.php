<?php
require_once '../config.php';
require_once '../database.php';
require_once '../auth.php';

requireAdmin('../login_new.php');

$message = '';
$messageType = '';

// Processar adição de créditos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credits'])) {
    $userId = (int)$_POST['user_id'];
    $amount = (int)$_POST['amount'];
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if ($userId && $amount > 0) {
        if (addCredits($userId, $amount, getCurrentUserId(), $description)) {
            $message = "✅ $amount crédito(s) adicionado(s) com sucesso!";
            $messageType = 'success';
        } else {
            $message = 'Erro ao adicionar créditos.';
            $messageType = 'error';
        }
    } else {
        $message = 'Dados inválidos.';
        $messageType = 'error';
    }
}

// Buscar usuário por email
$selectedUser = null;
$userTransactions = [];
if (isset($_GET['user_id'])) {
    $selectedUser = getUserById((int)$_GET['user_id']);
    if ($selectedUser) {
        $userTransactions = getUserTransactions($selectedUser['id'], 50);
    }
}

$flash = getFlashMessage();
if ($flash) {
    $message = $flash['text'];
    $messageType = $flash['type'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Créditos - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>


<?php include 'nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>💎 Gerenciar Créditos</h1>
            <p>Adicione ou remova créditos dos usuários</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Buscar Usuário</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label>Email do Usuário</label>
                    <input type="email" name="user_email" placeholder="email@exemplo.com" required
                           style="max-width: 400px;">
                    <button type="submit" class="btn btn-small" style="margin-left: 10px;">🔍 Buscar</button>
                </div>
            </form>
        </div>
        
        <?php if (isset($_GET['user_email'])): ?>
            <?php
            $searchEmail = $_GET['user_email'];
            $selectedUser = getUserByEmail($searchEmail);
            
            if ($selectedUser):
                $userTransactions = getUserTransactions($selectedUser['id'], 50);
            ?>
                <div class="section">
                    <h2>Usuário Encontrado</h2>
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($selectedUser['name'] ?: 'Não informado'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedUser['email']); ?></p>
                        <p><strong>Créditos Atuais:</strong> <span style="font-size: 24px; color: #667eea;">💎 <?php echo number_format($selectedUser['credits']); ?></span></p>
                        <p><strong>Status:</strong> 
                            <?php if ($selectedUser['is_active']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <h3>Adicionar Créditos</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                        
                        <div class="form-group">
                            <label>Quantidade de Créditos</label>
                            <input type="number" name="amount" min="1" required placeholder="Ex: 50">
                        </div>
                        
                        <div class="form-group">
                            <label>Descrição (opcional)</label>
                            <input type="text" name="description" placeholder="Ex: Recarga mensal">
                        </div>
                        
                        <button type="submit" name="add_credits" class="btn">💎 Adicionar Créditos</button>
                    </form>
                </div>
                
                <?php if (!empty($userTransactions)): ?>
                    <div class="section">
                        <h2>📋 Histórico de Transações</h2>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userTransactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $types = [
                                                    'add' => '➕ Adição',
                                                    'remove' => '➖ Remoção',
                                                    'purchase' => '💰 Compra',
                                                    'admin' => '👨‍💼 Admin',
                                                    'view' => '📺 Visualização'
                                                ];
                                                echo $types[$transaction['type']] ?? $transaction['type'];
                                                ?>
                                            </td>
                                            <td>
                                                <strong style="color: <?php echo $transaction['amount'] > 0 ? '#28a745' : '#dc3545'; ?>">
                                                    <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount']); ?>
                                                </strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['description'] ?: '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="section">
                    <div class="alert alert-warning">
                        ⚠️ Nenhum usuário encontrado com o email <strong><?php echo htmlspecialchars($searchEmail); ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
