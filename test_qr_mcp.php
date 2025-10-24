<?php
// Teste direto do QR Code com MCP
header('Content-Type: application/json');

// Incluir autoload do Composer
require_once 'vendor/autoload.php';

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
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

