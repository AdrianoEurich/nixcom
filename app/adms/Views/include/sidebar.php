<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
if (!defined('URLADM')) {
    define('URLADM', 'http://localhost/nixcom/adms/');
}
if (!defined('URL')) {
    define('URL', 'http://localhost/nixcom/');
}

// Recupera o ID e status do anúncio da sessão, se existir
$anuncio_id = $_SESSION['anuncio_id'] ?? null;
$anuncio_status = $_SESSION['anuncio_status'] ?? null;
$has_anuncio = $_SESSION['has_anuncio'] ?? false;

// Debug para verificar os valores da sessão
error_log("DEBUG SIDEBAR: anuncio_id=" . ($anuncio_id ?? 'null') . ", anuncio_status=" . ($anuncio_status ?? 'null') . ", has_anuncio=" . ($has_anuncio ? 'true' : 'false'));
error_log("DEBUG SIDEBAR: Sessão completa - " . json_encode([
    'user_id' => $_SESSION['user_id'] ?? 'null',
    'has_anuncio' => $_SESSION['has_anuncio'] ?? 'null',
    'anuncio_status' => $_SESSION['anuncio_status'] ?? 'null',
    'anuncio_id' => $_SESSION['anuncio_id'] ?? 'null'
]));
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h4 href="<?= URL ?>home">Dashboard</h4>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="<?= URLADM ?>dashboard" data-spa="true"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <li id="navCriarAnuncio" class="user-only-link">
            <a href="<?= URLADM ?>anuncio/index" data-spa="true"><i class="fa-solid fa-address-card"></i> <span>Criar Anúncio</span></a>
        </li>
        <?php if ($has_anuncio && $anuncio_id): ?>
            <li id="navEditarAnuncio" class="user-only-link">
                <a href="<?= URLADM ?>anuncio/editarAnuncio?id=<?= $anuncio_id ?>" data-spa="true"><i class="fas fa-user-edit"></i> <span>Editar Anúncio</span></a>
            </li>
            <li id="navVisualizarAnuncio" class="user-only-link">
                <a href="<?= URLADM ?>anuncio/visualizarAnuncio?id=<?= $anuncio_id ?>" data-spa="true"><i class="fas fa-eye"></i> <span>Visualizar Anúncio</span></a>
            </li>
            <?php
            // Só mostra o PAUSAR/ATIVAR se o anúncio está ativo, pausado ou inativo (não mostra quando está pendente)
            if ($anuncio_status === 'active' || $anuncio_status === 'pausado' || $anuncio_status === 'inactive'):
            ?>
                <li id="navPausarAnuncio" class="user-only-link">
                    <a href="#" data-spa="false" onclick="handlePausarAnuncioClick(event)" class="nav-link highlight" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4) !important; font-weight: 600 !important; border: 1px solid rgba(234, 88, 12, 0.3) !important;">
                        <i class="fa-solid <?= ($anuncio_status === 'active') ? 'fa-pause' : 'fa-play' ?>"></i> 
                        <span><?= ($anuncio_status === 'active') ? 'Pausar Anúncio' : 'Ativar Anúncio' ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        // Apenas para administradores (nível 3 ou superior)
        if (isset($_SESSION['user_level_numeric']) && $_SESSION['user_level_numeric'] >= 3) {
            ?>
            <li id="navAdminUsers">
                <a href="<?= URLADM ?>admin-users" data-spa="true"><i class="fas fa-users"></i> <span>Gerenciar Usuários</span></a>
            </li>
            <?php
        }
        ?>

        <li>
            <a href="<?= URLADM ?>financeiro" data-spa="false"><i class="fas fa-dollar-sign"></i> <span>Financeiro</span></a>
        </li>
        <li class="logout-item">
            <a href="<?= URLADM ?>logout"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <span>v3.1.0</span>
    </div>
</nav>