<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="content pt-0 px-0 pb-3" id="paymentContent" data-page-type="payment">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-card">
                    <div class="payment-header">
                        <h2 class="payment-title">
                            <i class="fas fa-credit-card me-2"></i>Pagamento via PIX
                        </h2>
                        <p class="payment-subtitle">Escaneie o QR Code ou copie o código PIX para finalizar seu pagamento</p>
                    </div>
                    
                    <div class="payment-content">
                        <!-- Informações do Plano -->
                        <div class="plan-info mb-4">
                            <div class="plan-card">
                                <h4 class="plan-name"><?= $this->data['plan_name'] ?></h4>
                                <div class="plan-price"><?= $this->data['plan_price'] ?></div>
                                <div class="plan-features">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Painel administrativo</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Criação de anúncios</li>
                                        <li><i class="fas fa-check text-success me-2"></i><?= $this->data['plano']['max_fotos'] ?> fotos na galeria</li>
                                        <?php if ($this->data['plano']['max_videos'] > 0): ?>
                                        <li><i class="fas fa-check text-success me-2"></i><?= $this->data['plano']['max_videos'] ?> vídeos</li>
                                        <?php endif; ?>
                                        <?php if ($this->data['plano']['max_audios'] > 0): ?>
                                        <li><i class="fas fa-check text-success me-2"></i><?= $this->data['plano']['max_audios'] ?> áudios</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seleção de Período -->
                        <div class="period-selection mb-4">
                            <h5 class="mb-3">Escolha o período de pagamento:</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="period-option" data-period="1_mes">
                                        <div class="period-card">
                                            <h6>1 Mês</h6>
                                            <div class="period-price">R$ <?= number_format($this->data['plano']['preco_mensal'], 2, ',', '.') ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="period-option" data-period="6_meses">
                                        <div class="period-card">
                                            <h6>6 Meses</h6>
                                            <div class="period-price">R$ <?= number_format($this->data['plano']['preco_6_meses'], 2, ',', '.') ?></div>
                                            <small class="text-muted">Economia de 10%</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="period-option" data-period="12_meses">
                                        <div class="period-card">
                                            <h6>12 Meses</h6>
                                            <div class="period-price">R$ <?= number_format($this->data['plano']['preco_12_meses'], 2, ',', '.') ?></div>
                                            <small class="text-muted">Economia de 20%</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botão de Pagamento -->
                        <div class="payment-actions text-center">
                            <button id="generatePaymentBtn" class="btn btn-primary btn-lg" disabled>
                                <i class="fas fa-qrcode me-2"></i>Gerar PIX
                            </button>
                        </div>
                        
                        <!-- Área do PIX (inicialmente oculta) -->
                        <div id="pix-area" class="pix-area" style="display: none;">
                            <div class="pix-content">
                                <div class="pix-header">
                                    <h5><i class="fas fa-mobile-alt me-2"></i>Pague com PIX</h5>
                                    <p class="text-muted">Escaneie o QR Code com seu app bancário ou copie o código PIX</p>
                                </div>
                                
                                <div class="pix-options">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="pix-option active" id="qr-code-option">
                                                <div class="pix-option-header">
                                                    <i class="fas fa-qrcode"></i>
                                                    <span>QR Code</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pix-option" id="copy-paste-option">
                                                <div class="pix-option-header">
                                                    <i class="fas fa-copy"></i>
                                                    <span>Código PIX</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- QR Code -->
                                <div id="qrCodeSection" class="pix-content-area">
                                    <div class="qr-code-container">
                                        <div id="qrCodeImage" class="qr-code-image">
                                            <!-- QR Code será inserido aqui -->
                                        </div>
                                    </div>
                                    <p class="qr-instructions">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Abra seu app bancário, escaneie o QR Code e confirme o pagamento
                                    </p>
                                </div>
                                
                                <!-- Código PIX -->
                                <div id="pixCodeSection" class="pix-content-area" style="display: none;">
                                    <div class="pix-code-container">
                                        <textarea id="pixCode" class="pix-code" readonly></textarea>
                                        <button id="copyPixCode" class="btn btn-outline-primary">
                                            <i class="fas fa-copy me-2"></i>Copiar Código PIX
                                        </button>
                                    </div>
                                    <p class="pix-instructions">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Copie o código PIX e cole no seu app bancário para pagar
                                    </p>
                                </div>
                                
                                <!-- Status do Pagamento -->
                                <div class="payment-status">
                                    <div class="status-info">
                                        <div class="status-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="status-text">
                                            <h6>Aguardando Pagamento</h6>
                                            <p class="text-muted">O pagamento expira em <span id="expires-at">--:--</span></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botões de Ação -->
                                <div class="payment-actions-final">
                                    <button id="checkPaymentBtn" class="btn btn-success">
                                        <i class="fas fa-check me-2"></i>Verificar Pagamento
                                    </button>
                                    <button id="btnCancelPayment" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </button>
                                    <button id="newPaymentBtn" class="btn btn-outline-secondary" style="display: none;">
                                        <i class="fas fa-plus me-2"></i>Novo Pagamento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= URLADM ?>assets/css/pagamento.css">

<style>
/* CSS específico para centralização da página de pagamento via SPA */
#paymentContent > .container {
    max-width: 1100px;
    margin: 0 auto;
}

/* Forçar centralização segura quando carregado via SPA */
.main-content #paymentContent {
    display: flex;
    justify-content: center;
}

.main-content #paymentContent > .container {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto !important;
}

/* Garantir que os cartões usem todo o espaço disponível */
.card.h-100 {
    height: 100%;
}
</style>

<script>
// Definir URLADM para uso no JavaScript
window.URLADM = '<?= URLADM ?>';
window.PLANO_ID = <?= $this->data['plano']['id'] ?>;
</script>

<script src="<?= URLADM ?>assets/js/pagamento.js"></script>