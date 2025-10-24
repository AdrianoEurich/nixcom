/**
 * script.js - Funcionalidades principais do site Nixcom
 * 
 * Este arquivo contém toda a interatividade do site principal
 * incluindo animações, efeitos de scroll e validações de formulário
 */

document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // EFEITO DE SCROLL NA NAVBAR
    // =============================================
    const navbar = document.querySelector('.navbar');
    
    // Throttling para limitar a frequência das chamadas de scroll
    let scrollTimeout;
    let animationTimeout;
    let isScrolling = false;
    let lastScrollTime = 0;
    
    // Função unificada para gerenciar todos os efeitos de scroll
    function handleScroll() {
        const now = Date.now();
        
        // DEBUG: Log de scroll (limitado)
            // handleScroll executado
        
        // Marcar como em execução
        isScrolling = true;
        
        // Efeito na navbar (sempre executa)
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        // Animações de scroll (throttled - máximo 1x por 100ms)
        if (now - lastScrollTime > 100) {
            lastScrollTime = now;
            
            if (animationTimeout) {
                clearTimeout(animationTimeout);
            }
            
            animationTimeout = setTimeout(() => {
                animateOnScroll();
                isScrolling = false;
                animationTimeout = null;
            }, 50);
        }
        
        // Destacar link ativo na navegação (throttled)
        if (!scrollTimeout) {
            scrollTimeout = setTimeout(() => {
                const sections = document.querySelectorAll('section');
                let current = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (pageYOffset >= sectionTop - 100) {
                        current = section.getAttribute('id');
                    }
                });
                
                // Atualiza o estilo dos links da navegação para destacar o ativo
                document.querySelectorAll('nav a').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${current}`) {
                        link.classList.add('active');
                    }
                });
                
                scrollTimeout = null;
            }, 200); // 200ms de delay
        }
    }
    
    // Adiciona um único event listener para scroll com throttling
    window.addEventListener('scroll', handleScroll, { passive: true });
  
    // =============================================
    // SCROLL SUAVE PARA LINKS ANCORA
    // =============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 70,
                    behavior: 'smooth'
                });
                
                // Fechar menu mobile ao clicar em um link
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    navbarCollapse.classList.remove('show');
                }
            }
        });
    });
  
    // =============================================
    // ANIMAÇÃO AO ROLAR A PÁGINA
    // =============================================
    function animateOnScroll() {
        const elements = document.querySelectorAll('.service-card, .highlight-item, .contact-form, .info-item, .cta-box');
        
        elements.forEach((element, index) => {
            try {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            } catch (error) {
                console.error('🔍 DEBUG LOGIN: Erro na animação do elemento', index, error);
            }
        });
    }
    
    // Configurar estado inicial dos elementos animados
    document.querySelectorAll('.service-card, .highlight-item, .contact-form, .info-item, .cta-box').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'all 0.6s ease';
    });
    
    // Inicializa a animação ao carregar a página
    animateOnScroll(); // Executar uma vez ao carregar a página
  
    // =============================================
    // ENVIO DE FORMULÁRIOS
    // =============================================
    const forms = document.querySelectorAll('.contact-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            
            // Simular envio do formulário
            setTimeout(() => {
                submitButton.innerHTML = '<i class="fas fa-check"></i> Enviado!';
                
                // Resetar formulário após 2 segundos
                setTimeout(() => {
                    this.reset();
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                    
                    // Mostrar mensagem de sucesso (pode ser substituído por um modal)
                    alert('Mensagem enviada com sucesso! Entraremos em contato em breve.');
                }, 2000);
            }, 1500);
        });
    });
  
    // =============================================
    // DESTACAR LINK ATIVO NA NAVEGAÇÃO
    // =============================================
    // (Movido para a função handleScroll unificada acima)
  });