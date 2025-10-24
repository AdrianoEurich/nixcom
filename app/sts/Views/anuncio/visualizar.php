<?php
/**
 * View para visualizar um anúncio específico - STS
 */

if (!defined('C7E3L8K9E5')) {
    die("Erro: Acesso negado!");
}

// Página de visualização STS carregada

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

// Mídias - Corrigir estrutura de dados
$fotosGaleria = [];
if (isset($anuncio['todas_fotos']) && is_array($anuncio['todas_fotos'])) {
    foreach ($anuncio['todas_fotos'] as $foto) {
        if (is_array($foto) && isset($foto['path'])) {
            $fotosGaleria[] = $foto['path'];
        } elseif (is_string($foto)) {
            $fotosGaleria[] = $foto;
        }
    }
}

$videos = isset($anuncio['videos']) && is_array($anuncio['videos']) ? $anuncio['videos'] : [];
$audios = isset($anuncio['audios']) && is_array($anuncio['audios']) ? $anuncio['audios'] : [];

// Preços
$preco15min = displayValue($anuncio['price_15min'] ?? '');
$preco30min = displayValue($anuncio['price_30min'] ?? '');
$preco1h = displayValue($anuncio['price_1h'] ?? '');
?>

<!-- CSS específico para esta página -->
<link rel="stylesheet" href="<?= URL ?>app/sts/assets/css/visualizar-anuncio.css">

