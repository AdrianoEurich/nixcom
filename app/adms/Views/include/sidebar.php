<?php
// Opcional: Garante que o arquivo não pode ser acessado diretamente
// É uma boa prática de segurança para includes
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h4 href="<?= URL ?>home">Nixcom Admin</h4>
    </div>

    <ul class="sidebar-menu">
        <li class="active">
            <a href="<?= URLADM ?>dashboard" data-spa="true"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <li class="sidebar-item">
            <a href="" data-spa="true"><i class="fa-solid fa-address-card"></i> <span>Criar Anúncio</span></a>
        </li>
        <li>
            <a href="<?= URLADM ?>anuncio/editar" data-spa="true"><i class="fas fa-user-edit"></i> <span>Editar Anúncio</span></a>
        </li>
        <li>
            <a href="<?= URLADM ?>anuncio/visualizar" data-spa="true"><i class="fas fa-eye"></i> <span>Visualizar Anúncio</span></a>
        </li>
        <li>
            <a href="<?= URLADM ?>anuncio/pausar" data-spa="true"><i class="fa-solid fa-pause"></i> <span>Pausar Anúncio</span></a>
        </li>
        <li>
            <a href="<?= URLADM ?>financeiro" data-spa="true"><i class="fa-solid fa-trash"></i> <span>Excluir Anúncio</span></a>
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