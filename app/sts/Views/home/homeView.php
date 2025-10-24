<?php
// =============================================
// CONFIGURAÇÃO DE IMAGENS - HOME
// =============================================
// Para alterar as imagens, modifique os caminhos abaixo:

// Imagem principal do hero (seção inicial)
$hero_image = URL . "app/sts/assets/images/home/hero-mulheres-celulares.jpg";

// Imagem da seção de destaques
$highlights_image = URL . "app/sts/assets/images/home/seguranca-privacidade.jpg";

// Imagens das categorias (já configuradas no sistema)
$category_images = [
    'mulher' => URL . "app/public/uploads/categorias/mulher/mulher_principal.jpg",
    'homem' => URL . "app/public/uploads/categorias/homem/homem_principal.jpg", 
    'trans' => URL . "app/public/uploads/categorias/trans/trans_principal.jpg"
];
?>

<section id="home" class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">Plataforma <span class="text-highlight">Premium</span> para Anúncios de Luxo</h1>
                <p class="hero-subtitle">Conectamos clientes exclusivos com profissionais de alto padrão. Sua presença digital elegante e discreta.</p>
                <div class="hero-buttons">
                    <?php
use Sts\Models\Helper\LoginHelperImproved;
$isLoggedIn = LoginHelperImproved::isLoggedIn();
                    $buttonUrl = LoginHelperImproved::getRedirectUrl(URLADM . "cadastro");
                    $buttonText = LoginHelperImproved::getButtonText("Criar Anúncio");
                    $buttonClass = LoginHelperImproved::getButtonClass("btn btn-lg me-3");
                    ?>
                    <a href="<?= $buttonUrl ?>" class="<?= $buttonClass ?>"><?= $buttonText ?></a>
                    <a href="#contato" class="btn btn-outline-light btn-lg">Saiba Mais</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-image-container">
                    <img src="<?= $hero_image ?>" alt="Luxo e elegância" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="currentColor"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" fill="currentColor"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="currentColor"></path>
        </svg>
    </div>
</section>

<section id="acompanhantes" class="categories-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">Escolha sua <span class="text-highlight">Preferência</span></h2>
            <p class="section-subtitle">Encontre exatamente o que você está procurando</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="category-card" data-category="mulher">
                    <div class="category-image">
                        <img src="<?= URL ?>app/public/uploads/categorias/mulher/mulher_principal.jpg" alt="Mulheres">
                        <div class="category-overlay">
                            <i class="fas fa-venus category-icon"></i>
                        </div>
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Mulheres</h3>
                        <p class="category-description">Garotas de programa elegantes e sofisticadas para momentos especiais.</p>
                        <a href="<?= URL ?>categorias/mulher" class="category-btn">
                            <i class="fas fa-arrow-right me-2"></i>Ver Mulheres
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="category-card" data-category="homem">
                    <div class="category-image">
                        <img src="<?= URL ?>app/public/uploads/categorias/homem/homem_principal.jpg" alt="Homens">
                        <div class="category-overlay">
                            <i class="fas fa-mars category-icon"></i>
                        </div>
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Homens</h3>
                        <p class="category-description">Garotos de programa charmosos e discretos para sua companhia.</p>
                        <a href="<?= URL ?>categorias/homem" class="category-btn">
                            <i class="fas fa-arrow-right me-2"></i>Ver Homens
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="category-card" data-category="trans">
                    <div class="category-image">
                        <img src="<?= URL ?>app/public/uploads/categorias/trans/trans_principal.jpg" alt="Trans">
                        <div class="category-overlay">
                            <i class="fas fa-transgender category-icon"></i>
                        </div>
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Trans</h3>
                        <p class="category-description">Garotas trans lindas e autênticas para experiências únicas e especiais.</p>
                        <a href="<?= URL ?>categorias/trans" class="category-btn">
                            <i class="fas fa-arrow-right me-2"></i>Ver Trans
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Seção Destaques -->
<section class="highlights-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="<?= $highlights_image ?>" alt="Plataforma Premium" class="img-fluid rounded shadow-lg">
            </div>
            <div class="col-lg-6">
                <h2 class="section-title mb-4">Por que escolher nossa <span class="text-highlight">Plataforma</span>?</h2>
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="highlight-content">
                        <h4>Cliente Exclusivo</h4>
                        <p>Conectamos você com clientes de alto padrão que valorizam qualidade e discrição.</p>
                    </div>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="highlight-content">
                        <h4>Privacidade Garantida</h4>
                        <p>Seus dados são protegidos com tecnologia de ponta e políticas rigorosas de confidencialidade.</p>
                    </div>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="highlight-content">
                        <h4>Suporte 24/7</h4>
                        <p>Equipe dedicada disponível para atender suas necessidades a qualquer momento.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Seção Anuncie Grátis -->
