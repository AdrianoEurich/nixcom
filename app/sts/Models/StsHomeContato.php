<?php
namespace Sts\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Classe StsHomeContato - Processa o formulário de contato
 *
 * Responsável por:
 * - Validar dados (a validação principal será na Controller)
 * - Inserir no banco de dados
 * - Retornar feedback (sucesso/erro)
 */
class StsHomeContato
{
    /** @var array|null $data Recebe os dados do formulário */
    private ?array $data;

    /** @var bool $result Retorna true em caso de sucesso e false em caso de erro */
    private bool $result = false;

    /**
     * Cadastra a mensagem de contato no banco de dados.
     *
     * @param array|null $data Recebe os dados do formulário de contato.
     * @return boolean Retorna true em caso de sucesso e false em caso de erro.
     */
    public function create(?array $data): bool
    {
        $this->data = $data;
        error_log('MODEL STS HOMECONTATO - Método create() chamado. Dados: ' . print_r($this->data, true)); // Log dos dados recebidos

        if ($this->insert()) {
            error_log('MODEL STS HOMECONTATO - Inserção bem-sucedida.');
            return true;
        } else {
            error_log('MODEL STS HOMECONTATO - Falha na inserção.');
            return false;
        }
    }

    /**
     * Realiza a inserção dos dados no banco de dados, removendo a máscara do telefone.
     *
     * @return void
     */
    private function insert(): bool
    {
        $stsCreate = new \Sts\Models\Helper\StsCreate();

        // Remove a máscara do telefone
        $telefoneLimpo = preg_replace('/[^0-9]/', '', $this->data['telefone']);
        error_log('MODEL STS HOMECONTATO - Telefone limpo: ' . $telefoneLimpo); // Adicione este log para verificar

        $dataInsert = [
            'nomeCompleto' => $this->data['nome'], // Ajuste para 'nomeCompleto'
            'email' => $this->data['email'],
            'telefone' => $telefoneLimpo, // Salva o telefone sem máscara
            'assunto' => $this->data['assunto'],
            'mensagem' => $this->data['mensagem'],
            'dataCriacao' => date('Y-m-d H:i:s') // Adiciona a data de criação
        ];
        error_log('MODEL STS HOMECONTATO - Dados para inserção: ' . print_r($dataInsert, true)); // Log dos dados que serão inseridos
        $stsCreate->exeCreate("formulario_contato", $dataInsert); // Use o nome correto da tabela

        if ($stsCreate->getResult()) {
            $this->result = true;
            error_log('MODEL STS HOMECONTATO - Resultado da inserção: sucesso. Último ID: ' . $stsCreate->getResult()); // Log de sucesso com o ID
        } else {
            $this->result = false;
            error_log('MODEL STS HOMECONTATO - Resultado da inserção: falha.'); // Log de falha
        }

        return $this->result;
    }
}