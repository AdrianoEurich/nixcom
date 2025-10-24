<?php
// Visualizar anúncio diretamente
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir constante
define('C7E3L8K9E5', true);

// Definir URL
define('URL', 'http://localhost/nixcom/');

// Pegar ID da URL
$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID do anúncio não fornecido");
}

try {
    // Conectar ao banco
    $pdo = new PDO("mysql:host=localhost;dbname=nixcom;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar anúncio
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.service_name as nome,
            a.description as descricao,
            a.price_15min,
            a.price_30min,
            a.price_1h,
            a.phone_number as telefone,
            a.cover_photo_path as foto_principal,
            a.status,
            a.categoria,
            a.age as idade,
            a.height_m as altura,
            a.weight_kg as peso,
            a.gender as genero,
            a.nationality as nacionalidade,
            a.ethnicity as etnia,
            a.eye_color as cor_olhos,
            a.neighborhood_name as bairro,
            c.Nome as cidade,
            e.Nome as estado
        FROM anuncios a
        LEFT JOIN cidade c ON a.city_id = c.Codigo
        LEFT JOIN estado e ON a.state_id = e.Uf
        WHERE a.id = ? 
        AND a.status = 'active'
    ");
    
    $stmt->execute([$id]);
    $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anuncio) {
        die("Anúncio não encontrado ou inativo");
    }
    
    // Debug: mostrar dados do anúncio
    error_log("DEBUG ver_anuncio.php - Anúncio encontrado: " . print_r($anuncio, true));
    
} catch (\Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($anuncio['nome'] ?? 'Perfil') ?> - Nixcom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #D4AF37;
            --secondary-color: #B8860B;
            --accent-color: #ff4081;
            --dark-color: #1a1a1a;
            --light-color: #2d2d2d;
            --gradient-primary: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            --gradient-secondary: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            --gradient-gold: linear-gradient(135deg, #D4AF37, #B8860B);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-primary);
            color: #fff;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .profile-header {
            background: var(--gradient-secondary);
            color: white;
            padding: 60px 0 40px;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid #404040;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 5px solid #D4AF37;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .profile-card {
            background: var(--gradient-secondary);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid #404040;
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .profile-card-header {
            background: rgba(0, 0, 0, 0.2);
            color: #D4AF37;
            padding: 20px;
            font-weight: 600;
            font-size: 1.2rem;
            border-bottom: 1px solid #404040;
        }

        .profile-card-body {
            padding: 30px;
            color: #fff;
        }

        .contact-btn {
            background: var(--gradient-gold);
            color: #000;
            padding: 15px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
            margin: 10px;
        }

        .contact-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(212, 175, 55, 0.4);
            color: #000;
            text-decoration: none;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.2);
            color: #D4AF37;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(0, 0, 0, 0.3);
            color: #D4AF37;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .price-highlight {
            background: var(--gradient-gold);
            color: #000;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 600;
        }

        .info-highlight {
            background: rgba(0, 0, 0, 0.2);
            border-left: 4px solid #D4AF37;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 10px 10px 0;
            color: #fff;
        }
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .media-section img, .media-section video {
            border: 2px solid #D4AF37;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
            transition: transform 0.3s ease;
        }

        .media-section img:hover, .media-section video:hover {
            transform: scale(1.05);
        }

        .badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .text-muted {
            color: #ccc !important;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <!-- Header do Perfil -->
        <section class="profile-header">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
        
        <div class="container">
            <div class="text-center">
                <img src="<?= $anuncio['foto_principal'] ?? 'https://via.placeholder.com/200x200/e91e63/ffffff?text=' . urlencode($anuncio['nome'] ?? 'Perfil') ?>" 
                     alt="<?= htmlspecialchars($anuncio['nome'] ?? 'Perfil') ?>" 
                     class="profile-image">
                
                <h1 class="profile-name"><?= htmlspecialchars($anuncio['nome'] ?? 'Nome não informado') ?></h1>
                
                <div class="text-white">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <?= htmlspecialchars($anuncio['cidade'] ?? 'Cidade não informada') ?>, 
                    <?= htmlspecialchars($anuncio['estado'] ?? 'Estado não informado') ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        <div class="row">
            <!-- Informações Principais -->
            <div class="col-lg-8">
                <!-- Descrição -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fas fa-user me-2"></i>Sobre Mim
                    </div>
                    <div class="profile-card-body">
                        <p class="lead"><?= htmlspecialchars($anuncio['descricao'] ?? 'Descrição não disponível') ?></p>
                    </div>
                </div>

                <!-- Informações Pessoais -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fas fa-info-circle me-2"></i>Informações Pessoais
                    </div>
                    <div class="profile-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Idade:</strong> <?= $anuncio['idade'] ?? 'Não informado' ?> anos</p>
                                <p><strong>Altura:</strong> <?= $anuncio['altura'] ?? 'Não informado' ?>m</p>
                                <p><strong>Peso:</strong> <?= $anuncio['peso'] ?? 'Não informado' ?>kg</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Gênero:</strong> <?= ucfirst($anuncio['genero'] ?? 'Não informado') ?></p>
                                <p><strong>Cor dos Olhos:</strong> <?= ucfirst($anuncio['cor_olhos'] ?? 'Não informado') ?></p>
                                <p><strong>Nacionalidade:</strong> <?= ucfirst($anuncio['nacionalidade'] ?? 'Não informado') ?></p>
                                <p><strong>Etnia:</strong> <?= ucfirst($anuncio['etnia'] ?? 'Não informado') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Preços -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fas fa-tags me-2"></i>Preços
                    </div>
                    <div class="profile-card-body">
                        <?php if ($anuncio['price_15min']): ?>
                        <div class="price-highlight">
                            <i class="fas fa-clock me-2"></i><strong>15 minutos:</strong> R$ <?= number_format($anuncio['price_15min'], 2, ',', '.') ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($anuncio['price_30min']): ?>
                        <div class="price-highlight">
                            <i class="fas fa-clock me-2"></i><strong>30 minutos:</strong> R$ <?= number_format($anuncio['price_30min'], 2, ',', '.') ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($anuncio['price_1h']): ?>
                        <div class="price-highlight">
                            <i class="fas fa-clock me-2"></i><strong>1 hora:</strong> R$ <?= number_format($anuncio['price_1h'], 2, ',', '.') ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contato -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fas fa-phone me-2"></i>Contato
                    </div>
                    <div class="profile-card-body text-center">
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $anuncio['telefone'] ?? '') ?>" 
                           class="contact-btn" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </a>
                        
                        <a href="tel:<?= $anuncio['telefone'] ?? '' ?>" class="contact-btn">
                            <i class="fas fa-phone me-2"></i>Ligar
                        </a>
                    </div>
                </div>

                <!-- Galeria de Fotos -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fas fa-images me-2"></i>Galeria de Fotos
                    </div>
                    <div class="profile-card-body media-section">
                        <?php if ($anuncio['foto_principal']): ?>
                        <div class="mb-3">
                            <img src="<?= htmlspecialchars($anuncio['foto_principal']) ?>" alt="Foto Principal" class="img-fluid rounded" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                        </div>
                        <?php else: ?>
                        <p class="text-muted"><i class="fas fa-info-circle me-2"></i>Nenhuma foto disponível</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Localização -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fas fa-map-marker-alt me-2"></i>Localização
                    </div>
                    <div class="profile-card-body">
                        <p><strong>Cidade:</strong> <?= htmlspecialchars($anuncio['cidade'] ?? 'Não informado') ?></p>
                        <p><strong>Estado:</strong> <?= htmlspecialchars($anuncio['estado'] ?? 'Não informado') ?></p>
                        <?php if ($anuncio['bairro']): ?>
                        <p><strong>Bairro:</strong> <?= htmlspecialchars($anuncio['bairro']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
