<?php
// app/adms/Views/anuncio/visualizar_anuncio.php
// Layout redesenhado baseado no site de acompanhantes

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Debugging: Loga os dados do anúncio recebidos na view
error_log("DEBUG VIEW: anuncio_data (visualizar_anuncio.php) recebido. ID: " . ($anuncio_data['id'] ?? 'N/A'));

// Define PATH_ROOT se ainda não estiver definido.
if (!defined('PATH_ROOT')) {
    $parsed_url = parse_url(URL);
    $path_segment = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : ''; 
    
    if (!empty($path_segment)) {
        define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $path_segment . DIRECTORY_SEPARATOR);
    } else {
        define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
    }
}

// Carrega os dados dos estados para mapear UF para nome completo
$states_data = [];
$states_json_filepath = PATH_ROOT . 'app/adms/assets/js/data/states.json';

if (file_exists($states_json_filepath)) {
    $states_json_content = file_get_contents($states_json_filepath);
    $decoded_states = json_decode($states_json_content, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded_states['data'])) {
        $states_data = $decoded_states['data'];
    }
}

/**
 * Retorna o nome completo do estado a partir da UF.
 */
function getStateNameFromUf(string $uf, array $states_data): string
{
    if (empty($uf)) {
        return 'Não informado';
    }
    foreach ($states_data as $state) {
        if (isset($state['Uf']) && $state['Uf'] === $uf && isset($state['Nome'])) {
            return $state['Nome'];
        }
    }
    return 'Não informado';
}

/**
 * Função auxiliar para exibir valores com fallback.
 */
function displayValue($value, $default = 'Não informado')
{
    return !empty($value) ? $value : $default;
}

// Dados básicos
$anuncio = $anuncio_data ?? [];
$nome = displayValue($anuncio['service_name'] ?? '');
$idade = displayValue($anuncio['age'] ?? '');
$telefone = displayValue($anuncio['phone_number'] ?? '');
$altura = displayValue($anuncio['height_m'] ?? '');
$peso = displayValue($anuncio['weight_kg'] ?? '');
$corOlhos = displayValue($anuncio['eye_color'] ?? '');
$genero = displayValue($anuncio['gender'] ?? '');
$nacionalidade = displayValue($anuncio['nationality'] ?? '');
$etnia = displayValue($anuncio['ethnicity'] ?? '');
$descricao = displayValue($anuncio['description'] ?? '');

// Localização
$cidade = $anuncio['city_name'] ?? $anuncio['city'] ?? $anuncio['cidade'] ?? '';
$estado = $anuncio['state_name'] ?? $anuncio['state'] ?? $anuncio['estado'] ?? $anuncio['state_id'] ?? '';
$bairro = $anuncio['neighborhood_name'] ?? $anuncio['neighborhood_id'] ?? $anuncio['bairro'] ?? '';

// Se a cidade veio como código numérico, mostrar como "Não informado"
if (is_numeric($cidade)) {
    $cidade = 'Não informado';
}

// Se o estado veio como UF (2 letras), converter para nome completo
if (strlen($estado) == 2) {
    $estado = getStateNameFromUf($estado, $states_data);
}

// Preços
$preco15min = displayValue($anuncio['price_15min'] ?? '');
$preco30min = displayValue($anuncio['price_30min'] ?? '');
$preco1h = displayValue($anuncio['price_1h'] ?? '');

// Mídias
$fotoPrincipal = $anuncio['cover_photo_path'] ?? URLADM . 'assets/images/anuncios/default_cover.jpg';
$videoConfirmacao = $anuncio['confirmation_video_path'] ?? '';
$fotosGaleria = $anuncio['fotos_galeria'] ?? [];
$videos = $anuncio['videos'] ?? [];
$audios = $anuncio['audios'] ?? [];

