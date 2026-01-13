<?php
/**
 * Tornar Usuário Admin
 * Use esta página para dar permissão de admin a um usuário
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Precisa estar logado
if (!isLoggedIn()) {
    die('Faça login primeiro!');
}

$message = '';
$messageType = '';

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_admin'])) {
    $email = sanitizeInput($_POST['email']);
    $user = getUserByEmail($email);
    
    if ($user) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
        if ($stmt->execute([$email])) {
            $message = "✅ Usuário $email agora é ADMIN!";
            $messageType = 'success';
            
            // Se é o próprio usuário, atualiza a sessão
            if (getCurrentUserId() == $user['id']) {
                $_SESSION['is_admin'] = true;
            }
        } else {
            $message = "❌ Erro ao atualizar usuário.";
            $messageType = 'error';
        }
    } else {
        $message = "❌ Usuário não encontrado!";
        $messageType = 'error';
    }
}

// Pega informações do usuário atual
$currentUser = getCurrentUser();
$allUsers = getAllUsers();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tornar Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .current-user {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .current-user h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .user-info {
            margin: 5px 0;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-admin {
            background: #28a745;
            color: white;
        }
        
        .badge-user {
            background: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .users-list {
            margin-top: 30px;
            border-top: 2px solid #e0e0e0;
            padding-top: 20px;
        }
        
        .user-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👑 Tornar Administrador</h1>
        <p class="subtitle">Dê permissão de admin para acessar o painel</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="current-user">
            <h3>Seu Usuário Atual:</h3>
            <div class="user-info">
                <strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?>
                <?php if ($currentUser['is_admin']): ?>
                    <span class="badge badge-admin">ADMIN</span>
                <?php else: ?>
                    <span class="badge badge-user">USUÁRIO</span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <strong>Nome:</strong> <?php echo htmlspecialchars($currentUser['name'] ?: 'Não informado'); ?>
            </div>
            <div class="user-info">
                <strong>Créditos:</strong> <?php echo $currentUser['credits']; ?>
            </div>
        </div>
        
        <?php if (!$currentUser['is_admin']): ?>
            <div class="alert alert-info">
                ℹ️ <strong>Você não é admin ainda.</strong><br>
                Use o formulário abaixo para se tornar admin ou tornar outro usuário admin.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                ✅ <strong>Você já é admin!</strong><br>
                Agora pode acessar o painel admin e a página de teste de serviços.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Selecione o usuário para tornar ADMIN:</label>
                <select name="email" required>
                    <option value="">-- Escolha um usuário --</option>
                    <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo htmlspecialchars($user['email']); ?>"
                                <?php echo $user['id'] == $currentUser['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['email']); ?>
                            <?php if ($user['name']): ?>
                                (<?php echo htmlspecialchars($user['name']); ?>)
                            <?php endif; ?>
                            <?php if ($user['is_admin']): ?>
                                - JÁ É ADMIN
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="make_admin" class="btn">
                👑 Tornar Administrador
            </button>
        </form>
        
        <div class="users-list">
            <h3>Todos os Usuários:</h3>
            <?php foreach ($allUsers as $user): ?>
                <div class="user-item">
                    <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                    <?php if ($user['is_admin']): ?>
                        <span class="badge badge-admin">ADMIN</span>
                    <?php else: ?>
                        <span class="badge badge-user">USUÁRIO</span>
                    <?php endif; ?>
                    <br>
                    <small>Créditos: <?php echo $user['credits']; ?> | 
                    Criado: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="back-link">
            <?php if (isAdmin()): ?>
                <a href="admin/">→ Ir para Painel Admin</a> |
            <?php endif; ?>
            <a href="test_odin_services.php">→ Testar Serviços Odin</a> |
            <a href="index_new.php">← Voltar ao Catálogo</a>
        </div>
    </div>
</body>
</html>
