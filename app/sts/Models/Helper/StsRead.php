<?php
namespace Sts\Models\Helper;

use PDO;
use PDOException;

class StsRead extends StsConn
{
    private string $select;
    private array $values = [];
    private array|null $result = [];
    private object $query;
    private object $conn;

    public function getResult(): array|null
    {
        return $this->result;
    }

    public function exeRead(string $table, string|null $terms = null, string|null $parseString = null): void
    {
        if (!empty($parseString)) {
            parse_str($parseString, $this->values);
        }
        $this->select = "SELECT * FROM {$table} {$terms}";
        $this->exeInstruction();
    }

    public function fullRead(string $query, string|null $parseString = null): void
    {
        $this->select = $query;
        if (!empty($parseString)) {
            parse_str($parseString, $this->values);
        }
        $this->exeInstruction();
    }

    private function exeInstruction(): void
    {
        $this->connection();
        try {
            $this->exeParameter();
            $this->query->execute();
            $this->result = $this->query->fetchAll();
        } catch (PDOException $err) {
            $this->result = null;
        }
    }

    private function connection(): void
    {
        $this->conn = $this->connectDb();
        $this->query = $this->conn->prepare($this->select);
        $this->query->setFetchMode(PDO::FETCH_ASSOC);
    }

    private function exeParameter(): void
    {
        if ($this->values) {
            foreach ($this->values as $link => $value) {
                $value = is_numeric($value) ? (int)$value : $value;
                $this->query->bindValue(":{$link}", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        }
    }
}
