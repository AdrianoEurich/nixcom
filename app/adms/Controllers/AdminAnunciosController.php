<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\Models\AdmsAnuncio; // Importa o modelo AdmsAnuncio

/**
 * Controlador para gerenciar as ações de administração de anúncios (aprovar, rejeitar, ativar, desativar, excluir).
 * Todas as ações são projetadas para serem chamadas via AJAX.
 */
class AdminAnunciosController
{
    private array $data;
    private int $anuncioId;
    private int $userId; // Para validar se o usuário tem permissão (admin)

    public function __construct()
    {
        // Verifica se o usuário está logado e tem nível de acesso de administrador (nível 3 ou superior)
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_level'] ?? 0) < 3) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado. Você não tem permissão para esta ação.']);
            exit();
        }
        $this->userId = $_SESSION['user_id'];
        
        // As requisições devem ser POST e JSON
        $input = file_get_contents('php://input');
        $this->data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Requisição inválida. Formato JSON esperado.']);
            exit();
        }

        $this->anuncioId = $this->data['anuncio_id'] ?? 0;
        if (empty($this->anuncioId)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID do anúncio não fornecido.']);
            exit();
        }
    }

    /**
     * Aprova um anúncio.
     */
    public function approveAnuncio(): void
    {
        $admsAnuncio = new AdmsAnuncio();
        if ($admsAnuncio->updateAnuncioStatus($this->anuncioId, 'active')) {
            $this->sendJsonResponse(['success' => true, 'message' => 'Anúncio aprovado com sucesso!', 'newStatus' => 'active']);
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => $admsAnuncio->getMsg()['text'] ?? 'Falha ao aprovar o anúncio.']);
        }
    }

    /**
     * Rejeita um anúncio.
     */
    public function rejectAnuncio(): void
    {
        $admsAnuncio = new AdmsAnuncio();
        if ($admsAnuncio->updateAnuncioStatus($this->anuncioId, 'rejected')) {
            $this->sendJsonResponse(['success' => true, 'message' => 'Anúncio rejeitado com sucesso!', 'newStatus' => 'rejected']);
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => $admsAnuncio->getMsg()['text'] ?? 'Falha ao rejeitar o anúncio.']);
        }
    }

    /**
     * Ativa um anúncio (muda de 'inactive' para 'active').
     */
    public function activateAnuncio(): void
    {
        $admsAnuncio = new AdmsAnuncio();
        if ($admsAnuncio->updateAnuncioStatus($this->anuncioId, 'active')) {
            $this->sendJsonResponse(['success' => true, 'message' => 'Anúncio ativado com sucesso!', 'newStatus' => 'active']);
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => $admsAnuncio->getMsg()['text'] ?? 'Falha ao ativar o anúncio.']);
        }
    }

    /**
     * Desativa um anúncio (muda de 'active' para 'inactive').
     */
    public function deactivateAnuncio(): void
    {
        $admsAnuncio = new AdmsAnuncio();
        if ($admsAnuncio->updateAnuncioStatus($this->anuncioId, 'inactive')) { // 'inactive' é o status para "pausado"
            $this->sendJsonResponse(['success' => true, 'message' => 'Anúncio desativado com sucesso!', 'newStatus' => 'inactive']);
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => $admsAnuncio->getMsg()['text'] ?? 'Falha ao desativar o anúncio.']);
        }
    }

    /**
     * Exclui um anúncio.
     */
    public function deleteAnuncio(): void
    {
        $admsAnuncio = new AdmsAnuncio();
        if ($admsAnuncio->deleteAnuncio($this->anuncioId)) { // Assumindo que você terá um método deleteAnuncio no seu modelo
            $this->sendJsonResponse(['success' => true, 'message' => 'Anúncio excluído com sucesso!']);
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => $admsAnuncio->getMsg()['text'] ?? 'Falha ao excluir o anúncio.']);
        }
    }

    /**
     * Envia uma resposta JSON e encerra a execução.
     * @param array $response O array de dados a ser convertido para JSON.
     */
    private function sendJsonResponse(array $response): void
    {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
