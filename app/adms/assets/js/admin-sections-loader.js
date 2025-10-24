/**
 * Carregador de estilos para seções administrativas
 * Garante que os estilos sejam aplicados corretamente no SPA
 */

class AdminSectionsLoader {
    constructor() {
        this.cssLoaded = false;
        this.init();
    }

    init() {
        // Aplicar estilos imediatamente
        this.forceApplyStyles();
        
        // Carregar CSS se não estiver carregado
        this.loadAdminCSS();
        
        // Aplicar estilos após carregamento
        this.applyStyles();
        
        // Observar mudanças no DOM para aplicar estilos em conteúdo dinâmico
        this.observeDOM();
    }

    loadAdminCSS() {
        if (this.cssLoaded) return;
        
        const cssUrl = window.URLADM + 'assets/css/admin-sections.css?' + window.CACHE_BUSTER;
        
        // Verificar se o CSS já está carregado
        const existingLink = document.querySelector(`link[href*="admin-sections.css"]`);
        if (existingLink) {
            this.cssLoaded = true;
            console.log('INFO AdminSectionsLoader: CSS já carregado');
            return;
        }

        // Carregar CSS dinamicamente
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = cssUrl;
        link.type = 'text/css';
        link.onload = () => {
            console.log('INFO AdminSectionsLoader: CSS carregado com sucesso');
            this.cssLoaded = true;
            this.applyStyles();
        };
        link.onerror = () => {
            console.error('ERRO AdminSectionsLoader: Falha ao carregar CSS');
        };
        
        document.head.appendChild(link);
        console.log('INFO AdminSectionsLoader: Adicionando CSS ao head:', cssUrl);
    }

    forceApplyStyles() {
        // Forçar aplicação imediata de estilos inline
        console.log('INFO AdminSectionsLoader: Forçando aplicação de estilos...');
        
        // Aguardar um pouco para garantir que o DOM esteja carregado
        setTimeout(() => {
            this.applyInlineStyles();
        }, 50);
    }

    applyInlineStyles() {
        // Aplicar estilos para admin-section
        const adminSection = document.querySelector('.admin-section');
        if (adminSection && !adminSection.style.background) {
            adminSection.style.background = 'linear-gradient(135deg, #2c3e50 0%, #34495e 100%)';
            adminSection.style.borderRadius = '20px';
            adminSection.style.padding = '30px';
            adminSection.style.marginBottom = '25px';
            adminSection.style.border = 'none';
            adminSection.style.boxShadow = '0 10px 40px rgba(44, 62, 80, 0.3)';
            console.log('INFO AdminSectionsLoader: Estilos aplicados ao admin-section');
        }

        // Aplicar estilos para sidebar-sections
        const sidebarSections = document.querySelectorAll('.sidebar-section');
        sidebarSections.forEach((section, index) => {
            const h4 = section.querySelector('h4');
            if (h4 && !section.style.background) {
                if (h4.textContent.includes('Informações do Plano')) {
                    section.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    section.style.borderRadius = '20px';
                    section.style.padding = '30px';
                    section.style.marginBottom = '25px';
                    section.style.border = 'none';
                    section.style.boxShadow = '0 8px 32px rgba(102, 126, 234, 0.4)';
                    section.style.transition = 'all 0.3s ease';
                    console.log('INFO AdminSectionsLoader: Estilos aplicados à seção de Informações do Plano');
                } else if (h4.textContent.includes('Dados Administrativos')) {
                    section.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
                    section.style.borderRadius = '20px';
                    section.style.padding = '30px';
                    section.style.marginBottom = '25px';
                    section.style.border = 'none';
                    section.style.boxShadow = '0 8px 32px rgba(231, 76, 60, 0.4)';
                    section.style.transition = 'all 0.3s ease';
                    console.log('INFO AdminSectionsLoader: Estilos aplicados à seção de Dados Administrativos');
                }
            }
        });

        // Aplicar estilos para admin-info
        const adminInfo = document.querySelector('.admin-info');
        if (adminInfo && !adminInfo.style.background) {
            adminInfo.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            adminInfo.style.borderRadius = '15px';
            adminInfo.style.padding = '25px';
            adminInfo.style.marginBottom = '20px';
            adminInfo.style.border = 'none';
            adminInfo.style.boxShadow = '0 8px 32px rgba(102, 126, 234, 0.3)';
            console.log('INFO AdminSectionsLoader: Estilos aplicados ao admin-info');
        }

        // Aplicar estilos para info-cards
        const infoCards = document.querySelectorAll('.info-card');
        infoCards.forEach((card, index) => {
            if (!card.style.background) {
                card.style.textAlign = 'center';
                card.style.padding = '20px';
                card.style.borderRadius = '15px';
                card.style.border = 'none';
                card.style.transition = 'all 0.3s ease';
                card.style.transform = 'translateY(0)';
                
                switch(index) {
                    case 0: // Data de Criação
                        card.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                        card.style.boxShadow = '0 6px 20px rgba(40, 167, 69, 0.4)';
                        break;
                    case 1: // Última Modificação
                        card.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
                        card.style.boxShadow = '0 6px 20px rgba(255, 193, 7, 0.4)';
                        break;
                    case 2: // IP de Registro
                        card.style.background = 'linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%)';
                        card.style.boxShadow = '0 6px 20px rgba(23, 162, 184, 0.4)';
                        break;
                }
                console.log(`INFO AdminSectionsLoader: Estilos aplicados ao info-card ${index + 1}`);
            }
        });
    }

