<?php
// app/adms/Views/anuncio/visualizar_anuncio.php
// Esta view é responsável por exibir os detalhes do anúncio do usuário logado.

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// $this->data['anuncio_data'] deve conter todos os dados do anúncio,
// incluindo os caminhos completos das mídias (já com URL base).
// As variáveis são extraídas do controlador, então podemos acessá-las diretamente.
// Ex: $anuncio_data['id'], $anuncio_data['cover_photo_path'], etc.

// Debugging: Loga os dados do anúncio recebidos na view
error_log("DEBUG VIEW: anuncio_data (visualizar_anuncio.php) recebido. ID: " . ($anuncio_data['id'] ?? 'N/A'));
error_log("DEBUG VIEW: confirmation_video_path (URL): " . ($anuncio_data['confirmation_video_path'] ?? 'Vazio'));
error_log("DEBUG VIEW: cover_photo_path (URL): " . ($anuncio_data['cover_photo_path'] ?? 'Vazio'));
error_log("DEBUG VIEW: fotos_galeria (URLs): " . (json_encode($anuncio_data['fotos_galeria'] ?? []) ?: 'Vazio'));
error_log("DEBUG VIEW: videos (URLs): " . (json_encode($anuncio_data['videos'] ?? []) ?: 'Vazio'));
error_log("DEBUG VIEW: audios (URLs): " . (json_encode($anuncio_data['audios'] ?? []) ?: 'Vazio'));


// Define PATH_ROOT se ainda não estiver definido.
// Esta é uma tentativa de inferir o caminho raiz do projeto no sistema de arquivos.
// É ALTAMENTE RECOMENDADO que PATH_ROOT seja definido globalmente em um arquivo de configuração
// como config.php ou main.php, no topo do seu projeto.
if (!defined('PATH_ROOT')) {
    // Analisa a URL base do projeto para encontrar o subdiretório (ex: 'nixcom')
    $parsed_url = parse_url(URL);
    $path_segment = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : ''; 
    
    if (!empty($path_segment)) {
        // Se a URL tem um subdiretório (ex: http://localhost/nixcom/),
        // o DOCUMENT_ROOT precisa ser estendido com ele para chegar à raiz do projeto.
        // Ex: DOCUMENT_ROOT = C:/xampp/htdocs/, path_segment = nixcom
        // PATH_ROOT = C:/xampp/htdocs/nixcom/
        define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $path_segment . DIRECTORY_SEPARATOR);
    } else {
        // Se a URL não tem subdiretório (ex: http://localhost/),
        // DOCUMENT_ROOT já é a raiz do projeto.
        define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
    }
    error_log("DEBUG VIEW: PATH_ROOT inferido como: " . PATH_ROOT);
}

// Carrega os dados dos estados para mapear UF para nome completo
$states_data = [];
// Caminho absoluto para states.json no sistema de arquivos do servidor
$states_json_filepath = PATH_ROOT . 'app/adms/assets/js/data/states.json';

// Tenta carregar o arquivo JSON. Adicione mais logs para depuração se o problema persistir.
if (file_exists($states_json_filepath)) {
    $states_json_content = file_get_contents($states_json_filepath);
    $decoded_states = json_decode($states_json_content, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded_states['data'])) {
        $states_data = $decoded_states['data'];
        error_log("DEBUG VIEW: states.json carregado com sucesso. Total de estados: " . count($states_data));
    } else {
        error_log("ERRO VIEW: Falha ao decodificar states.json ou estrutura inesperada. Erro: " . json_last_error_msg());
    }
} else {
    error_log("ERRO VIEW: states.json NÃO encontrado em " . $states_json_filepath . ". Verifique o caminho e permissões.");
}


/**
 * Retorna o nome completo do estado a partir da UF.
 * @param string $uf A sigla do estado (ex: "PR").
 * @param array $states_data O array de dados dos estados.
 * @return string O nome completo do estado ou "Não informado" se não encontrado ou UF vazia.
 */
