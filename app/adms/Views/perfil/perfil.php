<?php if (!defined('C7E3L8K9E5')) die("Erro: Acesso negado"); ?>

<div class="row g-4 d-flex align-items-start">
    <div class="col-xl-3 col-lg-4 col-md-5">
        <div class="card shadow foto-perfil-card d-flex flex-column">
            <div class="card-header py-3 bg-primary text-white">
                <h5 class="m-0"><i class="fas fa-camera me-2"></i>Alterar Foto</h5>
            </div>
            <div class="card-body d-flex flex-column p-4 flex-grow-1 align-items-center">
                <div class="foto-container mb-4 d-flex align-items-center justify-content-center">
                    <?php
                    $fotoUsuario = $_SESSION['usuario']['foto'] ?? 'usuario.png';
                    $urlFoto = URLADM . 'assets/images/users/' . $fotoUsuario . '?t=' . time();
                    $caminhoFisicoFoto = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/assets/images/users/' . $fotoUsuario;

                    if (!file_exists($caminhoFisicoFoto) || empty($fotoUsuario) || $fotoUsuario === 'usuario.png') {
                        $fotoUsuario = 'usuario.png';
                        $urlFoto = URLADM . 'assets/images/users/usuario.png?t=' . time();
                    }
                    ?>
                    <img src="<?= $urlFoto ?>"
                         id="fotoPreview"
                         class="rounded-circle img-thumbnail"
                         style="max-width: 200px; max-height: 200px; object-fit: cover; border: 3px solid #dee2e6;"
                         alt="Foto de Perfil"
                         onerror="this.onerror=null;this.src='<?= URLADM ?>assets/images/users/usuario.png?t=' + Date.now()">
                </div>

                <form method="POST" action="<?= URLADM ?>perfil/atualizarFoto" enctype="multipart/form-data" id="formFoto" class="mt-auto w-100">
                    <div class="d-flex flex-column gap-3 w-100">
                        <div class="text-center">
                            <label for="fotoInput" class="btn btn-outline-primary btn-lg w-100 position-relative">
                                <i class="fas fa-camera me-2"></i>ESCOLHER FOTO
                                <input type="file"
                                       class="form-control d-none"
                                       id="fotoInput"
                                       name="foto_perfil"
                                       accept="image/*"
                                       required>
                            </label>
                            <div id="fileName" class="small text-muted mt-2">Nenhum arquivo selecionado</div>
                        </div>
                        <button class="btn btn-primary btn-lg py-2" type="submit" id="uploadBtn">
                            <i class="fas fa-upload me-2"></i>ATUALIZAR FOTO
                        </button>

                        <button type="button" class="btn btn-danger btn-lg py-2 mt-2" id="removeFotoBtn" style="display: <?= ($fotoUsuario === 'usuario.png') ? 'none' : 'block' ?>">
                            <i class="fas fa-trash me-2"></i>REMOVER FOTO
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">Formatos: JPG, PNG, WEBP</small>
                    <small class="text-muted d-block">Tamanho máximo: 16 MB</small> 
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-lg-8 col-md-7 d-flex flex-column gap-4">
        <div class="card shadow flex-grow-1">
            <div class="card-header py-3 bg-primary text-white">
                <h5 class="m-0"><i class="fas fa-user-circle me-2"></i>INFORMAÇÕES PESSOAIS</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= URLADM ?>perfil/atualizarNome" id="formNome">
                    <div class="mb-4">
                        <label class="form-label fs-5 fw-bold" for="nome">NOME COMPLETO</label>
                        <input type="text"
                               class="form-control form-control-lg border-2"
                               id="nome"
                               name="nome"
                               value="<?= htmlspecialchars($this->data['user_data']['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required
                               style="padding: 12px 15px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fs-5 fw-bold">E-MAIL</label>
                        <p class="form-text-display form-control-lg"><?= htmlspecialchars($this->data['user_data']['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 py-2">
                            <i class="fas fa-save me-2"></i>SALVAR ALTERAÇÕES
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header py-3 bg-warning text-dark">
                <h5 class="m-0"><i class="fas fa-key me-2"></i>ALTERAR SENHA</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= URLADM ?>perfil/atualizarSenha" id="formSenha">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fs-5 fw-bold" for="senha_atual">SENHA ATUAL</label>
                            <input type="password"
                                   class="form-control form-control-lg border-2"
                                   id="senha_atual"
                                   name="senha_atual"
                                   required
                                   style="padding: 12px 15px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-5 fw-bold" for="nova_senha">NOVA SENHA</label>
                            <input type="password"
                                   class="form-control form-control-lg border-2"
                                   id="nova_senha"
                                   name="nova_senha"
                                   required
                                   style="padding: 12px 15px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-5 fw-bold" for="confirma_senha">CONFIRMAR</label>
                            <input type="password"
                                   class="form-control form-control-lg border-2"
                                   id="confirma_senha"
                                   name="confirma_senha"
                                   required
                                   style="padding: 12px 15px;">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-warning btn-lg px-4 py-2">
                            <i class="fas fa-key me-2"></i>ALTERAR SENHA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="alertModalLabel">ALERTA</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="alertModalBody">
                <p class="fs-5">Mensagem de alerta aqui...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-dismiss="modal" id="alertModalBtn">ENTENDI</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="confirmModalLabel">CONFIRMAÇÃO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                <p class="fs-5">Deseja realmente executar esta ação?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-lg px-4" id="confirmModalCancelBtn">CANCELAR</button>
                <button type="button" class="btn btn-warning btn-lg px-4" id="confirmModalConfirmBtn">CONFIRMAR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="actionModalTitle">CONFIRMAR AÇÃO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="actionModalBody">
                <p class="fs-5">Deseja realmente executar esta ação?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-primary" id="actionModalConfirm">CONFIRMAR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">SUCESSO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fs-5" id="successMessage">Operação realizada com sucesso!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">ENTENDI</button>
            </div>
        </div>
    </div>
</div>

<script>
    const currentUserName = "<?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Nome do Usuário', ENT_QUOTES, 'UTF-8') ?>";
</script>

<script src="<?= URLADM ?>assets/js/perfil.js?v=<?= time() ?>"></script>