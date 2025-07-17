<?php

/**
 * View para visualizar os detalhes de um anúncio.
 *
 * Esta view exibe todas as informações de um anúncio específico, incluindo
 * localização, dados pessoais, serviços, preços, mídia e aparência.
 *
 * @var array $data Dados do anúncio a ser exibido.
 * Esperado: $data['anuncio_data']
 */
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    exit("Erro: Página não encontrada!");
}

$anuncio_data = $anuncio_data ?? [];

// Helper para exibir um valor ou "Não informado"
function display_value($value)
{
    return !empty($value) ? htmlspecialchars($value) : 'Não informado';
}

// Helper para exibir uma lista de itens ou "Não informado"
function display_list($list)
{
    if (is_array($list) && !empty($list)) {
        return implode(', ', array_map('htmlspecialchars', $list));
    }
    return 'Não informado';
}

// Helper para formatar preços (CORRIGIDO: Cast para float)
function format_price($price)
{
    // Garante que o valor seja tratado como float antes de formatar
    if (!empty($price) && (float)$price > 0) {
        return 'R$ ' . number_format((float)$price, 2, ',', '.');
    }
    return 'Não informado';
}

?>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary text-white">
        <h5 class="m-0"><i class="fas fa-eye me-2"></i>Detalhes do Anúncio</h5>
    </div>
    <div class="card-body p-4">

        <h4 class="mb-4 text-primary">Informações Básicas</h4>
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <strong>Estado:</strong> <?= display_value($anuncio_data['state_uf'] ?? '') ?>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Cidade:</strong> <?= display_value($anuncio_data['city_name'] ?? '') ?>
            </div>
            <div class="col-md-4 mb-2">
                <strong>Bairro:</strong> <?= display_value($anuncio_data['neighborhood_name'] ?? '') ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-2 col-sm-6 mb-2">
                <strong>Idade:</strong> <?= display_value($anuncio_data['age'] ?? '') ?>
            </div>
            <div class="col-md-2 col-sm-6 mb-2">
                <strong>Altura:</strong> <?= display_value(isset($anuncio_data['height_m']) ? str_replace('.', ',', $anuncio_data['height_m']) . ' m' : '') ?>
            </div>
            <div class="col-md-2 col-sm-6 mb-2">
                <strong>Peso:</strong> <?= display_value(isset($anuncio_data['weight_kg']) ? $anuncio_data['weight_kg'] . ' kg' : '') ?>
            </div>
            <div class="col-md-2 col-sm-6 mb-2">
                <strong>Nacionalidade:</strong> <?= display_value($anuncio_data['nationality'] ?? '') ?>
            </div>
            <div class="col-md-2 col-sm-6 mb-2">
                <strong>Etnia:</strong> <?= display_value($anuncio_data['ethnicity'] ?? '') ?>
            </div>
            <div class="col-md-2 col-sm-6 mb-2">
                <strong>Cor dos Olhos:</strong> <?= display_value($anuncio_data['eye_color'] ?? '') ?>
            </div>
        </div>

        <div class="mb-4">
            <strong>Descrição sobre mim:</strong>
            <p class="text-break"><?= display_value($anuncio_data['description'] ?? '') ?></p>
        </div>

        <h4 class="mb-3 text-primary">Sobre Mim (Aparência)</h4>
        <p><?= display_list($anuncio_data['aparencia'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Idiomas</h4>
        <p><?= display_list($anuncio_data['idiomas'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Local de Atendimento</h4>
        <p><?= display_list($anuncio_data['locais_atendimento'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Formas de Pagamento</h4>
        <p><?= display_list($anuncio_data['formas_pagamento'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Serviços Oferecidos</h4>
        <p><?= display_list($anuncio_data['servicos'] ?? []) ?></p>

        <h4 class="mb-3 text-primary">Preços</h4>
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <strong>15 minutos:</strong> <?= format_price($anuncio_data['price_15min'] ?? 0) ?>
            </div>
            <div class="col-md-4 mb-2">
                <strong>30 minutos:</strong> <?= format_price($anuncio_data['price_30min'] ?? 0) ?>
            </div>
            <div class="col-md-4 mb-2">
                <strong>1 Hora:</strong> <?= format_price($anuncio_data['price_1h'] ?? 0) ?>
            </div>
        </div>

        <h4 class="mb-3 text-primary">Mídia</h4>

        <!-- Seção do Vídeo de Confirmação do Usuário -->
        <?php if (!empty($anuncio_data['confirmation_video_path'])) : ?>
            <div class="mb-4">
                <label class="form-label fw-bold">Vídeo de Confirmação</label>
                <!-- DEBUG: Exibe o caminho do vídeo para depuração -->
                <p class="text-muted small">Caminho do Vídeo (DEBUG): <?= htmlspecialchars($anuncio_data['confirmation_video_path']) ?></p>
                <div class="d-flex justify-content-start" style="width: 150px; height: 250px;">
                    <video controls class="rounded shadow-sm" style="width: 100%; height: 100%; object-fit: contain;">
                        <source src="<?= htmlspecialchars($anuncio_data['confirmation_video_path']) ?>" type="video/mp4">
                        Seu navegador não suporta a tag de vídeo.
                    </video>
                </div>
            </div>
            <hr class="my-4">
        <?php endif; ?>
        <!-- FIM Seção do Vídeo de Confirmação do Usuário -->

        <!-- Seção da Foto da Capa -->
        <?php if (!empty($anuncio_data['cover_photo_path'])) : ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Foto da Capa</label>
                <div class="d-flex justify-content-start">
                    <img src="<?= htmlspecialchars($anuncio_data['cover_photo_path']) ?>" alt="Foto da Capa" class="img-fluid rounded shadow-sm" style="max-width: 250px; height: auto;">
                </div>
            </div>
            <hr class="my-4">
        <?php endif; ?>
        <!-- FIM Seção da Foto da Capa -->

        <!-- Seção de Fotos da Galeria -->
        <?php if (!empty($anuncio_data['fotos_galeria'])) : ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Fotos da Galeria</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($anuncio_data['fotos_galeria'] as $photo_path) : ?>
                        <img src="<?= htmlspecialchars($photo_path) ?>" alt="Foto da Galeria" class="img-fluid rounded shadow-sm" style="max-width: 150px; height: auto;">
                    <?php endforeach; ?>
                </div>
            </div>
            <hr class="my-4">
        <?php endif; ?>
        <!-- FIM Seção de Fotos da Galeria -->

        <!-- Seção de Vídeos (Galeria) -->
        <?php if (!empty($anuncio_data['videos'])) : ?>
            <div class="mb-4">
                <label class="form-label fw-bold">Vídeos</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($anuncio_data['videos'] as $video_path) : ?>
                        <video controls class="rounded shadow-sm" style="max-width: 250px; height: auto;">
                            <source src="<?= htmlspecialchars($video_path) ?>" type="video/mp4">
                            Seu navegador não suporta a tag de vídeo.
                        </video>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr class="my-4">
        <?php endif; ?>
        <!-- FIM Seção de Vídeos (Galeria) -->

        <!-- Seção de Áudios -->
        <?php if (!empty($anuncio_data['audios'])) : ?>
            <div class="mb-4">
                <label class="form-label fw-bold">Áudios</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($anuncio_data['audios'] as $audio_path) : ?>
                        <audio controls class="rounded shadow-sm">
                            <source src="<?= htmlspecialchars($audio_path) ?>" type="audio/mpeg">
                            Seu navegador não suporta a tag de áudio.
                        </audio>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <!-- FIM Seção de Áudios -->

    </div>
</div>
