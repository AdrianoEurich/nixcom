/**
 * script.js - Funcionalidades principais do site Nixcom
 *
 * Este arquivo contém toda a interatividade do site principal,
 * incluindo animações, efeitos de scroll e o envio do formulário de contato via AJAX.
 */

document.addEventListener('DOMContentLoaded', function () {
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
                document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + current) {
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
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            if (targetId === '#') return; // Evita clicar no link '#'

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // Realiza o scroll suave até o destino
                window.scrollTo({
                    top: targetElement.offsetTop - 70, // Ajuste para o topo com offset
                    behavior: 'smooth'
                });

                // Fecha o menu mobile quando um link é clicado
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
                console.error('🔍 DEBUG PERSONALIZADO: Erro na animação do elemento', index, error);
            }
        });
    }

    // Configuração inicial dos elementos que terão animação
    document.querySelectorAll('.service-card, .highlight-item, .contact-form, .info-item, .cta-box').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'all 0.6s ease'; // Transição suave
    });

    // Inicializa a animação ao carregar a página
    animateOnScroll(); // Executa uma vez ao carregar a página para garantir que a animação aconteça.

    // =============================================
    // =============================================
    // =============================================
    // ENVIO DE FORMULÁRIO DE CONTATO (AJUSTADO PARA AJAX)
    // =============================================
    const formContato = document.getElementById('formContato');
    if (formContato) {
        const btnEnviar = formContato.querySelector('.btn-enviar');
        const btnTextoPadrao = btnEnviar.textContent;

        // Observação: o modal de feedback agora é gerenciado globalmente.
        // Este arquivo usa a função global `showFeedbackModal(type, message)`.
        // A implementação concreta delega para `NixcomModalManager` quando disponível.

        // Função para validar o formato do e-mail
        const validarEmail = (email) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        };

        // Função para validar o formato do telefone (mínimo 10 dígitos)
        const validarTelefone = (telefone) => {
            const telefoneLimpo = telefone.replace(/[^0-9]/g, '');
            return telefoneLimpo.length >= 10; // Mínimo de 10 dígitos (DDD + número)
        };

        // Adiciona um listener de evento para o envio do formulário
        formContato.addEventListener('submit', function (event) {
            event.preventDefault(); // Impede o recarregamento padrão do formulário

            let errosFrontend = []; // Array para armazenar mensagens de erro do frontend

            const nomeInput = document.getElementById('nome');
            const emailInput = document.getElementById('email');
            const telefoneInput = document.getElementById('telefone');

            // Validação do campo nome
            if (!nomeInput.value.trim()) {
                errosFrontend.push('Por favor, preencha o seu nome.');
            }

            // Validação do campo e-mail
            if (!emailInput.value.trim()) {
                errosFrontend.push('Por favor, preencha o seu e-mail.');
            } else if (!validarEmail(emailInput.value)) {
                errosFrontend.push('Por favor, informe um e-mail válido.');
            }

            // Validação do campo telefone
            if (!telefoneInput.value.trim()) {
                errosFrontend.push('Por favor, preencha o seu telefone.');
            } else if (!validarTelefone(telefoneInput.value)) {
                errosFrontend.push('Por favor, informe um telefone válido (com DDD).');
            }

            // Se houver erros no frontend, exibe as mensagens no modal e interrompe o envio
            if (errosFrontend.length > 0) {
                showFeedbackModal('error', errosFrontend.join('<br>'));
                return; // Impede o envio se houver erros no frontend
            }

            // Adiciona o efeito de loading e desabilita o botão
            btnEnviar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            btnEnviar.disabled = true;

            const formData = new FormData(formContato);

            // Envia os dados do formulário via AJAX para o seu backend
            fetch(baseUrl + 'home/cadastrar', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json()) // Espera uma resposta no formato JSON
                .then(data => {
                    // Adiciona um delay de 2 segundos (2000ms) antes de mostrar o modal
                    setTimeout(() => {
                        // Verifica se a resposta indica sucesso
                        if (data.success) {
                            showFeedbackModal('success', data.message); // Exibe mensagem de sucesso no modal
                            formContato.reset(); // Limpa o formulário
                            btnEnviar.textContent = btnTextoPadrao; // Restaura o texto original do botão
                            btnEnviar.disabled = false; // Reabilita o botão
                        } else {
                            // Se a resposta indicar erro
                            showFeedbackModal('error', data.message); // Exibe mensagem de erro no modal
                            btnEnviar.textContent = btnTextoPadrao; // Restaura o texto original do botão
                            btnEnviar.disabled = false; // Reabilita o botão
                        }
                    }, 2000); // 2 segundos de delay
                })
                .catch(error => {
                    // Em caso de erro na requisição (erro de rede, servidor fora do ar, etc.)
                    console.error('Erro ao enviar o formulário:', error);
                    // Adiciona um delay de 2 segundos (2000ms) antes de mostrar o modal de erro
                    setTimeout(() => {
                        showFeedbackModal('error', 'Erro ao enviar o formulário. Por favor, tente novamente mais tarde.');
                        btnEnviar.textContent = btnTextoPadrao; // Restaura o texto original do botão
                        btnEnviar.disabled = false; // Reabilita o botão
                    }, 2000); // 2 segundos de delay
                });
        });
    }


    // Adicionando máscara de telefone (você pode usar uma biblioteca como jQuery Mask Plugin para mais opções)
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function (event) {
            let value = event.target.value.replace(/\D/g, '');
            const length = value.length;
            if (length > 0) {
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
            }
            event.target.value = value;
        });
    }

    // =============================================
    // DESTACAR LINK ATIVO NA NAVEGAÇÃO
    // =============================================
    // (Movido para a função handleScroll unificada acima)
});

