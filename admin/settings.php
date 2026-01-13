<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php
require_once '../config.php';
require_once '../database.php';
require_once '../auth.php';

requireAdmin();

$message = '';
$messageType = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => sanitizeInput($_POST['site_name'] ?? ''),
        'initial_credits' => (int)($_POST['initial_credits'] ?? 0),
        'credits_per_view' => (int)($_POST['credits_per_view'] ?? 1),
        'allow_registration' => isset($_POST['allow_registration']) ? 1 : 0,
        'odin_service_index' => (int)($_POST['odin_service_index'] ?? 0),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'credit_price' => floatval($_POST['credit_price'] ?? 1.00)
    ];
    
    foreach ($settings as $key => $value) {
        setSetting($key, $value);
    }
    
    $message = '✅ Configurações salvas com sucesso!';
    $messageType = 'success';
}

// Carrega configurações atuais
$currentSettings = [];
foreach (getAllSettings() as $setting) {
    $currentSettings[$setting['setting_key']] = $setting['setting_value'];
}

$flash = getFlashMessage();
if ($flash) {
    $message = $flash['text'];
    $messageType = $flash['type'];
}
?>

    <?php include 'nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>⚙️ Configurações do Sistema</h1>
            <p>Configure o comportamento da plataforma</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="section">
                <h2>🏢 Informações Gerais</h2>
                
                <div class="form-group">
                    <label>Nome do Site</label>
                    <input type="text" name="site_name" 
                           value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? 'IPTV Premium'); ?>" 
                           required>
                    <small style="color: #666;">Nome que aparece no topo do site</small>
                </div>
            </div>
            
            <div class="section">
                <h2>💎 Configurações de Créditos</h2>
                
                <div class="form-group">
                    <label>Créditos Iniciais</label>
                    <input type="number" name="initial_credits" min="0" 
                           value="<?php echo $currentSettings['initial_credits'] ?? 10; ?>" 
                           required>
                    <small style="color: #666;">Quantidade de créditos que novos usuários recebem ao criar conta</small>
                </div>
                
                <div class="form-group">
                    <label>Créditos por Visualização</label>
                    <input type="number" name="credits_per_view" min="1" 
                           value="<?php echo $currentSettings['credits_per_view'] ?? 1; ?>" 
                           required>
                    <small style="color: #666;">Créditos gastos ao assistir um filme/série</small>
                </div>
                
                <div class="form-group">
                    <label>Preço por Crédito (R$)</label>
                    <input type="number" name="credit_price" min="0" step="0.01" 
                           value="<?php echo $currentSettings['credit_price'] ?? 1.00; ?>" 
                           required>
                    <small style="color: #666;">Preço unitário do crédito para cálculo de receita (não usado atualmente para compras automáticas)</small>
                </div>
            </div>
            
            <div class="section">
                <h2>🔐 Controle de Acesso</h2>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="allow_registration" 
                               <?php echo ($currentSettings['allow_registration'] ?? 1) == 1 ? 'checked' : ''; ?>>
                        Permitir Novos Cadastros
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Se desativado, apenas admins podem criar contas
                    </small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="maintenance_mode" 
                               <?php echo ($currentSettings['maintenance_mode'] ?? 0) == 1 ? 'checked' : ''; ?>>
                        Modo Manutenção
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Se ativado, apenas administradores podem acessar o site
                    </small>
                </div>
            </div>
            
            <div class="section">
                <h2>🌐 Configurações Odin</h2>
                
                <div class="form-group">
                    <label>Serviço Odin para Geração de Testes</label>
                    <select name="odin_service_index" required>
                        <?php
                        $services = getServices();
                        $selectedIndex = (int)($currentSettings['odin_service_index'] ?? 0);
                        foreach ($services as $index => $service):
                        ?>
                            <option value="<?php echo $index; ?>" <?php echo $index === $selectedIndex ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #666;">Qual serviço da Odin usar para gerar testes temporários</small>
                </div>
            </div>
            
            <div class="section">
                <button type="submit" class="btn" style="font-size: 18px;">
                    💾 Salvar Todas as Configurações
                </button>
            </div>
        </form>
        
        <div class="section">
            <h2>📊 Status do Cache</h2>
            <p style="margin-bottom: 15px;">Gerencie o cache de filmes e séries</p>
            <div style="display: flex; gap: 10px;">
                <button onclick="updateCache('movies')" class="btn">
                    🔄 Atualizar Cache de Filmes
                </button>
                <button onclick="updateCache('series')" class="btn">
                    🔄 Atualizar Cache de Séries
                </button>
                <button onclick="updateCache('all')" class="btn btn-success">
                    🔄 Atualizar Tudo
                </button>
            </div>
            <div id="cacheStatus" style="margin-top: 15px;"></div>
        </div>
    </div>
    
    <script>
        function updateCache(type) {
            const statusDiv = document.getElementById('cacheStatus');
            statusDiv.innerHTML = '<div class="alert alert-info">⏳ Atualizando cache... Isso pode demorar alguns minutos.</div>';
            
            fetch(`../cache_manager.php?action=update&type=${type}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let msg = '✅ Cache atualizado com sucesso!<br>';
                        if (data.movies_count) msg += `Filmes: ${data.movies_count}<br>`;
                        if (data.series_count) msg += `Séries: ${data.series_count}`;
                        statusDiv.innerHTML = `<div class="alert alert-success">${msg}</div>`;
                    } else {
                        statusDiv.innerHTML = `<div class="alert alert-error">❌ Erro: ${data.errors.join(', ')}</div>`;
                    }
                })
                .catch(err => {
                    statusDiv.innerHTML = '<div class="alert alert-error">❌ Erro ao atualizar cache</div>';
                    console.error(err);
                });
        }
        
        // Carrega status do cache ao abrir a página
        fetch('../cache_manager.php?action=status')
            .then(r => r.json())
            .then(data => {
                let html = '<table class="data-table"><thead><tr><th>Tipo</th><th>Status</th><th>Quantidade</th><th>Última Atualização</th></tr></thead><tbody>';
                
                html += '<tr>';
                html += '<td>🎬 Filmes</td>';
                html += `<td>${data.movies.exists ? '<span class="badge badge-success">Disponível</span>' : '<span class="badge badge-danger">Não disponível</span>'}</td>`;
                html += `<td>${data.movies.count || 0}</td>`;
                html += `<td>${data.movies.updated || 'Nunca'}</td>`;
                html += '</tr>';
                
                html += '<tr>';
                html += '<td>📺 Séries</td>';
                html += `<td>${data.series.exists ? '<span class="badge badge-success">Disponível</span>' : '<span class="badge badge-danger">Não disponível</span>'}</td>`;
                html += `<td>${data.series.count || 0}</td>`;
                html += `<td>${data.series.updated || 'Nunca'}</td>`;
                html += '</tr>';
                
                html += '</tbody></table>';
                document.getElementById('cacheStatus').innerHTML = html;
            });
    </script>
</body>
</html>
