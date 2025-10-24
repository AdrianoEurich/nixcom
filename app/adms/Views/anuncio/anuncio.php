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
?>

<style>
/* CSS DE EMERGÊNCIA PARA PREVIEWS - FORÇA EXIBIÇÃO */
.photo-preview[src]:not([src=""]),
video.photo-preview[src]:not([src=""]),
audio.photo-preview[src]:not([src=""]),
img.photo-preview[src]:not([src=""]) {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    object-fit: cover !important;
    border-radius: 6px !important;
}

#confirmationVideoPreview[src],
#coverPhotoPreview[src] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    object-fit: contain !important;
    max-width: 100% !important;
    max-height: 200px !important;
    border-radius: 6px !important;
}

.photo-preview.d-block {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.photo-upload-box .photo-preview[src]:not([src=""]) {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* FORÇA EXIBIÇÃO IMEDIATA */
.photo-upload-box {
    position: relative !important;
}

.photo-upload-box .photo-preview {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 10 !important;
}

/* Tipografia do título do formulário igual ao modal */
.form-title-section h1#formAnuncioTitle {
    font-family: 'Poppins', sans-serif !important;
    font-weight: 700 !important;
    letter-spacing: 0.3px;
}
.form-title-section p {
    font-family: 'Poppins', sans-serif !important;
    font-weight: 500 !important;
}

/* Aplicar Poppins em todo o formulário */
.form-container {
    font-family: 'Poppins', sans-serif !important;
}
.form-container h1,
.form-container h2,
.form-container h3,
.form-container h4,
.form-container h5,
.form-container h6,
.form-container p,
.form-container small,
.form-container label,
.form-container .form-label,
.form-container .form-text,
.form-container .form-control,
.form-container .form-select,
.form-container .input-group-text,
.form-container .btn,
.form-container .checkbox-item label,
.form-container .radio-item label {
    font-family: 'Poppins', sans-serif !important;
}

/* Checkbox items clicáveis em toda a área */
.checkbox-group .checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 2px solid #adb5bd;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    user-select: none;
}
.checkbox-group .checkbox-item:hover {
    background: #f8f9fa;
}
.checkbox-group .checkbox-item.active {
    background: #eef2ff;
    border-color: #0d6efd;
    border-width: 2px;
}
.checkbox-group .checkbox-item input[type="checkbox"] {
    /* Forçar estilização consistente */
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border: 2px solid #000000 !important; /* borda preta forte */
    border-radius: 3px;
    background: #ffffff;
    display: inline-block;
    position: relative;
    margin: 0 8px 0 0;
    vertical-align: middle;
}
.checkbox-group .checkbox-item input[type="checkbox"]:checked {
    background-color: #0d6efd; /* azul ao marcar (como antes) */
    border-color: #0d6efd;
}
.checkbox-group .checkbox-item input[type="checkbox"]:checked::after {
    content: '';
    position: absolute;
    left: 4px;
    top: 0px;
    width: 6px;
    height: 12px;
    border: solid #ffffff; /* check branco visível no fundo preto */
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.checkbox-group .checkbox-item label { cursor: pointer; }
</style>


<?php

// As variáveis $user_plan_type, $has_anuncio, $anuncio_data, $form_mode
// são esperadas para serem definidas via extract($this->data) em ConfigViewAdm.
// Se, por algum motivo, elas não estiverem definidas (o que não deveria acontecer
// se o controlador e ConfigViewAdm estiverem funcionando), elas serão inicializadas
// com valores padrão via operador ?? (null coalescing operator).
$user_plan_type = $user_plan_type ?? 'free';
$has_anuncio = $has_anuncio ?? false;
$anuncio_data = $anuncio_data ?? [];
$form_mode = $form_mode ?? 'create'; // 'create' ou 'edit'
$user_role = strtolower($_SESSION['user_role'] ?? 'normal');

// Define a URL de ação do formulário e o texto do botão com base no modo
$form_action = ($form_mode === 'edit') ? URLADM . 'anuncio/updateAnuncio' : URLADM . 'anuncio/createAnuncio';
// O texto do botão será definido pelo JS, mas mantemos um fallback aqui se o JS falhar
$submit_button_text = ($form_mode === 'edit') ? 'ATUALIZAR ANÚNCIO' : 'CRIAR ANÚNCIO';
// O título do formulário será definido pelo JS. O PHP apenas define o fallback.
$form_title = ($form_mode === 'edit') ? 'EDITAR ANÚNCIO' : 'CRIAR NOVO ANÚNCIO';
$title_icon_class = ($form_mode === 'edit') ? 'fas fa-crown' : 'fas fa-gem'; // PHP define o ícone correto para o modo

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

<!-- Formulário Sensual e Elegante -->
<div class="form-container" data-page-type="form">
    <!-- Cabeçalho Sensual -->
    <div class="form-header">
        <div class="form-title-section">
            <h1 id="formAnuncioTitle"><?= $form_title ?></h1>
            <p>Crie seu anúncio elegante e atraente com nosso formulário sofisticado</p>
        </div>
    </div>
    
    <!-- Conteúdo Elegante -->
    <div class="form-content">
        <!-- CORREÇÃO AQUI: ID do formulário alterado para 'formAnuncio' -->
        <form id="formAnuncio" action="<?= $form_action ?>" method="POST" enctype="multipart/form-data"
              data-user-plan-type="<?= htmlspecialchars($user_plan_type) ?>"
              data-form-mode="<?= htmlspecialchars($form_mode) ?>"
              data-anuncio-data="<?= htmlspecialchars(json_encode($anuncio_data)) ?>"
              data-anuncio-id="<?= htmlspecialchars($anuncio_data['id'] ?? '') ?>">

            <?php if ($form_mode === 'edit' && isset($anuncio_data['id'])): ?>
                <input type="hidden" name="anuncio_id" value="<?= htmlspecialchars($anuncio_data['id']) ?>">
            <?php endif; ?>
            
            <?php
            // Proteção CSRF - Temporariamente desabilitada para debug
            // require_once __DIR__ . '/../../CoreAdm/Helpers/CsrfHelper.php';
            // echo \CoreAdm\Helpers\CsrfHelper::generateHiddenField();
            ?>

            <!-- Seção de Informações Básicas -->
            <div class="form-section">
                <h4>Informações Básicas</h4>
            <!-- Linha 1: Nome * Idade * Telefone * -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="service_name" class="form-label fw-bold">Nome <span class="text-danger">*</span></label>
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
            </div>

            <!-- Seção de Aparência -->
            <div class="form-section">
                <h4>Sobre Mim (Aparência) <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 item de aparência.</small>
                <div class="checkbox-group" id="aparencia-checkboxes">
                    <?php
                    $aparencia = [
                        "Magra", "Peito natural", "Siliconada", "Peitos grande", "Peitos pequeno",
                        "Depilada", "Peluda", "Alta", "Baixa", "Mignon", "Ruiva", "Morena", "Gordinha",
                        "Loira", "Pele Morena"
                    ];
                    foreach ($aparencia as $item) {
                        $id_aparencia = 'aparencia_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $item)));
                        echo '<div class="checkbox-item">';
                        echo '<input type="checkbox" value="' . htmlspecialchars($item) . '" id="' . $id_aparencia . '" name="aparencia[]" ' . is_checked('aparencia', $item, $anuncio_data) . '>';
                        echo '<label for="' . $id_aparencia . '">' . htmlspecialchars($item) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="text-danger small mb-4" id="aparencia-feedback"></div>
            </div>

            <!-- Seção de Idiomas -->
            <div class="form-section">
                <h4>Idiomas <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 idioma.</small>
                <div class="checkbox-group" id="idiomas-checkboxes">
                    <?php
                    $idiomas = ["Português", "Inglês", "Espanhol"];
                    foreach ($idiomas as $idioma) {
                        $id_idioma = 'idioma_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $idioma)));
                        echo '<div class="checkbox-item">';
                        echo '<input type="checkbox" value="' . htmlspecialchars($idioma) . '" id="' . $id_idioma . '" name="idiomas[]" ' . is_checked('idiomas', $idioma, $anuncio_data) . '>';
                        echo '<label for="' . $id_idioma . '">' . htmlspecialchars($idioma) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="text-danger small mb-4" id="idiomas-feedback"></div>
            </div>

            <!-- Seção de Local de Atendimento -->
            <div class="form-section">
                <h4>Local de Atendimento <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 local.</small>
                <div class="checkbox-group" id="locais_atendimento-checkboxes">
                    <?php
                    $locais = ["Hotel", "Motel", "A domicílio", "Com Local"];
                    foreach ($locais as $local) {
                        $id_local = 'local_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $local)));
                        echo '<div class="checkbox-item">';
                        echo '<input type="checkbox" value="' . htmlspecialchars($local) . '" id="' . $id_local . '" name="locais_atendimento[]" ' . is_checked('locais_atendimento', $local, $anuncio_data) . '>';
                        echo '<label for="' . $id_local . '">' . htmlspecialchars($local) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="text-danger small mb-4" id="locais_atendimento-feedback"></div>
            </div>

            <!-- Seção de Formas de Pagamento -->
            <div class="form-section">
                <h4>Formas de Pagamento <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 forma de pagamento.</small>
                <div class="checkbox-group" id="formas_pagamento-checkboxes">
                    <?php
                    $pagamentos = ["Dinheiro", "Pix", "Cartão de Crédito"];
                    foreach ($pagamentos as $pagamento) {
                        $id_pagamento = 'pagamento_' . str_replace([' ', '/', '-'], '', mb_strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $pagamento)));
                        echo '<div class="checkbox-item">';
                        echo '<input type="checkbox" value="' . htmlspecialchars($pagamento) . '" id="' . $id_pagamento . '" name="formas_pagamento[]" ' . is_checked('formas_pagamento', $pagamento, $anuncio_data) . '>';
                        echo '<label for="' . $id_pagamento . '">' . htmlspecialchars($pagamento) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="text-danger small mb-4" id="formas_pagamento-feedback"></div>
            </div>

            <!-- Seção de Serviços Oferecidos -->
            <div class="form-section">
                <h4>Serviços Oferecidos <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 2 serviços.</small>
                <div class="checkbox-group" id="servicos-checkboxes">
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
                        echo '<div class="checkbox-item">';
                        echo '<input type="checkbox" value="' . htmlspecialchars($item) . '" id="' . $id_servico . '" name="servicos[]" ' . is_checked('servicos', $item, $anuncio_data) . '>';
                        echo '<label for="' . $id_servico . '">' . htmlspecialchars($item) . '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="text-danger small mb-4" id="servicos-feedback"></div>
            </div>

            <!-- Seção de Preços -->
            <div class="form-section">
                <h4>Preços <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Preencha pelo menos um preço.</small>
                <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                        <label for="price_15min" class="form-label fw-bold">15 minutos</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control price-mask" id="price_15min" name="price_15min" placeholder="0,00" value="">
                        </div>
                        <div class="invalid-feedback" id="price_15min-feedback"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_30min" class="form-label fw-bold">30 minutos</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control price-mask" id="price_30min" name="price_30min" placeholder="0,00" value="">
                        </div>
                        <div class="invalid-feedback" id="price_30min-feedback"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="price_1h" class="form-label fw-bold">1 Hora</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control price-mask" id="price_1h" name="price_1h" placeholder="0,00" value="">
                        </div>
                        <div class="invalid-feedback" id="price_1h-feedback"></div>
                    </div>
                </div>
                <div class="text-danger small mb-4" id="precos-feedback"></div>
            </div>

            <!-- Seção de Mídia -->
            <div class="form-section">
                <h4>Mídia <span class="text-danger">*</span></h4>
                <div class="mb-4">
                    <label class="form-label fw-bold">Vídeo de Demonstração (Exemplo)</label>
                    <div class="demo-video-container">
                        <video controls muted autoplay loop class="rounded shadow-sm media-fill-contain">
                            <source src="<?= URL ?>app/public/uploads/system_videos/fixed_nixcom_confirmation.mp4" type="video/mp4">
                            Seu navegador não suporta a tag de vídeo.
                        </video>
                    </div>
                    <small class="text-muted d-block text-start mt-2">Este é um vídeo de demonstração para te ajudar a criar o seu anúncio.</small>
                </div>

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label fw-bold">Vídeo de Confirmação (Seu Vídeo) <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3 justify-content-start">
                    <div class="photo-upload-box video-confirmation-box" id="confirmationVideoUploadBox">
                        <input type="file" id="confirmation_video_input" name="confirmation_video" accept="video/*" class="d-none">
                        <input type="hidden" name="confirmation_video_removed" id="confirmation_video_removed" value="false">
                        <?php if (isset($anuncio_data['confirmation_video_path']) && !empty($anuncio_data['confirmation_video_path'])): ?>
                            <input type="hidden" name="existing_confirmation_video_path" value="<?= htmlspecialchars($anuncio_data['confirmation_video_path']) ?>">
                        <?php endif; ?>
                        <video id="confirmationVideoPreview" alt="" class="photo-preview rounded mx-auto d-block media-fill-contain"></video>
                        <div class="upload-placeholder">
                            <i class="fas fa-video fa-2x"></i>
                            <p>Seu Vídeo de Confirmação</p>
                        </div>
                        <button type="button" class="btn-remove-photo <?= isset($anuncio_data['confirmation_video_path']) && !empty($anuncio_data['confirmation_video_path']) ? '' : 'd-none' ?>">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
                <small class="text-muted">Um vídeo curto de confirmação é obrigatório para o seu anúncio. <strong>Tamanho máximo: 100MB</strong></small>
                <div class="text-danger small mt-2" id="confirmationVideo-feedback"></div>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label fw-bold">Foto da Capa <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3 justify-content-start">
                    <div class="photo-upload-box" id="coverPhotoUploadBox">
                        <input type="file" id="foto_capa_input" name="foto_capa" accept="image/*" class="d-none">
                        <input type="hidden" name="cover_photo_removed" id="cover_photo_removed" value="false">
                        <?php if (isset($anuncio_data['cover_photo_path']) && !empty($anuncio_data['cover_photo_path'])): ?>
                            <input type="hidden" name="existing_cover_photo_path" value="<?= htmlspecialchars($anuncio_data['cover_photo_path']) ?>">
                        <?php endif; ?>
                        <img id="coverPhotoPreview" alt="" class="photo-preview rounded mx-auto d-block">
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

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label fw-bold">Galeria de Fotos <span class="text-danger">*</span></label>
                <small class="text-muted d-block mb-2">Adicione fotos para sua galeria. <strong>Plano FREE:</strong> até 2 fotos. <strong>Plano BASIC:</strong> até 20 fotos. <strong>Plano PREMIUM:</strong> até 20 fotos. <strong>Tamanho máximo: 32MB por foto</strong></small>
                <div class="row g-3" id="galleryPhotoContainer">
                    <?php
                    $max_gallery_photos = 20;
                    for ($i = 0; $i < $max_gallery_photos; $i++) :
                        $photo_path = $anuncio_data['fotos_galeria'][$i] ?? '';
                    ?>
                        <div class="col-auto">
                            <div class="photo-upload-box gallery-upload-box">
                                <input type="file" id="gallery_photo_input_<?= $i ?>" name="fotos_galeria_upload_<?= $i ?>" accept="image/*" class="d-none">
                                <input type="hidden" name="existing_gallery_paths[]" value="<?= htmlspecialchars($photo_path) ?>">
                                <img id="galleryPhotoPreview_<?= $i ?>" alt="" class="photo-preview rounded mx-auto d-block">
                                <div class="upload-placeholder">
                                    <i class="fas fa-image fa-2x"></i>
                                </div>
                                <button type="button" class="btn-remove-photo <?= !empty($photo_path) ? '' : 'd-none' ?>">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <div class="premium-lock-overlay" style="display: none;">
                                    <i class="fas fa-lock"></i>
                                    <p>Exclusivo para Planos Pagos</p>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="text-danger small mt-2" id="galleryPhotoContainer-feedback"></div>
            </div>

            <hr class="my-4">

            <?php if ($user_plan_type === 'premium'): ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Vídeos</label>
                <small class="text-muted d-block mb-2">Adicione vídeos ao seu anúncio. <strong>Disponível apenas para Plano PREMIUM</strong> (até 3 vídeos). <strong>Tamanho máximo: 100MB por vídeo</strong></small>
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
                                <video id="videoPreview_<?= $i ?>" alt="" class="photo-preview rounded mx-auto d-block media-fill-contain" controls <?= !empty($video_path) ? 'src="' . htmlspecialchars($video_path) . '" style="display: block !important;"' : 'style="display: none;"' ?>></video>
                                <div class="upload-placeholder">
                                    <i class="fas fa-video fa-2x"></i>
                                </div>
                                <button type="button" class="btn-remove-photo <?= !empty($video_path) ? '' : 'd-none' ?>" <?= !empty($video_path) ? 'style="display: block !important;"' : '' ?>>
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
            <?php endif; ?>

            <hr class="my-4">

            <?php if ($user_plan_type === 'premium'): ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Áudios</label>
                <small class="text-muted d-block mb-2">Adicione áudios ao seu anúncio. <strong>Disponível apenas para Plano PREMIUM</strong> (até 3 áudios). <strong>Tamanho máximo: 32MB por áudio</strong></small>
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
                                <audio id="audioPreview_<?= $i ?>" alt="" class="photo-preview rounded mx-auto d-block" controls <?= !empty($audio_path) ? 'src="' . htmlspecialchars($audio_path) . '" style="display: block !important;"' : 'style="display: none;"' ?>></audio>
                                <div class="upload-placeholder">
                                    <i class="fas fa-music fa-2x"></i>
                                </div>
                                <button type="button" class="btn-remove-photo <?= !empty($audio_path) ? '' : 'd-none' ?>" <?= !empty($audio_path) ? 'style="display: block !important;"' : '' ?>>
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
            <?php endif; ?>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-lg" id="btnSubmitAnuncio" style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                    transition: all 0.3s ease;
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 35px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 25px rgba(102, 126, 234, 0.3)'">
                    <i class="fas fa-plus-circle me-2"></i>
                    <?= $submit_button_text ?>
                </button>
            </div>
        </form>

        <?php if (($user_role === 'admin' || $user_role === 'administrador') && $form_mode === 'edit' && isset($anuncio_data['id'])): ?>
            <?php 
            // Função para capturar IP real
            function getRealIP() {
                $ip_keys = [
                    'HTTP_X_FORWARDED_FOR',
                    'HTTP_X_REAL_IP', 
                    'HTTP_CLIENT_IP',
                    'HTTP_X_FORWARDED',
                    'HTTP_FORWARDED_FOR',
                    'HTTP_FORWARDED',
                    'REMOTE_ADDR'
                ];
                
                foreach ($ip_keys as $key) {
                    if (!empty($_SERVER[$key])) {
                        $ip = $_SERVER[$key];
                        // Se há múltiplos IPs (proxy), pegar o primeiro
                        if (strpos($ip, ',') !== false) {
                            $ip = trim(explode(',', $ip)[0]);
                        }
                        // Validar se é um IP válido
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                            return $ip;
                        }
                    }
                }
                
                // Fallback para REMOTE_ADDR
                return $_SERVER['REMOTE_ADDR'] ?? 'N/A';
            }
            
            $real_ip = getRealIP();
            error_log("DEBUG IP REAL: " . $real_ip);
            ?>
            <hr class="my-5">
            
            <!-- Área Administrativa -->
            <div class="admin-section" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); border-radius: 20px; padding: 30px; margin-bottom: 25px; border: none; box-shadow: 0 10px 40px rgba(44, 62, 80, 0.3);">
                <h3 class="text-center mb-5" style="color: #D4AF37; font-size: 24px; font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">🔧 Área Administrativa</h3>
                
                <!-- Informações do Plano -->
                <div class="sidebar-section mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 30px; margin-bottom: 25px; border: none; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 45px rgba(102, 126, 234, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 32px rgba(102, 126, 234, 0.4)'">
                    <h4 style="color: #fff; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">📋 Informações do Plano</h4>
                    <div class="info-table">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">Tipo de Plano:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: <?= $anuncio_data['plan_type'] === 'premium' ? '#FFD700' : ($anuncio_data['plan_type'] === 'basic' ? '#87CEEB' : '#D3D3D3') ?>; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                    <?= strtoupper($anuncio_data['plan_type'] ?? 'N/A') ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">Limites do Plano:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                    <?php 
                                    $tipoPlano = $anuncio_data['plan_type'] ?? 'free';
                                    if ($tipoPlano === 'free'): 
                                        echo 'Foto de capa + 2 fotos na galeria';
                                    elseif ($tipoPlano === 'basic'): 
                                        echo 'Foto de capa + 20 fotos na galeria';
                                    elseif ($tipoPlano === 'premium'): 
                                        echo 'Foto de capa + 20 fotos na galeria + 3 vídeos + 3 áudios';
                                    endif; 
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Administrativos -->
                <div class="sidebar-section mb-4" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border-radius: 20px; padding: 30px; margin-bottom: 25px; border: none; box-shadow: 0 8px 32px rgba(231, 76, 60, 0.4); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 45px rgba(231, 76, 60, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 32px rgba(231, 76, 60, 0.4)'">
                    <h4 style="color: #fff; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">📊 Dados Administrativos</h4>
                    <div class="info-table">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">ID do Anúncio:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($anuncio_data['id'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">ID do Usuário:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($anuncio_data['user_id'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">Anunciante:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($anuncio_data['service_name'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">Nome:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                    <?= htmlspecialchars($anuncio_data['nome'] ?? 'N/A') ?>
                                    <?php if (!empty($anuncio_data['email'])): ?>
                                        <small class="text-light" style="opacity:0.9;"> (<?= htmlspecialchars($anuncio_data['email']) ?>)</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">Estado:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($anuncio_data['estado'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">IP de Registro:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: #fff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($anuncio_data['user_registration_ip'] ?? $real_ip) ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong style="color: rgba(255,255,255,0.9); font-size: 16px;">Status Atual:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span style="color: <?= $anuncio_data['status'] === 'active' ? '#90EE90' : ($anuncio_data['status'] === 'pending' ? '#FFD700' : '#FFB6C1') ?>; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                    <?= strtoupper($anuncio_data['status'] ?? 'N/A') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-actions text-center">
                <h4 class="mb-4 text-primary">Ações do Administrador</h4>
                
                <!-- Informações Administrativas Resumidas -->
                <div class="admin-info mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 25px; margin-bottom: 20px; border: none; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);">
                    <h5 class="text-center mb-4" style="color: #fff; font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">📊 Informações Administrativas</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="info-card" style="text-align: center; padding: 20px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 15px; border: none; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); transform: translateY(0);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(40, 167, 69, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(40, 167, 69, 0.4)'">
                                <i class="fas fa-calendar-plus mb-3" style="font-size: 32px; display: block; color: #fff; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>
                                <h6 class="mb-2" style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; color: rgba(255,255,255,0.9); font-weight: 600;">Data de Criação</h6>
                                <p class="mb-0 fw-bold" style="font-size: 16px; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= date('d/m/Y H:i', strtotime($anuncio_data['created_at'])) ?></p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-card" style="text-align: center; padding: 20px; background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); border-radius: 15px; border: none; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4); transform: translateY(0);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(255, 193, 7, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(255, 193, 7, 0.4)'">
                                <i class="fas fa-edit mb-3" style="font-size: 32px; display: block; color: #fff; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>
                                <h6 class="mb-2" style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; color: rgba(255,255,255,0.9); font-weight: 600;">Última Modificação</h6>
                                <p class="mb-0 fw-bold" style="font-size: 16px; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= !empty($anuncio_data['updated_at']) ? date('d/m/Y H:i', strtotime($anuncio_data['updated_at'])) : 'Nunca modificado' ?></p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-card" style="text-align: center; padding: 20px; background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); border-radius: 15px; border: none; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4); transform: translateY(0);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(23, 162, 184, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(23, 162, 184, 0.4)'">
                                <i class="fas fa-globe mb-3" style="font-size: 32px; display: block; color: #fff; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>
                                <h6 class="mb-2" style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; color: rgba(255,255,255,0.9); font-weight: 600;">IP de Registro</h6>
                                <p class="mb-0 fw-bold" style="font-size: 16px; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($anuncio_data['user_registration_ip'] ?? $real_ip) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <!-- Botões de Ação do Administrador -->
                    <button type="button" class="btn btn-success btn-lg" id="btnApproveAnuncio" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>" data-anunciante-user-id="<?= htmlspecialchars($anuncio_data['user_id'] ?? '') ?>">
                        Aprovar Anúncio
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="btnRejectAnuncio" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>" data-anunciante-user-id="<?= htmlspecialchars($anuncio_data['user_id'] ?? '') ?>">
                        Reprovar Anúncio
                    </button>
                    <a href="#" class="btn btn-primary btn-lg" id="btnVisualizarAnuncio" data-spa="true" data-anuncio-id="<?= htmlspecialchars($anuncio_data['id']) ?>">
Visualizar Anúncio
                    </a>
                    <!-- AJUSTE: Botão "Excluir Anúncio" foi substituído por "Excluir Conta" -->
                    <button type="button" class="btn btn-outline-danger btn-lg" id="btnDeleteAccount" data-anunciante-user-id="<?= htmlspecialchars($anuncio_data['user_id'] ?? '') ?>">
                        Excluir Conta
                    </button>
                    <?php
                    $current_anuncio_status = $anuncio_data['status'] ?? 'not_found';
                    if ($current_anuncio_status === 'active') {
                        echo '<button type="button" class="btn btn-warning btn-lg" id="btnDeactivateAnuncio" data-anuncio-id="' . htmlspecialchars($anuncio_data['id']) . '" data-anunciante-user-id="' . htmlspecialchars($anuncio_data['user_id'] ?? '') . '">';
                        echo 'Pausar Anúncio';
                        echo '</button>';
                    } elseif ($current_anuncio_status === 'pausado') {
                        echo '<button type="button" class="btn btn-info btn-lg" id="btnActivateAnuncio" data-anuncio-id="' . htmlspecialchars($anuncio_data['id']) . '" data-anunciante-user-id="' . htmlspecialchars($anuncio_data['user_id'] ?? '') . '">';
                        echo 'Ativar Anúncio';
                        echo '</button>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Feedback removido (usar modal global definido no layout) -->

<!-- Script para aplicar estilos imediatamente -->
<script>
// Aplicar estilos imediatamente quando a página carregar
function applyAdminStylesImmediately() {
    console.log('INFO: Aplicando estilos administrativos imediatamente...');
    
    // Aplicar estilos para admin-section
    const adminSection = document.querySelector('.admin-section');
    if (adminSection) {
        adminSection.style.background = 'linear-gradient(135deg, #2c3e50 0%, #34495e 100%)';
        adminSection.style.borderRadius = '20px';
        adminSection.style.padding = '30px';
        adminSection.style.marginBottom = '25px';
        adminSection.style.border = 'none';
        adminSection.style.boxShadow = '0 10px 40px rgba(44, 62, 80, 0.3)';
    }

    // Aplicar estilos para sidebar-sections
    const sidebarSections = document.querySelectorAll('.sidebar-section');
    sidebarSections.forEach((section) => {
        const h4 = section.querySelector('h4');
        if (h4) {
            if (h4.textContent.includes('Informações do Plano')) {
                section.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                section.style.borderRadius = '20px';
                section.style.padding = '30px';
                section.style.marginBottom = '25px';
                section.style.border = 'none';
                section.style.boxShadow = '0 8px 32px rgba(102, 126, 234, 0.4)';
                section.style.transition = 'all 0.3s ease';
            } else if (h4.textContent.includes('Dados Administrativos')) {
                section.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
                section.style.borderRadius = '20px';
                section.style.padding = '30px';
                section.style.marginBottom = '25px';
                section.style.border = 'none';
                section.style.boxShadow = '0 8px 32px rgba(231, 76, 60, 0.4)';
                section.style.transition = 'all 0.3s ease';
            }
        }
    });

    // Aplicar estilos para admin-info
    const adminInfo = document.querySelector('.admin-info');
    if (adminInfo) {
        adminInfo.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        adminInfo.style.borderRadius = '15px';
        adminInfo.style.padding = '25px';
        adminInfo.style.marginBottom = '20px';
        adminInfo.style.border = 'none';
        adminInfo.style.boxShadow = '0 8px 32px rgba(102, 126, 234, 0.3)';
    }

    // Aplicar estilos para info-cards
    const infoCards = document.querySelectorAll('.info-card');
    infoCards.forEach((card, index) => {
        card.style.textAlign = 'center';
        card.style.padding = '20px';
        card.style.borderRadius = '15px';
        card.style.border = 'none';
        card.style.transition = 'all 0.3s ease';
        card.style.transform = 'translateY(0)';
        
        switch(index) {
            case 0: // Data de Criação
                card.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                card.style.boxShadow = '0 6px 20px rgba(40, 167, 69, 0.4)';
                break;
            case 1: // Última Modificação
                card.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
                card.style.boxShadow = '0 6px 20px rgba(255, 193, 7, 0.4)';
                break;
            case 2: // IP de Registro
                card.style.background = 'linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%)';
                card.style.boxShadow = '0 6px 20px rgba(23, 162, 184, 0.4)';
                break;
        }
    });
}

// Expor função globalmente
window.applyAdminStylesImmediately = applyAdminStylesImmediately;

// Aplicar imediatamente
applyAdminStylesImmediately();

// Aplicar novamente após pequenos delays
setTimeout(applyAdminStylesImmediately, 100);
setTimeout(applyAdminStylesImmediately, 500);
setTimeout(applyAdminStylesImmediately, 1000);

// Tornar toda a área do checkbox clicável
(function initClickableCheckboxes(){
    try {
        const groups = document.querySelectorAll('.checkbox-group');
        groups.forEach(group => {
            group.querySelectorAll('.checkbox-item').forEach(item => {
                const cb = item.querySelector('input[type="checkbox"]');
                if (!cb) return;
                // estado visual inicial
                if (cb.checked) item.classList.add('active');
                // evitar duplicação
                if (item._clickableBound) return;
                item._clickableBound = true;
                item.addEventListener('click', (e) => {
                    // se o clique foi no próprio input, deixar comportamento padrão
                    if (e.target === cb) return;
                    // toggle manual
                    cb.checked = !cb.checked;
                    item.classList.toggle('active', cb.checked);
                    // disparar change para validações
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                });
                // sincronizar classe quando checkbox mudar por outros meios
                cb.addEventListener('change', () => {
                    item.classList.toggle('active', cb.checked);
                });
                // também tornar o texto (label) clicável sem duplicar toggle
                const label = item.querySelector('label');
                if (label && !label._bound) {
                    label._bound = true;
                    label.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        cb.checked = !cb.checked;
                        item.classList.toggle('active', cb.checked);
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
            });
        });
    } catch (e) { console.warn('initClickableCheckboxes error', e); }
})();
</script>

<!-- O script JS anuncio.js será carregado via main.php ou dashboard_custom.js -->
<!-- Certifique-se de que jquery.mask.min.js NÃO esteja sendo carregado em nenhum lugar. -->