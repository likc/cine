<?php
/**
 * API para testar serviços Odin
 * Retorna JSON com resultado do teste
 */

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Precisa estar logado
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Não autorizado. Faça login primeiro.']));
}

// Recebe a URL para testar
$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? null;

if (!$url) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'URL não fornecida']));
}

// Testa o serviço
$result = generateTest($url);

// Retorna resultado como JSON
header('Content-Type: application/json');
echo json_encode($result);
?>
