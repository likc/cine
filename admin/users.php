<?php
require_once '../config.php';
require_once '../database.php';
require_once '../auth.php';

requireAdmin('../login_new.php');

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit_user') {
        $userId = (int)$_POST['user_id'];
        $data = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'credits' => (int)$_POST['credits'],
            'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (updateUser($userId, $data)) {
            $message = 'Usuário atualizado com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Erro ao atualizar usuário.';
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        if (deleteUser($userId)) {
            $message = 'Usuário deletado com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Erro ao deletar usuário.';
            $messageType = 'error';
        }
    }
}

// Paginação
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$users = getAllUsers($search, 'created_at', 'DESC', $perPage, $offset);
$totalUsers = countUsers($search);
$totalPages = ceil($totalUsers / $perPage);

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
    <title>Usuários - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

    <?php include 'nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>👥 Gerenciar Usuários</h1>
            <p>Total: <?php echo number_format($totalUsers); ?> usuário(s)</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <form method="GET" action="" style="margin-bottom: 20px;">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Buscar por email ou nome..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="max-width: 400px;">
                    <button type="submit" class="btn btn-small" style="margin-left: 10px;">🔍 Buscar</button>
                    <?php if ($search): ?>
                        <a href="users.php" class="btn btn-secondary btn-small">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Nome</th>
                            <th>Créditos</th>
                            <th>Status</th>
                            <th>Admin</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                    Nenhum usuário encontrado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name'] ?: '-'); ?></td>
                                    <td><strong><?php echo number_format($user['credits']); ?></strong></td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge badge-info">Admin</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-small" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            ✏️ Editar
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Tem certeza que deseja deletar este usuário?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">🗑️ Deletar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Edição -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
            <h2 style="margin-bottom: 20px;">Editar Usuário</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" id="edit_email" disabled style="background: #f5f5f5;">
                </div>
                
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="name" id="edit_name">
                </div>
                
                <div class="form-group">
                    <label>Créditos</label>
                    <input type="number" name="credits" id="edit_credits" min="0">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_admin" id="edit_is_admin">
                        Administrador
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        Ativo
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn">Salvar</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_credits').value = user.credits;
            document.getElementById('edit_is_admin').checked = user.is_admin == 1;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>
