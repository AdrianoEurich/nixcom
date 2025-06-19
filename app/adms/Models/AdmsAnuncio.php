<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\Conn; // Sua classe de conexão com o banco de dados
use Adms\CoreAdm\Helpers\Upload; // Uma classe auxiliar para upload de arquivos (será necessário criá-la se não tiver)
use PDOException; // Para tratar erros do PDO

class AdmsAnuncio extends Conn
{
    private object $conn;
    private array $data; // Dados do formulário
    private array $files; // Dados dos arquivos uploaded
    private int $userId; // ID do usuário logado
    private bool $result; // Resultado da operação (sucesso/falha)
    private array $msg; // Mensagens de erro ou sucesso
    private string $uploadDir = 'app/public/uploads/anuncios/'; // Diretório para uploads de anúncios

    public function __construct()
    {
        $this->conn = $this->connectDb();
        // Garante que o diretório de upload existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true); // Permissões 0755 são seguras para diretórios
        }
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

        // 1. Validação inicial dos dados
        if (!$this->validateInput()) {
            $this->result = false;
            return false;
        }

        // 2. Processar campos JSON
        $this->processJsonFields();

        // 3. Processar Upload de Arquivos
        if (!$this->handleFileUploads()) {
            $this->result = false;
            return false;
        }

        // 4. Inserir no banco de dados
        try {
            $query = "INSERT INTO anuncios (
                usuario_id, titulo, descricao_sobre_mim, idade, nacionalidade, 
                estado_uf, cidade_ibge_id, bairro_ibge_id, 
                servicos_json, aparencia_json, idiomas_json, 
                locais_atendimento_json, formas_pagamento_json, 
                preco_15min, preco_30min, preco_1h, 
                foto_capa_path, fotos_galeria_json, videos_json, audios_json, 
                altura_cm, peso_kg, etnia, status, created
            ) VALUES (
                :usuario_id, :titulo, :descricao_sobre_mim, :idade, :nacionalidade, 
                :estado_uf, :cidade_ibge_id, :bairro_ibge_id, 
                :servicos_json, :aparencia_json, :idiomas_json, 
                :locais_atendimento_json, :formas_pagamento_json, 
                :preco_15min, :preco_30min, :preco_1h, 
                :foto_capa_path, :fotos_galeria_json, :videos_json, :audios_json, 
                :altura_cm, :peso_kg, :etnia, :status, NOW()
            )";
            
            $stmt = $this->conn->prepare($query);

            // BIND DOS PARÂMETROS
            $stmt->bindParam(':usuario_id', $this->userId, \PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $this->data['titulo'], \PDO::PARAM_STR);
            $stmt->bindParam(':descricao_sobre_mim', $this->data['descricao_sobre_mim'], \PDO::PARAM_STR);
            $stmt->bindParam(':idade', $this->data['idade'], \PDO::PARAM_INT);
            $stmt->bindParam(':nacionalidade', $this->data['nacionalidade'], \PDO::PARAM_STR);
            $stmt->bindParam(':estado_uf', $this->data['state_id'], \PDO::PARAM_STR);
            $stmt->bindParam(':cidade_ibge_id', $this->data['city_id'], \PDO::PARAM_INT);
            $stmt->bindParam(':bairro_ibge_id', $this->data['neighborhood_id'], \PDO::PARAM_INT);

            // Campos JSON (já processados)
            $stmt->bindParam(':servicos_json', $this->data['servicos_json'], \PDO::PARAM_STR);
            $stmt->bindParam(':aparencia_json', $this->data['aparencia_json'], \PDO::PARAM_STR);
            $stmt->bindParam(':idiomas_json', $this->data['idiomas_json'], \PDO::PARAM_STR);
            $stmt->bindParam(':locais_atendimento_json', $this->data['locais_atendimento_json'], \PDO::PARAM_STR);
            $stmt->bindParam(':formas_pagamento_json', $this->data['formas_pagamento_json'], \PDO::PARAM_STR);

