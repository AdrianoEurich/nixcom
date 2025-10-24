/**
 * JavaScript para a página de visualizar anúncio
 * Funcionalidades específicas para a visualização de anúncios
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: Página de visualizar anúncio carregada');
    
    // Configuração inicial
    setupAnuncioView();
    
    // Configuração dos botões de administrador (se existirem)
    setupAdminButtons();
    
    // Aplica cor de fundo para SPA
    applyBackgroundColor();
});

// Função para aplicar cor de fundo (chamada pelo SPA)
window.initializeVisualizarAnuncioPage = function(url, data) {
    console.log('INFO JS: Inicializando página de visualizar anúncio via SPA');
    
    // Aguarda um pouco para garantir que o DOM foi carregado
    setTimeout(() => {
        setupAnuncioView();
        setupAdminButtons();
        applyBackgroundColor();
    }, 100);
};

/**
 * Aplica a cor de fundo cinza na área de conteúdo
 */
function applyBackgroundColor() {
    console.log('INFO JS: Aplicando cor de fundo cinza');
    
    // Aplica cor no content-wrapper
    const contentWrapper = document.getElementById('content-wrapper');
    if (contentWrapper) {
        contentWrapper.style.backgroundColor = '#1f1f1f';
        contentWrapper.style.minHeight = '100vh';
    }
    
    // Aplica cor no main-content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.backgroundColor = '#1f1f1f';
        mainContent.style.minHeight = '100vh';
    }
    
    // Aplica cor no viewport
    const viewport = document.querySelector('.viewport');
    if (viewport) {
        viewport.style.backgroundColor = '#1f1f1f';
    }
    
    // Aplica cor no body
    document.body.style.backgroundColor = '#1f1f1f';
    
    console.log('INFO JS: Cor de fundo aplicada com sucesso');
}

/**
 * Remove a cor de fundo cinza aplicada pela página
 */
function removeBackgroundColor() {
    console.log('INFO JS: Removendo cor de fundo cinza');
    
    // Remove cor do content-wrapper
    const contentWrapper = document.getElementById('content-wrapper');
    if (contentWrapper) {
        contentWrapper.style.backgroundColor = '';
        contentWrapper.style.minHeight = '';
    }
    
    // Remove cor do main-content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.backgroundColor = '';
        mainContent.style.minHeight = '';
    }
    
    // Remove cor do viewport
    const viewport = document.querySelector('.viewport');
    if (viewport) {
        viewport.style.backgroundColor = '';
    }
    
    // Remove cor do body
    document.body.style.backgroundColor = '';
    
    console.log('INFO JS: Cor de fundo removida com sucesso');
}

/**
 * Função global para limpeza de eventos da página de visualizar anúncio
 */
window.clearVisualizarEvents = function() {
    console.log('INFO JS: clearVisualizarEvents - Limpando eventos da página de visualizar anúncio');
    removeBackgroundColor();
};

/**
 * Configuração inicial da página de visualizar anúncio
 */
function setupAnuncioView() {
    console.log('INFO JS: Iniciando setupAnuncioView...');
    
    const anuncioId = document.querySelector('[data-anuncio-id]')?.dataset.anuncioId;
    
    if (!anuncioId) {
        console.warn('WARN JS: ID do anúncio não encontrado na página');
    }
    
    console.log('INFO JS: Configurando visualização do anúncio ID:', anuncioId);
    
    // Verifica se os elementos existem
    const galleryItems = document.querySelectorAll('.galeria-item');
    const modal = document.getElementById('carouselModal');
    
    console.log('INFO JS: Elementos encontrados:');
    console.log('- Galeria items:', galleryItems.length);
    console.log('- Modal carrossel:', modal ? 'Sim' : 'Não');
    
    // Adiciona efeitos visuais aos elementos de mídia
    setupMediaEffects();
    
    // Configura carrossel para as imagens da galeria
    setupGalleryCarousel();
}

/**
 * Configura efeitos visuais para elementos de mídia
 */
