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
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
  
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
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;
            
            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    }
    
    // Configurar estado inicial dos elementos animados
    document.querySelectorAll('.service-card, .highlight-item, .contact-form, .info-item, .cta-box').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'all 0.6s ease';
    });
    
    // Adicionar event listener para scroll
    window.addEventListener('scroll', animateOnScroll);
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
    const sections = document.querySelectorAll('section');
    
    window.addEventListener('scroll', function() {
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (pageYOffset >= sectionTop - 100) {
                current = section.getAttribute('id');
            }
        });
        
        document.querySelectorAll('nav a').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
  });