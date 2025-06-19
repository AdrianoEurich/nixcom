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
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/dashboard_custom.css">
    <link rel="shortcut icon" href="<?= URLADM ?>assets/images/icon/favicon.ico" type="image/x-icon">
</head>
<body class="layout-test h-100">
    <script>
        // Garante que as constantes PHP URL e URLADM estejam definidas no JS
        const URL = "<?= URL ?>";
        const URLADM = "<?= URLADM ?>";
    </script>
    <?php include_once $includeBasePath . 'topbar.php'; ?>

    <div class="d-flex" id="wrapper">
        <?php include_once $includeBasePath . 'sidebar.php'; ?>

        <div id="content-wrapper" class="container-fluid">
            <div class="main-content" id="dynamic-content">
                <?php
                // Esta variável ($viewContent) é definida no ConfigViewAdm::loadView()
                // e contém o caminho completo para a view de conteúdo (ex: dashboard/content_dashboard.php).
                if (isset($viewContent) && file_exists($viewContent)) {
                    include_once $viewContent;
                } else {
                    echo "<div class='alert alert-info'>Nenhum conteúdo específico definido para esta página ou o arquivo não foi encontrado.</div>";
                    // Em um ambiente de produção, você pode redirecionar para uma página de erro 404
                    // header("Location: " . URLADM . "erro/index/404"); exit();
                }
                ?>
            </div>
            <?php // include_once $includeBasePath . 'footer.php'; ?>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="<?= URLADM ?>assets/js/dashboard_custom.js"></script>
    <script src="<?= URLADM ?>assets/js/anuncio.js"></script>

</body>
</html>