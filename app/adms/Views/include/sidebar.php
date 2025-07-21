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
        <li id="navCriarAnuncio"> <!-- Já tinha ID, mantido -->
            <a href="<?= URLADM ?>anuncio/index" data-spa="true"><i class="fa-solid fa-address-card"></i> <span>Criar Anúncio</span></a>
        </li>
        <li id="navEditarAnuncio"> <!-- Já tinha ID, mantido -->
            <a href="<?= URLADM ?>anuncio/editarAnuncio" data-spa="true"><i class="fas fa-user-edit"></i> <span>Editar Anúncio</span></a>
        </li>
        <li id="navVisualizarAnuncio"> <!-- ADICIONADO ID -->
            <!-- CORREÇÃO AQUI: O href agora aponta para o método correto do controlador -->
            <a href="<?= URLADM ?>anuncio/visualizarAnuncio" data-spa="true"><i class="fas fa-eye"></i> <span>Visualizar Anúncio</span></a>
        </li>
        <li id="navPausarAnuncio"> <!-- ADICIONADO ID -->
            <!-- ALTERAÇÃO AQUI: href="#" para controle total via JS -->
            <a href="#" data-spa="true"><i class="fa-solid fa-pause"></i> <span>Pausar Anúncio</span></a>
        </li>
        <li id="navExcluirAnuncio"> <!-- ADICIONADO ID -->
            <a href="<?= URLADM ?>anuncio/excluir" data-spa="true"><i class="fa-solid fa-trash"></i> <span>Excluir Anúncio</span></a>
        </li>
        <li>
            <a href="<?= URLADM ?>financeiro" data-spa="true"><i class="fas fa-dollar-sign"></i> <span>Financeiro</span></a>
        </li>
        <li class="logout-item">
            <a href="<?= URLADM ?>login/logout"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <span>v2.1.0</span>
    </div>
</nav>
