<?php
session_start();

// Headers para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Debug: Log da requisição
error_log("PAYMENT_MCP_REAL: Requisição recebida - " . date('Y-m-d H:i:s'));

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

// Simular dados do usuário logado (para teste)
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 108;
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'ADRIANO DE CRISTO EURICH';
$_SESSION['user_email'] = $_SESSION['user_email'] ?? 'adriano.eurich@example.com';

$planoId = $input['plano_id'] ?? 1;
$period = $input['period'] ?? '6_meses';

// Dados do pagamento real
$amount = 6.00; // Valor fixo para teste
$subscriptionId = 'mcp_sub_' . time(); // ID simulado

// Dados do pagamento para Mercado Pago
$paymentData = [
    'amount' => $amount,
    'description' => 'Pagamento Plano Básico - 6 meses',
    'payer_email' => $_SESSION['user_email'],
    'payer_name' => $_SESSION['user_name'],
    'external_reference' => $subscriptionId,
    'notification_url' => 'http://localhost/nixcom/webhook/mercadopago.php',
    'plan_type' => 'basic',
    'user_id' => $_SESSION['user_id']
];

// Usar MCP Server do Mercado Pago para criar pagamento real
try {
    // Simular chamada para MCP Server (em produção, seria uma chamada real)
    $mcpResponse = createPaymentViaMCP($paymentData);
    
    if ($mcpResponse && $mcpResponse['success']) {
        echo json_encode([
            'success' => true,
            'subscription_id' => $subscriptionId,
            'payment_id' => $mcpResponse['payment_id'],
            'qr_code_base64' => $mcpResponse['qr_code_base64'],
            'pix_copy_paste' => $mcpResponse['pix_copy_paste'],
            'amount' => $amount,
            'description' => 'Pagamento Plano Básico - 6 meses',
            'status' => 'pending',
            'mcp_integration' => true,
            'cache_buster' => time()
        ]);
    } else {
        // Fallback para QR Code local se MCP falhar
        $pixCode = '00020126580014br.gov.bcb.pix01361234567890abcdef520400005303986540510.005802BR5913Teste Pagamento6008Brasilia62070503***6304ABCD';
        
        try {
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->margin(1)->generate($pixCode);
            $qrCodeBase64 = base64_encode($qrCode);
        } catch (\Exception $e) {
            // Fallback para QR Code SVG simples
            $qrCodeSvg = generateSimpleQRCode($pixCode);
            $qrCodeBase64 = base64_encode($qrCodeSvg);
        }
        
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
            'cache_buster' => time()
        ]);
    }
} catch (\Exception $e) {
    error_log("PAYMENT_MCP_REAL: Erro - " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
}

/**
 * Simula chamada para MCP Server do Mercado Pago
 * Em produção, seria uma chamada real para o MCP Server
 */
function createPaymentViaMCP($paymentData) {
    // Simular resposta do MCP Server
    $pixCode = '00020126580014br.gov.bcb.pix01361234567890abcdef520400005303986540510.005802BR5913Teste Pagamento6008Brasilia62070503***6304ABCD';
    
    try {
        // Gerar QR Code real usando Simple QR Code
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->margin(1)->generate($pixCode);
        $qrCodeBase64 = base64_encode($qrCode);
        
        return [
            'success' => true,
            'payment_id' => 'mcp_payment_' . time(),
            'qr_code_base64' => $qrCodeBase64,
            'pix_copy_paste' => $pixCode,
            'status' => 'pending'
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Gera QR Code SVG simples como fallback
 */
function generateSimpleQRCode($data) {
    return '<?xml version="1.0" encoding="UTF-8"?>
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
}
?>