function setupMediaEffects() {
    // Efeito de hover para cards de informação
    const infoItems = document.querySelectorAll('.info-item');
    infoItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.05)';
        });
    });
    
    // Efeito de zoom para imagens da galeria
    const galleryImages = document.querySelectorAll('.media-item img');
    galleryImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * Configura carrossel para imagens da galeria
 */
function setupGalleryCarousel() {
    console.log('INFO JS: Iniciando configuração do carrossel...');
    
    // Coleta todas as fotos da galeria
    const galleryItems = document.querySelectorAll('.galeria-item');
    const galleryPhotos = [];
    
    console.log('INFO JS: Encontrados', galleryItems.length, 'itens da galeria');
    
    galleryItems.forEach((item, index) => {
        const img = item.querySelector('img');
        if (img) {
            galleryPhotos.push(img.src);
            console.log('INFO JS: Foto', index + 1, ':', img.src);
        }
    });
    
    console.log('INFO JS: Configurando carrossel com', galleryPhotos.length, 'fotos');
    
    // Adiciona event listeners para abrir o carrossel
    galleryItems.forEach((item, index) => {
        console.log('INFO JS: Adicionando event listener para item', index);
        
        item.addEventListener('click', function(e) {
            console.log('INFO JS: Clique detectado no item', index);
            e.preventDefault();
            openCarousel(index);
        });
        
        // Adiciona cursor pointer
        item.style.cursor = 'pointer';
    });
    
    // Torna as funções globais para uso no HTML
    window.galleryPhotos = galleryPhotos;
    window.currentImageIndex = 0;
    window.isZoomed = false;
    
    // Configura event listeners do carrossel
    setupCarouselEventListeners();
    
    console.log('INFO JS: Carrossel configurado com sucesso!');
}

/**
 * Abre uma imagem em lightbox
 */
