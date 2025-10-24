/**
 * JavaScript para Visualização de Anúncios - Nixcom
 * Sistema de Galeria Moderna
 */

// Sistema de Galeria Moderna
let currentImageIndex = 0;
let galleryImages = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 DEBUG: Script da página de visualizar anúncio carregado');
    
    // O navbar agora é gerenciado pelo navbar_unificado.php
    // Apenas inicializar a galeria
    initGallery();
    initImageViewer();
});

// Inicializar Galeria
function initGallery() {
    const galleryItems = document.querySelectorAll('.gallery-img');
    galleryImages = Array.from(galleryItems).map(img => img.src);
}

// Inicializar Visualizador
function initImageViewer() {
    const viewer = document.getElementById('imageViewer');
    const viewerImage = document.getElementById('viewerImage');
    
    // Navegação por teclado
    document.addEventListener('keydown', function(e) {
        if (viewer.classList.contains('show')) {
            if (e.key === 'ArrowLeft') {
                prevImage();
            } else if (e.key === 'ArrowRight') {
                nextImage();
            } else if (e.key === 'Escape') {
                closeImageViewer();
            }
        }
    });
}

// Abrir Visualizador
function openImageViewer(index) {
    const viewer = document.getElementById('imageViewer');
    const viewerImage = document.getElementById('viewerImage');
    const viewerCurrent = document.getElementById('viewerCurrent');
    const viewerTotal = document.getElementById('viewerTotal');
    
    currentImageIndex = index;
    
    // Mostrar visualizador
    viewer.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Carregar imagem
    showLoader();
    viewerImage.src = galleryImages[currentImageIndex];
    viewerImage.alt = `Foto ${currentImageIndex + 1}`;
    
    // Atualizar contador
    viewerCurrent.textContent = currentImageIndex + 1;
    viewerTotal.textContent = galleryImages.length;
    
    // Esconder loader quando imagem carregar
    viewerImage.onload = function() {
        hideLoader();
    };
}

// Fechar Visualizador
function closeImageViewer() {
    const viewer = document.getElementById('imageViewer');
    
    viewer.classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Navegação
function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    loadImage();
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
    loadImage();
}

function loadImage() {
    const viewerImage = document.getElementById('viewerImage');
    const viewerCurrent = document.getElementById('viewerCurrent');
    
    showLoader();
    viewerImage.src = galleryImages[currentImageIndex];
    viewerImage.alt = `Foto ${currentImageIndex + 1}`;
    
    // Atualizar contador
    viewerCurrent.textContent = currentImageIndex + 1;
    
    // Esconder loader quando imagem carregar
    viewerImage.onload = function() {
        hideLoader();
    };
}

// Funções auxiliares
function showLoader() {
    const loader = document.querySelector('.viewer-loader');
    if (loader) {
        loader.classList.add('show');
    }
}

function hideLoader() {
    const loader = document.querySelector('.viewer-loader');
    if (loader) {
        loader.classList.remove('show');
    }
}
