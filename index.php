<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

checkMaintenanceMode();

$currentUser = getCurrentUser();
$userCredits = $currentUser ? $currentUser['credits'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('site_name', 'IPTV Premium'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #141414; color: #fff; }
        .header { background: linear-gradient(90deg, #000 0%, #1a1a1a 100%); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .logo h1 { font-size: 24px; color: #e50914; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .credits { background: #667eea; padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        .btn { padding: 10px 20px; background: #e50914; color: white; text-decoration: none; border-radius: 5px; font-weight: 500; border: none; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #f40612; }
        .btn-secondary { background: #333; }
        .btn-secondary:hover { background: #555; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px 20px; }
        
        .breadcrumb { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; font-size: 14px; color: #999; }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        
        #backButton { margin-bottom: 20px; }
        #backButton .btn { display: inline-flex; align-items: center; gap: 8px; font-size: 16px; padding: 12px 24px; }
        #backButton .btn:hover { transform: translateX(-3px); }
        
        .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .search-bar { flex: 1; min-width: 300px; }
        .search-bar input { width: 100%; padding: 12px 20px; border: 2px solid #333; border-radius: 5px; background: #1a1a1a; color: white; font-size: 16px; }
        .search-bar input:focus { outline: none; border-color: #667eea; }
        .category-filter { padding: 12px 20px; border: 2px solid #333; border-radius: 5px; background: #1a1a1a; color: white; cursor: pointer; font-size: 14px; }
        
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #333; }
        .tab { padding: 10px 20px; background: transparent; border: none; color: #999; cursor: pointer; font-size: 16px; }
        .tab.active { color: white; border-bottom: 3px solid #e50914; }
        
        .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; min-height: 400px; }
        .content-card { background: #1a1a1a; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.3s; position: relative; }
        .content-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .content-card img { width: 100%; height: 300px; object-fit: cover; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-card img.error { display: none; }
        .content-card .placeholder { width: 100%; height: 300px; display: none; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 14px; text-align: center; padding: 20px; }
        
        .content-info { padding: 15px; }
        .content-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .content-meta { font-size: 13px; color: #999; }
        .content-category { font-size: 12px; color: #667eea; margin-top: 5px; }
        
        .play-button { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(229, 9, 20, 0.9); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
        .content-card:hover .play-button { opacity: 1; }
        .play-button svg { fill: white; width: 24px; height: 24px; margin-left: 3px; }
        
        .season-list, .episode-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .season-card, .episode-card { background: #1a1a1a; padding: 20px; border-radius: 8px; cursor: pointer; transition: background 0.3s; border-left: 4px solid transparent; }
        .season-card:hover, .episode-card:hover { background: #2a2a2a; border-left-color: #e50914; }
        .season-card h3, .episode-card h3 { margin-bottom: 5px; color: #fff; }
        .season-card p, .episode-card p { color: #999; font-size: 14px; }
        
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 40px; }
        .pagination button { padding: 10px 20px; background: #333; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .pagination button:hover { background: #555; }
        .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination .page-info { padding: 10px 20px; color: #999; }
        
        .loading, .no-results { text-align: center; padding: 40px; color: #999; }
        .alert { padding: 15px 20px; border-radius: 5px; margin-bottom: 20px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a1a; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; text-align: center; }
        .modal-content h2 { margin-bottom: 15px; color: #e50914; }
        .modal-content p { margin-bottom: 20px; color: #ccc; }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo"><h1>🎬 <?php echo htmlspecialchars(getSetting('site_name', 'IPTV Premium')); ?></h1></div>
        <div class="user-info">
            <?php if (isLoggedIn()): ?>
                <div class="credits">💎 <?php echo $userCredits; ?> créditos</div>
                <span><?php echo htmlspecialchars($currentUser['email']); ?></span>
                <?php if (isAdmin()): ?><a href="admin/" class="btn btn-secondary">Admin</a><?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            <?php else: ?>
                <a href="login_new.php" class="btn btn-secondary">Entrar</a>
                <a href="register.php" class="btn">Criar Conta Grátis</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'no_credits'): ?>
            <div class="alert alert-warning">⚠️ Você não tem créditos suficientes!</div>
        <?php endif; ?>
        
        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info">
                ℹ️ <strong>Navegue gratuitamente!</strong> Para assistir, <a href="register.php" style="color: #0c5460; font-weight: bold;">crie uma conta grátis</a> e ganhe <?php echo getSetting('initial_credits', 10); ?> créditos!
            </div>
        <?php endif; ?>
        
        <div class="breadcrumb" id="breadcrumb" style="display: none;"></div>
        
        <div id="backButton" style="display: none; margin-bottom: 20px;">
            <button class="btn btn-secondary" onclick="goBack()">
                ← Voltar
            </button>
        </div>
        
        <div class="filters" id="filters">
            <div class="search-bar"><input type="text" id="searchInput" placeholder="🔍 Buscar..."></div>
            <select class="category-filter" id="categoryFilter"><option value="">Todas categorias</option></select>
            <button class="btn btn-secondary" onclick="search()">Buscar</button>
        </div>
        
        <div class="tabs" id="tabs">
            <button class="tab active" onclick="switchTab('movies')">🎬 Filmes</button>
            <button class="tab" onclick="switchTab('series')">📺 Séries</button>
        </div>
        
        <div id="contentGrid" class="content-grid"></div>
        <div id="loading" class="loading" style="display:none;">Carregando...</div>
        <div id="noResults" class="no-results" style="display:none;">Nenhum conteúdo encontrado</div>
        
        <div class="pagination" id="pagination" style="display:none;">
            <button onclick="previousPage()">← Anterior</button>
            <span class="page-info" id="pageInfo">Página 1</span>
            <button onclick="nextPage()">Próxima →</button>
        </div>
    </div>
    
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2>Assistir?</h2>
            <p id="modalText"></p>
            <div class="modal-buttons">
                <button class="btn" onclick="confirmWatch()">Sim</button>
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'movies';
        let allContent = [];
        let filteredContent = [];
        let categories = {};
        let currentPage = 1;
        let itemsPerPage = 20;
        let selectedContent = null;
        let currentView = 'list';
        let currentSeries = null;
        let currentSeriesData = null; // NOVO: armazena dados completos da série
        let currentSeason = null;
        
        const userLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        const userCredits = <?php echo $userCredits; ?>;
        const creditsPerView = <?php echo getSetting('credits_per_view', 1); ?>;
        
        loadContent();
        loadCategories();
        
        function switchTab(tab) {
            currentTab = tab;
            currentPage = 1;
            currentView = 'list';
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            updateBreadcrumb();
            loadContent();
        }
        
        async function loadCategories() {
            try {
                const moviesRes = await fetch('proxy_hls.php?action=api&api_action=get_vod_categories');
                const seriesRes = await fetch('proxy_hls.php?action=api&api_action=get_series_categories');
                
                const moviesCats = await moviesRes.json();
                const seriesCats = await seriesRes.json();
                
                if (Array.isArray(moviesCats)) {
                    moviesCats.forEach(cat => {
                        categories[cat.category_id] = cat.category_name;
                    });
                }
                
                if (Array.isArray(seriesCats)) {
                    seriesCats.forEach(cat => {
                        categories[cat.category_id] = cat.category_name;
                    });
                }
                
                console.log('Categorias carregadas:', Object.keys(categories).length);
            } catch (e) {
                console.error('Erro ao carregar categorias:', e);
            }
        }
        
        function getCategoryName(categoryId) {
            return categories[categoryId] || `Categoria ${categoryId}`;
        }
        
        function updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            const backButton = document.getElementById('backButton');
            let html = '';
            
            if (currentView === 'list') {
                breadcrumb.style.display = 'none';
                backButton.style.display = 'none';
            } else if (currentView === 'seasons') {
                breadcrumb.style.display = 'flex';
                backButton.style.display = 'block';
                html = `<a href="#" onclick="backToList(); return false;">📺 Séries</a> <span>›</span> <span>${currentSeries.name}</span>`;
            } else if (currentView === 'episodes') {
                breadcrumb.style.display = 'flex';
                backButton.style.display = 'block';
                html = `<a href="#" onclick="backToList(); return false;">📺 Séries</a> <span>›</span> <a href="#" onclick="showSeasons(currentSeries); return false;">${currentSeries.name}</a> <span>›</span> <span>Temporada ${currentSeason.season_number}</span>`;
            }
            
            breadcrumb.innerHTML = html;
        }
        
        function goBack() {
            if (currentView === 'episodes') {
                // Está nos episódios, volta para temporadas
                showSeasons(currentSeries);
            } else if (currentView === 'seasons') {
                // Está nas temporadas, volta para lista
                backToList();
            }
        }
        
        function backToList() {
            currentView = 'list';
            currentSeries = null;
            currentSeriesData = null;
            currentSeason = null;
            currentPage = 1;
            updateBreadcrumb();
            renderContent();
        }
        
        async function loadContent(searchTerm = '', category = '') {
            const grid = document.getElementById('contentGrid');
            const loading = document.getElementById('loading');
            const noResults = document.getElementById('noResults');
            const pagination = document.getElementById('pagination');
            
            grid.style.display = 'none';
            pagination.style.display = 'none';
            loading.style.display = 'block';
            noResults.style.display = 'none';
            
            let url = `cache_manager.php?action=get&type=${currentTab}`;
            if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
            
            try {
                const r = await fetch(url);
                const data = await r.json();
                
                loading.style.display = 'none';
                
                if (!data || data.error || data.length === 0) {
                    noResults.style.display = 'block';
                    return;
                }
                
                allContent = data;
                filteredContent = category ? data.filter(i => i.category_id == category) : data;
                
                extractCategories(data);
                renderContent();
            } catch (err) {
                loading.style.display = 'none';
                noResults.style.display = 'block';
                console.error(err);
            }
        }
        
        function extractCategories(data) {
            const cats = {};
            data.forEach(i => {
                if (i.category_id) {
                    cats[i.category_id] = (cats[i.category_id] || 0) + 1;
                }
            });
            
            const select = document.getElementById('categoryFilter');
            select.innerHTML = `<option value="">Todas categorias (${data.length})</option>`;
            
            Object.keys(cats).sort((a, b) => {
                const nameA = getCategoryName(a);
                const nameB = getCategoryName(b);
                return nameA.localeCompare(nameB);
            }).forEach(catId => {
                const option = document.createElement('option');
                option.value = catId;
                option.textContent = `${getCategoryName(catId)} (${cats[catId]})`;
                select.appendChild(option);
            });
        }
        
        function renderContent() {
            const grid = document.getElementById('contentGrid');
            const pagination = document.getElementById('pagination');
            
            if (currentView !== 'list') {
                grid.className = currentView === 'seasons' ? 'season-list' : 'episode-list';
                pagination.style.display = 'none';
                return;
            }
            
            grid.className = 'content-grid';
            
            if (filteredContent.length === 0) {
                document.getElementById('noResults').style.display = 'block';
                return;
            }
            
            const totalPages = Math.ceil(filteredContent.length / itemsPerPage);
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageItems = filteredContent.slice(start, end);
            
            grid.innerHTML = pageItems.map(item => {
                const name = escapeHtml(item.name);
                const year = item.year ? ` (${item.year})` : '';
                const rating = item.rating ? `⭐ ${item.rating}` : '';
                const categoryName = item.category_id ? getCategoryName(item.category_id) : '';
                
                // URL da cover - CORRIGIDO: filmes também têm cover!
                let coverUrl = item.cover || item.stream_icon || `https://via.placeholder.com/200x300/667eea/ffffff?text=${encodeURIComponent(name.substring(0, 20))}`;
                
                const onclick = currentTab === 'movies' 
                    ? `selectContent(${JSON.stringify(item).replace(/'/g, "&apos;")})`
                    : `showSeasons(${JSON.stringify(item).replace(/'/g, "&apos;")})`;
                
                return `
                    <div class="content-card" onclick='${onclick}'>
                        <img src="${coverUrl}" alt="${name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="placeholder">${name.substring(0, 30)}</div>
                        <div class="play-button">
                            <svg viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <div class="content-info">
                            <div class="content-title">${name}</div>
                            <div class="content-meta">${year} ${rating}</div>
                            ${categoryName ? `<div class="content-category">📁 ${categoryName}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
            grid.style.display = 'grid';
            
            if (totalPages > 1) {
                document.getElementById('pageInfo').textContent = `Página ${currentPage} de ${totalPages}`;
                pagination.style.display = 'flex';
                pagination.querySelectorAll('button')[0].disabled = currentPage === 1;
                pagination.querySelectorAll('button')[1].disabled = currentPage === totalPages;
            }
        }
        
        async function showSeasons(series) {
            currentSeries = series;
            currentView = 'seasons';
            updateBreadcrumb();
            
            const grid = document.getElementById('contentGrid');
            const loading = document.getElementById('loading');
            
            grid.style.display = 'none';
            
            // Se já tem dados, não precisa recarregar
            if (currentSeriesData && currentSeriesData.info && currentSeriesData.info.name === series.name) {
                console.log('Usando dados em cache da série'); // DEBUG
                renderSeasons();
                return;
            }
            
            loading.style.display = 'block';
            
            try {
                const res = await fetch(`proxy_hls.php?action=api&api_action=get_series_info&series_id=${series.id}`);
                const data = await res.json();
                
                loading.style.display = 'none';
                
                console.log('Dados da série:', data); // DEBUG
                
                if (!data || !data.seasons) {
                    alert('Não foi possível carregar as temporadas');
                    backToList();
                    return;
                }
                
                // CORREÇÃO: Armazena dados completos da série
                currentSeriesData = data;
                
                renderSeasons();
            } catch (err) {
                loading.style.display = 'none';
                console.error('Erro ao carregar série:', err);
                alert('Erro ao carregar temporadas: ' + err.message);
                backToList();
            }
        }
        
        function renderSeasons() {
            const grid = document.getElementById('contentGrid');
            
            grid.className = 'season-list';
            
            // Processa temporadas
            const seasons = Array.isArray(currentSeriesData.seasons) ? currentSeriesData.seasons : Object.values(currentSeriesData.seasons);
            
            console.log('Temporadas encontradas:', seasons); // DEBUG
            
            grid.innerHTML = seasons.map(season => {
                // Busca número da temporada em diferentes campos
                const seasonNum = season.season_number || season.season_num || season.id || season.name;
                const episodeCount = season.episode_count || (season.episodes ? season.episodes.length : 0);
                
                return `
                    <div class="season-card" onclick='showEpisodes("${seasonNum}")'>
                        <h3>📺 Temporada ${seasonNum}</h3>
                        <p>${episodeCount} episódios</p>
                        <p style="color: #667eea; margin-top: 10px;">Clique para ver episódios →</p>
                    </div>
                `;
            }).join('');
            
            grid.style.display = 'grid';
        }
        
        function showEpisodes(seasonNumber) {
            currentSeason = { season_number: seasonNumber };
            currentView = 'episodes';
            updateBreadcrumb();
            
            const grid = document.getElementById('contentGrid');
            grid.className = 'episode-list';
            
            // CORREÇÃO: Busca episódios nos dados armazenados
            let episodes = [];
            
            if (currentSeriesData) {
                // Converte seasonNumber para string para garantir compatibilidade
                const seasonKey = String(seasonNumber);
                
                console.log('Buscando temporada:', seasonKey); // DEBUG
                console.log('Estrutura de dados:', currentSeriesData); // DEBUG
                
                // Tenta diferentes estruturas de dados da API
                if (currentSeriesData.episodes) {
                    // Formato: { episodes: { "1": [...], "2": [...] } }
                    // Tenta com string e number
                    episodes = currentSeriesData.episodes[seasonKey] || 
                               currentSeriesData.episodes[seasonNumber] || [];
                    console.log('Episódios do formato 1:', episodes); // DEBUG
                } else if (currentSeriesData.seasons) {
                    // Formato: { seasons: [{ episodes: [...] }] }
                    const seasons = Array.isArray(currentSeriesData.seasons) ? 
                                    currentSeriesData.seasons : 
                                    Object.values(currentSeriesData.seasons);
                    
                    // Busca temporada comparando como string e number
                    const season = seasons.find(s => {
                        const sNum = s.season_number || s.season_num || s.id;
                        return String(sNum) === seasonKey || sNum == seasonNumber;
                    });
                    
                    console.log('Temporada encontrada:', season); // DEBUG
                    
                    if (season && season.episodes) {
                        episodes = Array.isArray(season.episodes) ? 
                                   season.episodes : 
                                   Object.values(season.episodes);
                    }
                }
            }
            
            console.log('Episódios encontrados:', episodes.length, episodes); // DEBUG
            
            if (!episodes || episodes.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                        <h3>😕 Nenhum episódio disponível</h3>
                        <p style="margin-top: 10px;">Esta temporada pode não ter episódios cadastrados ainda.</p>
                        <p style="margin-top: 5px;"><small>Tente outra temporada ou volte mais tarde</small></p>
                    </div>
                `;
                grid.style.display = 'grid';
                return;
            }
            
            grid.innerHTML = episodes.map(episode => {
                const epNum = episode.episode_num || episode.episode_number || episode.id;
                const title = episode.title || episode.name || `Episódio ${epNum}`;
                const duration = episode.info?.duration || episode.duration || '';
                
                return `
                    <div class="episode-card" onclick='selectEpisode(${JSON.stringify(episode).replace(/'/g, "&apos;")})'>
                        <h3>▶️ Episódio ${epNum}</h3>
                        <p><strong>${escapeHtml(title)}</strong></p>
                        ${duration ? `<p style="color: #999; margin-top: 5px;">${duration}</p>` : ''}
                        <p style="color: #e50914; margin-top: 10px; font-weight: 600;">Assistir →</p>
                    </div>
                `;
            }).join('');
            
            grid.style.display = 'grid';
        }
        
        function selectEpisode(episode) {
            if (!userLoggedIn) {
                window.location.href = 'login_new.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }
            
            if (userCredits < creditsPerView) {
                alert('Você não tem créditos suficientes!');
                return;
            }
            
            const epNum = episode.episode_num || episode.episode_number || episode.id;
            
            selectedContent = {
                id: episode.id,
                name: `${currentSeries.name} - S${currentSeason.season_number}E${epNum}`,
                type: 'episode'
            };
            
            document.getElementById('modalText').innerHTML = `
                <strong>${selectedContent.name}</strong><br>
                Custo: ${creditsPerView} crédito(s)<br>
                Restará: ${userCredits - creditsPerView}
            `;
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                renderContent();
                window.scrollTo(0, 0);
            }
        }
        
        function nextPage() {
            const totalPages = Math.ceil(filteredContent.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderContent();
                window.scrollTo(0, 0);
            }
        }
        
        function search() {
            const term = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            currentPage = 1;
            loadContent(term, category);
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
            
            document.getElementById('modalText').innerHTML = `
                <strong>${content.name}</strong><br>
                Custo: ${creditsPerView} crédito(s)<br>
                Restará: ${userCredits - creditsPerView}
            `;
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            selectedContent = null;
        }
        
        function confirmWatch() {
            if (!selectedContent) return;
            
            const type = selectedContent.type === 'episode' ? 'episode' : 'movie';
            window.location.href = `watch_new.php?type=${type}&id=${selectedContent.id}&name=${encodeURIComponent(selectedContent.name)}`;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        document.getElementById('searchInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') search();
        });
        
        document.getElementById('categoryFilter').addEventListener('change', search);
    </script>
</body>
</html>