<?php
// Endpoint real para pagamento PIX com Mercado Pago
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
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

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

    // Dados do usuário da sessão
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Usuário';
    $userEmail = $_SESSION['user_email'] ?? 'usuario@exemplo.com';

    // Valores dos planos
    $planValues = [
        1 => ['name' => 'Básico', 'amount' => 6.00, 'months' => 6],
        2 => ['name' => 'Intermediário', 'amount' => 12.00, 'months' => 6],
        3 => ['name' => 'Avançado', 'amount' => 18.00, 'months' => 6]
    ];

    $planData = $planValues[$planoId] ?? $planValues[1];
    $amount = $planData['amount'];
    $planName = $planData['name'];

    // Incluir autoloader do Composer
    require_once 'vendor/autoload.php';

    // Usar a classe do Mercado Pago
    $mercadoPago = new \Adms\Models\AdmsMercadoPago();

    // Dados do pagamento
    $paymentData = [
        'amount' => $amount,
        'description' => "Pagamento Plano {$planName} - {$planData['months']} meses",
        'payer_email' => $userEmail,
        'payer_name' => $userName,
        'external_reference' => 'payment_' . time() . '_' . $userId,
        'notification_url' => 'http://localhost/nixcom/webhook/mercadopago.php',
        'plan_type' => strtolower($planName),
        'user_id' => $userId
    ];

    // Criar pagamento PIX
    $paymentResult = $mercadoPago->createPixPayment($paymentData);

    if ($paymentResult && isset($paymentResult['qr_code'])) {
        // Sucesso - retornar dados do pagamento
        echo json_encode([
            'success' => true,
            'subscription_id' => $paymentResult['external_reference'],
            'payment_id' => $paymentResult['payment_id'],
            'qr_code_base64' => $paymentResult['qr_code_base64'],
            'pix_copy_paste' => $paymentResult['qr_code'],
            'amount' => $amount,
            'description' => $paymentData['description'],
            'status' => $paymentResult['status']
        ]);
    } else {
        // Erro na criação do pagamento
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar pagamento no Mercado Pago'
        ]);
    }

} catch (\Exception $e) {
    error_log("Payment Real Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>


