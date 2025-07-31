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
$user_role = $_SESSION['user_role'] ?? 'normal'; // <--- Adicionado: Pega o papel do usuário da sessão

// Define a URL de ação do formulário e o texto do botão com base no modo
$form_action = ($form_mode === 'edit') ? URLADM . 'anuncio/updateAnuncio' : URLADM . 'anuncio/createAnuncio';
// O texto do botão será definido pelo JS, mas mantemos um fallback aqui se o JS falhar
$submit_button_text = ($form_mode === 'edit') ? '<i class="fas fa-save me-2"></i>ATUALIZAR ANÚNCIO' : '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
// O título do formulário será definido pelo JS. O PHP apenas define o fallback.
$form_title = ($form_mode === 'edit') ? 'EDITAR ANÚNCIO' : 'CRIAR NOVO ANÚNCIO';
$title_icon_class = ($form_mode === 'edit') ? 'fas fa-edit' : 'fas fa-plus-circle'; // PHP define o ícone correto para o modo

// Função auxiliar para verificar se um item deve ser marcado (útil para pré-preencher formulário)
function is_checked(string $field_name, string $item_value, array $anuncio_data): string
{
    // Verifica se o campo existe e se o valor corresponde, tanto em $anuncio_data quanto em $_POST
    $current_values = [];
    if (isset($anuncio_data[$field_name]) && is_array($anuncio_data[$field_name])) {
        $current_values = $anuncio_data[$field_name];
    } elseif (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
        $current_values = $_POST[$field_name];
    }
    
    if (in_array($item_value, $current_values)) {
        return 'checked';
    }
    return '';
}

// Função auxiliar para selecionar uma opção de um select
function is_selected(string $field_name, string $option_value, array $anuncio_data): string
{
    // Prioriza $anuncio_data, depois $_POST
    if (isset($anuncio_data[$field_name]) && $anuncio_data[$field_name] == $option_value) {
        return 'selected';
    }
    if (isset($_POST[$field_name]) && $_POST[$field_name] == $option_value) {
        return 'selected';
    }
    return '';
}

?>