<section id="anuncie" class="cta-section py-5">
    <div class="container">
        <div class="cta-box text-center p-5 rounded">
            <h2 class="cta-title mb-4">Anuncie Conosco <span class="text-highlight">Grátis</span></h2>
                <?php if ($isLoggedIn): ?>
                    <p class="cta-text mb-5">Bem-vindo(a), <?= LoginHelperImproved::getUserName() ?>! Acesse seu dashboard para gerenciar seus anúncios.</p>
                    <button class="btn btn-success btn-lg btn-animate" onclick="window.location.href='<?= URLADM ?>dashboard'">Meu Dashboard</button>
                <?php else: ?>
                    <p class="cta-text mb-5">Cadastre-se agora e comece a divulgar seu negócio sem custos iniciais!</p>
                    <button class="btn btn-primary btn-lg btn-animate" onclick="window.location.href='<?= URLADM ?>cadastro'">Cadastre-se Gratuitamente</button>
                <?php endif; ?>
        </div>
    </div>
</section>


<section id="contato" class="contact-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">Entre em <span class="text-highlight">Contato</span></h2>
            <p class="section-subtitle">Estamos prontos para ajudar no que precisar</p>
        </div>
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <form class="contact-form" id="formContato" method="POST" action="<?= URL ?>contato.php">
                    <div class="mb-3">
                        <input type="text" id="nome" class="form-control" placeholder="Seu nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" id="email" class="form-control" placeholder="Seu e-mail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" id="telefone" class="form-control" placeholder="Seu telefone" name="telefone" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" id="assunto" class="form-control" placeholder="Assunto" name="assunto" required>
                    </div>
                    <div class="mb-3">
                        <textarea id="mensagem" class="form-control" rows="1" placeholder="Sua mensagem" name="mensagem" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-enviar">
                        Enviar Mensagem
                    </button>
                </form>
            </div>
            <div class="col-lg-6">
                <div class="contact-info">
                    <div class="info-item">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h4>Telefone/WhatsApp</h4>
                            <p>(41) 99181-9145</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>E-mail</h4>
                            <p>adriano.eurich@gmail.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Horário de Atendimento</h4>
                            <p>Segunda a Sexta: 9h às 18h</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel"></h5>
            </div>
            <div class="modal-body text-center">
                <i id="feedbackIcon" class="mb-3"></i>
                <p id="feedbackMessage" class="lead"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" id="feedbackModalOkBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
// Processar formulário de contato
document.addEventListener('DOMContentLoaded', function() {
    const formContato = document.getElementById('formContato');
    const btnEnviar = formContato.querySelector('.btn-enviar');
    
    if (formContato) {
        formContato.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Mostrar loading no botão
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
            btnEnviar.disabled = true;
            
            // Coletar dados do formulário
            const formData = new FormData(formContato);
            
            // Enviar via AJAX
            
            fetch(formContato.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'sucesso') {
                    // Restaurar botão primeiro
                    btnEnviar.innerHTML = 'Enviar Mensagem';
                    btnEnviar.disabled = false;
                    
                    // Mostrar sucesso
                    console.log('🎯 DEBUG: Chamando showFeedbackModal para sucesso');
                    showFeedbackModal('success', 'Mensagem enviada com sucesso!');
                    formContato.reset();
                    
                    // Modal será fechado pelo usuário clicando no botão OK
                    console.log('✅ DEBUG: Modal será fechado pelo usuário');
                } else {
                    // Restaurar botão
                    btnEnviar.innerHTML = 'Enviar Mensagem';
                    btnEnviar.disabled = false;
                    
                    // Mostrar erro
                    showFeedbackModal('error', data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                
                // Restaurar botão
                btnEnviar.innerHTML = 'Enviar Mensagem';
                btnEnviar.disabled = false;
                
                showFeedbackModal('error', 'Erro ao enviar mensagem. Tente novamente.');
            });
        });
    }
});

// Função showFeedback removida - usando apenas showFeedbackModal do personalizado.js
</script>

<style>
/* =============================================
   MODAL DE FEEDBACK BONITO - Nixcom
   Baseado no GPHub que funcionou perfeitamente
   ============================================= */

/* Modal principal com animações suaves */
.modal {
    z-index: 999999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    animation: modalFadeIn 0.3s ease-out !important;
    overflow: hidden !important;
}

/* Evitar barra de rolagem no modal - CORREÇÃO DEFINITIVA */
.modal.show {
    overflow: hidden !important;
}

.modal.show .modal-dialog {
    overflow: visible !important;
}

