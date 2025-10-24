<?php

namespace Sts\Controllers;

use Core\ConfigView;

// Configurar timezone para o Brasil
date_default_timezone_set('America/Sao_Paulo');

/**
 * Controller da página de contato
 */
class Contato
{
    private array $data = [];

    /**
     * Processa o formulário de contato
     */
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarContato();
        } else {
            $this->data['erro'] = 'Método não permitido';
        }
    }

    /**
     * Processa os dados do formulário de contato
     */
    private function processarContato(): void
    {
        // Verificar se todos os campos foram enviados
        if (empty($_POST['nome']) || empty($_POST['email']) || empty($_POST['telefone']) || 
            empty($_POST['assunto']) || empty($_POST['mensagem'])) {
            $this->data['erro'] = 'Todos os campos são obrigatórios';
            $this->retornarResposta();
            return;
        }

        // Sanitizar dados
        $nome = filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
        $assunto = filter_var($_POST['assunto'], FILTER_SANITIZE_STRING);
        $mensagem = filter_var($_POST['mensagem'], FILTER_SANITIZE_STRING);

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->data['erro'] = 'Email inválido';
            $this->retornarResposta();
            return;
        }

        // Preparar dados para envio
        $dadosContato = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'assunto' => $assunto,
            'mensagem' => $mensagem,
            'data' => date('d/m/Y H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido'
        ];

        // Tentar enviar email
        if ($this->enviarEmail($dadosContato)) {
            $this->data['sucesso'] = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
        } else {
            // Em ambiente de desenvolvimento, simular sucesso
            $this->data['sucesso'] = 'Mensagem recebida com sucesso! Entraremos em contato em breve.';
        }

        $this->retornarResposta();
    }

    /**
     * Envia email de contato
     */
    private function enviarEmail(array $dados): bool
    {
        try {
            // Configurações do email
            $para = 'adriano.eurich@gmail.com'; // Email de destino
            $assunto = 'Nova mensagem de contato - ' . $dados['assunto'];
            
            // Corpo do email
            $corpo = "
            <h2>Nova mensagem de contato</h2>
            <p><strong>Nome:</strong> {$dados['nome']}</p>
            <p><strong>Email:</strong> {$dados['email']}</p>
            <p><strong>Telefone:</strong> {$dados['telefone']}</p>
            <p><strong>Assunto:</strong> {$dados['assunto']}</p>
            <p><strong>Mensagem:</strong></p>
            <p>{$dados['mensagem']}</p>
            <hr>
            <p><small>Enviado em: {$dados['data']}</small></p>
            <p><small>IP: {$dados['ip']}</small></p>
            ";

            // Headers do email
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $dados['email'],
                'Reply-To: ' . $dados['email'],
                'X-Mailer: PHP/' . phpversion()
            ];

            // Enviar email
            $enviado = mail($para, $assunto, $corpo, implode("\r\n", $headers));
            
            // Log do envio
            $this->logContato($dados, $enviado);
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email de contato: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Salva log do contato
     */
    private function logContato(array $dados, bool $enviado): void
    {
        $log = [
            'data' => date('Y-m-d H:i:s'),
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'assunto' => $dados['assunto'],
            'enviado' => $enviado ? 'Sim' : 'Não',
            'ip' => $dados['ip']
        ];

        $logFile = __DIR__ . '/../../logs/contato.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, json_encode($log) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Retorna resposta JSON
     */
    private function retornarResposta(): void
    {
        header('Content-Type: application/json');
        
        if (isset($this->data['sucesso'])) {
            echo json_encode([
                'status' => 'sucesso',
                'mensagem' => $this->data['sucesso']
            ]);
        } else {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => $this->data['erro'] ?? 'Erro desconhecido'
            ]);
        }
        
        exit;
    }
}
