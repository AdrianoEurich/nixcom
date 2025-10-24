<?php
// app/adms/Views/users/list_users.php

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gerenciar Usuários</h1>
    </div>

    <?php
    // Exibir mensagens de sucesso ou erro, se houver
    if (isset($this->data['msg']) && !empty($this->data['msg'])) {
        $msgType = $this->data['msg']['type'];
        $msgText = $this->data['msg']['text'];
        echo "<div class='alert alert-{$msgType} alert-dismissible fade show' role='alert'>
                {$msgText}
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
              </div>";
    }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Usuários</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="userSearchInput" class="form-control bg-light border-0 small" placeholder="Pesquisar por nome ou e-mail..."
                               aria-label="Search" aria-describedby="button-search" value="<?= htmlspecialchars($this->data['pagination_data']['search_term'] ?? ''); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="button-search-users">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <!-- Botão para adicionar novo usuário, se aplicável -->
                    <!-- <a href="#" class="btn btn-success btn-icon-split">
                        <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                        <span class="text">Novo Usuário</span>
                    </a> -->
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Nível de Acesso</th>
                            <th>Último Acesso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php
                        if (!empty($this->data['listUsers'])) {
                            foreach ($this->data['listUsers'] as $user) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']); ?></td>
                                    <td><?= htmlspecialchars($user['nome']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td><?= htmlspecialchars($user['nivel_acesso']); ?></td>
                                    <td><?= htmlspecialchars($user['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : 'N/A'); ?></td>
                                    <td>
                                        <?php if ($user['status'] !== 'deleted'): ?>
                                            <button class="btn btn-danger btn-sm soft-delete-user-btn" data-user-id="<?= $user['id']; ?>" title="Desativar Conta">
                                                <i class="fas fa-trash"></i> Soft Delete
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm activate-user-btn" data-user-id="<?= $user['id']; ?>" title="Ativar Conta">
                                                <i class="fas fa-check"></i> Ativar
                                            </button>
                                        <?php endif; ?>
                                        <!-- Botão de editar, se houver uma rota de edição de usuário -->
                                        <!-- <a href="<?= URLADM; ?>admin-users/edit/<?= $user['id']; ?>" class="btn btn-info btn-sm" title="Editar Usuário">
                                            <i class="fas fa-edit"></i>
                                        </a> -->
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Nenhum usuário encontrado.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center" id="usersPagination">
                    <?php
                    $currentPage = $this->data['pagination_data']['current_page'];
                    $totalPages = $this->data['pagination_data']['total_pages'];
                    $searchTerm = htmlspecialchars($this->data['pagination_data']['search_term']);

                    // Botão "Anterior"
                    if ($currentPage > 1) {
                        echo "<li class='page-item'><a class='page-link' href='#' data-page='" . ($currentPage - 1) . "'>Anterior</a></li>";
                    } else {
                        echo "<li class='page-item disabled'><span class='page-link'>Anterior</span></li>";
                    }

                    // Links de páginas
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);

                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i == $currentPage) ? 'active' : '';
                        echo "<li class='page-item {$activeClass}'><a class='page-link' href='#' data-page='{$i}'>{$i}</a></li>";
                    }

                    // Botão "Próximo"
                    if ($currentPage < $totalPages) {
                        echo "<li class='page-item'><a class='page-link' href='#' data-page='" . ($currentPage + 1) . "'>Próximo</a></li>";
                    } else {
                        echo "<li class='page-item disabled'><span class='page-link'>Próximo</span></li>";
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
    // URL base para as requisições AJAX
    // Verificar se baseUrl já foi declarada para evitar erro de redeclaração
    if (typeof baseUrl === 'undefined') {
        const baseUrl = '<?= URLADM; ?>admin-users'; 
    }
</script>
<!-- Incluir o arquivo JavaScript específico para o gerenciamento de usuários -->
<script src="<?= URLADM; ?>assets/js/admin_users.js"></script>