/* Forçar remoção de scroll em todos os elementos do modal - CORREÇÃO DEFINITIVA */
.modal,
.modal.show,
.modal-dialog,
.modal-dialog-centered,
.modal-content,
.modal-body {
    overflow: hidden !important;
    max-height: none !important;
}

/* Correção específica para SPA - manter centralização sem posicionamento fixo */
.modal.show {
    overflow: hidden !important;
}

.modal.show .modal-dialog {
    margin: 0 auto !important;
    max-height: 90vh !important;
    overflow: hidden !important;
}

.modal.show .modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 auto !important;
    padding: 1rem !important;
    overflow: hidden !important;
}

/* Garantir que o body não tenha scroll quando modal estiver aberto */
body.modal-open {
    overflow: hidden !important;
    padding-right: 0 !important;
}

/* Correção para remover backdrop escuro após fechar modal */
body:not(.modal-open) {
    overflow: auto !important;
    padding-right: 0 !important;
}

/* Forçar remoção do backdrop após fechar */
.modal-backdrop {
    z-index: 999998 !important;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.8) 0%, rgba(52, 73, 94, 0.9) 100%) !important;
    backdrop-filter: blur(5px) !important;
    animation: backdropFadeIn 0.3s ease-out !important;
}

/* Garantir que o backdrop seja removido */
.modal-backdrop.fade {
    opacity: 0 !important;
}

.modal-backdrop.show {
    opacity: 1 !important;
}

/* Forçar remoção de todos os backdrops */
.modal-backdrop {
    transition: opacity 0.15s linear !important;
}

/* Correção definitiva para backdrop */
body.modal-open .modal-backdrop {
    z-index: 999998 !important;
}

body:not(.modal-open) .modal-backdrop {
    display: none !important;
}

/* Garantir que não haja backdrop duplicado */
.modal-backdrop + .modal-backdrop {
    display: none !important;
}

/* Correção específica para o modal-dialog */
.modal-dialog {
    margin: 0 !important;
    max-width: 500px !important;
    width: 90% !important;
    max-height: 90vh !important;
    overflow: hidden !important;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Backdrop com gradiente bonito */
.modal-backdrop {
    z-index: 999998 !important;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.8) 0%, rgba(52, 73, 94, 0.9) 100%) !important;
    backdrop-filter: blur(5px) !important;
    animation: backdropFadeIn 0.3s ease-out !important;
}

@keyframes backdropFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Dialog centralizado com animação - CORREÇÃO PARA SPA */
.modal-dialog {
    margin: 1.75rem auto !important;
    max-width: 500px !important;
    width: 90% !important;
    animation: dialogSlideIn 0.4s ease-out !important;
}

@keyframes dialogSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: calc(100vh - 3.5rem) !important;
    max-height: calc(100vh - 3.5rem) !important;
    overflow: hidden !important;
    margin: 0 auto !important;
    padding: 1rem !important;
}

/* Conteúdo do modal com design moderno */
.modal-content {
    border-radius: 20px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    border: none !important;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
    overflow: hidden !important;
    position: relative !important;
}

/* Header com gradiente dinâmico */
.modal-header {
    background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%) !important;
    color: white !important;
    border: none !important;
    padding: 25px 30px !important;
    position: relative !important;
    overflow: hidden !important;
}

.modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Título com tipografia moderna */
.modal-title {
    font-family: 'Poppins', sans-serif !important;
    font-weight: 700 !important;
    font-size: 1.5rem !important;
    margin: 0 !important;
    color: white !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;
    position: relative !important;
    z-index: 1 !important;
}

/* Body com espaçamento generoso */
.modal-body {
    padding: 30px !important;
    background: #ffffff !important;
    text-align: center !important;
    font-family: 'Poppins', sans-serif !important;
    min-height: 150px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: center !important;
    position: relative !important;
}

/* Ícone com animação */
#feedbackIcon {
    font-size: 4rem !important;
    margin-bottom: 20px !important;
    display: block !important;
    text-align: center !important;
    animation: iconBounce 0.6s ease-out !important;
    text-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
}

@keyframes iconBounce {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Mensagem com design destacado */
#feedbackMessage {
    font-size: 1.2rem !important;
    line-height: 1.6 !important;
    margin: 0 !important;
    padding: 20px !important;
    word-wrap: break-word !important;
    max-width: 100% !important;
    display: block !important;
    text-align: center !important;
    color: #2c3e50 !important;
    font-weight: 600 !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border: 2px solid #dee2e6 !important;
    border-radius: 15px !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    position: relative !important;
    animation: messageSlideIn 0.5s ease-out !important;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Footer com botão moderno */
.modal-footer {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-top: 1px solid #dee2e6 !important;
    padding: 20px 30px !important;
    border-radius: 0 0 20px 20px !important;
}

/* Botão OK com design moderno */
#feedbackModalOkBtn {
    background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%) !important;
    border: none !important;
    border-radius: 25px !important;
    padding: 12px 30px !important;
    font-weight: 600 !important;
    font-size: 1rem !important;
    color: white !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 15px rgba(78, 84, 200, 0.3) !important;
    position: relative !important;
    overflow: hidden !important;
}

