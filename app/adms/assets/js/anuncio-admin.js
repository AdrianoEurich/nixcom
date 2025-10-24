// anuncio-admin.js - Módulo para funcionalidades de administrador
console.log('👑 ANÚNCIO ADMIN carregado');

/**
 * Habilita ou desabilita um botão baseado no estado
 * @param {HTMLElement} button - O elemento do botão
 * @param {boolean} enabled - Se o botão deve estar habilitado
 */
function toggleButtonState(button, enabled) {
    if (!button) return;
    
    if (enabled) {
        button.disabled = false;
        button.classList.remove('disabled');
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
    } else {
        button.disabled = true;
        button.classList.add('disabled');
        button.style.opacity = '0.5';
        button.style.cursor = 'not-allowed';
    }
}

/**
 * Configura os event listeners para os botões de ação do administrador (Aprovar, Reprovar, Excluir, Ativar, Pausar, Visualizar, Excluir Conta).
 * @param {string} anuncioId O ID do anúncio.
 * @param {string} anuncianteUserId O ID do usuário anunciante.
 * @param {string} currentAnuncioStatus O status atual do anúncio (ex: 'pending', 'approved', 'active', 'pausado', 'rejected').
 */
function setupAdminActionButtons(anuncioId, anuncianteUserId, currentAnuncioStatus) {
    console.log('INFO JS: setupAdminActionButtons - Configurando botões de ação do administrador.');
    console.log(`DEBUG JS: setupAdminActionButtons - Anúncio ID: ${anuncioId}, Anunciante User ID: ${anuncianteUserId}, Status: ${currentAnuncioStatus}`);

    const btnApprove = document.getElementById('btnApproveAnuncio');
    const btnReject = document.getElementById('btnRejectAnuncio');
    const btnDelete = document.getElementById('btnDeleteAnuncio');
    const btnActivate = document.getElementById('btnActivateAnuncio');
    const btnDeactivate = document.getElementById('btnDeactivateAnuncio');
    const btnVisualizar = document.getElementById('btnVisualizarAnuncio');
    const btnDeleteAccount = document.getElementById('btnDeleteAccount');

    // Remove listeners antigos para evitar duplicação em navegações SPA
    if (btnApprove && btnApprove._clickHandler) btnApprove.removeEventListener('click', btnApprove._clickHandler);
    if (btnReject && btnReject._clickHandler) btnReject.removeEventListener('click', btnReject._clickHandler);
    if (btnDelete && btnDelete._clickHandler) btnDelete.removeEventListener('click', btnDelete._clickHandler);
    if (btnActivate && btnActivate._clickHandler) btnActivate.removeEventListener('click', btnActivate._clickHandler);
    if (btnDeactivate && btnDeactivate._clickHandler) btnDeactivate.removeEventListener('click', btnDeactivate._clickHandler);
    if (btnVisualizar && btnVisualizar._clickHandler) btnVisualizar.removeEventListener('click', btnVisualizar._clickHandler);
    if (btnDeleteAccount && btnDeleteAccount._clickHandler) btnDeleteAccount.removeEventListener('click', btnDeleteAccount._clickHandler);

    // Lógica para habilitar/desabilitar e adicionar listeners
    if (btnApprove) {
        const canApprove = currentAnuncioStatus === 'pending' || currentAnuncioStatus === 'rejected';
        toggleButtonState(btnApprove, canApprove);
        if (canApprove) {
            const handler = function() {
                window.showConfirmModal(
                    'Tem certeza que deseja APROVAR este anúncio? Ele ficará visível publicamente.',
                    'Aprovar Anúncio'
                ).then(result => {
                    if (result) {
                        performAdminAction('approve', anuncioId, anuncianteUserId);
                    }
                });
            };
            btnApprove.addEventListener('click', handler);
            btnApprove._clickHandler = handler;
        }
    }

    if (btnReject) {
        const canReject = currentAnuncioStatus === 'pending' || currentAnuncioStatus === 'approved' || currentAnuncioStatus === 'active';
        toggleButtonState(btnReject, canReject);
        if (canReject) {
            const handler = function() {
                window.showConfirmModal(
                    'Tem certeza que deseja REPROVAR este anúncio? Ele não ficará visível publicamente.',
                    'Reprovar Anúncio'
                ).then(result => {
                    if (result) {
                        performAdminAction('reject', anuncioId, anuncianteUserId);
                    }
                });
            };
            btnReject.addEventListener('click', handler);
            btnReject._clickHandler = handler;
        }
    }

    if (btnActivate) {
        const canActivate = currentAnuncioStatus === 'pausado';
        toggleButtonState(btnActivate, canActivate);
        if (canActivate) {
            const handler = function() {
                window.showConfirmModal(
                    'Tem certeza que deseja ATIVAR este anúncio? Ele ficará visível publicamente.',
                    'Ativar Anúncio'
                ).then(result => {
                    if (result) {
                        performAdminAction('activate', anuncioId, anuncianteUserId);
                    }
                });
            };
            btnActivate.addEventListener('click', handler);
            btnActivate._clickHandler = handler;
        }
    }

    if (btnDeactivate) {
        const canDeactivate = currentAnuncioStatus === 'active';
        toggleButtonState(btnDeactivate, canDeactivate);
        if (canDeactivate) {
            const handler = function() {
                window.showConfirmModal(
                    'Tem certeza que deseja PAUSAR este anúncio? Ele não ficará visível publicamente.',
                    'Pausar Anúncio'
                ).then(result => {
                    if (result) {
                        performAdminAction('deactivate', anuncioId, anuncianteUserId);
                    }
                });
            };
            btnDeactivate.addEventListener('click', handler);
            btnDeactivate._clickHandler = handler;
        }
    }

    // Lógica para o botão "Visualizar Anúncio" para o administrador
    if (btnVisualizar) {
        toggleButtonState(btnVisualizar, true);
        btnVisualizar.href = `${window.URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`;
        btnVisualizar.dataset.spa = 'true';
        console.log(`DEBUG JS: btnVisualizarAnuncio configurado para admin. Href: ${btnVisualizar.href}`);
    }

    // Lógica para o botão "Excluir Conta" do usuário (admin)
    if (btnDeleteAccount) {
        const handler = function (event) {
            event.preventDefault();
            const anuncianteUserIdFinal = btnDeleteAccount.dataset.anuncianteUserId || anuncianteUserId;

            if (typeof window.showConfirmModal === 'function') {
                window.showConfirmModal(
                    "Tem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível.",
                    'Excluir Conta do Usuário',
                    'danger'
                ).then(confirmed => {
                    if (confirmed) {
                        console.log(`DEBUG JS: Usuário confirmou exclusão da conta. User ID: ${anuncianteUserIdFinal}`);
                        
                        window.showLoadingModal();
                        
                        fetch(window.URLADM + 'perfil/deleteAccount', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                user_id: anuncianteUserIdFinal,
                                admin_action: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('DEBUG JS: Resposta da exclusão da conta:', data);
                            
                            setTimeout(() => {
                                window.hideLoadingModal();
                                
                                if (data.success) {
                                    window.showFeedbackModal('success', 'Conta excluída com sucesso!', 'Exclusão de Conta');
                                    setTimeout(() => {
                                        const target = (data.redirect_url && typeof data.redirect_url === 'string') ? data.redirect_url : (window.URLADM + 'dashboard');
                                        window.location.href = target;
                                    }, 1200);
                                } else {
                                    window.showFeedbackModal('error', data.message || 'Erro ao excluir conta.', 'Erro na Exclusão');
                                }
                            }, 300);
                        })
                        .catch(error => {
                            console.error('ERROR JS: Erro na exclusão da conta:', error);
                            
                            setTimeout(() => {
                                window.hideLoadingModal();
                                window.showFeedbackModal('error', 'Erro interno. Tente novamente.', 'Erro na Exclusão');
                            }, 300);
                        });
                    }
                });
            }
        };
        btnDeleteAccount.addEventListener('click', handler);
        btnDeleteAccount._clickHandler = handler;
    }
}

