<?php

/**
 * View para criar um novo anúncio no painel administrativo.
 *
 * Esta view exibe o formulário para adicionar um anúncio, incluindo campos
 * para localização, informações pessoais, serviços, preços, mídia e aparência.
 *
 * @var array $data Dados passados para a view (se houver, por exemplo, dados pré-preenchidos ou erros).
 */
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    exit("Erro: Página não encontrada!"); // Usando exit para finalizar a execução de forma mais clara
}

// Inicializa $data se não estiver definida para evitar erros de undefined variable
$data = $data ?? [];

// Função auxiliar para verificar se um item deve ser marcado (útil para pré-preencher formulário)
function is_checked(string $field_name, string $item_value, array $data): string
{
    // A validação para checkboxes no PHP geralmente viria de um array de valores já selecionados.
    // Para este exemplo, assumimos que $data[$field_name] seria um array de strings.
    if (isset($data[$field_name]) && is_array($data[$field_name]) && in_array($item_value, $data[$field_name])) {
        return 'checked';
    }
    return '';
}

// Função auxiliar para selecionar uma opção de um select
function is_selected(string $field_name, string $option_value, array $data): string
{
    if (isset($data[$field_name]) && $data[$field_name] == $option_value) {
        return 'selected';
    }
    return '';
}

