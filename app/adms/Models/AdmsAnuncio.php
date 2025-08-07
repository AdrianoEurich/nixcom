<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDOException;
use Adms\CoreAdm\Helpers\Upload;
use Exception;

class AdmsAnuncio extends StsConn
{
    private object $conn;
    private array $data; // Dados do formulário (POST)
    private array $files; // Dados dos arquivos uploaded (FILES)
    private int $userId; // ID do usuário logado (para criação/edição própria)
    private string $userPlanType; // Tipo de plano do usuário (free/premium)
    private bool $result; // Resultado da operação (sucesso/falha)
    private array $msg; // Mensagens de erro ou sucesso
    private string $uploadDir = 'app/public/uploads/anuncios/'; // Diretório para uploads de anúncios (relativo à raiz do projeto)
    private string $projectRoot; // Caminho absoluto para a raiz do projeto
    private ?array $existingAnuncio = null; // Para armazenar dados do anúncio existente em modo de edição

    // Novas propriedades para armazenar os dados de lookup de localização
    private array $statesLookup = [];
    private array $citiesLookup = [];

    public function __construct()
    {
        $this->conn = $this->connectDb();

        // Tenta inferir PATH_ROOT de forma mais robusta se não estiver definido globalmente
        if (!defined('PATH_ROOT')) {
            // Analisa a URL base do projeto para encontrar o subdiretório (ex: 'nixcom')
            $parsed_url = parse_url(URL);
            $path_segment = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
            
            if (!empty($path_segment)) {
                define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $path_segment . DIRECTORY_SEPARATOR);
            } else {
                define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
            }
            error_log("DEBUG ANUNCIO: PATH_ROOT inferido como: " . PATH_ROOT);
        }
        $this->projectRoot = PATH_ROOT; // Usa a constante global ou a inferida

        // Garante que o diretório de upload existe
        if (!is_dir($this->projectRoot . $this->uploadDir)) {
            mkdir($this->projectRoot . $this->uploadDir, 0755, true);
        }
        // Garante que os subdiretórios para uploads existam
        if (!is_dir($this->projectRoot . $this->uploadDir . 'capas/')) {
            mkdir($this->projectRoot . $this->uploadDir . 'capas/', 0755, true);
        }
        if (!is_dir($this->projectRoot . $this->uploadDir . 'galeria/')) {
            mkdir($this->projectRoot . $this->uploadDir . 'galeria/', 0755, true);
        }
        if (!is_dir($this->projectRoot . $this->uploadDir . 'videos/')) {
            mkdir($this->projectRoot . $this->uploadDir . 'videos/', 0755, true);
        }
        if (!is_dir($this->projectRoot . $this->uploadDir . 'audios/')) {
            mkdir($this->projectRoot . $this->uploadDir . 'audios/', 0755, true);
        }
        // NOVO: Diretório para vídeos de confirmação
        if (!is_dir($this->projectRoot . $this->uploadDir . 'confirmation_videos/')) {
            mkdir($this->projectRoot . $this->uploadDir . 'confirmation_videos/', 0755, true);
        }

