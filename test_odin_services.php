<?php
/**
 * Teste de Serviços Odin
 * Testa todos os serviços disponíveis para ver qual está funcionando
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Precisa estar logado
requireLogin('login_new.php');

// Mostra aviso se não é admin
$isUserAdmin = isAdmin();
if (!$isUserAdmin) {
    echo '<div style="background:#fff3cd;color:#856404;padding:15px;margin:20px;border-radius:5px;text-align:center;">';
    echo '⚠️ <strong>Aviso:</strong> Você não é administrador, mas pode ver os testes. ';
    echo 'Para configurar o serviço, faça login como admin.';
    echo '</div>';
}

$testing = isset($_GET['test']);
$services = getServices();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Serviços Odin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .service {
            border: 2px solid #e0e0e0;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            position: relative;
        }
        
        .service.testing {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .service.success {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .service.error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .service-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .service-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #e0e0e0;
            color: #666;
        }
        
        .status-testing {
            background: #667eea;
            color: white;
        }
        
        .status-success {
            background: #28a745;
            color: white;
        }
        
        .status-error {
            background: #dc3545;
            color: white;
        }
        
        .service-details {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
            display: none;
        }
        
        .service-details.show {
            display: block;
        }
        
        .service-url {
            font-family: monospace;
            font-size: 12px;
            color: #999;
            word-break: break-all;
            margin-top: 5px;
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
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .summary.show {
            display: block;
        }
        
        .summary-item {
            display: inline-block;
            margin-right: 30px;
            font-size: 16px;
        }
        
        .summary-item strong {
            font-size: 24px;
            display: block;
        }
        
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste de Serviços Odin</h1>
        <p class="subtitle">Testando <?php echo count($services); ?> serviços para ver quais estão funcionando</p>
        
        <div class="summary" id="summary">
            <div class="summary-item">
                <strong id="totalTested">0</strong>
                <span>Testados</span>
            </div>
            <div class="summary-item" style="color: #28a745;">
                <strong id="totalSuccess">0</strong>
                <span>✅ Funcionando</span>
            </div>
            <div class="summary-item" style="color: #dc3545;">
                <strong id="totalError">0</strong>
                <span>❌ Com Erro</span>
            </div>
        </div>
        
        <button class="btn" onclick="startTest()" id="btnStart">
            🚀 Iniciar Teste em Todos
        </button>
        
        <a href="admin/settings.php" class="btn btn-secondary" style="margin-left: 10px;">
            ← Voltar para Configurações
        </a>
        
        <div id="services">
            <?php foreach ($services as $index => $service): ?>
                <div class="service" id="service-<?php echo $index; ?>">
                    <div class="service-header">
                        <span class="service-name">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </span>
                        <span class="service-status status-pending" id="status-<?php echo $index; ?>">
                            Aguardando...
                        </span>
                    </div>
                    <div class="service-url">
                        <?php echo htmlspecialchars($service['url']); ?>
                    </div>
                    <div class="service-details" id="details-<?php echo $index; ?>">
                        <div id="result-<?php echo $index; ?>"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        const services = <?php echo json_encode($services); ?>;
        let currentIndex = 0;
        let successCount = 0;
        let errorCount = 0;
        
        function updateSummary() {
            document.getElementById('totalTested').textContent = currentIndex;
            document.getElementById('totalSuccess').textContent = successCount;
            document.getElementById('totalError').textContent = errorCount;
            document.getElementById('summary').classList.add('show');
        }
        
        async function testService(index) {
            const service = services[index];
            const serviceDiv = document.getElementById('service-' + index);
            const statusSpan = document.getElementById('status-' + index);
            const detailsDiv = document.getElementById('details-' + index);
            const resultDiv = document.getElementById('result-' + index);
            
            // Marca como testando
            serviceDiv.classList.add('testing');
            statusSpan.className = 'service-status status-testing';
            statusSpan.innerHTML = '<span class="loading"></span> Testando...';
            
            try {
                const response = await fetch('test_odin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ url: service.url })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    serviceDiv.classList.remove('testing');
                    serviceDiv.classList.add('success');
                    statusSpan.className = 'service-status status-success';
                    statusSpan.textContent = '✅ Funcionando';
                    
                    resultDiv.innerHTML = `
                        <strong>✅ Sucesso!</strong><br>
                        Username: ${result.username}<br>
                        Password: ${result.password}<br>
                        Server: ${result.server}:${result.port}
                    `;
                    
                    successCount++;
                } else {
                    serviceDiv.classList.remove('testing');
                    serviceDiv.classList.add('error');
                    statusSpan.className = 'service-status status-error';
                    statusSpan.textContent = '❌ Erro';
                    
                    resultDiv.innerHTML = `
                        <strong>❌ Erro:</strong><br>
                        ${result.error || 'Erro desconhecido'}
                    `;
                    
                    errorCount++;
                }
                
                detailsDiv.classList.add('show');
            } catch (error) {
                serviceDiv.classList.remove('testing');
                serviceDiv.classList.add('error');
                statusSpan.className = 'service-status status-error';
                statusSpan.textContent = '❌ Erro';
                
                resultDiv.innerHTML = `<strong>❌ Erro:</strong><br>${error.message}`;
                detailsDiv.classList.add('show');
                
                errorCount++;
            }
            
            currentIndex++;
            updateSummary();
        }
        
        async function startTest() {
            document.getElementById('btnStart').disabled = true;
            document.getElementById('btnStart').innerHTML = '<span class="loading"></span> Testando...';
            
            currentIndex = 0;
            successCount = 0;
            errorCount = 0;
            updateSummary();
            
            // Testa todos os serviços sequencialmente
            for (let i = 0; i < services.length; i++) {
                await testService(i);
                // Pequeno delay entre testes
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            document.getElementById('btnStart').disabled = false;
            document.getElementById('btnStart').innerHTML = '🔄 Testar Novamente';
            
            alert(`Teste concluído!\n✅ ${successCount} funcionando\n❌ ${errorCount} com erro`);
        }
    </script>
</body>
</html>
