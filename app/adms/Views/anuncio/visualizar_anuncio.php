<?php

/**
 * View para visualizar os detalhes de um anúncio no painel administrativo.
 *
 * Esta view exibe todas as informações do anúncio de forma somente leitura.
 *
 * @var array $data Dados passados para a view.
 * Esperado: $data['anuncio_data']
 */
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    exit("Erro: Página não encontrada!"); 
}

$anuncio_data = $anuncio_data ?? []; // Garante que $anuncio_data está definido

// Define o form_mode explicitamente como 'view' para esta página
$form_mode = 'view';

// Função auxiliar para exibir um valor, ou 'Não Informado' se vazio
function display_value($value): string
{
    return !empty($value) ? htmlspecialchars($value) : 'Não Informado';
}

// Função auxiliar para exibir itens de listas (checkboxes)
function display_list_items(array $items): string
{
    if (empty($items)) {
        return 'Nenhum selecionado';
    }
    return htmlspecialchars(implode(', ', $items));
}

?>

<!-- ENVOLVENDO TODO O CARD EM UM FORM PARA QUE O JS POSSA LER O data-form-mode -->
<form id="formCriarAnuncio" data-form-mode="<?= htmlspecialchars($form_mode) ?>" data-user-plan-type="premium">
    <div class="card shadow mb-4">
        <!-- Título da Página -->
        <!-- REMOVIDO O ESTILO INLINE. O JS VAI ADICIONAR bg-primary e text-white. -->
        <div class="card-header py-3 text-white">
            <!-- ADICIONADO id="formAnuncioTitle" para que o JS altere o texto. -->
            <h5 class="m-0" id="formAnuncioTitle"><i class="fas fa-eye me-2"></i>VISUALIZAR ANÚNCIO</h5>
        </div>
        <div class="card-body p-4">

            <?php if (empty($anuncio_data)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Você ainda não possui um anúncio cadastrado.
                    <a href="<?= URLADM ?>anuncio/index" class="alert-link">Clique aqui para criar um!</a>
                </div>
            <?php else: ?>

                <h4 class="mb-3 text-primary">Informações Básicas</h4>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <p class="fw-bold mb-1">Estado:</p>
                        <p><?= display_value($anuncio_data['state_name'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="fw-bold mb-1">Cidade:</p>
                        <p><?= display_value($anuncio_data['city_name'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="fw-bold mb-1">Bairro:</p>
                        <p><?= display_value($anuncio_data['neighborhood_name'] ?? '') ?></p>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <p class="fw-bold mb-1">Idade:</p>
                        <p><?= display_value($anuncio_data['age'] ?? '') ?></p>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <p class="fw-bold mb-1">Altura:</p>
                        <p><?= display_value($anuncio_data['height_m'] ?? '') ?> m</p>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <p class="fw-bold mb-1">Peso:</p>
                        <p><?= display_value($anuncio_data['weight_kg'] ?? '') ?> kg</p>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <p class="fw-bold mb-1">Nacionalidade:</p>
                        <p><?= display_value($anuncio_data['nationality'] ?? '') ?></p>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <p class="fw-bold mb-1">Etnia:</p>
                        <p><?= display_value($anuncio_data['ethnicity'] ?? '') ?></p>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-3">
                        <p class="fw-bold mb-1">Cor dos Olhos:</p>
                        <p><?= display_value($anuncio_data['eye_color'] ?? '') ?></p>
                    </div>
                </div>

                <h4 class="mb-3 text-primary">Descrição sobre mim</h4>
                <p class="mb-4"><?= display_value($anuncio_data['description'] ?? '') ?></p>

                <h4 class="mb-3 text-primary">Sobre Mim (Aparência)</h4>
                <p class="mb-4"><?= display_list_items($anuncio_data['aparencia'] ?? []) ?></p>

                <h4 class="mb-3 text-primary">Idiomas</h4>
                <p class="mb-4"><?= display_list_items($anuncio_data['idiomas'] ?? []) ?></p>

                <h4 class="mb-3 text-primary">Local de Atendimento</h4>
                <p class="mb-4"><?= display_list_items($anuncio_data['locais_atendimento'] ?? []) ?></p>

                <h4 class="mb-3 text-primary">Formas de Pagamento</h4>
                <p class="mb-4"><?= display_list_items($anuncio_data['formas_pagamento'] ?? []) ?></p>

                <h4 class="mb-3 text-primary">Serviços Oferecidos</h4>
                <p class="mb-4"><?= display_list_items($anuncio_data['servicos'] ?? []) ?></p>

                <h4 class="mb-3 text-primary">Preços</h4>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <p class="fw-bold mb-1">15 minutos:</p>
                        <p>R$ <?= display_value($anuncio_data['price_15min'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="fw-bold mb-1">30 minutos:</p>
                        <p>R$ <?= display_value($anuncio_data['price_30min'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="fw-bold mb-1">1 Hora:</p>
                        <p>R$ <?= display_value($anuncio_data['price_1h'] ?? '') ?></p>
                    </div>
                </div>

                <h4 class="mb-3 text-primary">Mídia</h4>

                <div class="mb-3">
                    <p class="fw-bold mb-1">Foto da Capa:</p>
                    <?php if (!empty($anuncio_data['cover_photo_path'])): ?>
                        <img src="<?= htmlspecialchars($anuncio_data['cover_photo_path']) ?>" alt="Foto da Capa" class="img-fluid rounded shadow-sm" style="max-width: 300px; height: auto;">
                    <?php else: ?>
                        <p>Nenhuma foto de capa cadastrada.</p>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <p class="fw-bold mb-1">Fotos da Galeria:</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!empty($anuncio_data['fotos_galeria'])): ?>
                            <?php foreach ($anuncio_data['fotos_galeria'] as $photo_url): ?>
                                <img src="<?= htmlspecialchars($photo_url) ?>" alt="Foto da Galeria" class="img-fluid rounded shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Nenhuma foto de galeria cadastrada.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <p class="fw-bold mb-1">Vídeos:</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!empty($anuncio_data['videos'])): ?>
                            <?php foreach ($anuncio_data['videos'] as $video_url): ?>
                                <video controls src="<?= htmlspecialchars($video_url) ?>" class="rounded shadow-sm" style="width: 200px; height: 150px; object-fit: cover;"></video>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Nenhum vídeo cadastrado.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <p class="fw-bold mb-1">Áudios:</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!empty($anuncio_data['audios'])): ?>
                            <?php foreach ($anuncio_data['audios'] as $audio_url): ?>
                                <audio controls src="<?= htmlspecialchars($audio_url) ?>" class="rounded shadow-sm" style="width: 250px;"></audio>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Nenhum áudio cadastrado.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</form>
