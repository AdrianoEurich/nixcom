<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<di class="login-container">
    <div class="login-card">
        <div class="login-header text-center mb-3">
            <a href="<?= URLADM ?>" class="login-logo">
                <span class="brand-highlight">Nix</span>com
            </a>
            <p class="login-subtitle">Crie sua conta</p>
        </div>

        <form id="cadastroForm" class="login-form" method="post" action="<?= URLADM ?>cadastro/salvar">
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" placeholder="Seu nome" name="cadastro[nome]"
                        value="<?= htmlspecialchars($this->data['form_data']['nome'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" placeholder="Seu e-mail" name="cadastro[email]"
                        value="<?= htmlspecialchars($this->data['form_data']['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" placeholder="Sua senha" name="cadastro[senha]" required>
                    <button class="btn btn-outline-secondary toggle-password" name="cadastro" type="button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" placeholder="Confirme a senha" name="cadastro[confirmar_senha]" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-sm w-100 mb-2" id="submitBtn">
                Cadastrar
            </button>

            <div class="text-center">
                <p class="small">
                    Já tem uma conta? <a href="<?= URLADM ?>login" class="register-link">Faça login</a>
                </p>
            </div>
        </form>
    </div>

    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i id="feedbackIcon" class="mb-3"></i>
                    <p id="feedbackMessage"></p>
                </div>
            </div>
        </div>
    </div>