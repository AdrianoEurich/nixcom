<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsMercadoPago
{
    private string $accessToken;
    private string $baseUrl;
    private bool $isSandbox;

    public function __construct()
    {
        // Usar configuração centralizada
        $this->isSandbox = \Adms\Config\MercadoPagoConfig::isSandbox();
        $this->baseUrl = \Adms\Config\MercadoPagoConfig::getBaseUrl();
        $this->accessToken = \Adms\Config\MercadoPagoConfig::getAccessToken();
    }

    /**
     * Cria um pagamento PIX real
     */
    public function createPixPayment(array $paymentData): ?array
    {
        try {
            $url = $this->baseUrl . '/v1/payments';
            
            $payload = [
                'transaction_amount' => (float) $paymentData['amount'],
                'description' => $paymentData['description'],
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $paymentData['payer_email'],
                    'first_name' => $paymentData['payer_name']
                ],
                'external_reference' => $paymentData['external_reference'],
                'metadata' => [
                    'plan_type' => $paymentData['plan_type'] ?? 'basic',
                    'user_id' => $paymentData['user_id'] ?? null
                ]
            ];
            // Incluir notification_url somente se presente (sandbox sem webhook não envia)
            if (!empty($paymentData['notification_url'])) {
                $payload['notification_url'] = $paymentData['notification_url'];
            }

            error_log("MercadoPago - Criando pagamento real: " . json_encode($payload));

            $response = $this->makeRequest('POST', $url, $payload);
            
            error_log("MercadoPago - Resposta real: " . json_encode($response));
            
            if ($response && isset($response['id'])) {
                $pointOfInteraction = $response['point_of_interaction'] ?? [];
                $transactionData = $pointOfInteraction['transaction_data'] ?? [];
                
                return [
                    'payment_id' => $response['id'],
                    'status' => $response['status'],
                    'qr_code' => $transactionData['qr_code'] ?? null,
                    'qr_code_base64' => $transactionData['qr_code_base64'] ?? null,
                    'qr_text' => $transactionData['qr_code'] ?? null,
                    'external_reference' => $response['external_reference'],
                    'transaction_amount' => $response['transaction_amount'],
                    'date_of_expiration' => $response['date_of_expiration'] ?? null
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::createPixPayment - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Consulta status de um pagamento
     */
    public function getPaymentStatus(string $paymentId): ?array
    {
        try {
            $url = $this->baseUrl . '/v1/payments/' . $paymentId;
            $response = $this->makeRequest('GET', $url);
            
            if ($response) {
                return [
                    'id' => $response['id'],
                    'status' => $response['status'],
                    'status_detail' => $response['status_detail'],
                    'external_reference' => $response['external_reference'],
                    'transaction_amount' => $response['transaction_amount'],
                    'date_approved' => $response['date_approved'] ?? null
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::getPaymentStatus - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida webhook do Mercado Pago
     */
    public function validateWebhook(array $data, string $signature): bool
    {
        try {
            // Em produção, validar a assinatura do webhook
            // Por enquanto, apenas verificar se os dados básicos existem
            return isset($data['type']) && isset($data['data']['id']);
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::validateWebhook - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Processa notificação de webhook
     */
    public function processWebhookNotification(array $data): ?array
    {
        try {
            if (!isset($data['type']) || $data['type'] !== 'payment') {
                return null;
            }

            $paymentId = $data['data']['id'] ?? null;
            if (!$paymentId) {
                return null;
            }

            // Consultar status do pagamento
            $paymentStatus = $this->getPaymentStatus($paymentId);
            
            if ($paymentStatus && $paymentStatus['status'] === 'approved') {
                return [
                    'payment_id' => $paymentId,
                    'status' => 'approved',
                    'external_reference' => $paymentStatus['external_reference'],
                    'amount' => $paymentStatus['transaction_amount'],
                    'date_approved' => $paymentStatus['date_approved']
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::processWebhookNotification - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Faz requisição HTTP para a API do Mercado Pago
     */
    private function makeRequest(string $method, string $url, array $data = null): ?array
    {
        try {
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . uniqid()
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // Ambiente: em sandbox, relaxar verificação SSL para evitar erros em dev (Windows/XAMPP sem cacert)
            $isSandbox = \Adms\Config\MercadoPagoConfig::isSandbox();
            if ($isSandbox) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            if ($method === 'POST' && $data) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            if ($httpCode >= 400) {
                // Log curto para depuração, sem expor resposta completa
                error_log("AdmsMercadoPago::makeRequest HTTP Error: " . $httpCode . " body: " . substr((string)$response, 0, 300));
                throw new \Exception("HTTP Error: " . $httpCode);
            }

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON Decode Error: " . json_last_error_msg());
            }

            return $decodedResponse;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::makeRequest - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Gera QR Code base64 a partir do texto PIX
     */
    public function generateQRCodeBase64(string $qrText): ?string
    {
        try {
            // Usar biblioteca QR Code (se disponível) ou API externa
            // Por enquanto, retornar null (será implementado com biblioteca QR Code)
            return null;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::generateQRCodeBase64 - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Processa estorno de pagamento
     */
    public function processRefund(string $paymentId, float $amount = null, string $reason = 'Solicitação do usuário'): ?array
    {
        try {
            $url = $this->baseUrl . '/v1/payments/' . $paymentId . '/refunds';
            
            $payload = [
                'amount' => $amount, // Se null, será estorno total
                'reason' => $reason
            ];

            error_log("MercadoPago - Processando estorno: " . json_encode($payload));

            $response = $this->makeRequest('POST', $url, $payload);
            
            error_log("MercadoPago - Resposta do estorno: " . json_encode($response));
            
            if ($response && isset($response['id'])) {
                return [
                    'refund_id' => $response['id'],
                    'status' => $response['status'],
                    'amount' => $response['amount'],
                    'reason' => $response['reason'] ?? $reason,
                    'date_created' => $response['date_created'],
                    'payment_id' => $response['payment_id']
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::processRefund - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Consulta status de um estorno
     */
    public function getRefundStatus(string $refundId): ?array
    {
        try {
            $url = $this->baseUrl . '/v1/refunds/' . $refundId;
            $response = $this->makeRequest('GET', $url);
            
            if ($response) {
                return [
                    'id' => $response['id'],
                    'status' => $response['status'],
                    'amount' => $response['amount'],
                    'reason' => $response['reason'],
                    'payment_id' => $response['payment_id'],
                    'date_created' => $response['date_created'],
                    'date_approved' => $response['date_approved'] ?? null
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::getRefundStatus - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lista estornos de um pagamento
     */
    public function getPaymentRefunds(string $paymentId): ?array
    {
        try {
            $url = $this->baseUrl . '/v1/payments/' . $paymentId . '/refunds';
            $response = $this->makeRequest('GET', $url);
            
            if ($response && isset($response['data'])) {
                return $response['data'];
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("AdmsMercadoPago::getPaymentRefunds - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém configurações do ambiente
     */
    public function getEnvironmentInfo(): array
    {
        return [
            'is_sandbox' => $this->isSandbox,
            'base_url' => $this->baseUrl,
            'has_token' => !empty($this->accessToken)
        ];
    }
}
