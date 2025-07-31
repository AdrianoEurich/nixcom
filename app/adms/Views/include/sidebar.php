<?php
// Opcional: Garante que o arquivo não pode ser acessado diretamente
// É uma boa prática de segurança para includes
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Certifique-se de que URLADM e URL estejam definidas
// Geralmente definidas em um arquivo de configuração como ConfigAdm.php
if (!defined('URLADM')) {
    define('URLADM', 'http://localhost/nixcom/adms/'); 
}
if (!defined('URL')) {
    define('URL', 'http://localhost/nixcom/'); 
}
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h4 href="<?= URL ?>home">Nixcom Admin</h4>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="<?= URLADM ?>dashboard" data-spa="true"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <!-- Adicionando classe 'user-only-link' para links que são EXCLUSIVOS de usuários normais -->
        <li id="navCriarAnuncio" class="user-only-link">
            <a href="<?= URLADM ?>anuncio/index" data-spa="true"><i class="fa-solid fa-address-card"></i> <span>Criar Anúncio</span></a>
        </li>
        <li id="navEditarAnuncio" class="user-only-link">
            <a href="<?= URLADM ?>anuncio/editarAnuncio" data-spa="true"><i class="fas fa-user-edit"></i> <span>Editar Anúncio</span></a>
        </li>
        <li id="navVisualizarAnuncio" class="user-only-link">
            <a href="<?= URLADM ?>anuncio/visualizarAnuncio" data-spa="true"><i class="fas fa-eye"></i> <span>Visualizar Anúncio</span></a>
        </li>
        <li id="navPausarAnuncio" class="user-only-link">
            <a href="#" data-spa="true"><i class="fa-solid fa-pause"></i> <span>Pausar Anúncio</span></a>
        </li>
        <li id="navExcluirAnuncio" class="user-only-link">
            <a href="<?= URLADM ?>anuncio/excluir" data-spa="true"><i class="fa-solid fa-trash"></i> <span>Excluir Anúncio</span></a>
        </li>
        <li>
            <a href="<?= URLADM ?>financeiro" data-spa="true"><i class="fas fa-dollar-sign"></i> <span>Financeiro</span></a>
        </li>

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

        <li class="logout-item">
            <!-- CORREÇÃO CRÍTICA AQUI: O link deve apontar para o controlador Logout diretamente -->
            <a href="<?= URLADM ?>logout"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <span>v2.1.0</span>
    </div>
</nav>
