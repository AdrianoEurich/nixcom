<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header('Location: /');
    die('Erro: Página não encontrada!');
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsUser;

class FinanceiroController
{
    private array $data = [];

    public function __construct()
    {
        $this->ensureLoggedIn();
    }

    private function ensureLoggedIn(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            header('Location: ' . URLADM . 'login');
            exit();
        }
    }

    public function index(): void
    {
        $isAdmin = false;
        $userLevel = $_SESSION['user_level'] ?? $_SESSION['user_role'] ?? 'usuario';
        if ($userLevel === 'administrador' || (isset($_SESSION['user_level_numeric']) && (int)$_SESSION['user_level_numeric'] >= 3)) {
            $isAdmin = true;
        }

        if ($isAdmin) {
            $this->loadAdminFinanceiro();
        } else {
            $this->loadUserFinanceiro();
        }
    }

    private function loadUserFinanceiro(): void
    {
        $this->data['title'] = 'Financeiro';
        $this->data['sidebar_active'] = 'financeiro';
        $this->data['user'] = [
            'nome' => $_SESSION['user_name'] ?? '',
            'plan_type' => $_SESSION['user_plan'] ?? 'free',
            'payment_status' => $_SESSION['payment_status'] ?? 'pending',
            'email' => $_SESSION['user_email'] ?? '',
        ];
        $loadView = new ConfigViewAdm('adms/Views/financeiro/user_financeiro', $this->data);
        $loadView->loadView();
    }

    private function loadAdminFinanceiro(): void
    {
        $this->data['title'] = 'Financeiro (Admin)';
        $this->data['sidebar_active'] = 'financeiro';

        // KPIs básicos via modelo de usuários (se disponível)
        $stats = [];
        try {
            $userModel = new AdmsUser();
            if (method_exists($userModel, 'getUsersStats')) {
                $stats = $userModel->getUsersStats();
            }
        } catch (\Throwable $e) {
            $stats = [];
        }
        $this->data['stats'] = $stats;

        $loadView = new ConfigViewAdm('adms/Views/financeiro/admin_financeiro', $this->data);
        $loadView->loadView();
    }
}
