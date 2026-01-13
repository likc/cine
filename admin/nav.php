<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-nav">
    <div class="nav-brand">
        <h1>🎬 Admin Panel</h1>
    </div>
    <ul class="nav-menu">
        <li><a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">👥 Usuários</a></li>
        <li><a href="credits.php" class="<?php echo $currentPage === 'credits.php' ? 'active' : ''; ?>">💎 Créditos</a></li>
        <li><a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">⚙️ Configurações</a></li>
        <li><a href="logs.php" class="<?php echo $currentPage === 'logs.php' ? 'active' : ''; ?>">📋 Logs</a></li>
        <li><a href="../index_new.php">🏠 Site</a></li>
        <li><a href="../logout.php">🚪 Sair</a></li>
    </ul>
</nav>
