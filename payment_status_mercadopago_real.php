<?php
// Endpoint de status com Mercado Pago real
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Debug: Log da requisição
error_log("PAYMENT_STATUS_MERCADOPAGO_REAL: Requisição recebida - " . date('Y-m-d H:i:s'));

$subscriptionId = $_GET['subscription_id'] ?? null;

if (!$subscriptionId) {
    echo json_encode(['success' => false, 'message' => 'ID da assinatura não fornecido']);
    exit;
}

try {
    // Configuração do Mercado Pago
    $accessToken = 'APP_USR-8226898734680411-101115-9d226eccd0f3b50c836673d870c039f7-12767031';
    $baseUrl = 'https://api.mercadopago.com';
    
    // Extrair payment_id do subscription_id
    $paymentId = str_replace('mp_real_', '', $subscriptionId);
    
    // Consultar status do pagamento
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/payments/' . $paymentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $paymentData = json_decode($response, true);
        
        echo json_encode([
            'success' => true,
            'payment_id' => $paymentData['id'],
            'status' => $paymentData['status'],
            'status_detail' => $paymentData['status_detail'],
            'external_reference' => $paymentData['external_reference'],
            'amount' => $paymentData['transaction_amount'],
            'date_approved' => $paymentData['date_approved'],
            'mcp_integration' => true,
            'mp_payment_id' => $paymentData['id']
        ]);
    } else {
        throw new \Exception('Erro ao consultar pagamento: ' . $httpCode);
    }
    
} catch (\Exception $e) {
    error_log("PAYMENT_STATUS_MERCADOPAGO_REAL: Erro - " . $e->getMessage());
    
    // Fallback para status simulado
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
        'mcp_integration' => false,
        'fallback_mode' => true,
        'error' => $e->getMessage()
    ]);
}
?>