            // Preços (podem ser NULL)
            $stmt->bindParam(':preco_15min', $this->data['precos_15min'], \PDO::PARAM_STR);
            $stmt->bindParam(':preco_30min', $this->data['precos_30min'], \PDO::PARAM_STR);
            $stmt->bindParam(':preco_1h', $this->data['precos_1h'], \PDO::PARAM_STR);

            // Caminhos de Mídia (já processados)
            $stmt->bindParam(':foto_capa_path', $this->data['foto_capa_path'], \PDO::PARAM_STR);
            $stmt->bindParam(':fotos_galeria_json', $this->data['fotos_galeria_json'], \PDO::PARAM_STR);
            $stmt->bindParam(':videos_json', $this->data['videos_json'], \PDO::PARAM_STR);
            $stmt->bindParam(':audios_json', $this->data['audios_json'], \PDO::PARAM_STR);

            // Campos de Aparência
            $stmt->bindParam(':altura_cm', $this->data['altura'], \PDO::PARAM_INT);
            $stmt->bindParam(':peso_kg', $this->data['peso'], \PDO::PARAM_STR);
            $stmt->bindParam(':etnia', $this->data['etnia'], \PDO::PARAM_STR);
            
            $status = 'aguardando_aprovacao'; // Define o status inicial
            $stmt->bindParam(':status', $status, \PDO::PARAM_STR);

            $stmt->execute();

