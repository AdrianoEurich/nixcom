<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDOException;
use Adms\CoreAdm\Helpers\Upload;

class AdmsAnuncio extends StsConn
{
    private object $conn;
    private array $data; // Dados do formulário
    private array $files; // Dados dos arquivos uploaded
    private int $userId; // ID do usuário logado
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
        $this->projectRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR; // Ex: C:\xampp\htdocs\nixcom\

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
            if (json_last_error() === JSON_ERROR_NONE && isset($statesRaw['data'])) {
                foreach ($statesRaw['data'] as $state) {
                    $this->statesLookup[$state['Uf']] = $state['Nome'];
                }
            } else {
                error_log("ERRO ANUNCIO: Erro ao decodificar states.json ou formato inválido.");
            }
        } else {
            error_log("ERRO ANUNCIO: states.json não encontrado em " . $statesJsonPath);
        }

        if (file_exists($citiesJsonPath)) {
            $citiesRaw = json_decode(file_get_contents($citiesJsonPath), true);
            if (json_last_error() === JSON_ERROR_NONE && isset($citiesRaw['data'])) {
                foreach ($citiesRaw['data'] as $city) {
                    $this->citiesLookup[$city['Codigo']] = $city['Nome'];
                }
            } else {
                error_log("ERRO ANUNCIO: Erro ao decodificar cities.json ou formato inválido.");
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

        // 1. Obter o tipo de plano do usuário
        if (!$this->getUserPlanType($this->userId)) { // Passa userId para o método
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Não foi possível determinar o plano do usuário.'];
            return false;
        }

        // 2. **NOVA VERIFICAÇÃO**: Checar se o usuário já possui um anúncio
        if ($this->checkExistingAnuncio()) {
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Você já possui um anúncio cadastrado. Um usuário pode ter apenas um anúncio.'];
            $this->msg['errors']['form'] = 'Você já possui um anúncio cadastrado.'; // Erro geral para o formulário
            return false;
        }

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
                $this->msg['errors']['foto_capa_input'] = 'Foto de capa é obrigatória.';
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }

            $upload = new Upload();
            $uploadedCapaPath = $upload->uploadFile($this->files['foto_capa'], $this->projectRoot . $this->uploadDir . 'capas/');
            if (!$uploadedCapaPath) {
                $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload da foto de capa: ' . $upload->getMsg()['text']];
                $this->msg['errors']['foto_capa_input'] = 'Erro no upload da foto de capa.';
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
                user_id, state_uf, city_code, neighborhood_name, age, height_m, weight_kg, gender,
                nationality, ethnicity, eye_color, phone_number, description, price_15min, price_30min, price_1h,
                cover_photo_path, confirmation_video_path, plan_type, status, created_at
            ) VALUES (
                :user_id, :state_uf, :city_code, :neighborhood_name, :age, :height_m, :weight_kg, :gender,
                :nationality, :ethnicity, :eye_color, :phone_number, :description, :price_15min, :price_30min, :price_1h,
                :cover_photo_path, :confirmation_video_path, :plan_type, :status, NOW()
            )";

            $stmtAnuncio = $this->conn->prepare($queryAnuncio);

            // Formata altura e peso para o DB
            $height_m = str_replace(',', '.', $this->data['altura']);
            $weight_kg = (int) $this->data['peso'];

            $stmtAnuncio->bindParam(':user_id', $this->userId, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':state_uf', $this->data['state_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':city_code', $this->data['city_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':neighborhood_name', $this->data['neighborhood_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':age', $this->data['idade'], \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':height_m', $height_m, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':weight_kg', $weight_kg, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':gender', $this->data['gender'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':nationality', $this->data['nacionalidade'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':ethnicity', $this->data['etnia'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':eye_color', $this->data['cor_olhos'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':phone_number', $this->data['phone_number'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':description', $this->data['descricao_sobre_mim'], \PDO::PARAM_STR);

            // Preços (trata valores vazios como NULL)
            $price15 = !empty($this->data['precos']['15min']) ? str_replace(',', '.', $this->data['precos']['15min']) : null;
            $price30 = !empty($this->data['precos']['30min']) ? str_replace(',', '.', $this->data['precos']['30min']) : null;
            $price1h = !empty($this->data['precos']['1h']) ? str_replace(',', '.', $this->data['precos']['1h']) : null;

            $stmtAnuncio->bindParam(':price_15min', $price15, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_30min', $price30, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_1h', $price1h, \PDO::PARAM_STR);

            $stmtAnuncio->bindParam(':cover_photo_path', $this->data['cover_photo_path'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':confirmation_video_path', $this->data['confirmation_video_path'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':plan_type', $this->userPlanType, \PDO::PARAM_STR);
            $status = 'pending';
            $stmtAnuncio->bindParam(':status', $status, \PDO::PARAM_STR);

            $stmtAnuncio->execute();
            $anuncioId = $this->conn->lastInsertId();

            // 6. Inserir em tabelas de relacionamento (checkboxes)
            $this->insertRelatedData($anuncioId, 'anuncio_aparencias', $this->data['aparencia'] ?? [], 'aparencia_item');
            $this->insertRelatedData($anuncioId, 'anuncio_idiomas', $this->data['idiomas'] ?? [], 'idioma_name');
            $this->insertRelatedData($anuncioId, 'anuncio_locais_atendimento', $this->data['locais_atendimento'] ?? [], 'local_name');
            $this->insertRelatedData($anuncioId, 'anuncio_formas_pagamento', $this->data['formas_pagamento'] ?? [], 'forma_name');
            $this->insertRelatedData($anuncioId, 'anuncio_servicos_oferecidos', $this->data['servicos'] ?? [], 'servico_name'); // CORRIGIDO AQUI

            // 7. Processar e Inserir Mídias da Galeria (Fotos, Vídeos, Áudios)
            if (!$this->handleGalleryUploads($anuncioId)) {
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }

            $this->conn->commit();
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio criado com sucesso e aguardando aprovação!', 'anuncio_id' => $anuncioId];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errorInfo = $stmtAnuncio->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: Falha na transação de criação. Rollback. Mensagem: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2] . " - Query: " . ($stmtAnuncio->queryString ?? 'N/A') . " - Dados: " . print_r($this->data, true));
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro ao salvar anúncio no banco de dados. Por favor, tente novamente.'];
            return false;
        } catch (\Exception $e) {
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

        // 1. Obter o tipo de plano do usuário
        if (!$this->getUserPlanType($this->userId)) {
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Não foi possível determinar o plano do usuário.'];
            return false;
        }

        // 2. Obter dados do anúncio existente para gerenciar mídias antigas e validação
        $existingAnuncio = $this->getAnuncioById($anuncioId);
        if (!$existingAnuncio || $existingAnuncio['user_id'] !== $this->userId) {
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Anúncio não encontrado ou você não tem permissão para editá-lo.'];
            return false;
        }
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
                    $this->conn->rollBack();
                    $this->result = false;
                    return false;
                }
                if (!empty($existingAnuncio['cover_photo_path'])) {
                    $this->deleteFile(str_replace(URL, '', $existingAnuncio['cover_photo_path']));
                }
                $newCapaPath = $this->uploadDir . 'capas/' . basename($uploadedPath);
            } else if (isset($this->data['cover_photo_removed']) && $this->data['cover_photo_removed'] === 'true') {
                if (!empty($existingAnuncio['cover_photo_path'])) {
                    $this->deleteFile(str_replace(URL, '', $existingAnuncio['cover_photo_path']));
                }
                $newCapaPath = null;
            } else {
                $newCapaPath = str_replace(URL, '', $existingAnuncio['cover_photo_path']);
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
                state_uf = :state_uf, city_code = :city_code, neighborhood_name = :neighborhood_name,
                age = :age, height_m = :height_m, weight_kg = :weight_kg, gender = :gender,
                nationality = :nationality, ethnicity = :ethnicity, eye_color = :eye_color, phone_number = :phone_number,
                description = :description, price_15min = :price_15min, price_30min = :price_30min, price_1h = :price_1h,
                cover_photo_path = :cover_photo_path, confirmation_video_path = :confirmation_video_path, plan_type = :plan_type, status = :status, updated_at = NOW()
            WHERE id = :anuncio_id AND user_id = :user_id";

            $stmtAnuncio = $this->conn->prepare($queryAnuncio);

            // Formata altura e peso para o DB
            $height_m = str_replace(',', '.', $this->data['altura']);
            $weight_kg = (int) $this->data['peso'];

            $stmtAnuncio->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':user_id', $this->userId, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':state_uf', $this->data['state_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':city_code', $this->data['city_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':neighborhood_name', $this->data['neighborhood_id'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':age', $this->data['idade'], \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':height_m', $height_m, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':weight_kg', $weight_kg, \PDO::PARAM_INT);
            $stmtAnuncio->bindParam(':gender', $this->data['gender'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':nationality', $this->data['nacionalidade'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':ethnicity', $this->data['etnia'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':eye_color', $this->data['cor_olhos'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':phone_number', $this->data['phone_number'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':description', $this->data['descricao_sobre_mim'], \PDO::PARAM_STR);

            // Preços (trata valores vazios como NULL)
            $price15 = !empty($this->data['precos']['15min']) ? str_replace(',', '.', $this->data['precos']['15min']) : null;
            $price30 = !empty($this->data['precos']['30min']) ? str_replace(',', '.', $this->data['precos']['30min']) : null;
            $price1h = !empty($this->data['precos']['1h']) ? str_replace(',', '.', $this->data['precos']['1h']) : null;

            $stmtAnuncio->bindParam(':price_15min', $price15, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_30min', $price30, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_1h', $price1h, \PDO::PARAM_STR);

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
            $this->updateRelatedData($anuncioId, 'anuncio_servicos_oferecidos', $this->data['servicos'] ?? [], 'servico_name'); // CORRIGIDO AQUI

            $keptGalleryPaths = array_map(function($path) {
                return str_replace(URL, '', $path);
            }, $this->data['existing_gallery_paths'] ?? []);
            error_log("DEBUG ANUNCIO: updateAnuncio - Kept Gallery Paths (relative): " . print_r($keptGalleryPaths, true));

            $keptVideoPaths = array_map(function($path) {
                return str_replace(URL, '', $path);
            }, $this->data['existing_video_paths'] ?? []);
            error_log("DEBUG ANUNCIO: updateAnuncio - Kept Video Paths (relative): " . print_r($keptVideoPaths, true));

            $keptAudioPaths = array_map(function($path) {
                return str_replace(URL, '', $path);
            }, $this->data['existing_audio_paths'] ?? []);
            error_log("DEBUG ANUNCIO: updateAnuncio - Kept Audio Paths (relative): " . print_r($keptAudioPaths, true));


            // 7. Processar e Atualizar Mídias da Galeria (Fotos, Vídeos, Áudios)
            if (!$this->updateGalleryMedia($anuncioId, $existingAnuncio, $keptGalleryPaths, $keptVideoPaths, $keptAudioPaths)) {
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }

            $this->conn->commit();
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio atualizado com sucesso e aguardando aprovação!', 'anuncio_id' => $anuncioId];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errorInfo = $stmtAnuncio->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: Falha na transação de atualização. Rollback. Mensagem: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2] . " - Query: " . ($stmtAnuncio->queryString ?? 'N/A') . " - Dados: " . print_r($this->data, true));
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar anúncio no banco de dados. Por favor, tente novamente.'];
            return false;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL ANUNCIO: Falha na transação de atualização. Rollback. Mensagem: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao atualizar o anúncio.'];
            return false;
        }
    }

    /**
     * Atualiza o status de um anúncio.
     * @param int $anuncioId O ID do anúncio a ser atualizado.
     * @param string $newStatus O novo status ('active', 'inactive', 'pending', 'rejected').
     * @return bool True se a atualização for bem-sucedida, false caso contrário.
     */
    public function updateAnuncioStatus(int $anuncioId, string $newStatus): bool
    {
        try {
            $query = "UPDATE anuncios SET status = :status, updated_at = NOW() WHERE id = :anuncio_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $newStatus, \PDO::PARAM_STR);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->result = true;
                $this->msg = ['type' => 'success', 'text' => 'Status do anúncio atualizado com sucesso!'];
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("ERRO ANUNCIO: updateAnuncioStatus - Falha ao atualizar status no DB para Anúncio ID " . $anuncioId . ". Erro PDO: " . $errorInfo[2]);
                $this->result = false;
                $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar o status do anúncio.'];
                return false;
            }
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: updateAnuncioStatus - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao atualizar status do anúncio.'];
            return false;
        } catch (\Exception $e) {
            error_log("ERRO GERAL ANUNCIO: updateAnuncioStatus - Erro geral: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao atualizar o status do anúncio.'];
            return false;
        }
    }

    /**
     * Deleta um anúncio e suas mídias associadas do banco de dados e do sistema de arquivos.
     * @param int $anuncioId O ID do anúncio a ser excluído.
     * @return bool True se a exclusão for bem-sucedida, false caso contrário.
     */
    public function deleteAnuncio(int $anuncioId): bool
    {
        $this->conn->beginTransaction();
        try {
            // 1. Obter todos os caminhos de mídia associados ao anúncio
            $mediaPathsToDelete = [];

            // Mídias da galeria (fotos, vídeos, áudios)
            $mediaPathsToDelete = array_merge($mediaPathsToDelete, $this->getMediaPaths($anuncioId, 'anuncio_fotos', false));
            $mediaPathsToDelete = array_merge($mediaPathsToDelete, $this->getMediaPaths($anuncioId, 'anuncio_videos', false));
            $mediaPathsToDelete = array_merge($mediaPathsToDelete, $this->getMediaPaths($anuncioId, 'anuncio_audios', false));

            // Mídias principais (capa e vídeo de confirmação)
            $queryMainMedia = "SELECT cover_photo_path, confirmation_video_path FROM anuncios WHERE id = :anuncio_id LIMIT 1";
            $stmtMainMedia = $this->conn->prepare($queryMainMedia);
            $stmtMainMedia->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmtMainMedia->execute();
            $mainMedia = $stmtMainMedia->fetch(\PDO::FETCH_ASSOC);

            if ($mainMedia) {
                if (!empty($mainMedia['cover_photo_path'])) {
                    $mediaPathsToDelete[] = $mainMedia['cover_photo_path'];
                }
                if (!empty($mainMedia['confirmation_video_path'])) {
                    $mediaPathsToDelete[] = $mainMedia['confirmation_video_path'];
                }
            }
            error_log("DEBUG ANUNCIO: deleteAnuncio - Caminhos de mídia a serem deletados: " . print_r($mediaPathsToDelete, true));


            // 2. Deletar registros das tabelas de relacionamento
            $this->deleteMediaFromDb($anuncioId, 'anuncio_fotos');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_videos');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_audios');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_aparencias');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_idiomas');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_locais_atendimento');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_formas_pagamento');
            $this->deleteMediaFromDb($anuncioId, 'anuncio_servicos_oferecidos'); // CORRIGIDO AQUI

            // 3. Deletar o registro principal do anúncio
            $queryDeleteAnuncio = "DELETE FROM anuncios WHERE id = :anuncio_id";
            $stmtDeleteAnuncio = $this->conn->prepare($queryDeleteAnuncio);
            $stmtDeleteAnuncio->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            if (!$stmtDeleteAnuncio->execute()) {
                $errorInfo = $stmtDeleteAnuncio->errorInfo();
                error_log("ERRO ANUNCIO: deleteAnuncio - Falha ao deletar anúncio principal. Erro PDO: " . $errorInfo[2]);
                throw new \Exception("Falha ao deletar anúncio principal.");
            }

            // 4. Deletar os arquivos do sistema de arquivos
            foreach ($mediaPathsToDelete as $path) {
                $this->deleteFile($path);
            }

            $this->conn->commit();
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio excluído com sucesso!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errorInfo = $stmtDeleteAnuncio->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: Falha na transação de exclusão. Rollback. Mensagem: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao excluir anúncio.'];
            return false;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO GERAL ANUNCIO: Falha na exclusão. Rollback. Mensagem: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao excluir o anúncio.'];
            return false;
        }
    }

    /**
     * Busca um anúncio específico pelo ID do anúncio.
     * Usado internamente para obter dados existentes antes de uma atualização.
     * @param int $anuncioId O ID do anúncio a ser buscado.
     * @return array|null Retorna um array associativo com os dados do anúncio se encontrado, ou null.
     */
    private function getAnuncioById(int $anuncioId): ?array
    {
        error_log("DEBUG ANUNCIO: getAnuncioById - Buscando anúncio para Anúncio ID: " . $anuncioId);
        try {
            $query = "SELECT
                                a.id, a.user_id, a.state_uf, a.city_code, a.neighborhood_name, a.age, a.height_m, a.weight_kg,
                                a.gender, a.nationality, a.ethnicity, a.eye_color, a.phone_number, a.description, a.price_15min, a.price_30min, a.price_1h,
                                a.cover_photo_path, a.confirmation_video_path, a.plan_type, a.status, a.created_at, a.updated_at
                            FROM anuncios AS a
                            WHERE a.id = :anuncio_id LIMIT 1";

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
                $anuncio['servicos'] = $this->getRelatedData($anuncio['id'], 'anuncio_servicos_oferecidos', 'servico_name'); // CORRIGIDO AQUI
                $anuncio['fotos_galeria'] = $this->getMediaPaths($anuncio['id'], 'anuncio_fotos');
                $anuncio['videos'] = $this->getMediaPaths($anuncio['id'], 'anuncio_videos');
                $anuncio['audios'] = $this->getMediaPaths($anuncio['id'], 'anuncio_audios');

                // Mapear UF para nome do estado
                $anuncio['state_name'] = $this->statesLookup[$anuncio['state_uf']] ?? $anuncio['state_uf'];
                // Mapear código da cidade para nome da cidade
                $anuncio['city_name'] = $this->citiesLookup[$anuncio['city_code']] ?? $anuncio['city_code'];

                // Formatar preços para o frontend (com vírgula)
                $anuncio['price_15min'] = $anuncio['price_15min'] ? number_format((float)$anuncio['price_15min'], 2, ',', '') : '';
                $anuncio['price_30min'] = $anuncio['price_30min'] ? number_format((float)$anuncio['price_30min'], 2, ',', '') : '';
                $anuncio['price_1h'] = $anuncio['price_1h'] ? number_format((float)$anuncio['price_1h'], 2, ',', '') : '';

                // Formatar altura e peso para o frontend (com vírgula)
                $anuncio['height_m'] = $anuncio['height_m'] ? number_format((float)$anuncio['height_m'], 2, ',', '') : '';
                $anuncio['weight_kg'] = $anuncio['weight_kg'] ? (string)(int)$anuncio['weight_kg'] : ''; // Apenas o número inteiro

                // Prefixar o caminho da foto de capa e do vídeo de confirmação com a URL base para o frontend
                if (!empty($anuncio['cover_photo_path'])) {
                    $anuncio['cover_photo_path'] = URL . $anuncio['cover_photo_path'];
                }
                if (!empty($anuncio['confirmation_video_path'])) {
                    $anuncio['confirmation_video_path'] = URL . $anuncio['confirmation_video_path'];
                }

                return $anuncio;
            }
            error_log("DEBUG ANUNCIO: getAnuncioById - Nenhum anúncio encontrado para Anúncio ID: " . $anuncioId);
            return null;
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getAnuncioById - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return null;
        }
    }

    /**
     * Busca um anúncio específico pelo ID do usuário.
     * @param int $userId O ID do usuário cujo anúncio será buscado.
     * @return array|null Retorna um array associativo com os dados do anúncio se encontrado, ou null.
     */
    public function getAnuncioByUserId(int $userId): ?array
    {
        error_log("DEBUG ANUNCIO: getAnuncioByUserId - Buscando anúncio para User ID: " . $userId);
        try {
            $query = "SELECT
                                a.id, a.user_id, a.state_uf, a.city_code, a.neighborhood_name, a.age, a.height_m, a.weight_kg,
                                a.gender, a.nationality, a.ethnicity, a.eye_color, a.phone_number, a.description, a.price_15min, a.price_30min, a.price_1h,
                                a.cover_photo_path, a.confirmation_video_path, a.plan_type, a.status, a.created_at, a.updated_at
                            FROM anuncios AS a
                            WHERE a.user_id = :user_id LIMIT 1";

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
                $anuncio['servicos'] = $this->getRelatedData($anuncio['id'], 'anuncio_servicos_oferecidos', 'servico_name'); // CORRIGIDO AQUI
                $anuncio['fotos_galeria'] = $this->getMediaPaths($anuncio['id'], 'anuncio_fotos');
                $anuncio['videos'] = $this->getMediaPaths($anuncio['id'], 'anuncio_videos');
                $anuncio['audios'] = $this->getMediaPaths($anuncio['id'], 'anuncio_audios');

                // Mapear UF para nome do estado
                $anuncio['state_name'] = $this->statesLookup[$anuncio['state_uf']] ?? $anuncio['state_uf'];
                // Mapear código da cidade para nome da cidade
                $anuncio['city_name'] = $this->citiesLookup[$anuncio['city_code']] ?? $anuncio['city_code'];

                // Formatar preços para o frontend (com vírgula)
                $anuncio['price_15min'] = $anuncio['price_15min'] ? number_format((float)$anuncio['price_15min'], 2, ',', '') : '';
                $anuncio['price_30min'] = $anuncio['price_30min'] ? number_format((float)$anuncio['price_30min'], 2, ',', '') : '';
                $anuncio['price_1h'] = $anuncio['price_1h'] ? number_format((float)$anuncio['price_1h'], 2, ',', '') : '';

                // Formatar altura e peso para o frontend (com vírgula)
                $anuncio['height_m'] = $anuncio['height_m'] ? number_format((float)$anuncio['height_m'], 2, ',', '') : '';
                $anuncio['weight_kg'] = $anuncio['weight_kg'] ? (string)(int)$anuncio['weight_kg'] : ''; // Apenas o número inteiro

                // Prefixar o caminho da foto de capa e do vídeo de confirmação com a URL base para o frontend
                if (!empty($anuncio['cover_photo_path'])) {
                    $anuncio['cover_photo_path'] = URL . $anuncio['cover_photo_path'];
                }
                if (!empty($anuncio['confirmation_video_path'])) {
                    $anuncio['confirmation_video_path'] = URL . $anuncio['confirmation_video_path'];
                }

                return $anuncio;
            }
            error_log("DEBUG ANUNCIO: getAnuncioByUserId - Nenhum anúncio encontrado para User ID: " . $userId);
            return null;
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getAnuncioByUserId - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return null;
        }
    }

    /**
     * Busca os últimos anúncios para o dashboard do administrador, com paginação e filtro.
     * @param int $page A página atual.
     * @param int $limit O número de registros por página.
     * @param string $searchTerm Termo de busca para nome/email do anunciante.
     * @param string $filterStatus Status para filtrar ('all', 'active', 'pending', 'rejected', 'paused').
     * @return array Retorna um array de anúncios.
     */
    public function getLatestAnuncios(int $page, int $limit, string $searchTerm = '', string $filterStatus = 'all'): array
    {
        $offset = ($page - 1) * $limit;
        $query = "SELECT
                                a.id, a.status, a.created_at, a.visits, a.gender AS category_gender,
                                u.name AS user_name, u.email AS user_email
                            FROM anuncios AS a
                            JOIN usuarios AS u ON a.user_id = u.id
                            WHERE 1=1";

        $binds = [];

        // Adiciona filtro por termo de busca
        if (!empty($searchTerm)) {
            $query .= " AND (u.name LIKE :search_term OR u.email LIKE :search_term)";
            $binds[':search_term'] = '%' . $searchTerm . '%';
        }

        // Adiciona filtro por status
        if ($filterStatus !== 'all' && in_array($filterStatus, ['active', 'pending', 'rejected', 'inactive'])) {
            $query .= " AND a.status = :status";
            $binds[':status'] = $filterStatus;
        }

        $query .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $anuncios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formata os dados para a view
            foreach ($anuncios as &$anuncio) {
                $anuncio['category'] = $anuncio['category_gender'];
                unset($anuncio['category_gender']);
                $anuncio['visits'] = number_format($anuncio['visits'], 0, ',', '.');
                $anuncio['created_at'] = date('d/m/Y', strtotime($anuncio['created_at']));
            }
            return $anuncios;
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getLatestAnuncios - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return [];
        }
    }

    /**
     * Retorna o total de anúncios para a paginação, com base no termo de busca e status.
     * @param string $searchTerm Termo de busca para nome/email do anunciante.
     * @param string $filterStatus Status para filtrar ('all', 'active', 'pending', 'rejected', 'paused').
     * @return int O total de anúncios.
     */
    public function getTotalAnuncios(string $searchTerm = '', string $filterStatus = 'all'): int
    {
        $query = "SELECT COUNT(a.id) AS total
                    FROM anuncios AS a
                    JOIN usuarios AS u ON a.user_id = u.id
                    WHERE 1=1";

        $binds = [];

        if (!empty($searchTerm)) {
            $query .= " AND (u.name LIKE :search_term OR u.email LIKE :search_term)";
            $binds[':search_term'] = '%' . $searchTerm . '%';
        }

        if ($filterStatus !== 'all' && in_array($filterStatus, ['active', 'pending', 'rejected', 'inactive'])) {
            $query .= " AND a.status = :status";
            $binds[':status'] = $filterStatus;
        }

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int) $result['total'];
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
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
        try {
            $query = "SELECT {$columnName} FROM {$tableName} WHERE anuncio_id = :anuncio_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), $columnName);
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getRelatedData - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
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
        try {
            $query = "SELECT path FROM {$tableName} WHERE anuncio_id = :anuncio_id ORDER BY id ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
            $stmt->execute();
            $paths = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'path');

            if ($prefixWithUrl) {
                $prefixedPaths = [];
                foreach ($paths as $path) {
                    $prefixedPaths[] = URL . $path;
                }
                return $prefixedPaths;
            }
            return $paths;
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getMediaPaths - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return [];
        }
    }

    /**
     * Verifica se o usuário logado já possui um anúncio cadastrado.
     * @return bool True se o usuário já tem um anúncio, false caso contrário.
     */
    private function checkExistingAnuncio(): bool
    {
        try {
            $query = "SELECT id FROM anuncios WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->userId, \PDO::PARAM_INT);
            $stmt->execute();
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: checkExistingAnuncio - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            return false;
        }
    }

    /**
     * Obtém o tipo de plano do usuário logado.
     * @return bool True se o plano for obtido com sucesso, false caso contrário.
     */
    public function getUserPlanType(int $userId): bool
    {
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
            $this->userPlanType = 'free';
            return false;
        } catch (PDOException $e) {
            $errorInfo = $stmtUser->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: getUserPlanType - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->userPlanType = 'free';
            return false;
        }
    }

    /**
     * Valida os campos obrigatórios e formata dados antes da inserção/atualização.
     * @return bool True se a validação for bem-sucedida, false caso contrário.
     */
    private function validateInput(): bool
    {
        // Limpa e valida os campos de texto/número/select
        $fieldsToTrim = [
            'state_id', 'city_id', 'neighborhood_id', 'idade', 'altura', 'peso', 'nacionalidade',
            'descricao_sobre_mim', 'etnia', 'cor_olhos', 'gender', 'phone_number'
        ];
        foreach ($fieldsToTrim as $field) {
            $this->data[$field] = trim($this->data[$field] ?? '');
        }

        $requiredFields = [
            'state_id', 'city_id', 'neighborhood_id', 'idade', 'altura', 'peso', 'nacionalidade',
            'descricao_sobre_mim', 'gender', 'phone_number'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->data[$field])) {
                $this->msg = ['type' => 'error', 'text' => 'O campo ' . $field . ' é obrigatório.'];
                $this->msg['errors'][$field] = 'Este campo é obrigatório.';
                return false;
            }
        }

        // Validação de Idade
        if (!filter_var($this->data['idade'], FILTER_VALIDATE_INT, ["options" => ["min_range"=>18, "max_range"=>99]])) {
            $this->msg = ['type' => 'error', 'text' => 'A idade deve ser um número inteiro entre 18 e 99.'];
            $this->msg['errors']['idade'] = 'Idade inválida.';
            return false;
        }

        // Validação de Altura
        $alturaInput = trim($this->data['altura'] ?? '');
        $alturaFloat = (float)str_replace(',', '.', $alturaInput);

        if (!is_numeric($alturaFloat) || $alturaFloat <= 0.5 || $alturaFloat > 3.0) {
            $this->msg = ['type' => 'error', 'text' => 'A altura deve ser um número válido (ex: 1,70) entre 0,50 e 3,00 metros.'];
            $this->msg['errors']['altura'] = 'Altura inválida.';
            return false;
        }

        // Validação de Peso
        $pesoInput = trim($this->data['peso'] ?? '');
        if (!filter_var($pesoInput, FILTER_VALIDATE_INT, ["options" => ["min_range"=>10, "max_range"=>500]])) {
            $this->msg = ['type' => 'error', 'text' => 'O peso deve ser um número inteiro válido (ex: 65) entre 10 e 500 kg.'];
            $this->msg['errors']['peso'] = 'Peso inválido.';
            return false;
        }

        // Validação de Telefone (formato (XX) XXXXX-XXXX)
        $cleanPhoneNumber = preg_replace('/\D/', '', $this->data['phone_number']);
        if (!preg_match('/^\d{10,11}$/', $cleanPhoneNumber)) {
            $this->msg = ['type' => 'error', 'text' => 'O número de telefone é inválido. Formato esperado: (XX) XXXXX-XXXX.'];
            $this->msg['errors']['phone_number'] = 'Telefone inválido.';
            return false;
        }

        // Validação de Preços (pelo menos um deve ser preenchido)
        $precos = $this->data['precos'] ?? [];
        $price15 = !empty($precos['15min']) ? (float)str_replace(',', '.', $precos['15min']) : 0;
        $price30 = !empty($precos['30min']) ? (float)str_replace(',', '.', $precos['30min']) : 0;
        $price1h = !empty($precos['1h']) ? (float)str_replace(',', '.', $precos['1h']) : 0;

        if ($price15 <= 0 && $price30 <= 0 && $price1h <= 0) {
            $this->msg = ['type' => 'error', 'text' => 'Pelo menos um preço deve ser preenchido com um valor maior que zero.'];
            $this->msg['errors']['precos'] = 'Preencha pelo menos um preço.';
            return false;
        }
        $this->data['precos']['15min'] = $price15 > 0 ? (string) $price15 : null;
        $this->data['precos']['30min'] = $price30 > 0 ? (string) $price30 : null;
        $this->data['precos']['1h'] = $price1h > 0 ? (string) $price1h : null;

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
                $this->msg = ['type' => 'error', 'text' => $rules['msg']];
                $this->msg['errors'][$groupName] = $rules['msg'];
                return false;
            }
        }

        // Validação de Média com base no plano (para criação e atualização)
        $isUpdateMode = ($this->existingAnuncio !== null);

        // Validação do Vídeo de Confirmação
        $confirmationVideoFile = $this->files['confirmation_video'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => ''];
        $confirmationVideoRemoved = ($this->data['confirmation_video_removed'] ?? 'false') === 'true';
        $hasExistingConfirmationVideo = !empty($this->existingAnuncio['confirmation_video_path'] ?? null);

        if ($isUpdateMode) {
            if (!$hasExistingConfirmationVideo && ($confirmationVideoFile['error'] === UPLOAD_ERR_NO_FILE || empty($confirmationVideoFile['name'])) && !$confirmationVideoRemoved) {
                $this->msg = ['type' => 'error', 'text' => 'O vídeo de confirmação é obrigatório.'];
                $this->msg['errors']['confirmationVideo-feedback'] = 'O vídeo de confirmação é obrigatório.';
                return false;
            }
            if ($confirmationVideoFile['error'] !== UPLOAD_ERR_NO_FILE && $confirmationVideoFile['error'] !== UPLOAD_ERR_OK) {
                $this->msg = ['type' => 'error', 'text' => 'Erro no upload do vídeo de confirmação: ' . $confirmationVideoFile['error']];
                $this->msg['errors']['confirmationVideo-feedback'] = 'Erro no upload do vídeo.';
                return false;
            }
        } else {
            if ($confirmationVideoFile['error'] !== UPLOAD_ERR_OK || empty($confirmationVideoFile['name'])) {
                $this->msg = ['type' => 'error', 'text' => 'O vídeo de confirmação é obrigatório.'];
                $this->msg['errors']['confirmationVideo-feedback'] = 'O vídeo de confirmação é obrigatório.';
                return false;
            }
        }

        // --- VALIDAÇÃO DA GALERIA DE FOTOS ---
        // Contagem de novas fotos da galeria
        $totalNewGalleryFiles = 0;
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            foreach ($this->files['fotos_galeria']['error'] as $error) {
                if ($error === UPLOAD_ERR_OK) {
                    $totalNewGalleryFiles++;
                }
            }
        }

        // Contagem de fotos da galeria existentes que o usuário deseja manter
        // O frontend envia 'existing_gallery_paths' apenas para as fotos que *não* foram removidas.
        $keptGalleryPathsCount = count($this->data['existing_gallery_paths'] ?? []);

        // Total de fotos que estarão no anúncio após a operação (novas + mantidas)
        $currentTotalGalleryPhotos = $keptGalleryPathsCount + $totalNewGalleryFiles;

        error_log("DEBUG ANUNCIO: validateInput - userPlanType: " . $this->userPlanType);
        error_log("DEBUG ANUNCIO: validateInput - keptGalleryPathsCount: " . $keptGalleryPathsCount);
        error_log("DEBUG ANUNCIO: validateInput - totalNewGalleryFiles: " . $totalNewGalleryFiles);
        error_log("DEBUG ANUNCIO: validateInput - currentTotalGalleryPhotos (calculated): " . $currentTotalGalleryPhotos);


        $freePhotoLimit = 1;
        $minPhotosRequired = 1; // Mínimo de 1 foto na galeria para qualquer plano

        // Lógica de validação para modo de atualização (edit)
        if ($isUpdateMode) {
            if ($currentTotalGalleryPhotos < $minPhotosRequired) {
                $this->msg = ['type' => 'error', 'text' => 'Você deve manter ou enviar pelo menos ' . $minPhotosRequired . ' foto(s) para a galeria.'];
                $this->msg['errors']['galleryPhotoContainer'] = 'Mínimo de ' . $minPhotosRequired . ' foto(s) na galeria.';
                return false;
            }
            if ($this->userPlanType === 'free' && $currentTotalGalleryPhotos > $freePhotoLimit) {
                $this->msg = ['type' => 'error', 'text' => 'Seu plano gratuito permite apenas ' . $freePhotoLimit . ' foto na galeria.'];
                $this->msg['errors']['galleryPhotoContainer'] = 'Limite de ' . $freePhotoLimit . ' foto para plano gratuito.';
                return false;
            }
            if ($this->userPlanType === 'premium' && $currentTotalGalleryPhotos > 20) {
                $this->msg = ['type' => 'error', 'text' => 'Seu plano premium permite no máximo 20 fotos na galeria.'];
                $this->msg['errors']['galleryPhotoContainer'] = 'Limite de 20 fotos para plano premium.';
                return false;
            }
        } else { // Lógica de validação para modo de criação
            if ($totalNewGalleryFiles < $minPhotosRequired) {
                $this->msg = ['type' => 'error', 'text' => 'Você deve enviar pelo menos ' . $minPhotosRequired . ' foto(s) para a galeria.'];
                $this->msg['errors']['galleryPhotoContainer'] = 'Mínimo de ' . $minPhotosRequired . ' foto(s) na galeria.';
                return false;
            }
            if ($this->userPlanType === 'free' && $totalNewGalleryFiles > $freePhotoLimit) {
                $this->msg = ['type' => 'error', 'text' => 'Seu plano gratuito permite apenas ' . $freePhotoLimit . ' foto na galeria.'];
                $this->msg['errors']['galleryPhotoContainer'] = 'Limite de ' . $freePhotoLimit . ' foto para plano gratuito.';
                return false;
            }
            if ($this->userPlanType === 'premium' && $totalNewGalleryFiles > 20) {
                $this->msg = ['type' => 'error', 'text' => 'Seu plano premium permite no máximo 20 fotos na galeria.'];
                $this->msg['errors']['galleryPhotoContainer'] = 'Limite de 20 fotos para plano premium.';
                return false;
            }
        }

        // Validação de Vídeos
        $totalNewVideoFiles = 0;
        if (isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            foreach ($this->files['videos']['error'] as $error) {
                if ($error === UPLOAD_ERR_OK) {
                    $totalNewVideoFiles++;
                }
            }
        }
        $keptVideoPathsCount = count($this->data['existing_video_paths'] ?? []);
        $currentTotalVideoFiles = $keptVideoPathsCount + $totalNewVideoFiles;


        if ($this->userPlanType === 'free') {
            if ($currentTotalVideoFiles > 0) {
                $this->msg = ['type' => 'error', 'text' => 'Vídeos são permitidos apenas para planos pagos.'];
                $this->msg['errors']['videoUploadBoxes'] = 'Recurso premium.';
                return false;
            }
        } else {
            if ($currentTotalVideoFiles > 3) {
                $this->msg = ['type' => 'error', 'text' => 'Seu plano premium permite no máximo 3 vídeos.'];
                $this->msg['errors']['videoUploadBoxes'] = 'Limite de 3 vídeos.';
                return false;
            }
        }

        // Validação de Áudios
        $totalNewAudioFiles = 0;
        if (isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
            foreach ($this->files['audios']['error'] as $error) {
                if ($error === UPLOAD_ERR_OK) {
                    $totalNewAudioFiles++;
                }
            }
        }
        $keptAudioPathsCount = count($this->data['existing_audio_paths'] ?? []);
        $currentTotalAudioFiles = $keptAudioPathsCount + $totalNewAudioFiles;

        if ($this->userPlanType === 'free') {
            if ($currentTotalAudioFiles > 0) {
                $this->msg = ['type' => 'error', 'text' => 'Áudios são permitidos apenas para planos pagos.'];
                $this->msg['errors']['audioUploadBoxes'] = 'Recurso premium.';
                return false;
            }
        } else {
            if ($currentTotalAudioFiles > 3) {
                $this->msg = ['type' => 'error', 'text' => 'Seu plano premium permite no máximo 3 áudios.'];
                $this->msg['errors']['audioUploadBoxes'] = 'Limite de 3 áudios.';
                return false;
            }
        }

        return true;
    }

    /**
     * Insere dados em tabelas de relacionamento (muitos-para-muitos).
     * @param int $anuncioId O ID do anúncio principal.
     * @param string $tableName O nome da tabela de relacionamento.
     * @param array $items Os itens a serem inseridos (ex: ['Magra', 'Loira']).
     * @param string $columnName O nome da coluna que armazena o item.
     * @throws \Exception Se a inserção falhar.
     */
    private function insertRelatedData(int $anuncioId, string $tableName, array $items, string $columnName): void
    {
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
                throw new \Exception("Falha ao inserir item '{$item}' na tabela '{$tableName}'.");
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
     * @throws \Exception Se a operação falhar.
     */
    private function updateRelatedData(int $anuncioId, string $tableName, array $items, string $columnName): void
    {
        // 1. Deleta todos os registros existentes para este anuncio_id na tabela
        $queryDelete = "DELETE FROM {$tableName} WHERE anuncio_id = :anuncio_id";
        $stmtDelete = $this->conn->prepare($queryDelete);
        $stmtDelete->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        if (!$stmtDelete->execute()) {
            $errorInfo = $stmtDelete->errorInfo();
            error_log("ERRO ANUNCIO: updateRelatedData - Falha ao deletar registros antigos da tabela '{$tableName}'. Erro PDO: " . $errorInfo[2]);
            throw new \Exception("Falha ao deletar registros antigos da tabela '{$tableName}'.");
        }

        // 2. Insere os novos itens (reutiliza a lógica de insertRelatedData)
        $this->insertRelatedData($anuncioId, $tableName, $items, $columnName);
    }

    /**
     * Lida com o upload e inserção de fotos da galeria, vídeos e áudios.
     * Usado na CRIAÇÃO de um anúncio.
     * @param int $anuncioId O ID do anúncio principal.
     * @return bool True se todos os uploads e inserções forem bem-sucedidos, false caso contrário.
     * @throws \Exception Se um upload ou inserção falhar.
     */
    private function handleGalleryUploads(int $anuncioId): bool
    {
        $upload = new Upload();

        // Subdiretórios para diferentes tipos de mídia (já criados no construtor)
        $galleryDir = $this->projectRoot . $this->uploadDir . 'galeria/';
        $videosDir = $this->projectRoot . $this->uploadDir . 'videos/';
        $audiosDir = $this->projectRoot . $this->uploadDir . 'audios/';

        // --- Fotos da Galeria ---
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            $totalUploaded = 0;
            foreach ($this->files['fotos_galeria']['tmp_name'] as $index => $tmpName) {
                if ($this->files['fotos_galeria']['error'][$index] === UPLOAD_ERR_OK && !empty($this->files['fotos_galeria']['name'][$index])) {
                    // Verifica limite de plano
                    if (($this->userPlanType === 'free' && $totalUploaded >= 1) ||
                        ($this->userPlanType === 'premium' && $totalUploaded >= 20)) {
                        error_log("AVISO ANUNCIO: handleGalleryUploads - Limite de fotos excedido para plano atual.");
                        continue;
                    }

                    $file = [
                        'name' => $this->files['fotos_galeria']['name'][$index],
                        'type' => $this->files['fotos_galeria']['type'][$index],
                        'tmp_name' => $tmpName,
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
                        $stmt->bindParam(':order_index', $index, \PDO::PARAM_INT);
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("ERRO ANUNCIO: handleGalleryUploads - Falha ao inserir foto de galeria no DB. Erro PDO: " . $errorInfo[2]);
                            throw new \Exception("Falha ao inserir foto de galeria no banco de dados.");
                        }
                        $totalUploaded++;
                    } else {
                        error_log("ERRO ANUNCIO: handleGalleryUploads - Falha no upload da foto de galeria: " . $upload->getMsg()['text']);
                        throw new \Exception("Falha no upload de uma foto da galeria.");
                    }
                }
            }
        }

        // --- Vídeos da Galeria ---
        if ($this->userPlanType === 'premium' && isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            $totalUploaded = 0;
            foreach ($this->files['videos']['tmp_name'] as $index => $tmpName) {
                if ($this->files['videos']['error'][$index] === UPLOAD_ERR_OK && !empty($this->files['videos']['name'][$index])) {
                    if ($totalUploaded >= 3) {
                        error_log("AVISO ANUNCIO: handleGalleryUploads - Limite de vídeos excedido para plano premium.");
                        continue;
                    }
                    $file = [
                        'name' => $this->files['videos']['name'][$index],
                        'type' => $this->files['videos']['type'][$index],
                        'tmp_name' => $tmpName,
                        'error' => $this->files['videos']['error'][$index],
                        'size' => $this->files['videos']['size'][$index],
                    ];
                    $uploadedPath = $upload->uploadFile($file, $videosDir);
                    if ($uploadedPath) {
                        $relativePath = $this->uploadDir . 'videos/' . basename($uploadedPath);
                        $query = "INSERT INTO anuncio_videos (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                        $stmt->bindParam(':path', $relativePath, \PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("ERRO ANUNCIO: handleGalleryUploads - Falha ao inserir vídeo de galeria no DB. Erro PDO: " . $errorInfo[2]);
                            throw new \Exception("Falha ao inserir vídeo de galeria no banco de dados.");
                        }
                        $totalUploaded++;
                    } else {
                        error_log("ERRO ANUNCIO: handleGalleryUploads - Falha no upload do vídeo de galeria: " . $upload->getMsg()['text']);
                        throw new \Exception("Falha no upload de um vídeo da galeria.");
                    }
                }
            }
        }
        // --- Áudios da Galeria ---
        if ($this->userPlanType === 'premium' && isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
            $totalUploaded = 0;
            foreach ($this->files['audios']['tmp_name'] as $index => $tmpName) {
                if ($this->files['audios']['error'][$index] === UPLOAD_ERR_OK && !empty($this->files['audios']['name'][$index])) {
                    if ($totalUploaded >= 3) {
                        error_log("AVISO ANUNCIO: handleGalleryUploads - Limite de áudios excedido para plano premium.");
                        continue;
                    }
                    $file = [
                        'name' => $this->files['audios']['name'][$index],
                        'type' => $this->files['audios']['type'][$index],
                        'tmp_name' => $tmpName,
                        'error' => $this->files['audios']['error'][$index],
                        'size' => $this->files['audios']['size'][$index],
                    ];
                    $uploadedPath = $upload->uploadFile($file, $audiosDir);
                    if ($uploadedPath) {
                        $relativePath = $this->uploadDir . 'audios/' . basename($uploadedPath);
                        $query = "INSERT INTO anuncio_audios (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                        $stmt->bindParam(':path', $relativePath, \PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("ERRO ANUNCIO: handleGalleryUploads - Falha ao inserir áudio de galeria no DB. Erro PDO: " . $errorInfo[2]);
                            throw new \Exception("Falha ao inserir áudio de galeria no banco de dados.");
                        }
                        $totalUploaded++;
                    } else {
                        error_log("ERRO ANUNCIO: handleGalleryUploads - Falha no upload do áudio de galeria: " . $upload->getMsg()['text']);
                        throw new \Exception("Falha no upload de um áudio da galeria.");
                    }
                }
            }
        }
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

        $currentVideoPath = str_replace(URL, '', $existingVideoPath ?? '');

        if ($confirmationVideoFile['error'] === UPLOAD_ERR_OK && !empty($confirmationVideoFile['name'])) {
            $uploadedPath = $upload->uploadFile($confirmationVideoFile, $this->projectRoot . $this->uploadDir . 'confirmation_videos/');
            if (!$uploadedPath) {
                $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload do vídeo de confirmação: ' . $upload->getMsg()['text']];
                $this->msg['errors']['confirmationVideo-feedback'] = 'Erro no upload do vídeo de confirmação.';
                return false;
            }
            if (!empty($currentVideoPath)) {
                $this->deleteFile($currentVideoPath);
            }
            return $this->uploadDir . 'confirmation_videos/' . basename($uploadedPath);
        }
        else if ($confirmationVideoRemoved) {
            if (!empty($currentVideoPath)) {
                $this->deleteFile($currentVideoPath);
            }
            return null;
        }
        else {
            return $currentVideoPath;
        }
    }

    /**
     * Lida com a atualização das mídias da galeria (fotos, vídeos, áudios).
     * Compara mídias existentes com as mantidas e novas para gerenciar exclusões e inserções.
     *
     * @param int $anuncioId O ID do anúncio principal.
     * @param array $existingAnuncio Os dados do anúncio existente, incluindo caminhos de mídia (prefixados com URL).
     * @param array $keptGalleryPaths Caminhos das fotos da galeria que o usuário deseja manter (relativos ao root do projeto).
     * @param array $keptVideoPaths Caminhos dos vídeos que o usuário deseja manter (relativos ao root do projeto).
     * @param array $keptAudioPaths Caminhos dos áudios que o usuário deseja manter (relativos ao root do projeto).
     * @return bool True se todas as operações forem bem-sucedidas, false caso contrário.
     * @throws \Exception Se uma operação falhar.
     */
    private function updateGalleryMedia(int $anuncioId, array $existingAnuncio, array $keptGalleryPaths, array $keptVideoPaths, array $keptAudioPaths): bool
    {
        $upload = new Upload();
        $galleryDir = $this->projectRoot . $this->uploadDir . 'galeria/';
        $videosDir = $this->projectRoot . $this->uploadDir . 'videos/';
        $audiosDir = $this->projectRoot . $this->uploadDir . 'audios/';

        // --- Processar Fotos da Galeria ---
        $currentDbGalleryPaths = $this->getMediaPaths($anuncioId, 'anuncio_fotos', false);

        $photosToDelete = array_diff($currentDbGalleryPaths, $keptGalleryPaths);
        foreach ($photosToDelete as $path) {
            $this->deleteFile($path);
        }
        $this->deleteMediaFromDb($anuncioId, 'anuncio_fotos');

        $newUploadedGalleryPaths = [];
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            $currentTotalPhotos = count($keptGalleryPaths); // Começa a contagem com as fotos que foram mantidas
            $freePhotoLimit = 1;
            $premiumPhotoLimit = 20;

            foreach ($this->files['fotos_galeria']['tmp_name'] as $index => $tmpName) {
                if ($this->files['fotos_galeria']['error'][$index] === UPLOAD_ERR_OK && !empty($this->files['fotos_galeria']['name'][$index])) {
                    if (($this->userPlanType === 'free' && $currentTotalPhotos >= $freePhotoLimit) ||
                        ($this->userPlanType === 'premium' && $currentTotalPhotos >= $premiumPhotoLimit)) {
                        error_log("AVISO ANUNCIO: updateGalleryMedia - Limite de fotos excedido para o plano atual ao adicionar nova foto.");
                        continue; // Pula o upload e a inserção se o limite for atingido
                    }

                    $file = [
                        'name' => $this->files['fotos_galeria']['name'][$index],
                        'type' => $this->files['fotos_galeria']['type'][$index],
                        'tmp_name' => $tmpName,
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
            $stmt = $this->conn->prepare($query);
            foreach ($allGalleryPaths as $index => $path) {
                $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                $stmt->bindParam(':path', $path, \PDO::PARAM_STR);
                $stmt->bindParam(':order_index', $index, \PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERRO ANUNCIO: updateGalleryMedia - Falha ao re-inserir foto da galeria no DB para índice {$index}. Erro PDO: " . $errorInfo[2]);
                    throw new \Exception("Falha ao re-inserir foto da galeria no DB.");
                }
            }
        }

        // --- Processar Vídeos ---
        $currentDbVideoPaths = $this->getMediaPaths($anuncioId, 'anuncio_videos', false);

        $pathsToDeleteVideos = array_diff($currentDbVideoPaths, $keptVideoPaths);
        foreach ($pathsToDeleteVideos as $path) {
            $this->deleteFile($path);
        }
        $this->deleteMediaFromDb($anuncioId, 'anuncio_videos');

        $newUploadedVideoPaths = [];
        if ($this->userPlanType === 'premium' && isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            $currentTotalVideos = count($keptVideoPaths);
            foreach ($this->files['videos']['tmp_name'] as $index => $tmpName) {
                if ($this->files['videos']['error'][$index] === UPLOAD_ERR_OK && !empty($this->files['videos']['name'][$index])) {
                    if ($currentTotalVideos >= 3) {
                        error_log("AVISO ANUNCIO: updateGalleryMedia - Limite de vídeos excedido para plano premium ao adicionar novo vídeo.");
                        continue;
                    }
                    $file = [
                        'name' => $this->files['videos']['name'][$index],
                        'type' => $this->files['videos']['type'][$index],
                        'tmp_name' => $tmpName,
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
        $allVideoPaths = array_merge($keptVideoPaths, $newUploadedVideoPaths);
        if (!empty($allVideoPaths)) {
            $query = "INSERT INTO anuncio_videos (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
            $stmt = $this->conn->prepare($query);
            foreach ($allVideoPaths as $path) {
                $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                $stmt->bindParam(':path', $path, \PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERRO ANUNCIO: updateGalleryMedia - Falha ao re-inserir vídeo no DB. Erro PDO: " . $errorInfo[2]);
                    throw new \Exception("Falha ao re-inserir vídeo no DB.");
                }
            }
        }

        // --- Processar Áudios ---
        $currentDbAudioPaths = $this->getMediaPaths($anuncioId, 'anuncio_audios', false);

        $pathsToDeleteAudios = array_diff($currentDbAudioPaths, $keptAudioPaths);
        foreach ($pathsToDeleteAudios as $path) {
            $this->deleteFile($path);
        }
        $this->deleteMediaFromDb($anuncioId, 'anuncio_audios');

        $newUploadedAudioPaths = [];
        if ($this->userPlanType === 'premium' && isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
            $currentTotalAudios = count($keptAudioPaths);
            foreach ($this->files['audios']['tmp_name'] as $index => $tmpName) {
                if ($this->files['audios']['error'][$index] === UPLOAD_ERR_OK && !empty($this->files['audios']['name'][$index])) {
                    if ($currentTotalAudios >= 3) {
                        error_log("AVISO ANUNCIO: updateGalleryMedia - Limite de áudios excedido para plano premium ao adicionar novo áudio.");
                        continue;
                    }
                    $file = [
                        'name' => $this->files['audios']['name'][$index],
                        'type' => $this->files['audios']['type'][$index],
                        'tmp_name' => $tmpName,
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

        $allAudioPaths = array_merge($keptAudioPaths, $newUploadedAudioPaths);
        if (!empty($allAudioPaths)) {
            $query = "INSERT INTO anuncio_audios (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
            $stmt = $this->conn->prepare($query);
            foreach ($allAudioPaths as $path) {
                $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                $stmt->bindParam(':path', $path, \PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERRO ANUNCIO: updateGalleryMedia - Falha ao re-inserir áudio no DB. Erro PDO: " . $errorInfo[2]);
                    throw new \Exception("Falha ao re-inserir áudio no DB.");
                }
            }
        }

        return true;
    }

    /**
     * Deleta um arquivo do sistema de arquivos.
     * @param string $relativePath O caminho relativo do arquivo a partir da raiz do projeto.
     */
    private function deleteFile(string $relativePath): void
    {
        $fullPath = $this->projectRoot . $relativePath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            if (!unlink($fullPath)) {
                error_log("ERRO ANUNCIO: deleteFile - Falha ao deletar arquivo: " . $fullPath);
            } else {
                error_log("DEBUG ANUNCIO: Arquivo deletado: " . $fullPath);
            }
        } else {
            error_log("DEBUG ANUNCIO: Não foi possível deletar arquivo (não existe ou não é um arquivo): " . $fullPath);
        }
    }

    /**
     * Deleta todos os registros de mídia de uma tabela de relacionamento para um dado anuncio_id.
     * Usado na exclusão completa do anúncio ou para limpar antes de re-inserir.
     * @param int $anuncioId O ID do anúncio.
     * @param string $tableName O nome da tabela de relacionamento.
     * @throws \Exception Se a exclusão falhar.
     */
    private function deleteMediaFromDb(int $anuncioId, string $tableName): void
    {
        $queryDelete = "DELETE FROM {$tableName} WHERE anuncio_id = :anuncio_id";
        $stmtDelete = $this->conn->prepare($queryDelete);
        $stmtDelete->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        if (!$stmtDelete->execute()) {
            $errorInfo = $stmtDelete->errorInfo();
            error_log("ERRO ANUNCIO: deleteMediaFromDb - Falha ao deletar registros da tabela '{$tableName}'. Erro PDO: " . $errorInfo[2]);
            throw new \Exception("Falha ao deletar registros antigos da tabela '{$tableName}'.");
        }
    }

    /**
     * Pausa ou ativa o anúncio de um usuário.
     * Se o anúncio estiver 'active', muda para 'inactive'.
     * Se o anúncio estiver 'inactive', muda para 'active'.
     * Se o anúncio estiver 'pending' ou 'rejected', não permite a operação.
     * @param int $userId O ID do usuário cujo anúncio será pausado/ativado.
     * @return bool True se a operação for bem-sucedida, false caso contrário.
     */
    public function pauseAnuncio(int $userId): bool
    {
        error_log("DEBUG ANUNCIO: pauseAnuncio - Tentando pausar/ativar anúncio para User ID: " . $userId);
        try {
            // 1. Buscar o status atual do anúncio
            $queryStatus = "SELECT id, status FROM anuncios WHERE user_id = :user_id LIMIT 1";
            $stmtStatus = $this->conn->prepare($queryStatus);
            $stmtStatus->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmtStatus->execute();
            $anuncio = $stmtStatus->fetch(\PDO::FETCH_ASSOC);

            if (!$anuncio) {
                $this->result = false;
                $this->msg = ['type' => 'error', 'text' => 'Anúncio não encontrado para este usuário.'];
                error_log("ERRO ANUNCIO: pauseAnuncio - Anúncio não encontrado para User ID: " . $userId);
                return false;
            }

            $currentStatus = $anuncio['status'];
            $newStatus = '';
            $message = '';

            if ($currentStatus === 'active') {
                $newStatus = 'inactive';
                $message = 'Anúncio pausado com sucesso!';
                error_log("INFO ANUNCIO: pauseAnuncio - Anúncio de User ID " . $userId . " mudando de 'active' para 'inactive'.");
            } elseif ($currentStatus === 'inactive') {
                $newStatus = 'active';
                $message = 'Anúncio ativado com sucesso!';
                error_log("INFO ANUNCIO: pauseAnuncio - Anúncio de User ID " . $userId . " mudando de 'inactive' para 'active'.");
            } else {
                $this->result = false;
                $this->msg = ['type' => 'info', 'text' => 'O status atual do seu anúncio não permite esta operação. Status: ' . $currentStatus];
                error_log("AVISO ANUNCIO: pauseAnuncio - Operação não permitida para status: " . $currentStatus . " para User ID: " . $userId);
                return false;
            }

            // 2. Atualizar o status no banco de dados
            $queryUpdate = "UPDATE anuncios SET status = :status, updated_at = NOW() WHERE id = :anuncio_id";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->bindParam(':status', $newStatus, \PDO::PARAM_STR);
            $stmtUpdate->bindParam(':anuncio_id', $anuncio['id'], \PDO::PARAM_INT);

            if ($stmtUpdate->execute()) {
                $this->result = true;
                $this->msg = ['type' => 'success', 'text' => $message];
                return true;
            } else {
                $errorInfo = $stmtUpdate->errorInfo();
                $this->result = false;
                $this->msg = ['type' => 'error', 'text' => 'Erro ao atualizar o status do anúncio.'];
                error_log("ERRO ANUNCIO: pauseAnuncio - Falha ao atualizar status no DB para User ID " . $userId . ". Erro PDO: " . $errorInfo[2]);
                return false;
            }

        } catch (PDOException $e) {
            $errorInfo = $stmtUpdate->errorInfo() ?? ['N/A', 'N/A', 'N/A'];
            error_log("ERRO PDO ANUNCIO: pauseAnuncio - Erro PDO: " . $e->getMessage() . " - SQLSTATE: " . $errorInfo[0] . " - Código Erro PDO: " . $errorInfo[1] . " - Mensagem Erro PDO: " . $errorInfo[2]);
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro no banco de dados ao pausar/ativar anúncio.'];
            return false;
        } catch (\Exception $e) {
            error_log("ERRO GERAL ANUNCIO: pauseAnuncio - Erro geral: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Ocorreu um erro inesperado ao pausar/ativar anúncio.'];
            return false;
        }
    }
}
