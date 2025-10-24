<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="signup-container">
    <div class="container">
        <div class="signup-header">
            <h1 class="signup-title">Crie Sua Conta</h1>
            <p class="signup-subtitle">Escolha seu plano e crie sua conta para começar a atrair clientes de alto padrão</p>
            
            <div class="step-indicator">
                <div class="step active" id="step1">
                    <span>1</span>
                    <div class="step-label">Escolher Plano</div>
                </div>
                <div class="step-line"></div>
                <div class="step" id="step2">
                    <span>2</span>
                    <div class="step-label">Seus Dados</div>
                </div>
                <div class="step-line"></div>
                <div class="step" id="step3">
                    <span>3</span>
                    <div class="step-label">Finalizar</div>
                </div>
            </div>
            
            <div class="step-actions">
                <button id="btnContinue" class="btn btn-signup" disabled>Continuar</button>
                <a href="<?= URLADM ?>login" class="login-link">Já tem uma conta? Faça login</a>
            </div>
        </div>
        
        <div class="plan-cards-container">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="plan-card" data-plan="free">
                            <h3 class="plan-name">Plano Gratuito</h3>
                            <div class="plan-price">Grátis</div>
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Painel administrativo</li>
                                <li><i class="fas fa-check"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check"></i> 2 fotos na galeria</li>
                                <li><i class="fas fa-check"></i> 1 foto de capa</li>
                                <li class="disabled"><i class="fas fa-times"></i> Vídeos</li>
                                <li class="disabled"><i class="fas fa-times"></i> Áudios</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="plan-card" data-plan="basic">
                            <div class="popular-badge">Mais Popular</div>
                            <h3 class="plan-name">Plano Básico</h3>
                            <div class="plan-price">R$ 29,90/mês</div>
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Painel administrativo</li>
                                <li><i class="fas fa-check"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check"></i> 20 fotos na galeria</li>
                                <li><i class="fas fa-check"></i> 1 foto de capa</li>
                                <li class="disabled"><i class="fas fa-times"></i> Vídeos</li>
                                <li class="disabled"><i class="fas fa-times"></i> Áudios</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="plan-card" data-plan="premium">
                            <h3 class="plan-name">Plano Premium</h3>
                            <div class="plan-price">R$ 49,90/mês</div>
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Painel administrativo</li>
                                <li><i class="fas fa-check"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check"></i> 20 fotos na galeria</li>
                                <li><i class="fas fa-check"></i> 1 foto de capa</li>
                                <li><i class="fas fa-check"></i> 3 vídeos</li>
                                <li><i class="fas fa-check"></i> 3 áudios</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-container" style="display: none;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <h2 class="h3 mb-4 text-center">Seus Dados</h2>
                        <p class="text-center text-muted mb-4">Preencha suas informações para continuar</p>
                        
                        <form id="cadastroForm" class="needs-validation" novalidate>
                            <!-- Nome e Email -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label fw-semibold">
                                        <i class="fas fa-user me-2 text-primary"></i>Nome Completo *
                                    </label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           placeholder="Digite seu nome completo" required>
                                    <div class="invalid-feedback" id="nome-feedback">
                                        Por favor, digite seu nome completo.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="fas fa-envelope me-2 text-primary"></i>E-mail *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="seu@email.com" required>
                                    <div class="invalid-feedback" id="email-feedback">
                                        Por favor, digite um e-mail válido.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Telefone e CPF -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label for="telefone" class="form-label fw-semibold">
                                        <i class="fas fa-phone me-2 text-primary"></i>Telefone *
                                    </label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" 
                                           placeholder="(11) 99999-9999" maxlength="15" required>
                                    <div class="invalid-feedback" id="telefone-feedback">
                                        Por favor, digite um telefone válido.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="cpf" class="form-label fw-semibold">
                                        <i class="fas fa-id-card me-2 text-primary"></i>CPF *
                                    </label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" 
                                           placeholder="000.000.000-00" maxlength="14" required>
                                    <div class="invalid-feedback" id="cpf-feedback">
                                        Por favor, digite um CPF válido.
                                </div>
                                </div>
                            </div>
                            
                            <!-- Senhas -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label for="senha" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-primary"></i>Senha *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="senha" name="senha" 
                                               placeholder="Mínimo 6 caracteres" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleSenha">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback" id="senha-feedback">
                                        A senha deve ter no mínimo 6 caracteres.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirmar_senha" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-primary"></i>Confirmar Senha *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                               placeholder="Confirme sua senha" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmarSenha">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback" id="confirmar_senha-feedback">
                                        As senhas não coincidem.
                                </div>
                                </div>
                            </div>
                            
                            <!-- Botão de Cadastro -->
                            <div class="text-center mt-4">
                                <button type="button" id="btnCreateAccount" class="btn btn-signup px-4" disabled>
                                    <i class="fas fa-user-plus me-2"></i>Criar Conta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Definir URLADM para uso no JavaScript
window.URLADM = '<?= URLADM ?>';
</script>