<!-- Conteúdo Principal -->
<div class="visualizar-anuncio-container" style="padding-top: 100px;">
    <div class="container">
        <div class="row justify-content-center">
            <!-- Coluna Principal -->
            <div class="col-lg-12 col-xl-11">
                <!-- Card de Informações Básicas -->
                <div class="profile-section main-profile-card">
                    <div class="main-profile-container">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                                <div class="profile-image-container">
                            <img src="<?= !empty($fotoPrincipal) ? $fotoPrincipal : URL . 'app/sts/assets/images/users/usuario.png' ?>" 
                                 alt="<?= htmlspecialchars($nome) ?>" 
                                         class="profile-image-modern">
                                    <div class="profile-image-overlay">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                </div>
                        </div>
                        <div class="col-md-9">
                                <div class="profile-header">
                                    <div class="profile-name-section">
                                        <h1 class="profile-name"><?= htmlspecialchars($nome) ?></h1>
                                        <span class="plan-badge-modern plan-<?= strtolower($planType) ?>">
                                    <?= strtoupper($planType) ?>
                                </span>
                            </div>
                                    <div class="profile-location">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?= htmlspecialchars($bairro) ?>, <?= htmlspecialchars($cidade) ?> - <?= htmlspecialchars($estado) ?>
                            </div>
                                    <div class="profile-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-user me-2"></i>
                                            <span><?= htmlspecialchars($idade) ?> anos</span>
                            </div>
                                        <div class="stat-item">
                                            <i class="fas fa-ruler-vertical me-2"></i>
                                            <span><?= htmlspecialchars($altura) ?>m</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-weight me-2"></i>
                                            <span><?= htmlspecialchars($peso) ?>kg</span>
                                        </div>
                                    </div>
                                    <div class="profile-date">
                                        <i class="fas fa-calendar me-2"></i>
                                        Anúncio criado em <?= date('d/m/Y', strtotime($anuncio['created_at'] ?? 'now')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cards de Preços e Contato lado a lado -->
                <div class="row">
                    <div class="col-md-6">
                        <!-- Card de Preços -->
                        <div class="profile-section pricing-card">
                            <h3><i class="fas fa-tags me-2"></i>Preços</h3>
                            <div class="pricing-container">
                            <?php if (!empty($preco15min)): ?>
                                    <div class="price-item">
                                        <div class="price-time">
                                            <i class="fas fa-clock me-2"></i>15 minutos
                                        </div>
                                        <div class="price-value">
                                            R$ <?= number_format($preco15min, 2, ',', '.') ?>
                                        </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($preco30min)): ?>
                                    <div class="price-item">
                                        <div class="price-time">
                                            <i class="fas fa-clock me-2"></i>30 minutos
                                        </div>
                                        <div class="price-value">
                                            R$ <?= number_format($preco30min, 2, ',', '.') ?>
                                        </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($preco1h)): ?>
                                    <div class="price-item">
                                        <div class="price-time">
                                            <i class="fas fa-clock me-2"></i>1 hora
                                        </div>
                                        <div class="price-value">
                                            R$ <?= number_format($preco1h, 2, ',', '.') ?>
                                        </div>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Card de Contato -->
                        <div class="profile-section contact-card">
                            <h3><i class="fas fa-phone me-2"></i>Contato</h3>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <div class="contact-label">
                                        <i class="fas fa-phone me-2"></i>Telefone
                                    </div>
                                    <div class="contact-value">
                                        <?= htmlspecialchars($telefone) ?>
                                    </div>
                                </div>
                                
                                <div class="contact-location">
                                    <div class="contact-location-item">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <span><?= htmlspecialchars($bairro) ?>, <?= htmlspecialchars($cidade) ?> - <?= htmlspecialchars($estado) ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($telefone)): ?>
                                <div class="contact-action">
                                    <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $telefone) ?>" 
                                       target="_blank" 
                                       class="whatsapp-btn">
                                        <i class="fab fa-whatsapp me-2"></i>Entrar em Contato
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card de Informações Básicas -->
                <div class="profile-section info-card">
                    <h3><i class="fas fa-info-circle me-2"></i>Informações Básicas</h3>
                    <div class="info-container">
                        <div class="info-item-modern">
                            <div class="info-label-modern">
                                <i class="fas fa-eye me-2"></i>Cor dos Olhos
                            </div>
                            <div class="info-value-modern"><?= htmlspecialchars($corOlhos) ?></div>
                        </div>
                        <div class="info-item-modern">
                            <div class="info-label-modern">
                                <i class="fas fa-venus-mars me-2"></i>Gênero
                            </div>
                            <div class="info-value-modern"><?= htmlspecialchars($genero) ?></div>
                        </div>
                        <div class="info-item-modern">
                            <div class="info-label-modern">
                                <i class="fas fa-globe me-2"></i>Nacionalidade
                            </div>
                            <div class="info-value-modern"><?= htmlspecialchars($nacionalidade) ?></div>
                        </div>
                        <div class="info-item-modern">
                            <div class="info-label-modern">
                                <i class="fas fa-palette me-2"></i>Etnia
                            </div>
                            <div class="info-value-modern"><?= htmlspecialchars($etnia) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Card de Descrição -->
                <div class="profile-section description-card">
                    <h3><i class="fas fa-align-left me-2"></i>Descrição</h3>
                    <div class="description-container">
                        <div class="description-content">
                            <?= nl2br(htmlspecialchars($descricao)) ?>
                        </div>
                    </div>
                </div>

                <!-- Card de Galeria de Fotos -->
                <div class="profile-section gallery-card">
                    <h3><i class="fas fa-images me-2"></i>Galeria de Fotos</h3>
                    <div class="gallery-container">
                        <?php if (!empty($fotosGaleria)): ?>
                            <div class="modern-gallery">
                                <div class="gallery-grid">
                                    <?php foreach ($fotosGaleria as $index => $foto): ?>
                                        <div class="gallery-item" 
                                             onclick="openImageViewer(<?= $index ?>)">
                                            <img src="<?= htmlspecialchars($foto) ?>" 
                                                 alt="Foto <?= $index + 1 ?>"
                                                 class="gallery-img">
                                            <div class="gallery-overlay">
                                                <i class="fas fa-search-plus"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-info">
                                <i class="fas fa-images"></i>
                                <p>Nenhuma foto disponível</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card de Vídeos -->
                <?php if (!empty($videos)): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-video me-2"></i>Vídeos</h3>
                    <div class="row">
                        <?php foreach ($videos as $index => $video): ?>
                            <div class="col-md-6 mb-3">
                                <div class="media-item">
                                    <h5>Vídeo <?= $index + 1 ?></h5>
                                    <video controls class="w-100" style="max-width: 100%;">
                                        <source src="<?= htmlspecialchars($video['path']) ?>" type="video/mp4">
                                        <source src="<?= htmlspecialchars($video['path']) ?>" type="video/webm">
                                        Seu navegador não suporta o elemento de vídeo.
                                    </video>
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($video['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Card de Áudios -->
                <?php if (!empty($audios)): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-microphone me-2"></i>Áudios</h3>
                    <div class="row">
                        <?php foreach ($audios as $index => $audio): ?>
                            <div class="col-md-6 mb-3">
                                <div class="media-item">
                                    <h5>Áudio <?= $index + 1 ?></h5>
                                    <audio controls class="w-100">
                                        <source src="<?= htmlspecialchars($audio['path']) ?>" type="audio/mpeg">
                                        <source src="<?= htmlspecialchars($audio['path']) ?>" type="audio/wav">
                                        Seu navegador não suporta o elemento de áudio.
                                    </audio>
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($audio['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Visualizador de Imagens Moderno -->
<div id="imageViewer" class="image-viewer">
    <div class="viewer-overlay" onclick="closeImageViewer()"></div>
    <div class="viewer-container">
        <div class="viewer-header">
            <div class="viewer-title">
                <i class="fas fa-images"></i>
                <span>Visualizar Foto</span>
            </div>
            <button class="viewer-close" onclick="closeImageViewer()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="viewer-content">
            <button class="viewer-nav viewer-prev" onclick="prevImage()">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="viewer-image-container">
                <img id="viewerImage" src="" alt="" class="viewer-image">
                <div class="viewer-loader">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <button class="viewer-nav viewer-next" onclick="nextImage()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="viewer-footer">
            <div class="viewer-counter">
                <span id="viewerCurrent">1</span> / <span id="viewerTotal"><?= count($fotosGaleria) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para esta página -->
<script src="<?= URL ?>app/sts/assets/js/visualizar-anuncio.js"></script>