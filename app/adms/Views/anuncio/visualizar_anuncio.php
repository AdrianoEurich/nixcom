<?php
/**
 * View para visualizar um anúncio específico - Nixcom
 */

if (!defined('C7E3L8K9E5')) {
    die("Erro: Acesso negado!");
}

// Página de visualização Nixcom carregada

// Verificar se há dados do anúncio
if (isset($this) && isset($this->data) && empty($this->data['anuncio'])) {
    echo "<div class=\"alert alert-danger\">Anúncio não encontrado.</div>";
    return;
} elseif (!isset($this) || !isset($this->data)) {
    // Valores padrão para quando não há contexto de objeto
    $anuncio = [
        'id' => 29,
        'service_name' => 'Anúncio de Teste',
        'description' => 'Descrição de teste',
        'price_1h' => '100.00',
        'phone_number' => '(11) 99999-9999',
        'cover_photo_path' => 'app/public/uploads/anuncios/galeria/68d8964da08ef.jpg',
        'neighborhood_name' => 'Centro',
        'gender' => 'Feminino',
        'city_id' => 1,
        'state_id' => 'SP'
    ];
    } else {
    $anuncio = $this->data['anuncio'];
}

// Função para exibir valores
function displayValue($value) {
    return !empty($value) ? $value : 'Não informado';
}

// Dados básicos
$nome = displayValue($anuncio['nome'] ?? '');
$idade = displayValue($anuncio['idade'] ?? '');
$altura = displayValue($anuncio['altura'] ?? '');
$peso = displayValue($anuncio['peso'] ?? '');
$genero = displayValue($anuncio['genero'] ?? '');
$nacionalidade = displayValue($anuncio['nacionalidade'] ?? '');
$etnia = displayValue($anuncio['etnia'] ?? '');
$corOlhos = displayValue($anuncio['cor_olhos'] ?? '');
$telefone = displayValue($anuncio['telefone'] ?? '');
$descricao = displayValue($anuncio['descricao'] ?? '');
$bairro = displayValue($anuncio['bairro'] ?? '');
$cidade = displayValue($anuncio['cidade'] ?? '');
$estado = displayValue($anuncio['estado'] ?? '');
$fotoPrincipal = displayValue($anuncio['foto_principal'] ?? '');
$planType = displayValue($anuncio['plan_type'] ?? '');

// Mídias
$fotosGaleria = isset($anuncio['todas_fotos']) && is_array($anuncio['todas_fotos']) ? $anuncio['todas_fotos'] : [];
$videos = isset($anuncio['videos']) && is_array($anuncio['videos']) ? $anuncio['videos'] : [];
$audios = isset($anuncio['audios']) && is_array($anuncio['audios']) ? $anuncio['audios'] : [];

// Preços
$preco15min = displayValue($anuncio['price_15min'] ?? '');
$preco30min = displayValue($anuncio['price_30min'] ?? '');
$preco1h = displayValue($anuncio['price_1h'] ?? '');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nome) ?> - Nixcom</title>
    
    <link rel="icon" href="<?= URL ?>app/sts/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/css/site.css">
    
    <script>
        // Verificar se baseUrl já foi declarada para evitar erro de redeclaração
        if (typeof baseUrl === 'undefined') {
            const baseUrl = '<?= URL ?>';
        }
    </script>
    