    applyStyles() {
        // Aplicar animação de entrada
        this.animateElements();
    }


    animateElements() {
        const elements = document.querySelectorAll('.admin-section, .admin-info, .info-card');
        
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.5s ease-out';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    observeDOM() {
        // Observar mudanças no DOM para aplicar estilos em conteúdo dinâmico
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            const adminElements = node.querySelectorAll ? 
                                node.querySelectorAll('.admin-section, .admin-info, .info-card') : [];
                            
                            if (node.classList && node.classList.contains('admin-section')) {
                                adminElements.push(node);
                            }
                            
                            adminElements.forEach(element => {
                                this.enhanceElement(element);
                            });
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Função para aplicar estilos imediatamente
function forceApplyAdminStyles() {
    console.log('INFO AdminSectionsLoader: Aplicando estilos imediatamente...');
    
    // Aplicar estilos para admin-section
    const adminSection = document.querySelector('.admin-section');
    if (adminSection) {
        adminSection.style.background = 'linear-gradient(135deg, #2c3e50 0%, #34495e 100%)';
        adminSection.style.borderRadius = '20px';
        adminSection.style.padding = '30px';
        adminSection.style.marginBottom = '25px';
        adminSection.style.border = 'none';
        adminSection.style.boxShadow = '0 10px 40px rgba(44, 62, 80, 0.3)';
        console.log('INFO AdminSectionsLoader: Estilos aplicados ao admin-section');
    }

    // Aplicar estilos para sidebar-sections
    const sidebarSections = document.querySelectorAll('.sidebar-section');
    sidebarSections.forEach((section, index) => {
        const h4 = section.querySelector('h4');
        if (h4) {
            if (h4.textContent.includes('Informações do Plano')) {
                section.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                section.style.borderRadius = '20px';
                section.style.padding = '30px';
                section.style.marginBottom = '25px';
                section.style.border = 'none';
                section.style.boxShadow = '0 8px 32px rgba(102, 126, 234, 0.4)';
                section.style.transition = 'all 0.3s ease';
                console.log('INFO AdminSectionsLoader: Estilos aplicados à seção de Informações do Plano');
            } else if (h4.textContent.includes('Dados Administrativos')) {
                section.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
                section.style.borderRadius = '20px';
                section.style.padding = '30px';
                section.style.marginBottom = '25px';
                section.style.border = 'none';
                section.style.boxShadow = '0 8px 32px rgba(231, 76, 60, 0.4)';
                section.style.transition = 'all 0.3s ease';
                console.log('INFO AdminSectionsLoader: Estilos aplicados à seção de Dados Administrativos');
            }
        }
    });

    // Aplicar estilos para admin-info
    const adminInfo = document.querySelector('.admin-info');
    if (adminInfo) {
        adminInfo.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        adminInfo.style.borderRadius = '15px';
        adminInfo.style.padding = '25px';
        adminInfo.style.marginBottom = '20px';
        adminInfo.style.border = 'none';
        adminInfo.style.boxShadow = '0 8px 32px rgba(102, 126, 234, 0.3)';
        console.log('INFO AdminSectionsLoader: Estilos aplicados ao admin-info');
    }

    // Aplicar estilos para info-cards
    const infoCards = document.querySelectorAll('.info-card');
    infoCards.forEach((card, index) => {
        card.style.textAlign = 'center';
        card.style.padding = '20px';
        card.style.borderRadius = '15px';
        card.style.border = 'none';
        card.style.transition = 'all 0.3s ease';
        card.style.transform = 'translateY(0)';
        
        switch(index) {
            case 0: // Data de Criação
                card.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                card.style.boxShadow = '0 6px 20px rgba(40, 167, 69, 0.4)';
                break;
            case 1: // Última Modificação
                card.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
                card.style.boxShadow = '0 6px 20px rgba(255, 193, 7, 0.4)';
                break;
            case 2: // IP de Registro
                card.style.background = 'linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%)';
                card.style.boxShadow = '0 6px 20px rgba(23, 162, 184, 0.4)';
                break;
        }
        console.log(`INFO AdminSectionsLoader: Estilos aplicados ao info-card ${index + 1}`);
    });
}

// Aplicar estilos imediatamente
forceApplyAdminStyles();

// Aplicar novamente após um pequeno delay
setTimeout(forceApplyAdminStyles, 100);
setTimeout(forceApplyAdminStyles, 500);

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    forceApplyAdminStyles();
    window.adminSectionsLoader = new AdminSectionsLoader();
});

// Aplicar estilos imediatamente se o DOM já estiver carregado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        forceApplyAdminStyles();
        window.adminSectionsLoader = new AdminSectionsLoader();
    });
} else {
    // DOM já carregado, aplicar imediatamente
    forceApplyAdminStyles();
    window.adminSectionsLoader = new AdminSectionsLoader();
}

// Reinicializar após carregamento de conteúdo SPA
if (window.SpaUtils && window.SpaUtils.loadContent) {
    const originalLoadContent = window.SpaUtils.loadContent;
    window.SpaUtils.loadContent = async function(...args) {
        const result = await originalLoadContent.apply(this, args);
        
        // Aplicar estilos após carregamento SPA
        setTimeout(() => {
            forceApplyAdminStyles();
            if (window.adminSectionsLoader) {
                window.adminSectionsLoader.applyStyles();
            }
        }, 100);
        
        return result;
    };
}