/**
 * Realiza a ação do administrador via AJAX.
 * @param {string} action A ação a ser realizada ('approve', 'reject', 'activate', 'deactivate', 'delete').
 * @param {string} anuncioId O ID do anúncio.
 * @param {string} anuncianteUserId O ID do usuário anunciante.
 */
async function performAdminAction(action, anuncioId, anuncianteUserId) {
    console.log(`INFO JS: performAdminAction - Executando ação: ${action} para anúncio ID: ${anuncioId}`);

    window.showLoadingModal();

    try {
        const response = await fetch(`${window.URLADM}anuncio/adminAction`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: action,
                anuncio_id: anuncioId,
                anunciante_user_id: anuncianteUserId
            })
        });

        const data = await response.json();
        console.log(`DEBUG JS: performAdminAction - Resposta da ação ${action}:`, data);

        setTimeout(() => {
            window.hideLoadingModal();

            if (data.success) {
                window.showFeedbackModal('success', data.message || 'Ação realizada com sucesso!', 'Sucesso');
                
                // Atualizar botões de administrador dinamicamente se o novo status foi fornecido
                if (data.new_status && window.updateAdminButtonsAfterAction) {
                    console.log('🔄 ANÚNCIO ADMIN: Atualizando botões com novo status:', data.new_status);
                    window.updateAdminButtonsAfterAction(data.new_status, anuncioId, anuncianteUserId);
                } else if (!data.new_status) {
                    console.warn('⚠️ ANÚNCIO ADMIN: new_status vazio na resposta. Tentando determinar status baseado na ação...');
                    // Tentar determinar o status baseado na ação
                    let expectedStatus = '';
                    if (action === 'deactivate') expectedStatus = 'pausado';
                    else if (action === 'activate') expectedStatus = 'active';
                    else if (action === 'approve') expectedStatus = 'active';
                    else if (action === 'reject') expectedStatus = 'rejected';
                    
                    if (expectedStatus && window.updateAdminButtonsAfterAction) {
                        console.log('🔄 ANÚNCIO ADMIN: Usando status esperado:', expectedStatus);
                        window.updateAdminButtonsAfterAction(expectedStatus, anuncioId, anuncianteUserId);
                    }
                }
                
                // Recarregar dados da dashboard se estivermos na página de dashboard
                if (window.location.pathname.includes('/dashboard') || window.location.pathname.includes('/adms/dashboard')) {
                    console.log('🔄 ANÚNCIO ADMIN: Recarregando dados da dashboard...');
                    setTimeout(() => {
                        if (window.loadAnunciosData) {
                            window.loadAnunciosData();
                        } else if (window.initializeAnunciosListPage) {
                            window.initializeAnunciosListPage(window.location.href);
                        }
                    }, 1000);
                }
            } else {
                window.showFeedbackModal('error', data.message || 'Erro ao realizar ação.', 'Erro');
            }
        }, 3000);

    } catch (error) {
        console.error(`ERROR JS: Erro na ação ${action}:`, error);
        
        setTimeout(() => {
            window.hideLoadingModal();
            window.showFeedbackModal('error', 'Erro interno. Tente novamente.', 'Erro');
        }, 3000);
    }
}

