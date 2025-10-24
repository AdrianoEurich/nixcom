<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header text-center mb-3">
            <a href="<?= URLADM ?>" class="login-logo">
                <span class="brand-highlight">GP</span>HUB
            </a>
            <p class="login-subtitle">Crie sua conta</p>
            
            <!-- Plano Selecionado -->
            <?php if (isset($this->data['selected_plan']) && $this->data['selected_plan'] !== 'free'): ?>
            <div class="selected-plan-badge mb-3">
                <div class="badge bg-warning text-dark">
                    <i class="fas fa-crown"></i>
                    Plano <?= strtoupper($this->data['selected_plan']) ?> Selecionado
                </div>
                <small class="text-muted d-block mt-1">
                    <?php if ($this->data['selected_plan'] === 'basic'): ?>
                        R$ 200 por 6 meses - Até 20 fotos
                    <?php elseif ($this->data['selected_plan'] === 'premium'): ?>
                        R$ 300 por 6 meses - Fotos + Vídeos + Áudios
                    <?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
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

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aceitarTermos" required>
                    <label class="form-check-label small" for="aceitarTermos">
                        Li e aceito os <a href="#" id="verTermos" class="text-primary">Termos e Condições</a> de uso da plataforma
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aceitarResponsabilidade" required>
                    <label class="form-check-label small" for="aceitarResponsabilidade">
                        Declaro que sou responsável por todas as fotos e informações que serão publicadas em meu anúncio
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-sm w-100 mb-2" id="submitBtn">
                Cadastrar
            </button>

            <div class="text-center">
                <p class="small">
                    Já tem uma conta? <a href="<?= URLADM ?>login" class="register-link">Faça login</a>
                </p>
                <p class="small mt-2">
                    <a href="<?= URLADM ?>planos" class="text-warning">
                        <i class="fas fa-crown"></i> Escolher Plano Premium
                    </a>
                </p>
            </div>
        </form>
    </div>
    <!-- ATENÇÃO: OS MODAIS FORAM REMOVIDOS DESTE ARQUIVO. ELES ESTÃO AGORA APENAS NO footer.php PARA ESTE LAYOUT. -->
</div>
