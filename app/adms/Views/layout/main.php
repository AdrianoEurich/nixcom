<?php
// app/adms/Views/layout/main.php
// Este é o layout principal da área administrativa.
// Ele inclui a topbar, sidebar e carrega o conteúdo dinâmico da view.

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Definir timezone para corresponder ao MySQL
date_default_timezone_set('America/Sao_Paulo');

// A variável $viewContent será definida no ConfigViewAdm::loadView()
// e conterá o caminho completo para a view de conteúdo (ex: app/adms/Views/dashboard/content_dashboard.php).
// As variáveis passadas pelo controlador (ex: $user_data, $sidebar_active)
// também estarão disponíveis aqui via extract($this->data) no ConfigViewAdm.

// Define o caminho base para inclusão de arquivos (sidebar, topbar, etc.)
// __DIR__ é o diretório atual (app/adms/Views/layout)
// '/../include/' vai para app/adms/Views/include/
$includeBasePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR;

// --- INÍCIO DA LÓGICA PARA GARANTIR O ESTADO CORRETO DO ANÚNCIO NO BODY ---
// Usa os dados da sessão em vez de buscar do banco novamente
$has_anuncio = $_SESSION['has_anuncio'] ?? false;
$anuncio_status = $_SESSION['anuncio_status'] ?? 'not_found';
$current_user_id = $_SESSION['user_id'] ?? ''; // Pega o user_id da sessão
$current_user_name = $_SESSION['user_name'] ?? 'Usuário'; // Pega o nome do usuário da sessão
$current_user_role = $_SESSION['user_role'] ?? 'normal'; // Pega o papel do usuário da sessão (ex: 'admin', 'normal')
$current_user_photo = $_SESSION['user_photo_path'] ?? 'usuario.png'; // NOVO: Pega o caminho da foto do usuário da sessão
$current_user_plan = $_SESSION['user_plan'] ?? 'free'; // Pega o plano do usuário da sessão
$current_payment_status = $_SESSION['payment_status'] ?? 'pending'; // Pega o status de pagamento da sessão

error_log("DEBUG PHP MAIN: user_id=" . $current_user_id . ", has_anuncio=" . ($has_anuncio ? 'true' : 'false') . ", anuncio_status=" . $anuncio_status . ", user_role=" . $current_user_role . ", user_plan=" . $current_user_plan . ", payment_status=" . $current_payment_status);
error_log("DEBUG PHP MAIN: Sessão completa - " . json_encode([
    'user_id' => $_SESSION['user_id'] ?? 'null',
    'has_anuncio' => $_SESSION['has_anuncio'] ?? 'null',
    'anuncio_status' => $_SESSION['anuncio_status'] ?? 'null',
    'anuncio_id' => $_SESSION['anuncio_id'] ?? 'null',
    'user_role' => $_SESSION['user_role'] ?? 'null',
    'user_plan' => $_SESSION['user_plan'] ?? 'null',
    'payment_status' => $_SESSION['payment_status'] ?? 'null'
]));
// --- FIM DA LÓGICA ---

