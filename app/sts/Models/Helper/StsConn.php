<?php
namespace Sts\Models\Helper;

use PDO;
use PDOException;

abstract class StsConn
{
    // Configurações do banco de dados (definidas em algum arquivo de configuração)
    private string $host = HOST;
    private string $user = USER;
    private string $pass = PASS;
    private string $dbname = DBNAME;
    private $port = PORT;
    private ?PDO $connect = null;

    /**
     * Estabelece conexão com o banco de dados
     * @return PDO Objeto PDO para interação com o banco
     * @throws PDOException Em caso de falha na conexão
     */
    protected function connectDb(): PDO
    {
        if ($this->connect === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                
                $this->connect = new PDO($dsn, $this->user, $this->pass, $options);
                
            } catch (PDOException $e) {
                error_log("Erro de conexão PDO: " . $e->getMessage());
                throw new PDOException("Falha na conexão com o banco de dados. Tente novamente mais tarde.");
            }
        }
        return $this->connect;
    }
}