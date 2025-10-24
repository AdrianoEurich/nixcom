<?php

namespace Sts\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Controller da página Home
 */
class Home
{
    private array $data = [];
    private ?array $formData;

    /**
     * Método principal da página Home.
     * Carrega a view da página inicial.
     *
     * @return void
     */
    public function index(): void
    {
        $this->data = [];
        $loadView = new \Core\ConfigView("sts/Views/home/homeView", $this->data);
        $loadView->loadView();
    }

    /**
     * Método para receber e cadastrar os dados do formulário de contato.
     * Realiza validações e interage com o model para salvar no banco de dados.
     * Envia uma resposta JSON para o JavaScript.
     *
     * @return void
     */
    public function cadastrar(): void
    {
        error_log('CONTROLLER HOME - Requisição POST para cadastrar recebida.'); // Log de entrada na função

        // Verifica se a requisição é do tipo POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recebe os dados do formulário e sanitiza usando FILTER_SANITIZE_FULL_SPECIAL_CHARS
            $this->formData = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            error_log('CONTROLLER HOME - Dados do formulário recebidos: ' . print_r($this->formData, true)); // Log dos dados recebidos

            $response = ['success' => false, 'message' => 'Erro ao enviar a mensagem.'];

            // Validação dos campos obrigatórios
            if (
                !empty($this->formData['nome']) &&
                !empty($this->formData['email']) &&
                !empty($this->formData['telefone']) &&
                !empty($this->formData['assunto']) &&
                !empty($this->formData['mensagem'])
            ) {
                error_log('CONTROLLER HOME - Validação dos campos obrigatórios passou.');
                // Remove espaços em branco no início e final das strings
                $this->formData = array_map('trim', $this->formData);
                error_log('CONTROLLER HOME - Dados do formulário após trim: ' . print_r($this->formData, true)); // Log após trim

                // Validação adicional do e-mail
                if (!filter_var($this->formData['email'], FILTER_VALIDATE_EMAIL)) {
                    error_log('CONTROLLER HOME - Erro de validação: e-mail inválido.');
                    $response['message'] = 'Por favor, informe um e-mail válido.';
                } else {
                    error_log('CONTROLLER HOME - Validação de e-mail passou.');

                    // Validação adicional do telefone (mínimo 10 dígitos)
                    $telefoneLimpo = preg_replace('/[^0-9]/', '', $this->formData['telefone']);
                    if (strlen($telefoneLimpo) < 10) {
                        error_log('CONTROLLER HOME - Erro de validação: telefone inválido (mínimo 10 dígitos).');
                        $response['message'] = 'Por favor, informe um telefone válido (com DDD).';
                    } else {
                        error_log('CONTROLLER HOME - Validação de telefone passou.');
                        // Cria uma instância do model StsHomeContato
                        $createContato = new \Sts\Models\StsHomeContato();

                        error_log('CONTROLLER HOME - Tentando cadastrar contato no banco de dados.');
                        // Chama o método para cadastrar a mensagem no banco de dados
                        if ($createContato->create($this->formData)) {
                            error_log('CONTROLLER HOME - Cadastro no banco de dados bem-sucedido.');
                            
                            // Enviar email de notificação para o administrador
                            $this->enviarEmailContato($this->formData);
                            
                            $response['success'] = true;
                            $response['message'] = 'Mensagem enviada com sucesso!';
                        } else {
                            error_log('CONTROLLER HOME - Falha ao cadastrar contato no banco de dados.');
                            $response['message'] = 'Erro ao salvar a mensagem no banco de dados.';
                        }
                    }
                }
            } else {
                error_log('CONTROLLER HOME - Erro de validação: campos obrigatórios não preenchidos.');
                $response['message'] = 'Por favor, preencha todos os campos obrigatórios.';
            }

            // Envia a resposta em formato JSON para o JavaScript
            header('Content-Type: application/json');
            echo json_encode($response);
            error_log('CONTROLLER HOME - Resposta JSON enviada: ' . json_encode($response)); // Log da resposta JSON
        } else {
            // Se a requisição não for POST, redireciona para a página inicial ou exibe um erro
            header("Location: /");
            die("Erro: Requisição inválida!");
        }
        error_log('CONTROLLER HOME - Fim da função cadastrar.'); // Log de saída da função
    }

    /**
     * Envia email de notificação para o administrador
     */
    private function enviarEmailContato(array $data): void
    {
        try {
            // Por enquanto, apenas log. Implementar PHPMailer depois
            error_log("EMAIL CONTATO STS: Nome: {$data['nome']}, Email: {$data['email']}, Assunto: {$data['assunto']}");
            
            // TODO: Implementar envio real de email com PHPMailer
            // $this->enviarEmailReal($data);
        } catch (Exception $e) {
            error_log("ERRO EMAIL CONTATO STS: " . $e->getMessage());
        }
    }
}