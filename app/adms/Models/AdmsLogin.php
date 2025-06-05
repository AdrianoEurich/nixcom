<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;

class AdmsLogin extends StsConn // Certifique-se de que StsConn está no namespace correto e é acessível
{
    private array $result = [
        'success' => false,
        'message' => '', // Mensagem de texto puro
        'user' => null,
        'attempts_remaining' => null
    ];

    private const MAX_ATTEMPTS = 5;
    private const ATTEMPT_WINDOW_MINUTES = 15;

    /**
     * Verifica credenciais do usuário
     * @param string $email
     * @param string $password
     * @return array Retorna array com resultado da autenticação
     */
    public function verificarCredenciais(string $email, string $password): array
    {
        error_log("DEBUG: Início de verificarCredenciais para email: " . $email);

        // Validações básicas (redundantes se já feitas no controller, mas seguro manter)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->result['message'] = "E-mail inválido";
            error_log("DEBUG: E-mail inválido: " . $email);
            return $this->result;
        }

        if (strlen($password) < 6) {
            $this->result['message'] = "Senha deve ter no mínimo 6 caracteres";
            error_log("DEBUG: Senha muito curta.");
            return $this->result;
        }

        // Verifica tentativas recentes para prevenir brute force
        $attempts = $this->tentativasRecentes($email);
        $remainingAttempts = self::MAX_ATTEMPTS - $attempts;
        $this->result['attempts_remaining'] = max(0, $remainingAttempts);
        error_log("DEBUG: Tentativas falhas recentes para " . $email . ": " . $attempts . " | Restantes: " . $remainingAttempts);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->result['message'] = "Muitas tentativas falhas. Tente novamente em " . self::ATTEMPT_WINDOW_MINUTES . " minutos.";
            $this->registrarTentativa($email, false);
            error_log("DEBUG: Bloqueado por muitas tentativas para: " . $email);
            return $this->result;
        }

        // Busca usuário no banco de dados
        error_log("DEBUG: Chamando buscarUsuarioPorEmail para: " . $email);
        $user = $this->buscarUsuarioPorEmail($email);

        if (!$user) {
            $this->result['message'] = "Credenciais inválidas"; // MENSAGEM ORIGINAL
            $this->registrarTentativa($email, false);
            error_log("DEBUG: Usuário NÃO encontrado no DB para email: " . $email);
            return $this->result;
        }

        error_log("DEBUG: Usuário ENCONTRADO. ID: " . $user['id'] . ", Email: " . $user['email'] . ", Status: " . ($user['status'] ?? 'N/A - Campo status não encontrado'));
        error_log("DEBUG: Senha Hashed do DB: " . $user['senha']);
        error_log("DEBUG: Senha digitada (plain): " . $password);


        // Verifica status da conta
        // Adicionada uma verificação para garantir que 'status' existe
        if (isset($user['status']) && $user['status'] !== 'ativo') {
            $statusMessage = [
                'inativo' => "Conta inativa. Entre em contato com o suporte.",
                'suspenso' => "Conta suspensa. Entre em contato com o suporte.",
                'bloqueado' => "Conta bloqueada por segurança."
            ];

            $this->result['message'] = $statusMessage[$user['status']] ?? "Conta não está ativa";
            $this->registrarTentativa($email, false);
            error_log("DEBUG: Conta não ativa para email: " . $email . ". Status: " . $user['status']);
            return $this->result;
        }

        // Verifica senha
        error_log("DEBUG: Verificando senha com password_verify...");
        if (!password_verify($password, $user['senha'])) {
            $this->result['message'] = "Credenciais inválidas"; // MENSAGEM ORIGINAL
            $this->registrarTentativa($email, false);
            error_log("DEBUG: password_verify FALHOU para email: " . $email);
            return $this->result;
        }
        error_log("DEBUG: password_verify SUCESSO para email: " . $email);

        // Atualiza último acesso e registra tentativa bem-sucedida
        $this->atualizarUltimoAcesso($user['id']);
        $this->registrarTentativa($email, true); // Registra sucesso

        // Prepara dados do usuário para sessão (sem informações sensíveis)
        $this->result = [
            'success' => true,
            'message' => "Login bem-sucedido", // Mensagem de texto puro
            'user' => [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'nivel_acesso' => $user['nivel_acesso'],
                'foto' => $user['foto'] ?? 'usuario.png', // <<< AQUI ESTÁ A LINHA ADICIONADA!
                'ultimo_acesso' => $user['ultimo_acesso'] ?? null
            ],
            'attempts_remaining' => self::MAX_ATTEMPTS // Reseta contagem após login bem-sucedido
        ];
        error_log("DEBUG: Login bem-sucedido para email: " . $email);
        return $this->result;
    }

    /**
     * Busca usuário por email
     * @param string $email
     * @return array|null
     */
    private function buscarUsuarioPorEmail(string $email): ?array
    {
        try {
            $conn = $this->connectDb(); // Chama o método connectDb da classe pai ou deste mesmo modelo
            $query = "SELECT id, nome, email, senha, nivel_acesso, status, ultimo_acesso, foto
                      FROM usuarios
                      WHERE email = :email
                      LIMIT 1";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("DEBUG: Erro ao buscar usuário no DB: " . $e->getMessage());
            return null;
        }
    }


    public function atualizarFoto(int $userId, string $nomeArquivo): bool
    {
        try {
            $conn = $this->connectDb();
            $query = "UPDATE usuarios SET foto = :foto WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':foto', $nomeArquivo);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar foto: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Registra tentativa de login
     * @param string $email
     * @param bool $sucesso
     */
    private function registrarTentativa(string $email, bool $sucesso): void
    {
        try {
            $conn = $this->connectDb();
            $query = "INSERT INTO login_tentativas
                      (email, sucesso, ip, user_agent, data_hora)
                      VALUES (:email, :sucesso, :ip, :user_agent, NOW())";

            $stmt = $conn->prepare($query);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':sucesso', $sucesso, PDO::PARAM_BOOL);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido', PDO::PARAM_STR);
            $stmt->execute();
            error_log("DEBUG: Tentativa de login registrada: email=" . $email . ", sucesso=" . ($sucesso ? 'true' : 'false'));
        } catch (PDOException $e) {
            error_log("DEBUG: Erro ao registrar tentativa de login: " . $e->getMessage());
        }
    }

    /**
     * Conta tentativas recentes falhas
     * @param string $email
     * @return int Número de tentativas falhas na janela de tempo
     */
    private function tentativasRecentes(string $email): int
    {
        try {
            $conn = $this->connectDb();
            $query = "SELECT COUNT(*)
                      FROM login_tentativas
                      WHERE email = :email
                      AND data_hora > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
                      AND sucesso = 0"; // Apenas tentativas falhas

            $stmt = $conn->prepare($query);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':minutes', self::ATTEMPT_WINDOW_MINUTES, PDO::PARAM_INT);
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            error_log("DEBUG: Contagem de tentativas recentes para " . $email . ": " . $count);
            return $count;
        } catch (PDOException $e) {
            error_log("DEBUG: Erro ao contar tentativas recentes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Atualiza data do último acesso
     * @param int $userId
     */
    private function atualizarUltimoAcesso(int $userId): void
    {
        try {
            $conn = $this->connectDb();
            $query = "UPDATE usuarios
                      SET ultimo_acesso = NOW()
                      WHERE id = :id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            error_log("DEBUG: Último acesso atualizado para userId: " . $userId);
        } catch (PDOException $e) {
            error_log("DEBUG: Erro ao atualizar último acesso: " . $e->getMessage());
        }
    }

    /**
     * Sobrescreve ou define o método de conexão com o banco de dados.
     * Deve ter a mesma visibilidade (protected) ou mais permissiva que o método na classe pai (StsConn).
     * MUDADO PARA PROTECTED
     */
    protected function connectDb(): PDO
    {
        try {
            // Se StsConn já tem um connectDb() e é protected, você pode chamar parent::connectDb()
            // ou ter sua própria implementação se precisar de lógica diferente aqui.
            // Para simplificar, vou manter sua implementação direta, mas com a visibilidade correta.

            $conn = new PDO("mysql:host=" . HOST . ";dbname=" . DBNAME, USER, PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("DEBUG: Conexão com o DB estabelecida com sucesso via AdmsLogin::connectDb().");
            return $conn;
        } catch (PDOException $e) {
            error_log("ERRO FATAL DB: Falha na conexão com o banco de dados em AdmsLogin::connectDb(): " . $e->getMessage());
            die("Erro: Falha na conexão com o banco de dados! Por favor, tente novamente mais tarde.");
        }
    }
}