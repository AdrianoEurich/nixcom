/**
 * Sistema de Notificações
 * Gerencia notificações do administrador (sino)
 */

let notificacoesData = [];
// let contadorInterval; // Removido para evitar conflito com mensagens.js

// Inicializar sistema de notificações
document.addEventListener('DOMContentLoaded', function() {
    initNotificacoesSystem();
});

/**
 * Inicializa o sistema de notificações
 */
function initNotificacoesSystem() {
    loadContadorNotificacoes();
    
    const notificacoesBtn = document.getElementById('notificacoesBtn');
    if (notificacoesBtn) {
        notificacoesBtn.addEventListener('click', function() {
            loadNotificacoes();
        });
    }
    
    // Atualizar contador a cada 30 segundos
    window.contadorIntervalNotificacoes = setInterval(loadContadorNotificacoes, 30000);
}

/**
 * Carrega notificações
 */
async function loadNotificacoes() {
    const container = document.getElementById('notificacoesContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">Carregando notificações...</span>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch(`${window.URLADM}notificacoes/getNotificacoes`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            credentials: 'include'
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON (loadNotificacoes):', text);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erro: Servidor retornou resposta inválida.
                </div>
            `;
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            notificacoesData = data.notificacoes || [];
            renderNotificacoes(notificacoesData);
        } else {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.message}
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Erro ao carregar notificações.
            </div>
        `;
    }
}

/**
 * Renderiza notificações
 */
function renderNotificacoes(notificacoes) {
    const container = document.getElementById('notificacoesContainer');
    if (!container) return;
    
    if (notificacoes.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhuma notificação</h5>
                <p class="text-muted">Você não possui notificações no momento.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    notificacoes.forEach(notificacao => {
        const dataFormatada = formatarData(notificacao.created_at);
        const tipoIcon = getTipoIcon(notificacao.tipo);
        const tipoColor = getTipoColor(notificacao.tipo);
        const tipoTexto = getTipoTexto(notificacao.tipo);
        
        html += `
            <div class="card mb-3 ${notificacao.lida == 0 ? 'border-warning' : ''}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas ${tipoIcon} me-2 text-${tipoColor}"></i>
                        <strong>${tipoTexto}</strong>
                        ${notificacao.lida == 0 ? '<span class="badge bg-warning ms-2">Nova</span>' : ''}
                    </div>
                    <div class="d-flex align-items-center">
                        <small class="text-muted me-2" style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 12px; font-weight: 500;">${dataFormatada}</small>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="card-title">${notificacao.titulo}</h6>
                    <p class="card-text">${notificacao.mensagem}</p>
                    
                    <div class="d-flex gap-2 mt-3">
                        ${notificacao.lida == 0 ? `
                            <button class="btn btn-sm btn-outline-success" onclick="marcarNotificacaoLida(${notificacao.id})">
                                <i class="fas fa-check me-1"></i>Marcar como Lida
                            </button>
                        ` : ''}
                        
                        ${getAcaoNotificacao(notificacao)}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

/**
 * Obtém ícone do tipo de notificação
 */
function getTipoIcon(tipo) {
    const icons = {
        'anuncio_pendente': 'fa-bullhorn',
        'comentario_pendente': 'fa-comment',
        'mensagem_direta': 'fa-envelope',
        'formulario_contato': 'fa-file-alt'
    };
    return icons[tipo] || 'fa-bell';
}

/**
 * Obtém cor do tipo de notificação
 */
function getTipoColor(tipo) {
    const colors = {
        'anuncio_pendente': 'primary',
        'comentario_pendente': 'info',
        'mensagem_direta': 'success',
        'formulario_contato': 'warning'
    };
    return colors[tipo] || 'secondary';
}

/**
 * Obtém texto do tipo de notificação
 */
function getTipoTexto(tipo) {
    const textos = {
        'anuncio_pendente': 'Anúncio Pendente',
        'comentario_pendente': 'Comentário Pendente',
        'mensagem_direta': 'Mensagem Direta',
        'formulario_contato': 'Formulário de Contato'
    };
    return textos[tipo] || 'Notificação';
}

/**
 * Obtém ação da notificação
 */
function getAcaoNotificacao(notificacao) {
    if (notificacao.tipo === 'anuncio_pendente' && notificacao.anuncio_id) {
        return `
            <button class="btn btn-sm btn-primary" onclick="visualizarAnuncio(${notificacao.anuncio_id})">
                <i class="fas fa-eye me-1"></i>Ver Anúncio
            </button>
        `;
    }
    
    if (notificacao.tipo === 'comentario_pendente' && notificacao.comentario_id) {
        return `
            <button class="btn btn-sm btn-info" onclick="visualizarComentario(${notificacao.comentario_id})">
                <i class="fas fa-comment me-1"></i>Ver Comentário
            </button>
        `;
    }
    
    return '';
}

/**
 * Marca notificação como lida
 */
async function marcarNotificacaoLida(id) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        
        const response = await fetch(`${window.URLADM}notificacoes/marcarLida`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadNotificacoes();
            loadContadorNotificacoes();
        } else {
            console.error('Erro ao marcar notificação como lida:', data.message);
        }
        
    } catch (error) {
        console.error('Erro ao marcar notificação como lida:', error);
    }
}

/**
 * Carrega contador de notificações
 */
async function loadContadorNotificacoes() {
    const badge = document.getElementById('notificacoesCount');
    if (!badge) return;
    
    try {
        const response = await fetch(`${window.URLADM}notificacoes/getContador`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            credentials: 'include'
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            const total = data.total || 0;
            if (total > 0) {
                badge.textContent = total;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        
    } catch (error) {
        console.error('Erro ao carregar contador de notificações:', error);
    }
}

/**
 * Formata data para exibição
 */
function formatarData(dataString) {
    const data = new Date(dataString);
    const agora = new Date();
    const diffMs = agora - data;
    const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffDias === 0) {
        return 'Hoje às ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDias === 1) {
        return 'Ontem às ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDias < 7) {
        return `${diffDias} dias atrás`;
    } else {
        return data.toLocaleDateString('pt-BR');
    }
}

/**
 * Visualiza anúncio
 */
function visualizarAnuncio(anuncioId) {
    window.location.href = `${window.URLADM}anuncio/visualizarAnuncio/${anuncioId}`;
}

/**
 * Visualiza comentário
 */
function visualizarComentario(comentarioId) {
    // Implementar visualização de comentário
    console.log('Visualizar comentário:', comentarioId);
}

// Limpar interval quando a página for descarregada
window.addEventListener('beforeunload', function() {
    if (window.contadorIntervalNotificacoes) {
        clearInterval(window.contadorIntervalNotificacoes);
    }
});
