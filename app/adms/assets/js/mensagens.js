/**
 * Sistema de Mensagens - JavaScript
 * Gerencia mensagens diretas e formulário de contato
 */

console.log('📧 Sistema de Mensagens carregado');

// Variáveis globais
let mensagensData = [];
let contadorInterval = null;

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('📧 Inicializando sistema de mensagens...');
    
    // Inicializar sistema para todos os usuários
    initMessagesSystem();
    initUserContact();
});

/**
 * Inicializa sistema de mensagens para todos os usuários
 */
function initMessagesSystem() {
    // Carregar contador inicial
    loadContador();
    
    // Configurar evento do botão de mensagens
    const mensagensBtn = document.getElementById('mensagensBtn');
    if (mensagensBtn) {
        mensagensBtn.addEventListener('click', function() {
            loadMensagens();
        });
    }
    
    // Carregar contador a cada 30 segundos
    contadorInterval = setInterval(loadContador, 30000);
    
    // Configurar botão "Marcar Todas como Lidas"
    const marcarTodasBtn = document.getElementById('marcarTodasLidasBtn');
    if (marcarTodasBtn) {
        marcarTodasBtn.addEventListener('click', marcarTodasComoLidas);
    }
}

/**
 * Inicializa sistema de mensagens para administradores (mantido para compatibilidade)
 */
function initAdminMessages() {
    // Carregar contador inicial
    loadContador();
    
    // Configurar evento do botão de mensagens
    const mensagensBtn = document.getElementById('mensagensBtn');
    if (mensagensBtn) {
        mensagensBtn.addEventListener('click', function() {
            loadMensagens();
        });
    }
    
    // Carregar contador a cada 30 segundos
    contadorInterval = setInterval(loadContador, 30000);
}

/**
 * Inicializa modal de contato para usuários normais
 */
function initUserContact() {
    const contatoEnviarBtn = document.getElementById('contatoEnviarBtn');
    if (contatoEnviarBtn) {
        contatoEnviarBtn.addEventListener('click', function() {
            enviarMensagemDireta();
        });
    }
    
    // Configurar contador de caracteres
    const contatoMensagem = document.getElementById('contatoMensagem');
    const contatoCharCount = document.getElementById('contatoCharCount');
    
    if (contatoMensagem && contatoCharCount) {
        contatoMensagem.addEventListener('input', function() {
            const length = this.value.length;
            contatoCharCount.textContent = length;
            
            // Mudar cor baseada no limite
            if (length > 400) {
                contatoCharCount.style.color = '#dc3545';
            } else if (length > 300) {
                contatoCharCount.style.color = '#ffc107';
            } else {
                contatoCharCount.style.color = '#6c757d';
            }
        });
    }
    
    // Configurar animações dos inputs
    const contatoInputs = document.querySelectorAll('.contato-input, .contato-textarea');
    contatoInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
}

/**
 * Carrega contador de mensagens não lidas
 */
