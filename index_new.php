<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Verifica modo manutenção
checkMaintenanceMode();

$currentUser = getCurrentUser();
$userCredits = $currentUser ? $currentUser['credits'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('site_name', 'IPTV Premium'); ?> - Filmes e Séries Online</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #141414;
            color: #fff;
        }
        
        .header {
            background: linear-gradient(90deg, #000 0%, #1a1a1a 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo h1 {
            font-size: 24px;
            color: #e50914;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .credits {
            background: #667eea;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .btn {
            padding: 10px 20px;
            background: #e50914;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #f40612;
        }
        
        .btn-secondary {
            background: #333;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .search-bar {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #333;
            border-radius: 5px;
            background: #1a1a1a;
            color: white;
            font-size: 16px;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-bar button {
            padding: 12px 30px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
        }
        
        .tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }
        
        .tab.active {
            color: white;
            border-bottom: 3px solid #e50914;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .content-card {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .content-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: #333;
        }
        
        .content-info {
            padding: 15px;
        }
        
        .content-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .content-meta {
            font-size: 13px;
            color: #999;
        }
        
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(229, 9, 20, 0.9);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .content-card:hover .play-button {
            opacity: 1;
        }
        
        .play-button svg {
            fill: white;
            width: 24px;
            height: 24px;
            margin-left: 3px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .no-results {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .modal-content h2 {
            margin-bottom: 15px;
            color: #e50914;
        }
        
        .modal-content p {
            margin-bottom: 20px;
            color: #ccc;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>🎬 <?php echo htmlspecialchars(getSetting('site_name', 'IPTV Premium')); ?></h1>
        </div>
        <div class="user-info">
            <?php if (isLoggedIn()): ?>
                <div class="credits">💎 <?php echo $userCredits; ?> créditos</div>
                <span><?php echo htmlspecialchars($currentUser['email']); ?></span>
                <?php if (isAdmin()): ?>
                    <a href="admin/" class="btn btn-secondary">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            <?php else: ?>
                <a href="login_new.php" class="btn btn-secondary">Entrar</a>
                <a href="register.php" class="btn">Criar Conta Grátis</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'no_credits'): ?>
                <div class="alert alert-warning">
                    ⚠️ Você não tem créditos suficientes! Adquira mais créditos para assistir.
                </div>
            <?php elseif ($_GET['error'] === 'not_logged_in'): ?>
                <div class="alert alert-info">
                    ℹ️ Faça login ou crie uma conta grátis para assistir!
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info">
                ℹ️ <strong>Navegue gratuitamente!</strong> Veja todo o catálogo. 
                Para assistir, <a href="register.php" style="color: #0c5460; font-weight: bold;">crie uma conta grátis</a> 
                e ganhe <?php echo getSetting('initial_credits', 10); ?> créditos!
            </div>
        <?php endif; ?>
        
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Buscar filmes e séries...">
            <button class="btn" onclick="search()">🔍 Buscar</button>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('movies')">🎬 Filmes</button>
            <button class="tab" onclick="switchTab('series')">📺 Séries</button>
        </div>
        
        <div id="contentGrid" class="content-grid"></div>
        <div id="loading" class="loading" style="display: none;">
            Carregando...
        </div>
        <div id="noResults" class="no-results" style="display: none;">
            Nenhum conteúdo encontrado
        </div>
    </div>
    
    <!-- Modal de confirmação -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2>Assistir este conteúdo?</h2>
            <p id="modalText"></p>
            <div class="modal-buttons">
                <button class="btn" onclick="confirmWatch()">Sim, assistir</button>
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'movies';
        let currentContent = [];
        let selectedContent = null;
        
        const userLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        const userCredits = <?php echo $userCredits; ?>;
        const creditsPerView = <?php echo getSetting('credits_per_view', 1); ?>;
        
        // Carrega conteúdo inicial
        loadContent();
        
        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            loadContent();
        }
        
        function loadContent(searchTerm = '') {
            const grid = document.getElementById('contentGrid');
            const loading = document.getElementById('loading');
            const noResults = document.getElementById('noResults');
            
            grid.style.display = 'none';
            loading.style.display = 'block';
            noResults.style.display = 'none';
            
            fetch(`cache_manager.php?action=get&type=${currentTab}&search=${encodeURIComponent(searchTerm)}`)
                .then(r => r.json())
                .then(data => {
                    loading.style.display = 'none';
                    
                    if (!data || data.length === 0) {
                        noResults.style.display = 'block';
                        return;
                    }
                    
                    currentContent = data;
                    renderContent(data);
                })
                .catch(err => {
                    loading.style.display = 'none';
                    noResults.style.display = 'block';
                    console.error('Erro ao carregar:', err);
                });
        }
        
        function renderContent(items) {
            const grid = document.getElementById('contentGrid');
            grid.innerHTML = '';
            grid.style.display = 'grid';
            
            items.forEach(item => {
                const card = document.createElement('div');
                card.className = 'content-card';
                card.onclick = () => selectContent(item);
                
                card.innerHTML = `
                    <img src="https://via.placeholder.com/200x300/333/999?text=${encodeURIComponent(item.name.substring(0, 20))}" 
                         alt="${item.name}">
                    <div class="play-button">
                        <svg viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </div>
                    <div class="content-info">
                        <div class="content-title">${item.name}</div>
                        <div class="content-meta">
                            ${item.year || 'N/A'} ${item.rating ? '⭐ ' + item.rating : ''}
                        </div>
                    </div>
                `;
                
                grid.appendChild(card);
            });
        }
        
        function selectContent(content) {
            selectedContent = content;
            
            if (!userLoggedIn) {
                window.location.href = 'login_new.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }
            
            if (userCredits < creditsPerView) {
                alert('Você não tem créditos suficientes!');
                return;
            }
            
            const modal = document.getElementById('confirmModal');
            const modalText = document.getElementById('modalText');
            
            modalText.innerHTML = `
                <strong>${content.name}</strong><br>
                Custo: ${creditsPerView} crédito(s)<br>
                Seus créditos após: ${userCredits - creditsPerView}
            `;
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            selectedContent = null;
        }
        
        function confirmWatch() {
            if (!selectedContent) return;
            
            const type = currentTab === 'movies' ? 'movie' : 'series';
            window.location.href = `watch.php?type=${type}&id=${selectedContent.id}&name=${encodeURIComponent(selectedContent.name)}`;
        }
        
        function search() {
            const term = document.getElementById('searchInput').value;
            loadContent(term);
        }
        
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') search();
        });
    </script>
</body>
</html>