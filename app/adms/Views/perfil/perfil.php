<?php if (!defined('C7E3L8K9E5')) die("Erro: Acesso negado"); ?>

<div class="row g-4">

    <div class="col-xl-3 col-lg-4 col-md-5">
        <div class="card shadow h-100 foto-perfil-card">
            <div class="card-header py-3 bg-primary text-white">
                <h5 class="m-0"><i class="fas fa-camera me-2"></i>Alterar Foto</h5>
            </div>
            <div class="card-body d-flex flex-column p-4">
                <div class="foto-container flex-grow-1 d-flex align-items-center justify-content-center">
                    <?php
                    // Obtém o nome da foto do usuário da sessão
                    $fotoUsuario = $_SESSION['usuario']['foto'] ?? 'usuario.png';

                    // Constrói a URL da foto com um "cache buster" para garantir que seja sempre a versão mais recente
                    $urlFoto = URLADM . 'assets/images/users/' . $fotoUsuario . '?t=' . time();

                    // Caminho físico completo para verificar se o arquivo existe
                    // Usamos 'app' no caminho porque 'nixcom/app/adms/assets/images/users' é a pasta
                    // NOTA: $_SERVER['DOCUMENT_ROOT'] pode precisar de ajuste dependendo da sua configuração do servidor.
                    // Se 'nixcom' não estiver diretamente na raiz do DOCUMENT_ROOT, o caminho pode ser diferente.
                    // Uma forma mais robusta seria usar PATH_ROOT se estiver definido corretamente.
                    $caminhoFisicoFoto = PATH_ROOT . 'assets/images/users/' . $fotoUsuario; // Usando PATH_ROOT

                    // Se o arquivo físico não existe ou se a foto atual é 'usuario.png', define para a imagem padrão
                    if (!file_exists($caminhoFisicoFoto) || empty($fotoUsuario) || $fotoUsuario === 'usuario.png') {
                        $fotoUsuario = 'usuario.png';
                        $urlFoto = URLADM . 'assets/images/users/usuario.png?t=' . time(); // Cache buster para a imagem padrão também
                    }
                    ?>
                    <img src="<?= $urlFoto ?>"
                        id="fotoPreview"
                        class="rounded-circle img-thumbnail mx-auto"
                        style="max-width: 100%; max-height: 300px; object-fit: cover; border: 3px solid #dee2e6;"
                        alt="Foto de Perfil"
                        onerror="this.onerror=null;this.src='<?= URLADM ?>assets/images/users/usuario.png?t=' + Date.now()">
                </div>

                <form method="POST" action="<?= URLADM ?>perfil/atualizarFoto" enctype="multipart/form-data" id="formFoto" class="mt-3">
                    <div class="d-flex flex-column gap-3">
                        <div class="text-center">
                            <label for="fotoInput" class="btn btn-outline-primary btn-lg w-100 position-relative">
                                <i class="fas fa-camera me-2"></i>ESCOLHER FOTO
                                <input type="file"
                                    class="form-control d-none"
                                    id="fotoInput"
                                    name="foto_perfil"
                                    accept="image/*">
                                    <!-- Removido 'required' para permitir remover foto ou não selecionar uma nova -->
                            </label>
                            <div id="fileName" class="small text-muted mt-2">Nenhum arquivo selecionado</div>
                        </div>
                        
                        <!-- Botão de Remover Foto (visível apenas se houver uma foto diferente da padrão) -->
                        <button type="button" class="btn btn-outline-danger btn-lg py-2 <?= ($fotoUsuario === 'usuario.png' || empty($fotoUsuario)) ? 'd-none' : ''; ?>" id="removeFotoButton">
                            <i class="fas fa-trash-alt me-2"></i>REMOVER FOTO
                        </button>

                        <!-- Campos hidden para gerenciamento de foto via JS/Backend -->
                        <input type="hidden" id="existing_photo_path" name="existing_photo_path" value="<?= htmlspecialchars($fotoUsuario, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" id="photo_removed" name="photo_removed" value="false">

                        <button class="btn btn-primary btn-lg py-2" type="submit" id="uploadBtn">
                            <i class="fas fa-upload me-2"></i>ATUALIZAR FOTO
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">Formatos: JPG, PNG, WEBP (Até 4MB)</small>
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
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 py-2">
                            <i class="fas fa-save me-2"></i>SALVAR ALTERAÇÕES
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow flex-grow-1">
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

<!-- Modais de Feedback e Confirmação (mantidos aqui para compatibilidade, mas idealmente centralizados no layout principal) -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="feedbackModalHeader">
                <h5 class="modal-title" id="feedbackModalLabel">Mensagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i id="feedbackIcon" class="mb-3"></i>
                <p class="fs-5" id="feedbackMessage"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-dismiss="modal" id="feedbackModalOkBtn">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark" id="confirmModalHeader">
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

<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-3 text-light">Carregando...</p>
            </div>
        </div>
    </div>
</div>

<!-- REMOVIDO: O bloco <script> que chamava window.initializePerfilPage() e incluía perfil.js -->
<!-- Isso será gerenciado pelo dashboard_custom.js para evitar problemas em SPA. -->