<!-- Adicionado data-page-type="form" para o JavaScript -->
<div class="card shadow mb-4" data-page-type="form">
    <!-- Título do Formulário: Cor de fundo será definida pelo JS -->
    <div class="card-header py-3">
        <h5 class="m-0" id="formAnuncioTitle"><i class="<?= $title_icon_class ?> me-2"></i><?= $form_title ?></h5>
    </div>
    <div class="card-body p-4">
        <!-- CORREÇÃO AQUI: ID do formulário alterado para 'formAnuncio' -->
        <form id="formAnuncio" action="<?= $form_action ?>" method="POST" enctype="multipart/form-data"
              data-user-plan-type="<?= htmlspecialchars($user_plan_type) ?>"
              data-form-mode="<?= htmlspecialchars($form_mode) ?>"
              data-anuncio-data="<?= htmlspecialchars(json_encode($anuncio_data)) ?>"> <!-- Passa todos os dados do anúncio para o JS -->

            <?php if ($form_mode === 'edit' && isset($anuncio_data['id'])): ?>
                <input type="hidden" name="anuncio_id" value="<?= htmlspecialchars($anuncio_data['id']) ?>">
            <?php endif; ?>

            <h4 class="mb-4 text-primary">Informações Básicas</h4>

            <!-- Linha 1: Nome de trabalho * Idade * Telefone * -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="service_name" class="form-label fw-bold">Nome de trabalho <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="service_name" name="service_name" placeholder="Ex: Acompanhante de Luxo" value="<?= htmlspecialchars($anuncio_data['service_name'] ?? $_POST['service_name'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="service_name-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label for="age" class="form-label fw-bold">Idade <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="age" name="age" min="18" max="99" placeholder="Sua idade" value="<?= htmlspecialchars($anuncio_data['age'] ?? $_POST['age'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="age-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label for="phone_number" class="form-label fw-bold">Telefone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control phone-mask" id="phone_number" name="phone_number" placeholder="(XX) XXXXX-XXXX" value="<?= htmlspecialchars($anuncio_data['phone_number'] ?? $_POST['phone_number'] ?? '') ?>" required>
                    <div class="invalid-feedback" id="phone_number-feedback"></div>
                </div>
            </div>

            <!-- Linha 2: Altura (m) * Peso (kg) * Cor dos Olhos -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="height_m" class="form-label fw-bold">Altura (m) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control height-mask" id="height_m" name="height_m" placeholder="Ex: 1,70" value="<?= htmlspecialchars($anuncio_data['height_m'] ?? $_POST['height_m'] ?? '') ?>" required>
                        <span class="input-group-text">m</span>
                    </div>
                    <div class="invalid-feedback" id="height_m-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label for="weight_kg" class="form-label fw-bold">Peso (kg) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control weight-mask" id="weight_kg" name="weight_kg" placeholder="Ex: 65" value="<?= htmlspecialchars($anuncio_data['weight_kg'] ?? $_POST['weight_kg'] ?? '') ?>" required>
                        <span class="input-group-text">kg</span>
                    </div>
                    <div class="invalid-feedback" id="weight_kg-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label for="eye_color" class="form-label fw-bold">Cor dos Olhos</label>
                    <select class="form-select" id="eye_color" name="eye_color">
                        <option value="">Selecione</option>
                        <?php
                        $cores_olhos = ["Azuis", "Castanhos", "Verdes", "Pretos", "Mel"];
                        foreach ($cores_olhos as $cor) : ?>
                            <option value="<?= htmlspecialchars($cor) ?>" <?= is_selected('eye_color', $cor, $anuncio_data) ?>><?= htmlspecialchars($cor) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" id="eye_color-feedback"></div>
                </div>
            </div>

            <!-- Linha 3: Gênero * Nacionalidade * Etnia* -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="gender" class="form-label fw-bold">Gênero <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required data-initial-value="<?= htmlspecialchars($anuncio_data['gender'] ?? $_POST['gender'] ?? '') ?>">
                        <option value="">Selecione</option>
                        <option value="Feminino" <?= is_selected('gender', 'Feminino', $anuncio_data) ?>>Mulher</option>
                        <option value="Masculino" <?= is_selected('gender', 'Masculino', $anuncio_data) ?>>Homem</option>
                        <option value="Trans" <?= is_selected('gender', 'Trans', $anuncio_data) ?>>Trans</option>
                    </select>
                    <div class="invalid-feedback" id="gender-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label for="nationality" class="form-label fw-bold">Nacionalidade <span class="text-danger">*</span></label>
                    <select class="form-select" id="nationality" name="nationality" required data-initial-value="<?= htmlspecialchars($anuncio_data['nationality'] ?? $_POST['nationality'] ?? '') ?>">
                        <option value="">Selecione </option>
                        <?php
                        $nacionalidades = [
                            "Brasileira", "Portuguesa", "Americana", "Argentina", "Chilena",
                            "Colombiana", "Espanhola", "Francesa", "Inglesa", "Italiana",
                            "Japonesa", "Mexicana", "Paraguaia", "Uruguaia", "Venezuelana",
                        ];
                        sort($nacionalidades);
                        foreach ($nacionalidades as $nacionalidade) : ?>
                            <option value="<?= htmlspecialchars($nacionalidade) ?>" <?= is_selected('nationality', $nacionalidade, $anuncio_data) ?>>
                                <?= htmlspecialchars($nacionalidade) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" id="nationality-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label for="ethnicity" class="form-label fw-bold">Etnia</label>
                    <select class="form-select" id="ethnicity" name="ethnicity">
                        <option value="">Selecione</option>
                        <?php
                        $etnias = ["Africana", "Asiática", "Caucasiana", "Indígena", "Latina", "Mestiça"];
                        foreach ($etnias as $etnia) : ?>
                            <option value="<?= htmlspecialchars($etnia) ?>" <?= is_selected('ethnicity', $etnia, $anuncio_data) ?>><?= htmlspecialchars($etnia) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" id="ethnicity-feedback"></div>
                </div>
            </div>

            <!-- Linha 4: Estado * Cidade* Bairro * -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="state_id" class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                    <select class="form-select" id="state_id" name="state_id" required data-initial-value="<?= htmlspecialchars($anuncio_data['state_uf'] ?? $_POST['state_id'] ?? '') ?>">
                        <option value="">Carregando Estados...</option>
                    </select>
                    <div class="invalid-feedback" id="state_id-feedback"></div>
                </div>

                <div class="col-md-4">
                    <label for="city_id" class="form-label fw-bold">Cidade <span class="text-danger">*</span></label>
                    <select class="form-select" id="city_id" name="city_id" disabled required data-initial-value="<?= htmlspecialchars($anuncio_data['city_code'] ?? $_POST['city_id'] ?? '') ?>">
                        <option value="">Selecione a Cidade</option>
                    </select>
                    <div class="invalid-feedback" id="city_id-feedback"></div>
                </div>

                <div class="col-md-4">
                    <label for="neighborhood_name" class="form-label fw-bold">Bairro <span class="text-danger">*</span></label>
                    <!-- CORREÇÃO AQUI: ID do input de bairro alterado para 'neighborhood_name' -->
                    <input type="text" class="form-control" id="neighborhood_name" name="neighborhood_name"
                                 placeholder="Selecione a Cidade primeiro" disabled required
                                 value="<?= htmlspecialchars($anuncio_data['neighborhood_name'] ?? $_POST['neighborhood_name'] ?? '') ?>"
                                 data-initial-value="<?= htmlspecialchars($anuncio_data['neighborhood_name'] ?? $_POST['neighborhood_name'] ?? '') ?>">
                    <div class="invalid-feedback" id="neighborhood_name-feedback"></div>
                </div>
            </div>

            <div class="mb-4">
                <label for="description" class="form-label fw-bold">Descrição sobre mim <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Conte um pouco sobre você..." required><?= htmlspecialchars($anuncio_data['description'] ?? $_POST['description'] ?? '') ?></textarea>
                <div class="invalid-feedback" id="description-feedback"></div>
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
            <div class="row mb-2" id="locais_atendimento-checkboxes">
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
            <div class="text-danger small mb-4" id="locais_atendimento-feedback"></div>

            <h4 class="mb-3 text-primary">Formas de Pagamento <span class="text-danger">*</span></h4>
            <small class="text-muted d-block mb-3">Selecione pelo menos 1 forma de pagamento.</small>
            <div class="row mb-2" id="formas_pagamento-checkboxes">
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
            <div class="text-danger small mb-4" id="formas_pagamento-feedback"></div>


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
                    "DESPEDIDAS DE SOLTEIROS", "ORGIAS", "SEXCAM", "STRAP ON"
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
                    <label for="price_15min" class="form-label fw-bold">15 minutos</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control price-mask" id="price_15min" name="price_15min" placeholder="0,00" value="<?= htmlspecialchars($anuncio_data['price_15min'] ?? ($_POST['price_15min'] ?? '')) ?>">
                    </div>
                    <div class="invalid-feedback" id="price_15min-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="price_30min" class="form-label fw-bold">30 minutos</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control price-mask" id="price_30min" name="price_30min" placeholder="0,00" value="<?= htmlspecialchars($anuncio_data['price_30min'] ?? ($_POST['price_30min'] ?? '')) ?>">
                    </div>
                    <div class="invalid-feedback" id="price_30min-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="price_1h" class="form-label fw-bold">1 Hora</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control price-mask" id="price_1h" name="price_1h" placeholder="0,00" value="<?= htmlspecialchars($anuncio_data['price_1h'] ?? ($_POST['price_1h'] ?? '')) ?>">
                    </div>
                    <div class="invalid-feedback" id="price_1h-feedback"></div>
                </div>
            </div>
            <div class="text-danger small mb-4" id="precos-feedback"></div>


            <h4 class="mb-3 text-primary">Mídia <span class="text-danger">*</span></h4>
            <!-- Seção do Vídeo de Demonstração (AJUSTADO PARA RETRATO) -->
            <?php if ($form_mode === 'create'): ?>
            <div class="mb-4">
                <label class="form-label fw-bold">Vídeo de Demonstração (Exemplo)</label>
                <!-- Contêiner com a nova classe CSS para dimensões de retrato -->
                <div class="demo-video-container">
                    <!-- Vídeo usa a nova classe CSS para object-fit -->
                    <video controls muted autoplay loop class="rounded shadow-sm media-fill-contain">
                        <source src="<?= URL ?>app/public/uploads/system_videos/fixed_nixcom_confirmation.mp4" type="video/mp4">
                        Seu navegador não suporta a tag de vídeo.
                    </video>
                </div>
                <small class="text-muted d-block text-start mt-2">Este é um vídeo de demonstração para te ajudar a criar o seu anúncio.</small>
            </div>
            <?php endif; ?>
            <!-- FIM Seção do Vídeo de Demonstração -->

            <hr class="my-4">

            <!-- Seção do Vídeo de Confirmação do Usuário (AJUSTADO PARA RETRATO) -->
            <div class="mb-3">
                <label class="form-label fw-bold">Vídeo de Confirmação (Seu Vídeo) <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3 justify-content-start">
                    <!-- Definido width e height fixos para o photo-upload-box para retrato usando a nova classe -->
                    <div class="photo-upload-box video-confirmation-box" id="confirmationVideoUploadBox">
                        <!-- REMOVIDO o atributo 'required' daqui -->
                        <input type="file" id="confirmation_video_input" name="confirmation_video" accept="video/*" class="d-none">
                        <!-- Hidden input para sinalizar remoção de vídeo existente -->
                        <input type="hidden" name="confirmation_video_removed" id="confirmation_video_removed" value="false">
                        <?php if (isset($anuncio_data['confirmation_video_path']) && !empty($anuncio_data['confirmation_video_path'])): ?>
                            <input type="hidden" name="existing_confirmation_video_path" value="<?= htmlspecialchars($anuncio_data['confirmation_video_path']) ?>">
                            <!-- REMOVIDO src do PHP, será preenchido pelo JS -->
                            <video id="confirmationVideoPreview" alt="Pré-visualização do vídeo de confirmação" class="photo-preview rounded mx-auto d-block media-fill-contain"></video>
                        <?php else: ?>
                            <!-- REMOVIDO src do PHP, será preenchido pelo JS -->
                            <video id="confirmationVideoPreview" alt="Pré-visualização do vídeo de confirmação" class="photo-preview rounded mx-auto d-block media-fill-contain"></video>
                        <?php endif; ?>
                        <!-- O placeholder também precisa se ajustar à altura do contêiner -->
                        <div class="upload-placeholder">
                            <i class="fas fa-video fa-2x"></i>
                            <p>Seu Vídeo de Confirmação</p>
                        </div>
                        <button type="button" class="btn-remove-photo <?= isset($anuncio_data['confirmation_video_path']) && !empty($anuncio_data['confirmation_video_path']) ? '' : 'd-none' ?>">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
                <small class="text-muted">Um vídeo curto de confirmação é obrigatório para o seu anúncio.</small>
                <div class="text-danger small mt-2" id="confirmationVideo-feedback"></div>
            </div>
            <!-- FIM Seção do Vídeo de Confirmação do Usuário -->

            <hr class="my-4">

            <!-- Seção da Foto da Capa -->
            <div class="mb-3">
                <label class="form-label fw-bold">Foto da Capa <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3 justify-content-start">
                    <div class="photo-upload-box" id="coverPhotoUploadBox">
                        <input type="file" id="foto_capa_input" name="foto_capa" accept="image/*" class="d-none">
                        <input type="hidden" name="cover_photo_removed" id="cover_photo_removed" value="false">
                        <?php if (isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path'])): ?>
                            <input type="hidden" name="existing_cover_photo_path" value="<?= htmlspecialchars($anuncio_data['cover_photo_path']) ?>">
                            <!-- REMOVIDO src do PHP, será preenchido pelo JS -->
                            <img id="coverPhotoPreview" alt="Pré-visualização da foto da capa" class="photo-preview rounded mx-auto d-block">
                        <?php else: ?>
                            <!-- REMOVIDO src do PHP, será preenchido pelo JS -->
                            <img id="coverPhotoPreview" alt="Pré-visualização da foto da capa" class="photo-preview rounded mx-auto d-block">
                        <?php endif; ?>
                        <div class="upload-placeholder">
                            <i class="fas fa-camera fa-2x"></i>
                            <p>Foto da Capa</p>
                        </div>
                        <button type="button" class="btn-remove-photo <?= isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path']) ? '' : 'd-none' ?>">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
                <small class="text-muted">A foto da capa é a imagem principal do seu anúncio.</small>
                <div class="text-danger small mt-2" id="coverPhoto-feedback"></div>
            </div>
            <!-- FIM Seção da Foto da Capa -->

            <hr class="my-4">

            <!-- Seção da Galeria de Fotos -->
            <div class="mb-3">
                <label class="form-label fw-bold">Galeria de Fotos <span class="text-danger">*</span></label>
                <small class="text-muted d-block mb-2">Adicione fotos para sua galeria. Mínimo de 1 foto. Plano Gratuito: 1 foto. Plano Premium: até 20 fotos.</small>
                <div class="row g-3" id="galleryPhotoContainer">
                    <?php
                    $max_gallery_photos = 20; // Limite máximo para o plano premium
                    for ($i = 0; $i < $max_gallery_photos; $i++) :
                        $photo_path = $anuncio_data['fotos_galeria'][$i] ?? '';
                    ?>
                        <div class="col-auto">
                            <div class="photo-upload-box gallery-upload-box">
                                <input type="file" id="gallery_photo_input_<?= $i ?>" name="fotos_galeria_upload_<?= $i ?>" accept="image/*" class="d-none">
                                <input type="hidden" name="existing_gallery_paths[]" value="<?= htmlspecialchars($photo_path) ?>">
                                <img id="galleryPhotoPreview_<?= $i ?>" alt="Pré-visualização da foto da galeria <?= $i + 1 ?>" class="photo-preview rounded mx-auto d-block">
                                <div class="upload-placeholder">
                                    <i class="fas fa-image fa-2x"></i>
                                    <p>Foto <?= $i + 1 ?></p>
                                </div>
                                <button type="button" class="btn-remove-photo <?= !empty($photo_path) ? '' : 'd-none' ?>">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <div class="premium-lock-overlay" style="display: none;">
                                    <i class="fas fa-lock"></i>
                                    <p>Exclusivo para Plano Premium</p>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="text-danger small mt-2" id="galleryPhotoContainer-feedback"></div>
            </div>
            <!-- FIM Seção da Galeria de Fotos -->

            <hr class="my-4">

            <!-- Seção de Vídeos (Plano Premium) -->
            <div class="mb-3">
                <label class="form-label fw-bold">Vídeos</label>
                <small class="text-muted d-block mb-2">Adicione vídeos ao seu anúncio. Disponível apenas para Planos Premium (até 3 vídeos).</small>
                <div class="row g-3" id="videoUploadBoxes">
                    <?php
                    $max_videos = 3;
                    for ($i = 0; $i < $max_videos; $i++) :
                        $video_path = $anuncio_data['videos'][$i] ?? '';
                    ?>
                        <div class="col-auto">
                            <div class="photo-upload-box video-upload-box">
                                <input type="file" id="video_input_<?= $i ?>" name="videos_upload_<?= $i ?>" accept="video/mp4,video/webm" class="d-none">
                                <input type="hidden" name="existing_video_paths[]" value="<?= htmlspecialchars($video_path) ?>">
                                <video id="videoPreview_<?= $i ?>" alt="Pré-visualização do vídeo <?= $i + 1 ?>" class="photo-preview rounded mx-auto d-block media-fill-contain" controls></video>
                                <div class="upload-placeholder">
                                    <i class="fas fa-video fa-2x"></i>
                                    <p>Vídeo <?= $i + 1 ?></p>
                                </div>
                                <button type="button" class="btn-remove-photo <?= !empty($video_path) ? '' : 'd-none' ?>">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <div class="premium-lock-overlay" style="display: none;">
                                    <i class="fas fa-lock"></i>
                                    <p>Exclusivo para Plano Premium</p>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="text-danger small mt-2" id="videoUploadBoxes-feedback"></div>
            </div>
            <!-- FIM Seção de Vídeos -->

            <hr class="my-4">

            <!-- Seção de Áudios (Plano Premium) -->
            <div class="mb-3">
                <label class="form-label fw-bold">Áudios</label>
                <small class="text-muted d-block mb-2">Adicione áudios ao seu anúncio. Disponível apenas para Planos Premium (até 3 áudios).</small>
                <div class="row g-3" id="audioUploadBoxes">
                    <?php
                    $max_audios = 3;
                    for ($i = 0; $i < $max_audios; $i++) :
                        $audio_path = $anuncio_data['audios'][$i] ?? '';
                    ?>
                        <div class="col-auto">
                            <div class="photo-upload-box audio-upload-box">
                                <input type="file" id="audio_input_<?= $i ?>" name="audios_upload_<?= $i ?>" accept="audio/*" class="d-none">
                                <input type="hidden" name="existing_audio_paths[]" value="<?= htmlspecialchars($audio_path) ?>">
                                <audio id="audioPreview_<?= $i ?>" alt="Pré-visualização do áudio <?= $i + 1 ?>" class="photo-preview rounded mx-auto d-block" controls></audio>
                                <div class="upload-placeholder">
                                    <i class="fas fa-music fa-2x"></i>
                                    <p>Adicionar Áudio</p>
                                </div>
                                <button type="button" class="btn-remove-photo <?= !empty($audio_path) ? '' : 'd-none' ?>">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <div class="premium-lock-overlay" style="display: none;">
                                    <i class="fas fa-lock"></i>
                                    <p>Exclusivo para Plano Premium</p>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="text-danger small mt-2" id="audioUploadBoxes-feedback"></div>
            </div>
            <!-- FIM Seção de Áudios -->

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-lg w-auto" id="btnSubmitAnuncio">
                    <?= $submit_button_text ?>
                </button>
            </div>
        </form>

        <?php if ($user_role === 'admin' && $form_mode === 'edit' && isset($anuncio_data['id'])): ?>
            <hr class="my-5">
            <div class="admin-actions text-center">
                <h4 class="mb-4 text-primary">Ações do Administrador</h4>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <!-- Botões de Ação do Administrador -->
                    <button type="button" class="btn btn-success btn-lg" id="btnApproveAnuncio" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>" data-anunciante-user-id="<?= htmlspecialchars($anuncio_data['user_id'] ?? '') ?>">
                        <i class="fas fa-check-circle me-2"></i>Aprovar Anúncio
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="btnRejectAnuncio" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>" data-anunciante-user-id="<?= htmlspecialchars($anuncio_data['user_id'] ?? '') ?>">
                        <i class="fas fa-times-circle me-2"></i>Reprovar Anúncio
                    </button>
                    <!-- NOVO BOTÃO: VISUALIZAR ANÚNCIO -->
                    <!-- Adicionado aqui, antes do botão de Excluir, para melhor organização visual -->
                    <a href="#" class="btn btn-primary btn-lg" id="btnVisualizarAnuncio" data-spa="true" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>">
                        <i class="fas fa-eye me-2"></i>Visualizar Anúncio
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-lg" id="btnDeleteAnuncio" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>" data-anunciante-user-id="<?= htmlspecialchars($anuncio_data['user_id'] ?? '') ?>">
                        <i class="fas fa-trash-alt me-2"></i>Excluir Anúncio
                    </button>
                    <?php
                    // Lógica para o botão de Pausar/Ativar para o Admin (se o anúncio já tiver um status)
                    $current_anuncio_status = $anuncio_data['status'] ?? 'not_found';
                    if ($current_anuncio_status === 'active') {
                        echo '<button type="button" class="btn btn-warning btn-lg" id="btnDeactivateAnuncio" data-anuncio-id="' . htmlspecialchars($anuncio_data['id']) . '" data-anunciante-user-id="' . htmlspecialchars($anuncio_data['user_id'] ?? '') . '">';
                        echo '<i class="fas fa-pause-circle me-2"></i>Pausar Anúncio';
                        echo '</button>';
                    } elseif ($current_anuncio_status === 'inactive') {
                        echo '<button type="button" class="btn btn-info btn-lg" id="btnActivateAnuncio" data-anuncio-id="' . htmlspecialchars($anuncio_data['id']) . '" data-anunciante-user-id="' . htmlspecialchars($anuncio_data['user_id'] ?? '') . '">';
                        echo '<i class="fas fa-play-circle me-2"></i>Ativar Anúncio';
                        echo '</button>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- O script JS anuncio.js será carregado via main.php ou dashboard_custom.js -->
<!-- Certifique-se de que jquery.mask.min.js NÃO esteja sendo carregado em nenhum lugar. -->
