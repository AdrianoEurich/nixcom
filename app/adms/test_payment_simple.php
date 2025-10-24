<?php
// Teste simples de pagamento
header('Content-Type: application/json');

// Simular dados de sessão para teste
$_SESSION['user_id'] = 108;

try {
    // Dados simulados para teste
    $amount = 6.00;
    $subscriptionId = time();
    
    // Dados do pagamento simulados
    $pixCode = '00020126580014br.gov.bcb.pix01361234567890abcdef520400005303986540510.005802BR5913Teste Pagamento6008Brasilia62070503***6304ABCD';
    
    // QR Code SVG simples (sempre funciona)
    $qrCodeSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
    <rect width="80" height="80" fill="white"/>
    <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="8" fill="black">QR Code</text>
    <text x="50%" y="60%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="6" fill="gray">PIX Payment</text>
</svg>';
    $qrCodeBase64 = base64_encode($qrCodeSvg);
    
    // Retornar dados do pagamento
    echo json_encode([
        'success' => true,
        'subscription_id' => $subscriptionId,
        'payment_id' => 'test_payment_' . time(),
        'qr_code_base64' => $qrCodeBase64,
        'pix_copy_paste' => $pixCode,
        'amount' => $amount,
        'description' => 'Pagamento Plano Básico - 6 meses',
        'status' => 'pending'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
