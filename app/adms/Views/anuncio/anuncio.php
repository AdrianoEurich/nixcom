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
    die("Erro: Página não encontrada!");
}
?>


    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h5 class="m-0"><i class="fas fa-bullhorn me-2"></i>CRIAR NOVO ANÚNCIO</h5>
        </div>
        <div class="card-body p-4">
            <form id="formCriarAnuncio" action="<?= URLADM ?>cadastrar-anuncio/index" method="POST" enctype="multipart/form-data">

                <h4 class="mb-4 text-primary">Informações Básicas</h4>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="state_id" class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                        <select class="form-select" id="state_id" name="state_id" required>
                            <option value="">Carregando Estados...</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="city_id" class="form-label fw-bold">Cidade <span class="text-danger">*</span></label>
                        <select class="form-select" id="city_id" name="city_id" disabled required>
                            <option value="">Selecione a Cidade</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="neighborhood_id" class="form-label fw-bold">Bairro <span class="text-danger">*</span></label>
                        <select class="form-select" id="neighborhood_id" name="neighborhood_id" disabled required>
                            <option value="">Selecione o Bairro</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="idade" class="form-label fw-bold">Idade <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="idade" name="idade" min="18" max="99" placeholder="Sua idade" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nacionalidade" class="form-label fw-bold">Nacionalidade <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nacionalidade" name="nacionalidade" placeholder="Sua nacionalidade" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="descricao_sobre_mim" class="form-label fw-bold">Descrição sobre mim <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="descricao_sobre_mim" name="descricao_sobre_mim" rows="5" placeholder="Conte um pouco sobre você..." required></textarea>
                </div>

                <h4 class="mb-3 text-primary">Serviços Oferecidos <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 2 serviços.</small>
                <div class="row mb-4" id="servicos-checkboxes">
                    <?php
                    $servicos = [
                        "BEIJO NA BOCA", "ATENDE CASAIS", "FETICHISMO", "ORAL COM CAMISINHA",
                        "ORAL SEM CAMISINHA", "SQUITING", "SADO SUBMISSA", "CHUVA DOURADA",
                        "SEXO ANAL", "GARGANTA PROFUNDA", "LESBIANISMO", "EJACULAÇÃO NO CORPO",
                        "ORAL ATÉ O FINAL", "DUPLAS", "DOMINADORA", "FANTASIAS E FIGURINOS",
                        "MASSAGEM ERÓTICA", "ATENÇÃO A MULHERES", "EJACULAÇÃO FACIAL", "SADO SUAVE",
                        "FESTAS EVENTOS", "FISTING ANAL", "ATENÇÃO A DEFICIENTES FÍSICOS",
                        "DESPEDIDAS DE SOLTEIROS", "ORGIAS", "FISTING VAGINAL", "SEXCAM", "STRAP ON"
                    ];
                    foreach ($servicos as $servico) {
                        $id_servico = 'servico_' . str_replace(' ', '', preg_replace('/[^a-zA-Z0-9]/', '', $servico));
                        echo '<div class="col-md-4 col-sm-6 mb-2">';
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($servico) . '" id="' . $id_servico . '" name="servicos[]">';
                        echo '<label class="form-check-label" for="' . $id_servico . '">' . htmlspecialchars($servico) . '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <h4 class="mb-3 text-primary">Preços <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Preencha pelo menos um preço.</small>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="preco_15min" class="form-label fw-bold">15 minutos</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="preco_15min" name="precos[15min]" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="preco_30min" class="form-label fw-bold">30 minutos</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="preco_30min" name="precos[30min]" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="preco_1h" class="form-label fw-bold">1 Hora</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="preco_1h" name="precos[1h]" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <h4 class="mb-3 text-primary">Mídia <span class="text-danger">*</span></h4>

                <div class="mb-3">
                    <label class="form-label fw-bold">Foto da Capa <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3 justify-content-start">
                        <div class="photo-upload-box cover-photo-box" id="coverPhotoUploadBox">
                            <input type="file" id="foto_capa_input" name="foto_capa" accept="image/*" class="d-none" required>
                            <img id="coverPhotoPreview" src="#" alt="" class="photo-preview rounded mx-auto d-block" style="display: none;">
                            <div class="upload-placeholder">
                                <i class="fas fa-camera fa-2x"></i>
                                <p>Foto da Capa</p>
                            </div>
                            <button type="button" class="btn-remove-photo d-none" data-target-input="foto_capa_input" data-target-preview="coverPhotoPreview" data-target-box="coverPhotoUploadBox">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Apenas uma foto para a capa.</small>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="form-label fw-bold">Fotos da Galeria (Máx. 20, 3 Gratuitas)</label>
                    <div class="d-flex flex-wrap gap-3" id="galleryPhotoContainer">
                        <?php for ($i = 0; $i < 3; $i++) : ?>
                            <div class="photo-upload-box gallery-upload-box active-plan" data-photo-index="<?= $i ?>">
                                <input type="file" name="fotos_galeria[]" accept="image/*" class="d-none">
                                <img src="#" alt="" class="photo-preview rounded mx-auto d-block" style="display: none;">
                                <div class="upload-placeholder">
                                    <i class="fas fa-camera fa-2x"></i>
                                    <p>Adicionar Foto</p>
                                </div>
                                <button type="button" class="btn-remove-photo d-none">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                        <?php endfor; ?>

                        <?php for ($i = 3; $i < 20; $i++) : ?>
                            <div class="photo-upload-box gallery-upload-box premium-locked" data-photo-index="<?= $i ?>">
                                <input type="file" name="fotos_galeria[]" accept="image/*" class="d-none" disabled>
                                <img src="#" alt="" class="photo-preview rounded mx-auto d-block" style="display: none;">
                                <div class="upload-placeholder">
                                    <i class="fas fa-lock fa-2x"></i>
                                    <p>Plano Pago</p>
                                </div>
                                <button type="button" class="btn-remove-photo d-none">
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
                        <?php for ($i = 0; $i < 3; $i++) : ?>
                            <div class="photo-upload-box video-upload-box premium-locked">
                                <input class="d-none" type="file" name="videos[]" accept="video/*" disabled>
                                <video class="photo-preview rounded mx-auto d-block" style="display: none;" controls></video>
                                <div class="upload-placeholder">
                                    <i class="fas fa-lock fa-2x"></i>
                                    <p>Plano Pago</p>
                                </div>
                                <button type="button" class="btn-remove-photo d-none">
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
                        <?php for ($i = 0; $i < 3; $i++) : ?>
                            <div class="photo-upload-box audio-upload-box premium-locked">
                                <input class="d-none" type="file" name="audios[]" accept="audio/*" disabled>
                                <audio class="photo-preview rounded mx-auto d-block" style="display: none;" controls></audio>
                                <div class="upload-placeholder">
                                    <i class="fas fa-lock fa-2x"></i> <p>Plano Pago</p>
                                </div>
                                <button type="button" class="btn-remove-photo d-none">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <small class="text-muted">3 áudios. Apenas para planos pagos.</small>
                </div>

                <h4 class="mb-3 text-primary">Sobre Mim (Aparência) <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 item de aparência.</small>
                <div class="row mb-4" id="aparencia-checkboxes">
                    <?php
                    $aparencia = [
                        "Magra", "Peito natural", "Siliconada", "Peitos grande", "Peitos pequeno",
                        "Depilada", "Peluda", "Alta", "Baixa", "Mignon", "Ruiva", "Morena",
                        "Gordinha", "Loira", "Pele Morena"
                    ];
                    foreach ($aparencia as $item) {
                        $id_aparencia = 'aparencia_' . str_replace(' ', '', preg_replace('/[^a-zA-Z0-9]/', '', $item));
                        echo '<div class="col-md-3 col-sm-6 mb-2">';
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($item) . '" id="' . $id_aparencia . '" name="aparencia[]">';
                        echo '<label class="form-check-label" for="' . $id_aparencia . '">' . htmlspecialchars($item) . '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="altura" class="form-label fw-bold">Altura (cm)</label>
                        <input type="number" class="form-control" id="altura" name="altura" min="100" max="250" placeholder="Ex: 170">
                    </div>
                    <div class="col-md-4">
                        <label for="peso" class="form-label fw-bold">Peso (kg)</label>
                        <input type="number" step="0.1" class="form-control" id="peso" name="peso" min="30" max="200" placeholder="Ex: 60.5">
                    </div>
                    <div class="col-md-4">
                        <label for="etnia" class="form-label fw-bold">Etnia</label>
                        <input type="text" class="form-control" id="etnia" name="etnia" placeholder="Ex: Caucasiana, Asiática">
                    </div>
                </div>

                <h4 class="mb-3 text-primary">Idiomas <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 idioma.</small>
                <div class="row mb-4" id="idiomas-checkboxes">
                    <?php
                    $idiomas = ["Português", "Inglês", "Espanhol"];
                    foreach ($idiomas as $idioma) {
                        $id_idioma = 'idioma_' . str_replace(' ', '', preg_replace('/[^a-zA-Z0-9]/', '', $idioma));
                        echo '<div class="col-md-4 col-sm-6 mb-2">';
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($idioma) . '" id="' . $id_idioma . '" name="idiomas[]">';
                        echo '<label class="form-check-label" for="' . $id_idioma . '">' . htmlspecialchars($idioma) . '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <h4 class="mb-3 text-primary">Local de Atendimento <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 local.</small>
                <div class="row mb-4" id="locais-checkboxes">
                    <?php
                    $locais = ["Hotel", "Motel", "A domicílio", "Com Local"];
                    foreach ($locais as $local) {
                        $id_local = 'local_' . str_replace(' ', '', preg_replace('/[^a-zA-Z0-9]/', '', $local));
                        echo '<div class="col-md-3 col-sm-6 mb-2">';
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($local) . '" id="' . $id_local . '" name="locais_atendimento[]">';
                        echo '<label class="form-check-label" for="' . $id_local . '">' . htmlspecialchars($local) . '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <h4 class="mb-3 text-primary">Formas de Pagamento <span class="text-danger">*</span></h4>
                <small class="text-muted d-block mb-3">Selecione pelo menos 1 forma de pagamento.</small>
                <div class="row mb-4" id="pagamentos-checkboxes">
                    <?php
                    $pagamentos = ["Dinheiro", "Pix", "Cartão de Crédito"];
                    foreach ($pagamentos as $pagamento) {
                        $id_pagamento = 'pagamento_' . str_replace(' ', '', preg_replace('/[^a-zA-Z0-9]/', '', $pagamento));
                        echo '<div class="col-md-4 col-sm-6 mb-2">';
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input" type="checkbox" value="' . htmlspecialchars($pagamento) . '" id="' . $id_pagamento . '" name="formas_pagamento[]">';
                        echo '<label class="form-check-label" for="' . $id_pagamento . '">' . htmlspecialchars($pagamento) . '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success btn-lg px-5 py-3" id="btnSubmitAnuncio">
                        <i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO
                    </button>
                </div>
            </form>
        </div>
    </div>
