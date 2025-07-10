<?php
namespace Adms\Models;

// Verificação de segurança: garantir que o arquivo seja acessado via a constante definida
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Importar classes de helper necessárias do namespace Sts\Models\Helper
use Sts\Models\Helper\StsCreate; // Usar o helper Create do Sts
use Sts\Models\Helper\StsRead;   // Usar o helper Read do Sts
use PDOException;

class AdmsCadastro
{
    private array $formData;
    private bool $result = false;
    private array $msg = ['type' => 'info', 'text' => '']; // Mensagem de texto puro

    public function getResult(): bool { return $this->result; }
    public function getMsg(): array { return $this->msg; }

    public function create(array $data): bool
    {
        $this->formData = $data;

        if (!$this->validateInput()) {
            return false;
        }

        if (!$this->checkUniqueEmail()) {
            return false;
        }

        return $this->insertUser();
    }

    private function validateInput(): bool
    {
        // Remove espaços em branco das entradas de string para dados mais limpos
        $this->formData['nome'] = trim($this->formData['nome'] ?? '');
        $this->formData['email'] = trim($this->formData['email'] ?? '');
        $this->formData['senha'] = $this->formData['senha'] ?? '';
        $this->formData['confirmar_senha'] = $this->formData['confirmar_senha'] ?? '';

        if (empty($this->formData['nome'])) {
            $this->setMsg('danger', "Erro: Preencha o nome completo!");
            return false;
        }

        if (empty($this->formData['email'])) {
            $this->setMsg('danger', "Erro: Preencha o e-mail!");
            return false;
        }

        if (!filter_var($this->formData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setMsg('danger', "Erro: E-mail inválido!");
            return false;
        }

        if (empty($this->formData['senha'])) {
            $this->setMsg('danger', "Erro: Preencha a senha!");
            return false;
        }

        if (strlen($this->formData['senha']) < 6) {
            $this->setMsg('danger', "Erro: A senha deve ter no mínimo 6 caracteres!");
            return false;
        }

        if (empty($this->formData['confirmar_senha'])) {
            $this->setMsg('danger', "Erro: Confirme a senha!");
            return false;
        }

        if ($this->formData['senha'] !== $this->formData['confirmar_senha']) {
            $this->setMsg('danger', "Erro: As senhas não coincidem!");
            return false;
        }

        return true;
    }

    private function checkUniqueEmail(): bool
    {
        try {
            // Usa o helper Read do Sts
            $read = new StsRead(); 
            $read->fullRead("SELECT id FROM usuarios WHERE email = :email LIMIT 1", "email={$this->formData['email']}");

            if ($read->getResult()) {
                $this->setMsg('danger', "Erro: Este e-mail já está cadastrado!");
                return false;
            }
            return true;
        } catch (PDOException $e) {
            $this->setMsg('danger', "Erro: Problema ao verificar e-mail. Tente novamente mais tarde.");
            error_log("AdmsCadastro - checkUniqueEmail PDOException: " . $e->getMessage());
            return false;
        }
    }

    private function insertUser(): bool
    {
        try {
            // Remove a confirmação de senha do array de dados antes de inserir no banco
            unset($this->formData['confirmar_senha']); 

            $dataInsert = [
                'nome' => $this->formData['nome'],
                'email' => $this->formData['email'],
                'senha' => password_hash($this->formData['senha'], PASSWORD_DEFAULT),
                'created' => date('Y-m-d H:i:s')
            ];

            // Usa o helper Create do Sts
            $create = new StsCreate(); 
            $create->exeCreate("usuarios", $dataInsert);

            if ($create->getResult()) {
                $this->result = true;
                $this->setMsg('success', "Sucesso: Cadastro realizado com sucesso!");
                return true;
            }

            $this->setMsg('danger', "Erro: Não foi possível completar o cadastro. Tente novamente!");
            return false;
        } catch (PDOException $e) {
            $this->setMsg('danger', "Erro: Problema ao cadastrar. Tente novamente mais tarde.");
            error_log("AdmsCadastro - insertUser PDOException: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método auxiliar para definir o array da mensagem facilmente.
     * @param string $type 'success', 'danger', 'info', 'warning'
     * @param string $text O texto da mensagem
     */
    private function setMsg(string $type, string $text): void
    {
        $this->msg = ['type' => $type, 'text' => $text];
    }
}