// Dados relacionados - Convertendo arrays para strings quando necessário
$aparencia = is_array($anuncio['aparencia'] ?? []) ? implode(', ', $anuncio['aparencia']) : ($anuncio['aparencia'] ?? '');
$idiomas = is_array($anuncio['idiomas'] ?? []) ? implode(', ', $anuncio['idiomas']) : ($anuncio['idiomas'] ?? '');
$locaisAtendimento = is_array($anuncio['locais_atendimento'] ?? []) ? implode(', ', $anuncio['locais_atendimento']) : ($anuncio['locais_atendimento'] ?? '');
$formasPagamento = is_array($anuncio['formas_pagamento'] ?? []) ? implode(', ', $anuncio['formas_pagamento']) : ($anuncio['formas_pagamento'] ?? '');
$servicos = is_array($anuncio['servicos'] ?? []) ? implode(', ', $anuncio['servicos']) : ($anuncio['servicos'] ?? '');

// Status e datas
$status = displayValue($anuncio['status'] ?? '');
$dataCriacao = displayValue($anuncio['created_at'] ?? '');
$dataModificacao = displayValue($anuncio['updated_at'] ?? '');
$visitas = displayValue($anuncio['visits'] ?? '0');
$tipoPlano = displayValue($anuncio['plan_type'] ?? '');

// Debugging: Loga os dados processados
error_log("DEBUG VIEW: Dados processados - Nome: $nome, Idade: $idade, Telefone: $telefone");
error_log("DEBUG VIEW: Mídias - Fotos Galeria: " . count($fotosGaleria) . ", Vídeos: " . count($videos) . ", Áudios: " . count($audios));
error_log("DEBUG VIEW: Foto Principal: " . $fotoPrincipal);
error_log("DEBUG VIEW: URLADM: " . (defined('URLADM') ? URLADM : 'NÃO DEFINIDO'));
error_log("DEBUG VIEW: Cidade: '$cidade', Estado: '$estado', Bairro: '$bairro'");
error_log("DEBUG VIEW: Campos de localização - city_name: " . ($anuncio['city_name'] ?? 'NÃO DEFINIDO') . ", city: " . ($anuncio['city'] ?? 'NÃO DEFINIDO') . ", cidade: " . ($anuncio['cidade'] ?? 'NÃO DEFINIDO'));
error_log("DEBUG VIEW: Campos de estado - state_name: " . ($anuncio['state_name'] ?? 'NÃO DEFINIDO') . ", state: " . ($anuncio['state'] ?? 'NÃO DEFINIDO') . ", estado: " . ($anuncio['estado'] ?? 'NÃO DEFINIDO') . ", state_id: " . ($anuncio['state_id'] ?? 'NÃO DEFINIDO'));
error_log("DEBUG VIEW: Campos de bairro - neighborhood_id: " . ($anuncio['neighborhood_id'] ?? 'NÃO DEFINIDO') . ", bairro: " . ($anuncio['bairro'] ?? 'NÃO DEFINIDO'));
if (!empty($fotosGaleria)) {
    error_log("DEBUG VIEW: Primeira foto da galeria: " . $fotosGaleria[0]);
}
?>

<!-- Página de visualização de anúncio - Carregada via SPA -->
<link rel="stylesheet" href="<?= URLADM ?>assets/css/visualizar_anuncio_gphub.css?v=<?= time() . '_' . rand(1000, 9999); ?>">

