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
    
    <link rel="icon" href="http://localhost/nixcom/app/sts/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="http://localhost/nixcom/app/sts/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="http://localhost/nixcom/app/sts/assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="http://localhost/nixcom/app/sts/assets/css/site.css">
    
    <style>
        /* CSS para Visualização de Anúncios - Nixcom */
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
            will-change: transform;
        }

        .profile-section h3 {
            color: #D4AF37;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Estilos para o card principal */
        .main-profile-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 3px solid #D4AF37;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .main-profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #D4AF37, #FFD700, #D4AF37, #FFD700, #D4AF37);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .main-profile-container {
            padding: 30px;
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
        }

        .profile-image-modern {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #D4AF37;
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
            transition: all 0.3s ease;
        }

        .profile-image-modern:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 50px rgba(212, 175, 55, 0.6);
        }

        .profile-image-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #D4AF37, #FFD700);
            color: #000;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .profile-header {
            padding-left: 20px;
        }

        .profile-name-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .profile-name {
            color: #D4AF37;
            font-size: 3rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            background: linear-gradient(45deg, #D4AF37, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .plan-badge-modern {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .plan-badge-modern.plan-free {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .plan-badge-modern.plan-basic {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .plan-badge-modern.plan-premium {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #000;
        }

        .profile-location {
            color: #fff;
            font-size: 1.4rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(255, 215, 0, 0.05));
            padding: 15px 20px;
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .profile-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            color: #fff;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(255, 215, 0, 0.1));
        }

        .profile-date {
            color: #17a2b8;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(111, 66, 193, 0.05));
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(23, 162, 184, 0.3);
            display: inline-flex;
        }

        /* Estilos para o card de preços */
        .pricing-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #D4AF37;
            position: relative;
            overflow: hidden;
            min-height: 300px;
            display: flex;
            flex-direction: column;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #D4AF37, #FFD700, #D4AF37);
        }

        .pricing-container {
            padding: 20px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin: 10px 0;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(255, 215, 0, 0.05));
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            transition: all 0.3s ease;
        }

        .price-item:hover {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(255, 215, 0, 0.1));
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
        }

        .price-time {
            color: #D4AF37;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .price-value {
            color: #fff;
            font-weight: bold;
            font-size: 18px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Estilos para o card de contato */
        .contact-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #25d366;
            position: relative;
            overflow: hidden;
            min-height: 300px;
            display: flex;
            flex-direction: column;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #25d366, #128c7e, #25d366);
        }

        .contact-info {
            padding: 20px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .contact-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin: 10px 0;
            background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(18, 140, 126, 0.05));
            border-radius: 15px;
            border: 1px solid rgba(37, 211, 102, 0.3);
        }

        .contact-label {
            color: #25d366;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .contact-value {
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .contact-location {
            margin: 15px 0;
            padding: 15px 20px;
            background: linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(18, 140, 126, 0.05));
            border-radius: 15px;
            border: 1px solid rgba(37, 211, 102, 0.3);
        }

        .contact-location-item {
            color: #25d366;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .contact-location-item i {
            color: #25d366;
            font-size: 18px;
        }

        .contact-action {
            text-align: center;
            margin-top: auto;
            padding-top: 20px;
        }

        .whatsapp-btn {
            background: linear-gradient(45deg, #25d366, #128c7e);
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }

        .whatsapp-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.5);
            color: white;
            text-decoration: none;
        }

        /* Estilos para o card de informações básicas */
        .info-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #17a2b8;
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #6f42c1, #17a2b8);
        }

        .info-container {
            padding: 20px 0;
        }

        .info-item-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin: 10px 0;
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(111, 66, 193, 0.05));
            border-radius: 15px;
            border: 1px solid rgba(23, 162, 184, 0.3);
            transition: all 0.3s ease;
        }

        .info-item-modern:hover {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.2), rgba(111, 66, 193, 0.1));
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.2);
        }

        .info-label-modern {
            color: #17a2b8;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .info-value-modern {
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Estilos para o card de descrição */
        .description-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #6f42c1;
            position: relative;
            overflow: hidden;
        }

        .description-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6f42c1, #e83e8c, #6f42c1);
        }

        .description-container {
            padding: 20px 0;
        }

        .description-content {
            background: linear-gradient(135deg, rgba(111, 66, 193, 0.1), rgba(232, 62, 140, 0.05));
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(111, 66, 193, 0.3);
            color: #fff;
            font-size: 16px;
            line-height: 1.6;
            text-align: justify;
        }

        /* Estilos para o card da galeria */
        .gallery-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #e83e8c;
            position: relative;
            overflow: hidden;
        }

        .gallery-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e83e8c, #dc3545, #e83e8c);
        }

        .gallery-container {
            padding: 20px 0;
        }

        /* Galeria Moderna */
        .modern-gallery {
            width: 100%;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            padding: 20px 0;
        }

        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            aspect-ratio: 1;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
            display: block;
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(232, 62, 140, 0.8), rgba(220, 53, 69, 0.8));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-overlay i {
            color: white;
            font-size: 20px;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay i {
            transform: scale(1);
        }

        /* Estilos para mensagens de "sem informação" */
        .no-info {
            text-align: center;
            padding: 30px 20px;
            color: #888;
            font-style: italic;
            background: linear-gradient(135deg, rgba(136, 136, 136, 0.1), rgba(136, 136, 136, 0.05));
            border-radius: 15px;
            border: 1px solid rgba(136, 136, 136, 0.3);
        }

        .no-info i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #666;
        }

        /* Visualizador de Imagens Moderno */
        .image-viewer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1050;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-viewer.show {
            display: flex;
            opacity: 1;
        }

        .viewer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .viewer-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(145deg, #1a1a1a, #2d2d2d);
            max-width: 90vw;
            max-height: 90vh;
            margin: auto;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .viewer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, rgba(232, 62, 140, 0.1), rgba(220, 53, 69, 0.1));
            border-bottom: 1px solid rgba(232, 62, 140, 0.2);
        }

        .viewer-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e83e8c;
            font-size: 16px;
            font-weight: bold;
        }

        .viewer-close {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e83e8c, #dc3545);
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .viewer-close:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(232, 62, 140, 0.4);
        }

        .viewer-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 0;
            min-height: 0;
        }

        .viewer-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(232, 62, 140, 0.9), rgba(220, 53, 69, 0.9));
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .viewer-prev {
            left: 20px;
            transform: translateY(-50%);
        }

        .viewer-next {
            right: 20px;
            transform: translateY(-50%);
        }

        .viewer-nav:hover {
            background: linear-gradient(135deg, rgba(232, 62, 140, 1), rgba(220, 53, 69, 1));
            transform: translateY(-50%) scale(1.1);
        }

        .viewer-image-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 80px;
            box-sizing: border-box;
            overflow: visible;
        }

        .viewer-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: transform 0.3s ease;
            display: block;
        }

        .viewer-image:hover {
            transform: scale(1.02);
        }

        .viewer-loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }

        .viewer-loader.show {
            display: block;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid rgba(232, 62, 140, 0.3);
            border-top: 3px solid #e83e8c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .viewer-footer {
            padding: 15px 20px;
            background: linear-gradient(135deg, rgba(232, 62, 140, 0.1), rgba(220, 53, 69, 0.1));
            border-top: 1px solid rgba(232, 62, 140, 0.2);
        }

        .viewer-counter {
            text-align: center;
            color: #e83e8c;
            font-weight: bold;
            font-size: 14px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 10px;
            }
            
            .viewer-container {
                max-width: 95%;
                max-height: 80%;
                margin: 10px auto;
            }
            
            .viewer-header {
                padding: 12px 15px;
            }
            
            .viewer-title {
                font-size: 14px;
            }
            
            .viewer-close {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
            
            .viewer-nav {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }
            
            .viewer-prev {
                left: 10px;
                transform: translateY(-50%);
            }
            
            .viewer-next {
                right: 10px;
                transform: translateY(-50%);
            }
            
            .viewer-content {
                padding: 0;
            }
            
            .viewer-image-container {
                padding: 50px 60px;
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 8px;
            }
        }

        /* Garantir que o navbar tenha z-index correto */
        .navbar {
            z-index: 1030 !important;
        }
        
        .navbar-collapse {
            z-index: 1031 !important;
        }
    </style>
    
    <script>
        // Verificar se baseUrl já foi declarada para evitar erro de redeclaração
        if (typeof baseUrl === 'undefined') {
            const baseUrl = 'http://localhost/nixcom/';
        }
    </script>
</head>

<body data-bs-spy="scroll" data-bs-target="#navbar">
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
                                <img src="<?= !empty($fotoPrincipal) ? $fotoPrincipal : 'http://localhost/nixcom/app/sts/assets/images/users/usuario.png' ?>" 
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

    <script src="http://localhost/nixcom/app/sts/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="http://localhost/nixcom/app/sts/assets/js/personalizado.js"></script>
    <script src="http://localhost/nixcom/app/sts/assets/js/visualizar-anuncio.js"></script>
</body>
</html>
