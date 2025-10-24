<?php
// Endpoint direto para criação de pagamento
session_start();

// Debug: Log da requisição
error_log("PAGAMENTO_CREATE: Requisição recebida - " . date('Y-m-d H:i:s'));

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Desabilitar exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

try {
    // Obter dados de entrada
    $input = null;
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $input = json_decode($rawInput, true);
    }
    
    if (!$input && !empty($_POST)) {
        $input = $_POST;
    }
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Dados de entrada não encontrados']);
        exit;
    }
    
    $planoId = $input['plano_id'] ?? 1;
    $period = $input['period'] ?? '6_meses';

    // Dados simulados para teste
    $amount = 6.00;
    $subscriptionId = time();
    
    // Dados do pagamento simulados (funcionando)
    $pixCode = '00020126580014br.gov.bcb.pix01361234567890abcdef520400005303986540510.005802BR5913Teste Pagamento6008Brasilia62070503***6304ABCD';
    
    // Gerar QR Code real usando Simple QR Code (ultra otimizado)
    try {
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(80)->margin(0)->generate($pixCode);
        $qrCodeBase64 = base64_encode($qrCode);
    } catch (Exception $e) {
        // Fallback para QR Code SVG simples
        $qrCodeSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
    <rect width="80" height="80" fill="white"/>
    <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="8" fill="black">QR Code</text>
    <text x="50%" y="60%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="6" fill="gray">PIX Payment</text>
</svg>';
        $qrCodeBase64 = base64_encode($qrCodeSvg);
    }
    
    $payment = [
        'payment_id' => 'test_payment_' . time(),
        'status' => 'pending',
        'qr_code_base64' => $qrCodeBase64,
        'pix_copy_paste' => $pixCode
    ];

    // Retornar dados do pagamento
    echo json_encode([
        'success' => true,
        'subscription_id' => $subscriptionId,
        'payment_id' => $payment['payment_id'],
        'qr_code_base64' => $payment['qr_code_base64'],
        'pix_copy_paste' => $payment['pix_copy_paste'],
        'amount' => $amount,
        'description' => 'Pagamento Plano Básico - 6 meses',
        'status' => 'pending'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