function getStateNameFromUf(string $uf, array $states_data): string
{
    if (empty($uf)) {
        return 'Não informado';
    }
    foreach ($states_data as $state) {
        if (isset($state['Uf']) && $state['Uf'] === $uf && isset($state['Nome'])) {
            return $state['Nome'];
        }
    }
    return 'Não informado';
}


/**
 * Formata um valor monetário para exibição.
 * @param mixed $value O valor a ser formatado.
 * @return string O valor formatado como "R$ X.YY" ou "Não informado".
 */
function formatCurrency($value) {
    if ($value === null || $value === '' || is_nan((float)$value)) {
        return 'Não informado';
    }
    // Garante que o valor seja um float, substituindo vírgula por ponto se necessário
    $floatValue = is_string($value) ? floatval(str_replace(',', '.', $value)) : floatval($value);
    return 'R$ ' . number_format($floatValue, 2, ',', '.');
}

/**
 * Exibe um valor, retornando "Não informado" se estiver vazio.
 * @param mixed $value O valor a ser exibido.
 * @return string O valor formatado ou "Não informado".
 */
function displayValue($value) {
    return !empty($value) ? htmlspecialchars($value) : 'Não informado';
}

/**
 * Exibe uma lista de itens, retornando "Nenhum item selecionado" se estiver vazia.
 * @param array $items O array de itens.
 * @return string A lista formatada ou "Nenhum item selecionado".
 */
function displayList($items) {
    if (empty($items)) {
        return 'Nenhum item selecionado.';
    }
    // Garante que $items é um array antes de implode
    if (!is_array($items)) {
        $items = [$items]; // Converte para array se for string única
    }
    return htmlspecialchars(implode(', ', $items));
}

/**
 * Formata o status do anúncio para um badge Bootstrap.
 * @param string $status O status do anúncio (e.g., 'pending', 'active', 'inactive', 'rejected').
 * @return string O HTML do badge formatado.
 */
function formatStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pendente</span>';
        case 'active':
            return '<span class="badge bg-success">Ativo</span>';
        case 'inactive':
            return '<span class="badge bg-info">Inativo</span>';
        case 'rejected':
            return '<span class="badge bg-danger">Rejeitado</span>';
        default:
            return '<span class="badge bg-secondary">Desconhecido</span>';
    }
}

/**
 * Formata uma string de data/hora para o formato 'DD-MM-YYYY HH:MM:SS'.
 * @param string $datetime A string de data/hora no formato 'YYYY-MM-DD HH:MM:SS'.
 * @return string A data/hora formatada ou "N/A".
 */
function formatDateTime($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    // Cria um objeto DateTime a partir da string de entrada
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    if ($date) {
        // Formata para 'DD-MM-YYYY HH:MM:SS'
        return $date->format('d-m-Y H:i:s');
    }
    return 'N/A'; // Fallback se a análise falhar
}


/**
 * Converte uma URL de mídia para um caminho de sistema de arquivos para file_exists().
 * Assume que a URL base (definida por URL) corresponde à raiz do projeto (definida por PATH_ROOT).
 * @param string $media_url A URL completa da mídia.
 * @return string O caminho absoluto no sistema de arquivos.
 */
function getFileSystemPathFromUrl(string $media_url): string
{
    if (empty($media_url) || !defined('URL') || !defined('PATH_ROOT')) {
        return '';
    }
    // Garante que a URL base termine com '/' para um str_replace limpo
    $base_url_with_slash = rtrim(URL, '/') . '/';
    // Remove a URL base para obter o caminho relativo ao projeto (ex: 'app/public/uploads/...')
    $relativePath = str_replace($base_url_with_slash, '', $media_url);
    // Concatena com a raiz do sistema de arquivos do projeto
    return PATH_ROOT . $relativePath;
}

?>

