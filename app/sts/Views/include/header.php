<?php
// Verificação de segurança para evitar acesso direto
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nixcom - Soluções Digitais</title>

    <link rel="icon" href="<?= URL ?>app/sts/assets/images/icon/favicon.ico">

    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/css/site.css">

    <script>
        const baseUrl = '<?= URL ?>';
    </script>
</head>

<body data-bs-spy="scroll" data-bs-target="#navbar">
    <nav id="navbar" class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <span class="brand-highlight">Nix</span>com
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#servicos">Serviços</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contato">Contato</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLADM; ?>login">Login</a>
                    </li>
                    <li class="nav-item nav-item-btn">
                        <a class="btn btn-primary btn-animate" href="#anuncie">Anuncie Grátis</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>