// Assumindo que URL e URLADM são definidas em ConfigAdm.php e estão disponíveis globalmente

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://ajax.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src \'self\' data: blob: https://randomuser.me; media-src \'self\' data: blob:; connect-src \'self\' https://ajax.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Nixcom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/dashboard_custom.css?v=<?php echo time() . '_' . rand(1000, 9999); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/modern_form.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/anuncio-professional.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/contato-modal-profissional.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/dashboard-modern.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/delete-account-modal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/modal-base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/plan-modal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/admin-cards.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/announcements-card-fixed.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/modal-feedback-beautiful.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/modal-mensagens-beautiful.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/loading-modal-fix.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/modal-sizes-specific.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="<?php echo URLADM; ?>assets/images/icon/favicon.ico" type="image/x-icon">
</head>
<!-- AQUI É ONDE ADICIONAMOS O data-user-id, data-has-anuncio, data-anuncio-status, data-user-role e data-user-name -->
<body id="page-top"
      data-user-id="<?= htmlspecialchars($current_user_id); ?>"
      data-has-anuncio="<?= htmlspecialchars($has_anuncio ? 'true' : 'false') ?>"
      data-anuncio-status="<?= htmlspecialchars($anuncio_status) ?>"
      data-user-role="<?= htmlspecialchars($current_user_role) ?>"
      data-user-name="<?= htmlspecialchars($current_user_name) ?>"
      data-user-photo="<?= htmlspecialchars($current_user_photo) ?>"
      data-user-plan="<?= htmlspecialchars($current_user_plan) ?>"
      data-payment-status="<?= htmlspecialchars($current_payment_status) ?>">
    <script>
        // Garante que as constantes PHP URL e URLADM estejam definidas no JS
        // Explicitamente anexamos a window para garantir acessibilidade global.
        // RENOMEADO DE 'URL' PARA 'projectBaseURL' PARA EVITAR CONFLITO COM O CONSTRUTOR NATIVO 'URL'
        window.projectBaseURL = "<?php echo URL; ?>";
        window.URLADM = "<?php echo URLADM; ?>";
        console.log("DEBUG PHP to JS: window.URLADM = " + window.URLADM);
        console.log("DEBUG PHP to JS: window.projectBaseURL = " + window.projectBaseURL); // Novo log
        console.log("DEBUG PHP to JS: Test String = <?php echo 'PHP_TEST_SUCCESS'; ?>");
        console.log("DEBUG PHP to JS: Body data-has-anuncio = " + document.body.dataset.hasAnuncio);
        console.log("DEBUG PHP to JS: Body data-anuncio-status = " + document.body.dataset.anuncioStatus);
        console.log("DEBUG PHP to JS: Body data-user-id = " + document.body.dataset.userId);
        console.log("DEBUG PHP to JS: Body data-user-role = " + document.body.dataset.userRole); // Novo log
        console.log("DEBUG PHP to JS: Body data-user-name = " + document.body.dataset.userName); // Novo log
        console.log("DEBUG PHP to JS: Body data-user-plan = " + document.body.dataset.userPlan); // Novo log
        console.log("DEBUG PHP to JS: Body data-payment-status = " + document.body.dataset.paymentStatus); // Novo log
    </script>
    <?php include_once $includeBasePath . 'topbar.php'; ?>

    <div class="d-flex" id="wrapper">
        <?php include_once $includeBasePath . 'sidebar.php'; ?>

        <div id="content-wrapper" class="container-fluid" style="background-color: #1f1f1f !important;">
            <div class="main-content" id="dynamic-content">
                <?php
                if (isset($viewContent) && file_exists($viewContent)) {
                    include_once $viewContent;
                } else {
                    echo "<div class='alert alert-info'>Nenhum conteúdo específico definido para esta página ou o arquivo não foi encontrado.</div>";
                }
                ?>
            </div>
            <?php // include_once $includeBasePath . 'footer.php'; ?>
        </div>
    </div>

    <!-- ** INÍCIO DO HTML DOS MODAIS GLOBAIS (APENAS PARA A ÁREA ADMINISTRATIVA) ** -->

    <!-- Modal de Feedback (Sucesso/Erro/Info/Warning/Primary) -->
    <div class="modal fade modal-feedback-beautiful modal-theme-login" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="--bs-modal-width: 500px; max-width: 500px !important; width: auto;">
            <div class="modal-content modal-content-beautiful">
                <div class="modal-header modal-header-beautiful">
                    <h5 class="modal-title modal-title-beautiful" id="feedbackModalLabel"></h5>
                </div>
                <div class="modal-body modal-body-beautiful text-center">
                    <i id="feedbackIcon" class="feedback-icon-beautiful mb-3"></i>
                    <p id="feedbackMessage" class="feedback-message-beautiful lead"></p>
                </div>
                <div class="modal-footer modal-footer-beautiful justify-content-center">
                    <button type="button" class="btn btn-primary feedback-btn-beautiful" id="feedbackModalOkBtn" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade modal-theme-login" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-beautiful">
                    <h5 class="modal-title modal-title-beautiful" id="confirmModalLabel">
                        <i class="fa-solid fa-trash me-2"></i>
                        <span id="confirmModalLabelText"></span>
                    </h5>
                </div>
                <div class="modal-body" id="confirmModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmModalCancelBtn" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmModalConfirmBtn">
                        <i class="fas fa-check me-1"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Loading -->
    <div class="modal fade modal-theme-login" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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

        <!-- Modal de Exclusão de Conta - Versão Global Limpa -->
        <div class="modal fade modal-theme-login" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-xxl modal-dialog-centered modal-auto-height modal-fullscreen-lg-down">
                <div class="modal-content delete-account-modal">
                    <!-- Header -->
                    <div class="modal-header delete-account-header">
                        <h5 class="modal-title d-flex align-items-center gap-2" id="deleteAccountModalLabel">
                            <i class="fas fa-exclamation-triangle"></i>
                            Exclusão Definitiva de Conta
                        </h5>
                    </div>
                    <!-- Body -->
                    <div class="modal-body delete-account-body">
                        <div class="delete-account-content mx-auto">
                            <div class="mb-4">
                                <h6 class="section-title text-uppercase text-danger fw-bold mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Ação Irreversível
                                </h6>
                                <p class="section-text mb-0">
                                    Você está prestes a <strong>EXCLUIR DEFINITIVAMENTE</strong> sua conta e todos os dados associados.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h6 class="section-title text-uppercase text-primary fw-bold mb-2">
                                    <i class="fas fa-crown me-2"></i>Planos Pagos
                                </h6>
                                <p class="section-text mb-0">
                                    Se você possui um plano pago e excluir sua conta, será necessário adquirir um novo plano para criar uma nova conta.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h6 class="section-title text-uppercase text-dark fw-bold mb-2">
                                    <i class="fas fa-ban me-2 text-danger"></i>Não Pode Ser Desfeito
                                </h6>
                                <p class="section-text mb-0">
                                    Todos os dados serão removidos permanentemente do servidor e <strong>não poderão ser recuperados</strong>.
                                </p>
                            </div>

                            <div>
                                <h6 class="section-title text-uppercase text-dark fw-bold mb-2">
                                    <i class="fas fa-trash-alt me-2 text-secondary"></i>O Que Será Removido
                                </h6>
                                <ul class="section-list mb-0">
                                    <li>Sua conta de usuário</li>
                                    <li>Todas as fotos dos anúncios</li>
                                    <li>Todos os vídeos e áudios</li>
                                    <li>Dados pessoais</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="modal-footer delete-account-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="deleteAccountCancelBtn" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger rounded-pill px-4" id="deleteAccountConfirmBtn">
                            <i class="fas fa-trash-alt me-2"></i>Sim, Excluir Tudo
                        </button>
                    </div>
                </div>
            </div>
        </div>

    
    <!-- Modal de Exclusão de Conta do Usuário -->
    <div class="modal fade modal-theme-login" id="confirmDeleteAccountModal" tabindex="-1" aria-labelledby="confirmDeleteAccountLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="confirmDeleteAccountLabel">Excluir Conta do Usuário</h5>
          </div>
          <div class="modal-body">
            Tem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteAccountBtn">Confirmar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ** FIM DO HTML DOS MODAIS GLOBAIS ** -->

    <!-- Scripts JavaScript -->
    <!-- 1. Primeiro, carregue o Bootstrap JS (MUITO IMPORTANTE!) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 2. Carregue jQuery com fallback local -->
    <script>
        // Carregar jQuery com fallback
        function loadJQuery() {
            const script = document.createElement('script');
            script.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js';
            script.onload = () => {
                console.log('✅ jQuery carregado via CDN');
                loadInputmask();
            };
            script.onerror = () => {
                console.warn('⚠️ CDN jQuery falhou, carregando local...');
                const localScript = document.createElement('script');
                localScript.src = '<?php echo URLADM; ?>assets/js/jquery-3.7.1.min.js';
                localScript.onload = () => {
                    console.log('✅ jQuery carregado localmente');
                    loadInputmask();
                };
                localScript.onerror = () => {
                    console.error('❌ Falha ao carregar jQuery (CDN e local)');
                };
                document.head.appendChild(localScript);
            };
            document.head.appendChild(script);
        }
        
        // Carregar Inputmask com fallback local
        function loadInputmask() {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js';
            script.onload = () => {
                console.log('✅ Inputmask carregado via CDN');
                initJQueryReady();
            };
            script.onerror = () => {
                console.warn('⚠️ CDN Inputmask falhou, carregando local...');
                const localScript = document.createElement('script');
                localScript.src = '<?php echo URLADM; ?>assets/js/jquery.inputmask.min.js';
                localScript.onload = () => {
                    console.log('✅ Inputmask carregado localmente');
                    initJQueryReady();
                };
                localScript.onerror = () => {
                    console.error('❌ Falha ao carregar Inputmask (CDN e local)');
                };
                document.head.appendChild(localScript);
            };
            document.head.appendChild(script);
        }
        
        // Inicializar quando jQuery estiver pronto
        function initJQueryReady() {
            if (typeof $ !== 'undefined' && typeof $.fn.inputmask !== 'undefined') {
                console.log('✅ jQuery e Inputmask prontos para uso');
                // Expor função globalmente
                window.waitForJQuery = function(callback) {
                    callback();
                };
            } else {
                console.log('⏳ Aguardando jQuery e Inputmask...');
                setTimeout(initJQueryReady, 100);
            }
        }
        
        // Iniciar carregamento
        loadJQuery();
    </script>

    <!-- 4. Em seguida, carregue general-utils.js -->
    <script src="<?php echo URLADM; ?>assets/js/general-utils.js?v=<?php echo time(); ?>"></script>

    <!-- 5. Depois, carregue Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- 6. Carregue o script original do formulário -->
    <script src="<?php echo URLADM; ?>assets/js/mascaras_funcionais.js"></script>
    
    <!-- 7. Por fim, carregue SEU SCRIPT PRINCIPAL SPA que gerencia o carregamento dinâmico -->
    <!-- Os outros scripts específicos de página serão carregados por dashboard_custom.js -->
    <script src="<?php echo URLADM; ?>assets/js/dashboard_custom.js?v=<?php echo time(); ?>"></script>
    
    <!-- 8. Script específico para modal de exclusão de conta - REMOVIDO (integrado no dashboard_custom.js) -->
    
    <!-- 8. LAZY LOADING: Carregar scripts pesados apenas quando necessário -->
    <script>
        // Função para carregar scripts dinamicamente
        function loadScript(src, callback) {
            const script = document.createElement('script');
            script.src = src;
            script.onload = callback;
            script.onerror = () => console.error('Erro ao carregar script:', src);
            document.head.appendChild(script);
        }
        
        // Carregar Chart.js apenas quando necessário
        window.loadChartJS = function() {
            if (!window.Chart) {
                loadScript('https://cdn.jsdelivr.net/npm/chart.js', () => {
                    console.log('Chart.js carregado dinamicamente');
                });
            }
        };
    </script>

    <!-- Modais do Sistema de Mensagens -->
    
    <!-- Modal de Mensagens para Administradores -->
    <div class="modal fade modal-feedback-beautiful modal-theme-login" id="mensagensModal" tabindex="-1" aria-labelledby="mensagensModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-content-beautiful">
                <div class="modal-header modal-header-beautiful bg-primary">
                    <h5 class="modal-title modal-title-beautiful" id="mensagensModalLabel">
                        <i class="fas fa-envelope me-2"></i>Central de Mensagens
                    </h5>
                </div>
                <div class="modal-body modal-body-beautiful p-0">
                    <div id="mensagensContainer" class="p-4" style="max-height: 60vh; overflow-y: auto;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando mensagens...</span>
                            </div>
                            <p class="mt-3 mb-0" style="color: #000000; font-weight: bold; font-size: 16px;">Carregando mensagens...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-beautiful">
                    <button type="button" class="btn btn-warning" id="marcarTodasLidasBtn" style="display: none;">
                        <i class="fas fa-check-double me-2"></i>Marcar Todas como Lidas
                    </button>
                    <button type="button" class="btn btn-primary feedback-btn-beautiful" data-bs-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Contato para Usuários Normais - Design Profissional -->
    <div class="modal fade modal-theme-login" id="contatoModal" tabindex="-1" aria-labelledby="contatoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content contato-modal-profissional">
                <!-- Header com Gradiente -->
                <div class="modal-header contato-modal-header">
                    <div class="contato-header-content">
                        <div class="contato-header-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="contato-header-text">
                            <h5 class="modal-title" id="contatoModalLabel">
                                Entrar em Contato
                            </h5>
                            <p class="contato-header-subtitle">
                                Envie sua mensagem para nossa equipe de suporte
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Body com Formulário -->
                <div class="modal-body contato-modal-body">
                    <form id="contatoForm" class="contato-form-profissional">
                        <!-- Campo Assunto -->
                        <div class="contato-field-group">
                            <label for="contatoAssunto" class="contato-field-label">
                                <i class="fas fa-tag me-2"></i>Assunto da Mensagem
                            </label>
                            <div class="contato-input-wrapper">
                                <input type="text" class="form-control contato-input" id="contatoAssunto" name="assunto" 
                                       placeholder="Digite um título breve e descritivo para sua mensagem..." required>
                                <div class="contato-input-icon">
                                    <i class="fas fa-edit"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campo Mensagem -->
                        <div class="contato-field-group">
                            <label for="contatoMensagem" class="contato-field-label">
                                <i class="fas fa-comment-dots me-2"></i>Sua Mensagem
                            </label>
                            <div class="contato-textarea-wrapper">
                                <textarea class="form-control contato-textarea" id="contatoMensagem" name="mensagem" 
                                          rows="4" placeholder="Descreva detalhadamente sua dúvida, sugestão ou problema. Quanto mais informações você fornecer, melhor poderemos ajudá-lo..." required></textarea>
                                <div class="contato-textarea-icon">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                            </div>
                            <div class="contato-char-counter">
                                <span id="contatoCharCount">0</span> / 500 caracteres
                            </div>
                        </div>
                        
                    </form>
                </div>
                
                <!-- Footer com Botões -->
                <div class="modal-footer contato-modal-footer">
                    <button type="button" class="btn contato-btn-cancel" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn contato-btn-send" id="contatoEnviarBtn">
                        <i class="fas fa-paper-plane me-2"></i>Enviar para Suporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Notificações para Administradores -->
    <div class="modal fade modal-feedback-beautiful modal-theme-login" id="notificacoesModal" tabindex="-1" aria-labelledby="notificacoesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-content-beautiful">
                <div class="modal-header modal-header-beautiful bg-warning">
                    <h5 class="modal-title modal-title-beautiful" id="notificacoesModalLabel">
                        <i class="fas fa-bell me-2"></i>Central de Notificações
                    </h5>
                </div>
                <div class="modal-body modal-body-beautiful p-0">
                    <div id="notificacoesContainer" class="p-4">
                        <div class="text-center">
                            <div class="spinner-border text-warning" role="status">
                                <span class="visually-hidden">Carregando notificações...</span>
                            </div>
                            <p class="mt-3 mb-0" style="color: #000000; font-weight: bold; font-size: 16px;">Carregando notificações...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-beautiful">
                    <button type="button" class="btn btn-primary feedback-btn-beautiful" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts do Sistema de Mensagens -->
    <script src="<?php echo URLADM; ?>assets/js/mensagens.js?v=<?php echo time(); ?>"></script>
    
    <!-- Scripts do Sistema de Notificações -->
    <script src="<?php echo URLADM; ?>assets/js/notificacoes.js?v=<?php echo time(); ?>"></script>
    
    <!-- Scripts do Sistema de Anúncios -->
    <script src="<?php echo URLADM; ?>assets/js/anuncio-forms.js?v=<?php echo time(); ?>"></script>
    
    <!-- Scripts do Sistema de Administração de Anúncios -->
    <script src="<?php echo URLADM; ?>assets/js/anuncio-admin.js?v=<?php echo time(); ?>"></script>
    
</body>
</html>
