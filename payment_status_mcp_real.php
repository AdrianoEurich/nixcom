<?php
session_start();

// Headers para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Debug: Log da requisição
error_log("PAYMENT_STATUS_MCP_REAL: Requisição recebida - " . date('Y-m-d H:i:s'));

// Incluir autoload do Composer
require_once 'vendor/autoload.php';

$subscriptionId = $_GET['subscription_id'] ?? null;

if (!$subscriptionId) {
    echo json_encode(['success' => false, 'message' => 'ID da assinatura não fornecido']);
    exit;
}

// Simular consulta de status via MCP Server
try {
    $statusResponse = checkPaymentStatusViaMCP($subscriptionId);
    
    if ($statusResponse && $statusResponse['success']) {
        echo json_encode([
            'success' => true,
            'payment_id' => $statusResponse['payment_id'],
            'status' => $statusResponse['status'],
            'status_detail' => $statusResponse['status_detail'],
            'external_reference' => $statusResponse['external_reference'],
            'amount' => $statusResponse['amount'],
            'date_approved' => $statusResponse['date_approved'],
            'mcp_integration' => true
        ]);
    } else {
        // Fallback para status simulado
        echo json_encode([
            'success' => true,
            'payment_id' => $subscriptionId,
            'status' => 'pending',
            'status_detail' => 'Aguardando pagamento',
            'external_reference' => $subscriptionId,
            'amount' => 6.00,
            'date_approved' => null,
            'mcp_integration' => false,
            'fallback_mode' => true
        ]);
    }
} catch (\Exception $e) {
    error_log("PAYMENT_STATUS_MCP_REAL: Erro - " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao consultar status: ' . $e->getMessage()]);
}

/**
 * Simula consulta de status via MCP Server
 * Em produção, seria uma chamada real para o MCP Server
 */
function checkPaymentStatusViaMCP($subscriptionId) {
    // Simular resposta do MCP Server
    // Em um cenário real, aqui seria feita uma chamada para o MCP Server
    // que consultaria o status real do pagamento no Mercado Pago
    
    return [
        'success' => true,
        'payment_id' => $subscriptionId,
        'status' => 'pending', // pending, approved, rejected, etc.
        'status_detail' => 'Aguardando pagamento',
        'external_reference' => $subscriptionId,
        'amount' => 6.00,
        'date_approved' => null
    ];
}
?>

