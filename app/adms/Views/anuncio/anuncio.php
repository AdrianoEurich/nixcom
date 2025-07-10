<?php

/**
 * View para criar ou editar um anúncio no painel administrativo.
 *
 * Esta view exibe o formulário para adicionar ou editar um anúncio, incluindo campos
 * para localização, informações pessoais, serviços, preços, mídia e aparência.
 *
 * @var array $data Dados passados para a view (se houver, por exemplo, dados pré-preenchidos ou erros).
 * Esperado: $data['user_plan_type'], $data['has_anuncio'], $data['anuncio_data'], $data['form_mode']
 */
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    exit("Erro: Página não encontrada!"); 
}

// As variáveis $user_plan_type, $has_anuncio, $anuncio_data, $form_mode
// são esperadas para serem definidas via extract($this->data) em ConfigViewAdm.
// Se, por algum motivo, elas não estiverem definidas (o que não deveria acontecer
// se o controlador e ConfigViewAdm estiverem funcionando), elas serão inicializadas
// com valores padrão via operador ?? (null coalescing operator).
$user_plan_type = $user_plan_type ?? 'free';
$has_anuncio = $has_anuncio ?? false;
$anuncio_data = $anuncio_data ?? [];
$form_mode = $form_mode ?? 'create'; // 'create' ou 'edit'

// DEBUG HTML: Verifique os valores das variáveis PHP
echo "<!-- DEBUG HTML: user_plan_type = " . htmlspecialchars($user_plan_type) . " -->\n";
echo "<!-- DEBUG HTML: has_anuncio = " . ($has_anuncio ? 'true' : 'false') . " -->\n";
echo "<!-- DEBUG HTML: form_mode = " . htmlspecialchars($form_mode) . " -->\n";
echo "<!-- DEBUG HTML: anuncio_data (partial) = " . htmlspecialchars(json_encode(array_slice($anuncio_data, 0, 5))) . " -->\n"; // Apenas os 5 primeiros para não poluir

// Define a URL de ação do formulário e o texto do botão com base no modo
$form_action = ($form_mode === 'edit') ? URLADM . 'anuncio/atualizarAnuncio' : URLADM . 'anuncio/salvarAnuncio';
$submit_button_text = ($form_mode === 'edit') ? '<i class="fas fa-save me-2"></i>ATUALIZAR ANÚNCIO' : '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
$form_title = ($form_mode === 'edit') ? 'EDITAR ANÚNCIO' : 'CRIAR NOVO ANÚNCIO';
$title_icon_class = ($form_mode === 'edit') ? 'fas fa-edit' : 'fas fa-bullhorn'; // Ícone alterado aqui

// Função auxiliar para verificar se um item deve ser marcado (útil para pré-preencher formulário)
function is_checked(string $field_name, string $item_value, array $anuncio_data): string
{
    if (isset($anuncio_data[$field_name]) && is_array($anuncio_data[$field_name]) && in_array($item_value, $anuncio_data[$field_name])) {
        return 'checked';
    }
    return '';
}

// Função auxiliar para selecionar uma opção de um select
function is_selected(string $field_name, string $option_value, array $anuncio_data): string
{
    if (isset($anuncio_data[$field_name]) && $anuncio_data[$field_name] == $option_value) {
        return 'selected';
    }
    return '';
}

?>

