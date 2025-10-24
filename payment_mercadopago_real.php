<?php
// Endpoint com integração REAL do Mercado Pago
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Debug: Log da requisição
error_log("PAYMENT_MERCADOPAGO_REAL: Requisição recebida - " . date('Y-m-d H:i:s'));

// Incluir autoload do Composer
require_once 'vendor/autoload.php';

// Capturar dados de entrada
$rawInput = file_get_contents('php://input');
if ($rawInput) {
    $input = json_decode($rawInput, true);
}

if (!$input && !empty($_POST)) {
    $input = $_POST;
}

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados de entrada não encontrados']);
    return;
}

// Dados do pagamento
$amount = 6.00;
$subscriptionId = 'mp_real_' . time();

try {
    // Configuração do Mercado Pago
    $accessToken = 'APP_USR-8226898734680411-101115-9d226eccd0f3b50c836673d870c039f7-12767031';
    $baseUrl = 'https://api.mercadopago.com';
    
    // Dados do pagamento PIX
    $paymentData = [
        'transaction_amount' => $amount,
        'description' => 'Pagamento Plano Básico - 6 meses',
        'payment_method_id' => 'pix',
        'payer' => [
            'email' => 'adriano.eurich@example.com',
            'first_name' => 'ADRIANO',
            'last_name' => 'EURICH'
        ],
        'external_reference' => $subscriptionId,
        'notification_url' => 'http://localhost/nixcom/webhook/mercadopago.php'
    ];
    
    // Criar pagamento via API do Mercado Pago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/payments');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        $paymentResponse = json_decode($response, true);
        
        if (isset($paymentResponse['id']) && isset($paymentResponse['point_of_interaction']['transaction_data']['qr_code'])) {
            // Pagamento criado com sucesso
            $qrCode = $paymentResponse['point_of_interaction']['transaction_data']['qr_code'];
            $qrCodeBase64 = $paymentResponse['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;
            
            // Se não tiver base64, gerar QR Code da string
            if (!$qrCodeBase64) {
                $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCode);
                $qrCodeData = file_get_contents($qrApiUrl);
                $qrCodeBase64 = base64_encode($qrCodeData);
            }
            
            echo json_encode([
                'success' => true,
                'subscription_id' => $subscriptionId,
                'payment_id' => $paymentResponse['id'],
                'qr_code_base64' => $qrCodeBase64,
                'pix_copy_paste' => $qrCode,
                'amount' => $amount,
                'description' => 'Pagamento Plano Básico - 6 meses',
                'status' => $paymentResponse['status'],
                'mcp_integration' => true,
                'qr_type' => 'png',
                'qr_source' => 'mercadopago',
                'mp_payment_id' => $paymentResponse['id'],
                'cache_buster' => time()
            ]);
        } else {
            throw new \Exception('Resposta do Mercado Pago inválida');
        }
    } else {
        throw new \Exception('Erro na API do Mercado Pago: ' . $httpCode . ' - ' . $response);
    }
    
} catch (\Exception $e) {
    error_log("PAYMENT_MERCADOPAGO_REAL: Erro - " . $e->getMessage());
    
    // Fallback para QR Code simulado
    $pixCode = '00020126580014br.gov.bcb.pix01361234567890abcdef520400005303986540510.005802BR5913Teste Pagamento6008Brasilia62070503***6304ABCD';
    
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($pixCode);
    $qrCodeData = file_get_contents($qrApiUrl);
    $qrCodeBase64 = base64_encode($qrCodeData);
    
    echo json_encode([
        'success' => true,
        'subscription_id' => $subscriptionId,
        'payment_id' => 'fallback_payment_' . time(),
        'qr_code_base64' => $qrCodeBase64,
        'pix_copy_paste' => $pixCode,
        'amount' => $amount,
        'description' => 'Pagamento Plano Básico - 6 meses',
        'status' => 'pending',
        'mcp_integration' => false,
        'fallback_mode' => true,
        'qr_type' => 'png',
        'qr_source' => 'fallback',
        'error' => $e->getMessage(),
        'cache_buster' => time()
    ]);
}
?>

