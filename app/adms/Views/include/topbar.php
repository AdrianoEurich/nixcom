<?php
// Opcional: Garante que o arquivo não pode ser acessado diretamente
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

$nomeUsuario = $_SESSION['usuario']['nome'] ?? $_SESSION['user_name'] ?? 'Usuário'; 
$fotoUsuario = $_SESSION['usuario']['foto'] ?? 'usuario.png';

$urlFotoUsuario = URLADM . 'assets/images/users/' . $fotoUsuario;

$caminhoFisicoFoto = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/assets/images/users/' . $fotoUsuario;
if (!file_exists($caminhoFisicoFoto) || empty($fotoUsuario) || $fotoUsuario === 'usuario.png') {
    $urlFotoUsuario = URLADM . 'assets/images/users/usuario.png';
}
?>

<nav class="topbar">
    <div class="container-fluid">
        <div class="topbar-left">
            <button id="sidebarToggle" class="btn btn-link d-lg-none">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="topbar-right">
            <div class="notifications-wrapper">
                <!-- Sistema de Notificações (Sino) -->
                <div class="notification-dropdown me-2">
                    <button class="notification-btn position-relative" id="notificacoesBtn" data-bs-toggle="modal" data-bs-target="#notificacoesModal">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger sm-badge" id="notificacoesCount" style="display: none;">0</span>
                    </button>
                </div>
                
                <!-- Sistema de Mensagens (Envelope) -->
                <div class="notification-dropdown me-2">
                    <button class="notification-btn position-relative" id="mensagensBtn" data-bs-toggle="modal" data-bs-target="#mensagensModal">
                        <i class="fas fa-envelope"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger sm-badge" id="mensagensCount" style="display: none;">0</span>
                    </button>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn dropdown-toggle user-profile" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <img src="<?= htmlspecialchars($urlFotoUsuario) ?>"
                            id="topbar-user-photo"
                            alt="<?= htmlspecialchars($nomeUsuario) ?>"
                            class="profile-img"
                            onerror="this.src='<?= URLADM ?>assets/images/users/usuario.png'">
                    </div>
                    <div class="user-name" id="topbar-user-name"><?= htmlspecialchars($nomeUsuario) ?></div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <h6 class="dropdown-header">Minha Conta</h6>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= URLADM ?>perfil/index" data-spa>
                            <i class="fas fa-user-circle me-2"></i> Configurações
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" id="deleteAccountLink" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fa-solid fa-trash-can me-2"></i> Excluir Conta
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= URLADM ?>logout">
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<!-- O modal de exclusão agora é o modal de confirmação global do main.php -->