<div class="card shadow mb-4">
    <!-- Título do Formulário: Cor de fundo alterada para 'bg-warning' (laranja) -->
    <div class="card-header py-3 bg-warning text-white">
        <h5 class="m-0"><i class="<?= $title_icon_class ?> me-2"></i><?= $form_title ?></h5>
    </div>
    <div class="card-body p-4">
        <form id="formCriarAnuncio" action="<?= $form_action ?>" method="POST" enctype="multipart/form-data" 
              data-user-plan-type="<?= htmlspecialchars($user_plan_type) ?>" 
              data-form-mode="<?= htmlspecialchars($form_mode) ?>">

            <?php if ($form_mode === 'edit' && isset($anuncio_data['id'])): ?>
                <input type="hidden" name="anuncio_id" value="<?= htmlspecialchars($anuncio_data['id']) ?>">
            <?php endif; ?>

            <h4 class="mb-4 text-primary">Informações Básicas</h4>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="state_id" class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                    <select class="form-select" id="state_id" name="state_id" required data-initial-value="<?= htmlspecialchars($anuncio_data['state_uf'] ?? '') ?>">
                        <option value="">Carregando Estados...</option>
                    </select>
                    <div class="invalid-feedback" id="state_id-feedback"></div>
                </div>

                <div class="col-md-4">
                    <label for="city_id" class="form-label fw-bold">Cidade <span class="text-danger">*</span></label>
                    <select class="form-select" id="city_id" name="city_id" disabled required data-initial-value="<?= htmlspecialchars($anuncio_data['city_code'] ?? '') ?>">
                        <option value="">Selecione a Cidade</option>
                    </select>
                    <div class="invalid-feedback" id="city_id-feedback"></div>
                </div>

                <div class="col-md-4">
                    <label for="neighborhood_id" class="form-label fw-bold">Bairro <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="neighborhood_id" name="neighborhood_id" 
                           placeholder="Selecione a Cidade primeiro" disabled required 
                           data-initial-value="<?= htmlspecialchars($anuncio_data['neighborhood_name'] ?? '') ?>" 
                           value="<?= htmlspecialchars($anuncio_data['neighborhood_name'] ?? '') ?>">
                    <div class="invalid-feedback" id="neighborhood_id-feedback"></div>
                </div>
            </div>

            <div class="row mb-3"> 
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="idade" class="form-label fw-bold">Idade <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="idade" name="idade" min="18" max="99" placeholder="Sua idade" value="<?= htmlspecialchars($anuncio_data['age'] ?? '') ?>" required>
                    <div class="invalid-feedback">Por favor, digite uma idade válida (mínimo 18).</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="altura" class="form-label fw-bold">Altura (m) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="altura" name="altura" placeholder="Ex: 1,70" value="<?= htmlspecialchars($anuncio_data['height_m'] ?? '') ?>" required>
                        <span class="input-group-text">m</span>
                    </div>
                    <div class="invalid-feedback">Por favor, digite uma altura válida (ex: 1,70).</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="peso" class="form-label fw-bold">Peso (kg) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">kg</span>
                        <input type="text" class="form-control" id="peso" name="peso" placeholder="Ex: 65" value="<?= htmlspecialchars($anuncio_data['weight_kg'] ?? '') ?>" required>
                    </div>
                    <div class="invalid-feedback">Por favor, digite um peso válido (ex: 65).</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="nacionalidade" class="form-label fw-bold">Nacionalidade <span class="text-danger">*</span></label>
                    <select class="form-select" id="nacionalidade" name="nacionalidade" required data-initial-value="<?= htmlspecialchars($anuncio_data['nationality'] ?? '') ?>">
                        <option value="">Selecione </option>
                        <?php
                        $nacionalidades = [
                            "Brasileira", "Portuguesa", "Americana", "Argentina", "Chilena",
                            "Colombiana", "Espanhola", "Francesa", "Inglesa", "Italiana",
                            "Japonesa", "Mexicana", "Paraguaia", "Uruguaia", "Venezuelana",
                            "" // Adicionado um valor vazio para "Selecione"
                        ];
                        sort($nacionalidades); 
                        foreach ($nacionalidades as $nacionalidade) : ?>
                            <option value="<?= htmlspecialchars($nacionalidade) ?>" <?= is_selected('nationality', $nacionalidade, $anuncio_data) ?>>
                                <?= htmlspecialchars($nacionalidade) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Por favor, selecione a nacionalidade.</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="etnia" class="form-label fw-bold">Etnia</label>
                    <select class="form-select" id="etnia" name="etnia">
                        <option value="">Selecione</option>
                        <?php
                        $etnias = ["Africana", "Asiática", "Caucasiana", "Indígena", "Latina", "Mestiça", ""]; // Adicionado um valor vazio
                        foreach ($etnias as $etnia) : ?>
                            <option value="<?= htmlspecialchars($etnia) ?>" <?= is_selected('ethnicity', $etnia, $anuncio_data) ?>><?= htmlspecialchars($etnia) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="cor_olhos" class="form-label fw-bold">Cor dos Olhos</label>
                    <select class="form-select" id="cor_olhos" name="cor_olhos">
                        <option value="">Selecione</option>
                        <?php
                        $cores_olhos = ["Azuis", "Castanhos", "Verdes", "Pretos", "Mel", ""]; // Adicionado um valor vazio
                        foreach ($cores_olhos as $cor) : ?>
                            <option value="<?= htmlspecialchars($cor) ?>" <?= is_selected('eye_color', $cor, $anuncio_data) ?>><?= htmlspecialchars($cor) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="descricao_sobre_mim" class="form-label fw-bold">Descrição sobre mim <span class="text-danger">*</span></label>
                <textarea class="form-control" id="descricao_sobre_mim" name="descricao_sobre_mim" rows="5" placeholder="Conte um pouco sobre você..." required><?= htmlspecialchars($anuncio_data['description'] ?? '') ?></textarea>
                <div class="invalid-feedback">Por favor, preencha a descrição sobre você.</div>
            </div>


            <h4 class="mb-3 text-primary">Sobre Mim (Aparência) <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 1 item de aparência.</small>
            <div class="row mb-2" id="aparencia-checkboxes">
                <?php
                $aparencia = [
                    "Magra", "Peito natural", "Siliconada", "Peitos grande", "Peitos pequeno",
                    "Depilada", "Peluda", "Alta", "Baixa", "Mignon", "Ruiva", "Morena", "Gordinha",
                    "Loira", "Pele Morena"
                ];
                foreach ($aparencia as $item) {
                    $id_aparencia = 'aparencia_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $item))); 
                    echo '<div class="col-md-3 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($item) . '" id="' . $id_aparencia . '" name="aparencia[]" ' . is_checked('aparencia', $item, $anuncio_data) . '>';
                    echo '<label class="form-check-label" for="' . $id_aparencia . '">' . htmlspecialchars($item) . '</label>'; 
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="text-danger small mb-4" id="aparencia-feedback"></div>

            <h4 class="mb-3 text-primary">Idiomas <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 1 idioma.</small>
            <div class="row mb-2" id="idiomas-checkboxes">
                <?php
                $idiomas = ["Português", "Inglês", "Espanhol"];
                foreach ($idiomas as $idioma) {
                    $id_idioma = 'idioma_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $idioma))); 
                    echo '<div class="col-md-4 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($idioma) . '" id="' . $id_idioma . '" name="idiomas[]" ' . is_checked('idiomas', $idioma, $anuncio_data) . '>';
                    echo '<label class="form-check-label" for="' . $id_idioma . '">' . htmlspecialchars($idioma) . '</label>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="text-danger small mb-4" id="idiomas-feedback"></div>

            <h4 class="mb-3 text-primary">Local de Atendimento <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 1 local.</small>
            <div class="row mb-2" id="locais-checkboxes">
                <?php
                $locais = ["Hotel", "Motel", "A domicílio", "Com Local"];
                foreach ($locais as $local) {
                    $id_local = 'local_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $local))); 
                    echo '<div class="col-md-3 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($local) . '" id="' . $id_local . '" name="locais_atendimento[]" ' . is_checked('locais_atendimento', $local, $anuncio_data) . '>';
                    echo '<label class="form-check-label" for="' . $id_local . '">' . htmlspecialchars($local) . '</label>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="text-danger small mb-4" id="locais-feedback"></div>

            <h4 class="mb-3 text-primary">Formas de Pagamento <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 1 forma de pagamento.</small>
            <div class="row mb-2" id="pagamentos-checkboxes">
                <?php
                $pagamentos = ["Dinheiro", "Pix", "Cartão de Crédito"];
                foreach ($pagamentos as $pagamento) {
                    $id_pagamento = 'pagamento_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $pagamento))); 
                    echo '<div class="col-md-4 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($pagamento) . '" id="' . $id_pagamento . '" name="formas_pagamento[]" ' . is_checked('formas_pagamento', $pagamento, $anuncio_data) . '>';
                    echo '<label class="form-check-label" for="' . $id_pagamento . '">' . htmlspecialchars($pagamento) . '</label>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="text-danger small mb-4" id="pagamentos-feedback"></div>


            <h4 class="mb-3 text-primary">Serviços Oferecidos <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 2 serviços.</small>
            <div class="row mb-2" id="servicos-checkboxes">
                <?php
                $servicos = [
                    "BEIJO NA BOCA", "ATENDE CASAIS", "FETICHISMO", "ORAL COM CAMISINHA", "ORAL SEM CAMISINHA",
                    "SQUITING", "SADO SUBMISSA", "CHUVA DOURADA", "SEXO ANAL", "GARGANTA PROFUNDA",
                    "LESBIANISMO", "EJACULAÇÃO NO CORPO", "ORAL ATÉ O FINAL", "DUPLAS", "DOMINADORA",
                    "FANTASIAS E FIGURINOS", "MASSAGEM ERÓTICA", "ATENÇÃO A MULHERES", "EJACULAÇÃO FACIAL",
                    "SADO SUAVE", "FESTAS EVENTOS", "FISTING ANAL", "ATENÇÃO A DEFICIENTES FÍSICOS",
                    "DESPEDIDAS DE SOLTEIROS", "ORGIAS", "FISTING VAGINAL", "SEXCAM", "STRAP ON"
                ];
                foreach ($servicos as $item) {
                    $id_servico = 'servico_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $item))); 
                    echo '<div class="col-md-4 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($item) . '" id="' . $id_servico . '" name="servicos[]" ' . is_checked('servicos', $item, $anuncio_data) . '>';
                    echo '<label class="form-check-label" for="' . $id_servico . '">' . htmlspecialchars($item) . '</label>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="text-danger small mb-4" id="servicos-feedback"></div>

            <h4 class="mb-3 text-primary">Preços <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Preencha pelo menos um preço.</small>
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label for="preco_15min" class="form-label fw-bold">15 minutos</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" id="preco_15min" name="precos[15min]" placeholder="0,00" value="<?= htmlspecialchars($anuncio_data['price_15min'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback" id="preco_15min-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="preco_30min" class="form-label fw-bold">30 minutos</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" id="preco_30min" name="precos[30min]" placeholder="0,00" value="<?= htmlspecialchars($anuncio_data['price_30min'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback" id="preco_30min-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="preco_1h" class="form-label fw-bold">1 Hora</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" id="preco_1h" name="precos[1h]" placeholder="0,00" value="<?= htmlspecialchars($anuncio_data['price_1h'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback" id="preco_1h-feedback"></div>
                </div>
            </div>
            <div class="text-danger small mb-4" id="precos-feedback"></div>


            <h4 class="mb-3 text-primary">Mídia <span class="text-danger">*</span></h4>

            <div class="mb-3">
                <label class="form-label fw-bold">Foto da Capa <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3 justify-content-start">
                    <div class="photo-upload-box cover-photo-box" id="coverPhotoUploadBox">
                        <input type="file" id="foto_capa_input" name="foto_capa" accept="image/*" class="d-none" <?= ($form_mode === 'create' || empty($anuncio_data['cover_photo_path'])) ? 'required' : '' ?>>
                        <!-- Hidden input to signal if cover photo was removed -->
                        <input type="hidden" name="cover_photo_removed" id="cover_photo_removed" value="false">
                        <img id="coverPhotoPreview" src="<?= isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path']) ? htmlspecialchars($anuncio_data['cover_photo_path']) : '' ?>" alt="Pré-visualização da capa" class="photo-preview rounded mx-auto d-block" style="display: <?= isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path']) ? 'block' : 'none' ?>;">
                        <div class="upload-placeholder" style="display: <?= isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path']) ? 'none' : 'flex' ?>;">
                            <i class="fas fa-camera fa-2x"></i>
                            <p>Foto da Capa</p>
                        </div>
                        <button type="button" class="btn-remove-photo <?= isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path']) ? '' : 'd-none' ?>">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
                <small class="text-muted">Apenas uma foto para a capa.</small>
                <div class="text-danger small mt-2" id="coverPhoto-feedback"></div>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label fw-bold">Fotos da Galeria (Máx. 20, 1 Gratuita)</label> <!-- ATUALIZADO AQUI -->
                <div class="d-flex flex-wrap gap-3" id="galleryPhotoContainer">
                    <?php
                    $existing_gallery_photos = $anuncio_data['fotos_galeria'] ?? [];
                    for ($i = 0; $i < 20; $i++) :
                        // ATUALIZAÇÃO: Apenas o primeiro slot é gratuito
                        $is_free_slot = $i === 0; 
                        $has_photo = isset($existing_gallery_photos[$i]) && !empty($existing_gallery_photos[$i]);
                        $photo_url = $has_photo ? htmlspecialchars($existing_gallery_photos[$i]) : '';
                        $display_style = $has_photo ? 'block' : 'none';
                        $placeholder_display_style = $has_photo ? 'none' : 'flex';
                        $remove_button_class = $has_photo ? '' : 'd-none';
                    ?>
                        <div class="photo-upload-box gallery-upload-box" data-photo-index="<?= $i ?>" data-is-free-slot="<?= $is_free_slot ? 'true' : 'false' ?>">
                            <input type="file" name="fotos_galeria[]" accept="image/*" class="d-none">
                            <?php if ($has_photo): // Adiciona input hidden APENAS se houver uma foto existente ?>
                                <input type="hidden" name="existing_gallery_paths[]" value="<?= $photo_url ?>">
                            <?php endif; ?>
                            <img src="<?= $photo_url ?>" alt="Pré-visualização da galeria" class="photo-preview rounded mx-auto d-block" style="display: <?= $display_style ?>;">
                            <div class="upload-placeholder" style="display: <?= $placeholder_display_style ?>;">
                                <i class="fas fa-camera fa-2x"></i>
                                <p>Adicionar Foto</p>
                            </div>
                            <button type="button" class="btn-remove-photo <?= $remove_button_class ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            <div class="premium-lock-overlay" style="display: none;">
                                <i class="fas fa-lock fa-2x"></i>
                                <p>Plano Pago</p>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted">A primeira foto é gratuita. As demais são liberadas apenas para planos pagos.</small> <!-- ATUALIZADO AQUI -->
                <div class="text-danger small mt-2" id="gallery-feedback-error"></div> 
            </div>

            <hr class="my-4">

            <div class="mb-4">
                <label class="form-label fw-bold">Vídeos (Máx. 3)</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $existing_videos = $anuncio_data['videos'] ?? [];
                    for ($i = 0; $i < 3; $i++) :
                        $has_video = isset($existing_videos[$i]) && !empty($existing_videos[$i]);
                        $video_url = $has_video ? htmlspecialchars($existing_videos[$i]) : '';
                        $display_style = $has_video ? 'block' : 'none';
                        $placeholder_display_style = $has_video ? 'none' : 'flex';
                        $remove_button_class = $has_video ? '' : 'd-none';
                    ?>
                        <div class="photo-upload-box video-upload-box">
                            <input class="d-none" type="file" name="videos[]" accept="video/*">
                            <?php if ($has_video): // Adiciona input hidden APENAS se houver um vídeo existente ?>
                                <input type="hidden" name="existing_video_paths[]" value="<?= $video_url ?>">
                            <?php endif; ?>
                            <video class="photo-preview rounded mx-auto d-block" style="display: <?= $display_style ?>;" controls src="<?= $video_url ?>"></video>
                            <div class="upload-placeholder" style="display: <?= $placeholder_display_style ?>;">
                                <i class="fas fa-video fa-2x"></i>
                                <p>Adicionar Vídeo</p>
                            </div>
                            <button type="button" class="btn-remove-photo <?= $remove_button_class ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            <div class="premium-lock-overlay" style="display: none;">
                                <i class="fas fa-lock fa-2x"></i>
                                <p>Plano Pago</p>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted">3 vídeos curtos. Apenas para planos pagos.</small>
                <div class="text-danger small mt-2" id="videos-feedback-error"></div> 
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Áudios (Máx. 3)</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $existing_audios = $anuncio_data['audios'] ?? [];
                    for ($i = 0; $i < 3; $i++) :
                        $has_audio = isset($existing_audios[$i]) && !empty($existing_audios[$i]);
                        $audio_url = $has_audio ? htmlspecialchars($existing_audios[$i]) : '';
                        $display_style = $has_audio ? 'block' : 'none';
                        $placeholder_display_style = $has_audio ? 'none' : 'flex';
                        $remove_button_class = $has_audio ? '' : 'd-none';
                    ?>
                        <div class="photo-upload-box audio-upload-box">
                            <input class="d-none" type="file" name="audios[]" accept="audio/*">
                            <?php if ($has_audio): // Adiciona input hidden APENAS se houver um áudio existente ?>
                                <input type="hidden" name="existing_audio_paths[]" value="<?= $audio_url ?>">
                            <?php endif; ?>
                            <audio class="photo-preview rounded mx-auto d-block" style="display: <?= $display_style ?>;" controls src="<?= $audio_url ?>"></audio>
                            <div class="upload-placeholder" style="display: <?= $placeholder_display_style ?>;">
                                <i class="fas fa-microphone fa-2x"></i>
                                <p>Adicionar Áudio</p>
                            </div>
                            <button type="button" class="btn-remove-photo <?= $remove_button_class ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            <div class="premium-lock-overlay" style="display: none;">
                                <i class="fas fa-lock fa-2x"></i>
                                <p>Plano Pago</p>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted">3 áudios. Apenas para planos pagos.</small>
                <div class="text-danger small mt-2" id="audios-feedback-error"></div> 
            </div>

            <div class="text-end mt-4">
                <!-- Botão de Submit: Cor alterada para 'btn-warning' (laranja) -->
                <button type="submit" class="btn btn-warning btn-lg px-5 py-3" id="btnSubmitAnuncio">
                    <?= $submit_button_text ?>
                </button>
            </div>
        </form>
    </div>
</div>
