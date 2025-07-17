<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn; 
use PDOException;
use Adms\CoreAdm\Helpers\Upload; // Sua classe de Upload

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
            // CORREÇÃO AQUI: Apenas uma chamada a file_get_contents()
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
                user_id, state_uf, city_code, neighborhood_name, age, height_m, weight_kg,
                nationality, ethnicity, eye_color, description, price_15min, price_30min, price_1h,
                cover_photo_path, confirmation_video_path, plan_type, status, created_at
            ) VALUES (
                :user_id, :state_uf, :city_code, :neighborhood_name, :age, :height_m, :weight_kg,
                :nationality, :ethnicity, :eye_color, :description, :price_15min, :price_30min, :price_1h,
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
            $stmtAnuncio->bindParam(':nationality', $this->data['nacionalidade'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':ethnicity', $this->data['etnia'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':eye_color', $this->data['cor_olhos'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':description', $this->data['descricao_sobre_mim'], \PDO::PARAM_STR);
            
            // Preços (trata valores vazios como NULL)
            $price15 = !empty($this->data['precos']['15min']) ? str_replace(',', '.', $this->data['precos']['15min']) : null;
            $price30 = !empty($this->data['precos']['30min']) ? str_replace(',', '.', $this->data['precos']['30min']) : null;
            $price1h = !empty($this->data['precos']['1h']) ? str_replace(',', '.', $this->data['precos']['1h']) : null;

            $stmtAnuncio->bindParam(':price_15min', $price15, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_30min', $price30, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_1h', $price1h, \PDO::PARAM_STR);

            $stmtAnuncio->bindParam(':cover_photo_path', $this->data['cover_photo_path'], \PDO::PARAM_STR);
            // NOVO: Bind do caminho do vídeo de confirmação
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
            $this->insertRelatedData($anuncioId, 'anuncio_servicos_oferecidos', $this->data['servicos'] ?? [], 'servico_name');

            // 7. Processar e Inserir Mídias da Galeria (Fotos, Vídeos, Áudios)
            if (!$this->handleGalleryUploads($anuncioId)) {
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }

            $this->conn->commit(); 
            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio criado com sucesso e aguardando aprovação!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack(); 
            error_log("ERRO PDO ANUNCIO: Falha na transação de criação. Rollback. Mensagem: " . $e->getMessage() . " - SQL: " . $queryAnuncio . " - Dados: " . print_r($this->data, true)); 
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
        
        error_log("DEBUG CONTROLLER ANUNCIO: updateAnuncio iniciado para Anúncio ID: " . $anuncioId . ", User ID: " . $this->userId);

        // 1. Obter o tipo de plano do usuário
        if (!$this->getUserPlanType($this->userId)) { // Passa userId para o método
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
        $this->existingAnuncio = $existingAnuncio; // Define para uso na validação

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
                // Tenta fazer upload da nova foto
                $uploadedPath = $upload->uploadFile($this->files['foto_capa'], $this->projectRoot . $this->uploadDir . 'capas/');
                if (!$uploadedPath) {
                    $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload da nova foto de capa: ' . $upload->getMsg()['text']];
                    $this->conn->rollBack();
                    $this->result = false;
                    return false;
                }
                // Se o upload da nova foto foi bem-sucedido, exclui a antiga
                if (!empty($existingAnuncio['cover_photo_path'])) {
                    // Remove o prefixo URL para obter o caminho relativo ao projeto para exclusão
                    $this->deleteFile(str_replace(URL, '', $existingAnuncio['cover_photo_path']));
                }
                // Salva o caminho relativo à raiz do projeto no DB
                $newCapaPath = $this->uploadDir . 'capas/' . basename($uploadedPath); 
            } else if (isset($this->data['cover_photo_removed']) && $this->data['cover_photo_removed'] === 'true') {
                // Se o input de foto_capa está vazio E um sinalizador de remoção foi enviado (significa que foi removida)
                if (!empty($existingAnuncio['cover_photo_path'])) {
                    $this->deleteFile(str_replace(URL, '', $existingAnuncio['cover_photo_path']));
                }
                $newCapaPath = null; // Define como NULL no banco de dados
            } else {
                // Nenhuma nova foto enviada, mantém a antiga (se existir)
                // O caminho já vem prefixado com URL do getAnuncioById, então precisamos remover
                $newCapaPath = str_replace(URL, '', $existingAnuncio['cover_photo_path']);
            }
            $this->data['cover_photo_path'] = $newCapaPath;

            // NOVO: 4.1. Processar Upload/Remoção do Vídeo de Confirmação
            $confirmationVideoPath = $this->handleConfirmationVideoUpload($existingAnuncio['confirmation_video_path'] ?? null);
            if ($confirmationVideoPath === false) { // handleConfirmationVideoUpload retorna false em caso de erro
                $this->conn->rollBack();
                $this->result = false;
                return false;
            }
            $this->data['confirmation_video_path'] = $confirmationVideoPath;


            // 5. Atualizar na tabela principal `anuncios`
            $queryAnuncio = "UPDATE anuncios SET
                state_uf = :state_uf, city_code = :city_code, neighborhood_name = :neighborhood_name,
                age = :age, height_m = :height_m, weight_kg = :weight_kg,
                nationality = :nationality, ethnicity = :ethnicity, eye_color = :eye_color,
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
            $stmtAnuncio->bindParam(':nationality', $this->data['nacionalidade'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':ethnicity', $this->data['etnia'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':eye_color', $this->data['cor_olhos'], \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':description', $this->data['descricao_sobre_mim'], \PDO::PARAM_STR);
            
            // Preços (trata valores vazios como NULL)
            $price15 = !empty($this->data['precos']['15min']) ? str_replace(',', '.', $this->data['precos']['15min']) : null;
            $price30 = !empty($this->data['precos']['30min']) ? str_replace(',', '.', $this->data['precos']['30min']) : null;
            $price1h = !empty($this->data['precos']['1h']) ? str_replace(',', '.', $this->data['precos']['1h']) : null;

            $stmtAnuncio->bindParam(':price_15min', $price15, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_30min', $price30, \PDO::PARAM_STR);
            $stmtAnuncio->bindParam(':price_1h', $price1h, \PDO::PARAM_STR);

            $stmtAnuncio->bindParam(':cover_photo_path', $this->data['cover_photo_path'], \PDO::PARAM_STR);
            // NOVO: Bind do caminho do vídeo de confirmação
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

            // Get paths of existing gallery photos that the user wants to keep
            // These come from hidden inputs named 'existing_gallery_paths[]'
            // Need to convert them back from full URL to relative path for DB operations
            $keptGalleryPaths = array_map(function($path) {
                return str_replace(URL, '', $path);
            }, $this->data['existing_gallery_paths'] ?? []);
            error_log("DEBUG ANUNCIO: updateAnuncio - Kept Gallery Paths (relative): " . print_r($keptGalleryPaths, true));

            // Get paths of existing video paths that the user wants to keep
            $keptVideoPaths = array_map(function($path) {
                return str_replace(URL, '', $path);
            }, $this->data['existing_video_paths'] ?? []);
            error_log("DEBUG ANUNCIO: updateAnuncio - Kept Video Paths (relative): " . print_r($keptVideoPaths, true));

            // Get paths of existing audio paths that the user wants to keep
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
            $this->msg = ['type' => 'success', 'text' => 'Anúncio atualizado com sucesso e aguardando aprovação!'];
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack(); 
            error_log("ERRO PDO ANUNCIO: Falha na transação de atualização. Rollback. Mensagem: " . $e->getMessage() . " - SQL: " . $queryAnuncio . " - Dados: " . print_r($this->data, true)); 
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
                        a.nationality, a.ethnicity, a.eye_color, a.description, a.price_15min, a.price_30min, a.price_1h,
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
                error_log("DEBUG ANUNCIO: getAnuncioById - Status do BD: " . ($anuncio['status'] ?? 'NÃO ENCONTRADO NO BD')); // NOVO LOG

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
                $anuncio['price_15min'] = $anuncio['price_15min'] ? number_format((float)$anuncio['price_15min'], 2, ',', '') : '';
                $anuncio['price_30min'] = $anuncio['price_30min'] ? number_format((float)$anuncio['price_30min'], 2, ',', '') : '';
                $anuncio['price_1h'] = $anuncio['price_1h'] ? number_format((float)$anuncio['price_1h'], 2, ',', '') : '';
                
                // Formatar altura e peso para o frontend (com vírgula)
                $anuncio['height_m'] = $anuncio['height_m'] ? number_format((float)$anuncio['height_m'], 2, ',', '') : '';
                $anuncio['weight_kg'] = $anuncio['weight_kg'] ? (string)(int)$anuncio['weight_kg'] : ''; // Apenas o número inteiro

                // Prefixar o caminho da foto de capa e do vídeo de confirmação com a URL base para o frontend
                if (!empty($anuncio['cover_photo_path'])) {
                    // Assumindo que 'URL' é uma constante global definida como a URL base do seu site
                    $anuncio['cover_photo_path'] = URL . $anuncio['cover_photo_path'];
                }
                // NOVO: Prefixar o caminho do vídeo de confirmação
                if (!empty($anuncio['confirmation_video_path'])) {
                    $anuncio['confirmation_video_path'] = URL . $anuncio['confirmation_video_path'];
                }

                return $anuncio;
            }
            error_log("DEBUG ANUNCIO: getAnuncioById - Nenhum anúncio encontrado para Anúncio ID: " . $anuncioId);
            return null;
        } catch (PDOException $e) {
            error_log("ERRO ANUNCIO: getAnuncioById - Erro PDO: " . $e->getMessage());
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
                        a.nationality, a.ethnicity, a.eye_color, a.description, a.price_15min, a.price_30min, a.price_1h,
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
                error_log("DEBUG ANUNCIO: getAnuncioByUserId - Status do BD: " . ($anuncio['status'] ?? 'NÃO ENCONTRADO NO BD')); // NOVO LOG

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
                $anuncio['price_15min'] = $anuncio['price_15min'] ? number_format((float)$anuncio['price_15min'], 2, ',', '') : '';
                $anuncio['price_30min'] = $anuncio['price_30min'] ? number_format((float)$anuncio['price_30min'], 2, ',', '') : '';
                $anuncio['price_1h'] = $anuncio['price_1h'] ? number_format((float)$anuncio['price_1h'], 2, ',', '') : '';
                
                // Formatar altura e peso para o frontend (com vírgula)
                $anuncio['height_m'] = $anuncio['height_m'] ? number_format((float)$anuncio['height_m'], 2, ',', '') : '';
                $anuncio['weight_kg'] = $anuncio['weight_kg'] ? (string)(int)$anuncio['weight_kg'] : ''; // Apenas o número inteiro

                // Prefixar o caminho da foto de capa e do vídeo de confirmação com a URL base para o frontend
                if (!empty($anuncio['cover_photo_path'])) {
                    // Assumindo que 'URL' é uma constante global definida como a URL base do seu site
                    $anuncio['cover_photo_path'] = URL . $anuncio['cover_photo_path'];
                }
                // NOVO: Prefixar o caminho do vídeo de confirmação
                if (!empty($anuncio['confirmation_video_path'])) {
                    $anuncio['confirmation_video_path'] = URL . $anuncio['confirmation_video_path'];
                }

                return $anuncio;
            }
            error_log("DEBUG ANUNCIO: getAnuncioByUserId - Nenhum anúncio encontrado para User ID: " . $userId);
            return null;
        } catch (PDOException $e) {
            error_log("ERRO ANUNCIO: getAnuncioByUserId - Erro PDO: " . $e->getMessage());
            return null;
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
        $query = "SELECT {$columnName} FROM {$tableName} WHERE anuncio_id = :anuncio_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), $columnName);
    }

    /**
     * Busca caminhos de mídia (fotos, vídeos, áudios).
     * @param int $anuncioId O ID do anúncio.
     * @param string $tableName O nome da tabela de mídia (anuncio_fotos, anuncio_videos, anuncio_audios).
     * @return array Retorna um array de strings com os caminhos dos arquivos.
     */
    private function getMediaPaths(int $anuncioId, string $tableName): array
    {
        $query = "SELECT path FROM {$tableName} WHERE anuncio_id = :anuncio_id ORDER BY order_index ASC"; // order_index apenas para fotos
        if ($tableName !== 'anuncio_fotos') {
             $query = "SELECT path FROM {$tableName} WHERE anuncio_id = :anuncio_id"; // Sem order_index para vídeos/áudios
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        $stmt->execute();
        $paths = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'path');

        // Adiciona o prefixo da URL base para que o frontend possa exibir corretamente
        $prefixedPaths = [];
        foreach ($paths as $path) {
            // Assumindo que 'URL' é uma constante global definida como a URL base do seu site
            $prefixedPaths[] = URL . $path; 
        }
        return $prefixedPaths;
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
            error_log("ERRO ANUNCIO: checkExistingAnuncio - Erro PDO: " . $e->getMessage());
            return false; 
        }
    }

    /**
     * Obtém o tipo de plano do usuário logado.
     * @return bool True se o plano for obtido com sucesso, false caso contrário.
     */
    public function getUserPlanType(int $userId): bool // Tornar public para ser acessível pelo controller
    {
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
            'descricao_sobre_mim', 'etnia', 'cor_olhos'
        ];
        foreach ($fieldsToTrim as $field) {
            $this->data[$field] = trim($this->data[$field] ?? '');
        }
        
        $requiredFields = [
            'state_id', 'city_id', 'neighborhood_id', 'idade', 'altura', 'peso', 'nacionalidade',
            'descricao_sobre_mim'
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
            $this->msg = ['type' => 'error', 'text' => 'A idade deve ser um número entre 18 e 99.'];
            $this->msg['errors']['idade'] = 'Idade inválida.';
            return false;
        }

        // Validação de Altura
        $alturaFloat = (float)str_replace(',', '.', $this->data['altura']);
        if (!is_numeric($alturaFloat) || $alturaFloat <= 0.5 || $alturaFloat > 3.0) { 
            $this->msg = ['type' => 'error', 'text' => 'A altura deve ser um número válido (ex: 1,70).'];
            $this->msg['errors']['altura'] = 'Altura inválida.';
            return false;
        }

        // Validação de Peso
        $pesoInt = (int)$this->data['peso']; 
        if (!is_numeric($pesoInt) || $pesoInt <= 10 || $pesoInt > 500) { 
            $this->msg = ['type' => 'error', 'text' => 'O peso deve ser um número válido (ex: 65).'];
            $this->msg['errors']['peso'] = 'Peso inválido.';
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
        $confirmationVideoFile = $this->files['confirmation_video'] ?? null;
        $confirmationVideoRemoved = ($this->data['confirmation_video_removed'] ?? 'false') === 'true';
        $hasExistingConfirmationVideo = !empty($this->existingAnuncio['confirmation_video_path'] ?? null);

        if ($isUpdateMode) { // Modo de Edição
            if (!$hasExistingConfirmationVideo && ($confirmationVideoFile['error'] === UPLOAD_ERR_NO_FILE || empty($confirmationVideoFile['name']))) {
                // Se não tinha vídeo existente E nenhum novo foi enviado
                $this->msg = ['type' => 'error', 'text' => 'O vídeo de confirmação é obrigatório.'];
                $this->msg['errors']['confirmationVideo-feedback'] = 'O vídeo de confirmação é obrigatório.';
                return false;
            }
            if ($confirmationVideoFile['error'] !== UPLOAD_ERR_NO_FILE && $confirmationVideoFile['error'] !== UPLOAD_ERR_OK) {
                // Se um arquivo foi tentado upload e houve erro
                $this->msg = ['type' => 'error', 'text' => 'Erro no upload do vídeo de confirmação: ' . $confirmationVideoFile['error']];
                $this->msg['errors']['confirmationVideo-feedback'] = 'Erro no upload do vídeo.';
                return false;
            }
        } else { // Modo de Criação
            if ($confirmationVideoFile['error'] !== UPLOAD_ERR_OK || empty($confirmationVideoFile['name'])) {
                $this->msg = ['type' => 'error', 'text' => 'O vídeo de confirmação é obrigatório.'];
                $this->msg['errors']['confirmationVideo-feedback'] = 'O vídeo de confirmação é obrigatório.';
                return false;
            }
        }


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
        // Estes vêm dos inputs hidden 'existing_gallery_paths[]'
        $keptGalleryPathsCount = count($this->data['existing_gallery_paths'] ?? []);
        
        // Total de fotos que estarão no anúncio após a operação (novas + mantidas)
        $currentTotalGalleryPhotos = $keptGalleryPathsCount + $totalNewGalleryFiles;

        // ATUALIZAÇÃO: Limite de 1 foto para plano gratuito
        $freePhotoLimit = 1;
        $minPhotosRequired = 1; // Mínimo de fotos para qualquer plano

        if ($isUpdateMode) {
            // Em modo de edição, a validação considera o total de fotos (novas + mantidas)
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
        } else { // Modo de Criação
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
        } else { // Plano Premium
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
        } else { // Plano Premium
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

        // Fotos da Galeria
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            $totalFiles = count($this->files['fotos_galeria']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($this->files['fotos_galeria']['error'][$i] === UPLOAD_ERR_OK && !empty($this->files['fotos_galeria']['name'][$i])) {
                    $currentFile = [
                        'name' => $this->files['fotos_galeria']['name'][$i],
                        'type' => $this->files['fotos_galeria']['type'][$i],
                        'tmp_name' => $this->files['fotos_galeria']['tmp_name'][$i],
                        'error' => $this->files['fotos_galeria']['error'][$i],
                        'size' => $this->files['fotos_galeria']['size'][$i],
                    ];

                    $uploadedPath = $upload->uploadFile($currentFile, $galleryDir);
                    if ($uploadedPath) {
                        $query = "INSERT INTO anuncio_fotos (anuncio_id, path, order_index, created_at) VALUES (:anuncio_id, :path, :order_index, NOW())";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                        // Salva o caminho relativo à raiz do projeto no DB
                        $dbPath = $this->uploadDir . 'galeria/' . basename($uploadedPath);
                        $stmt->bindParam(':path', $dbPath, \PDO::PARAM_STR);
                        $stmt->bindParam(':order_index', $i, \PDO::PARAM_INT); 
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("ERRO ANUNCIO: handleGalleryUploads - Falha ao inserir foto da galeria no DB para índice {$i}. Erro PDO: " . $errorInfo[2]);
                            throw new \Exception("Falha ao inserir foto da galeria no DB.");
                        }
                    } else {
                        error_log("ERRO ANUNCIO: handleGalleryUploads - Erro no upload da foto de galeria índice {$i}: " . $upload->getMsg()['text']);
                    }
                } else if ($this->files['fotos_galeria']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    error_log("ERRO ANUNCIO: handleGalleryUploads - Erro de upload PHP para foto de galeria índice {$i}: " . $this->files['fotos_galeria']['error'][$i]);
                }
            }
        }

        // Vídeos (apenas se o plano for premium)
        if ($this->userPlanType === 'premium' && isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            $totalFiles = count($this->files['videos']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($this->files['videos']['error'][$i] === UPLOAD_ERR_OK && !empty($this->files['videos']['name'][$i])) {
                    $currentFile = [
                        'name' => $this->files['videos']['name'][$i],
                        'type' => $this->files['videos']['type'][$i],
                        'tmp_name' => $this->files['videos']['tmp_name'][$i],
                        'error' => $this->files['videos']['error'][$i],
                        'size' => $this->files['videos']['size'][$i],
                    ];

                    $uploadedPath = $upload->uploadFile($currentFile, $videosDir);
                    if ($uploadedPath) {
                        $query = "INSERT INTO anuncio_videos (anuncio_id, path, created_at) VALUES (:anuncio_id, :path, NOW())";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
                        // Salva o caminho relativo à raiz do projeto no DB
                        $dbPath = $this->uploadDir . 'videos/' . basename($uploadedPath);
                        $stmt->bindParam(':path', $dbPath, \PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("ERRO ANUNCIO: handleGalleryUploads - Falha ao inserir vídeo no DB para índice {$i}. Erro PDO: " . $errorInfo[2]);
                            throw new \Exception("Falha ao inserir vídeo no DB.");
                        }
                    } else {
                        error_log("ERRO ANUNCIO: handleGalleryUploads - Erro no upload do vídeo índice {$i}: " . $upload->getMsg()['text']);
                        throw new \Exception("Erro no upload de vídeo."); 
                    }
                } else if ($this->files['videos']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    error_log("ERRO ANUNCIO: handleGalleryUploads - Erro de upload PHP para vídeo índice {$i}: " . $this->files['videos']['error'][$i]);
                }
            }
        }
        return true;
    }

    /**
     * Lida com a atualização das mídias da galeria (fotos, vídeos, áudios).
     * Compara mídias existentes com as mantidas e novas para gerenciar exclusões e inserções.
     *
     * @param int $anuncioId O ID do anúncio principal.
     * @param array $existingAnuncio Os dados do anúncio existente, incluindo caminhos de mídia (prefixados com URL).
     * @param array $keptGalleryPaths Caminhos das fotos da galeria que o usuário deseja manter (prefixados com URL).
     * @param array $keptVideoPaths Caminhos dos vídeos que o usuário deseja manter (prefixados com URL).
     * @param array $keptAudioPaths Caminhos dos áudios que o usuário deseja manter (prefixados com URL).
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
        $currentDbGalleryPaths = array_map(function($path) {
            return str_replace(URL, '', $path); // Converte para caminho relativo do DB
        }, $existingAnuncio['fotos_galeria'] ?? []);
        
        $pathsToDelete = array_diff($currentDbGalleryPaths, $keptGalleryPaths);
        $pathsToKeep = array_intersect($currentDbGalleryPaths, $keptGalleryPaths);
        $newUploadedGalleryPaths = [];

        // 1. Deletar fotos que não foram mantidas
        foreach ($pathsToDelete as $path) {
            $this->deleteFile($path); // $path já é relativo ao root do projeto
        }
        $this->deleteMediaFromDb($anuncioId, 'anuncio_fotos'); // Limpa todos os registros de fotos do DB

        // 2. Upload de novas fotos e coleta de caminhos
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            $totalFiles = count($this->files['fotos_galeria']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($this->files['fotos_galeria']['error'][$i] === UPLOAD_ERR_OK && !empty($this->files['fotos_galeria']['name'][$i])) {
                    $currentFile = [
                        'name' => $this->files['fotos_galeria']['name'][$i],
                        'type' => $this->files['fotos_galeria']['type'][$i],
                        'tmp_name' => $this->files['fotos_galeria']['tmp_name'][$i],
                        'error' => $this->files['fotos_galeria']['error'][$i],
                        'size' => $this->files['fotos_galeria']['size'][$i],
                    ];
                    $uploadedPath = $upload->uploadFile($currentFile, $galleryDir);
                    if ($uploadedPath) {
                        $newUploadedGalleryPaths[] = $this->uploadDir . 'galeria/' . basename($uploadedPath);
                    } else {
                        error_log("ERRO ANUNCIO: updateGalleryMedia - Erro no upload de nova foto de galeria: " . $upload->getMsg()['text']);
                        // Não lançar exceção aqui, apenas logar, para permitir outras mídias
                    }
                }
            }
        }

        // 3. Re-inserir todas as fotos (mantidas + novas) na ordem correta
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
        $currentDbVideoPaths = array_map(function($path) {
            return str_replace(URL, '', $path);
        }, $existingAnuncio['videos'] ?? []);

        $pathsToDeleteVideos = array_diff($currentDbVideoPaths, $keptVideoPaths);
        $newUploadedVideoPaths = [];

        // 1. Deletar vídeos que não foram mantidos
        foreach ($pathsToDeleteVideos as $path) {
            $this->deleteFile($path);
        }
        $this->deleteMediaFromDb($anuncioId, 'anuncio_videos'); // Limpa todos os registros de vídeos do DB

        // 2. Upload de novos vídeos e coleta de caminhos
        if ($this->userPlanType === 'premium' && isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            $totalFiles = count($this->files['videos']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($this->files['videos']['error'][$i] === UPLOAD_ERR_OK && !empty($this->files['videos']['name'][$i])) {
                    $currentFile = [
                        'name' => $this->files['videos']['name'][$i],
                        'type' => $this->files['videos']['type'][$i],
                        'tmp_name' => $this->files['videos']['tmp_name'][$i],
                        'error' => $this->files['videos']['error'][$i],
                        'size' => $this->files['videos']['size'][$i],
                    ];
                    $uploadedPath = $upload->uploadFile($currentFile, $videosDir);
                    if ($uploadedPath) {
                        $newUploadedVideoPaths[] = $this->uploadDir . 'videos/' . basename($uploadedPath);
                    } else {
                        error_log("ERRO ANUNCIO: updateGalleryMedia - Erro no upload de novo vídeo: " . $upload->getMsg()['text']);
                        throw new \Exception("Erro no upload de vídeo."); 
                    }
                }
            }
        }
        
        // 3. Re-inserir todos os vídeos (mantidos + novos)
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
        $currentDbAudioPaths = array_map(function($path) {
            return str_replace(URL, '', $path);
        }, $existingAnuncio['audios'] ?? []);

        $pathsToDeleteAudios = array_diff($currentDbAudioPaths, $keptAudioPaths);
        $newUploadedAudioPaths = [];

        // 1. Deletar áudios que não foram mantidos
        foreach ($pathsToDeleteAudios as $path) {
            $this->deleteFile($path);
        }
        $this->deleteMediaFromDb($anuncioId, 'anuncio_audios'); // Limpa todos os registros de áudios do DB

        // 2. Upload de novos áudios e coleta de caminhos
        if ($this->userPlanType === 'premium' && isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
            $totalFiles = count($this->files['audios']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($this->files['audios']['error'][$i] === UPLOAD_ERR_OK && !empty($this->files['audios']['name'][$i])) {
                    $currentFile = [
                        'name' => $this->files['audios']['name'][$i],
                        'type' => $this->files['audios']['type'][$i],
                        'tmp_name' => $this->files['audios']['tmp_name'][$i],
                        'error' => $this->files['audios']['error'][$i],
                        'size' => $this->files['audios']['size'][$i],
                    ];
                    $uploadedPath = $upload->uploadFile($currentFile, $audiosDir);
                    if ($uploadedPath) {
                        $newUploadedAudioPaths[] = $this->uploadDir . 'audios/' . basename($uploadedPath);
                    } else {
                        error_log("ERRO ANUNCIO: updateGalleryMedia - Erro no upload de novo áudio: " . $upload->getMsg()['text']);
                        throw new \Exception("Erro no upload de áudio."); 
                    }
                }
            }
        }

        // 3. Re-inserir todos os áudios (mantidos + novos)
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
     * Lida com o upload e exclusão do vídeo de confirmação do usuário.
     * @param string|null $existingVideoPath O caminho do vídeo existente no DB (com URL prefixada), ou null se não houver.
     * @return string|null|false Retorna o novo caminho do vídeo (relativo ao root do projeto), null se removido, ou false em caso de erro.
     * @throws \Exception Se o upload ou exclusão falhar.
     */
    private function handleConfirmationVideoUpload(?string $existingVideoPath): string|null|false
    {
        $upload = new Upload();
        $confirmationVideoDir = $this->projectRoot . $this->uploadDir . 'confirmation_videos/';
        $newConfirmationVideoFile = $this->files['confirmation_video'] ?? null;
        $confirmationVideoRemoved = ($this->data['confirmation_video_removed'] ?? 'false') === 'true';

        $currentDbPath = null;
        if ($existingVideoPath) {
            $currentDbPath = str_replace(URL, '', $existingVideoPath); // Converte para caminho relativo do DB
        }

        // Caso 1: Novo vídeo enviado
        if ($newConfirmationVideoFile && $newConfirmationVideoFile['error'] === UPLOAD_ERR_OK && !empty($newConfirmationVideoFile['name'])) {
            // Exclui o vídeo antigo se existir
            if ($currentDbPath) {
                $this->deleteFile($currentDbPath);
            }
            // Faz upload do novo vídeo
            $uploadedPath = $upload->uploadFile($newConfirmationVideoFile, $confirmationVideoDir);
            if (!$uploadedPath) {
                $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload do vídeo de confirmação: ' . $upload->getMsg()['text']];
                $this->msg['errors']['confirmationVideo-feedback'] = 'Erro no upload do vídeo.';
                return false;
            }
            return $this->uploadDir . 'confirmation_videos/' . basename($uploadedPath); // Retorna o novo caminho relativo
        } 
        // Caso 2: Vídeo existente marcado para remoção
        else if ($confirmationVideoRemoved) {
            if ($currentDbPath) {
                $this->deleteFile($currentDbPath);
            }
            return null; // Sinaliza para definir o campo no DB como NULL
        } 
        // Caso 3: Nenhum novo vídeo enviado e não marcado para remoção (mantém o existente)
        else {
            return $currentDbPath; // Retorna o caminho existente (relativo)
        }
    }


    /**
     * Deleta um arquivo do sistema de arquivos.
     * @param string $dbFilePath O caminho do arquivo como salvo no banco de dados (e.g., 'app/public/uploads/anuncios/capas/xyz.jpg').
     */
    private function deleteFile(string $dbFilePath): void
    {
        $fullPath = $this->projectRoot . $dbFilePath; // Constrói o caminho absoluto no servidor

        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
            error_log("DEBUG ANUNCIO: Arquivo deletado: " . $fullPath);
        } else {
            error_log("DEBUG ANUNCIO: Não foi possível deletar arquivo (não existe ou não é um arquivo): " . $fullPath);
        }
    }

    /**
     * Deleta registros de mídia de uma tabela do banco de dados.
     * @param int $anuncioId O ID do anúncio.
     * @param string $tableName O nome da tabela de mídia (anuncio_fotos, anuncio_videos, anuncio_audios).
     * @throws \Exception Se a exclusão falhar.
     */
    private function deleteMediaFromDb(int $anuncioId, string $tableName): void
    {
        $queryDelete = "DELETE FROM {$tableName} WHERE anuncio_id = :anuncio_id";
        $stmtDelete = $this->conn->prepare($queryDelete);
        $stmtDelete->bindParam(':anuncio_id', $anuncioId, \PDO::PARAM_INT);
        if (!$stmtDelete->execute()) {
            $errorInfo = $stmtDelete->errorInfo();
            error_log("ERRO ANUNCIO: deleteMediaFromDb - Falha ao deletar mídia antiga da tabela '{$tableName}'. Erro PDO: " . $errorInfo[2]);
            throw new \Exception("Falha ao deletar mídia antiga da tabela '{$tableName}'.");
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

            // Corrigido para usar 'inactive' em vez de 'paused'
            if ($currentStatus === 'active') {
                $newStatus = 'inactive'; // CORRIGIDO AQUI
                $message = 'Anúncio pausado com sucesso!';
                error_log("INFO ANUNCIO: pauseAnuncio - Anúncio de User ID " . $userId . " mudando de 'active' para 'inactive'.");
            } elseif ($currentStatus === 'inactive') { // CORRIGIDO AQUI
                $newStatus = 'active';
                $message = 'Anúncio ativado com sucesso!';
                error_log("INFO ANUNCIO: pauseAnuncio - Anúncio de User ID " . $userId . " mudando de 'inactive' para 'active'.");
            } else {
                // Se o status for 'pending' ou 'rejected', não permite pausar/ativar diretamente
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
            error_log("ERRO PDO ANUNCIO: pauseAnuncio - Erro PDO: " . $e->getMessage());
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
