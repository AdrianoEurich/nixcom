<?php
// Headers para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Debug: Log da requisição
error_log("PAYMENT_MCP_SIMPLE: Requisição recebida - " . date('Y-m-d H:i:s'));

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
$subscriptionId = 'mcp_sub_' . time();

// Gerar QR Code real usando Simple QR Code
$pixCode = '00020126580014br.gov.bcb.pix01361234567890abcdef520400005303986540510.005802BR5913Teste Pagamento6008Brasilia62070503***6304ABCD';

try {
    $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->margin(1)->generate($pixCode);
    $qrCodeBase64 = base64_encode($qrCode);
    
    echo json_encode([
        'success' => true,
        'subscription_id' => $subscriptionId,
        'payment_id' => 'mcp_payment_' . time(),
        'qr_code_base64' => $qrCodeBase64,
        'pix_copy_paste' => $pixCode,
        'amount' => $amount,
        'description' => 'Pagamento Plano Básico - 6 meses',
        'status' => 'pending',
        'mcp_integration' => true,
        'cache_buster' => time()
    ]);
} catch (\Exception $e) {
    // Fallback para QR Code SVG simples
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
    
    echo json_encode([
        'success' => true,
        'subscription_id' => $subscriptionId,
        'payment_id' => 'mcp_payment_' . time(),
        'qr_code_base64' => $qrCodeBase64,
        'pix_copy_paste' => $pixCode,
        'amount' => $amount,
        'description' => 'Pagamento Plano Básico - 6 meses',
        'status' => 'pending',
        'mcp_integration' => false,
        'fallback_mode' => true,
        'cache_buster' => time()
    ]);
}
?>