<style>
    .visualizar-anuncio-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        color: #fff;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    min-height: 100vh;
    }

    .profile-section {
        background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
    border-radius: 20px;
            padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 1px solid #404040;
        transition: all 0.3s ease;
            margin-bottom: 30px;
            min-height: 200px;
    }

    .profile-section:hover {
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        transform: translateY(-2px);
            will-change: transform; /* Otimização para performance */
        }

        .profile-section h3 {
            color: #D4AF37;
            font-size: 24px;
        margin-bottom: 20px;
    font-weight: 700;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        border-bottom: 1px solid #404040;
    }

        .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #D4AF37;
        font-weight: 600;
            font-size: 16px;
    }

    .info-value {
        color: #fff;
        font-size: 16px;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #D4AF37;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
        }

        .plan-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            vertical-align: middle;
        }

        .plan-free { background: #6c757d; color: white; }
        .plan-basic { background: #007bff; color: white; }
        .plan-premium { background: #ffc107; color: #000; }

        .gallery-item {
    position: relative;
    overflow: hidden;
            border-radius: 15px;
            margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .gallery-item img {
    width: 100%;
            height: 250px;
    object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
            will-change: transform; /* Otimização para performance */
        }

        .gallery-overlay {
            position: absolute;
        top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .contact-btn {
            background: linear-gradient(45deg, #25d366, #128c7e);
        border: none;
            padding: 12px 24px;
            border-radius: 25px;
    color: white;
            font-weight: bold;
            font-size: 16px;
        transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
        color: white;
        }

        .price-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
            font-weight: bold;
        font-size: 16px;
            margin: 10px 0;
            display: inline-block;
        }

        .media-item {
            background: rgba(0, 0, 0, 0.3);
    border-radius: 15px;
            padding: 20px;
        margin-bottom: 20px;
            border: 1px solid #404040;
        }

        .media-item h5 {
        color: #D4AF37;
        margin-bottom: 15px;
        }

        .no-media {
        text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .no-media i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #555;
}
</style>
</head>

<body data-bs-spy="scroll" data-bs-target="#navbar">
    <!-- Conteúdo Principal -->
    <div class="visualizar-anuncio-container" style="padding-top: 100px;">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Coluna Principal -->
                <div class="col-lg-12 col-xl-11">
                    <!-- Card de Informações Básicas -->
                    <div class="profile-section">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <img src="<?= !empty($fotoPrincipal) ? $fotoPrincipal : URL . 'app/sts/assets/images/users/usuario.png' ?>" 
                                     alt="<?= htmlspecialchars($nome) ?>" 
                                     class="profile-image">
                        </div>
                            <div class="col-md-9">
                                <div class="d-flex align-items-center mb-4">
                                    <h1 class="mb-0 me-3" style="color: #D4AF37; font-size: 2.5rem;"><?= htmlspecialchars($nome) ?></h1>
                                    <span class="plan-badge plan-<?= strtolower($planType) ?>">
                                        <?= strtoupper($planType) ?>
                                    </span>
                        </div>
                                <p class="lead mb-4" style="font-size: 1.3rem;">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?= htmlspecialchars($bairro) ?>, <?= htmlspecialchars($cidade) ?> - <?= htmlspecialchars($estado) ?>
                                </p>
                                <div class="d-flex flex-wrap gap-4">
                                    <span class="badge bg-light text-dark" style="font-size: 1.1rem; padding: 10px 15px;">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($idade) ?> anos
                                    </span>
                                    <span class="badge bg-light text-dark" style="font-size: 1.1rem; padding: 10px 15px;">
                                        <i class="fas fa-ruler-vertical me-1"></i><?= htmlspecialchars($altura) ?>m
                                    </span>
                                    <span class="badge bg-light text-dark" style="font-size: 1.1rem; padding: 10px 15px;">
                                        <i class="fas fa-weight me-1"></i><?= htmlspecialchars($peso) ?>kg
                                    </span>
                        </div>

                                <div class="mt-3 text-start">
                                    <span class="badge bg-info text-white" style="font-size: 1rem; padding: 8px 12px;">
                                        <i class="fas fa-calendar me-1"></i>Anúncio criado em <?= date('d/m/Y', strtotime($anuncio['created_at'] ?? 'now')) ?>
                                    </span>
                        </div>
            </div>
            </div>
        </div>

                    <!-- Cards de Preços e Contato lado a lado -->
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Card de Preços -->
                            <div class="profile-section">
                                <h3><i class="fas fa-tags me-2"></i>Preços</h3>
                                <?php if (!empty($preco15min)): ?>
                                    <div class="text-center">
                                        <span class="price-badge">15 min - R$ <?= number_format($preco15min, 2, ',', '.') ?></span>
                        </div>
                                <?php endif; ?>

                                <?php if (!empty($preco30min)): ?>
                                    <div class="text-center">
                                        <span class="price-badge">30 min - R$ <?= number_format($preco30min, 2, ',', '.') ?></span>
                        </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($preco1h)): ?>
                                    <div class="text-center">
                                        <span class="price-badge">1 hora - R$ <?= number_format($preco1h, 2, ',', '.') ?></span>
            </div>
                    <?php endif; ?>
                        </div>
            </div>
                        <div class="col-md-6">
                            <!-- Card de Contato -->
                            <div class="profile-section">
                                <h3><i class="fas fa-phone me-2"></i>Contato</h3>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-phone me-2"></i>Telefone</span>
                                    <span class="info-value"><?= htmlspecialchars($telefone) ?></span>
                                </div>
                                
                                <?php if (!empty($telefone)): ?>
                                    <div class="text-center mt-4">
                                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $telefone) ?>" 
                                           target="_blank" 
                                           class="contact-btn">
                                            <i class="fab fa-whatsapp me-2"></i>Entrar em Contato
                                        </a>
            </div>
                    <?php endif; ?>
            </div>
        </div>
                    </div>

                    <!-- Card de Informações Básicas -->
                    <div class="profile-section">
                        <h3><i class="fas fa-info-circle me-2"></i>Informações Básicas</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-eye me-2"></i>Cor dos Olhos</span>
                                    <span class="info-value"><?= htmlspecialchars($corOlhos) ?></span>
                        </div>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-venus-mars me-2"></i>Gênero</span>
                                    <span class="info-value"><?= htmlspecialchars($genero) ?></span>
            </div>
            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-globe me-2"></i>Nacionalidade</span>
                                    <span class="info-value"><?= htmlspecialchars($nacionalidade) ?></span>
        </div>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-palette me-2"></i>Etnia</span>
                                    <span class="info-value"><?= htmlspecialchars($etnia) ?></span>
        </div>
            </div>
                        </div>
        </div>

                    <!-- Card de Descrição -->
                    <div class="profile-section">
                        <h3><i class="fas fa-align-left me-2"></i>Descrição</h3>
                        <p class="info-value" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($descricao)) ?></p>
        </div>

                    <!-- Card de Galeria de Fotos -->
                    <div class="profile-section">
                        <h3><i class="fas fa-images me-2"></i>Galeria de Fotos</h3>
                        <?php if (!empty($fotosGaleria)): ?>
                            <div class="row">
                                <?php foreach ($fotosGaleria as $index => $foto): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="gallery-item">
                                            <img src="<?= htmlspecialchars($foto) ?>" 
                                                 alt="Foto <?= $index + 1 ?>"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#galleryModal"
                                                 data-bs-slide-to="<?= $index ?>">
                                            <div class="gallery-overlay">
                                                <i class="fas fa-search-plus fa-2x text-white"></i>
                        </div>
                    </div>
                                        </div>
                            <?php endforeach; ?>
            </div>
                        <?php else: ?>
                            <div class="no-media">
                                <i class="fas fa-images"></i>
                                <p>Nenhuma foto disponível</p>
        </div>
                <?php endif; ?>
                            </div>
                            </div>
                                        </div>
                        </div>
                            </div>

    <!-- Modal da Galeria -->
    <div class="modal fade" id="galleryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Galeria de Fotos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                <div class="modal-body">
                    <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($fotosGaleria as $index => $foto): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="<?= htmlspecialchars($foto) ?>" 
                                         class="d-block w-100" 
                                         alt="Foto <?= $index + 1 ?>"
                                         style="max-height: 500px; object-fit: contain;">
                                    <div class="carousel-caption d-none d-md-block">
                                        <p class="text-white">
                                            <?= $index === 0 ? '<i class="fas fa-star me-1"></i>Foto de Capa' : "Foto " . ($index + 1) ?>
                                        </p>
            </div>
            </div>
                            <?php endforeach; ?>
            </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                        </div>
                        </div>
                            </div>
                        </div>
                    </div>

    <script src="<?= URL ?>app/sts/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
