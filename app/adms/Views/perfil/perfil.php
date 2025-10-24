<?php if (!defined('C7E3L8K9E5')) die("Erro: Acesso negado"); ?>

<div class="row g-4 d-flex align-items-start">
    <!-- CARD DA FOTO DE PERFIL -->
    <div class="col-xl-3 col-lg-4 col-md-5">
        <div class="card shadow foto-perfil-card d-flex flex-column">
            <div class="card-header">
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
                         style="max-width: 200px; max-height: 200px; object-fit: cover; border: 3px solid var(--accent);"
                         alt="Foto de Perfil"
                         onerror="this.onerror=null;this.src='<?= URLADM ?>assets/images/users/usuario.png?t=' + Date.now()">
                </div>

                <form method="POST" action="<?= URLADM ?>perfil/atualizarFoto" enctype="multipart/form-data" id="formFoto" class="mt-auto w-100">
                    <div class="d-flex flex-column gap-3 w-100">
                        <div class="text-center">
                            <label for="fotoInput" class="btn btn-primary w-100 position-relative">
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
                        <button class="btn btn-primary py-2 w-100" type="submit" id="uploadBtn">
                            <i class="fas fa-upload me-2"></i>ATUALIZAR FOTO
                        </button>

                        <button type="button" class="btn btn-danger py-2 mt-2 w-100" id="removeFotoBtn" style="display: <?= ($fotoUsuario === 'usuario.png') ? 'none' : 'block' ?>">
                            <i class="fas fa-trash me-2"></i>REMOVER FOTO
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">Formatos: JPG, PNG, WEBP</small>
                    <small class="text-muted d-block">Tamanho máximo: 16 MB</small> 
                </form>
            </div>
        </div>
    </div>

    <!-- COLUNA PRINCIPAL -->
    <div class="col-xl-9 col-lg-8 col-md-7 d-flex flex-column gap-4">
        <!-- CARD INFORMAÇÕES PESSOAIS -->
        <div class="card shadow flex-grow-1">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-user-circle me-2"></i>INFORMAÇÕES PESSOAIS</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= URLADM ?>perfil/atualizarNome" id="formNome" class="form-profile">
                    <div class="mb-4">
                        <label class="form-label" for="nome">NOME COMPLETO</label>
                        <input type="text"
                               class="form-control"
                               id="nome"
                               name="nome"
                               value="<?= htmlspecialchars($this->data['user_data']['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock me-2 text-muted"></i>E-MAIL
                            <small class="text-muted">(Somente leitura)</small>
                        </label>
                        <div class="form-control-plaintext bg-light border rounded p-3 text-muted" style="cursor: not-allowed;">
                            <i class="fas fa-envelope me-2"></i>
                            <?= htmlspecialchars($this->data['user_data']['email'] ?? $_SESSION['user_email'] ?? $_SESSION['usuario']['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            O e-mail não pode ser alterado por questões de segurança.
                        </small>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2">
                            <i class="fas fa-save me-2"></i>SALVAR ALTERAÇÕES
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CARD ALTERAR SENHA -->
        <div class="card shadow">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-key me-2"></i>ALTERAR SENHA</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= URLADM ?>perfil/atualizarSenha" id="formSenha" class="form-profile">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" for="senha_atual">SENHA ATUAL</label>
                            <input type="password"
                                   class="form-control"
                                   id="senha_atual"
                                   name="senha_atual"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="nova_senha">NOVA SENHA</label>
                            <input type="password"
                                   class="form-control"
                                   id="nova_senha"
                                   name="nova_senha"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="confirma_senha">CONFIRMAR</label>
                            <input type="password"
                                   class="form-control"
                                   id="confirma_senha"
                                   name="confirma_senha"
                                   required>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-warning px-4 py-2">
                            <i class="fas fa-key me-2"></i>ALTERAR SENHA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CARD EXCLUIR CONTA -->
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="m-0"><i class="fas fa-exclamation-triangle me-2"></i>ZONA DE PERIGO</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção!</strong> A exclusão da conta é permanente e irá remover todos os seus dados, incluindo anúncios, fotos e informações pessoais.
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-danger btn-lg" id="btnDeleteAccount">
                        <i class="fas fa-trash-alt me-2"></i>EXCLUIR MINHA CONTA
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const currentUserName = "<?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Nome do Usuário', ENT_QUOTES, 'UTF-8') ?>";
</script>

<script src="<?= URLADM ?>assets/js/perfil.js?v=<?= time() ?>"></script>