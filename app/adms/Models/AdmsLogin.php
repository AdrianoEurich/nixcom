<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;

class AdmsLogin extends StsConn
{
    private array $result = [
        'success' => false,
        'message' => '',
        'user' => null,
        'attempts_remaining' => null
    ];

    private object $conn;
    private const MAX_ATTEMPTS = 5;
    private const ATTEMPT_WINDOW_MINUTES = 15;

    // NOVO: Mapeamento de níveis de acesso de string para numérico
    private array $accessLevelMap = [
        'administrador' => 1, // Exemplo: Administrador tem nível 1
        'super_administrador' => 2, // Exemplo: Super Administrador tem nível 2
        'editor' => 3, // Exemplo: Editor tem nível 3
        'anunciante' => 4, // Exemplo: Anunciante tem nível 4
        'gerente' => 5, // Exemplo: Gerente tem nível 5
        'colaborador' => 6 // Exemplo: Colaborador tem nível 6
        // Adicione todos os níveis de acesso do seu banco de dados aqui
    ];

    public function __construct()
    {
        $this->conn = $this->connectDb();
        error_log("DEBUG: AdmsLogin::__construct - Conexão obtida de StsConn.");
    }

    public function verificarCredenciais(string $email, string $password): array
    {
        error_log("DEBUG: Início de verificarCredenciais para email: " . $email);

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

        error_log("DEBUG: Chamando buscarUsuarioPorEmail para: " . $email);
        $user = $this->buscarUsuarioPorEmail($email);

        if (!$user) {
            $this->result['message'] = "Credenciais inválidas";
            $this->registrarTentativa($email, false);
            error_log("DEBUG: Usuário NÃO encontrado no DB para email: " . $email);
            return $this->result;
        }

        error_log("DEBUG: Usuário ENCONTRADO. ID: " . $user['id'] . ", Email: " . $user['email'] . ", Status: " . ($user['status'] ?? 'N/A - Campo status não encontrado'));
        error_log("DEBUG: Senha Hashed do DB: " . $user['senha']);
        error_log("DEBUG: Senha digitada (plain): " . $password);


        if (isset($user['status'])) {
            if ($user['status'] === 'inativo') {
                $this->result['message'] = "Conta inativa. Entre em contato com o suporte.";
                $this->registrarTentativa($email, false);
                error_log("DEBUG: Conta inativa para email: " . $email);
                return $this->result;
            } elseif ($user['status'] === 'suspenso') {
                $this->result['message'] = "Conta suspensa. Entre em contato com o suporte.";
                $this->registrarTentativa($email, false);
                error_log("DEBUG: Conta suspensa para email: " . $email);
                return $this->result;
            } elseif ($user['status'] === 'bloqueado') {
                $this->result['message'] = "Conta bloqueada por segurança.";
                $this->registrarTentativa($email, false);
                error_log("DEBUG: Conta bloqueada para email: " . $email);
                return $this->result;
            } elseif ($user['status'] === 'deleted') {
                $this->result['message'] = "Sua conta foi excluída. Por favor, entre em contato com o suporte.";
                $this->registrarTentativa($email, false);
                error_log("DEBUG: Conta excluída para email: " . $email);
                return $this->result;
            }
        } else {
            $this->result['message'] = "Erro: Status da conta não pôde ser verificado.";
            $this->registrarTentativa($email, false);
            error_log("ERRO: Campo 'status' não encontrado para usuário: " . $email);
            return $this->result;
        }

        error_log("DEBUG: Verificando senha com password_verify...");
        if (!password_verify($password, $user['senha'])) {
            $this->result['message'] = "Credenciais inválidas";
            $this->registrarTentativa($email, false);
            error_log("DEBUG: password_verify FALHOU para email: " . $email);
            return $this->result;
        }
        error_log("DEBUG: password_verify SUCESSO para email: " . $email);

        $this->registrarTentativa($email, true);

        // --- CORREÇÃO AQUI: MAPEAMENTO DO NÍVEL DE ACESSO ---
        $numericAccessLevel = $this->accessLevelMap[strtolower($user['nivel_acesso'])] ?? 0;
        error_log("DEBUG ADMSLOGIN: Nível de acesso do DB (string): " . $user['nivel_acesso'] . " | Nível de acesso numérico mapeado: " . $numericAccessLevel);
        // --- FIM DA CORREÇÃO ---

        $this->result = [
            'success' => true,
            'message' => "Login bem-sucedido",
            'user' => [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'nivel_acesso' => $user['nivel_acesso'], // Manter a string se necessário para exibição
                'nivel_acesso_numeric' => $numericAccessLevel, // Adicionar o valor numérico
                'foto' => $user['foto'] ?? 'usuario.png',
                'ultimo_acesso' => $user['ultimo_acesso'] ?? null,
                'plan_type' => $user['plan_type'] ?? 'free',
                'payment_status' => $user['payment_status'] ?? 'pending'
            ],
            'attempts_remaining' => self::MAX_ATTEMPTS
        ];
        error_log("DEBUG: Login bem-sucedido para email: " . $email);
        return $this->result;
    }

    private function buscarUsuarioPorEmail(string $email): ?array
    {
        try {
            $query = "SELECT id, nome, email, senha, nivel_acesso, status, ultimo_acesso, foto, plan_type, payment_status
                      FROM usuarios
                      WHERE email = :email
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
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
            $query = "UPDATE usuarios SET foto = :foto WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':foto', $nomeArquivo);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar foto: " . $e->getMessage());
            return false;
        }
    }

    private function registrarTentativa(string $email, bool $sucesso): void
    {
        try {
            $query = "INSERT INTO login_tentativas
                      (email, sucesso, ip, user_agent, data_hora)
                      VALUES (:email, :sucesso, :ip, :user_agent, NOW())";

            $stmt = $this->conn->prepare($query);
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

    private function tentativasRecentes(string $email): int
    {
        try {
            $query = "SELECT COUNT(*)
                      FROM login_tentativas
                      WHERE email = :email
                      AND data_hora > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
                      AND sucesso = 0";

            $stmt = $this->conn->prepare($query);
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

    private function atualizarUltimoAcesso(int $userId): void
    {
        try {
            $query = "UPDATE usuarios
                      SET ultimo_acesso = NOW()
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            error_log("DEBUG: Último acesso atualizado para userId: " . $userId);
        } catch (PDOException $e) {
            error_log("DEBUG: Erro ao atualizar último acesso: " . $e->getMessage());
        }
    }
}