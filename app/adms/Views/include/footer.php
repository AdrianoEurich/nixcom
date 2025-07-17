<?php
// Verificação de segurança
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="login-footer text-center">
    <p>&copy; <?php echo date('Y'); ?> Nixcom. Todos os direitos reservados.</p>
</div>

<!-- ** INÍCIO DO HTML DOS MODAIS PARA LOGIN/CADASTRO ** -->

<!-- Modal de Feedback (Sucesso/Erro/Info/Warning/Primary) -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i id="feedbackIcon" class="mb-3"></i>
                <p id="feedbackMessage" class="lead"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" id="feedbackModalOkBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="confirmModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="confirmModalCancelBtn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmModalConfirmBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Loading -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-3 mb-0">Carregando...</p>
            </div>
        </div>
    </div>
</div>
<!-- ** FIM DO HTML DOS MODAIS PARA LOGIN/CADASTRO ** -->

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo URLADM; ?>assets/js/general-utils.js"></script>
<!-- JS Personalizado -->
<script src="<?php echo URLADM; ?>assets/js/login.js"></script>
<script src="<?php echo URLADM; ?>assets/js/cadastro.js"></script>

</body>

</html>