            $this->result = true;
            $this->msg = ['type' => 'success', 'text' => 'Anúncio criado com sucesso e aguardando aprovação!'];
            return true;

        } catch (PDOException $e) {
            // Em ambiente de produção, logue o erro e não exiba ao usuário
            // echo "ERRO: " . $e->getMessage();
            $this->result = false;
            $this->msg = ['type' => 'error', 'text' => 'Erro ao salvar anúncio no banco de dados.'];
            return false;
        }
    }

    /**
     * Valida os campos obrigatórios e formata dados antes da inserção.
     * @return bool True se a validação for bem-sucedida, false caso contrário.
     */
    private function validateInput(): bool
    {
        // Campos de texto/número/select
        $requiredFields = [
            'titulo', 'descricao_sobre_mim', 'idade', 'nacionalidade',
            'state_id', 'city_id', 'neighborhood_id'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->data[$field])) {
                $this->msg = ['type' => 'error', 'text' => 'O campo ' . $field . ' é obrigatório.'];
                // Para exibir um erro específico no frontend, você pode adicionar 'errors' array
                $this->msg['errors'][$field] = 'Este campo é obrigatório.';
                return false;
            }
        }
        
        // Validação de Idade
        if (!filter_var($this->data['idade'], FILTER_VALIDATE_INT, array("options" => array("min_range"=>18, "max_range"=>99)))) {
            $this->msg = ['type' => 'error', 'text' => 'A idade deve ser um número entre 18 e 99.'];
            $this->msg['errors']['idade'] = 'Idade inválida.';
            return false;
        }

        // Validação de Preços (pelo menos um deve ser preenchido)
        $this->data['precos_15min'] = filter_var($this->data['precos']['15min'] ?? '', FILTER_VALIDATE_FLOAT);
        $this->data['precos_30min'] = filter_var($this->data['precos']['30min'] ?? '', FILTER_VALIDATE_FLOAT);
        $this->data['precos_1h'] = filter_var($this->data['precos']['1h'] ?? '', FILTER_VALIDATE_FLOAT);

        if (empty($this->data['precos_15min']) && empty($this->data['precos_30min']) && empty($this->data['precos_1h'])) {
            $this->msg = ['type' => 'error', 'text' => 'Pelo menos um preço deve ser preenchido.'];
            $this->msg['errors']['preco_15min'] = 'Preencha pelo menos um preço.'; // Indica erro no primeiro campo de preço
            return false;
        }
        
        // Validação de checkboxes (mínimo de itens selecionados)
        $checkboxGroups = [
            'servicos' => ['min' => 2, 'msg' => 'Selecione pelo menos 2 serviços.'],
            'aparencia' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 item de aparência.'],
            'idiomas' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 idioma.'],
            'locais_atendimento' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 local de atendimento.'],
            'formas_pagamento' => ['min' => 1, 'msg' => 'Selecione pelo menos 1 forma de pagamento.']
        ];

        foreach ($checkboxGroups as $groupName => $rules) {
            // Garante que o array existe, mesmo que vazio
            $this->data[$groupName] = $this->data[$groupName] ?? []; 
            if (count($this->data[$groupName]) < $rules['min']) {
                $this->msg = ['type' => 'error', 'text' => $rules['msg']];
                $this->msg['errors'][$groupName] = $rules['msg']; // Associa o erro ao grupo
                return false;
            }
        }
        
        return true;
    }

    /**
     * Converte arrays de dados para strings JSON.
     */
    private function processJsonFields(): void
    {
        $jsonFields = [
            'servicos', 'aparencia', 'idiomas', 'locais_atendimento', 'formas_pagamento'
        ];

        foreach ($jsonFields as $field) {
            $this->data[$field . '_json'] = json_encode($this->data[$field] ?? []);
        }
    }

    /**
     * Lida com o upload de arquivos.
     * @return bool True se todos os uploads forem bem-sucedidos ou não houver, false caso contrário.
     */
    private function handleFileUploads(): bool
    {
        $upload = new Upload(); // Instancia sua classe de upload

        // Foto de Capa
        $this->data['foto_capa_path'] = 'anuncio_default.png'; // Valor padrão
        if (isset($this->files['foto_capa']) && $this->files['foto_capa']['error'] === UPLOAD_ERR_OK) {
            $uploadedCapaPath = $upload->uploadFile($this->files['foto_capa'], $this->uploadDir);
            if ($uploadedCapaPath) {
                $this->data['foto_capa_path'] = $uploadedCapaPath;
            } else {
                $this->msg = ['type' => 'error', 'text' => 'Erro ao fazer upload da foto de capa.'];
                $this->msg['errors']['foto_capa'] = 'Erro no upload da foto de capa.';
                return false;
            }
        } else {
             $this->msg = ['type' => 'error', 'text' => 'A foto de capa é obrigatória.'];
             $this->msg['errors']['foto_capa'] = 'Foto de capa é obrigatória.';
             return false;
        }

        // Fotos da Galeria
        $galleryPaths = [];
        if (isset($this->files['fotos_galeria']) && is_array($this->files['fotos_galeria']['name'])) {
            $totalFiles = count($this->files['fotos_galeria']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                // Prepara cada arquivo individualmente para a função de upload
                $currentFile = [
                    'name' => $this->files['fotos_galeria']['name'][$i],
                    'type' => $this->files['fotos_galeria']['type'][$i],
                    'tmp_name' => $this->files['fotos_galeria']['tmp_name'][$i],
                    'error' => $this->files['fotos_galeria']['error'][$i],
                    'size' => $this->files['fotos_galeria']['size'][$i],
                ];

                if ($currentFile['error'] === UPLOAD_ERR_OK) {
                    $uploadedPath = $upload->uploadFile($currentFile, $this->uploadDir);
                    if ($uploadedPath) {
                        $galleryPaths[] = $uploadedPath;
                    } else {
                        // Opcional: registrar erro para um arquivo específico na galeria
                        // $this->msg[] = ['type' => 'warning', 'text' => 'Erro ao fazer upload de uma foto da galeria.'];
                    }
                }
            }
        }
        $this->data['fotos_galeria_json'] = json_encode($galleryPaths);

        // Videos (similar a fotos da galeria, mas verifica se é um plano pago)
        // ... (implementar lógica similar para videos_json, verificando se o usuário tem permissão/plano)
        $videoPaths = [];
        if (isset($this->files['videos']) && is_array($this->files['videos']['name'])) {
            // Lógica de upload de vídeos aqui
            // ...
        }
        $this->data['videos_json'] = json_encode($videoPaths);

        // Audios (similar a videos)
        // ... (implementar lógica similar para audios_json)
        $audioPaths = [];
        if (isset($this->files['audios']) && is_array($this->files['audios']['name'])) {
             // Lógica de upload de áudios aqui
            // ...
        }
        $this->data['audios_json'] = json_encode($audioPaths);

        return true;
    }
}