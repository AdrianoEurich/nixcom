<?php
// app/adms/Views/layout/main.php
// Este é o layout principal da área administrativa.
// Ele inclui a topbar, sidebar e carrega o conteúdo dinâmico da view.

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// A variável $viewContent será definida no ConfigViewAdm::loadView()
// e conterá o caminho completo para a view de conteúdo (ex: app/adms/Views/dashboard/content_dashboard.php).
// As variáveis passadas pelo controlador (ex: $user_data, $sidebar_active)
// também estarão disponíveis aqui via extract($this->data) no ConfigViewAdm.

// Define o caminho base para inclusão de arquivos (sidebar, topbar, etc.)
// __DIR__ é o diretório atual (app/adms/Views/layout)
// '/../include/' vai para app/adms/Views/include/
$includeBasePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR;

// --- INÍCIO DA LÓGICA PARA GARANTIR O ESTADO CORRETO DO ANÚNCIO NO BODY ---
// Inclui o modelo AdmsAnuncio para consultar o banco de dados
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'AdmsAnuncio.php';
use Adms\Models\AdmsAnuncio;

$has_anuncio = false;
$anuncio_status = 'not_found';
$current_user_id = $_SESSION['user_id'] ?? ''; // Pega o user_id da sessão
$current_user_name = $_SESSION['user_name'] ?? 'Usuário'; // Pega o nome do usuário da sessão
$current_user_role = $_SESSION['user_role'] ?? 'normal'; // Pega o papel do usuário da sessão (ex: 'admin', 'normal')
$current_user_photo = $_SESSION['user_photo_path'] ?? 'usuario.png'; // NOVO: Pega o caminho da foto do usuário da sessão

// Verifica se o usuário está logado para buscar o status do anúncio
if (!empty($current_user_id)) {
    $admsAnuncioModel = new AdmsAnuncio();
    $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($current_user_id);

    if ($existingAnuncio) {
        $has_anuncio = true;
        $anuncio_status = $existingAnuncio['status'];
    }
    error_log("DEBUG PHP MAIN: user_id=" . $current_user_id . ", has_anuncio=" . ($has_anuncio ? 'true' : 'false') . ", anuncio_status=" . $anuncio_status . ", user_role=" . $current_user_role);
} else {
    error_log("DEBUG PHP MAIN: user_id não encontrado na sessão. has_anuncio=false, anuncio_status=not_found, user_role=" . $current_user_role);
}
// --- FIM DA LÓGICA ---

// Assumindo que URL e URLADM são definidas em ConfigAdm.php e estão disponíveis globalmente
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Nixcom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo URLADM; ?>assets/css/dashboard_custom.css">
    <link rel="shortcut icon" href="<?php echo URLADM; ?>assets/images/icon/favicon.ico" type="image/x-icon">
</head>
<!-- AQUI É ONDE ADICIONAMOS O data-user-id, data-has-anuncio, data-anuncio-status, data-user-role e data-user-name -->
<body id="page-top"
      data-user-id="<?= htmlspecialchars($current_user_id); ?>"
      data-has-anuncio="<?= htmlspecialchars($has_anuncio ? 'true' : 'false') ?>"
      data-anuncio-status="<?= htmlspecialchars($anuncio_status) ?>"
      data-user-role="<?= htmlspecialchars($current_user_role) ?>"
      data-user-name="<?= htmlspecialchars($current_user_name) ?>"
      data-user-photo="<?= htmlspecialchars($current_user_photo) ?>"> <!-- NOVO: Adicionado data-user-photo -->
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
    </script>
    <?php include_once $includeBasePath . 'topbar.php'; ?>

    <div class="d-flex" id="wrapper">
        <?php include_once $includeBasePath . 'sidebar.php'; ?>

        <div id="content-wrapper" class="container-fluid">
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
    <!-- ** FIM DO HTML DOS MODAIS GLOBAIS ** -->

    <!-- Scripts JavaScript -->
    <!-- 1. Primeiro, carregue o Bootstrap JS (MUITO IMPORTANTE!) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 2. Carregue jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- 3. Carregue jQuery.Inputmask (NOVO) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>

    <!-- 4. Em seguida, carregue general-utils.js -->
    <script src="<?php echo URLADM; ?>assets/js/general-utils.js"></script>

    <!-- 5. Depois, carregue Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- 6. Por fim, carregue SEU SCRIPT PRINCIPAL SPA que gerencia o carregamento dinâmico -->
    <!-- Os outros scripts específicos de página serão carregados por dashboard_custom.js -->
    <script src="<?php echo URLADM; ?>assets/js/dashboard_custom.js"></script>

</body>
</html>
