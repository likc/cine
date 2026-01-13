<?php
/**
 * Configuração do Banco de Dados e Funções Principais
 * 
 * IMPORTANTE: Configure as credenciais do seu banco abaixo!
 */

// ========================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ========================================
// ALTERE ESTAS CREDENCIAIS PARA AS SUAS!
define('DB_HOST', 'localhost');           // Geralmente 'localhost' na Hostgator
define('DB_NAME', 'minec761_cine');         // Nome do seu banco de dados
define('DB_USER', 'minec761_cine');         // Seu usuário MySQL
define('DB_PASS', 'csx19aqf1srx');           // Sua senha MySQL
define('DB_CHARSET', 'utf8mb4');

// ========================================
// CONEXÃO PDO
// ========================================
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erro de conexão com banco de dados. Verifique as configurações em database.php");
        }
    }
    
    return $pdo;
}

// ========================================
// FUNÇÕES DE CONFIGURAÇÕES
// ========================================

function getSetting($key, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

function setSetting($key, $value, $description = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value, description) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?, description = COALESCE(?, description)
    ");
    return $stmt->execute([$key, $value, $description, $value, $description]);
}

function getAllSettings() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
    return $stmt->fetchAll();
}

// ========================================
// FUNÇÕES DE USUÁRIOS
// ========================================

function getUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function createUser($email, $password, $name = null, $initialCredits = null) {
    $db = getDB();
    
    // Usa créditos configurados se não especificado
    if ($initialCredits === null) {
        $initialCredits = (int)getSetting('initial_credits', 10);
    }
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO users (email, password_hash, name, credits, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$email, $passwordHash, $name, $initialCredits]);
        
        $userId = $db->lastInsertId();
        
        // Registra créditos iniciais
        if ($initialCredits > 0) {
            addCreditTransaction($userId, $initialCredits, 'add', 'Créditos iniciais ao criar conta');
        }
        
        return $userId;
    } catch (PDOException $e) {
        return false;
    }
}

function updateUser($userId, $data) {
    $db = getDB();
    
    $allowed = ['email', 'name', 'credits', 'is_admin', 'is_active'];
    $updates = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed)) {
            $updates[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($updates)) return false;
    
    $values[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

function deleteUser($userId) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$userId]);
}

function getAllUsers($search = null, $orderBy = 'created_at', $order = 'DESC', $limit = null, $offset = 0) {
    $db = getDB();
    
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (email LIKE ? OR name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $allowedOrders = ['id', 'email', 'name', 'credits', 'created_at', 'last_login'];
    if (!in_array($orderBy, $allowedOrders)) $orderBy = 'created_at';
    
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    $sql .= " ORDER BY $orderBy $order";
    
    if ($limit) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countUsers($search = null) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (email LIKE ? OR name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// ========================================
// FUNÇÕES DE CRÉDITOS
// ========================================

function getUserCredits($userId) {
    $user = getUserById($userId);
    return $user ? (int)$user['credits'] : 0;
}

function addCredits($userId, $amount, $adminId = null, $description = null) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // Atualiza créditos do usuário
        $stmt = $db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
        
        // Registra transação
        addCreditTransaction($userId, $amount, 'admin', $description ?? 'Créditos adicionados manualmente', $adminId);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function removeCredits($userId, $amount, $description = null) {
    $db = getDB();
    
    // Verifica se tem créditos suficientes
    $currentCredits = getUserCredits($userId);
    if ($currentCredits < $amount) {
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        // Remove créditos
        $stmt = $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
        
        // Registra transação
        addCreditTransaction($userId, -$amount, 'remove', $description ?? 'Créditos removidos');
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function addCreditTransaction($userId, $amount, $type, $description = null, $adminId = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO credit_transactions (user_id, amount, type, description, admin_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    return $stmt->execute([$userId, $amount, $type, $description, $adminId]);
}

function getUserTransactions($userId, $limit = 50) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM credit_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// ========================================
// FUNÇÕES DE LOGS DE ACESSO
// ========================================

function logAccess($userId, $contentType, $contentId, $contentName, $creditsUsed, $testData = null) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO access_logs 
        (user_id, content_type, content_id, content_name, credits_used, 
         test_username, test_password, test_server, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([
        $userId,
        $contentType,
        $contentId,
        $contentName,
        $creditsUsed,
        $testData['username'] ?? null,
        $testData['password'] ?? null,
        $testData['server'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function getUserAccessLogs($userId, $limit = 50) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM access_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function getAllAccessLogs($limit = 100, $offset = 0) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT al.*, u.email, u.name 
        FROM access_logs al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

// ========================================
// FUNÇÕES DE ESTATÍSTICAS
// ========================================

function getStats() {
    $db = getDB();
    
    $stats = [];
    
    // Total de usuários
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Usuários ativos (logaram nos últimos 30 dias)
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['active_users'] = $stmt->fetchColumn();
    
    // Total de créditos em circulação
    $stmt = $db->query("SELECT SUM(credits) FROM users");
    $stats['total_credits'] = $stmt->fetchColumn() ?? 0;
    
    // Total de visualizações
    $stmt = $db->query("SELECT COUNT(*) FROM access_logs");
    $stats['total_views'] = $stmt->fetchColumn();
    
    // Visualizações hoje
    $stmt = $db->query("SELECT COUNT(*) FROM access_logs WHERE DATE(created_at) = CURDATE()");
    $stats['views_today'] = $stmt->fetchColumn();
    
    // Créditos gastos hoje
    $stmt = $db->query("SELECT SUM(credits_used) FROM access_logs WHERE DATE(created_at) = CURDATE()");
    $stats['credits_spent_today'] = $stmt->fetchColumn() ?? 0;
    
    // Novos usuários hoje
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $stmt->fetchColumn();
    
    // Conteúdo mais assistido
    $stmt = $db->query("
        SELECT content_name, content_type, COUNT(*) as views
        FROM access_logs
        GROUP BY content_id, content_name, content_type
        ORDER BY views DESC
        LIMIT 10
    ");
    $stats['top_content'] = $stmt->fetchAll();
    
    return $stats;
}

function getRevenueStats() {
    $db = getDB();
    
    $creditPrice = (float)getSetting('credit_price', 1.00);
    
    // Créditos comprados (transações de compra)
    $stmt = $db->query("
        SELECT 
            DATE(created_at) as date,
            SUM(amount) as credits
        FROM credit_transactions
        WHERE type = 'purchase'
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    
    $revenue = [];
    foreach ($stmt->fetchAll() as $row) {
        $revenue[] = [
            'date' => $row['date'],
            'credits' => $row['credits'],
            'revenue' => $row['credits'] * $creditPrice
        ];
    }
    
    return $revenue;
}

// ========================================
// FUNÇÃO DE AUTENTICAÇÃO
// ========================================

function authenticateUser($email, $password) {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return false;
    }
    
    if (!$user['is_active']) {
        return false;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    
    // Atualiza último login
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);
    
    return $user;
}

// ========================================
// FUNÇÕES DE VALIDAÇÃO
// ========================================

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isStrongPassword($password) {
    // Mínimo 6 caracteres (pode ajustar a regra)
    return strlen($password) >= 6;
}

?>