#feedbackModalOkBtn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

#feedbackModalOkBtn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(78, 84, 200, 0.4) !important;
}

#feedbackModalOkBtn:hover::before {
    left: 100%;
}

#feedbackModalOkBtn:active {
    transform: translateY(0) !important;
}

/* Cores específicas por tipo de feedback */
.modal-header.success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.modal-header.error {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%) !important;
}

.modal-header.warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
}

.modal-header.info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%) !important;
}

.modal-header.primary {
    background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%) !important;
}

/* Ícones coloridos */
#feedbackIcon.text-success {
    color: #28a745 !important;
}

#feedbackIcon.text-danger {
    color: #dc3545 !important;
}

#feedbackIcon.text-warning {
    color: #ffc107 !important;
}

#feedbackIcon.text-info {
    color: #17a2b8 !important;
}

#feedbackIcon.text-primary {
    color: #007bff !important;
}

/* Responsividade */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 1rem auto !important;
        max-width: 95% !important;
    }
    
    .modal-header {
        padding: 20px !important;
    }
    
    .modal-body {
        padding: 20px !important;
    }
    
    .modal-title {
        font-size: 1.3rem !important;
    }
    
    #feedbackMessage {
        font-size: 1.1rem !important;
        padding: 15px !important;
    }
    
    #feedbackIcon {
        font-size: 3rem !important;
    }
}

/* Animações de entrada */
.modal.show .modal-dialog {
    animation: modalShow 0.3s ease-out !important;
}

@keyframes modalShow {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Garantir que o modal apareça acima de tudo */
#feedbackModal {
    z-index: 999999999 !important;
}

#feedbackModal .modal-dialog {
    z-index: 1000000000 !important;
}

/* Efeito de brilho no modal */
.modal-content::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #4e54c8, #8f94fb, #4e54c8);
    border-radius: 22px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show .modal-content::before {
    opacity: 0.3;
}

/* Estilos para Cards de Categorias */
.categories-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f4f8 100%);
    position: relative;
    overflow: hidden;
}

.categories-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hearts" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(233,30,99,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23hearts)"/></svg>');
    opacity: 0.3;
}

.category-card {
    background: white;
    border-radius: 25px;
    box-shadow: 0 15px 35px rgba(233, 30, 99, 0.1);
    transition: all 0.4s ease;
    overflow: hidden;
    position: relative;
    border: 3px solid transparent;
    height: 100%;
}

.category-card:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 25px 50px rgba(233, 30, 99, 0.2);
    border-color: #e91e63;
}

.category-image {
    height: 250px;
    position: relative;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(233, 30, 99, 0.8) 0%, rgba(248, 187, 217, 0.8) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.category-card:hover .category-overlay {
    opacity: 1;
}

.category-icon {
    font-size: 4rem;
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.category-content {
    padding: 30px;
    text-align: center;
}

.category-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c2c2c;
    margin-bottom: 15px;
    position: relative;
}

.category-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: linear-gradient(135deg, #e91e63 0%, #f8bbd9 100%);
    border-radius: 2px;
}

.category-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 25px;
    font-size: 1rem;
}

.category-btn {
    display: inline-block;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 15px 30px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    position: relative;
    overflow: hidden;
}

.category-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.category-btn:hover::before {
    left: 100%;
}

.category-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(220, 53, 69, 0.4);
    color: white;
    text-decoration: none;
}

/* Responsividade */
@media (max-width: 768px) {
    .category-card {
        margin-bottom: 2rem;
    }
    
    .category-image {
        height: 200px;
    }
    
    .category-content {
        padding: 20px;
    }
    
    .category-title {
        font-size: 1.5rem;
    }
    
    .category-btn {
        padding: 12px 25px;
        font-size: 1rem;
    }
}

/* Efeitos especiais */
.category-card[data-category="mulher"] {
    background: linear-gradient(135deg, #fff 0%, #ffebee 100%);
}

.category-card[data-category="homem"] {
    background: linear-gradient(135deg, #fff 0%, #ffebee 100%);
}

.category-card[data-category="trans"] {
    background: linear-gradient(135deg, #fff 0%, #ffebee 100%);
}

.category-card[data-category="mulher"]:hover {
    border-color: #dc3545;
}

.category-card[data-category="homem"]:hover {
    border-color: #dc3545;
}

.category-card[data-category="trans"]:hover {
    border-color: #dc3545;
}
</style>