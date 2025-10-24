<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class TesteContato
{
    public function enviarMensagemDireta(): void
    {
        // Inicia a sessão se ainda não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=UTF-8');
        
        $response = [
            'success' => true,
            'message' => 'Teste funcionando!',
            'method' => $_SERVER['REQUEST_METHOD'],
            'user_id' => $_SESSION['user_id'] ?? 'não definido',
            'user_level' => $_SESSION['user_level_numeric'] ?? 'não definido',
            'post_data' => $_POST
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