?>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary text-white">
        <h5 class="m-0"><i class="fas fa-bullhorn me-2"></i>CRIAR NOVO ANÚNCIO</h5>
    </div>
    <div class="card-body p-4">
        <form id="formCriarAnuncio" action="<?= URLADM ?>/anuncio/salvarAnuncio" method="POST" enctype="multipart/form-data">

            <h4 class="mb-4 text-primary">Informações Básicas</h4>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="state_id" class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                    <select class="form-select" id="state_id" name="state_id" required data-initial-value="<?= htmlspecialchars($data['state_id'] ?? '') ?>">
                        <option value="">Carregando Estados...</option>
                    </select>
                    <div class="invalid-feedback" id="state_id-feedback"></div>
                </div>

                <div class="col-md-4">
                    <label for="city_id" class="form-label fw-bold">Cidade <span class="text-danger">*</span></label>
                    <select class="form-select" id="city_id" name="city_id" disabled required data-initial-value="<?= htmlspecialchars($data['city_id'] ?? '') ?>">
                        <option value="">Selecione a Cidade</option>
                    </select>
                    <div class="invalid-feedback" id="city_id-feedback"></div>
                </div>

                <div class="col-md-4">
                    <label for="neighborhood_id" class="form-label fw-bold">Bairro <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="neighborhood_id" name="neighborhood_id" 
                           placeholder="Selecione a Cidade primeiro" disabled required 
                           data-initial-value="<?= htmlspecialchars($data['neighborhood_id'] ?? '') ?>" 
                           value="<?= htmlspecialchars($data['neighborhood_id'] ?? '') ?>">
                    <div class="invalid-feedback" id="neighborhood_id-feedback"></div>
                </div>
            </div>

            <div class="row mb-3"> 
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="idade" class="form-label fw-bold">Idade <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="idade" name="idade" min="18" max="99" placeholder="Sua idade" value="<?= htmlspecialchars($data['idade'] ?? '') ?>" required>
                    <div class="invalid-feedback">Por favor, digite uma idade válida (mínimo 18).</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="altura" class="form-label fw-bold">Altura (m) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="altura" name="altura" placeholder="Ex: 1,70" value="<?= htmlspecialchars($data['altura'] ?? '') ?>" required>
                        <span class="input-group-text">m</span>
                    </div>
                    <div class="invalid-feedback">Por favor, digite uma altura válida (ex: 1,70).</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="peso" class="form-label fw-bold">Peso (kg) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="peso" name="peso" placeholder="Ex: 65" value="<?= htmlspecialchars($data['peso'] ?? '') ?>" required>
                        <span class="input-group-text">kg</span>
                    </div>
                    <div class="invalid-feedback">Por favor, digite um peso válido (ex: 65).</div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="nacionalidade" class="form-label fw-bold">Nacionalidade <span class="text-danger">*</span></label>
                    <select class="form-select" id="nacionalidade" name="nacionalidade" required data-initial-value="<?= htmlspecialchars($data['nacionalidade'] ?? '') ?>">
                        <option value="">Selecione </option>
                        <?php
                        // Lista de nacionalidades comuns (você pode expandir esta lista)
                        $nacionalidades = [
                            "Brasileira", "Portuguesa", "Americana", "Argentina", "Chilena",
                            "Colombiana", "Espanhola", "Francesa", "Inglesa", "Italiana",
                            "Japonesa", "Mexicana", "Paraguaia", "Uruguaia", "Venezuelana",
                            "Alemã", "Boliviana", "Chinesa", "Cubana"
                        ];
                        sort($nacionalidades); // Ordena as nacionalidades em ordem alfabética
                        foreach ($nacionalidades as $nacionalidade) : ?>
                            <option value="<?= htmlspecialchars($nacionalidade) ?>" <?= is_selected('nacionalidade', $nacionalidade, $data) ?>>
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
                        $etnias = ["Africana", "Asiática", "Caucasiana", "Indígena", "Latina", "Mestiça"];
                        foreach ($etnias as $etnia) : ?>
                            <option value="<?= htmlspecialchars($etnia) ?>" <?= is_selected('etnia', $etnia, $data) ?>><?= htmlspecialchars($etnia) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="cor_olhos" class="form-label fw-bold">Cor dos Olhos</label>
                    <select class="form-select" id="cor_olhos" name="cor_olhos">
                        <option value="">Selecione</option>
                        <?php
                        $cores_olhos = ["Azuis", "Castanhos", "Verdes", "Pretos", "Mel"];
                        foreach ($cores_olhos as $cor) : ?>
                            <option value="<?= htmlspecialchars($cor) ?>" <?= is_selected('cor_olhos', $cor, $data) ?>><?= htmlspecialchars($cor) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="descricao_sobre_mim" class="form-label fw-bold">Descrição sobre mim <span class="text-danger">*</span></label>
                <textarea class="form-control" id="descricao_sobre_mim" name="descricao_sobre_mim" rows="5" placeholder="Conte um pouco sobre você..." required><?= htmlspecialchars($data['descricao_sobre_mim'] ?? '') ?></textarea>
                <div class="invalid-feedback">Por favor, preencha a descrição sobre você.</div>
            </div>


            <h4 class="mb-3 text-primary">Sobre Mim (Aparência) <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 1 item de aparência.</small>
            <div class="row mb-2" id="aparencia-checkboxes">
                <?php
                $aparencia = [
                    "Magra",
                    "Peito natural",
                    "Siliconada",
                    "Peitos grande",
                    "Peitos pequeno",
                    "Depilada",
                    "Peluda",
                    "Alta",
                    "Baixa",
                    "Mignon",
                    "Ruiva",
                    "Morena",
                    "Gordinha",
                    "Loira",
                    "Pele Morena"
                ];
                foreach ($aparencia as $item) {
                    $id_aparencia = 'aparencia_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $item))); // ID mais limpo
                    echo '<div class="col-md-3 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($item) . '" id="' . $id_aparencia . '" name="aparencia[]" ' . is_checked('aparencia', $item, $data) . '>';
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
                    $id_idioma = 'idioma_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $idioma))); // ID mais limpo
                    echo '<div class="col-md-4 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($idioma) . '" id="' . $id_idioma . '" name="idiomas[]" ' . is_checked('idiomas', $idioma, $data) . '>';
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
                    $id_local = 'local_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $local))); // ID mais limpo
                    echo '<div class="col-md-3 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($local) . '" id="' . $id_local . '" name="locais_atendimento[]" ' . is_checked('locais_atendimento', $local, $data) . '>';
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
                    $id_pagamento = 'pagamento_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $pagamento))); // ID mais limpo
                    echo '<div class="col-md-4 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($pagamento) . '" id="' . $id_pagamento . '" name="formas_pagamento[]" ' . is_checked('formas_pagamento', $pagamento, $data) . '>';
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
                    "BEIJO NA BOCA",
                    "ATENDE CASAIS",
                    "FETICHISMO",
                    "ORAL COM CAMISINHA",
                    "ORAL SEM CAMISINHA",
                    "SQUITING",
                    "SADO SUBMISSA",
                    "CHUVA DOURADA",
                    "SEXO ANAL",
                    "GARGANTA PROFUNDA",
                    "LESBIANISMO",
                    "EJACULAÇÃO NO CORPO",
                    "ORAL ATÉ O FINAL",
                    "DUPLAS",
                    "DOMINADORA",
                    "FANTASIAS E FIGURINOS",
                    "MASSAGEM ERÓTICA",
                    "ATENÇÃO A MULHERES",
                    "EJACULAÇÃO FACIAL",
                    "SADO SUAVE",
                    "FESTAS EVENTOS",
                    "FISTING ANAL",
                    "ATENÇÃO A DEFICIENTES FÍSICOS",
                    "DESPEDIDAS DE SOLTEIROS",
                    "ORGIAS",
                    "FISTING VAGINAL",
                    "SEXCAM",
                    "STRAP ON"
                ];
                foreach ($servicos as $servico) {
                    $id_servico = 'servico_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $servico))); // ID mais limpo
                    echo '<div class="col-md-4 col-sm-6 mb-2">';
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($servico) . '" id="' . $id_servico . '" name="servicos[]" ' . is_checked('servicos', $servico, $data) . '>';
                    echo '<label class="form-check-label" for="' . $id_servico . '">' . htmlspecialchars($servico) . '</label>';
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
                        <input type="text" class="form-control" id="preco_15min" name="precos[15min]" placeholder="0,00" value="<?= htmlspecialchars($data['precos']['15min'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback" id="preco_15min-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="preco_30min" class="form-label fw-bold">30 minutos</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" id="preco_30min" name="precos[30min]" placeholder="0,00" value="<?= htmlspecialchars($data['precos']['30min'] ?? '') ?>">
                    </div>
                    <div class="invalid-feedback" id="preco_30min-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="preco_1h" class="form-label fw-bold">1 Hora</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" id="preco_1h" name="precos[1h]" placeholder="0,00" value="<?= htmlspecialchars($data['precos']['1h'] ?? '') ?>">
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
                        <input type="file" id="foto_capa_input" name="foto_capa" accept="image/*" class="d-none" required>
                        <img id="coverPhotoPreview" src="<?= isset($data['foto_capa_url']) && !empty($data['foto_capa_url']) ? htmlspecialchars($data['foto_capa_url']) : '' ?>" alt="Pré-visualização da capa" class="photo-preview rounded mx-auto d-block" style="display: <?= isset($data['foto_capa_url']) && !empty($data['foto_capa_url']) ? 'block' : 'none' ?>;">
                        <div class="upload-placeholder" style="display: <?= isset($data['foto_capa_url']) && !empty($data['foto_capa_url']) ? 'none' : 'flex' ?>;">
                            <i class="fas fa-camera fa-2x"></i>
                            <p>Foto da Capa</p>
                        </div>
                        <button type="button" class="btn-remove-photo <?= isset($data['foto_capa_url']) && !empty($data['foto_capa_url']) ? '' : 'd-none' ?>">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
                <small class="text-muted">Apenas uma foto para a capa.</small>
                <div class="text-danger small mt-2" id="coverPhoto-feedback"></div>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label fw-bold">Fotos da Galeria (Máx. 20, 3 Gratuitas)</label>
                <div class="d-flex flex-wrap gap-3" id="galleryPhotoContainer">
                    <?php
                    $existing_gallery_photos = $data['fotos_galeria'] ?? [];
                    for ($i = 0; $i < 20; $i++) :
                        $is_free = $i < 3;
                        $has_photo = isset($existing_gallery_photos[$i]) && !empty($existing_gallery_photos[$i]);
                        $photo_url = $has_photo ? htmlspecialchars($existing_gallery_photos[$i]) : '';
                        $display_style = $has_photo ? 'block' : 'none';
                        $placeholder_display_style = $has_photo ? 'none' : 'flex';
                        $remove_button_class = $has_photo ? '' : 'd-none';
                        $input_disabled = (!$is_free && !$has_photo) ? 'disabled' : ''; // Desabilita input se não for gratuito e não tiver foto

                        $box_class = 'gallery-upload-box';
                        if ($is_free) {
                            $box_class .= ' active-plan'; // Classe visual para indicar que é gratuito
                        } else {
                            $box_class .= ' premium-locked'; // Classe visual e funcional para bloquear
                        }
                    ?>
                        <div class="photo-upload-box <?= $box_class ?>" data-photo-index="<?= $i ?>">
                            <input type="file" name="fotos_galeria[]" accept="image/*" class="d-none" <?= $input_disabled ?>>
                            <img src="<?= $photo_url ?>" alt="Pré-visualização da galeria" class="photo-preview rounded mx-auto d-block" style="display: <?= $display_style ?>;">
                            <div class="upload-placeholder" style="display: <?= $placeholder_display_style ?>;">
                                <?php if (!$is_free && !$has_photo) : ?>
                                    <i class="fas fa-lock fa-2x"></i>
                                    <p>Plano Pago</p>
                                <?php else : ?>
                                    <i class="fas fa-camera fa-2x"></i>
                                    <p>Adicionar Foto</p>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-remove-photo <?= $remove_button_class ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted">As 3 primeiras fotos são gratuitas. As demais são liberadas apenas para planos pagos.</small>
            </div>

            <hr class="my-4">

            <div class="mb-4">
                <label class="form-label fw-bold">Vídeos (Máx. 3)</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $existing_videos = $data['videos'] ?? [];
                    for ($i = 0; $i < 3; $i++) :
                        $has_video = isset($existing_videos[$i]) && !empty($existing_videos[$i]);
                        $video_url = $has_video ? htmlspecialchars($existing_videos[$i]) : '';
                        $display_style = $has_video ? 'block' : 'none';
                        $placeholder_display_style = $has_video ? 'none' : 'flex';
                        $remove_button_class = $has_video ? '' : 'd-none';
                    ?>
                        <div class="photo-upload-box video-upload-box premium-locked">
                            <input class="d-none" type="file" name="videos[]" accept="video/*" disabled>
                            <video class="photo-preview rounded mx-auto d-block" style="display: <?= $display_style ?>;" controls src="<?= $video_url ?>"></video>
                            <div class="upload-placeholder" style="display: <?= $placeholder_display_style ?>;">
                                <i class="fas fa-lock fa-2x"></i>
                                <p>Plano Pago</p>
                            </div>
                            <button type="button" class="btn-remove-photo <?= $remove_button_class ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted">3 vídeos curtos. Apenas para planos pagos.</small>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Áudios (Máx. 3)</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $existing_audios = $data['audios'] ?? [];
                    for ($i = 0; $i < 3; $i++) :
                        $has_audio = isset($existing_audios[$i]) && !empty($existing_audios[$i]);
                        $audio_url = $has_audio ? htmlspecialchars($existing_audios[$i]) : '';
                        $display_style = $has_audio ? 'block' : 'none';
                        $placeholder_display_style = $has_audio ? 'none' : 'flex';
                        $remove_button_class = $has_audio ? '' : 'd-none';
                    ?>
                        <div class="photo-upload-box audio-upload-box premium-locked">
                            <input class="d-none" type="file" name="audios[]" accept="audio/*" disabled>
                            <audio class="photo-preview rounded mx-auto d-block" style="display: <?= $display_style ?>;" controls src="<?= $audio_url ?>"></audio>
                            <div class="upload-placeholder" style="display: <?= $placeholder_display_style ?>;">
                                <i class="fas fa-lock fa-2x"></i>
                                <p>Plano Pago</p>
                            </div>
                            <button type="button" class="btn-remove-photo <?= $remove_button_class ?>">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted">3 áudios. Apenas para planos pagos.</small>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5 py-3" id="btnSubmitAnuncio">
                    <i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO
                </button>
            </div>
        </form>
    </div>
</div>