<div class="visualizar-anuncio-container anuncio-view-container" data-anuncio-id="<?= htmlspecialchars($anuncio['id'] ?? '') ?>">
    <div class="viewport">
        <div class="larguraprofile anuncio-card">
            <!-- Primeira Linha: Informações Básicas e Descrição -->
            <div class="top-row">
                <!-- Sidebar Esquerda - Informações Básicas -->
                <div class="leftprofile profile-section">
                    <div class="modelo-perfil">
                        <img class="rounded-full" src="<?= !empty($fotoPrincipal) ? htmlspecialchars($fotoPrincipal) : 'https://via.placeholder.com/120x120' ?>" alt="Foto da Modelo">
                        <div class="modelo-info">
                            <div class="modelo-nome"><?= htmlspecialchars($nome) ?></div>
                            
                            <?php if (!empty($telefone)): ?>
                            <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/[^0-9]/', '', $telefone) ?>&text=Olá, vi seu perfil no site" class="whatsapp-btn contact-item" target="_blank">
                                <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($telefone) ?>
                            </a>
                <?php endif; ?>

                        </div>
                        </div>
                    
                    <!-- Stats - Movido para o mesmo nível de info-basicas -->
                    <div class="stats">
                        <div><span class="font-bold"><?= $visitas ?></span> Visualizações</div>
                        <div><span class="font-bold"><?= count($videos) ?></span> Vídeos</div>
                        <div><span class="font-bold"><?= count($fotosGaleria) ?></span> Imagens</div>
                        </div>

                    <!-- Preços - Movido para leftprofile -->
                    <div class="sidebar-section section-base">
                        <h3>Preços</h3>
                        <div class="preco-item">
                            <span class="preco-label">15 minutos:</span>
                            <span class="preco-value">R$ <?= htmlspecialchars($preco15min) ?></span>
                        </div>
                        <div class="preco-item">
                            <span class="preco-label">30 minutos:</span>
                            <span class="preco-value">R$ <?= htmlspecialchars($preco30min) ?></span>
                        </div>
                        <div class="preco-item">
                            <span class="preco-label">1 hora:</span>
                            <span class="preco-value">R$ <?= htmlspecialchars($preco1h) ?></span>
            </div>
            </div>
        </div>

                    <!-- Informações Básicas - Sempre visível -->
                    <div class="info-basicas section-base info-section">
                        <h3>Informações Básicas</h3>
                        <table class="info-table">
                            <tbody>
                                <tr>
                                    <th>
                                        <span class="info-label info-item">
                                            <i class="fas fa-user"></i> Nome:
                                        </span>
                                        <span class="info-value"><?= htmlspecialchars($nome) ?></span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label info-item">
                                            <i class="fas fa-birthday-cake"></i> Idade:
                                        </span>
                                        <span class="info-value"><?= htmlspecialchars($idade) ?> anos</span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label info-item">
                                            <i class="fas fa-ruler-vertical"></i> Altura:
                                        </span>
                                        <span class="info-value"><?= htmlspecialchars($altura) ?> m</span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label info-item">
                                            <i class="fas fa-weight"></i> Peso:
                                        </span>
                                        <span class="info-value"><?= htmlspecialchars($peso) ?> kg</span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label">Cor dos Olhos:</span>
                                        <span class="info-value"><?= htmlspecialchars($corOlhos) ?></span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label">Nacionalidade:</span>
                                        <span class="info-value"><?= htmlspecialchars($nacionalidade) ?></span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label">Etnia:</span>
                                        <span class="info-value"><?= htmlspecialchars($etnia) ?></span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label">Estado:</span>
                                        <span class="info-value"><?= htmlspecialchars($estado) ?></span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label">Cidade:</span>
                                        <span class="info-value"><?= htmlspecialchars($cidade) ?></span>
                                    </th>
                                </tr>
                                <tr>
                                    <th>
                                        <span class="info-label">Bairro:</span>
                                        <span class="info-value"><?= htmlspecialchars($bairro) ?></span>
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                        </div>

                        </div>

            <!-- Segunda Linha: Informações Adicionais -->
            <div class="bottom-row">
                <!-- Sidebar Direita - Informações Adicionais -->
                <div class="rightprofile profile-section">
                    <!-- Aparência -->
                    <?php if (!empty($aparencia)): ?>
                    <div class="sidebar-section section-base">
                        <h3>Aparência</h3>
                        <div class="info-tags">
                            <?php 
                            $aparenciaArray = explode(', ', $aparencia);
                            foreach ($aparenciaArray as $item): 
                            ?>
                            <span class="info-tag"><?= htmlspecialchars(trim($item)) ?></span>
                            <?php endforeach; ?>
                        </div>
            </div>
                    <?php endif; ?>

                    <!-- Idiomas -->
                    <?php if (!empty($idiomas)): ?>
                    <div class="sidebar-section section-base">
                        <h3>Idiomas</h3>
                        <div class="info-tags">
                            <?php 
                            $idiomasArray = explode(', ', $idiomas);
                            foreach ($idiomasArray as $item): 
                            ?>
                            <span class="info-tag"><?= htmlspecialchars(trim($item)) ?></span>
                            <?php endforeach; ?>
                        </div>
            </div>
                    <?php endif; ?>

                    <!-- Locais de Atendimento -->
                    <?php if (!empty($locaisAtendimento)): ?>
                    <div class="sidebar-section section-base">
                        <h3>Locais de Atendimento</h3>
                        <div class="info-tags">
                            <?php 
                            $locaisArray = explode(', ', $locaisAtendimento);
                            foreach ($locaisArray as $item): 
                            ?>
                            <span class="info-tag"><?= htmlspecialchars(trim($item)) ?></span>
                            <?php endforeach; ?>
            </div>
            </div>
                    <?php endif; ?>

                    <!-- Formas de Pagamento -->
                    <?php if (!empty($formasPagamento)): ?>
                    <div class="sidebar-section section-base">
                        <h3>Formas de Pagamento</h3>
                        <div class="info-tags">
                            <?php 
                            $formasArray = explode(', ', $formasPagamento);
                            foreach ($formasArray as $item): 
                            ?>
                            <span class="info-tag"><?= htmlspecialchars(trim($item)) ?></span>
                            <?php endforeach; ?>
            </div>
        </div>
                    <?php endif; ?>

                    <!-- Serviços -->
                    <?php if (!empty($servicos)): ?>
                    <div class="sidebar-section section-base">
                        <h3>Serviços</h3>
                        <div class="info-tags">
                            <?php 
                            $servicosArray = explode(', ', $servicos);
                            foreach ($servicosArray as $item): 
                            ?>
                            <span class="info-tag"><?= htmlspecialchars(trim($item)) ?></span>
                            <?php endforeach; ?>
                        </div>
            </div>
                    <?php endif; ?>

                    <!-- Descrição - Sempre visível -->
                    <div class="descricao-section">
                        <h3>Descrição sobre mim</h3>
                        <p><?= nl2br(htmlspecialchars($descricao)) ?></p>
            </div>
        </div>

                <!-- Espaço vazio para manter o grid -->
                <div></div>
        </div>

            <!-- Terceira Linha: Mídias ocupando toda a largura -->
            <div class="midias-full-width profile-section">

                <!-- Galeria de Fotos -->
                <?php if (!empty($fotosGaleria)): ?>
                <div class="sidebar-section section-base">
                    <h3>Galeria de Fotos</h3>
                    <div class="galeria-grid">
                        <?php foreach ($fotosGaleria as $index => $foto): ?>
                        <div class="galeria-item media-item" data-index="<?= $index ?>">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="Foto <?= $index + 1 ?>">
                            <div class="overlay">Foto <?= $index + 1 ?></div>
            </div>
                            <?php endforeach; ?>
                        </div>
        </div>
            <?php endif; ?>

                <!-- Vídeos -->
                <?php if (!empty($videos)): ?>
                <div class="sidebar-section section-base">
                    <h3>Vídeos</h3>
                    <div class="galeria-grid">
                        <?php foreach ($videos as $index => $video): ?>
                        <div class="galeria-item media-item">
                            <video controls style="width: 100%; height: 200px; object-fit: cover;">
                                            <source src="<?= htmlspecialchars($video) ?>" type="video/mp4">
                                            Seu navegador não suporta vídeos.
                                        </video>
                            <div class="overlay">Vídeo <?= $index + 1 ?></div>
        </div>
                    <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Áudios -->
                <?php if (!empty($audios)): ?>
                <div class="sidebar-section section-base">
                    <h3>Áudios</h3>
                    <div class="galeria-grid">
                        <?php foreach ($audios as $index => $audio): ?>
                        <div class="galeria-item media-item">
                            <audio controls style="width: 100%; height: 80px;">
                                            <source src="<?= htmlspecialchars($audio) ?>" type="audio/mpeg">
                                <source src="<?= htmlspecialchars($audio) ?>" type="audio/ogg">
                                <source src="<?= htmlspecialchars($audio) ?>" type="audio/wav">
                                            Seu navegador não suporta áudios.
                                        </audio>
                            <div class="overlay">Áudio <?= $index + 1 ?></div>
                                        </div>
                            <?php endforeach; ?>
            </div>
        </div>
                <?php endif; ?>
                            </div>
                            </div>
                                        </div>

            <!-- Seção de Administrador -->
            <?php 
            ?>
            

    <!-- Modal do Carrossel -->
    <div id="carouselModal" class="carousel-modal modal-feedback-beautiful">
        <div class="carousel-container modal-content-beautiful">
            <button class="carousel-close modal-close-beautiful" id="carouselClose">&times;</button>
            
            <button class="carousel-nav carousel-prev modal-nav-beautiful" id="carouselPrev">&#8249;</button>
            <button class="carousel-nav carousel-next modal-nav-beautiful" id="carouselNext">&#8250;</button>
            
            <div class="carousel-content modal-body-beautiful">
                <img id="carouselImage" class="carousel-image" src="" alt="">
                        </div>

            <div class="carousel-counter modal-footer-beautiful">
                <span id="carouselCounter">1 / 1</span>
                            </div>

            <div class="carousel-thumbnails modal-thumbnails-beautiful" id="carouselThumbnails">
                <!-- Thumbnails serão gerados dinamicamente -->
                        </div>
            </div>
            </div>
            </div>

            <!-- FOOTER -->
            <footer class="footer py-5">
                <div class="container">
                    <div class="row">
                        <!-- Coluna 1: Sobre -->
                        <div class="col-lg-4 mb-4 mb-lg-0">
                            <h3 class="footer-brand"><span class="brand-highlight">GP</span>HUB</h3>
                            <p class="footer-about">Plataforma premium para divulgação de anúncios elegantes e discretos. Conectando profissionais de alto padrão com clientes exclusivos.</p>
                        </div>

                        <!-- Coluna 2: Links rápidos -->
                        <div class="col-md-6 col-lg-2 mb-4 mb-md-0">
                            <h4 class="footer-title">Links</h4>
                            <div class="linha bg-primary mb-3" style="height: 2px; width: 50px;"></div>
                            <ul class="footer-links">
                                <li><a href="<?= URL ?>">Home</a></li>
                                <li><a href="<?= URL ?>#acompanhantes">Acompanhantes</a></li>
                                <li><a href="<?= URL ?>#contato">Contato</a></li>
                                <li><a href="<?= URLADM ?>login">Login</a></li>
                            </ul>
                        </div>

                        <!-- Coluna 3: Acompanhantes -->
                        <div class="col-md-6 col-lg-3 mb-4 mb-md-0">
                            <h4 class="footer-title">Acompanhantes</h4>
                            <div class="linha bg-primary mb-3" style="height: 2px; width: 50px;"></div>
                            <ul class="footer-links">
                                <li><a href="<?= URL ?>categorias/mulher">Mulheres</a></li>
                                <li><a href="<?= URL ?>categorias/homem">Homens</a></li>
                                <li><a href="<?= URL ?>categorias/trans">Trans</a></li>
                            </ul>
                        </div>

                        <!-- Coluna 4: Redes sociais -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <h5 class="footer-title mb-3">Rede Social</h5>
                            <div class="linha bg-primary mb-3" style="height: 2px; width: 50px;"></div>
                            <div class="social-links d-flex flex-wrap gap-3">
                                <a href="#" class="text-white twitter" title="Twitter (X)">
                                    <i class="fa-brands fa-x-twitter"></i>
                                </a>
                                <a href="#" class="text-white facebook" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="text-white instagram" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="text-white whatsapp" title="WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="#" class="text-white youtube" title="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                                <a href="#" class="text-white telegram" title="Telegram">
                                    <i class="fab fa-telegram"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Divisor e copyright -->
                    <hr class="footer-divider">
                    <div class="footer-bottom text-center">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> GPHub. Todos os direitos reservados.</p>
                    </div>
                </div>
            </footer>

<!-- JavaScript do carrossel movido para visualizar_anuncio.js -->
