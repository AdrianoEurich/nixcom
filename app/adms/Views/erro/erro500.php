<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>
<div>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro 5000 - Nixcom</title>
        <link rel="stylesheet" href="<?= URLADM ?>assets/bootstrap/css/bootstrap.min.css">
        <style>
            .error-container {
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
            }
        </style>
    </head>

    <body>
        <div class="error-container">
            <h1 class="text-danger">500</h1>
            <h2>Erro Interno do Servidor</h2>
            <p>Desculpe, algo deu errado no nosso servidor.</p>
            <a href="<?= URLADM ?>" class="btn btn-primary mt-3">Voltar à Página Inicial</a>
        </div>
</div>
</body>

</html>