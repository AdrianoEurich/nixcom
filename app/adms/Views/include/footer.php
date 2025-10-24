<?php
// Verificação de segurança
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="login-footer text-center">
    <p>&copy; <?php echo date('Y'); ?> GPHub. Todos os direitos reservados.</p>
</div>

<!-- ** MODAIS GLOBAIS PARA PÁGINAS DE LOGIN/CADASTRO ** -->

<!-- Modal de Feedback (Sucesso/Erro/Info/Warning/Primary) -->
<div class="modal fade modal-feedback-beautiful" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-beautiful">
            <div class="modal-header modal-header-beautiful">
                <h5 class="modal-title" id="feedbackModalLabel"></h5>
            </div>
            <div class="modal-body modal-body-beautiful text-center">
                <i id="feedbackIcon" class="mb-3"></i>
                <p id="feedbackMessage" class="lead"></p>
            </div>
            <div class="modal-footer modal-footer-beautiful justify-content-center">
                <button type="button" class="btn btn-primary modal-btn-beautiful" id="feedbackModalOkBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade modal-feedback-beautiful" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-beautiful">
            <div class="modal-header modal-header-beautiful">
                <h5 class="modal-title" id="confirmModalLabel"></h5>
            </div>
            <div class="modal-body modal-body-beautiful" id="confirmModalBody"></div>
            <div class="modal-footer modal-footer-beautiful">
                <button type="button" class="btn btn-secondary modal-btn-beautiful" id="confirmModalCancelBtn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger modal-btn-beautiful" id="confirmModalConfirmBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Loading - SIMPLES SEM CLASSES CONFLITANTES -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px;">
            <div class="modal-body text-center py-4" style="background: #ffffff;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-3 mb-0" style="color: #000000; font-weight: bold; font-size: 16px;">Carregando...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Termos e Condições (ESPECÍFICO PARA CADASTRO) -->
<div class="modal fade modal-feedback-beautiful" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-beautiful">
            <div class="modal-header modal-header-beautiful">
                <h5 class="modal-title" id="termsModalLabel">
                    <i class="fas fa-file-contract me-2"></i>
                    Termos e Condições de Uso
                </h5>
            </div>
            <div class="modal-body modal-body-beautiful">
                <div class="terms-content">
                    <h6 class="text-primary mb-3">Bem-vindo ao GPHUB!</h6>
                    
                    <p class="mb-3">Ao criar sua conta, você concorda com os seguintes termos e condições:</p>
                    
                    <div class="terms-section mb-3">
                        <h6><i class="fas fa-shield-alt text-success me-2"></i>Privacidade e Segurança</h6>
                        <ul class="list-unstyled ms-4">
                            <li><i class="fas fa-check text-success me-2"></i>Seus dados pessoais são protegidos</li>
                            <li><i class="fas fa-check text-success me-2"></i>Não compartilhamos informações com terceiros</li>
                            <li><i class="fas fa-check text-success me-2"></i>Você pode excluir sua conta a qualquer momento</li>
                        </ul>
                    </div>
                    
                    <div class="terms-section mb-3">
                        <h6><i class="fas fa-user-check text-info me-2"></i>Responsabilidades do Usuário</h6>
                        <ul class="list-unstyled ms-4">
                            <li><i class="fas fa-check text-success me-2"></i>Fornecer informações verdadeiras e precisas</li>
                            <li><i class="fas fa-check text-success me-2"></i>Manter a confidencialidade de sua senha</li>
                            <li><i class="fas fa-check text-success me-2"></i>Respeitar outros usuários da plataforma</li>
                        </ul>
                    </div>
                    
                    <div class="terms-section mb-3">
                        <h6><i class="fas fa-gavel text-warning me-2"></i>Uso da Plataforma</h6>
                        <ul class="list-unstyled ms-4">
                            <li><i class="fas fa-check text-success me-2"></i>Conteúdo deve ser apropriado e legal</li>
                            <li><i class="fas fa-check text-success me-2"></i>Proibido uso para atividades ilegais</li>
                            <li><i class="fas fa-check text-success me-2"></i>Respeito às leis locais e regulamentações</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Ao clicar em "Aceitar e Continuar", você confirma que leu, entendeu e concorda com todos os termos e condições apresentados.
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-beautiful">
                <button type="button" class="btn btn-secondary modal-btn-beautiful" id="termsModalRejectBtn" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Recusar
                </button>
                <button type="button" class="btn btn-success modal-btn-beautiful" id="termsModalAcceptBtn">
                    <i class="fas fa-check me-2"></i>Aceitar e Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Validação de Email (ESPECÍFICO PARA CADASTRO) -->
<div class="modal fade modal-feedback-beautiful" id="emailValidationModal" tabindex="-1" aria-labelledby="emailValidationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-beautiful">
            <div class="modal-header modal-header-beautiful">
                <h5 class="modal-title" id="emailValidationModalLabel">
                    <i class="fas fa-envelope me-2"></i>
                    Verificação de E-mail
                </h5>
            </div>
            <div class="modal-body modal-body-beautiful text-center">
                <div class="email-validation-content">
                    <i class="fas fa-envelope-open-text text-primary mb-3" style="font-size: 3rem;"></i>
                    <h6 class="text-primary mb-3">E-mail já cadastrado!</h6>
                    <p class="mb-3">O e-mail informado já está sendo usado por outro usuário. Por favor, utilize um e-mail diferente ou faça login se já possui uma conta.</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dica:</strong> Se você esqueceu sua senha, pode usar a opção "Esqueci minha senha" na página de login.
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-beautiful justify-content-center">
                <button type="button" class="btn btn-primary modal-btn-beautiful" id="emailValidationModalOkBtn" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>Entendi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sucesso no Cadastro (ESPECÍFICO PARA CADASTRO) -->
<div class="modal fade modal-feedback-beautiful" id="cadastroSuccessModal" tabindex="-1" aria-labelledby="cadastroSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-beautiful">
            <div class="modal-header modal-header-beautiful bg-success text-white">
                <h5 class="modal-title" id="cadastroSuccessModalLabel">
                    <i class="fas fa-user-plus me-2"></i>
                    Cadastro Realizado com Sucesso!
                </h5>
            </div>
            <div class="modal-body modal-body-beautiful text-center">
                <div class="success-content">
                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 4rem;"></i>
                    <h6 class="text-success mb-3">Parabéns! Sua conta foi criada!</h6>
                    <p class="mb-3">Agora você pode fazer login e começar a usar nossa plataforma. Enviamos um e-mail de confirmação para você.</p>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Próximos passos:</strong> Acesse sua conta e complete seu perfil para aproveitar ao máximo a plataforma.
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-beautiful justify-content-center">
                <button type="button" class="btn btn-success modal-btn-beautiful" id="cadastroSuccessModalLoginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>Ir para Login
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo URLADM; ?>assets/js/general-utils.js"></script>
<!-- JS Personalizado -->
<script src="<?php echo URLADM; ?>assets/js/login.js"></script>

<!-- JS específico da página -->
<?php
$currentPage = $_GET['url'] ?? '';
$jsFile = '';

switch($currentPage) {
    case 'cadastro':
        $jsFile = 'cadastro.js';
        break;
    case 'pagamento':
        $jsFile = 'pagamento.js';
        break;
    case 'planos':
        $jsFile = 'planos.js';
        break;
}

if ($jsFile) {
    echo '<script src="' . URLADM . 'assets/js/' . $jsFile . '"></script>';
}
?>

</body>

</html>
