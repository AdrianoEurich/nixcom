<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Extrai as variáveis passadas pelo controlador
// Verificar se estamos em contexto de objeto
if (isset($this) && isset($this->data)) {
    extract($this->data);
} else {
    // Valores padrão para quando não há contexto de objeto
    $categoria = 'mulher';
    $anuncios = [];
}

// A variável $categoria já vem do controller via extract($this->data)
$anuncios = $anuncios ?? [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($categoria) ?>s - GPHub</title>
    <link rel="icon" href="<?= URL ?>app/sts/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/css/site.css">
    <style>
        /* =============================================
           CARDS MODERNOS PARA ANÚNCIOS
           ============================================= */
        .anuncio-card-modern {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .anuncio-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .anuncio-image-container {
            position: relative;
            height: 280px;
            overflow: hidden;
        }

        .anuncio-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .anuncio-card-modern:hover .anuncio-image {
            transform: scale(1.05);
        }


        .anuncio-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .anuncio-nome {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .anuncio-location,
        .anuncio-cidade-estado,
        .anuncio-preco {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .anuncio-location i,
        .anuncio-cidade-estado i,
        .anuncio-preco i {
            color: #e74c3c;
            width: 16px;
        }

        .anuncio-bairro {
            font-weight: 600;
            color: #34495e;
        }

        .anuncio-actions-fixed {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 8px;
            flex-direction: column;
        }

        .anuncio-actions-fixed .btn {
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .anuncio-actions-fixed .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
        }

        .anuncio-actions-fixed .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-2px);
            color: white;
        }

        .anuncio-actions-fixed .btn-success {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border: none;
        }

        .anuncio-actions-fixed .btn-success:hover {
            background: linear-gradient(135deg, #20b358 0%, #0f7a6b 100%);
            transform: translateY(-2px);
        }

        /* Responsividade */
        @media (max-width: 1200px) {
            .col-xl-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }

        @media (max-width: 992px) {
            .col-lg-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }

        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (max-width: 576px) {
            .col-sm-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .anuncio-card-modern {
            animation: fadeInUp 0.6s ease forwards;
        }

        .anuncio-card-modern:nth-child(1) { animation-delay: 0.1s; }
        .anuncio-card-modern:nth-child(2) { animation-delay: 0.2s; }
        .anuncio-card-modern:nth-child(3) { animation-delay: 0.3s; }
        .anuncio-card-modern:nth-child(4) { animation-delay: 0.4s; }
    </style>
    <script>
        // Verificar se baseUrl já foi declarada para evitar erro de redeclaração
        if (typeof baseUrl === 'undefined') {
            const baseUrl = '<?= URL ?>';
        }
    </script>
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center <?= $categoria ?>">
                <h1 class="categoria-title">
                    <?php 
                    // Debug: mostrar a categoria
                    echo "<!-- DEBUG: categoria = " . htmlspecialchars($categoria) . " -->";
                    echo "<!-- DEBUG: tipo = " . gettype($categoria) . " -->";
                    echo "<!-- DEBUG: valor exato = '" . $categoria . "' -->";
                    
                    switch($categoria) {
                        case 'mulher':
                            echo 'Mulheres';
                            break;
                        case 'homem':
                            echo 'Homens';
                            break;
                        case 'trans':
                            echo 'Trans';
                            break;
                        default:
                            echo 'Mulheres (default)';
                    }
                    ?>
                </h1>
                <p class="categoria-subtitle">
                    Encontre os melhores profissionais da categoria <?= ucfirst($categoria) ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Anúncios -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($anuncios)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h3>Nenhum anúncio encontrado</h3>
                    <p class="text-muted">Não há anúncios disponíveis para esta categoria no momento.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="anuncio-card-modern">
                                <div class="anuncio-image-container">
                                    <img src="<?= htmlspecialchars($anuncio['foto_principal'] ?? URL . 'app/sts/assets/images/users/usuario.png') ?>" 
                                         alt="<?= htmlspecialchars($anuncio['nome'] ?? 'Perfil') ?>" 
                                         class="anuncio-image">
                                </div>
                                <div class="anuncio-content">
                                    <h5 class="anuncio-nome"><?= htmlspecialchars($anuncio['nome'] ?? 'Perfil') ?></h5>
                                    <div class="anuncio-location">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <span class="anuncio-bairro">Bairro: <?= htmlspecialchars($anuncio['bairro'] ?? 'Não informado') ?></span>
                                    </div>
                                    <div class="anuncio-cidade-estado">
                                        <i class="fas fa-city me-1"></i>
                                        <span><?= htmlspecialchars($anuncio['cidade'] ?? 'Cidade não informada') ?>, <?= htmlspecialchars($anuncio['estado'] ?? 'Estado não informado') ?></span>
                                    </div>
                                    
                                    <!-- Botões fixos no final -->
                                    <div class="anuncio-actions-fixed">
                                        <a href="<?= URL ?>anuncio/visualizar/<?= $anuncio['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Ver Perfil
                                        </a>
                                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $anuncio['telefone']) ?>" 
                                           target="_blank" 
                                           class="btn btn-success btn-sm">
                                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="<?= URL ?>app/sts/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>