async function loadContador() {
    try {
        const response = await fetch(`${window.URLADM}contato/getContador`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        // Verificar se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON (loadContador):', text);
            return;
        }

        const data = await response.json();
        
        if (data.success) {
            const badge = document.getElementById('mensagensCount');
            if (badge) {
                if (data.total > 0) {
                    badge.textContent = data.total;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.error('Erro ao carregar contador:', error);
    }
}

/**
 * Carrega mensagens para o modal do administrador
 */
async function loadMensagens() {
    const container = document.getElementById('mensagensContainer');
    if (!container) return;

    try {
        container.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando mensagens...</span>
                </div>
            </div>
        `;

        const response = await fetch(`${window.URLADM}contato/getMensagens`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        // Verificar se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON (loadMensagens):', text);
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
            mensagensData = data.mensagens;
            window.userType = data.user_type || 'user';
            renderMensagens(data.mensagens);
            
            // Mostrar botão "Marcar Todas como Lidas" para administradores
            const marcarTodasBtn = document.getElementById('marcarTodasLidasBtn');
            if (marcarTodasBtn && window.userType === 'admin') {
                marcarTodasBtn.style.display = 'inline-block';
            }
        } else {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.message}
                </div>
            `;
        }
    } catch (error) {
        console.error('Erro ao carregar mensagens:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Erro ao carregar mensagens. Tente novamente.
            </div>
        `;
    }
}

/**
 * Renderiza as mensagens no modal
 */
function renderMensagens(mensagens) {
    const container = document.getElementById('mensagensContainer');
    if (!container) return;

    if (mensagens.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nenhuma mensagem encontrada</p>
            </div>
        `;
        return;
    }

    let html = '';
    mensagens.forEach(mensagem => {
        console.log('DEBUG: Mensagem original:', mensagem.created_at);
        console.log('DEBUG: Mensagem completa:', mensagem);
        const email = mensagem.email || mensagem.user_email || mensagem.contato_email || mensagem.remetente_email || '';
        
        // Formatação da data com fallback
        let dataFormatada = 'Data inválida';
        try {
            const data = new Date(mensagem.created_at);
            if (!isNaN(data.getTime())) {
                // Formatação brasileira completa
                dataFormatada = data.toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
            } else {
                // Fallback se a data for inválida
                dataFormatada = mensagem.created_at || 'Data não disponível';
            }
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            dataFormatada = mensagem.created_at || 'Data não disponível';
        }
        
        console.log('DEBUG: Data formatada:', dataFormatada);
        const tipoIcon = mensagem.tipo === 'direta' ? 'fa-user' : 'fa-globe';
        const tipoBadge = mensagem.tipo === 'direta' ? 'bg-primary' : 'bg-success';
        const tipoTexto = mensagem.tipo === 'direta' ? 'Mensagem Direta' : 'Formulário de Contato';
        
        // Verificar se é resposta para usuário normal
        const isResposta = mensagem.resposta && mensagem.tipo === 'direta';
        const isNovaResposta = isResposta && mensagem.lida == 0;
        const isMensagemPendente = !mensagem.resposta && mensagem.tipo === 'direta';
        const podeResponder = mensagem.tipo === 'direta' && !mensagem.resposta && window.userType === 'admin';
        
        html += `
            <div class="card mb-3 ${mensagem.lida == 0 ? 'border-warning' : ''}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas ${tipoIcon} me-2"></i>
                        <strong>${mensagem.user_name}</strong>
                        ${isResposta ? '<span class="badge bg-info ms-2">Resposta do Admin</span>' : ''}
                        ${isMensagemPendente ? '<span class="badge bg-warning ms-2">Aguardando Resposta</span>' : ''}
                    </div>
                    <div class="d-flex align-items-center">
                        ${mensagem.lida == 0 ? '<span class="badge bg-warning">Nova</span>' : ''}
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="card-title mb-2"><i class="fas fa-tag me-2 text-primary"></i>${mensagem.assunto}</h6>
                    <div class="d-flex flex-wrap gap-2 small text-muted mb-2">
                        <span class="badge bg-light text-dark"><i class="fas fa-user me-1"></i>${mensagem.user_name}</span>
                        ${email ? `<span class="badge bg-light text-dark"><i class=\"fas fa-envelope me-1\"></i>${email}</span>` : ''}
                        <span class="badge bg-light text-dark"><i class="fas fa-clock me-1"></i>${dataFormatada}</span>
                        <span class="badge bg-light text-dark"><i class="fas fa-info-circle me-1"></i>${tipoTexto}</span>
                    </div>
                    <div class="mensagem-texto-formatado" style="text-align: justify; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff; margin: 10px 0;">${mensagem.mensagem}</div>
                    
                    ${mensagem.resposta ? `
                        <div class="alert alert-info mt-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><i class="fas fa-reply me-1"></i>Resposta do Administrador:</strong>
                                    <div class="resposta-texto-formatado" style="text-align: justify; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; padding: 12px; background: #e3f2fd; border-radius: 6px; border-left: 3px solid #2196f3; margin: 8px 0; font-size: 0.95rem;">${mensagem.resposta}</div>
                                </div>
                                ${mensagem.responded_at ? `
                                    <small class="text-muted" style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">
                                        <i class="fas fa-clock me-1"></i>${formatarDataResposta(mensagem.responded_at)}
                                    </small>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="d-flex gap-2 mt-3">
                        ${mensagem.lida == 0 ? `
                            <button class="btn btn-sm btn-outline-success" onclick="marcarComoLida(${mensagem.id}, '${mensagem.tipo}')">
                                <i class="fas fa-check me-1"></i>Marcar como Lida
                            </button>
                        ` : ''}
                        
                        ${podeResponder ? `
                            <button class="btn btn-sm btn-primary" onclick="toggleFormularioResposta(${mensagem.id})">
                                <i class="fas fa-reply me-1"></i>Responder
                            </button>
                        ` : ''}
                        
                        ${isMensagemPendente && window.userType === 'user' ? `
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-clock me-1"></i>Aguardando resposta do administrador
                            </span>
                        ` : ''}
                    </div>
                    
                    <!-- Formulário de Resposta Inline -->
                    <div id="formularioResposta_${mensagem.id}" class="mt-3" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-reply me-2"></i>Responder Mensagem</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Para:</label>
                                    <p class="form-control-plaintext bg-light p-2 rounded mb-0">${mensagem.user_name}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Assunto:</label>
                                    <p class="form-control-plaintext bg-light p-2 rounded mb-0">${mensagem.assunto}</p>
                                </div>
                                <div class="mb-3">
                                    <label for="respostaText_${mensagem.id}" class="form-label fw-bold">Sua Resposta:</label>
                                    <textarea class="form-control" id="respostaText_${mensagem.id}" rows="3" placeholder="Digite sua resposta aqui..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success btn-sm" onclick="enviarRespostaInline(${mensagem.id})">
                                        <i class="fas fa-paper-plane me-1"></i>Enviar Resposta
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="toggleFormularioResposta(${mensagem.id})">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Alterna formulário de resposta inline
 */
function toggleFormularioResposta(id) {
    const formulario = document.getElementById(`formularioResposta_${id}`);
    if (!formulario) return;
    
    if (formulario.style.display === 'none') {
        formulario.style.display = 'block';
        setTimeout(() => {
            const textarea = document.getElementById(`respostaText_${id}`);
            if (textarea) textarea.focus();
        }, 100);
    } else {
        formulario.style.display = 'none';
        const textarea = document.getElementById(`respostaText_${id}`);
        if (textarea) textarea.value = '';
    }
}

/**
 * Envia resposta inline
 */
async function enviarRespostaInline(id) {
    const textarea = document.getElementById(`respostaText_${id}`);
    const resposta = textarea ? textarea.value.trim() : '';
    
    if (!resposta) {
        showSuccessModal('Resposta obrigatória', 'Por favor, digite uma resposta.', 'error');
        return;
    }
    
    toggleFormularioResposta(id);
    await enviarResposta(id, resposta);
}

/**
 * Marca todas as mensagens como lidas
 */
async function marcarTodasComoLidas() {
    try {
        const response = await fetch(`${window.URLADM}contato/marcarTodasComoLidas`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        const data = await response.json();
        
        if (data.success) {
            showSuccessModal('Sucesso!', 'Todas as mensagens foram marcadas como lidas.', 'success');
            loadMensagens(); // Recarregar mensagens
            loadContador(); // Atualizar contador
        } else {
            showSuccessModal('Erro!', data.message || 'Erro ao marcar mensagens como lidas.', 'error');
        }
    } catch (error) {
        console.error('Erro ao marcar todas como lidas:', error);
        showSuccessModal('Erro!', 'Erro de conexão. Tente novamente.', 'error');
    }
}

/**
 * Marca mensagem como lida
 */
async function marcarComoLida(id, tipo) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('tipo', tipo);

        const response = await fetch(`${window.URLADM}contato/marcarLida`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        const data = await response.json();
        
        if (data.success) {
            // Recarregar mensagens
            loadMensagens();
            // Atualizar contador
            loadContador();
        } else {
            alert('Erro ao marcar como lida: ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao marcar como lida:', error);
        alert('Erro ao marcar como lida');
    }
}

/**
 * Mostra formulário de resposta
 */
function mostrarFormularioResposta(id) {
    const mensagem = mensagensData.find(m => m.id == id);
    if (!mensagem) return;

    // Mostrar modal de resposta personalizado
    showRespostaModal(mensagem, id);
}

/**
 * Envia resposta para mensagem direta
 */
async function enviarResposta(id, resposta) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('resposta', resposta);

        const response = await fetch(`${window.URLADM}contato/responderMensagem`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        const data = await response.json();
        console.log('DEBUG: Resposta do servidor:', data);
        
        if (data.success) {
            console.log('DEBUG: Mostrando modal de sucesso');
            const agora = new Date();
            const dataFormatada = agora.toLocaleString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit', 
                minute: '2-digit' 
            });
            showSuccessModal('Resposta enviada com sucesso!', `O usuário será notificado da sua resposta.<br><small class="text-muted">Respondido em: ${dataFormatada}</small>`, 'success');
            loadMensagens();
        } else {
            console.log('DEBUG: Mostrando modal de erro');
            showSuccessModal('Erro ao enviar resposta', data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao enviar resposta:', error);
        showSuccessModal('Erro ao enviar resposta', 'Ocorreu um erro inesperado. Tente novamente.', 'error');
    }
}

/**
 * Envia mensagem direta do usuário normal
 */
async function enviarMensagemDireta() {
    const assunto = document.getElementById('contatoAssunto').value.trim();
    const mensagem = document.getElementById('contatoMensagem').value.trim();

    if (!assunto || !mensagem) {
        showSuccessModal('Campos obrigatórios', 'Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
    }

    // Validação adicional
    if (assunto.length < 3) {
        showSuccessModal('Assunto muito curto', 'O assunto deve ter pelo menos 3 caracteres.', 'error');
        return;
    }

    if (mensagem.length < 10) {
        showSuccessModal('Mensagem muito curta', 'A mensagem deve ter pelo menos 10 caracteres.', 'error');
        return;
    }

    if (mensagem.length > 500) {
        showSuccessModal('Mensagem muito longa', 'A mensagem deve ter no máximo 500 caracteres.', 'error');
        return;
    }

    const btnEnviar = document.getElementById('contatoEnviarBtn');
    const originalText = btnEnviar.innerHTML;
    
    try {
        // Adicionar classe de loading
        btnEnviar.classList.add('loading');
        btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
        btnEnviar.disabled = true;

        const formData = new FormData();
        formData.append('assunto', assunto);
        formData.append('mensagem', mensagem);

        const response = await fetch(`${window.URLADM}contato/enviarMensagemDireta`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        // Verificar se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            alert('Erro: Servidor retornou resposta inválida. Verifique o console para detalhes.');
            return;
        }

        const data = await response.json();
        
        if (data.success) {
            // Mostrar modal de sucesso personalizado
            showSuccessModal('Mensagem enviada com sucesso!', 'O administrador será notificado e responderá em breve.', 'success');
            // Limpar formulário
            document.getElementById('contatoForm').reset();
            // Resetar contador de caracteres
            const contatoCharCount = document.getElementById('contatoCharCount');
            if (contatoCharCount) {
                contatoCharCount.textContent = '0';
                contatoCharCount.style.color = '#6c757d';
            }
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('contatoModal'));
            if (modal) modal.hide();
        } else {
            showSuccessModal('Erro ao enviar mensagem', data.message, 'error');
        }
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
        showSuccessModal('Erro ao enviar mensagem', 'Ocorreu um erro inesperado. Tente novamente.', 'error');
    } finally {
        // Restaurar botão
        btnEnviar.classList.remove('loading');
        btnEnviar.innerHTML = originalText;
        btnEnviar.disabled = false;
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
 * Formata data da resposta do administrador
 */
function formatarDataResposta(dataString) {
    const data = new Date(dataString);
    const agora = new Date();
    const diffMs = agora - data;
    const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const diffHoras = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMinutos = Math.floor(diffMs / (1000 * 60));
    
    if (diffMinutos < 1) {
        return 'Agora mesmo';
    } else if (diffMinutos < 60) {
        return `Há ${diffMinutos} min`;
    } else if (diffHoras < 24) {
        return `Há ${diffHoras}h`;
    } else if (diffDias === 1) {
        return 'Ontem ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDias < 7) {
        return `${diffDias} dias atrás`;
    } else {
        return data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' });
    }
}

/**
 * Mostra modal de resposta personalizado
 */
function showRespostaModal(mensagem, id) {
    const modalHtml = `
        <div class="modal fade" id="respostaModal" tabindex="-1" aria-labelledby="respostaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="border-radius: 15px 15px 0 0; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none;">
                        <h5 class="modal-title" id="respostaModalLabel" style="font-weight: 600;">
                            <i class="fas fa-reply me-2"></i>Responder Mensagem
                        </h5>
                    </div>
                    <div class="modal-body" style="padding: 25px;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Para:</label>
                            <p class="form-control-plaintext bg-light p-2 rounded">${mensagem.user_name}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assunto:</label>
                            <p class="form-control-plaintext bg-light p-2 rounded">${mensagem.assunto}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mensagem Original:</label>
                            <p class="form-control-plaintext bg-light p-3 rounded" style="min-height: 80px;">${mensagem.mensagem}</p>
                        </div>
                        <div class="mb-3">
                            <label for="respostaText" class="form-label fw-bold">Sua Resposta:</label>
                            <textarea class="form-control" id="respostaText" rows="4" placeholder="Digite sua resposta aqui..." style="border-radius: 10px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-radius: 0 0 15px 15px; border: none; padding: 20px 25px; background: #f8f9fa;">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" style="border-radius: 25px; padding: 10px 25px; font-weight: 500;">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-success" id="enviarRespostaBtn" style="border-radius: 25px; padding: 10px 25px; font-weight: 500;">
                            <i class="fas fa-paper-plane me-1"></i>Enviar Resposta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const existingModal = document.getElementById('respostaModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('respostaModal'));
    modal.show();
    
    // Configurar evento de envio
    document.getElementById('enviarRespostaBtn').addEventListener('click', function() {
        const resposta = document.getElementById('respostaText').value.trim();
        if (resposta) {
            enviarResposta(id, resposta);
            modal.hide();
        } else {
            showSuccessModal('Resposta obrigatória', 'Por favor, digite uma resposta.', 'error');
        }
    });
}

/**
 * Mostra modal de sucesso personalizado
 */
function showSuccessModal(title, message, type = 'success') {
    console.log('DEBUG: showSuccessModal chamado - Título:', title, 'Mensagem:', message, 'Tipo:', type);
    const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
    const bgClass = type === 'success' ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)';
    
    const modalHtml = `
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="border-radius: 15px 15px 0 0; background: ${bgClass}; color: white; border: none;">
                        <h5 class="modal-title" id="successModalLabel" style="font-weight: 600;">
                            <i class="fas ${iconClass} me-2"></i>${title}
                        </h5>
                    </div>
                            <div class="modal-body" style="padding: 25px; font-size: 16px; line-height: 1.6; text-align: center;">
                                <div class="mb-0">${message}</div>
                            </div>
                    <div class="modal-footer" style="border-radius: 0 0 15px 15px; border: none; padding: 20px 25px; background: #f8f9fa; justify-content: center;">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" style="border-radius: 25px; padding: 10px 30px; font-weight: 500;">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const existingModal = document.getElementById('successModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    console.log('DEBUG: Modal criado e sendo exibido');
    modal.show();
}

/**
 * Formata data para exibição
 */
function formatarData(dataString) {
    console.log('DEBUG formatarData: Input:', dataString);
    
    if (!dataString) return 'Data não disponível';
    
    try {
        const data = new Date(dataString);
        console.log('DEBUG formatarData: Data criada:', data);
        console.log('DEBUG formatarData: isNaN:', isNaN(data.getTime()));
        
        // Verificar se a data é válida
        if (isNaN(data.getTime())) {
            console.log('DEBUG formatarData: Data inválida');
            return 'Data inválida';
        }
        
        // Sempre mostrar data e hora completa
        const resultado = data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        
        console.log('DEBUG formatarData: Resultado:', resultado);
        return resultado;
        
    } catch (error) {
        console.error('Erro ao formatar data:', error);
        return 'Data inválida';
    }
}

/**
 * Formata data de resposta
 */
function formatarDataResposta(dataString) {
    if (!dataString) return 'Data não disponível';
    
    try {
        const data = new Date(dataString);
        
        if (isNaN(data.getTime())) {
            return 'Data inválida';
        }
        
        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        
    } catch (error) {
        console.error('Erro ao formatar data de resposta:', error);
        return 'Data inválida';
    }
}

// Limpar interval quando a página for descarregada
window.addEventListener('beforeunload', function() {
    if (contadorInterval) {
        clearInterval(contadorInterval);
    }
});