// =============================================
// FUNÇÃO PARA MOSTRAR MODAL DE FEEDBACK
// =============================================
// Backwards-compatible global showFeedbackModal that delegates to the centralized manager
function showFeedbackModal(type, message) {
    // If the new manager is available, use it (preferred)
    try {
        if (window.NixcomModalManager && typeof window.NixcomModalManager.showSimple === 'function') {
            window.NixcomModalManager.showSimple(type, message);
            // ensure potential orphan backdrops are cleaned after an action
            if (typeof window.NixcomModalManager.cleanOrphans === 'function') {
                setTimeout(window.NixcomModalManager.cleanOrphans, 300);
            }
            return;
        }
    } catch (e) {
        console.warn('NixcomModalManager unavailable, falling back to legacy implementation', e);
    }

    // Legacy fallback: minimal behavior to avoid breaking older pages
    console.log('� DEBUG: fallback showFeedbackModal chamada com:', { type, message });
    const modal = document.getElementById('feedbackModal');
    const modalTitle = modal ? modal.querySelector('#feedbackModalLabel') : null;
    const modalIcon = modal ? modal.querySelector('#feedbackIcon') : null;
    const modalMessage = modal ? modal.querySelector('#feedbackMessage') : null;

    if (modal && modalTitle && modalIcon && modalMessage) {
        modalTitle.textContent = (type === 'success') ? 'Sucesso!' : 'Informação';
        modalMessage.innerHTML = message;
        modalIcon.className = (type === 'success') ? 'fas fa-check-circle text-success mb-3' : 'fas fa-info-circle text-primary mb-3';
        const bs = bootstrap.Modal.getOrCreateInstance(modal);
        bs.show();
        setTimeout(() => bs.hide(), 3500);
        // schedule a cleanup just in case
        setTimeout(limparBackdropSeNecessario, 600);
    } else {
        // As a last resort, use alert()
        alert(message.replace(/<[^>]*>?/gm, ''));
    }
}

// =============================================
// FUNÇÃO GLOBAL PARA LIMPAR BACKDROP
// =============================================
function limparBackdrop() {
    const backdrops = document.querySelectorAll('.modal-backdrop');

    if (backdrops.length > 0) {
        backdrops.forEach(backdrop => backdrop.remove());
    }

    if (document.body.classList.contains('modal-open')) {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Forçar reflow
    document.body.offsetHeight;
}

// Função para limpar backdrop apenas quando necessário
function limparBackdropSeNecessario() {
    // Só limpa se não houver modal aberto
    const modaisAbertos = document.querySelectorAll('.modal.show');
    if (modaisAbertos.length === 0) {
        limparBackdrop();
    }
}

// If the global manager exists, prefer its periodic cleanup (it runs when needed).
// Otherwise keep a conservative interval to catch legacy orphan backdrops.
if (!(window.NixcomModalManager && typeof window.NixcomModalManager.cleanOrphans === 'function')) {
    setInterval(limparBackdropSeNecessario, 8000);
}