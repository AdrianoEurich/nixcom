/**
 * Sistema de Atualização em Tempo Real
 * Verifica o status do anúncio a cada 5 segundos e atualiza a interface
 */
class RealTimeUpdater {
    constructor() {
        this.isRunning = false;
        this.intervalId = null;
        this.lastStatus = null;
        this.userId = null;
        this.isAdmin = false;
        
        this.init();
    }

    init() {
        // Verificar se é administrador
        this.isAdmin = this.checkIfAdmin();
        
        // Se for admin, não iniciar o sistema
        if (this.isAdmin) {
            console.log('INFO RT: Usuário é administrador. Sistema de atualização em tempo real desabilitado.');
            return;
        }

        // Obter ID do usuário
        this.userId = this.getUserId();
        if (!this.userId) {
            console.log('INFO RT: Usuário não logado. Sistema de atualização em tempo real desabilitado.');
            return;
        }

        // Iniciar sistema
        this.start();
    }

    checkIfAdmin() {
        // Verificar se é administrador através de elementos da página
        const adminElements = document.querySelectorAll('[data-admin="true"], .admin-only, .administrator');
        return adminElements.length > 0 || 
               document.body.classList.contains('admin-page') ||
               window.location.pathname.includes('/admin/');
    }

    getUserId() {
        // Tentar obter ID do usuário de diferentes formas
        const userIdElement = document.querySelector('[data-user-id]');
        if (userIdElement) {
            return userIdElement.getAttribute('data-user-id');
        }

        // Verificar se existe uma variável global
        if (window.userId) {
            return window.userId;
        }

        // Verificar se existe nos dados da página
        if (window.pageData && window.pageData.user_id) {
            return window.pageData.user_id;
        }

        return null;
    }

    start() {
        if (this.isRunning) {
            console.log('INFO RT: Sistema já está rodando.');
            return;
        }

        console.log('INFO RT: Iniciando sistema de atualização em tempo real...');
        this.isRunning = true;
        
        // Verificação imediata
        this.checkStatus();
        
        // Verificação a cada 5 segundos
        this.intervalId = setInterval(() => {
            this.checkStatus();
        }, 5000);
    }

    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        this.isRunning = false;
        console.log('INFO RT: Sistema de atualização em tempo real parado.');
    }

    async checkStatus() {
        try {
            const response = await fetch('/nixcom/app/adms/anuncio/statusCheck', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                console.warn('AVISO RT: Erro na resposta do servidor:', response.status);
                return;
            }

            const data = await response.json();
            
            if (data.success) {
                this.processStatusUpdate(data);
            } else {
                console.warn('AVISO RT: Resposta de erro do servidor:', data.message);
            }

        } catch (error) {
            console.error('ERRO RT: Erro ao verificar status:', error);
        }
    }

    processStatusUpdate(data) {
        const currentStatus = data.anuncio_status;
        const hasAnuncio = data.has_anuncio;
        
        // Verificar se houve mudança
        if (this.lastStatus === null) {
            this.lastStatus = { status: currentStatus, has_anuncio: hasAnuncio };
            console.log('INFO RT: Status inicial definido:', this.lastStatus);
            return;
        }

        const statusChanged = this.lastStatus.status !== currentStatus || 
                             this.lastStatus.has_anuncio !== hasAnuncio;

        if (statusChanged) {
            console.log('INFO RT: Status mudou! Anterior:', this.lastStatus, 'Atual:', { status: currentStatus, has_anuncio: hasAnuncio });
            
            // Atualizar sidebar se a função existir
            if (typeof window.updateAnuncioSidebarLinks === 'function') {
                try {
                    window.updateAnuncioSidebarLinks();
                    console.log('INFO RT: Sidebar atualizada com sucesso.');
                } catch (error) {
                    console.error('ERRO RT: Erro ao atualizar sidebar:', error);
                }
            }

            // Mostrar notificação se a função existir
            if (typeof window.showFeedbackModal === 'function') {
                try {
                    const statusText = this.getStatusText(currentStatus, hasAnuncio);
                    window.showFeedbackModal('success', `Status do anúncio atualizado: ${statusText}`, 'Atualização em Tempo Real');
                } catch (error) {
                    console.error('ERRO RT: Erro ao mostrar notificação:', error);
                }
            }

            // Atualizar status anterior
            this.lastStatus = { status: currentStatus, has_anuncio: hasAnuncio };
        }
    }

    getStatusText(status, hasAnuncio) {
        if (!hasAnuncio) {
            return 'Sem anúncio ativo';
        }
        
        switch (status) {
            case 'active':
                return 'Anúncio ativo';
            case 'paused':
                return 'Anúncio pausado';
            case 'pending':
                return 'Anúncio pendente';
            case 'rejected':
                return 'Anúncio rejeitado';
            default:
                return `Status: ${status}`;
        }
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar um pouco para garantir que outros scripts carregaram
    setTimeout(() => {
        window.realTimeUpdater = new RealTimeUpdater();
    }, 1000);
});

// Limpar quando a página for descarregada
window.addEventListener('beforeunload', function() {
    if (window.realTimeUpdater) {
        window.realTimeUpdater.stop();
    }
});