        // Carrega os dados de localização no construtor
        $this->loadLocationLookups();
    }

    /**
     * Carrega os dados de estados e cidades de arquivos JSON para lookup.
     */
    private function loadLocationLookups(): void
    {
        $statesJsonPath = $this->projectRoot . 'app/adms/assets/js/data/states.json';
        $citiesJsonPath = $this->projectRoot . 'app/adms/assets/js/data/cities.json';

        if (file_exists($statesJsonPath)) {
            $statesRaw = json_decode(file_get_contents($statesJsonPath), true);
            // Verifica se a chave 'data' existe e se é um array
            if (json_last_error() === JSON_ERROR_NONE && isset($statesRaw['data']) && is_array($statesRaw['data'])) {
                foreach ($statesRaw['data'] as $state) {
                    $this->statesLookup[$state['Uf']] = $state['Nome'];
                }
            } else {
                error_log("ERRO ANUNCIO: Erro ao decodificar states.json ou formato inválido (missing 'data' key or not array). Erro: " . json_last_error_msg());
            }
        } else {
            error_log("ERRO ANUNCIO: states.json não encontrado em " . $statesJsonPath);
        }

        if (file_exists($citiesJsonPath)) {
            $citiesRaw = json_decode(file_get_contents($citiesJsonPath), true); 
            // Verifica se a chave 'data' existe e se é um array
            if (json_last_error() === JSON_ERROR_NONE && isset($citiesRaw['data']) && is_array($citiesRaw['data'])) {
                foreach ($citiesRaw['data'] as $city) {
                    $this->citiesLookup[$city['Codigo']] = $city['Nome'];
                }
            } else {
                error_log("ERRO ANUNCIO: Erro ao decodificar cities.json ou formato inválido (missing 'data' key or not array). Erro: " . json_last_error_msg());
            }
        } else {
            error_log("ERRO ANUNCIO: cities.json não encontrado em " . $citiesJsonPath);
        }
        error_log("DEBUG ANUNCIO: loadLocationLookups - Estados carregados: " . count($this->statesLookup) . ", Cidades carregadas: " . count($this->citiesLookup));
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
     * Cria um novo anúncio no banco de dados.
     *
     * @param array $data Dados do formulário (POST)
     * @param array $files Dados dos arquivos uploaded (FILES)
     * @param int $userId ID do usuário logado
     * @return bool True se o anúncio for criado com sucesso, false caso contrário.
     */
    public function createAnuncio(array $data, array $files, int $userId): bool
    {
        $this->data = $data;
        $this->files = $files;
        $this->userId = $userId;
        $this->existingAnuncio = null; // Garante que é modo de criação

        error_log("DEBUG ANUNCIO: createAnuncio - User ID recebido: " . $this->userId);

        // 1. Obter o tipo de plano do usuário
        if (!$this->getUserPlanType($this->userId)) { // Passa userId para o método
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Não foi possível determinar o plano do usuário.'];
            return false;
        }

        error_log("DEBUG ANUNCIO: createAnuncio - Tipo de Plano do Usuário: " . $this->userPlanType);

        // 2. **NOVA VERIFICAÇÃO**: Checar se o usuário já possui um anúncio ATIVO (não deletado)
        // A UNIQUE KEY em user_id na tabela `anuncios` já impede múltiplos anúncios por user_id.
        // Se um anúncio foi soft-deletado, o user_id ainda está lá.
        // A nova lógica é que um usuário com conta deletada PRECISA criar uma NOVA CONTA
        // para criar um NOVO anúncio. Portanto, esta checagem é para garantir que um usuário ATIVO
        // não crie mais de um anúncio.
        // Com a UNIQUE KEY em `user_id` removida do DB, esta validação PHP é crucial.
        if ($this->checkExistingAnuncio()) { // Este método já verifica `deleted_at IS NULL`
            error_log("DEBUG ANUNCIO: createAnuncio - checkExistingAnuncio retornou TRUE para o usuário " . $this->userId . ". Impedindo a criação de novo anúncio.");
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Você já possui um anúncio cadastrado. Um usuário pode ter apenas um anúncio ativo. Se você excluiu sua conta, você precisa criar uma nova conta para anunciar novamente.'];
            $this->msg['errors']['form'] = 'Você já possui um anúncio cadastrado.'; // Erro geral para o formulário
            return false;
        }
        error_log("DEBUG ANUNCIO: createAnuncio - checkExistingAnuncio retornou FALSE para o usuário " . $this->userId . ". Prosseguindo com a criação do anúncio.");


        // 3. Validação inicial dos dados e do plano
        if (!$this->validateInput()) {
            $this->result = false;
            return false;
        }

        // Inicia a transação para garantir a integridade dos dados
        $this->conn->beginTransaction();

        try {
            // 4. Processar Upload da Foto de Capa
            if (!isset($this->files['foto_capa']) || $this->files['foto_capa']['error'] !== UPLOAD_ERR_OK || empty($this->files['foto_capa']['name'])) {
                $this->msg = ['type' => 'error', 'text' => 'A foto de capa é obrigatória.'];
                $this->msg['errors']['coverPhoto'] = 'Foto de capa é obrigatória.'; // Campo de feedback corrigido
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }

            $upload = new Upload();
            $uploadedCapaPath = $upload->uploadFile($this->files['foto_capa'], $this->projectRoot . $this->uploadDir . 'capas/');
            if (!$uploadedCapaPath) {
                $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload da foto de capa: ' . $upload->getMsg()['text']];
                $this->msg['errors']['coverPhoto'] = 'Erro no upload da foto de capa.'; // Campo de feedback corrigido
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }
            // Salva o caminho relativo à raiz do projeto no DB
            $this->data['cover_photo_path'] = $this->uploadDir . 'capas/' . basename($uploadedCapaPath);

            // NOVO: 4.1. Processar Upload do Vídeo de Confirmação
            $confirmationVideoPath = $this->handleConfirmationVideoUpload(null); // null pois não há vídeo existente na criação
            if ($confirmationVideoPath === false) { // handleConfirmationVideoUpload retorna false em caso de erro
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }
            $this->data['confirmation_video_path'] = $confirmationVideoPath;


            // 5. Inserir na tabela principal `anuncios`
            $queryAnuncio = "INSERT INTO anuncios (
                                user_id, service_name, state_uf, city_code, neighborhood_name, age, height_m, weight_kg, gender,
                                nationality, ethnicity, eye_color, phone_number, description, price_15min, price_30min, price_1h,
                                cover_photo_path, confirmation_video_path, plan_type, status, created_at
                            ) VALUES (
                                :user_id, :service_name, :state_uf, :city_code, :neighborhood_name, :age, :height_m, :weight_kg, :gender,
                                :nationality, :ethnicity, :eye_color, :phone_number, :description, :price_15min, :price_30min, :price_1h,
                                :cover_photo_path, :confirmation_video_path, :plan_type, :status, NOW()
                            )";

            $stmtAnuncio = $this->conn->prepare($queryAnuncio);

            // Certifica-se que altura e peso são floats/ints antes de bindar
            $height_m = (float) $this->data['height_m'];
            $weight_kg = (int) $this->data['weight_kg'];

            $stmtAnuncio->bindParam(':user_id', $this->userId, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':service_name', $this->data['service_name'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':state_uf', $this->data['state_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':city_code', $this->data['city_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':neighborhood_name', $this->data['neighborhood_name'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':age', $this->data['age'], \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':height_m', $height_m, \PDO::PARAM_STR); // Armazenar como string para garantir formato decimal no DB
            $stmtAnuncio->bindParam(':weight_kg', $weight_kg, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':gender', $this->data['gender'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':nationality', $this->data['nationality'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':ethnicity', $this->data['ethnicity'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':eye_color', $this->data['eye_color'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':phone_number', $this->data['phone_number'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':description', $this->data['description'], \PDO::PARAM_STR);

            // Preços (trata valores vazios como NULL, já convertidos para float na validação)
            $price15 = $this->data['price_15min']; // Já é float ou null
            $price30 = $this->data['price_30min']; // Já é float ou null
            $price1h = $this->data['price_1h'];    // Já é float ou null

            $stmtAnuncio->bindParam(':price_15min', $price15, \PDO::PARAM_STR); // Armazenar como string para garantir formato decimal no DB
            $stmtAnuncio->bindParam(':price_30min', $price30, \PDO::PARAM_STR); // Armazenar como string para garantir formato decimal no DB
            $stmtAnuncio->bindParam(':price_1h', $price1h, \PDO::PARAM_STR);    // Armazenar como string para garantir formato decimal no DB

            $stmtAnuncio->bindParam(':cover_photo_path', $this->data['cover_photo_path'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':confirmation_video_path', $this->data['confirmation_video_path'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':plan_type', $this->userPlanType, \PDO::PARAM_STR);
            $status = 'pending';
            $stmtAnuncio->bindParam(':status', $status, \PDO::PARAM_STR);

            $stmtAnuncio->execute();
            $anuncioId = $this->conn->lastInsertId();
            error_log("DEBUG ANUNCIO: createAnuncio - Anúncio inserido com ID: " . $anuncioId . " para o usuário ID: " . $this->userId);


            // 6. Inserir em tabelas de relacionamento (checkboxes)
            $this->insertRelatedData($anuncioId, 'anuncio_aparencias', $this->data['aparencia'] ?? [], 'aparencia_item');
            $this->insertRelatedData($anuncioId, 'anuncio_idiomas', $this->data['idiomas'] ?? [], 'idioma_name');
            $this->insertRelatedData($anuncioId, 'anuncio_locais_atendimento', $this->data['locais_atendimento'] ?? [], 'local_name');
            $this->insertRelatedData($anuncioId, 'anuncio_formas_pagamento', $this->data['formas_pagamento'] ?? [], 'forma_name');
            $this->insertRelatedData($anuncioId, 'anuncio_servicos_oferecidos', $this->data['servicos'] ?? [], 'servico_name');

            // 7. Processar e Inserir Mídias da Galeria (Fotos, Vídeos, Áudios)
            // Na criação, handleGalleryUploads apenas processa novos uploads.
            // A validação de "pelo menos 1 foto" já foi feita em validateInput.
            if (!$this->handleGalleryUploads($anuncioId)) {
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }

            // Atualiza o status do anúncio do usuário na tabela `usuarios`
            $this->updateUserAnuncioStatus($this->userId, 'pending', true);

            $this->conn->commit();
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio criado com sucesso e enviado para aprovação!', 'anuncio_id' => $anuncioId];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errorInfo = ($stmtAnuncio instanceof \PDOStatement) ? $stmtAnuncio->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: Falha na transação de criação. Rollback. Mensagem: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2] . " - Query: " . ($stmtAnuncio->queryString ?? 'N/A') . " - Dados: " . print_r($this->data, true) . " - User ID sendo inserido: " . $this->userId);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro ao salvar anúncio no banco de dados. Por favor, tente novamente.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL ANUNCIO: Falha na transação de criação. Rollback. Mensagem: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao criar o anúncio.'];
            return false;
        }
    }

    /**
     * Atualiza um anúncio existente no banco de dados.
     *
     * @param array $data Dados do formulário (POST)
     * @param array $files Dados dos arquivos uploaded (FILES)
     * @param int $anuncioId O ID do anúncio a ser atualizado.
     * @param int $userId ID do usuário logado (para validação de propriedade).
     * @return bool True se o anúncio for atualizado com sucesso, false caso contrário.
     */
    public function updateAnuncio(array $data, array $files, int $anuncioId, int $userId): bool
    {
        $this->data = $data;
        $this->files = $files;
        $this->userId = $userId;

        error_log("DEBUG ANUNCIO: updateAnuncio iniciado para Anúncio ID: " . $anuncioId . ", User ID: " . $this->userId);
        error_log('DEBUG PHP: Conteúdo de $this->files: ' . print_r($this->files, true));
        error_log('DEBUG PHP: Conteúdo de $this->data: ' . print_r($this->data, true));


        // 1. Obter o tipo de plano do usuário
        if (!$this->getUserPlanType($this->userId)) {
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Não foi possível determinar o plano do usuário.'];
            return false;
        }

        // 2. Obter dados do anúncio existente para gerenciar mídias antigas e validação
        // Passa true para incluir anúncios deletados, pois um admin pode estar editando um anúncio deletado
        $existingAnuncio = $this->getAnuncioById($anuncioId, true); 
        
        // Obter o nível de acesso do usuário logado da sessão
        $loggedInUserLevel = $_SESSION['user_level_numeric'] ?? 0; 

        if (!$existingAnuncio) {
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Anúncio não encontrado.'];
            return false;
        }

        // --- CORREÇÃO DA LÓGICA DE PERMISSÃO ---
        // Permite a atualização se:
        // a) O usuário logado é o proprietário do anúncio ($existingAnuncio['user_id'] === $this->userId)
        // OU
        // b) O usuário logado é um administrador ($loggedInUserLevel >= 3)
        if ($existingAnuncio['user_id'] !== $this->userId && $loggedInUserLevel < 3) {
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Você não tem permissão para editar este anúncio.'];
            return false;
        }
        // --- FIM DA CORREÇÃO ---

        $this->existingAnuncio = $existingAnuncio;

        // 3. Validar os dados de entrada (agora com o contexto do anúncio existente)
        if (!$this->validateInput()) {
            $this->result = false;
            return false;
        }

        // Inicia a transação para garantir a integridade dos dados
        $this->conn->beginTransaction();

        try {
            // 4. Processar Upload da Nova Foto de Capa (se houver)
            $newCapaPath = null;
            $upload = new Upload();

            // Verifica se uma nova foto de capa foi enviada
            if (isset($this->files['foto_capa']) && $this->files['foto_capa']['error'] === UPLOAD_ERR_OK && !empty($this->files['foto_capa']['name'])) {
                $uploadedPath = $upload->uploadFile($this->files['foto_capa'], $this->projectRoot . $this->uploadDir . 'capas/');
                if (!$uploadedPath) {
                    $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload da nova foto de capa: ' . $upload->getMsg()['text']];
                    $this->msg['errors']['coverPhoto'] = 'Erro no upload da foto de capa.'; // Campo de feedback corrigido
                    $this->conn->rollBack();
                    $this->result = false;
                    return false;
                }
                // NÃO DELETA A CAPA ANTIGA AQUI, MANTEMOS TUDO
                $newCapaPath = $this->uploadDir . 'capas/' . basename($uploadedPath);
            } else if (isset($this->data['cover_photo_removed']) && $this->data['cover_photo_removed'] === 'true') {
                // Se a capa existente foi marcada para remoção, define como null no DB
                // NÃO DELETA O ARQUIVO FÍSICO
                $newCapaPath = null;
            } else {
                // Mantém a capa existente se nenhuma nova foi enviada e não foi marcada para remoção
                // O caminho já vem do banco de dados, não precisa de str_replace(URL, '', ...) aqui se já é relativo
                $newCapaPath = $existingAnuncio['cover_photo_path'];
            }
            $this->data['cover_photo_path'] = $newCapaPath;

            // NOVO: 4.1. Processar Upload/Remoção do Vídeo de Confirmação
            $confirmationVideoPath = $this->handleConfirmationVideoUpload($existingAnuncio['confirmation_video_path'] ?? null);
            if ($confirmationVideoPath === false) {
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }
            $this->data['confirmation_video_path'] = $confirmationVideoPath;


            // 5. Atualizar na tabela principal `anuncios`
            $queryAnuncio = "UPDATE anuncios SET
                service_name = :service_name, state_uf = :state_uf, city_code = :city_code, neighborhood_name = :neighborhood_name,
                age = :age, height_m = :height_m, weight_kg = :weight_kg, gender = :gender,
                nationality = :nationality, ethnicity = :ethnicity, eye_color = :eye_color, phone_number = :phone_number,
                description = :description, price_15min = :price_15min, price_30min = :price_30min, price_1h = :price_1h,
                cover_photo_path = :cover_photo_path, confirmation_video_path = :confirmation_video_path, plan_type = :plan_type, status = :status, updated_at = NOW()
            WHERE id = :anuncio_id"; // Removido AND user_id = :user_id para permitir que o admin edite

            $stmtAnuncio = $this->conn->prepare($queryAnuncio);

            // Certifica-se que altura e peso são floats/ints antes de bindar
            $height_m = (float) $this->data['height_m'];
            $weight_kg = (int) $this->data['weight_kg'];

            $stmtAnuncio->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            // $stmtAnuncio->bindParam(':user_id', $this->userId, \PDO::PARAM_INT); // Removido
            $stmtAnuncio->bindParam(':service_name', $this->data['service_name'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':state_uf', $this->data['state_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':city_code', $this->data['city_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':neighborhood_name', $this->data['neighborhood_name'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':age', $this->data['age'], \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':height_m', $height_m, \PDO::PARAM_STR); // Armazenar como string para garantir formato decimal no DB
            $stmtAnuncio->bindParam(':weight_kg', $weight_kg, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':gender', $this->data['gender'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':nationality', $this->data['nationality'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':ethnicity', $this->data['ethnicity'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':eye_color', $this->data['eye_color'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':phone_number', $this->data['phone_number'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':description', $this->data['description'], \PDO::PARAM_STR);

            // Preços (trata valores vazios como NULL, já convertidos para float na validação)
            $price15 = $this->data['price_15min']; // Já é float ou null
            $price30 = $this->data['price_30min']; // Já é float ou null
            $price1h = $this->data['price_1h'];    // Já é float ou null

            $stmtAnuncio->bindParam(':price_15min', $price15, \PDO::PARAM_STR); // Armazenar como string para garantir formato decimal no DB
            $stmtAnuncio->bindParam(':price_30min', $price30, \PDO::PARAM_STR); // Armazenar como string para garantir formato decimal no DB
            $stmtAnuncio->bindParam(':price_1h', $price1h, \PDO::PARAM_STR);    // Armazenar como string para garantir formato decimal no DB

            $stmtAnuncio->bindParam(':cover_photo_path', $this->data['cover_photo_path'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':confirmation_video_path', $this->data['confirmation_video_path'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':plan_type', $this->userPlanType, \PDO::PARAM_STR);
            $status = 'pending'; // Anúncio volta para pendente após edição
            $stmtAnuncio->bindParam(':status', $status, \PDO::PARAM_STR);

            $stmtAnuncio->execute();

            // 6. Atualizar tabelas de relacionamento (checkboxes)
            $this->updateRelatedData($anuncioId, 'anuncio_aparencias', $this->data['aparencia'] ?? [], 'aparencia_item');
            $this->updateRelatedData($anuncioId, 'anuncio_idiomas', $this->data['idiomas'] ?? [], 'idioma_name');
            $this->updateRelatedData($anuncioId, 'anuncio_locais_atendimento', $this->data['locais_atendimento'] ?? [], 'local_name');
            $this->updateRelatedData($anuncioId, 'anuncio_formas_pagamento', $this->data['formas_pagamento'] ?? [], 'forma_name');
            $this->updateRelatedData($anuncioId, 'anuncio_servicos_oferecidos', $this->data['servicos'] ?? [], 'servico_name');

            // 7. Processar e Atualizar Mídias da Galeria (Fotos, Vídeos, Áudios)
            // A função updateGalleryMedia agora recebe diretamente os caminhos existentes do POST
            if (!$this->updateGalleryMedia($anuncioId, $existingAnuncio)) {
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }
            // Atualiza o status do anúncio do usuário na tabela `usuarios`
            // Usamos o user_id do ANUNCIO, não do usuário logado, pois o admin está editando o anúncio de OUTRO usuário.
            $this->updateUserAnuncioStatus($existingAnuncio['user_id'], 'pending', true);

            $this->conn->commit();
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio atualizado com sucesso e aguardando aprovação!', 'anuncio_id' => $anuncioId];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errorInfo = ($stmtAnuncio instanceof \PDOStatement) ? $stmtAnuncio->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: Falha na transação de atualização. Rollback. Mensagem: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2] . " - Query: " . ($stmtAnuncio->queryString ?? 'N/A') . " - Dados: " . print_r($this->data, true));
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar anúncio no banco de dados. Por favor, tente novamente.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL ANUNCIO: Falha na transação de atualização. Rollback. Mensagem: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao atualizar o anúncio.'];
            return false;
        }
    }

    /**
     * Atualiza o status de um anúncio e, opcionalmente, o status do anúncio do usuário.
     * Este método é usado por administradores para aprovar, rejeitar, ativar ou desativar anúncios.
     * **ATENÇÃO**: Este método agora também pode ser usado para marcar um anúncio como 'deleted'
     * quando a conta do usuário é soft-deletada.
     * @param int $anuncioId O ID do anúncio a ser atualizado.
     * @param string $newStatus O novo status ('active', 'inactive', 'pending', 'rejected', 'deleted').
     * @param int|null $anuncianteUserId Opcional. O ID do usuário anunciante para atualizar a tabela `usuarios`.
     * @return bool True se a atualização for bem-sucedida, false caso contrário.
     */
    public function updateAnuncioStatus(int $anuncioId, string $newStatus, ?int $anuncianteUserId = null): bool
    {
        $stmt = null; 
        try {
            $this->conn->beginTransaction(); // Inicia a transação

            $query = "UPDATE anuncios SET status = :status, updated_at = NOW()";
            // Se o novo status for 'deleted', também preenche 'deleted_at' na tabela de anúncios
            if ($newStatus === 'deleted') {
                $query .= ", deleted_at = NOW()";
            } else {
                // Se o status NÃO for 'deleted', garante que 'deleted_at' seja NULL
                $query .= ", deleted_at = NULL";
            }
            $query .= " WHERE id = :anuncio_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $newStatus, \PDO::PARAM_STR);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                error_log("ERRO ANUNCIO: updateAnuncioStatus - Falha ao atualizar status no DB para Anúncio ID " . $anuncioId . ". Erro PDO: " . $errorInfo[2]);
                $this->result = false;
                $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar o status do anúncio.'];
                $this->conn->rollBack();
                return false;
            }

            // Atualiza o status do anúncio na tabela `usuarios` se o ID do anunciante for fornecido
            if ($anuncianteUserId !== null) {
                $hasAnuncio = true; // Para active, inactive, pending, rejected, deleted o anúncio existe
                // Se o status for 'deleted', o has_anuncio deve permanecer true, mas o anuncio_status deve ser 'deleted'
                // A lógica de "não tem anúncio" (has_anuncio = false) é apenas quando o anúncio é fisicamente removido,
                // o que não é mais o caso com o soft delete.
                if (!$this->updateUserAnuncioStatus($anuncianteUserId, $newStatus, $hasAnuncio)) {
                    error_log("ERRO ANUNCIO: updateAnuncioStatus - Falha ao atualizar status do usuário ID " . $anuncianteUserId);
                    $this->result = false;
                    $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar o status do anúncio e do usuário.'];
                    $this->conn->rollBack();
                    return false;
                }
            }

            $this->conn->commit(); // Confirma a transação
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Status do anúncio atualizado com sucesso!'];
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack(); // Reverte a transação em caso de erro
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: updateAnuncioStatus - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao atualizar status do anúncio.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack(); // Reverte a transação em caso de error
            error_log("ERRO GERAL ANUNCIO: updateAnuncioStatus - Erro geral: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao atualizar o status do anúncio.'];
            return false;
        }
    }

    /**
     * Deleta (soft delete) um anúncio do banco de dados.
     * As mídias físicas e os registros das tabelas de relacionamento SÃO MANTIDOS.
     * O anúncio principal é marcado com status 'deleted' e 'deleted_at' preenchido.
     *
     * @param int $anuncioId O ID do anúncio a ser excluído.
     * @param int|null $anuncianteUserId Opcional. O ID do usuário anunciante para atualizar a tabela `usuarios`.
     * @return bool True se a exclusão for bem-sucedida, false caso contrário.
     */
    public function deleteAnuncio(int $anuncioId, ?int $anuncianteUserId = null): bool
    {
        $stmtDeleteAnuncio = null; 
        try {
            $this->conn->beginTransaction(); // Inicia a transação

            // 1. Deletar (soft delete) o registro principal do anúncio
            // ALTERADO: De DELETE para UPDATE (soft delete)
            $queryDeleteAnuncio = "UPDATE anuncios SET status = 'deleted', deleted_at = NOW() WHERE id = :anuncio_id";
            $stmtDeleteAnuncio = $this->conn->prepare($queryDeleteAnuncio);
            $stmtDeleteAnuncio->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            if (!$stmtDeleteAnuncio->execute()) {
                $errorInfo = $stmtDeleteAnuncio->errorInfo();
                error_log("ERRO ANUNCIO: deleteAnuncio - Falha ao marcar anúncio principal como deletado. Erro PDO: " . $errorInfo[2]);
                throw new Exception("Falha ao deletar (soft delete) anúncio principal.");
            }

            // 2. Mídias físicas e registros de relacionamento SÃO MANTIDOS.
            // As chamadas para deleteMediaFromDb e deleteFile foram removidas daqui.

            // 3. Atualiza o status do anúncio do usuário na tabela `usuarios`
            // O has_anuncio deve permanecer true, e o anuncio_status deve ser 'deleted'
            if ($anuncianteUserId !== null) {
                if (!$this->updateUserAnuncioStatus($anuncianteUserId, 'deleted', true)) { // 'deleted' e has_anuncio = true
                    error_log("ERRO ANUNCIO: deleteAnuncio - Falha ao atualizar status do usuário ID " . $anuncianteUserId . " após soft delete do anúncio.");
                    $this->result = false;
                    $this->msg = ['type' => 'error', 'text' => 'Anúncio marcado como excluído, mas houve um erro ao atualizar o status do usuário.'];
                    $this->conn->rollBack();
                    return false;
                }
            }

            $this->conn->commit(); // Confirma a transação
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio marcado como excluído com sucesso para auditoria!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errorInfo = ($stmtDeleteAnuncio instanceof \PDOStatement) ? $stmtDeleteAnuncio->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: Falha na transação de soft delete. Rollback. Mensagem: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao marcar anúncio como excluído.'];
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL ANUNCIO: Falha no soft delete. Rollback. Mensagem: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao marcar o anúncio como excluído.'];
            return false;
        }
    }

    /**
     * Busca um anúncio específico pelo ID do anúncio.
     * Usado internamente para obter dados existentes antes de uma atualização.
     * Este método RETORNA ANÚNCIOS DELETADOS (deleted_at IS NOT NULL) se $includeDeleted for true.
     * @param int $anuncioId O ID do anúncio a ser buscado.
     * @param bool $includeDeleted Se true, inclui anúncios marcados como deletados.
     * @return array|null Retorna um array associativo com os dados do anúncio se encontrado, ou null.
     */
    public function getAnuncioById(int $anuncioId, bool $includeDeleted = false): ?array
    {
        $stmt = null; 
        error_log("DEBUG ANUNCIO: getAnuncioById - Buscando anúncio para Anúncio ID: " . $anuncioId . ", Incluir Deletados: " . ($includeDeleted ? 'true' : 'false'));
        try {
            $query = "SELECT
                                a.id, a.user_id, a.service_name, a.state_uf, a.city_code, a.neighborhood_name, a.age, a.height_m, a.weight_kg,
                                a.gender, a.nationality, a.ethnicity, a.eye_color, a.phone_number, a.description, a.price_15min, a.price_30min, a.price_1h,
                                a.cover_photo_path, a.confirmation_video_path, a.plan_type, a.status, a.created_at, a.updated_at, a.deleted_at, a.visits
                            FROM anuncios AS a
                            WHERE a.id = :anuncio_id";
            if (!$includeDeleted) {
                $query .= " AND a.deleted_at IS NULL";
            }
            $query .= " LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            $anuncio = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($anuncio) {
                error_log("DEBUG ANUNCIO: getAnuncioById - Anúncio encontrado para Anúncio ID: " . $anuncioId);
                error_log("DEBUG ANUNCIO: getAnuncioById - confirmation_video_path do BD: " . ($anuncio['confirmation_video_path'] ?? 'NÃO ENCONTRADO NO BD'));
                error_log("DEBUG ANUNCIO: getAnuncioById - Status do BD: " . ($anuncio['status'] ?? 'NÃO ENCONTRADO NO BD'));
                error_log("DEBUG ANUNCIO: getAnuncioById - phone_number do BD: " . ($anuncio['phone_number'] ?? 'NÃO ENCONTRADO NO BD'));


                // Buscar dados das tabelas de relacionamento
                $anuncio['aparencia'] = $this->getRelatedData($anuncio['id'], 'anuncio_aparencias', 'aparencia_item');
                $anuncio['idiomas'] = $this->getRelatedData($anuncio['id'], 'anuncio_idiomas', 'idioma_name');
                $anuncio['locais_atendimento'] = $this->getRelatedData($anuncio['id'], 'anuncio_locais_atendimento', 'local_name');
                $anuncio['formas_pagamento'] = $this->getRelatedData($anuncio['id'], 'anuncio_formas_pagamento', 'forma_name');
                $anuncio['servicos'] = $this->getRelatedData($anuncio['id'], 'anuncio_servicos_oferecidos', 'servico_name');
                $anuncio['fotos_galeria'] = $this->getMediaPaths($anuncio['id'], 'anuncio_fotos');
                $anuncio['videos'] = $this->getMediaPaths($anuncio['id'], 'anuncio_videos');
                $anuncio['audios'] = $this->getMediaPaths($anuncio['id'], 'anuncio_audios');

                // Mapear UF para nome do estado
                $anuncio['state_name'] = $this->statesLookup[$anuncio['state_uf']] ?? $anuncio['state_uf'];
                // Mapear código da cidade para nome da cidade
                $anuncio['city_name'] = $this->citiesLookup[$anuncio['city_code']] ?? $anuncio['city_code'];

                // Formatar preços para o frontend (com vírgula)
                // Usar isset() para evitar "Undefined array key" se o valor for NULL do DB
                $anuncio['price_15min'] = isset($anuncio['price_15min']) && $anuncio['price_15min'] !== null ? number_format((float)$anuncio['price_15min'], 2, ',', '') : '';
                $anuncio['price_30min'] = isset($anuncio['price_30min']) && $anuncio['price_30min'] !== null ? number_format((float)$anuncio['price_30min'], 2, ',', '') : '';
                $anuncio['price_1h'] = isset($anuncio['price_1h']) && $anuncio['price_1h'] !== null ? number_format((float)$anuncio['price_1h'], 2, ',', '') : '';

                // Formatar altura e peso para o frontend (com vírgula)
                $anuncio['height_m'] = $anuncio['height_m'] ? number_format((float)$anuncio['height_m'], 2, ',', '') : '';
                $anuncio['weight_kg'] = $anuncio['weight_kg'] ? (string)(int)$anuncio['weight_kg'] : ''; // Apenas o número inteiro

                // Prefixar o caminho da foto de capa e do vídeo de confirmação com a URL base para o frontend
                // ATENÇÃO: Se o caminho já for uma URL absoluta (ex: de um CDN), não prefixar novamente.
                if (!empty($anuncio['cover_photo_path'])) {
                    if (!filter_var($anuncio['cover_photo_path'], FILTER_VALIDATE_URL)) {
                        $anuncio['cover_photo_path'] = URL . $anuncio['cover_photo_path'];
                    }
                }
                if (!empty($anuncio['confirmation_video_path'])) {
                    if (!filter_var($anuncio['confirmation_video_path'], FILTER_VALIDATE_URL)) {
                        $anuncio['confirmation_video_path'] = URL . $anuncio['confirmation_video_path'];
                    }
                }

                return $anuncio;
            }
            error_log("DEBUG ANUNCIO: getAnuncioById - Nenhum anúncio encontrado para Anúncio ID: " . $anuncioId);
            return null;
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getAnuncioById - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return null;
        }
    }

    /**
     * Busca um anúncio específico pelo ID do usuário.
     * Este método pode RETORNAR ANÚNCIOS DELETADOS (deleted_at IS NOT NULL) se $includeDeleted for true.
     * @param int $userId O ID do usuário cujo anúncio será buscado.
     * @param bool $includeDeleted Se true, inclui anúncios marcados como deletados.
     * @return array|null Retorna um array associativo com os dados do anúncio se encontrado, ou null.
     */
    public function getAnuncioByUserId(int $userId, bool $includeDeleted = false): ?array
    {
        $stmt = null; 
        error_log("DEBUG ANUNCIO: getAnuncioByUserId - Buscando anúncio para User ID: " . $userId . ", Incluir Deletados: " . ($includeDeleted ? 'true' : 'false'));
        try {
            $query = "SELECT
                                a.id, a.user_id, a.service_name, a.state_uf, a.city_code, a.neighborhood_name, a.age, a.height_m, a.weight_kg,
                                a.gender, a.nationality, a.ethnicity, a.eye_color, a.phone_number, a.description, a.price_15min, price_30min, price_1h,
                                a.cover_photo_path, a.confirmation_video_path, a.plan_type, a.status, a.created_at, a.updated_at, a.deleted_at, a.visits
                            FROM anuncios AS a
                            WHERE a.user_id = :user_id";
            if (!$includeDeleted) { // Aplica filtro apenas se não for para incluir deletados
                $query .= " AND a.deleted_at IS NULL";
            }
            $query .= " LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $anuncio = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($anuncio) {
                error_log("DEBUG ANUNCIO: getAnuncioByUserId - Anúncio encontrado para User ID: " . $userId);
                error_log("DEBUG ANUNCIO: getAnuncioByUserId - confirmation_video_path do BD: " . ($anuncio['confirmation_video_path'] ?? 'NÃO ENCONTRADO NO BD'));
                error_log("DEBUG ANUNCIO: getAnuncioByUserId - Status do BD: " . ($anuncio['status'] ?? 'NÃO ENCONTRADO NO BD'));
                error_log("DEBUG ANUNCIO: getAnuncioByUserId - phone_number do BD: " . ($anuncio['phone_number'] ?? 'NÃO ENCONTRADO NO BD'));


                // Buscar dados das tabelas de relacionamento
                $anuncio['aparencia'] = $this->getRelatedData($anuncio['id'], 'anuncio_aparencias', 'aparencia_item');
                $anuncio['idiomas'] = $this->getRelatedData($anuncio['id'], 'anuncio_idiomas', 'idioma_name');
                $anuncio['locais_atendimento'] = $this->getRelatedData($anuncio['id'], 'anuncio_locais_atendimento', 'local_name');
                $anuncio['formas_pagamento'] = $this->getRelatedData($anuncio['id'], 'anuncio_formas_pagamento', 'forma_name');
                $anuncio['servicos'] = $this->getRelatedData($anuncio['id'], 'anuncio_servicos_oferecidos', 'servico_name');
                $anuncio['fotos_galeria'] = $this->getMediaPaths($anuncio['id'], 'anuncio_fotos');
                $anuncio['videos'] = $this->getMediaPaths($anuncio['id'], 'anuncio_videos');
                $anuncio['audios'] = $this->getMediaPaths($anuncio['id'], 'anuncio_audios');

                // Mapear UF para nome do estado
                $anuncio['state_name'] = $this->statesLookup[$anuncio['state_uf']] ?? $anuncio['state_uf'];
                // Mapear código da cidade para nome da cidade
                $anuncio['city_name'] = $this->citiesLookup[$anuncio['city_code']] ?? $anuncio['city_code'];

                // Formatar preços para o frontend (com vírgula)
                // Usar isset() para evitar "Undefined array key" se o valor for NULL do DB
                $anuncio['price_15min'] = isset($anuncio['price_15min']) && $anuncio['price_15min'] !== null ? number_format((float)$anuncio['price_15min'], 2, ',', '') : '';
                $anuncio['price_30min'] = isset($anuncio['price_30min']) && $anuncio['price_30min'] !== null ? number_format((float)$anuncio['price_30min'], 2, ',', '') : '';
                $anuncio['price_1h'] = isset($anuncio['price_1h']) && $anuncio['price_1h'] !== null ? number_format((float)$anuncio['price_1h'], 2, ',', '') : '';

                // Formatar altura e peso para o frontend (com vírgula)
                $anuncio['height_m'] = $anuncio['height_m'] ? number_format((float)$anuncio['height_m'], 2, ',', '') : '';
                $anuncio['weight_kg'] = $anuncio['weight_kg'] ? (string)(int)$anuncio['weight_kg'] : ''; // Apenas o número inteiro

                // Prefixar o caminho da foto de capa e do vídeo de confirmação com a URL base para o frontend
                // ATENÇÃO: Se o caminho já for uma URL absoluta (ex: de um CDN), não prefixar novamente.
                if (!empty($anuncio['cover_photo_path'])) {
                    if (!filter_var($anuncio['cover_photo_path'], FILTER_VALIDATE_URL)) {
                        $anuncio['cover_photo_path'] = URL . $anuncio['cover_photo_path'];
                    }
                }
                if (!empty($anuncio['confirmation_video_path'])) {
                    if (!filter_var($anuncio['confirmation_video_path'], FILTER_VALIDATE_URL)) {
                        $anuncio['confirmation_video_path'] = URL . $anuncio['confirmation_video_path'];
                    }
                }

                return $anuncio;
            }
            error_log("DEBUG ANUNCIO: getAnuncioByUserId - Nenhum anúncio encontrado para User ID: " . $userId);
            return null;
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getAnuncioByUserId - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return null;
        }
    }

    /**
     * NOVO MÉTODO: Obtém o user_id do proprietário de um anúncio dado o ID do anúncio.
     * @param int $anuncioId O ID do anúncio.
     * @return int|null O user_id do proprietário ou null se o anúncio não for encontrado.
     */
    public function getAnuncioOwnerId(int $anuncioId): ?int
    {
        $stmt = null;
        try {
            $query = "SELECT user_id FROM anuncios WHERE id = :anuncio_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['user_id'] ?? null;
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getAnuncioOwnerId - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return null;
        }
    }


/**
     * Busca os últimos anúncios para o dashboard do administrador, com paginação e filtro.
     * @param int $page A página atual.
     * @param int $limit O número de registros por página.
     * @param string $searchTerm Termo de busca para nome/email do anunciante.
     * @param string $filterStatus Status para filtrar ('all', 'active', 'pending', 'rejected', 'inactive', 'deleted').
     * @return array Retorna um array de anúncios.
     */
    public function getLatestAnuncios(int $page, int $limit, string $searchTerm = '', string $filterStatus = 'all'): array
    {
        $stmt = null; 
        $offset = ($page - 1) * $limit;
        $binds = [];
        $searchConditions = [];

        $query = "SELECT
                     a.id, a.user_id, a.status, a.created_at, a.visits, a.gender AS category_gender, a.service_name, a.deleted_at,
                     u.nome AS user_name, u.email AS user_email, a.state_uf, a.city_code
                  FROM anuncios AS a
                  JOIN usuarios AS u ON a.user_id = u.id
                  WHERE 1=1";

        // Adiciona filtro por termo de busca
        if (!empty($searchTerm)) {
            $searchFields = ['u.nome', 'u.email', 'a.gender', 'a.status', 'a.service_name'];
            foreach ($searchFields as $index => $field) {
                $paramName = ":search_term_" . $index;
                $searchConditions[] = "{$field} LIKE {$paramName}";
                $binds[$paramName] = '%' . $searchTerm . '%';
            }
            if (!empty($searchConditions)) {
                $query .= " AND (" . implode(' OR ', $searchConditions) . ")";
            }
        }

        // Lógica de filtro de status corrigida para funcionar com 'all' e 'deleted'
        if ($filterStatus === 'deleted') {
            $query .= " AND a.deleted_at IS NOT NULL";
        } else if ($filterStatus !== 'all') {
            $query .= " AND a.status = :status AND a.deleted_at IS NULL";
            $binds[':status'] = $filterStatus;
        }
        // Se filterStatus for 'all', não adiciona nenhuma condição de deleted_at ou status,
        // pois a cláusula 'WHERE 1=1' já permite buscar todos os registros.
        
        $query .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

        $binds[':limit'] = $limit;
        $binds[':offset'] = $offset;

        error_log("DEBUG ANUNCIO: getLatestAnuncios - Query FINAL: " . $query);
        error_log("DEBUG ANUNCIO: getLatestAnuncios - Binds FINAIS: " . print_r($binds, true));

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
            $anuncios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            error_log("DEBUG ANUNCIO: getLatestAnuncios - Resultados brutos: " . print_r($anuncios, true));

            foreach ($anuncios as &$anuncio) {
                $anuncio['category'] = $anuncio['category_gender'];
                unset($anuncio['category_gender']);
                $anuncio['visits'] = number_format($anuncio['visits'] ?? 0, 0, ',', '.');
                $anuncio['created_at'] = date('d/m/Y H:i', strtotime($anuncio['created_at']));
                $anuncio['state_name'] = $this->statesLookup[$anuncio['state_uf']] ?? $anuncio['state_uf'];
                $anuncio['city_name'] = $this->citiesLookup[$anuncio['city_code']] ?? $anuncio['city_code'];
            }
            error_log("DEBUG ANUNCIO: getLatestAnuncios - Resultados formatados: " . print_r($anuncios, true));
            return $anuncios;
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getLatestAnuncios - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return [];
        }
    }

    /**
     * Retorna o total de anúncios para a paginação, com base no termo de busca e status.
     * @param string $searchTerm Termo de busca para nome/email do anunciante.
     * @param string $filterStatus Status para filtrar ('all', 'active', 'pending', 'rejected', 'inactive', 'deleted').
     * @return int O total de anúncios.
     */
    public function getTotalAnuncios(string $searchTerm = '', string $filterStatus = 'all'): int
    {
        $stmt = null; 
        $binds = [];
        $searchConditions = [];
        
        $query = "SELECT COUNT(a.id) AS total
                  FROM anuncios AS a
                  JOIN usuarios AS u ON a.user_id = u.id
                  WHERE 1=1";

        if (!empty($searchTerm)) {
            $searchFields = ['u.nome', 'u.email', 'a.gender', 'a.status', 'a.service_name'];
            foreach ($searchFields as $index => $field) {
                $paramName = ":search_term_" . $index;
                $searchConditions[] = "{$field} LIKE {$paramName}";
                $binds[$paramName] = '%' . $searchTerm . '%';
            }
            if (!empty($searchConditions)) {
                $query .= " AND (" . implode(' OR ', $searchConditions) . ")";
            }
        }

        // Lógica de filtro de status corrigida para funcionar com 'all' e 'deleted'
        if ($filterStatus === 'deleted') {
            $query .= " AND a.deleted_at IS NOT NULL";
        } else if ($filterStatus !== 'all') {
            $query .= " AND a.status = :status AND a.deleted_at IS NULL";
            $binds[':status'] = $filterStatus;
        }
        
        error_log("DEBUG ANUNCIO: getTotalAnuncios - Query FINAL: " . $query);
        error_log("DEBUG ANUNCIO: getTotalAnuncios - Binds FINAIS: " . print_r($binds, true));

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value, \PDO::PARAM_STR); 
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("DEBUG ANUNCIO: getTotalAnuncios - Resultado: " . ($result['total'] ?? '0'));
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getTotalAnuncios - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return 0;
        }
    }

    /**
     * Busca dados de tabelas de relacionamento (checkboxes).
     * @param int $anuncioId O ID do anúncio.
     * @param string $tableName O nome da tabela de relacionamento.
     * @param string $columnName O nome da coluna que armazena o item.
     * @return array Retorna um array de strings com os itens relacionados.
     */
    private function getRelatedData(int $anuncioId, string $tableName, string $columnName): array
    {
        $stmt = null; 
        try {
            $query = "SELECT {$columnName} FROM {$tableName} WHERE anuncio_id = :anuncio_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), $columnName);
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getRelatedData - Erro PDO: " . $e->getMessage() . " . SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return [];
        }
    }

    /**
     * Busca caminhos de mídia (fotos, vídeos, áudios).
     * @param int $anuncioId O ID do anúncio.
     * @param string $tableName O nome da tabela de mídia (anuncio_fotos, anuncio_videos, anuncio_audios).
     * @param bool $prefixWithUrl Se deve prefixar o caminho com a URL base para o frontend.
     * @return array Retorna um array de strings com os caminhos dos arquivos.
     */
    private function getMediaPaths(int $anuncioId, string $tableName, bool $prefixWithUrl = true): array
    {
        $stmt = null; 
        try {
            $query = "SELECT path FROM {$tableName} WHERE anuncio_id = :anuncio_id ORDER BY id ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            $paths = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'path');

            if ($prefixWithUrl) {
                $prefixedPaths = [];
                foreach ($paths as $path) {
                    // Verifica se o caminho já é uma URL absoluta antes de prefixar
                    if (!filter_var($path, FILTER_VALIDATE_URL)) {
                        $prefixedPaths[] = URL . $path;
                    } else {
                        $prefixedPaths[] = $path; // Já é uma URL absoluta
                    }
                }
                return $prefixedPaths;
            }
            return $paths;
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getMediaPaths - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return [];
        }
    }

    /**
     * Verifica se o usuário logado já possui um anúncio cadastrado que NÃO esteja soft-deletado.
     * @return bool True se o usuário já tem um anúncio ativo, false caso contrário.
     */
    private function checkExistingAnuncio(): bool
    {
        $stmt = null;
        try {
            // Apenas verifica anúncios que NÃO foram soft-deletados
            $query = "SELECT id FROM anuncios WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1"; 
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->userId, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC); // Fetch the row
            $exists = ($result !== false && $result !== null); // Check if a row was actually returned
            error_log("DEBUG ANUNCIO: checkExistingAnuncio - Verificando para o usuário ID: " . $this->userId . ", Resultado da Query: " . print_r($result, true) . ", Existe: " . ($exists ? 'TRUE' : 'FALSE'));
            return $exists;
        } catch (PDOException $e) {
            $errorInfo = ($stmt instanceof \PDOStatement) ? $stmt->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: checkExistingAnuncio - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return false;
        }
    }

    /**
     * Obtém o tipo de plano do usuário logado.
     * @param int $userId O ID do usuário para buscar o plano.
     * @return bool True se o plano for obtido com sucesso, false caso contrário.
     */
    private function getUserPlanType(int $userId): bool
    {
        $stmtUser = null; 
        try {
            $queryUser = "SELECT plan_type FROM usuarios WHERE id = :user_id LIMIT 1";
            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmtUser->execute();
            $user = $stmtUser->fetch(\PDO::FETCH_ASSOC);

            if ($user && isset($user['plan_type'])) {
                $this->userPlanType = $user['plan_type'];
                return true;
            }
            $this->userPlanType = 'free'; // Fallback para 'free' se não encontrar ou não tiver plan_type
            return false;
        } catch (PDOException $e) {
            $errorInfo = ($stmtUser instanceof \PDOStatement) ? $stmtUser->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getUserPlanType - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->userPlanType = 'free'; // Fallback para 'free' em caso de erro
            return false;
        }
    }

    /**
     * Valida os campos obrigatórios e formata dados antes da inserção/atualização.
     * @return bool True se a validação for bem-sucedida, false caso contrário.
     */
    private function validateInput(): bool
    {
        $this->msg = []; // Limpa mensagens de erro anteriores
        $errors = [];

        // Limpa e valida os campos de texto/número/select
        $fieldsToTrim = [
            'service_name',
            'state_id', 'city_id', 'neighborhood_name', 'age', 'height_m', 'weight_kg', 'nationality',
            'description', 'ethnicity', 'eye_color', 'gender', 'phone_number'
        ];
        foreach ($fieldsToTrim as $field) {
            $this->data[$field] = trim($this->data[$field] ?? '');
        }

        $requiredFields = [
            'service_name',
            'state_id', 'city_id', 'neighborhood_name', 'age', 'height_m', 'weight_kg', 'nationality',
            'description', 'gender', 'phone_number'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->data[$field])) {
                $errors[$field] = 'O campo é obrigatório.'; // Mensagem genérica para feedback no frontend
            }
        }

        // Validação de Nome de Serviço (exemplo: mínimo de caracteres)
        if (strlen($this->data['service_name']) < 3) {
            $errors['service_name'] = 'O nome do serviço deve ter pelo menos 3 caracteres.';
        }


        // Validação de Idade (age)
        if (!filter_var($this->data['age'], FILTER_VALIDATE_INT, ["options" => ["min_range"=>18, "max_range"=>99]])) {
            $errors['age'] = 'A idade deve ser um número inteiro entre 18 e 99.';
        }

        // Validação de Altura (height_m)
        // O valor já deve vir formatado com ponto do JS, então apenas valida e converte para float.
        $alturaFloat = (float)str_replace(',', '.', $this->data['height_m'] ?? '0'); 
        if (!is_numeric($alturaFloat) || $alturaFloat <= 0.5 || $alturaFloat > 3.0) {
            $errors['height_m'] = 'A altura deve ser um número válido (ex: 1,70) entre 0.50 e 3.00 metros.';
        } else {
            // Salva o valor como float para uso posterior
            $this->data['height_m'] = $alturaFloat;
        }

        // Validação de Peso (weight_kg)
        $pesoInt = (int)preg_replace('/\D/', '', $this->data['weight_kg'] ?? '0'); // Remove não-dígitos
        if (!filter_var($pesoInt, FILTER_VALIDATE_INT, ["options" => ["min_range"=>10, "max_range"=>500]])) {
            $errors['weight_kg'] = 'O peso deve ser um número inteiro válido (ex: 65) entre 10 e 500 kg.';
        } else {
            // Salva o valor como int para uso posterior
            $this->data['weight_kg'] = $pesoInt;
        }

        // Validação de Telefone (formato (XX) XXXXX-XXXX)
        $cleanPhoneNumber = preg_replace('/\D/', '', $this->data['phone_number']);
        if (!preg_match('/^\d{10,11}$/', $cleanPhoneNumber)) {
            $errors['phone_number'] = 'Formato de telefone inválido. Use (XX) XXXXX-XXXX.';
        }

        // Validação de Preços (pelo menos um deve ser preenchido)
        // Os valores já vêm como float (com ponto) do JS, ou string vazia.
        $price15 = !empty($this->data['price_15min'] ?? '') ? (float)str_replace(',', '.', $this->data['price_15min']) : 0.0;
        $price30 = !empty($this->data['price_30min'] ?? '') ? (float)str_replace(',', '.', $this->data['price_30min']) : 0.0;
        $price1h = !empty($this->data['price_1h'] ?? '') ? (float)str_replace(',', '.', $this->data['price_1h']) : 0.0;

        if ($price15 <= 0 && $price30 <= 0 && $price1h <= 0) {
            $errors['precos'] = 'Pelo menos um preço deve ser preenchido com um valor maior que zero.';
        }
        // Atribui os valores formatados de volta para $this->data, ou null se forem zero
        $this->data['price_15min'] = $price15 > 0 ? $price15 : null;
        $this->data['price_30min'] = $price30 > 0 ? $price30 : null;
        $this->data['price_1h'] = $price1h > 0 ? $price1h : null;


        // Validação de checkboxes (mínimo de itens selecionados)
        $checkboxGroups = [
            'aparencia' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 item de aparência.'],
            'idiomas' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 idioma.'],
            'locais_atendimento' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 local de atendimento.'],
            'formas_pagamento' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 forma de pagamento.'],
            'servicos' => ['min' => 2, 'msg' => 'Selecione pelo menos 2 serviços.']
        ];

        foreach ($checkboxGroups as $groupName => $rules) {
            $this->data[$groupName] = $this->data[$groupName] ?? [];
            if (count($this->data[$groupName]) < $rules['min']) {
                $errors[$groupName] = $rules['msg'];
            }
        }

        // Validação de Mídia com base no plano (para criação e atualização)
        $isUpdateMode = ($this->existingAnuncio !== null);

        // Validação do Vídeo de Confirmação
        $confirmationVideoFile = $this->files['confirmation_video'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => ''];
        $confirmationVideoRemoved = ($this->data['confirmation_video_removed'] ?? 'false') === 'true';
        $hasExistingConfirmationVideo = !empty($this->existingAnuncio['confirmation_video_path'] ?? null);

        // A validação agora verifica se há um vídeo existente OU um novo vídeo sendo enviado
        if (!$hasExistingConfirmationVideo && ($confirmationVideoFile['error'] === UPLOAD_ERR_NO_FILE || empty($confirmationVideoFile['name'])) && !$confirmationVideoRemoved) {
            $errors['confirmationVideo'] = 'O vídeo de confirmação é obrigatório.';
        }
        if ($confirmationVideoFile['error'] !== UPLOAD_ERR_NO_FILE && $confirmationVideoFile['error'] !== UPLOAD_ERR_OK) {
            $errors['confirmationVideo'] = 'Erro no upload do vídeo de confirmação.';
        }
        
        // Validação da Foto de Capa
        $coverPhotoFile = $this->files['foto_capa'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => ''];
        $coverPhotoRemoved = ($this->data['cover_photo_removed'] ?? 'false') === 'true';
        $hasExistingCoverPhoto = !empty($this->existingAnuncio['cover_photo_path'] ?? null);

        if (!$hasExistingCoverPhoto && ($coverPhotoFile['error'] === UPLOAD_ERR_NO_FILE || empty($coverPhotoFile['name'])) && !$coverPhotoRemoved) {
             $errors['coverPhoto'] = 'A foto da capa é obrigatória.';
        }
        if ($coverPhotoFile['error'] !== UPLOAD_ERR_NO_FILE && $coverPhotoFile['error'] !== UPLOAD_ERR_OK) {
            $errors['coverPhoto'] = 'Erro no upload da foto de capa.';
        }


        // --- VALIDAÇÃO DA GALERIA DE FOTOS ---
        // Contagem de novas fotos da galeria
        $totalNewGalleryFiles = 0;
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            foreach ($this->files['fotos_galeria']['name'] as $index => $name) {
                if ($this->files['fotos_galeria']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) {
                    $totalNewGalleryFiles++;
                }
            }
        }

        // Contagem de fotos da galeria existentes que o usuário deseja manter
        $keptGalleryPathsCount = 0;
        // O JS envia existing_gallery_paths[] no POST, não fotos_galeria[] para caminhos existentes
        if (isset($this->data['fotos_galeria_existing']) && is_array($this->data['fotos_galeria_existing'])) {
            foreach ($this->data['fotos_galeria_existing'] as $path) {
                if (is_string($path) && !empty($path)) {
                    $keptGalleryPathsCount++;
                }
            }
        }
        
        // Total de fotos que estarão no anúncio após a operação (novas + mantidas)
        $currentTotalGalleryPhotos = $keptGalleryPathsCount + $totalNewGalleryFiles;

        $freePhotoLimit = 1;
        $minPhotosRequired = 1; // Mínimo de 1 foto na galeria para qualquer plano

        if ($currentTotalGalleryPhotos < $minPhotosRequired) {
            $errors['galleryPhotoContainer'] = 'Mínimo de ' . $minPhotosRequired . ' foto(s) na galeria é obrigatório.';
        }
        if ($this->userPlanType === 'free' && $currentTotalGalleryPhotos > $freePhotoLimit) {
            $errors['galleryPhotoContainer'] = 'Seu plano gratuito permite apenas ' . $freePhotoLimit . ' foto na galeria.';
        }
        if ($this->userPlanType === 'premium' && $currentTotalGalleryPhotos > 20) {
            $errors['galleryPhotoContainer'] = 'Seu plano premium permite no máximo 20 fotos na galeria.';
        }


        // Validação de Vídeos
        $totalNewVideoFiles = 0;
        if (isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            foreach ($this->files['videos']['name'] as $index => $name) {
                if ($this->files['videos']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) {
                    $totalNewVideoFiles++;
                }
            }
        }
        $keptVideoPathsCount = 0;
        if (isset($this->data['videos_existing']) && is_array($this->data['videos_existing'])) {
            foreach ($this->data['videos_existing'] as $path) {
                if (is_string($path) && !empty($path)) {
                    $keptVideoPathsCount++;
                }
            }
        }
        $currentTotalVideoFiles = $keptVideoPathsCount + $totalNewVideoFiles;


        if ($this->userPlanType === 'free') {
            if ($currentTotalVideoFiles > 0) {
                $errors['videoUploadBoxes'] = 'Vídeos são permitidos apenas para planos pagos.';
            }
        } else { // premium
            if ($currentTotalVideoFiles > 3) {
                $errors['videoUploadBoxes'] = 'Seu plano premium permite no máximo 3 vídeos.';
            }
        }

        // Validação de Áudios
        $totalNewAudioFiles = 0;
        if (isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
            foreach ($this->files['audios']['name'] as $index => $name) {
                if ($this->files['audios']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) {
                    $totalNewAudioFiles++;
                }
            }
        }
        $keptAudioPathsCount = 0;
        if (isset($this->data['audios_existing']) && is_array($this->data['audios_existing'])) {
            foreach ($this->data['audios_existing'] as $path) {
                if (is_string($path) && !empty($path)) {
                    $keptAudioPathsCount++;
                }
            }
        }
        $currentTotalAudios = $keptAudioPathsCount + $totalNewAudioFiles;

        if ($this->userPlanType === 'free') {
            if ($currentTotalAudios > 0) {
                $errors['audioUploadBoxes'] = 'Áudios são permitidos apenas para planos pagos.';
            }
        } else { // premium
            if ($currentTotalAudios > 3) {
                $errors['audioUploadBoxes'] = 'Seu plano premium permite no máximo 3 áudios.';
            }
        }

        if (!empty($errors)) {
            $this->msg = ['type' => 'error', 'text' => 'Por favor, corrija os erros no formulário.', 'errors' => $errors];
            return false;
        }

        return true;
    }

    /**
     * Insere dados em tabelas de relacionamento (muitos-para-muitos).
     * @param int $anuncioId O ID do anúncio principal.
     * @param string $tableName O nome da tabela de relacionamento.
     * @param array $items Os itens a serem inseridos (ex: ['Magra', 'Loira']).
     * @param string $columnName O nome da coluna que armazena o item.
     * @throws Exception Se a inserção falhar.
     */
    private function insertRelatedData(int $anuncioId, string $tableName, array $items, string $columnName): void
    {
        $stmt = null; 
        if (empty($items)) {
            return;
        }

        $query = "INSERT INTO {$tableName} (anuncio_id, {$columnName}) VALUES (:anuncio_id, :item)";
        $stmt = $this->conn->prepare($query);

        foreach ($items as $item) {
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->bindParam(':item', $item, \PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                error_log("ERRO ANUNCIO: insertRelatedData - Falha ao inserir item '{$item}' na tabela '{$tableName}'. Erro PDO: " . $errorInfo[2]);
                throw new Exception("Falha ao inserir item '{$item}' na tabela '{$tableName}'.");
            }
        }
    }

    /**
     * Atualiza dados em tabelas de relacionamento (muitos-para-muitos).
     * Primeiro deleta todos os registros existentes para o anuncio_id, depois insere os novos.
     * @param int $anuncioId O ID do anúncio principal.
     * @param string $tableName O nome da tabela de relacionamento.
     * @param array $items Os itens a serem inseridos (ex: ['Magra', 'Loira']).
     * @param string $columnName O nome da coluna que armazena o item.
     * @throws Exception Se a operação falhar.
     */
    private function updateRelatedData(int $anuncioId, string $tableName, array $items, string $columnName): void
    {
        $stmtDelete = null; 
        // 1. Deleta todos os registros existentes para este anuncio_id na tabela
        $queryDelete = "DELETE FROM {$tableName} WHERE anuncio_id = :anuncio_id";
        $stmtDelete = $this->conn->prepare($queryDelete);
        $stmtDelete->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        if (!$stmtDelete->execute()) {
            $errorInfo = $stmtDelete->errorInfo();
            error_log("ERRO ANUNCIO: updateRelatedData - Falha ao deletar registros antigos da tabela '{$tableName}'. Erro PDO: " . $errorInfo[2]);
            throw new Exception("Falha ao deletar registros antigos da tabela '{$tableName}'.");
        }

        // 2. Insere os novos itens (reutiliza a lógica de insertRelatedData)
        $this->insertRelatedData($anuncioId, $tableName, $items, $columnName);
    }

    /**
     * Lida com o upload e inserção de fotos da galeria, vídeos e áudios.
     * Usado na CRIAÇÃO de um anúncio.
     * @param int $anuncioId O ID do anúncio principal.
     * @return bool True se todos os uploads e inserções forem bem-sucedidos, false caso contrário.
     * @throws Exception Se um upload ou inserção falhar.
     */
    private function handleGalleryUploads(int $anuncioId): bool
    {
        $upload = new Upload();

        // Subdiretórios para diferentes tipos de mídia (já criados no construtor)
        $galleryDir = $this->projectRoot . $this->uploadDir . 'galeria/';
        $videosDir = $this->projectRoot . $this->uploadDir . 'videos/';
        $audiosDir = $this->projectRoot . $this->uploadDir . 'audios/';

        // --- Fotos da Galeria ---
        $totalUploadedGallery = 0;
        // O JS envia 'fotos_galeria[]' como um array de arquivos.
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            foreach ($this->files['fotos_galeria']['name'] as $index => $name) {
                if ($this->files['fotos_galeria']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) { 
                    // Verifica limite de plano
                    if (($this->userPlanType === 'free' && $totalUploadedGallery >= 1) ||
                        ($this->userPlanType === 'premium' && $totalUploadedGallery >= 20)) {
                        error_log("AVISO ANUNCIO: handleGalleryUploads - Limite de fotos excedido para plano atual.");
                        continue;
                    }

                    $file = [
                        'name' => $name,
                        'type' => $this->files['fotos_galeria']['type'][$index],
                        'tmp_name' => $this->files['fotos_galeria']['tmp_name'][$index],
                        'error' => $this->files['fotos_galeria']['error'][$index],
                        'size' => $this->files['fotos_galeria']['size'][$index],
                    ];
                    $uploadedPath = $upload->uploadFile($file, $galleryDir);
                    if ($uploadedPath) {
                        $relativePath = $this->uploadDir . 'galeria/' . basename($uploadedPath);
                        $query = "INSERT INTO anuncio_fotos (anuncio_id, path, order_index, created_at) VALUES (:anuncio_id, :path, :order_index, NOW())";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                        $stmt->bindParam(':path', $relativePath, \PDO::PARAM_STR);
                        $stmt->bindParam(':order_index', $totalUploadedGallery, \PDO::PARAM_INT); // Usar $totalUploadedGallery como order_index
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("ERRO ANUNCIO: handleGalleryUploads - Falha ao inserir foto de galeria no DB. Erro PDO: " . $errorInfo[2]);
                            throw new Exception("Falha ao inserir foto de galeria no banco de dados.");
                        }
                        $totalUploadedGallery++;
                    } else {
                        error_log("ERRO ANUNCIO: handleGalleryUploads - Falha no upload da foto de galeria: " . $upload->getMsg()['text']);
                        throw new Exception("Falha no upload de uma foto da galeria.");
                    }
                }
            }
        }
        // NÃO PROCESSA VÍDEOS/ÁUDIOS AQUI NA CRIAÇÃO, APENAS FOTOS DA GALERIA.
        // A lógica para vídeos/áudios já está em updateGalleryMedia se for um update.
        // Para criação, o handleConfirmationVideoUpload já cuida do vídeo principal.

        return true;
    }

    /**
     * Lida com o upload do vídeo de confirmação.
     * @param string|null $existingVideoPath O caminho do vídeo existente (se houver).
     * @return string|false|null O novo caminho do vídeo (relativo ao root do projeto), null se removido, ou false em caso de erro.
     */
    private function handleConfirmationVideoUpload(?string $existingVideoPath): string|false|null
    {
        $upload = new Upload();
        $confirmationVideoFile = $this->files['confirmation_video'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => ''];
        $confirmationVideoRemoved = ($this->data['confirmation_video_removed'] ?? 'false') === 'true';

        // O caminho existente do banco de dados já deve ser relativo (sem URL base)
        $currentVideoPath = $existingVideoPath; 

        if ($confirmationVideoFile['error'] === UPLOAD_ERR_OK && !empty($confirmationVideoFile['name'])) {
            $uploadedPath = $upload->uploadFile($confirmationVideoFile, $this->projectRoot . $this->uploadDir . 'confirmation_videos/');
            if (!$uploadedPath) {
                $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload do vídeo de confirmação: ' . $upload->getMsg()['text']];
                $this->msg['errors']['confirmationVideo'] = 'Erro no upload do vídeo de confirmação.'; // Feedback corrigido
                return false;
            }
            // NÃO DELETA O ARQUIVO ANTIGO, MANTEMOS TUDO
            return $this->uploadDir . 'confirmation_videos/' . basename($uploadedPath);
        }
        else if ($confirmationVideoRemoved) {
            // Se o vídeo foi marcado para remoção, define o caminho como null no DB
            // NÃO DELETA O ARQUIVO FÍSICO
            return null;
        }
        else {
            // Se nenhum novo vídeo foi enviado e não foi marcado para remoção, mantém o caminho existente
            return $currentVideoPath;
        }
    }

    /**
     * Lida com a atualização das mídias da galeria (fotos, vídeos, áudios).
     * Compara mídias existentes com as mantidas e novas para gerenciar exclusões e inserções.
     *
     * @param int $anuncioId O ID do anúncio principal.
     * @param array $existingAnuncio Os dados do anúncio existente, incluindo caminhos de mídia (prefixados com URL).
     * @return bool True se todas as operações forem bem-sucedidas, false caso contrário.
     * @throws Exception Se uma operação falhar.
     */
    private function updateGalleryMedia(int $anuncioId, array $existingAnuncio): bool 
    {
        $upload = new Upload();
        $galleryDir = $this->projectRoot . $this->uploadDir . 'galeria/';
        $videosDir = $this->projectRoot . $this->uploadDir . 'videos/';
        $audiosDir = $this->projectRoot . $this->uploadDir . 'audios/';

        // Get current paths from DB (relative)
        $currentDbGalleryPaths = $this->getMediaPaths($anuncioId, 'anuncio_fotos', false);
        $currentDbVideoPaths = $this->getMediaPaths($anuncioId, 'anuncio_videos', false);
        $currentDbAudioPaths = $this->getMediaPaths($anuncioId, 'anuncio_audios', false);

        // Get kept paths from POST (these are the existing paths that the user wants to keep)
        // They come in $this->data['fotos_galeria_existing'] as strings (corrigido no JS)
        $keptGalleryPaths = [];
        if (isset($this->data['fotos_galeria_existing']) && is_array($this->data['fotos_galeria_existing'])) {
            foreach ($this->data['fotos_galeria_existing'] as $path) {
                if (is_string($path) && !empty($path)) {
                    // O JS já envia o caminho relativo (sem URL base) para existing_gallery_paths[]
                    $keptGalleryPaths[] = $path;
                }
            }
        }
        error_log("DEBUG ANUNCIO: updateGalleryMedia - Kept Gallery Paths (from POST, relative): " . print_r($keptGalleryPaths, true));

        $keptVideoPaths = [];
        if (isset($this->data['videos_existing']) && is_array($this->data['videos_existing'])) {
            foreach ($this->data['videos_existing'] as $path) {
                if (is_string($path) && !empty($path)) {
                    $keptVideoPaths[] = $path;
                }
            }
        }
        error_log("DEBUG ANUNCIO: updateGalleryMedia - Kept Video Paths (from POST, relative): " . print_r($keptVideoPaths, true));


        $keptAudioPaths = [];
        if (isset($this->data['audios_existing']) && is_array($this->data['audios_existing'])) {
            foreach ($this->data['audios_existing'] as $path) {
                if (is_string($path) && !empty($path)) {
                    $keptAudioPaths[] = $path;
                }
            }
        }
        error_log("DEBUG ANUNCIO: updateGalleryMedia - Kept Audio Paths (from POST, relative): " . print_r($keptAudioPaths, true));

        // --- Processar Fotos da Galeria ---
        // NÃO DELETA ARQUIVOS FÍSICOS. Apenas limpa o DB e re-insere.
        $this->deleteMediaFromDb($anuncioId, 'anuncio_fotos'); // Limpa o DB antes de reinserir

        $newUploadedGalleryPaths = [];
        $currentTotalPhotos = count($keptGalleryPaths); // Começa a contagem com as fotos que foram mantidas
        $freePhotoLimit = 1;
        $premiumPhotoLimit = 20;

        // O JS envia 'fotos_galeria[]' como um array de arquivos.
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            foreach ($this->files['fotos_galeria']['name'] as $index => $name) {
                if ($this->files['fotos_galeria']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) {
                    if (($this->userPlanType === 'free' && $currentTotalPhotos >= $freePhotoLimit) ||
                        ($this->userPlanType === 'premium' && $currentTotalPhotos >= $premiumPhotoLimit)) {
                        error_log("AVISO ANUNCIO: updateGalleryMedia - Limite de fotos excedido para o plano atual ao adicionar nova foto.");
                        continue; // Pula o upload e a inserção se o limite for atingido
                    }

                    $file = [
                        'name' => $name,
                        'type' => $this->files['fotos_galeria']['type'][$index],
                        'tmp_name' => $this->files['fotos_galeria']['tmp_name'][$index],
                        'error' => $this->files['fotos_galeria']['error'][$index],
                        'size' => $this->files['fotos_galeria']['size'][$index],
                    ];
                    $uploadedPath = $upload->uploadFile($file, $galleryDir);
                    if ($uploadedPath) {
                        $newUploadedGalleryPaths[] = $this->uploadDir . 'galeria/' . basename($uploadedPath);
                        $currentTotalPhotos++; // Incrementa a contagem para a próxima iteração
                    } else {
                        error_log("ERRO ANUNCIO: updateGalleryMedia - Falha no upload da nova foto de galeria: " . $upload->getMsg()['text']);
                        // Não lança exceção aqui para permitir que outras fotos sejam processadas, mas registra o erro.
                    }
                }
            }
        }

        $allGalleryPaths = array_merge($keptGalleryPaths, $newUploadedGalleryPaths);

        if (!empty($allGalleryPaths)) {
            $query = "INSERT INTO anuncio_fotos (anuncio_id, path, order_index, created_at) VALUES (:anuncio_id, :path, :order_index, NOW())";
            $stmt = null; 
            $stmt = $this->conn->prepare($query);
            foreach ($allGalleryPaths as $index => $path) {
                $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                $stmt->bindParam(':path', $path, \PDO::PARAM_STR);
                $stmt->bindParam(':order_index', $index, \PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERRO ANUNCIO: updateGalleryMedia - Falha ao re-inserir foto da galeria no DB para índice {$index}. Erro PDO: " . $errorInfo[2]);
                    throw new Exception("Falha ao re-inserir foto da galeria no DB.");
                }
            }
        }

        // --- Processar Vídeos ---
        // NÃO DELETA ARQUIVOS FÍSICOS. Apenas limpa o DB e re-insere.
        $this->deleteMediaFromDb($anuncioId, 'anuncio_videos'); // Limpa o DB antes de reinserir

        $newUploadedVideoPaths = [];
        if ($this->userPlanType === 'premium') {
            $currentTotalVideos = count($keptVideoPaths);
            if (isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
                foreach ($this->files['videos']['name'] as $index => $name) {
                    if ($this->files['videos']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) {
                        if ($currentTotalVideos >= 3) {
                            error_log("AVISO ANUNCIO: updateGalleryMedia - Limite de vídeos excedido para plano premium ao adicionar novo vídeo.");
                            continue;
                        }
                        $file = [
                            'name' => $name,
                            'type' => $this->files['videos']['type'][$index],
                            'tmp_name' => $this->files['videos']['tmp_name'][$index],
                            'error' => $this->files['videos']['error'][$index],
                            'size' => $this->files['videos']['size'][$index],
                        ];
                        $uploadedPath = $upload->uploadFile($file, $videosDir);
                        if ($uploadedPath) {
                            $newUploadedVideoPaths[] = $this->uploadDir . 'videos/' . basename($uploadedPath);
                            $currentTotalVideos++;
                        } else {
                            error_log("ERRO ANUNCIO: updateGalleryMedia - Falha no upload do novo vídeo de galeria: " . $upload->getMsg()['text']);
                            // Não lança exceção aqui para permitir que outros vídeos sejam processados, mas registra o erro.
                        }
                    }
                }
            }
        }
        $allVideoPaths = array_merge($keptVideoPaths, $newUploadedVideoPaths);
        if (!empty($allVideoPaths)) {
            $query = "INSERT INTO anuncio_videos (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
            $stmt = null; 
            $stmt = $this->conn->prepare($query);
            foreach ($allVideoPaths as $path) {
                $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                $stmt->bindParam(':path', $path, \PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERRO ANUNCIO: updateGalleryMedia - Falha ao re-inserir vídeo no DB. Erro PDO: " . $errorInfo[2]);
                    throw new Exception("Falha ao re-inserir vídeo no DB.");
                }
            }
        }

        // --- Processar Áudios ---
        // NÃO DELETA ARQUIVOS FÍSICOS. Apenas limpa o DB e re-insere.
        $this->deleteMediaFromDb($anuncioId, 'anuncio_audios'); // Limpa o DB antes de reinserir

        $newUploadedAudioPaths = [];
        if ($this->userPlanType === 'premium') {
            $currentTotalAudios = count($keptAudioPaths);
            if (isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
                foreach ($this->files['audios']['name'] as $index => $name) {
                    if ($this->files['audios']['error'][$index] === UPLOAD_ERR_OK && !empty($name)) {
                        if ($currentTotalAudios >= 3) {
                            error_log("AVISO ANUNCIO: updateGalleryMedia - Limite de áudios excedido para plano premium ao adicionar novo áudio.");
                            continue;
                        }
                        $file = [
                            'name' => $name,
                            'type' => $this->files['audios']['type'][$index],
                            'tmp_name' => $this->files['audios']['tmp_name'][$index],
                            'error' => $this->files['audios']['error'][$index],
                            'size' => $this->files['audios']['size'][$index],
                        ];
                        $uploadedPath = $upload->uploadFile($file, $audiosDir);
                        if ($uploadedPath) {
                            $newUploadedAudioPaths[] = $this->uploadDir . 'audios/' . basename($uploadedPath);
                            $currentTotalAudios++;
                        } else {
                            error_log("ERRO ANUNCIO: updateGalleryMedia - Falha no upload de novo áudio: " . $upload->getMsg()['text']);
                            // Não lança exceção aqui para permitir que outros áudios sejam processados, mas registra o erro.
                        }
                    }
                }
            }
        }
        $allAudioPaths = array_merge($keptAudioPaths, $newUploadedAudioPaths);
        if (!empty($allAudioPaths)) {
            $query = "INSERT INTO anuncio_audios (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
            $stmt = null; 
            $stmt = $this->conn->prepare($query);
            foreach ($allAudioPaths as $path) {
                $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                $stmt->bindParam(':path', $path, \PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERRO ANUNCIO: updateGalleryMedia - Falha ao re-inserir áudio no DB. Erro PDO: " . $errorInfo[2]);
                    throw new Exception("Falha ao re-inserir áudio no DB.");
                }
            }
        }

        return true;
    }

    /**
     * Deleta um arquivo do sistema de arquivos.
     * **ATENÇÃO**: Esta função foi modificada para NÃO DELETAR arquivos físicos,
     * apenas logar a tentativa, conforme a nova premissa.
     * @param string $relativePath O caminho relativo do arquivo a partir da raiz do projeto.
     */
    private function deleteFile(string $relativePath): void
    {
        $fullPath = $this->projectRoot . $relativePath;

        // Comentado ou removido o unlink para manter os arquivos físicos
        /*
        if (file_exists($fullPath) && is_file($fullPath)) {
            if (!unlink($fullPath)) {
                error_log("AVISO ANUNCIO: deleteFile - Tentativa de deletar arquivo: " . $fullPath . " falhou. (Mantido por nova premissa)");
            } else {
                error_log("DEBUG ANUNCIO: Arquivo deletado: " . $fullPath . " (Esta operação foi desativada, mas o log indica que seria deletado)");
            }
        } else {
            error_log("DEBUG ANUNCIO: Não foi possível deletar arquivo (não existe ou não é um arquivo): " . $fullPath . " (Esta operação foi desativada)");
        }
        */
        error_log("INFO ANUNCIO: deleteFile - Operação de exclusão física de arquivo desativada para: " . $fullPath . ". Arquivo mantido no servidor.");
    }

    /**
     * Deleta todos os registros de mídia de uma tabela de relacionamento para um dado anuncio_id.
     * Usado para limpar registros do DB antes de re-inserir (em updates, por exemplo).
     * @param int $anuncioId O ID do anúncio.
     * @param string $tableName O nome da tabela de relacionamento.
     * @throws Exception Se a exclusão falhar.
     */
    private function deleteMediaFromDb(int $anuncioId, string $tableName): void
    {
        $stmtDelete = null; 
        $queryDelete = "DELETE FROM {$tableName} WHERE anuncio_id = :anuncio_id";
        $stmtDelete = $this->conn->prepare($queryDelete);
        $stmtDelete->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        if (!$stmtDelete->execute()) {
            $errorInfo = $stmtDelete->errorInfo();
            error_log("ERRO ANUNCIO: deleteMediaFromDb - Falha ao deletar registros antigos da tabela '{$tableName}'. Erro PDO: " . $errorInfo[2]);
            throw new Exception("Falha ao deletar registros antigos da tabela '{$tableName}'.");
        }
    }

    /**
     * Pausa ou ativa o anúncio de um usuário.
     * Se o anúncio estiver 'active', muda para 'inactive'.
     * Se o anúncio estiver 'inactive', muda para 'active'.
     * Se o anúncio estiver 'pending' ou 'rejected', não permite a operação.
     * Este método é para a ação do próprio anunciante.
     * @param int $userId O ID do usuário cujo anúncio será pausado/ativado.
     * @return bool True se a operação for bem-sucedida, false caso contrário.
     */
    public function toggleAnuncioStatus(int $userId): bool
    {
        $stmtStatus = null; 
        $stmtUpdate = null; 
        error_log("DEBUG ANUNCIO: toggleAnuncioStatus - Tentando pausar/ativar anúncio para User ID: " . $userId);
        try {
            // 1. Buscar o status atual do anúncio
            $queryStatus = "SELECT id, status FROM anuncios WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1"; // Adicionado filtro de soft delete
            $stmtStatus = $this->conn->prepare($queryStatus);
            $stmtStatus->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmtStatus->execute();
            $anuncio = $stmtStatus->fetch(\PDO::FETCH_ASSOC);

            if (!$anuncio) {
                $this->result = false;
                $this->msg = ['type' => 'error', 'text' => 'Anúncio não encontrado para este usuário.'];
                error_log("ERRO ANUNCIO: toggleAnuncioStatus - Anúncio não encontrado para User ID: " . $userId);
                return false;
            }

            $currentStatus = $anuncio['status'];
            $newStatus = '';
            $message = '';

            if ($currentStatus === 'active') {
                $newStatus = 'inactive';
                $message = 'Anúncio pausado com sucesso!';
                error_log("INFO ANUNCIO: toggleAnuncioStatus - Anúncio de User ID " . $userId . " mudando de 'active' para 'inactive'.");
            } elseif ($currentStatus === 'inactive') {
                $newStatus = 'active';
                $message = 'Anúncio ativado com sucesso!';
                error_log("INFO ANUNCIO: toggleAnuncioStatus - Anúncio de User ID " . $userId . " mudando de 'inactive' para 'active'.");
            } else {
                $this->result = false;
                $this->msg = ['type' => 'info', 'text' => 'O status atual do seu anúncio não permite esta operação. Status: ' . $currentStatus];
                error_log("AVISO ANUNCIO: toggleAnuncioStatus - Operação não permitida para status: " . $currentStatus . " para User ID: " . $userId);
                return false;
            }

            // Inicia a transação para garantir a integridade dos dados
            $this->conn->beginTransaction();

            try {
                // 2. Atualizar o status do anúncio no banco de dados
                $queryUpdate = "UPDATE anuncios SET status = :status, updated_at = NOW() WHERE id = :anuncio_id";
                $stmtUpdate = $this->conn->prepare($queryUpdate);
                $stmtUpdate->bindParam(':status', $newStatus, \PDO::PARAM_STR);
                $stmtUpdate->bindParam(':anuncio_id', $anuncio['id'], \PDO::PARAM_INT);

                if (!$stmtUpdate->execute()) {
                    $errorInfo = $stmtUpdate->errorInfo();
                    $this->result = false;
                    $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar o status do anúncio.'];
                    error_log("ERRO ANUNCIO: toggleAnuncioStatus - Falha ao atualizar status no DB para User ID " . $userId . ". Erro PDO: " . $errorInfo[2]);
                    $this->conn->rollBack();
                    return false;
                }

                // 3. Atualiza o status do anúncio do usuário na tabela `usuarios`
                $hasAnuncio = true; // O anúncio continua existindo, apenas muda de status
                if (!$this->updateUserAnuncioStatus($userId, $newStatus, $hasAnuncio)) {
                    error_log("ERRO ANUNCIO: toggleAnuncioStatus - Falha ao atualizar status do usuário ID " . $userId);
                    $this->result = false;
                    $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar o status do anúncio e do usuário.'];
                    $this->conn->rollBack();
                    return false;
                }

                $this->conn->commit(); // Confirma a transação
                $this->result = true;
                $this->msg = ['type' => 'success', 'text' => $message];
                return true;

            } catch (PDOException $e) {
                $this->conn->rollBack();
                $errorInfo = ($stmtUpdate instanceof \PDOStatement) ? $stmtUpdate->errorInfo() : ['N/A', 'N/A', 'N/A'];
                error_log("ERRO PDO ANUNCIO: toggleAnuncioStatus - Erro PDO na transação: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
                $this->result = false;
                $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao pausar/ativar anúncio.'];
                return false;
            }

        } catch (PDOException $e) {
            $errorInfo = ($stmtStatus instanceof \PDOStatement) ? $stmtStatus->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: toggleAnuncioStatus - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao pausar/ativar anúncio.'];
            return false;
        } catch (Exception $e) {
            error_log("ERRO GERAL ANUNCIO: toggleAnuncioStatus - Erro geral: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao pausar/ativar anúncio.'];
            return false;
        }
    }

    /**
     * Método auxiliar para atualizar o status do anúncio e o flag has_anuncio na tabela `usuarios`.
     * @param int $userId O ID do usuário a ser atualizado.
     * @param string $anuncioStatus O novo status do anúncio para a tabela `usuarios`.
     * @param bool $hasAnuncio O novo valor para `has_anuncio` na tabela `usuarios` (1 ou 0).
     * @return bool True se a atualização for bem-sucedida, false caso contrário.
     */
    private function updateUserAnuncioStatus(int $userId, string $anuncioStatus, bool $hasAnuncio): bool
    {
        $stmtUserUpdate = null;
        try {
            $queryUserUpdate = "UPDATE usuarios SET has_anuncio = :has_anuncio, anuncio_status = :anuncio_status, updated_at = NOW() WHERE id = :user_id";
            $stmtUserUpdate = $this->conn->prepare($queryUserUpdate);
            
            // Alteração aqui: Converte o booleano para int (0 ou 1) explicitamente
            $hasAnuncioInt = $hasAnuncio ? 1 : 0; 
            $stmtUserUpdate->bindParam(':has_anuncio', $hasAnuncioInt, \PDO::PARAM_INT); // Usando PARAM_INT
            
            $stmtUserUpdate->bindParam(':anuncio_status', $anuncioStatus, \PDO::PARAM_STR);
            $stmtUserUpdate->bindParam(':user_id', $userId, \PDO::PARAM_INT);

            if ($stmtUserUpdate->execute()) {
                error_log("DEBUG ANUNCIO: updateUserAnuncioStatus - Usuário ID {$userId} atualizado com has_anuncio: " . ($hasAnuncio ? 'true' : 'false') . ", anuncio_status: {$anuncioStatus}");
                return true;
            } else {
                $errorInfo = $stmtUserUpdate->errorInfo();
                error_log("ERRO ANUNCIO: updateUserAnuncioStatus - Falha ao atualizar has_anuncio/anuncio_status para User ID " . $userId . ". Erro PDO: " . $errorInfo[2]);
                return false;
            }
        } catch (PDOException $e) {
            $errorInfo = ($stmtUserUpdate instanceof \PDOStatement) ? $stmtUserUpdate->errorInfo() : ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: updateUserAnuncioStatus - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return false;
        } catch (Exception $e) {
            error_log("ERRO GERAL ANUNCIO: updateUserAnuncioStatus - Erro geral: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            return false;
        }
    }
}
