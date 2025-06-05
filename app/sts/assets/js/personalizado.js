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

    // Adiciona ou remove a classe 'scrolled' com base no scroll da página
    window.addEventListener('scroll', function () {
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

        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;

            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    }

    // Configuração inicial dos elementos que terão animação
    document.querySelectorAll('.service-card, .highlight-item, .contact-form, .info-item, .cta-box').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'all 0.6s ease'; // Transição suave
    });

    // Adiciona event listener para o scroll e inicializa a animação ao carregar a página
    window.addEventListener('scroll', animateOnScroll);
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

        // Referências ao modal e seus elementos
        const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
        const feedbackModalLabel = document.getElementById('feedbackModalLabel');
        const feedbackMessage = document.getElementById('feedbackMessage');
        const feedbackIcon = document.getElementById('feedbackIcon');

        // Função para exibir o modal de feedback
        function showFeedbackModal(type, message) {
            feedbackModalLabel.textContent = type === 'success' ? 'Sucesso!' : 'Erro!';
            feedbackMessage.textContent = message;

            // Limpa classes de ícones anteriores
            feedbackIcon.classList.remove('fa-check-circle', 'fa-times-circle', 'text-success', 'text-danger');

            if (type === 'success') {
                feedbackIcon.classList.add('fas', 'fa-check-circle', 'text-success');
                feedbackIcon.style.fontSize = '3rem'; // Ajuste de tamanho do ícone
            } else {
                feedbackIcon.classList.add('fas', 'fa-times-circle', 'text-danger');
                feedbackIcon.style.fontSize = '3rem'; // Ajuste de tamanho do ícone
            }

            feedbackModal.show(); // Mostra o modal

            // Esconde o modal automaticamente após 4 segundos (4000ms)
            setTimeout(() => {
                feedbackModal.hide();
            }, 4000);
        }

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
    const sections = document.querySelectorAll('section');

    window.addEventListener('scroll', function () {
        let current = '';

        // Verifica a posição de cada seção para destacar o link correspondente
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
    });
});