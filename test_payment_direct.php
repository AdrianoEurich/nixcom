<?php
// Teste de pagamento direto (fora da pasta adms)
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

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
<svg width="150" height="150" viewBox="0 0 150 150" xmlns="http://www.w3.org/2000/svg">
    <rect width="150" height="150" fill="white" stroke="black" stroke-width="2"/>
    <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="14" fill="black">QR Code</text>
    <text x="50%" y="65%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="10" fill="gray">PIX Payment</text>
    <rect x="20" y="20" width="20" height="20" fill="black"/>
    <rect x="50" y="20" width="20" height="20" fill="black"/>
    <rect x="80" y="20" width="20" height="20" fill="black"/>
    <rect x="110" y="20" width="20" height="20" fill="black"/>
    <rect x="20" y="50" width="20" height="20" fill="black"/>
    <rect x="80" y="50" width="20" height="20" fill="black"/>
    <rect x="110" y="50" width="20" height="20" fill="black"/>
    <rect x="20" y="80" width="20" height="20" fill="black"/>
    <rect x="50" y="80" width="20" height="20" fill="black"/>
    <rect x="80" y="80" width="20" height="20" fill="black"/>
    <rect x="110" y="80" width="20" height="20" fill="black"/>
    <rect x="20" y="110" width="20" height="20" fill="black"/>
    <rect x="50" y="110" width="20" height="20" fill="black"/>
    <rect x="80" y="110" width="20" height="20" fill="black"/>
    <rect x="110" y="110" width="20" height="20" fill="black"/>
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
        'status' => 'pending',
        'cache_buster' => time()
    ]);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
