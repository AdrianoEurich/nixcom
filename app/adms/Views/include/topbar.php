<?php
// Opcional: Garante que o arquivo não pode ser acessado diretamente
// É uma boa prática de segurança para includes
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
// Verifica se o usuário está logado e tem nome definido
$nomeUsuario = $_SESSION['usuario']['nome'] ?? 'Administrador';
$fotoUsuario = $_SESSION['usuario']['foto'] ?? URLADM . 'assets/images/users/usuario.png';
?>

<nav class="topbar">
    <div class="container-fluid">
        <button id="sidebarToggle" class="btn btn-link d-lg-none">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-right">
            <div class="notifications-wrapper">
                <div class="notification-dropdown me-2">
                    <button class="notification-btn position-relative">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger sm-badge">3</span>
                    </button>
                    <div class="notification-dropdown-content">
                        <div class="notification-header">
                            <h6>Notificações</h6>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon bg-primary-light">
                                <i class="fas fa-file-alt text-primary"></i>
                            </div>
                            <div class="notification-content">
                                <small>20 Dez, 2023</small>
                                <p>Novo relatório mensal disponível</p>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon bg-success-light">
                                <i class="fas fa-user-plus text-success"></i>
                            </div>
                            <div class="notification-content">
                                <small>19 Dez, 2023</small>
                                <p>5 novos usuários cadastrados</p>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="#">Ver todas</a>
                        </div>
                    </div>
                </div>

                <div class="notification-dropdown me-2">
                    <button class="notification-btn position-relative">
                        <i class="fas fa-envelope"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger sm-badge">7</span>
                    </button>
                    <div class="notification-dropdown-content">
                        <div class="notification-header">
                            <h6>Mensagens</h6>
                        </div>
                        <div class="notification-item">
                            <div class="notification-avatar">
                                <img src="https://randomuser.me/api/portraits/women/34.jpg" alt="Maria Silva">
                            </div>
                            <div class="notification-content">
                                <small>Maria Silva · 2d</small>
                                <p>Olá, gostaria de saber mais sobre...</p>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-avatar">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="João Santos">
                            </div>
                            <div class="notification-content">
                                <small>João Santos · 3d</small>
                                <p>Preciso de ajuda com meu anúncio...</p>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="#">Ver todas</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn dropdown-toggle user-profile" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <img src="<?= URLADM ?>assets/images/users/<?= $_SESSION['usuario']['foto'] ?? 'usuario.png' ?>"
                            alt="<?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Admin') ?>"
                            class="profile-img"
                            onerror="this.src='<?= URLADM ?>assets/images/users/usuario.png'">
                    </div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Admin') ?></div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <h6 class="dropdown-header">Minha Conta</h6>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= URLADM ?>perfil/perfil" data-spa>
                            <i class="fas fa-user-circle me-2"></i> Configurações
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= URLADM ?>perfil/perfil" data-spa>
                            <i class="fa-solid fa-trash-can"></i></i> Excluir Conta
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= URLADM ?>login/logout">
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>