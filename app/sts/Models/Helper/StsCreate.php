<?php

namespace Sts\Models\Helper;

use PDO;
use PDOException;

// Redirecionar ou parar o processamento quando o usuário não acessa o arquivo index.php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class StsCreate extends StsConn
{
    private string $table;
    private array $data;
    private ?string $result = null;
    private object $insert;
    private string $query;
    private object $conn;

    /**
     * Retorna o status do cadastro, retorna o último id quando cadastrar com sucesso e null quando houver erro.
     *
     * @return string|null
     */
    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Cadastrar no banco de dados.
     *
     * @param string $table
     * @param array $data
     * @return void
     */
    public function exeCreate(string $table, array $data): void
    {
        $this->table = $table;
        $this->data = $data;
        error_log('HELPER STS CREATE - Método exeCreate() chamado. Tabela: ' . $this->table . ', Dados: ' . print_r($this->data, true)); // Log da chamada
        $this->exeReplaceValues();
    }

    /**
     * Cria a QUERY e os links da QUERY.
     *
     * @return void
     */
    private function exeReplaceValues(): void
    {
        $columns = implode(', ', array_keys($this->data));
        $placeholders = ':' . implode(', :', array_keys($this->data));
        $this->query = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        error_log('HELPER STS CREATE - Query SQL gerada: ' . $this->query); // Log da query SQL
        $this->exeInstruction();
    }

    /**
     * Executa a QUERY.
     * Quando a execução da query for bem-sucedida, retorna o último ID inserido. Caso contrário, retorna null.
     *
     * @return void
     */
    private function exeInstruction(): void
    {
        $this->connection();
        try {
            $this->insert->execute($this->data);
            $this->result = $this->conn->lastInsertId();
            error_log('HELPER STS CREATE - Query executada com sucesso. Último ID inserido: ' . $this->result); // Log de sucesso
        } catch (PDOException $err) {
            // Importante para debug
            error_log('HELPER STS CREATE - Erro PDO ao inserir: ' . $err->getMessage() . '. Query: ' . $this->query . '. Dados: ' . print_r($this->data, true));
            $_SESSION['cadastro_msg'] = '<div class="alert alert-danger">Erro ao inserir: ' . $err->getMessage() . '</div>';
            $this->result = null;
        }
    }

    /**
     * Obtém a conexão com o banco de dados da classe pai "StsConn".
     * Prepara uma instrução para execução e retorna um objeto de instrução.
     *
     * @return void
     */
    private function connection(): void
    {
        $this->conn = $this->connectDb();
        $this->insert = $this->conn->prepare($this->query);
    }
}