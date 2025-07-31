<?php
// Opcional: Garante que o arquivo não pode ser acessado diretamente
// É uma boa prática de segurança para includes
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Certifique-se de que URLADM e URL estejam definidas
// Geralmente definidas em um arquivo de configuração como ConfigAdm.php
// Estas definições são apenas um fallback, o ideal é que venham de um Config.php global.
if (!defined('URLADM')) {
    define('URLADM', 'http://localhost/nixcom/adms/'); 
}
if (!defined('URL')) {
    define('URL', 'http://localhost/nixcom/'); 
}

// Verifica se o usuário está logado e tem nome definido
// CORREÇÃO AQUI: Usando a chave 'usuario' da sessão, que é atualizada pelo controlador Perfil.php
$nomeUsuario = $_SESSION['usuario']['nome'] ?? 'Usuário'; 
$fotoUsuario = $_SESSION['usuario']['foto'] ?? 'usuario.png'; // Apenas o nome do arquivo da foto

// Constrói a URL completa da foto
$urlFotoUsuario = URLADM . 'assets/images/users/' . $fotoUsuario;

// Verifica se o arquivo físico da foto existe, caso contrário, usa a foto padrão
$caminhoFisicoFoto = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/assets/images/users/' . $fotoUsuario;
if (!file_exists($caminhoFisicoFoto) || empty($fotoUsuario) || $fotoUsuario === 'usuario.png') {
    $urlFotoUsuario = URLADM . 'assets/images/users/usuario.png';
}

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
                        <!-- Usando $urlFotoUsuario que já foi definida acima e inclui o caminho completo -->
                        <img src="<?= htmlspecialchars($urlFotoUsuario) ?>"
                             id="topbar-user-photo" <!-- Adicionado ID para atualização da foto também -->
                             alt="<?= htmlspecialchars($nomeUsuario) ?>"
                             class="profile-img"
                             onerror="this.src='<?= URLADM ?>assets/images/users/usuario.png'">
                    </div>
                    <!-- Adicionado ID para atualização via JavaScript -->
                    <div class="user-name" id="topbar-user-name"><?= htmlspecialchars($nomeUsuario) ?></div>
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
                        <!-- ALTERAÇÃO CRÍTICA AQUI: Removido data-spa e adicionado ID para JS -->
                        <!-- Este link acionará um modal de confirmação via JS para o soft delete da conta do usuário -->
                        <a class="dropdown-item" href="#" id="deleteAccountLink">
                            <i class="fa-solid fa-trash-can me-2"></i> Excluir Conta
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <!-- O link de logout deve apontar diretamente para o controlador de logout -->
                        <a class="dropdown-item" href="<?= URLADM ?>logout">
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
