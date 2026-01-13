<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Se já está logado, redireciona
if (isLoggedIn()) {
    header('Location: index_new.php');
    exit;
}

// Verifica se cadastro está permitido
if (!checkRegistrationAllowed()) {
    die('Cadastros temporariamente desativados. Entre em contato com o administrador.');
}

$error = '';
$success = '';

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $name = sanitizeInput($_POST['name'] ?? '');
    
    // Validações
    if (empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Preencha todos os campos obrigatórios!';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido!';
    } elseif ($password !== $password_confirm) {
        $error = 'As senhas não coincidem!';
    } elseif (!isStrongPassword($password)) {
        $error = 'A senha deve ter no mínimo 6 caracteres!';
    } elseif (getUserByEmail($email)) {
        $error = 'Este email já está cadastrado!';
    } else {
        // Cria usuário
        $userId = createUser($email, $password, $name);
        
        if ($userId) {
            // Faz login automaticamente
            login($email, $password);
            redirectWithMessage('index_new.php', 'Conta criada com sucesso! Bem-vindo!', 'success');
        } else {
            $error = 'Erro ao criar conta. Tente novamente.';
        }
    }
}

// Pega mensagens flash
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') $success = $flash['text'];
    else $error = $flash['text'];
}

$initialCredits = getSetting('initial_credits', 10);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - <?php echo getSetting('site_name', 'IPTV Premium'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
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
        
        .credits-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .credits-info strong {
            color: #667eea;
            font-size: 20px;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="logo">
            <h1>🎬 <?php echo htmlspecialchars(getSetting('site_name', 'IPTV Premium')); ?></h1>
            <p>Crie sua conta grátis</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="credits-info">
            🎁 Ganhe <strong><?php echo $initialCredits; ?> créditos grátis</strong> ao criar sua conta!
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nome (opcional)</label>
                <input type="text" id="name" name="name" placeholder="Seu nome" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Senha *</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmar Senha *</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Digite a senha novamente" required>
            </div>
            
            <button type="submit" class="btn">Criar Conta Grátis</button>
        </form>
        
        <div class="links">
            Já tem uma conta? <a href="login.php">Fazer Login</a><br>
            <a href="index.php">← Voltar ao Catálogo</a>
        </div>
    </div>
</body>
</html>
