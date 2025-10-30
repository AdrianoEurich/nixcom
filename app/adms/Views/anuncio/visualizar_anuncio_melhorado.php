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
    
    <link rel="icon" href="http://localhost/nixcom/app/sts/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="http://localhost/nixcom/app/sts/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="http://localhost/nixcom/app/sts/assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="http://localhost/nixcom/app/sts/assets/css/site.css">
    
    <script>
        // Verificar se baseUrl já foi declarada para evitar erro de redeclaração
        if (typeof baseUrl === 'undefined') {
            const baseUrl = 'http://localhost/nixcom/';
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

        /* Estilos para o card de aparência */
        .appearance-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #e74c3c;
            position: relative;
            overflow: hidden;
        }

        .appearance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e74c3c, #c0392b, #e74c3c);
        }

        .appearance-container {
            padding: 20px 0;
        }

        .appearance-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .appearance-tag {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3);
        }

        .appearance-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.5);
        }

        /* Estilos para o card de idiomas */
        .languages-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #28a745;
            position: relative;
            overflow: hidden;
        }

        .languages-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #28a745);
        }

        .languages-container {
            padding: 20px 0;
        }

        .language-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .language-tag {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        .language-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.5);
        }

        /* Estilos para o card de locais */
        .locations-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #ffc107;
            position: relative;
            overflow: hidden;
        }

        .locations-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #fd7e14, #ffc107);
        }

        .locations-container {
            padding: 20px 0;
        }

        .location-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .location-tag {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #000;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3);
        }

        .location-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.5);
        }

        /* Estilos para o card de pagamentos */
        .payments-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #17a2b8;
            position: relative;
            overflow: hidden;
        }

        .payments-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #6f42c1, #17a2b8);
        }

        .payments-container {
            padding: 20px 0;
        }

        .payment-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .payment-tag {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
        }

        .payment-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.5);
        }

        /* Estilos para o card de serviços */
        .services-card {
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            border: 2px solid #dc3545;
            position: relative;
            overflow: hidden;
        }

        .services-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333, #dc3545);
        }

        .services-container {
            padding: 20px 0;
        }

        .service-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .service-tag {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }

        .service-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.5);
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

        /* Estilos para o card principal (foto e informações) */
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
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            padding: 20px 0;
        }

        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            aspect-ratio: 2/3; /* mais alto no desktop */
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

        /* Responsividade */
        @media (min-width: 1400px) {
            .gallery-item { min-height: 500px; }
        }
        @media (max-width: 1399.98px) and (min-width: 1200px) {
            .gallery-item { min-height: 440px; }
        }
        @media (max-width: 1199.98px) and (min-width: 992px) {
            .gallery-item { min-height: 380px; }
        }
        @media (max-width: 991.98px) and (min-width: 577px) {
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
            .gallery-item { min-height: 320px; }
        }
        @media (max-width: 576px) {
            .gallery-grid {
                grid-template-columns: 1fr; /* 1 por linha no mobile pequeno */
                gap: 14px;
            }
            .gallery-item {
                aspect-ratio: 4/3; /* cards maiores em mobile */
                min-height: unset;
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
                                </div>
                        </div>
                            <div class="col-md-9">
                                    <div class="profile-header">
                                        <div class="profile-name-section">
                                            <h1 class="profile-name"><?= htmlspecialchars($nome) ?></h1>
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
                                            <span class="price-time">
                                                <i class="fas fa-clock me-2"></i>15 minutos
                                            </span>
                                            <span class="price-value">R$ <?= number_format($preco15min, 2, ',', '.') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($preco30min)): ?>
                                        <div class="price-item">
                                            <span class="price-time">
                                                <i class="fas fa-clock me-2"></i>30 minutos
                                            </span>
                                            <span class="price-value">R$ <?= number_format($preco30min, 2, ',', '.') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($preco1h)): ?>
                                        <div class="price-item">
                                            <span class="price-time">
                                                <i class="fas fa-clock me-2"></i>1 hora
                                            </span>
                                            <span class="price-value">R$ <?= number_format($preco1h, 2, ',', '.') ?></span>
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
                                <span class="info-label-modern">
                                    <i class="fas fa-eye me-2"></i>Cor dos Olhos
                                </span>
                                <span class="info-value-modern"><?= htmlspecialchars($corOlhos) ?></span>
                            </div>
                            <div class="info-item-modern">
                                <span class="info-label-modern">
                                    <i class="fas fa-venus-mars me-2"></i>Gênero
                                </span>
                                <span class="info-value-modern"><?= htmlspecialchars($genero) ?></span>
                            </div>
                            <div class="info-item-modern">
                                <span class="info-label-modern">
                                    <i class="fas fa-globe me-2"></i>Nacionalidade
                                </span>
                                <span class="info-value-modern"><?= htmlspecialchars($nacionalidade) ?></span>
                            </div>
                            <div class="info-item-modern">
                                <span class="info-label-modern">
                                    <i class="fas fa-palette me-2"></i>Etnia
                                </span>
                                <span class="info-value-modern"><?= htmlspecialchars($etnia) ?></span>
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
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#galleryModal"
                                                 data-bs-slide-to="<?= $index ?>">
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
                            <?php if (!empty($fotosGaleria)): ?>
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
                            <?php endif; ?>
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

    <script>
        (function(){
            function setupGalleryBehavior(){
                var isSmall = window.innerWidth <= 576;
                var grid = document.querySelector('.modern-gallery .gallery-grid');
                if (!grid) return;
                grid.querySelectorAll('.gallery-item').forEach(function(item){
                    var img = item.querySelector('img');
                    if (isSmall) {
                        // Remover comportamento de carrossel no mobile pequeno
                        item.removeAttribute('data-bs-toggle');
                        item.removeAttribute('data-bs-target');
                        item.removeAttribute('data-bs-slide-to');
                        item.style.cursor = 'pointer';
                        if (!item._mobileBound) {
                            item.addEventListener('click', function(){
                                if (img && img.src) {
                                    window.open(img.src, '_blank');
                                }
                            });
                            item._mobileBound = true;
                        }
                    } else {
                        // Desktop/tablet mantêm o carrossel pelo markup existente
                    }
                });
            }
            try {
                window.addEventListener('DOMContentLoaded', setupGalleryBehavior);
                window.addEventListener('resize', setupGalleryBehavior);
            } catch(e) { /* silencioso */ }
        })();
    </script>
    <script src="http://localhost/nixcom/app/sts/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>