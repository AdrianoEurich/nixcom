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
 * REGRA: Nunca existe exclusão isolada de anúncio. Só existe "Excluir Conta", que desativa todos os anúncios vinculados.
 */
class AdmsUser extends StsConn
{
    private object $conn; // Armazena a conexão PDO
    private array $msg = [];
    private bool $result;
    private string $projectRoot; // Caminho raiz do projeto
    // Debug state (temporário): últimas queries montadas
    private string $lastListQuery = '';
    private array $lastListParams = [];
    private array $lastListDetectedColumns = [];
    private string $lastCountQuery = '';
    private array $lastCountParams = [];
    private array $lastCountDetectedColumns = [];

    public function __construct()
    {
        $this->conn = $this->connectDb();
        $this->projectRoot = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/';
    }

    /**
     * Atualiza parcialmente campos do usuário (ex.: nome, email, telefone, cpf)
     */
    public function updateUserPartial(int $userId, array $data): bool
    {
        try {
            $allowed = ['nome','email','telefone','cpf'];
            // Detectar colunas existentes na tabela para evitar erros
            $cols = method_exists($this, 'getTableColumns') ? $this->getTableColumns('usuarios') : $allowed;
            $toUpdate = [];
            $params = [':id' => $userId];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $data) && in_array($key, $cols, true) && $data[$key] !== null) {
                    $toUpdate[$key] = $data[$key];
                }
            }
            if (empty($toUpdate)) {
                return false; // nada para atualizar
            }
            $setParts = [];
            foreach ($toUpdate as $col => $val) {
                $ph = ':' . $col;
                $setParts[] = "$col = $ph";
                $params[$ph] = $val;
            }
            $setParts[] = 'updated_at = NOW()';
            $sql = 'UPDATE usuarios SET ' . implode(', ', $setParts) . ' WHERE id = :id';
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, \PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('ERRO PDO AdmsUser::updateUserPartial: ' . $e->getMessage());
            return false;
        }
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function getMsg(): array
    {
        return $this->msg;
    }

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
        }
        return 'free';
    }

    /**
     * Busca todos os usuários para o painel administrativo
     */
    public function getAllUsers(): array
    {
        try {
            $query = "SELECT u.id, u.nome, u.email, u.plan_type, u.status, u.payment_status, 
                             COALESCE(u.created_at, u.created) AS created, u.ultimo_acesso,
                             COUNT(a.id) as total_anuncios
                      FROM usuarios u 
                      LEFT JOIN anuncios a ON u.id = a.user_id 
                      WHERE u.nivel_acesso = 'usuario'
                      GROUP BY u.id 
                      ORDER BY COALESCE(u.created_at, u.created) DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getAllUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca dados de um usuário específico
     */
    public function getUserById(int $userId): ?array
    {
        try {
            $query = "SELECT * FROM usuarios WHERE id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUserById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza status do usuário
     */
    public function updateUserStatus(int $userId, string $status): bool
    {
        try {
            $query = "UPDATE usuarios SET status = :status, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status, \PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::updateUserStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza plano do usuário
     */
    public function updateUserPlan(int $userId, string $planType): bool
    {
        try {
            $query = "UPDATE usuarios SET plan_type = :plan_type, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':plan_type', $planType, \PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::updateUserPlan: " . $e->getMessage());
            return false;
        }
    }

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
            $stmt->bindParam(':nivel_acesso', $userData['nivel_acesso'], \PDO::PARAM_STR);
            $stmt->bindParam(':foto', $userData['foto'], \PDO::PARAM_STR);
            $stmt->bindParam(':registration_ip', $userData['registration_ip'], \PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ativo', \PDO::PARAM_STR);
            $stmt->bindValue(':has_anuncio', 0, \PDO::PARAM_INT);
            $stmt->bindValue(':anuncio_status', 'not_found', \PDO::PARAM_STR);

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
     * Realiza o soft delete de um usuário (desativa a conta).
     * REGRA: Nunca existe exclusão isolada de anúncio.
     * Ao excluir a conta, todos os anúncios vinculados são desativados.
     */
    public function softDeleteUser(int $userId): bool
    {
        $this->result = false;
        try {
            $this->conn->beginTransaction();

            // Soft delete do usuário (desativar conta)
            $query = "UPDATE usuarios SET nivel_acesso = 'usuario', status = 'inativo', updated_at = NOW() WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            $result = $stmt->execute();

            if (!$result) {
                $this->conn->rollBack();
                error_log("ERRO PDO AdmsUser::softDeleteUser: Falha ao desativar usuário ID: {$userId}. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao desativar usuário.'];
                return false;
            }

            // Desativar todos os anúncios do usuário
            $queryAnuncios = "UPDATE anuncios SET status = 'deleted', updated_at = NOW() WHERE user_id = :user_id";
            $stmtAnuncios = $this->conn->prepare($queryAnuncios);
            $stmtAnuncios->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $resultAnuncios = $stmtAnuncios->execute();

            if (!$resultAnuncios) {
                $this->conn->rollBack();
                error_log("ERRO PDO AdmsUser::softDeleteUser: Falha ao desativar anúncios do usuário ID: {$userId}. Erro: " . json_encode($stmtAnuncios->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao desativar anúncios do usuário.'];
                return false;
            }

            $this->conn->commit();
            error_log("DEBUG AdmsUser::softDeleteUser: Usuário ID: {$userId} e todos os anúncios desativados com sucesso.");
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Conta desativada com sucesso!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERRO PDO AdmsUser::softDeleteUser: Exceção ao desativar usuário: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao desativar usuário.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO AdmsUser::softDeleteUser: Exceção geral ao desativar usuário: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro inesperado ao desativar usuário.'];
            return false;
        }
    }

    /**
     * Realiza a exclusão definitiva de um usuário.
     * REGRA: Nunca existe exclusão isolada de anúncio.
     * Ao excluir a conta, todos os anúncios vinculados são excluídos em cascata.
     */
    public function deleteUser(int $userId): bool
    {
        $this->result = false;
        try {
            $this->conn->beginTransaction();

            // Exclusão definitiva do usuário (CASCADE irá excluir anúncios automaticamente)
            $query = "DELETE FROM usuarios WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
            $result = $stmt->execute();

            if (!$result) {
                $this->conn->rollBack();
                error_log("ERRO PDO AdmsUser::deleteUser: Falha ao excluir usuário ID: {$userId}. Erro: " . json_encode($stmt->errorInfo()));
                $this->msg = ['type' => 'error', 'text' => 'Erro ao excluir usuário.'];
                return false;
            }

            $this->conn->commit();
            error_log("DEBUG AdmsUser::deleteUser: Usuário ID: {$userId} e todos os anúncios vinculados excluídos com sucesso.");
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Usuário excluído com sucesso!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("ERRO PDO AdmsUser::deleteUser: Exceção ao excluir usuário: " . $e->getMessage());
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao excluir usuário.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL AdmsUser::deleteUser: Exceção ao excluir usuário: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao excluir usuário.'];
            return false;
        }
    }

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

    public function updatePassword(array $userData, int $userId): bool
    {
        $this->result = false;
        try {
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

    public function getUserByEmail(string $email): ?array
    {
        $sql = "SELECT id, nome, email, senha, nivel_acesso, foto, plan_type, has_anuncio, anuncio_status, ultimo_acesso FROM usuarios WHERE email = :email";
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
     * Busca dados do usuário com informações de estado e cidade do anúncio
     */
    public function getUserWithLocationData(int $userId, int $anuncioId): ?array
    {
        $sql = "SELECT 
                    u.id, u.nome, u.email, u.nivel_acesso, u.foto, u.ultimo_acesso, 
                    u.registration_ip, u.plan_type, u.has_anuncio, u.anuncio_status,
                    a.state_id, a.city_id
                FROM usuarios u
                LEFT JOIN anuncios a ON u.id = a.user_id
                WHERE u.id = :user_id AND a.id = :anuncio_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user) {
                // Buscar nome do estado e cidade dos arquivos JSON
                $user['estado_nome'] = $this->getStateNameFromUf($user['state_id'] ?? '');
                $user['cidade_nome'] = $this->getCityNameFromCode($user['city_id'] ?? '');
            }
            
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUserWithLocationData: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca o nome do estado a partir da UF usando o arquivo JSON
     */
    private function getStateNameFromUf(string $uf): string
    {
        if (empty($uf)) {
            return 'N/A';
        }
        
        $statesFile = $this->projectRoot . 'app/adms/assets/js/data/states.json';
        if (file_exists($statesFile)) {
            $statesContent = file_get_contents($statesFile);
            $statesData = json_decode($statesContent, true);
            if (isset($statesData['data']) && is_array($statesData['data'])) {
                foreach ($statesData['data'] as $state) {
                    if (isset($state['Uf']) && $state['Uf'] === $uf && isset($state['Nome'])) {
                        return $state['Nome'];
                    }
                }
            }
        }
        return 'N/A';
    }

    /**
     * Busca o nome da cidade a partir do código usando o arquivo JSON
     */
    private function getCityNameFromCode($cityCode): string
    {
        if (empty($cityCode)) {
            return 'N/A';
        }
        
        $citiesFile = $this->projectRoot . 'app/adms/assets/js/data/cities.json';
        if (file_exists($citiesFile)) {
            $citiesContent = file_get_contents($citiesFile);
            $citiesData = json_decode($citiesContent, true);
            if (isset($citiesData['data']) && is_array($citiesData['data'])) {
                foreach ($citiesData['data'] as $city) {
                    if (isset($city['Codigo']) && $city['Codigo'] == $cityCode && isset($city['Nome'])) {
                        return $city['Nome'];
                    }
                }
            }
        }
        return 'N/A';
    }

    public function getUsers(int $page, int $limit, string $searchTerm = ''): array
    {
        $offset = ($page - 1) * $limit;
        $query = "SELECT id, nome, email, nivel_acesso, status, ultimo_acesso, registration_ip FROM usuarios WHERE 1=1";
        $binds = [];

        if (!empty($searchTerm)) {
            $query .= " AND (nome LIKE :search_term OR email LIKE :search_term)";
            $binds[':search_term'] = '%' . $searchTerm . '%';
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

    public function getTotalUsers(string $searchTerm = ''): int
    {
        $query = "SELECT COUNT(id) AS total FROM usuarios WHERE 1=1";
        $binds = [];

        if (!empty($searchTerm)) {
            $query .= " AND (nome LIKE :search_term OR email LIKE :search_term)";
            $binds[':search_term'] = '%' . $searchTerm . '%';
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

    /**
     * Busca todos os usuários com filtros para administradores
     */
    public function getAllUsersWithFilters(int $page, int $limit, string $searchTerm = '', string $filterPlan = 'all', string $filterStatus = 'all'): array
    {
        try {
            $offset = ($page - 1) * $limit;
            // Detectar colunas disponíveis
            $cols = $this->getTableColumns('usuarios');
            error_log("DEBUG AdmsUser::getAllUsersWithFilters - Colunas detectadas: " . implode(',', $cols));
            $has = function(string $name) use ($cols): bool { return in_array($name, $cols, true); };

            // Mapear colunas com aliases esperados pela UI
            $selectParts = [
                'u.id'
            ];
            // nome (considerar colunas alternativas)
            if ($has('nome')) { $selectParts[] = 'u.nome'; }
            elseif ($has('name')) { $selectParts[] = 'u.name AS nome'; }
            elseif ($has('usuario')) { $selectParts[] = 'u.usuario AS nome'; }
            elseif ($has('username')) { $selectParts[] = 'u.username AS nome'; }
            elseif ($has('user_name')) { $selectParts[] = 'u.user_name AS nome'; }
            else { /* ausência de nome */ }
            // email (considerar alternativa 'mail')
            if ($has('email')) { $selectParts[] = 'u.email'; }
            elseif ($has('mail')) { $selectParts[] = 'u.mail AS email'; }
            // plan_type
            if ($has('plan_type')) { $selectParts[] = 'u.plan_type'; }
            // status
            if ($has('status')) { $selectParts[] = 'u.status'; }
            // payment_status
            if ($has('payment_status')) { $selectParts[] = 'u.payment_status'; }
            // created / created_at
            if ($has('created_at') && $has('created')) {
                $selectParts[] = 'COALESCE(u.created_at, u.created) AS created';
            } elseif ($has('created_at')) {
                $selectParts[] = 'u.created_at AS created';
            } elseif ($has('created')) {
                $selectParts[] = 'u.created AS created';
            }
            // ultimo_acesso
            if ($has('ultimo_acesso')) { $selectParts[] = 'u.ultimo_acesso'; }
            // total_anuncios por subconsulta
            $selectParts[] = '(SELECT COUNT(*) FROM anuncios a WHERE a.user_id = u.id) AS total_anuncios';

            $query = 'SELECT ' . implode(', ', $selectParts) . ' FROM usuarios u WHERE 1=1';

            $params = [];

            // WHERE cláusulas com nomes adaptáveis
            if (!empty($searchTerm)) {
                $likeLower = '%' . strtolower($searchTerm) . '%';
                $likeRaw = '%' . $searchTerm . '%';
                $searchExact = $searchTerm;
                $hasAt = strpos($searchTerm, '@') !== false;
                // Nome candidates
                $nameCol = null; $nameExpr = null;
                foreach (['nome','name','usuario','username','user_name'] as $cand) {
                    if ($has($cand)) { $nameCol = 'u.' . $cand; $nameExpr = 'LOWER(u.' . $cand . ')'; break; }
                }
                // Email candidates
                $emailCol = null; $emailExpr = null;
                foreach (['email','mail'] as $cand) {
                    if ($has($cand)) { $emailCol = 'u.' . $cand; $emailExpr = 'LOWER(u.' . $cand . ')'; break; }
                }

                $parts = [];
                // Use unique placeholders to avoid HY093
                if ($nameExpr) { $parts[] = $nameExpr . ' LIKE :search_lower1'; $params[':search_lower1'] = $likeLower; }
                if ($emailExpr) { $parts[] = $emailExpr . ' LIKE :search_lower2'; $params[':search_lower2'] = $likeLower; }
                // Raw LIKEs para lidar com collations/acento
                if ($nameCol) { $parts[] = $nameCol . ' LIKE :search_raw1'; $params[':search_raw1'] = $likeRaw; }
                if ($emailCol) { $parts[] = $emailCol . ' LIKE :search_raw2'; $params[':search_raw2'] = $likeRaw; }
                // Igualdade exata para email quando há '@'
                if ($hasAt && $emailCol) { $parts[] = $emailCol . ' = :search_exact'; $params[':search_exact'] = $searchExact; }

                if (!empty($parts)) {
                    $whereFrag = ' AND (' . implode(' OR ', $parts) . ')';
                    $query .= $whereFrag;
                    error_log("DEBUG LIST SEARCH - nameCol=$nameCol, emailCol=$emailCol, hasAt=" . ($hasAt?'1':'0'));
                    error_log("DEBUG LIST SEARCH - likeLower=$likeLower, likeRaw=$likeRaw, exact=$searchExact");
                    error_log("DEBUG LIST SEARCH - whereFrag=" . $whereFrag);
                }
            }

            if ($filterStatus !== 'all' && $has('status')) {
                $query .= ' AND u.status = :status';
                $params[':status'] = $filterStatus;
            }

            if ($filterPlan !== 'all' && $has('plan_type')) {
                $query .= ' AND u.plan_type = :plan';
                $params[':plan'] = $filterPlan;
            }

            // Ordenação estável
            $orderExpr = $has('id') ? 'u.id' : ($has('created_at') ? 'u.created_at' : ($has('created') ? 'u.created' : '1'));
            $query .= " ORDER BY $orderExpr DESC LIMIT :limit OFFSET :offset";

            // Persist debug state (LIST)
            $this->lastListDetectedColumns = $cols;
            $this->lastListQuery = $query;
            $this->lastListParams = $params;
            // DEBUG
            error_log("DEBUG AdmsUser::getAllUsersWithFilters - Query FINAL: " . $query);
            error_log("DEBUG AdmsUser::getAllUsersWithFilters - Params: " . print_r($params, true) . ", limit=" . $limit . ", offset=" . $offset);

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, \PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Enriquecer com defaults para chaves esperadas
            foreach ($rows as &$row) {
                if (!isset($row['nome'])) { $row['nome'] = $row['name'] ?? ('Usuário #' . ($row['id'] ?? '')); }
                if (!isset($row['plan_type'])) { $row['plan_type'] = 'free'; }
                if (!isset($row['status'])) { $row['status'] = 'ativo'; }
                if (!isset($row['payment_status'])) { $row['payment_status'] = 'pending'; }
                if (!isset($row['created'])) { $row['created'] = null; }
                if (!isset($row['ultimo_acesso'])) { $row['ultimo_acesso'] = null; }
                if (!isset($row['total_anuncios'])) { $row['total_anuncios'] = 0; }
            }

            return $rows;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getAllUsersWithFilters (primário): " . $e->getMessage());
            // Fallback: consulta com filtros básicos por nome/email e, se possível, plano/status
            try {
                // Tentar detectar colunas de novo (pode ter falhado antes por outro motivo)
                $fallbackCols = $this->getTableColumns('usuarios');
                if (empty($fallbackCols)) {
                    $fallbackCols = ['id','nome','email','status','plan_type'];
                }
                $hasCol = function(string $c) use ($fallbackCols): bool { return in_array($c, $fallbackCols, true); };

                $fallbackSql = "SELECT u.id";
                if ($hasCol('nome')) { $fallbackSql .= ", u.nome"; }
                if ($hasCol('email')) { $fallbackSql .= ", u.email"; }
                $fallbackSql .= " FROM usuarios u WHERE 1=1";

                $fallbackParams = [];
                if (!empty($searchTerm) && ($hasCol('nome') || $hasCol('email'))) {
                    $parts = [];
                    $likeLower = '%' . strtolower($searchTerm) . '%';
                    $likeRaw = '%' . $searchTerm . '%';
                    $hasAt = strpos($searchTerm, '@') !== false;

                    // Use unique placeholders to avoid HY093
                    if ($hasCol('nome')) {
                        $parts[] = 'LOWER(u.nome) LIKE :s_lower1';
                        $fallbackParams[':s_lower1'] = $likeLower;
                        $parts[] = 'u.nome LIKE :s_raw1';
                        $fallbackParams[':s_raw1'] = $likeRaw;
                    }
                    if ($hasCol('email')) {
                        $parts[] = 'LOWER(u.email) LIKE :s_lower2';
                        $fallbackParams[':s_lower2'] = $likeLower;
                        $parts[] = 'u.email LIKE :s_raw2';
                        $fallbackParams[':s_raw2'] = $likeRaw;
                        if ($hasAt) { $parts[] = 'u.email = :s_exact'; $fallbackParams[':s_exact'] = $searchTerm; }
                    }
                    $fallbackSql .= ' AND (' . implode(' OR ', $parts) . ')';
                    error_log('DEBUG LIST FALLBACK - whereParts=' . implode(' OR ', $parts));
                    error_log('DEBUG LIST FALLBACK - lower=' . $likeLower . ', raw=' . $likeRaw . ($hasAt ? (', exact=' . $searchTerm) : ''));
                }
                if ($filterStatus !== 'all' && $hasCol('status')) {
                    $fallbackSql .= " AND u.status = :fstatus";
                    $fallbackParams[':fstatus'] = $filterStatus;
                }
                if ($filterPlan !== 'all' && $hasCol('plan_type')) {
                    $fallbackSql .= " AND u.plan_type = :fplan";
                    $fallbackParams[':fplan'] = $filterPlan;
                }
                $fallbackSql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";

                $this->lastListDetectedColumns = $fallbackCols ?? [];
                $this->lastListQuery = $fallbackSql;
                $this->lastListParams = $fallbackParams ?? [];
                error_log("DEBUG AdmsUser::getAllUsersWithFilters - Fallback SQL: " . $fallbackSql . " | params=" . print_r($fallbackParams, true));

                $stmt = $this->conn->prepare($fallbackSql);
                foreach ($fallbackParams as $k => $v) {
                    $stmt->bindValue($k, $v, \PDO::PARAM_STR);
                }
                $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                // Enriquecer com valores padrão esperados pela UI
                foreach ($rows as &$row) {
                    if (!isset($row['plan_type'])) { $row['plan_type'] = 'free'; }
                    if (!isset($row['status'])) { $row['status'] = 'ativo'; }
                    if (!isset($row['payment_status'])) { $row['payment_status'] = 'pending'; }
                    if (!isset($row['created'])) { $row['created'] = null; }
                    if (!isset($row['ultimo_acesso'])) { $row['ultimo_acesso'] = null; }
                    if (!isset($row['total_anuncios'])) { $row['total_anuncios'] = 0; }
                }
                return $rows;
            } catch (PDOException $e2) {
                error_log("ERRO PDO AdmsUser::getAllUsersWithFilters (fallback): " . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * Conta total de usuários com filtros
     */
    public function getTotalUsersWithFilters(string $searchTerm = '', string $filterPlan = 'all', string $filterStatus = 'all'): int
    {
        try {
            $cols = $this->getTableColumns('usuarios');
            error_log("DEBUG AdmsUser::getTotalUsersWithFilters - Colunas detectadas: " . implode(',', $cols));
            $has = function(string $name) use ($cols): bool { return in_array($name, $cols, true); };

            $query = "SELECT COUNT(DISTINCT u.id) as total FROM usuarios u WHERE 1=1";
            $params = [];

            if (!empty($searchTerm)) {
                $likeLower = '%' . strtolower($searchTerm) . '%';
                $likeRaw = '%' . $searchTerm . '%';
                $nameCol = null; $nameExpr = null;
                foreach (['nome','name','usuario','username','user_name'] as $cand) {
                    if ($has($cand)) { $nameCol = 'u.' . $cand; $nameExpr = 'LOWER(u.' . $cand . ')'; break; }
                }
                $emailCol = null; $emailExpr = null;
                foreach (['email','mail'] as $cand) {
                    if ($has($cand)) { $emailCol = 'u.' . $cand; $emailExpr = 'LOWER(u.' . $cand . ')'; break; }
                }
                $parts = [];
                if ($nameExpr) { $parts[] = "$nameExpr LIKE :search_lower"; }
                if ($emailExpr) { $parts[] = "$emailExpr LIKE :search_lower"; }
                if ($nameCol) { $parts[] = "$nameCol LIKE :search_raw"; }
                if ($emailCol) { $parts[] = "$emailCol LIKE :search_raw"; }
                if (!empty($parts)) {
                    $query .= ' AND (' . implode(' OR ', $parts) . ')';
                    $params[':search_lower'] = $likeLower;
                    $params[':search_raw'] = $likeRaw;
                }
            }

            if ($filterStatus !== 'all' && $has('status')) {
                $query .= ' AND u.status = :status';
                $params[':status'] = $filterStatus;
            }

            if ($filterPlan !== 'all' && $has('plan_type')) {
                $query .= ' AND u.plan_type = :plan';
                $params[':plan'] = $filterPlan;
            }

            // Persist debug state (COUNT)
            $this->lastCountDetectedColumns = $cols;
            $this->lastCountQuery = $query;
            $this->lastCountParams = $params;
            // DEBUG
            error_log("DEBUG AdmsUser::getTotalUsersWithFilters - Query FINAL: " . $query);
            error_log("DEBUG AdmsUser::getTotalUsersWithFilters - Params: " . print_r($params, true));

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, \PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getTotalUsersWithFilters (primário): " . $e->getMessage());
            // Fallback: contar total aplicando filtros básicos quando possível
            try {
                $fallbackCols = $this->getTableColumns('usuarios');
                $hasCol = function(string $c) use ($fallbackCols): bool { return in_array($c, $fallbackCols, true); };
                $fallbackSql = "SELECT COUNT(*) AS total FROM usuarios u WHERE 1=1";
                $fallbackParams = [];

                if (!empty($searchTerm)) {
                    $parts = [];
                    if ($hasCol('nome')) { $parts[] = 'u.nome LIKE :s_raw'; }
                    if ($hasCol('email')) { $parts[] = 'u.email LIKE :s_raw'; }
                    if (empty($parts)) {
                        // tentar alternativas
                        if ($hasCol('name')) { $parts[] = 'u.name LIKE :s_raw'; }
                        if ($hasCol('mail')) { $parts[] = 'u.mail LIKE :s_raw'; }
                    }
                    if (!empty($parts)) {
                        $fallbackSql .= ' AND (' . implode(' OR ', $parts) . ')';
                        $fallbackParams[':s_raw'] = '%' . $searchTerm . '%';
                    }
                }
                if ($filterStatus !== 'all' && $hasCol('status')) {
                    $fallbackSql .= ' AND u.status = :fstatus';
                    $fallbackParams[':fstatus'] = $filterStatus;
                }
                if ($filterPlan !== 'all' && $hasCol('plan_type')) {
                    $fallbackSql .= ' AND u.plan_type = :fplan';
                    $fallbackParams[':fplan'] = $filterPlan;
                }

                $this->lastCountDetectedColumns = $fallbackCols;
                $this->lastCountQuery = $fallbackSql;
                $this->lastCountParams = $fallbackParams;

                $stmt = $this->conn->prepare($fallbackSql);
                foreach ($fallbackParams as $k => $v) { $stmt->bindValue($k, $v, \PDO::PARAM_STR); }
                $stmt->execute();
                $result = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0];
                return (int)($result['total'] ?? 0);
            } catch (PDOException $e2) {
                error_log("ERRO PDO AdmsUser::getTotalUsersWithFilters (fallback): " . $e2->getMessage());
                return 0;
            }
        }
    }

    // Debug getters (temporários)
    public function getLastListQuery(): string { return $this->lastListQuery; }
    public function getLastListParams(): array { return $this->lastListParams; }
    public function getLastListDetectedColumns(): array { return $this->lastListDetectedColumns; }
    public function getLastCountQuery(): string { return $this->lastCountQuery; }
    public function getLastCountParams(): array { return $this->lastCountParams; }
    public function getLastCountDetectedColumns(): array { return $this->lastCountDetectedColumns; }

    /**
     * Retorna as colunas existentes para uma tabela no banco atual
     */
    private function getTableColumns(string $table): array
    {
        // Tenta SHOW COLUMNS (mais permissivo em alguns ambientes)
        try {
            $stmt = $this->conn->prepare('SHOW COLUMNS FROM ' . $table);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if ($rows) {
                return array_map(fn($r) => (string)$r['Field'], $rows);
            }
        } catch (\Exception $e) {
            error_log('DEBUG getTableColumns via SHOW COLUMNS falhou: ' . $e->getMessage());
        }
        // Fallback: INFORMATION_SCHEMA
        try {
            $dbName = $this->getCurrentDatabaseName();
            if (!$dbName) { return []; }
            $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':db', $dbName, \PDO::PARAM_STR);
            $stmt->bindValue(':tbl', $table, \PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return array_map('strval', $rows ?: []);
        } catch (\Exception $e) {
            error_log("DEBUG getTableColumns via INFORMATION_SCHEMA falhou: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém o nome do banco de dados atual
     */
    private function getCurrentDatabaseName(): ?string
    {
        try {
            $stmt = $this->conn->query('SELECT DATABASE()');
            $db = $stmt->fetchColumn();
            return $db ?: null;
        } catch (\Exception $e) {
            error_log("DEBUG getCurrentDatabaseName erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca dados completos de um usuário específico
     */
    public function getUserCompleteData(int $userId): ?array
    {
        try {
            $query = "SELECT u.*, 
                             COUNT(a.id) as total_anuncios,
                             COUNT(CASE WHEN a.status = 'active' THEN 1 END) as anuncios_ativos,
                             COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as anuncios_pendentes,
                             COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) as anuncios_rejeitados
                      FROM usuarios u 
                      LEFT JOIN anuncios a ON u.id = a.user_id 
                      WHERE u.id = :user_id 
                      GROUP BY u.id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUserCompleteData: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza status de pagamento do usuário
     */
    public function updatePaymentStatus(int $userId, string $status): bool
    {
        try {
            $query = "UPDATE usuarios SET payment_status = :status, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status, \PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::updatePaymentStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca estatísticas gerais dos usuários
     */
    public function getUsersStats(): array
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_usuarios,
                        COUNT(CASE WHEN status = 'ativo' THEN 1 END) as usuarios_ativos,
                        COUNT(CASE WHEN status = 'inativo' THEN 1 END) as usuarios_inativos,
                        COUNT(CASE WHEN plan_type = 'free' THEN 1 END) as usuarios_free,
                        COUNT(CASE WHEN plan_type = 'basic' THEN 1 END) as usuarios_basic,
                        COUNT(CASE WHEN plan_type = 'premium' THEN 1 END) as usuarios_premium,
                        COUNT(CASE WHEN payment_status = 'approved' THEN 1 END) as pagamentos_aprovados,
                        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pagamentos_pendentes
                      FROM usuarios 
                      WHERE nivel_acesso = 'usuario'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsUser::getUsersStats: " . $e->getMessage());
            return [];
        }
    }
}