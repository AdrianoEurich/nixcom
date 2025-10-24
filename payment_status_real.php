<?php
// Endpoint real para verificar status de pagamento
session_start();

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
    $subscriptionId = $_GET['subscription_id'] ?? null;
    
    if (!$subscriptionId) {
        echo json_encode(['success' => false, 'message' => 'ID da assinatura não fornecido']);
        exit;
    }

    // Incluir autoloader do Composer
    require_once 'vendor/autoload.php';

    // Usar a classe do Mercado Pago
    $mercadoPago = new \Adms\Models\AdmsMercadoPago();

    // Consultar status do pagamento
    $paymentStatus = $mercadoPago->getPaymentStatus($subscriptionId);

    if ($paymentStatus) {
        echo json_encode([
            'success' => true,
            'status' => $paymentStatus['status'],
            'status_detail' => $paymentStatus['status_detail'],
            'amount' => $paymentStatus['transaction_amount'],
            'date_approved' => $paymentStatus['date_approved']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Pagamento não encontrado'
        ]);
    }

} catch (\Exception $e) {
    error_log("Payment Status Real Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>