function openImageLightbox(src, alt) {
    // Remove lightbox existente
    const existingLightbox = document.getElementById('imageLightbox');
    if (existingLightbox) {
        existingLightbox.remove();
    }
    
    // Cria o lightbox
    const lightbox = document.createElement('div');
    lightbox.id = 'imageLightbox';
    lightbox.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        cursor: pointer;
    `;
    
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    img.style.cssText = `
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    `;
    
    const closeButton = document.createElement('div');
    closeButton.innerHTML = '&times;';
    closeButton.style.cssText = `
        position: absolute;
        top: 20px;
        right: 30px;
        color: white;
        font-size: 40px;
        cursor: pointer;
        z-index: 10000;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        transition: background 0.3s ease;
    `;
    
    closeButton.addEventListener('mouseenter', function() {
        this.style.background = 'rgba(255, 0, 0, 0.7)';
    });
    
    closeButton.addEventListener('mouseleave', function() {
        this.style.background = 'rgba(0, 0, 0, 0.5)';
    });
    
    lightbox.appendChild(img);
    lightbox.appendChild(closeButton);
    document.body.appendChild(lightbox);
    
    // Fecha o lightbox ao clicar
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox || e.target === closeButton) {
            lightbox.remove();
        }
    });
    
    // Fecha com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            lightbox.remove();
        }
    });
}

/**
 * Configura os botões de administrador
 */
function setupAdminButtons() {
    // Verifica se existe a seção de administrador
    const adminSection = document.querySelector('.admin-section');
    
    if (!adminSection) {
        return; // Não é admin, não configura os botões
    }
    
    // Botão Aprovar Anúncio
    const btnApprove = document.getElementById('btnApproveAnuncio');
    if (btnApprove) {
        btnApprove.addEventListener('click', function() {
            const anuncioId = this.dataset.anuncioId;
            const anuncianteUserId = this.dataset.anuncianteUserId;
            
            handleAdminAction('approve', anuncioId, anuncianteUserId);
        });
    }
    
    // Botão Reprovar Anúncio
    const btnReject = document.getElementById('btnRejectAnuncio');
    if (btnReject) {
        btnReject.addEventListener('click', function() {
                const anuncioId = this.dataset.anuncioId;
                const anuncianteUserId = this.dataset.anuncianteUserId;
                
            handleAdminAction('reject', anuncioId, anuncianteUserId);
        });
    }
    
    // Botão Pausar Anúncio
    const btnDeactivate = document.getElementById('btnDeactivateAnuncio');
    if (btnDeactivate) {
        btnDeactivate.addEventListener('click', function() {
                const anuncioId = this.dataset.anuncioId;
                const anuncianteUserId = this.dataset.anuncianteUserId;
                
            handleAdminAction('deactivate', anuncioId, anuncianteUserId);
        });
    }
    
    // Botão Ativar Anúncio
    const btnActivate = document.getElementById('btnActivateAnuncio');
    if (btnActivate) {
        btnActivate.addEventListener('click', function() {
                const anuncioId = this.dataset.anuncioId;
                const anuncianteUserId = this.dataset.anuncianteUserId;
                
            handleAdminAction('activate', anuncioId, anuncianteUserId);
        });
    }
}

/**
 * Manipula ações de administrador
 */
async function handleAdminAction(action, anuncioId, anuncianteUserId) {
    const actionMessages = {
        'approve': {
            title: 'Aprovar Anúncio',
            message: 'Tem certeza que deseja aprovar este anúncio?',
            endpoint: 'approveAnuncio'
        },
        'reject': {
            title: 'Reprovar Anúncio',
            message: 'Tem certeza que deseja reprovar este anúncio?',
            endpoint: 'rejectAnuncio'
        },
        'deactivate': {
            title: 'Pausar Anúncio',
            message: 'Tem certeza que deseja pausar este anúncio?',
            endpoint: 'deactivateAnuncio'
        },
        'activate': {
            title: 'Ativar Anúncio',
            message: 'Tem certeza que deseja ativar este anúncio?',
            endpoint: 'activateAnuncio'
        }
    };
    
    const config = actionMessages[action];
    if (!config) {
        console.error('ERRO JS: Ação de administrador inválida:', action);
        return;
    }
    
    try {
        // Mostra modal de confirmação
        const confirmed = await window.showConfirmModal(
            config.message,
            config.title,
            'warning',
            'Confirmar',
            'Cancelar'
        );
        
        if (!confirmed) {
            return;
    }
    
        // Mostra loading
        window.showLoadingModal(`Processando ${config.title.toLowerCase()}...`);
        
        // Faz a requisição AJAX
        const formData = new FormData();
        formData.append('anuncio_id', anuncioId);
        formData.append('anunciante_user_id', anuncianteUserId);
        
        const response = await fetch(`${window.URLADM}anuncio/${config.endpoint}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
            body: formData
        });
        
        const result = await response.json();
        
        // Esconde loading
        window.hideLoadingModal();
        
        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso');
            
            // Atualiza o status na página
            updateAnuncioStatus(result.new_status);
            
            // Recarrega a página após 2 segundos
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            window.showFeedbackModal('error', result.message || 'Erro ao processar a ação', 'Erro');
        }
        
    } catch (error) {
        console.error('ERRO JS: Erro na requisição de administrador:', error);
        window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão. Tente novamente.', 'Erro de Rede');
    }
}

/**
 * Atualiza o status do anúncio na página
 */
function updateAnuncioStatus(newStatus) {
    const statusBadge = document.querySelector('.status-badge .badge');
    if (statusBadge) {
        // Remove classes antigas
        statusBadge.className = 'badge';
        
        // Adiciona nova classe baseada no status
        switch (newStatus) {
            case 'active':
                statusBadge.classList.add('bg-success');
                statusBadge.textContent = 'Ativo';
                break;
            case 'pausado':
                statusBadge.classList.add('bg-info');
                statusBadge.textContent = 'Pausado';
                break;
            case 'rejected':
                statusBadge.classList.add('bg-danger');
                statusBadge.textContent = 'Rejeitado';
                break;
            case 'pending':
                statusBadge.classList.add('bg-warning', 'text-dark');
                statusBadge.textContent = 'Pendente';
                break;
            default:
                statusBadge.classList.add('bg-secondary');
                statusBadge.textContent = 'Desconhecido';
        }
    }
}