<!-- Adicionado data-page-type="view" e data-anuncio-id para o JavaScript -->
<div class="card shadow mb-4" data-page-type="view" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id'] ?? '') ?>">
    <div class="card-header py-3 bg-primary text-white">
        <!-- Adicionado id="formAnuncioTitle" para o JavaScript aplicar as cores dinamicamente -->
        <h5 class="m-0" id="formAnuncioTitle"><i class="fas fa-eye me-2"></i>Detalhes do Anúncio</h5>
    </div>
    <div class="card-body p-4">

        <h4 class="mb-4 text-primary">Informações do Anúncio</h4>
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <strong>Nome de trabalho:</strong> <span id="displayServiceName"><?= displayValue($anuncio_data['service_name'] ?? '') ?></span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Status:</strong> <span id="displayStatus"><?= formatStatusBadge($anuncio_data['status'] ?? '') ?></span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Plano:</strong> <span id="displayPlanType"><?= displayValue($anuncio_data['plan_type'] ?? '') ?></span>
            </div>
        </div>

        <h4 class="mb-4 text-primary">Localização</h4>
        <div class="row mb-3">
            <div class="col-md-12 mb-2">
                <strong>Localização:</strong> <span id="displayLocation"><?= displayValue($anuncio_data['neighborhood_name'] ?? '') ?>, <?= displayValue($anuncio_data['city_name'] ?? '') ?> - <?= displayValue(getStateNameFromUf($anuncio_data['state_uf'] ?? '', $states_data)) ?></span>
            </div>
        </div>

        <h4 class="mb-4 text-primary">Informações Pessoais</h4>
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Telefone:</strong> <span id="displayPhoneNumber"><?= displayValue($anuncio_data['phone_number'] ?? '') ?></span>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Idade:</strong> <span id="displayAge"><?= displayValue($anuncio_data['age'] ?? '') ?></span>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Altura:</strong> <span id="displayHeight"><?= displayValue($anuncio_data['height_m'] ?? '') ?> m</span>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Peso:</strong> <span id="displayWeight"><?= displayValue($anuncio_data['weight_kg'] ?? '') ?> kg</span>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Gênero:</strong> <span id="displayGender"><?= displayValue($anuncio_data['gender'] ?? '') ?></span>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Nacionalidade:</strong> <span id="displayNationality"><?= displayValue($anuncio_data['nationality'] ?? '') ?></span>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Etnia:</strong> <span id="displayEthnicity"><?= displayValue($anuncio_data['ethnicity'] ?? '') ?></span>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <strong>Cor dos Olhos:</strong> <span id="displayEyeColor"><?= displayValue($anuncio_data['eye_color'] ?? '') ?></span>
            </div>
        </div>

        <div class="mb-4">
            <strong>Descrição sobre mim:</strong>
            <p id="displayDescription" class="text-break"><?= displayValue($anuncio_data['description'] ?? '') ?></p>
        </div>

        <h4 class="mb-3 text-primary">Sobre Mim (Aparência)</h4>
        <p id="displayAparencia"><?= displayList($anuncio_data['aparencia'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Idiomas</h4>
        <p id="displayIdiomas"><?= displayList($anuncio_data['idiomas'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Local de Atendimento</h4>
        <p id="displayLocais_atendimento"><?= displayList($anuncio_data['locais_atendimento'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Formas de Pagamento</h4>
        <p id="displayFormas_pagamento"><?= displayList($anuncio_data['formas_pagamento'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Serviços Oferecidos</h4>
        <p id="displayServicos"><?= displayList($anuncio_data['servicos'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Preços</h4>
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <strong>15 minutos:</strong> <span id="displayPrice15min"><?= formatCurrency($anuncio_data['price_15min'] ?? 0) ?></span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>30 minutos:</strong> <span id="displayPrice30min"><?= formatCurrency($anuncio_data['price_30min'] ?? 0) ?></span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>1 Hora:</strong> <span id="displayPrice1h"><?= formatCurrency($anuncio_data['price_1h'] ?? 0) ?></span>
            </div>
        </div>

        <h4 class="mb-3 text-primary">Mídia</h4>

        <!-- Seção do Vídeo de Confirmação do Usuário -->
        <div class="mb-4">
            <h5>Vídeo de Confirmação:</h5>
            <?php
            $confirmationVideoUrl = $anuncio_data['confirmation_video_path'] ?? '';
            $confirmationVideoFullPath = getFileSystemPathFromUrl($confirmationVideoUrl);
            error_log("DEBUG VIEW: Confirmation Video URL: " . $confirmationVideoUrl);
            error_log("DEBUG VIEW: Confirmation Video Full Path for file_exists(): " . $confirmationVideoFullPath);
            error_log("DEBUG VIEW: file_exists(Confirmation Video Full Path): " . (file_exists($confirmationVideoFullPath) ? 'true' : 'false'));
            ?>
            <?php if (!empty($confirmationVideoUrl) && file_exists($confirmationVideoFullPath)) : ?>
                <video id="confirmationVideoPlayer" controls class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                    <source src="<?= htmlspecialchars($confirmationVideoUrl) ?>" type="video/mp4">
                    Seu navegador não suporta o elemento de vídeo.
                </video>
            <?php else : ?>
                <p>Nenhum vídeo de confirmação enviado ou encontrado.</p>
                <img src="https://placehold.co/300x200/e0e0e0/555555?text=Sem+V%C3%ADdeo" alt="Placeholder de Vídeo" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
            <?php endif; ?>
        </div>

        <hr class="my-4">

        <!-- Seção da Foto da Capa -->
        <div class="mb-4">
            <h5>Foto da Capa:</h5>
            <?php
            $coverPhotoUrl = $anuncio_data['cover_photo_path'] ?? '';
            $coverPhotoFullPath = getFileSystemPathFromUrl($coverPhotoUrl);
            error_log("DEBUG VIEW: Cover Photo URL: " . $coverPhotoUrl);
            error_log("DEBUG VIEW: Cover Photo Full Path for file_exists(): " . $coverPhotoFullPath);
            error_log("DEBUG VIEW: file_exists(Cover Photo Full Path): " . (file_exists($coverPhotoFullPath) ? 'true' : 'false'));
            ?>
            <?php if (!empty($coverPhotoUrl) && file_exists($coverPhotoFullPath)) : ?>
                <img id="displayCoverPhoto" src="<?= htmlspecialchars($coverPhotoUrl) ?>" alt="Foto de Capa" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
            <?php else : ?>
                <p>Nenhuma foto de capa enviada ou encontrada.</p>
                <img src="https://placehold.co/300x200/e0e0e0/555555?text=Sem+Foto" alt="Placeholder de Foto" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
            <?php endif; ?>
        </div>

        <hr class="my-4">

        <!-- Seção de Fotos da Galeria -->
        <div class="mb-4">
            <h5>Fotos da Galeria:</h5>
            <div id="displayGalleryPhotos" class="row g-2">
                <?php
                $hasValidGalleryPhotos = false;
                if (!empty($anuncio_data['fotos_galeria']) && is_array($anuncio_data['fotos_galeria'])) : ?>
                    <?php foreach ($anuncio_data['fotos_galeria'] as $index => $foto_url) :
                        $fotoFullPath = getFileSystemPathFromUrl($foto_url);
                        error_log("DEBUG VIEW: Gallery Photo {$index} URL: " . $foto_url);
                        error_log("DEBUG VIEW: Gallery Photo {$index} Full Path for file_exists(): " . $fotoFullPath);
                        error_log("DEBUG VIEW: file_exists(Gallery Photo {$index} Full Path): " . (file_exists($fotoFullPath) ? 'true' : 'false'));
                        if (file_exists($fotoFullPath)) :
                            $hasValidGalleryPhotos = true;
                    ?>
                            <div class="col-4 col-md-3 col-lg-2">
                                <img src="<?= htmlspecialchars($foto_url) ?>" alt="Foto da Galeria" class="img-fluid rounded shadow-sm" style="height: 100px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!$hasValidGalleryPhotos) : ?>
                    <p class="text-muted">Nenhuma foto na galeria.</p>
                    <img src="https://placehold.co/300x200/e0e0e0/555555?text=Sem+Fotos" alt="Placeholder de Fotos" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-4">

        <!-- Seção de Vídeos (Galeria) -->
        <div class="mb-4">
            <h5>Vídeos:</h5>
            <div id="displayGalleryVideos" class="row g-2">
                <?php
                $hasValidGalleryVideos = false;
                if (!empty($anuncio_data['videos']) && is_array($anuncio_data['videos'])) : ?>
                    <?php foreach ($anuncio_data['videos'] as $index => $video_url) :
                        $videoFullPath = getFileSystemPathFromUrl($video_url);
                        error_log("DEBUG VIEW: Gallery Video {$index} URL: " . $video_url);
                        error_log("DEBUG VIEW: Gallery Video {$index} Full Path for file_exists(): " . $videoFullPath);
                        error_log("DEBUG VIEW: file_exists(Gallery Video {$index} Full Path): " . (file_exists($videoFullPath) ? 'true' : 'false'));
                        if (file_exists($videoFullPath)) :
                            $hasValidGalleryVideos = true;
                    ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <video controls class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                                    <source src="<?= htmlspecialchars($video_url) ?>" type="video/mp4">
                                    Seu navegador não suporta o elemento de vídeo.
                                </video>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!$hasValidGalleryVideos) : ?>
                    <p class="text-muted">Nenhum vídeo na galeria.</p>
                    <img src="https://placehold.co/300x200/e0e0e0/555555?text=Sem+V%C3%ADdeos" alt="Placeholder de Vídeos" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-4">

        <!-- Seção de Áudios -->
        <div class="mb-4">
            <h5>Áudios:</h5>
            <div id="displayGalleryAudios" class="row g-2">
                <?php
                $hasValidGalleryAudios = false;
                if (!empty($anuncio_data['audios']) && is_array($anuncio_data['audios'])) : ?>
                    <?php foreach ($anuncio_data['audios'] as $index => $audio_url) :
                        $audioFullPath = getFileSystemPathFromUrl($audio_url);
                        error_log("DEBUG VIEW: Gallery Audio {$index} URL: " . $audio_url);
                        error_log("DEBUG VIEW: Gallery Audio {$index} Full Path for file_exists(): " . $audioFullPath);
                        error_log("DEBUG VIEW: file_exists(Gallery Audio {$index} Full Path): " . (file_exists($audioFullPath) ? 'true' : 'false'));
                        if (file_exists($audioFullPath)) :
                            $hasValidGalleryAudios = true;
                    ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <audio controls class="w-100">
                                    <source src="<?= htmlspecialchars($audio_url) ?>" type="audio/mpeg">
                                    Seu navegador não suporta o elemento de áudio.
                                </audio>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!$hasValidGalleryAudios) : ?>
                    <p class="text-muted">Nenhum áudio na galeria.</p>
                    <img src="https://placehold.co/300x200/e0e0e0/555555?text=Sem+%C3%81udios" alt="Placeholder de Áudios" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-4">

        <h4 class="mb-3 text-primary">Detalhes Adicionais</h4>
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <strong>Criado em:</strong> <span id="displayCreatedAt"><?= formatDateTime($anuncio_data['created_at'] ?? '') ?></span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Última Atualização:</strong> <span id="displayUpdatedAt"><?= formatDateTime($anuncio_data['updated_at'] ?? '') ?></span>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Visitas:</strong> <span id="displayVisits"><?= displayValue($anuncio_data['visits'] ?? '0') ?></span>
            </div>
        </div>

    </div>
</div>
