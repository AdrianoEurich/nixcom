<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsPlanos;

class Planos
{
    private array $data = [];

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void
    {
        // Se o usuário já estiver logado, mostrar página de mudança de plano
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $this->data = [
                'title' => 'Mudar Plano - ' . SITE_NAME,
                'favicon' => URLADM . 'assets/images/favicon.ico',
                'is_logged_in' => true,
                'current_plan' => $_SESSION['user_plan'] ?? 'free',
                'payment_status' => $_SESSION['payment_status'] ?? 'pending'
            ];
        } else {
            $this->data = [
                'title' => 'Escolha Seu Plano - ' . SITE_NAME,
                'favicon' => URLADM . 'assets/images/favicon.ico',
                'is_logged_in' => false
            ];
        }

        $loadView = new ConfigViewAdm("adms/Views/planos/planos", $this->data);
        $loadView->loadViewLogin();
    }

    public function setSelectedPlan(): void
    {
        // Define o cabeçalho para indicar que a resposta será JSON
        header('Content-Type: application/json');

        // Inicializa a resposta padrão como erro
        $response = ['success' => false, 'message' => 'Erro desconhecido.'];

        // Inicia a sessão se ainda não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response['message'] = 'Método não permitido';
            echo json_encode($response);
            return;
        }

        // Lê os dados JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['plan'])) {
            $response['message'] = 'Dados inválidos';
            echo json_encode($response);
            return;
        }

        $planType = $data['plan'];

        // Validar o tipo de plano
        if (!in_array($planType, ['free', 'basic', 'premium'])) {
            $response['message'] = 'Tipo de plano inválido';
            echo json_encode($response);
            return;
        }

        // Armazenar o plano selecionado na sessão
        $_SESSION['selected_plan'] = $planType;
        
        $response = [
            'success' => true,
            'message' => 'Plano selecionado com sucesso',
            'plan_type' => $planType
        ];

        echo json_encode($response);
    }

    /**
     * Processa mudança de plano para usuário logado
     */
    public function changePlan(): void
    {
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
                return;
            }

            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || !isset($data['plan'])) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                return;
            }

            $newPlan = $data['plan'];
            $currentPlan = $_SESSION['user_plan'] ?? 'free';
            $currentPaymentStatus = $_SESSION['payment_status'] ?? 'pending';

            // Validar plano
            if (!in_array($newPlan, ['free', 'basic', 'premium'])) {
                echo json_encode(['success' => false, 'message' => 'Plano inválido']);
                return;
            }

            // LOG para debug
            error_log("PLANOS::changePlan - User ID: {$userId}, Current Plan: {$currentPlan}, New Plan: {$newPlan}, Payment Status: {$currentPaymentStatus}");

            // LÓGICA DE MUDANÇA DE PLANO
            
            // CASO 1: Mudando para o mesmo plano atual
            if ($newPlan === $currentPlan) {
                // Se já tem o plano e está aprovado, apenas informar
                if ($currentPaymentStatus === 'approved') {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Você já possui este plano ativo',
                        'action' => 'stay' // Não redirecionar
                    ]);
                    return;
                }
                // Se tem o plano mas pagamento pendente, redirecionar para pagamento
                else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Redirecionando para pagamento do seu plano atual',
                        'redirect' => URLADM . "pagamento?plan={$newPlan}"
                    ]);
                    return;
                }
            }

            // CASO 2: Mudando de Free para Pago (Basic ou Premium)
            if ($currentPlan === 'free' && in_array($newPlan, ['basic', 'premium'])) {
                // Atualizar plano no banco
                $admsUser = new \Adms\Models\AdmsUser();
                $result = $admsUser->updateUserPlan($userId, $newPlan);

                if ($result) {
                    // Atualizar sessão
                    $_SESSION['user_plan'] = $newPlan;
                    $_SESSION['payment_status'] = 'pending'; // Resetar para pendente
                    
                    error_log("PLANOS::changePlan - Plano atualizado de FREE para {$newPlan}. Payment status: pending");

                    echo json_encode([
                        'success' => true,
                        'message' => 'Plano alterado para ' . strtoupper($newPlan) . '. Complete o pagamento para ativar.',
                        'redirect' => URLADM . "pagamento?plan={$newPlan}",
                        'new_plan' => $newPlan,
                        'payment_required' => true
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao alterar plano no banco de dados']);
                }
                return;
            }

            // CASO 3: Mudando de Pago para Free (Downgrade)
            if (in_array($currentPlan, ['basic', 'premium']) && $newPlan === 'free') {
                // Permitir downgrade apenas se o pagamento atual não estiver aprovado
                if ($currentPaymentStatus === 'approved') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Não é possível fazer downgrade para plano gratuito enquanto seu plano pago estiver ativo. Entre em contato com o suporte.'
                    ]);
                    return;
                }

                // Permitir downgrade se pagamento não foi aprovado
                $admsUser = new \Adms\Models\AdmsUser();
                $result = $admsUser->updateUserPlan($userId, $newPlan);

                if ($result) {
                    $_SESSION['user_plan'] = $newPlan;
                    $_SESSION['payment_status'] = 'approved'; // Free não precisa pagamento
                    
                    error_log("PLANOS::changePlan - Downgrade para FREE realizado");

                    echo json_encode([
                        'success' => true,
                        'message' => 'Plano alterado para Gratuito com sucesso!',
                        'redirect' => URLADM . "dashboard",
                        'new_plan' => $newPlan
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao alterar plano']);
                }
                return;
            }

            // CASO 4: Mudando entre planos pagos (Basic <-> Premium)
            if (in_array($currentPlan, ['basic', 'premium']) && in_array($newPlan, ['basic', 'premium'])) {
                $admsUser = new \Adms\Models\AdmsUser();
                $result = $admsUser->updateUserPlan($userId, $newPlan);

                if ($result) {
                    $_SESSION['user_plan'] = $newPlan;
                    // Manter status de pagamento se já estava aprovado, senão marcar como pendente
                    if ($currentPaymentStatus !== 'approved') {
                        $_SESSION['payment_status'] = 'pending';
                    }
                    
                    error_log("PLANOS::changePlan - Mudança entre planos pagos: {$currentPlan} -> {$newPlan}");

                    // Se pagamento já aprovado, apenas informar
                    if ($currentPaymentStatus === 'approved') {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Plano alterado para ' . strtoupper($newPlan) . ' com sucesso! Seu pagamento será ajustado no próximo ciclo.',
                            'redirect' => URLADM . "dashboard",
                            'new_plan' => $newPlan
                        ]);
                    } else {
                        // Se não tinha pagamento aprovado, redirecionar para pagamento
                        echo json_encode([
                            'success' => true,
                            'message' => 'Plano alterado para ' . strtoupper($newPlan) . '. Complete o pagamento para ativar.',
                            'redirect' => URLADM . "pagamento?plan={$newPlan}",
                            'new_plan' => $newPlan,
                            'payment_required' => true
                        ]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao alterar plano']);
                }
                return;
            }

            // Caso não se encaixe em nenhuma das situações acima
            echo json_encode(['success' => false, 'message' => 'Operação de mudança de plano não permitida']);

        } catch (\Exception $e) {
            error_log("ERRO Planos::changePlan - Exceção: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao processar mudança de plano']);
        }
    }

    public function getPlans(): void
    {
        // Define o cabeçalho para indicar que a resposta será JSON
        header('Content-Type: application/json');

        try {
            $admsPlanos = new AdmsPlanos();
            $plans = $admsPlanos->getAllPlans();

            $response = [
                'success' => true,
                'plans' => $plans
            ];

            echo json_encode($response);
    } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Erro ao carregar planos: ' . $e->getMessage()
            ];

            echo json_encode($response);
        }
    }
}
