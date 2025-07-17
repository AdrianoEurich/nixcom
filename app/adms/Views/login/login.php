<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header text-center mb-3">
            <a href="<?= URLADM ?>" class="login-logo"> <span class="brand-highlight">Nix</span>com
            </a>
            <p class="login-subtitle">Faça login para acessar sua conta</p>
        </div>

        <form id="loginForm" class="login-form" method="POST" action="<?= URLADM ?>login/autenticar">
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" placeholder="Seu e-mail" name="login[email]"
                        value="<?= htmlspecialchars($this->data['form_email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="senha" placeholder="Sua senha" name="login[senha]" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="form-check small">
                    <input class="form-check-input" type="checkbox" id="remember" />
                    <label class="form-check-label" for="remember">Lembrar-me</label>
                </div>
                <a href="#" class="forgot-password">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                <span class="button-text">Entrar</span>
            </button>

            <div class="text-center">
                <p class="login-divider">ou continue com</p>
                <div class="social-login d-flex justify-content-center gap-2 mb-2">
                    <a href="#" class="btn btn-outline-primary btn-sm social-btn"><i class="fab fa-google"></i></a>
                    <a href="#" class="btn btn-outline-primary btn-sm social-btn"><i class="fab fa-facebook-f"></i></a>
                </div>
                <p class="small">
                    Não tem uma conta?<a href="<?= URLADM; ?>cadastro" class="register-link"> Cadastre-se</a>
                </p>
            </div>
        </form>
    </div>
    <!-- ATENÇÃO: OS MODAIS FORAM REMOVIDOS DESTE ARQUIVO. ELES ESTÃO AGORA APENAS NO footer.php PARA ESTE LAYOUT. -->
</div>
