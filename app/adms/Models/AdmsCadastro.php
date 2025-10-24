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
use Adms\Models\AdmsNotificacao; // Para notificações automáticas
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

        if (!$this->checkUniqueCPF()) {
            return false;
        }

        return $this->insertUser();
    }

    private function validateInput(): bool
    {
        // Remove espaços em branco das entradas de string para dados mais limpos
        $this->formData['nome'] = trim($this->formData['nome'] ?? '');
        $this->formData['email'] = trim($this->formData['email'] ?? '');
        $this->formData['telefone'] = trim($this->formData['telefone'] ?? '');
        $this->formData['cpf'] = trim($this->formData['cpf'] ?? '');
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

        if (empty($this->formData['telefone'])) {
            $this->setMsg('danger', "Erro: Preencha o telefone!");
            return false;
        }

        if (empty($this->formData['cpf'])) {
            $this->setMsg('danger', "Erro: Preencha o CPF!");
            return false;
        }

        if (!$this->validateCPF($this->formData['cpf'])) {
            $this->setMsg('danger', "Erro: CPF inválido!");
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

    /**
     * Valida CPF brasileiro
     * @param string $cpf CPF para validar
     * @return bool True se válido, false caso contrário
     */
    private function validateCPF(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verifica se não são todos os dígitos iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Calcula o primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $firstDigit = ($remainder < 2) ? 0 : 11 - $remainder;
        
        // Verifica o primeiro dígito
        if (intval($cpf[9]) !== $firstDigit) {
            return false;
        }
        
        // Calcula o segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $secondDigit = ($remainder < 2) ? 0 : 11 - $remainder;
        
        // Verifica o segundo dígito
        if (intval($cpf[10]) !== $secondDigit) {
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
                $this->setMsg('danger', "Este e-mail já está cadastrado!");
                return false;
            }
            return true;
        } catch (PDOException $e) {
            $this->setMsg('danger', "Erro: Problema ao verificar e-mail. Tente novamente mais tarde.");
            error_log("AdmsCadastro - checkUniqueEmail PDOException: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->setMsg('danger', "Erro: Problema ao verificar e-mail. Tente novamente mais tarde.");
            error_log("AdmsCadastro - checkUniqueEmail Exception: " . $e->getMessage());
            return false;
        }
    }

    private function checkUniqueCPF(): bool
    {
        try {
            // Usa o helper Read do Sts
            $read = new StsRead(); 
            $cpfClean = preg_replace('/[^0-9]/', '', $this->formData['cpf']);
            $read->fullRead("SELECT id FROM usuarios WHERE cpf = :cpf LIMIT 1", "cpf={$cpfClean}");

            if ($read->getResult()) {
                $this->setMsg('danger', "Este CPF já está cadastrado!");
                return false;
            }
            return true;
        } catch (PDOException $e) {
            $this->setMsg('danger', "Erro: Problema ao verificar CPF. Tente novamente mais tarde.");
            error_log("AdmsCadastro - checkUniqueCPF PDOException: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->setMsg('danger', "Erro: Problema ao verificar CPF. Tente novamente mais tarde.");
            error_log("AdmsCadastro - checkUniqueCPF Exception: " . $e->getMessage());
            return false;
        }
    }

    private function insertUser(): bool
    {
        try {
            error_log("DEBUG AdmsCadastro: Iniciando insertUser");
            error_log("DEBUG AdmsCadastro: Dados recebidos: " . print_r($this->formData, true));
            
            // Remove a confirmação de senha do array de dados antes de inserir no banco
            unset($this->formData['confirmar_senha']); 

            // Captura o IP do usuário
            $userIp = $this->getUserIp();
            error_log("DEBUG AdmsCadastro: IP capturado: {$userIp}");

            // Recupera o plano selecionado da sessão ou dos dados
            $selectedPlan = $_SESSION['selected_plan'] ?? $this->formData['plan_type'] ?? 'free';
            error_log("DEBUG AdmsCadastro: Plano selecionado: {$selectedPlan}");
            
            // Validar plano selecionado
            $validPlans = ['free', 'basic', 'premium'];
            if (!in_array($selectedPlan, $validPlans)) {
                $selectedPlan = 'free'; // Fallback para plano gratuito
                error_log("DEBUG AdmsCadastro: Plano inválido, usando fallback: {$selectedPlan}");
            }

            $dataInsert = [
                'nome' => $this->formData['nome'],
                'email' => $this->formData['email'],
                'telefone' => $this->formData['telefone'],
                'cpf' => preg_replace('/[^0-9]/', '', $this->formData['cpf']), // Remove formatação do CPF
                'senha' => password_hash($this->formData['senha'], PASSWORD_DEFAULT),
                'plan_type' => $selectedPlan, // Incluir o plano selecionado
                'created' => date('Y-m-d H:i:s'),
                'registration_ip' => $userIp
            ];
            
            error_log("DEBUG AdmsCadastro: Dados para inserção: " . print_r($dataInsert, true));

            // Usa o helper Create do Sts
            $create = new StsCreate(); 
            error_log("DEBUG AdmsCadastro: StsCreate instanciado");
            
            $create->exeCreate("usuarios", $dataInsert);
            error_log("DEBUG AdmsCadastro: exeCreate executado");

            if ($create->getResult()) {
                $this->result = true;
                $this->setMsg('success', "Sucesso: Cadastro realizado com sucesso!");
                error_log("DEBUG AdmsCadastro: Usuário cadastrado com sucesso. ID: " . $create->getResult());
                
                // Fazer login automático do usuário
                $this->autoLogin($this->formData['email']);
                
                // Enviar notificação para administradores sobre novo usuário
                $this->notifyAdminsNewUser($create->getResult(), $selectedPlan);
                
                return true;
            } else {
                error_log("DEBUG AdmsCadastro: Falha no cadastro. Resultado: " . $create->getResult());
                $this->setMsg('danger', "Erro: Não foi possível completar o cadastro. Tente novamente!");
                return false;
            }
        } catch (PDOException $e) {
            $this->setMsg('danger', "Erro: Problema ao cadastrar. Tente novamente mais tarde.");
            error_log("AdmsCadastro - insertUser PDOException: " . $e->getMessage());
            error_log("AdmsCadastro - insertUser PDOException Trace: " . $e->getTraceAsString());
            return false;
        } catch (\Exception $e) {
            $this->setMsg('danger', "Erro: Problema ao cadastrar. Tente novamente mais tarde.");
            error_log("AdmsCadastro - insertUser Exception: " . $e->getMessage());
            error_log("AdmsCadastro - insertUser Exception Trace: " . $e->getTraceAsString());
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

    /**
     * Captura o IP real do usuário, considerando proxies e load balancers
     * @return string IP do usuário
     */
    private function getUserIp(): string
    {
        // Lista de headers que podem conter o IP real
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // IP direto
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Se for uma lista de IPs (separados por vírgula), pega o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Valida se é um IP válido
                // Em ambiente de desenvolvimento, aceita IPs privados também
                $flags = FILTER_FLAG_NO_RES_RANGE;
                if (!in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'])) {
                    $flags |= FILTER_FLAG_NO_PRIV_RANGE;
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
                    return $ip;
                }
            }
        }

        // Fallback para REMOTE_ADDR (pode ser IP privado)
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Faz login automático do usuário após cadastro
     * @param string $email Email do usuário
     */
    private function autoLogin(string $email): void
    {
        try {
            // Buscar dados do usuário recém-criado
            $read = new StsRead();
            $read->fullRead("SELECT id, nome, email, nivel_acesso, plan_type FROM usuarios WHERE email = :email LIMIT 1", "email={$email}");
            
            if ($read->getResult()) {
                $userData = $read->getResult()[0];
                
                // Definir variáveis de sessão
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_name'] = $userData['nome'];
                $_SESSION['user_email'] = $userData['email'];
                $_SESSION['user_role'] = $userData['nivel_acesso'];
                $_SESSION['user_plan'] = $userData['plan_type'];
                $_SESSION['logged_in'] = true;
                
                // Criar array usuario para compatibilidade com o topbar
                $_SESSION['usuario'] = [
                    'id' => $userData['id'],
                    'nome' => $userData['nome'],
                    'email' => $userData['email'],
                    'nivel_acesso' => $userData['nivel_acesso'],
                    'foto' => 'usuario.png',
                    'ultimo_acesso' => date('Y-m-d H:i:s')
                ];
                
                // Definir user_level_numeric baseado no nivel_acesso
                $userLevelName = $userData['nivel_acesso'];
                $numericUserLevel = 0;
                
                if ($userLevelName === 'administrador') {
                    $numericUserLevel = 3;
                } elseif ($userLevelName === 'usuario' || $userLevelName === 'normal') {
                    $numericUserLevel = 1;
                }
                
                $_SESSION['user_level_numeric'] = $numericUserLevel;
                
                error_log("DEBUG AdmsCadastro: Login automático realizado para usuário ID: {$userData['id']}, nivel_acesso: {$userLevelName}, user_level_numeric: {$numericUserLevel}");
            }
        } catch (PDOException $e) {
            error_log("AdmsCadastro - autoLogin PDOException: " . $e->getMessage());
        }
    }

    /**
     * Notifica administradores sobre novo usuário
     */
    private function notifyAdminsNewUser(int $userId, string $selectedPlan): void
    {
        try {
            $admsNotificacao = new AdmsNotificacao();
            $admsNotificacao->notifyNewUser(
                $userId,
                $this->formData['nome'],
                $this->formData['email'],
                $selectedPlan
            );
    } catch (\Exception $e) {
            error_log("AdmsCadastro - notifyAdminsNewUser: " . $e->getMessage());
        }
    }
}
