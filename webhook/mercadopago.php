<?php
// Webhook do Mercado Pago para processar notificações de pagamento
session_start();

// Log da requisição
error_log("MercadoPago Webhook - Requisição recebida: " . date('Y-m-d H:i:s'));

// Obter dados do webhook
$rawInput = file_get_contents('php://input');
$webhookData = json_decode($rawInput, true);

// Log dos dados recebidos
error_log("MercadoPago Webhook - Dados: " . json_encode($webhookData));

try {
    // Incluir autoloader do Composer
    require_once '../vendor/autoload.php';

    // Usar a classe do Mercado Pago
    $mercadoPago = new \Adms\Models\AdmsMercadoPago();

    // Processar notificação do webhook
    $notification = $mercadoPago->processWebhookNotification($webhookData);

    if ($notification && $notification['status'] === 'approved') {
        // Pagamento aprovado - atualizar status do usuário
        $externalReference = $notification['external_reference'];
        $subscriptionId = explode('_', $externalReference)[1] ?? null;
        
        if ($subscriptionId) {
            error_log("MercadoPago Webhook - Pagamento aprovado para subscription: " . $subscriptionId);
            
            // Atualizar assinatura no banco
            $subscriptionModel = new \Adms\Models\AdmsSubscription();
            $subscription = $subscriptionModel->getSubscriptionById($subscriptionId);
            
            if ($subscription) {
                // Atualizar status da assinatura
                $subscriptionModel->approveSubscription($subscriptionId);
                
                // Atualizar status de pagamento do usuário
                $userModel = new \Adms\Models\AdmsUser();
                $userModel->updatePaymentStatus($subscription['user_id'], 'approved');
                
                // Log do pagamento aprovado
                $paymentLogModel = new \Adms\Models\AdmsPaymentLog();
                $paymentLogModel->logPayment([
                    'user_id' => $subscription['user_id'],
                    'payment_id' => $notification['payment_id'] ?? null,
                    'subscription_id' => $subscriptionId,
                    'action' => 'payment_approved',
                    'status' => 'approved',
                    'amount' => $notification['amount'] ?? null,
                    'plan_type' => $subscription['plan_type'] ?? null,
                    'payment_method' => 'pix',
                    'external_reference' => $externalReference,
                    'mercado_pago_data' => $notification
                ]);
                
                // Criar notificação para o usuário
                $notificationModel = new \Adms\Models\AdmsNotificacao();
                $notificationModel->createNotification(
                    $subscription['user_id'],
                    'Pagamento Aprovado',
                    "Seu pagamento foi aprovado! O plano está agora ativo.",
                    'success'
                );
                
                error_log("MercadoPago Webhook - Usuário {$subscription['user_id']} teve pagamento aprovado");
            }
        }
    }

    // Responder com sucesso para o Mercado Pago
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (\Exception $e) {
    error_log("MercadoPago Webhook - Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>