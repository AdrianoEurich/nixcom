<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Usar a classe de conexão existente do módulo Sts
use Sts\Models\Helper\StsConn; 
use PDOException; // Importa PDOException para tratamento de erros específicos do PDO
use Exception; // Importa Exception para tratamento de erros gerais

/**
 * Classe AdmsUser
 * Modelo responsável por interagir com a tabela de usuários no banco de dados.
 * Estende StsConn para gerenciar a conexão com o banco de dados.
 */
class AdmsUser extends StsConn
{
    private object $conn; // Armazena a conexão PDO
    private array $msg = []; // Mensagens de erro ou sucesso
    private bool $result; // Resultado da operação (sucesso/falha)

    public function __construct()
    {
        // Chama o método connectDb da classe pai (StsConn) para obter a conexão PDO
        $this->conn = $this->connectDb();
    }

    /**
     * Retorna o resultado da operação (true para sucesso, false para falha).
     * @return bool
     */
    public function getResult(): bool
    {
        return $this->result;
    }

    /**
     * Retorna a mensagem de erro/sucesso.
     * @return array
     */
    public function getMsg(): array
    {
        return $this->msg;
    }

    /**
     * Obtém o tipo de plano de um usuário pelo ID.
     * @param int $userId ID do usuário.
     * @return string Retorna 'premium' ou 'free'. Padrão 'free' se não encontrado.
     */
    public function getUserPlanType(int $userId): string
    {
        try {
            $query = "SELECT plan_type FROM usuarios WHERE id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result && isset($result['plan_type'])) {
                return $result['plan_type'];
            }
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUserPlanType: " . $e->getMessage());
            // Em caso de erro, retorna 'free' para não bloquear o usuário
        }
        return 'free'; // Padrão seguro se o usuário não for encontrado ou houver erro
    }

    /**
     * Cria um novo usuário no banco de dados.
     * Inclui o IP de registro e define o status inicial.
     *
     * @param array $userData Array associativo com os dados do usuário.
     * Esperado: 'nome', 'email', 'senha', 'nivel_acesso', 'foto', 'registration_ip'.
     * @return bool True em caso de sucesso, false em caso de falha.
     */
    public function createUser(array $userData): bool
    {
        $this->result = false;
        try {
            $query = "INSERT INTO usuarios (
                nome, email, senha, nivel_acesso, foto, ultimo_acesso, registration_ip, created_at, status, has_anuncio, anuncio_status
            ) VALUES (
                :nome, :email, :senha, :nivel_acesso, :foto, NOW(), :registration_ip, NOW(), :status, :has_anuncio, :anuncio_status
            )";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':nome', $userData['nome'], \PDO::PARAM_STR);
            $stmt->bindParam(':email', $userData['email'], \PDO::PARAM_STR);
            $stmt->bindParam(':senha', $userData['senha'], \PDO::PARAM_STR);
            $stmt->bindParam(':nivel_acesso', $userData['nivel_acesso'], \PDO::PARAM_STR); // Assumindo que nivel_acesso é string (enum)
            $stmt->bindParam(':foto', $userData['foto'], \PDO::PARAM_STR);
            $stmt->bindParam(':registration_ip', $userData['registration_ip'], \PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ativo', \PDO::PARAM_STR); // Status padrão para novo usuário
            $stmt->bindValue(':has_anuncio', 0, \PDO::PARAM_INT); // Padrão: não tem anúncio
            $stmt->bindValue(':anuncio_status', 'not_found', \PDO::PARAM_STR); // Padrão: anúncio não encontrado

            if ($stmt->execute()) {
                $lastInsertId = $this->conn->lastInsertId();
                error_log("DEBUG AdmsUser::createUser: Usuário criado com sucesso. ID: {$lastInsertId}, IP: {$userData['registration_ip']}");
                $this->result = true;
                $this->msg = ['type' => 'success', 'text' => 'Usuário cadastrado com sucesso!'];
                return true;
            } else {
                error_log("ERRO PDO AdmsUser::createUser: Falha ao criar usuário. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao cadastrar usuário. Por favor, tente novamente.'];
                return false;
            }
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::createUser: Exceção ao criar usuário: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao cadastrar usuário.'];
            return false;
        }
    }

    /**
     * Realiza o soft delete de um usuário, marcando-o como deletado.
     * Define a coluna 'deleted_at' com a data e hora atuais e o status como 'deleted'.
     * Também soft-deleta o anúncio associado, se existir.
     *
     * @param int $userId O ID do usuário a ser soft-deletado.
     * @return bool True se o soft delete for bem-sucedido, False caso contrário.
     */
    public function softDeleteUser(int $userId): bool
    {
        $this->result = false;
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE usuarios SET status = 'deleted', deleted_at = NOW() WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            $result = $stmt->execute();

            if (!$result) {
                $this->conn->rollBack();
                error_log("ERRO PDO AdmsUser::softDeleteUser: Falha ao realizar soft delete do usuário ID: {$userId}. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao marcar usuário como excluído.'];
                return false;
            }

            // Se o usuário foi soft-deletado, também soft-deleta o anúncio associado
            $admsAnuncio = new AdmsAnuncio();
            // Busca o anúncio, incluindo os deletados, para garantir que o soft delete seja aplicado
            $anuncio = $admsAnuncio->getAnuncioByUserId($userId, true); 
            if ($anuncio) {
                // Chama updateAnuncioStatus para marcar o anúncio como 'deleted'
                $anuncioSoftDeleteResult = $admsAnuncio->updateAnuncioStatus($anuncio['id'], 'deleted', $userId);
                if (!$anuncioSoftDeleteResult) {
                    $this->conn->rollBack();
                    error_log("ERRO AdmsUser::softDeleteUser: Falha ao soft-deletar anúncio ID: " . $anuncio['id'] . " para o usuário ID: {$userId}.");
                    $this->msg = ['type' => 'error', 'text' => 'Usuário excluído, mas houve um erro ao excluir o anúncio associado.'];
                    return false;
                }
            }

            $this->conn->commit();
            error_log("DEBUG AdmsUser::softDeleteUser: Usuário ID: {$userId} e anúncio associado (se houver) soft-deletados com sucesso.");
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Usuário marcado como excluído com sucesso!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERRO PDO AdmsUser::softDeleteUser: Exceção ao realizar soft delete do usuário: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao excluir usuário.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL AdmsUser::softDeleteUser: Exceção ao realizar soft delete do usuário: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao excluir usuário.'];
            return false;
        }
    }

    /**
     * Ativa uma conta de usuário soft-deletada.
     * Define a coluna 'deleted_at' como NULL e o status para 'ativo'.
     * Também reativa o anúncio associado (se ele estiver 'deleted'), mudando seu status para 'pending'.
     *
     * @param int $userId O ID do usuário a ser ativado.
     * @return bool True se a ativação for bem-sucedida, False caso contrário.
     */
    public function activateUser(int $userId): bool
    {
        $this->result = false;
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE usuarios SET status = 'ativo', deleted_at = NULL WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            $result = $stmt->execute();

            if (!$result) {
                $this->conn->rollBack();
                error_log("ERRO PDO AdmsUser::activateUser: Falha ao ativar usuário ID: {$userId}. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao ativar usuário.'];
                return false;
            }

            // Se o usuário foi ativado, verifica se ele tem um anúncio 'deleted' e o reativa para 'pending'
            $admsAnuncio = new AdmsAnuncio();
            // CORREÇÃO AQUI: Passa true para getAnuncioByUserId para que ele busque anúncios deletados também
            $anuncio = $admsAnuncio->getAnuncioByUserId($userId, true); 
            
            if ($anuncio && $anuncio['status'] === 'deleted') {
                // Muda o status do anúncio para 'pending' após a reativação da conta do usuário
                $anuncioActivateResult = $admsAnuncio->updateAnuncioStatus($anuncio['id'], 'pending', $userId); 
                if (!$anuncioActivateResult) {
                    $this->conn->rollBack();
                    error_log("ERRO AdmsUser::activateUser: Falha ao reativar anúncio ID: " . $anuncio['id'] . " para o usuário ID: {$userId}.");
                    $this->msg = ['type' => 'error', 'text' => 'Usuário ativado, mas houve um erro ao reativar o anúncio associado.'];
                    return false;
                }
            }

            $this->conn->commit();
            error_log("DEBUG AdmsUser::activateUser: Usuário ID: {$userId} e anúncio associado (se 'deleted') ativados com sucesso.");
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Usuário ativado com sucesso!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERRO PDO AdmsUser::activateUser: Exceção ao ativar usuário: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao ativar usuário.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL AdmsUser::activateUser: Exceção ao ativar usuário: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao ativar usuário.'];
            return false;
        }
    }

    /**
     * Atualiza o perfil de um usuário.
     * @param array $userData Dados do perfil a serem atualizados (nome, email, etc.).
     * @param int $userId ID do usuário a ser atualizado.
     * @return bool True se a atualização for bem-sucedida, False caso contrário.
     */
    public function updateProfile(array $userData, int $userId): bool
    {
        $this->result = false;
        try {
            $query = "UPDATE usuarios SET nome = :nome, email = :email, updated_at = NOW() WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $userData['nome'], \PDO::PARAM_STR);
            $stmt->bindParam(':email', $userData['email'], \PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $this->result = true;
                $this->msg = ['type' => 'success', 'text' => 'Perfil atualizado com sucesso!'];
                return true;
            } else {
                error_log("ERRO PDO AdmsUser::updateProfile: Falha ao atualizar perfil. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar perfil. Por favor, tente novamente.'];
                return false;
            }
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::updateProfile: Exceção ao atualizar perfil: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao atualizar perfil.'];
            return false;
        }
    }

    /**
     * Atualiza a senha de um usuário.
     * @param array $userData Dados da senha a serem atualizados (nova_senha).
     * @param int $userId ID do usuário a ter a senha atualizada.
     * @return bool True se a atualização for bem-sucedida, False caso contrário.
     */
    public function updatePassword(array $userData, int $userId): bool
    {
        $this->result = false;
        try {
            // Hash da nova senha
            $hashedPassword = password_hash($userData['nova_senha'], PASSWORD_DEFAULT);

            $query = "UPDATE usuarios SET senha = :senha, updated_at = NOW() WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':senha', $hashedPassword, \PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->result = true;
                $this->msg = ['type' => 'success', 'text' => 'Senha atualizada com sucesso!'];
                return true;
            } else {
                error_log("ERRO PDO AdmsUser::updatePassword: Falha ao atualizar senha. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar senha. Por favor, tente novamente.'];
                return false;
            }
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::updatePassword: Exceção ao atualizar senha: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao atualizar senha.'];
            return false;
        }
    }

    /**
     * Obtém os dados de um usuário pelo email.
     * Pode incluir usuários marcados como deletados se $includeDeleted for true.
     *
     * @param string $email O email do usuário.
     * @param bool $includeDeleted Se true, inclui usuários marcados como deletados.
     * @return array|null Os dados do usuário ou null se não encontrado.
     */
    public function getUserByEmail(string $email, bool $includeDeleted = false): ?array
    {
        $sql = "SELECT id, nome, email, senha, nivel_acesso, foto, deleted_at, plan_type, has_anuncio, anuncio_status, ultimo_acesso FROM usuarios WHERE email = :email";
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUserByEmail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém os dados de um usuário pelo ID.
     * Pode incluir usuários marcados como deletados se $includeDeleted for true.
     *
     * @param int $userId O ID do usuário.
     * @param bool $includeDeleted Se true, inclui usuários marcados como deletados.
     * @return array|null Os dados do usuário ou null se não encontrado.
     */
    public function getUserById(int $userId, bool $includeDeleted = false): ?array
    {
        $sql = "SELECT id, nome, email, nivel_acesso, foto, ultimo_acesso, deleted_at, registration_ip, plan_type, has_anuncio, anuncio_status FROM usuarios WHERE id = :id";
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUserById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém dados de usuários para listagem paginada, com busca e filtro de status.
     *
     * @param int $page A página atual.
     * @param int $limit O número de registros por página.
     * @param string $searchTerm Termo de busca para nome ou e-mail.
     * @param string $filterStatus Status para filtrar ('all', 'ativo', 'inativo', 'pendente', 'bloqueado', 'suspenso', 'deleted').
     * @return array Um array de usuários.
     */
    public function getUsers(int $page, int $limit, string $searchTerm = '', string $filterStatus = 'all'): array
    {
        $offset = ($page - 1) * $limit;
        $query = "SELECT id, nome, email, nivel_acesso, status, ultimo_acesso, deleted_at, registration_ip FROM usuarios WHERE 1=1"; // Adicionado registration_ip
        $binds = [];

        if (!empty($searchTerm)) {
            $query .= " AND (nome LIKE :search_term OR email LIKE :search_term)";
            $binds[':search_term'] = '%' . $searchTerm . '%';
        }

        if ($filterStatus !== 'all') {
            if ($filterStatus === 'deleted') {
                $query .= " AND deleted_at IS NOT NULL";
            } else {
                $query .= " AND status = :status AND deleted_at IS NULL"; // Garante que não busca deletados para outros status
                $binds[':status'] = $filterStatus;
            }
        } else {
            // Se 'all', por padrão, não queremos os deletados, a menos que o filtro 'deleted' seja explícito.
            // Então, para 'all', filtramos por deleted_at IS NULL.
            $query .= " AND deleted_at IS NULL"; 
        }

        $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $binds[':limit'] = $limit;
        $binds[':offset'] = $offset;

        error_log("DEBUG AdmsUser::getUsers - Query FINAL: " . $query);
        error_log("DEBUG AdmsUser::getUsers - Binds FINAIS: " . print_r($binds, true));

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($binds as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, \PDO::PARAM_STR);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna o total de usuários para a paginação, com base no termo de busca e status.
     * @param string $searchTerm Termo de busca para nome ou e-mail.
     * @param string $filterStatus Status para filtrar ('all', 'ativo', 'inativo', 'pendente', 'bloqueado', 'suspenso', 'deleted').
     * @return int O total de usuários.
     */
    public function getTotalUsers(string $searchTerm = '', string $filterStatus = 'all'): int
    {
        $query = "SELECT COUNT(id) AS total FROM usuarios WHERE 1=1";
        $binds = [];

        if (!empty($searchTerm)) {
            $query .= " AND (nome LIKE :search_term OR email LIKE :search_term)";
            $binds[':search_term'] = '%' . $searchTerm . '%';
        }

        if ($filterStatus !== 'all') {
            if ($filterStatus === 'deleted') {
                $query .= " AND deleted_at IS NOT NULL";
            } else {
                $query .= " AND status = :status AND deleted_at IS NULL"; // Garante que não busca deletados para outros status
                $binds[':status'] = $filterStatus;
            }
        } else {
            // Se 'all', por padrão, não queremos os deletados, a menos que o filtro 'deleted' seja explícito.
            // Então, para 'all', filtramos por deleted_at IS NULL.
            $query .= " AND deleted_at IS NULL"; 
        }

        error_log("DEBUG AdmsUser::getTotalUsers - Query FINAL: " . $query);
        error_log("DEBUG AdmsUser::getTotalUsers - Binds FINAIS: " . print_r($binds, true));

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value, \PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getTotalUsers: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Atualiza a data do último acesso de um usuário.
     * @param int $userId O ID do usuário.
     * @return bool True se a atualização for bem-sucedida, False caso contrário.
     */
    public function updateLastAccess(int $userId): bool
    {
        try {
            $query = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::updateLastAccess: " . $e->getMessage());
            return false;
        }
    }
}