/**
 * Função para compartilhar o anúncio (funcionalidade futura)
 */
function shareAnuncio() {
    const anuncioId = document.querySelector('[data-anuncio-id]')?.dataset.anuncioId;
    const anuncioUrl = `${window.location.origin}${window.location.pathname}?anuncio=${anuncioId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Anúncio Especial',
            text: 'Confira este anúncio especial!',
            url: anuncioUrl
        });
    } else {
        // Fallback para copiar URL
        navigator.clipboard.writeText(anuncioUrl).then(() => {
            window.showFeedbackModal('success', 'URL copiada para a área de transferência!', 'Compartilhado');
        }).catch(() => {
            window.showFeedbackModal('error', 'Erro ao copiar URL', 'Erro');
        });
    }
}

// Adiciona função global para compartilhar
window.shareAnuncio = shareAnuncio;

// ===== FUNÇÕES DO CARROSSEL =====

/**
 * Abre o carrossel de fotos
 */
function openCarousel(index) {
    console.log('INFO JS: openCarousel chamado com índice:', index);
    
    if (!window.galleryPhotos || window.galleryPhotos.length === 0) {
        console.warn('WARN JS: Nenhuma foto encontrada na galeria');
        return;
    }
    
    console.log('INFO JS: Abrindo carrossel para foto', index + 1, 'de', window.galleryPhotos.length);
    
    window.currentImageIndex = index;
    window.isZoomed = false;
    updateCarousel();
    
    const modal = document.getElementById('carouselModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        console.log('INFO JS: Modal do carrossel aberto');
    } else {
        console.error('ERRO JS: Modal do carrossel não encontrado!');
    }
}

/**
 * Fecha o carrossel
 */
function closeCarousel() {
    console.log('INFO JS: closeCarousel chamada');
    const modal = document.getElementById('carouselModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log('INFO JS: Carrossel fechado');
    } else {
        console.error('ERRO JS: Modal não encontrado para fechar');
    }
}

/**
 * Atualiza o carrossel com a imagem atual
 */
function updateCarousel() {
    const modal = document.getElementById('carouselModal');
    const image = document.getElementById('carouselImage');
    const counter = document.getElementById('carouselCounter');
    const thumbnails = document.getElementById('carouselThumbnails');
    
    if (!window.galleryPhotos || window.galleryPhotos.length === 0) return;
    
    // Animação de fade out
    image.style.opacity = '0';
    
    setTimeout(() => {
        // Atualiza imagem principal
        image.src = window.galleryPhotos[window.currentImageIndex];
        image.alt = `Foto ${window.currentImageIndex + 1}`;
        
        // Animação de fade in
        image.style.opacity = '1';
        
        // Reset zoom quando muda de imagem
        window.isZoomed = false;
        image.style.transform = 'scale(1)';
        image.style.cursor = 'zoom-in';
        
        // Atualiza contador
        counter.textContent = `${window.currentImageIndex + 1} / ${window.galleryPhotos.length}`;
        
        // Gera thumbnails
        thumbnails.innerHTML = '';
        window.galleryPhotos.forEach((photo, index) => {
            const thumbnail = document.createElement('img');
            thumbnail.src = photo;
            thumbnail.className = `carousel-thumbnail ${index === window.currentImageIndex ? 'active' : ''}`;
            thumbnail.onclick = () => {
                window.currentImageIndex = index;
                updateCarousel();
            };
            thumbnails.appendChild(thumbnail);
        });
    }, 150);
}

/**
 * Vai para a imagem anterior
 */
function previousImage() {
    console.log('INFO JS: previousImage chamada');
    if (!window.galleryPhotos || window.galleryPhotos.length === 0) {
        console.warn('WARN JS: Nenhuma foto na galeria para navegar');
        return;
    }
    window.currentImageIndex = (window.currentImageIndex - 1 + window.galleryPhotos.length) % window.galleryPhotos.length;
    console.log('INFO JS: Índice anterior:', window.currentImageIndex);
    updateCarousel();
}

/**
 * Vai para a próxima imagem
 */
function nextImage() {
    console.log('INFO JS: nextImage chamada');
    if (!window.galleryPhotos || window.galleryPhotos.length === 0) {
        console.warn('WARN JS: Nenhuma foto na galeria para navegar');
        return;
    }
    window.currentImageIndex = (window.currentImageIndex + 1) % window.galleryPhotos.length;
    console.log('INFO JS: Índice próximo:', window.currentImageIndex);
    updateCarousel();
}

/**
 * Alterna zoom da imagem
 */
function toggleZoom() {
    const image = document.getElementById('carouselImage');
    if (window.isZoomed) {
        image.style.transform = 'scale(1)';
        image.style.cursor = 'zoom-in';
        window.isZoomed = false;
    } else {
        image.style.transform = 'scale(2)';
        image.style.cursor = 'zoom-out';
        window.isZoomed = true;
    }
}

// Torna as funções globais
window.openCarousel = openCarousel;
window.closeCarousel = closeCarousel;
window.updateCarousel = updateCarousel;
window.previousImage = previousImage;
window.nextImage = nextImage;
window.toggleZoom = toggleZoom;

// Função de teste para o carrossel
window.testCarousel = function() {
    console.log('INFO JS: Testando carrossel...');
    
    const modal = document.getElementById('carouselModal');
    const galleryItems = document.querySelectorAll('.galeria-item');
    
    console.log('Modal encontrado:', modal ? 'Sim' : 'Não');
    console.log('Itens da galeria:', galleryItems.length);
    
    if (galleryItems.length > 0) {
        console.log('INFO JS: Abrindo carrossel de teste...');
        openCarousel(0);
    } else {
        console.warn('WARN JS: Nenhum item da galeria encontrado para teste');
    }
};

// Função para configurar event listeners do carrossel
function setupCarouselEventListeners() {
    console.log('INFO JS: Configurando event listeners do carrossel...');
    
    // Event listeners dos botões do carrossel
    const closeBtn = document.getElementById('carouselClose');
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');
    const image = document.getElementById('carouselImage');
    const modal = document.getElementById('carouselModal');

    console.log('INFO JS: Elementos do carrossel encontrados:');
    console.log('- Botão fechar:', closeBtn ? 'Sim' : 'Não');
    console.log('- Botão anterior:', prevBtn ? 'Sim' : 'Não');
    console.log('- Botão próximo:', nextBtn ? 'Sim' : 'Não');
    console.log('- Imagem:', image ? 'Sim' : 'Não');
    console.log('- Modal:', modal ? 'Sim' : 'Não');

    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            console.log('INFO JS: Botão fechar clicado');
            closeCarousel();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            console.log('INFO JS: Botão anterior clicado');
            previousImage();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            console.log('INFO JS: Botão próximo clicado');
            nextImage();
        });
    }

    // Clique na imagem para zoom
    if (image) {
        image.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('INFO JS: Imagem clicada para zoom');
            toggleZoom();
        });
    }

    // Fecha modal ao clicar fora da imagem
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                console.log('INFO JS: Modal clicado para fechar');
                closeCarousel();
            }
        });
    }
}

// Navegação com teclado (event listener global)
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('carouselModal');
    if (modal && modal.style.display === 'block') {
        switch(e.key) {
            case 'Escape':
                console.log('INFO JS: Tecla ESC pressionada');
                closeCarousel();
                break;
            case 'ArrowLeft':
                console.log('INFO JS: Seta esquerda pressionada');
                previousImage();
                break;
            case 'ArrowRight':
                console.log('INFO JS: Seta direita pressionada');
                nextImage();
                break;
        }
    }
});