// Função para atualizar botões de administrador após uma ação
function updateAdminButtonsAfterAction(newStatus, anuncioId, anuncianteUserId) {
    console.log('🔄 ANÚNCIO ADMIN: Atualizando botões após ação. Novo status:', newStatus);
    console.log('🔄 ANÚNCIO ADMIN: Parâmetros:', { newStatus, anuncioId, anuncianteUserId });
    
    // Remover botões existentes
    const adminButtonsContainer = document.querySelector('.d-flex.flex-column.flex-sm-row.justify-content-center.gap-3');
    console.log('🔄 ANÚNCIO ADMIN: Container encontrado:', !!adminButtonsContainer);
    console.log('🔄 ANÚNCIO ADMIN: Container HTML:', adminButtonsContainer?.outerHTML);
    
    if (adminButtonsContainer) {
        // Remover botões dinâmicos (pausar/ativar)
        const dynamicButtons = adminButtonsContainer.querySelectorAll('#btnDeactivateAnuncio, #btnActivateAnuncio');
        dynamicButtons.forEach(btn => btn.remove());
        
        // Adicionar botão apropriado baseado no novo status
        if (newStatus === 'active') {
            const pauseButton = document.createElement('button');
            pauseButton.type = 'button';
            pauseButton.className = 'btn btn-warning btn-lg';
            pauseButton.id = 'btnDeactivateAnuncio';
            pauseButton.setAttribute('data-anuncio-id', anuncioId);
            pauseButton.setAttribute('data-anunciante-user-id', anuncianteUserId);
            pauseButton.textContent = 'Pausar Anúncio';
            
            // Adicionar event listener
            pauseButton.addEventListener('click', function() {
                window.showConfirmModal(
                    'Tem certeza que deseja PAUSAR este anúncio? Ele ficará oculto do público.',
                    'Pausar Anúncio'
                ).then(result => {
                    if (result) {
                        performAdminAction('deactivate', anuncioId, anuncianteUserId);
                    }
                });
            });
            
            adminButtonsContainer.appendChild(pauseButton);
            console.log('✅ ANÚNCIO ADMIN: Botão "Pausar" adicionado');
            
        } else if (newStatus === 'pausado') {
            const activateButton = document.createElement('button');
            activateButton.type = 'button';
            activateButton.className = 'btn btn-info btn-lg';
            activateButton.id = 'btnActivateAnuncio';
            activateButton.setAttribute('data-anuncio-id', anuncioId);
            activateButton.setAttribute('data-anunciante-user-id', anuncianteUserId);
            activateButton.textContent = 'Ativar Anúncio';
            
            // Adicionar event listener
            activateButton.addEventListener('click', function() {
                window.showConfirmModal(
                    'Tem certeza que deseja ATIVAR este anúncio? Ele ficará visível publicamente.',
                    'Ativar Anúncio'
                ).then(result => {
                    if (result) {
                        performAdminAction('activate', anuncioId, anuncianteUserId);
                    }
                });
            });
            
            adminButtonsContainer.appendChild(activateButton);
            console.log('✅ ANÚNCIO ADMIN: Botão "Ativar" adicionado');
        }
    }
}

// Expor funções globalmente
window.setupAdminActionButtons = setupAdminActionButtons;
window.performAdminAction = performAdminAction;
window.toggleButtonState = toggleButtonState;
window.updateAdminButtonsAfterAction = updateAdminButtonsAfterAction;

console.log('✅ ANÚNCIO ADMIN: Módulo carregado e pronto');
