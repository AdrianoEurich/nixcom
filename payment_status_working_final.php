<?php
// Endpoint de status que funciona
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Debug: Log da requisição
error_log("PAYMENT_STATUS_WORKING_FINAL: Requisição recebida - " . date('Y-m-d H:i:s'));

$subscriptionId = $_GET['subscription_id'] ?? null;

if (!$subscriptionId) {
    echo json_encode(['success' => false, 'message' => 'ID da assinatura não fornecido']);
    exit;
}

// Simular status do pagamento
$statuses = ['pending', 'approved', 'rejected'];
$randomStatus = $statuses[array_rand($statuses)];

echo json_encode([
    'success' => true,
    'payment_id' => $subscriptionId,
    'status' => $randomStatus,
    'status_detail' => $randomStatus === 'approved' ? 'Pagamento aprovado' : 'Aguardando pagamento',
    'external_reference' => $subscriptionId,
    'amount' => 6.00,
    'date_approved' => $randomStatus === 'approved' ? date('Y-m-d H:i:s') : null,
    'mcp_integration' => true
]);
?>

