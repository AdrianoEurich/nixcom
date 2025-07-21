// app/adms/assets/js/dashboard_anuncios.js

/**
 * Arquivo JavaScript para gerenciar a funcionalidade de Anúncios Recentes e Pendentes
 * no dashboard do administrador.
 * Inclui:
 * - Lógica de busca e paginação AJAX para Anúncios Recentes.
 * - Lógica para os botões de Aprovar e Rejeitar Anúncios Pendentes.
 */

// Certifica-se de que URLADM e projectBaseURL estão definidos globalmente (vindos de main.php)
const URLADM = window.URLADM;
const projectBaseURL = window.projectBaseURL; // Renomeado para evitar conflito com construtor nativo URL

if (typeof URLADM === 'undefined' || typeof projectBaseURL === 'undefined') {
    console.error('ERRO JS: URLADM ou projectBaseURL não estão definidas. Funções de anúncios podem não funcionar.');
    // Não chama showFeedbackModal aqui, pois general-utils.js pode ainda não estar carregado
} else {
    console.log('INFO JS: dashboard_anuncios.js carregado.');
}


// --- Funções para Aprovar/Rejeitar Anúncios Pendentes ---

/**
 * Envia uma requisição AJAX para aprovar ou rejeitar um anúncio.
 * @param {number} anuncioId O ID do anúncio.
 * @param {string} action Ação a ser executada ('approve' ou 'reject').
 * @param {string} successMessage Mensagem de sucesso personalizada.
 * @param {string} errorMessage Mensagem de erro personalizada.
 */
async function updateAnuncioStatus(anuncioId, action, successMessage, errorMessage) {
    console.log(`DEBUG JS: Tentando ${action} anúncio ID: ${anuncioId}`);
    
    // Verifica se showLoadingModal está disponível antes de chamar
    if (typeof window.showLoadingModal === 'function') {
        window.showLoadingModal(`${action === 'approve' ? 'Aprovando' : 'Rejeitando'} anúncio...`);
    } else {
        console.warn('AVISO JS: showLoadingModal não está disponível.');
    }

    try {
        const url = `${URLADM}anuncio/${action}Anuncio`;
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ anuncio_id: anuncioId, ajax: true })
        });

        const data = await response.json();

        // Esconde o modal de carregamento
        if (typeof window.hideLoadingModal === 'function') {
            window.hideLoadingModal();
        }

        if (data.success) {
            if (typeof window.showFeedbackModal === 'function') {
                window.showFeedbackModal('success', data.message || successMessage, 'Sucesso!');
            }
            
            // 1. Atualiza o status do anúncio no body para que a sidebar reflita a mudança.
            if (typeof window.fetchAndApplyAnuncioStatus === 'function') {
                await window.fetchAndApplyAnuncioStatus(); // Busca o status mais recente do anúncio do usuário
            } else {
                console.warn('AVISO JS: fetchAndApplyAnuncioStatus não está disponível para atualizar o status do anúncio na sidebar.');
                // Fallback: se não puder buscar, assume um status para tentar atualizar a sidebar
                document.body.dataset.anuncioStatus = data.newStatus || 'pending'; // Assuma um status razoável ou o que o backend retornar
                document.body.dataset.hasAnuncio = 'true'; // Se aprovou, certamente tem anúncio
            }

            // 2. Atualiza a sidebar para refletir o novo status
            if (typeof window.updateAnuncioSidebarLinks === 'function') {
                window.updateAnuncioSidebarLinks();
            } else {
                console.warn('AVISO JS: updateAnuncioSidebarLinks não está disponível para atualizar a sidebar.');
            }

            // 3. Recarrega apenas a seção da tabela de anúncios recentes
            // Verifica se loadLatestAnuncios está disponível
            if (typeof window.loadLatestAnuncios === 'function') {
                window.loadLatestAnuncios(1, searchInput.value.trim()); 
            } else {
                console.warn('AVISO JS: loadLatestAnuncios não está disponível para recarregar a tabela.');
            }

        } else {
            if (typeof window.showFeedbackModal === 'function') {
                window.showFeedbackModal('error', data.message || errorMessage || 'Erro desconhecido.', 'Erro!');
            }
        }

    } catch (error) {
        console.error(`ERRO JS: Erro na requisição AJAX para ${action} anúncio:`, error);
        if (typeof window.hideLoadingModal === 'function') {
            window.hideLoadingModal(); // Garante que o modal de carregamento seja escondido
        }
        if (typeof window.showFeedbackModal === 'function') {
            window.showFeedbackModal('error', 'Ocorreu um erro de comunicação com o servidor.', 'Erro de Rede');
        }
    }
}

// --- Funções para Ativar/Desativar Anúncio (na página de Visualizar Anúncio) ---
// Estas funções são para a página de visualização de anúncio individual
// e devem chamar updateAnuncioStatus com as mensagens apropriadas.

/**
 * Ativa um anúncio específico.
 * @param {number} anuncioId O ID do anúncio a ser ativado.
 */
window.activateAnuncio = async function(anuncioId) {
    console.log(`DEBUG JS: Confirmação de activate de anúncio recebida.`);
    await updateAnuncioStatus(anuncioId, 'activate', 'Anúncio ativado com sucesso!', 'Falha ao ativar o anúncio.');
};

/**
 * Desativa um anúncio específico.
 * @param {number} anuncioId O ID do anúncio a ser desativado.
 */
window.deactivateAnuncio = async function(anuncioId) {
    console.log(`DEBUG JS: Confirmação de deactivate de anúncio recebida.`);
    await updateAnuncioStatus(anuncioId, 'deactivate', 'Anúncio desativado com sucesso!', 'Falha ao desativar o anúncio.');
};

/**
 * Função para carregar os anúncios recentes via AJAX.
 * Exposta globalmente para ser chamada por dashboard_custom.js
 * @param {number} page A página a ser carregada.
 * @param {string} searchTerm O termo de busca.
 */
window.loadLatestAnuncios = async function(page, searchTerm) {
    console.log(`DEBUG JS: loadLatestAnuncios - Carregando página ${page} com busca "${searchTerm}"`);
    if (typeof window.showLoadingModal === 'function') {
        window.showLoadingModal('Carregando anúncios...'); // Mostra modal de carregamento
    }

    try {
        const url = `${URLADM}dashboard?ajax=true&page=${page}&search=${encodeURIComponent(searchTerm)}`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const html = await response.text();
        
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        const newTableBody = tempDiv.querySelector('#latestAnunciosTableBody');
        const newPaginationControls = tempDiv.querySelector('#paginationControls');

        // Garante que os elementos existam antes de manipular
        const currentLatestAnunciosTableBody = document.getElementById('latestAnunciosTableBody');
        const currentPaginationControls = document.getElementById('paginationControls');

        if (currentLatestAnunciosTableBody && newTableBody) {
            currentLatestAnunciosTableBody.innerHTML = newTableBody.innerHTML;
        } else {
            console.warn('AVISO JS: Elemento #latestAnunciosTableBody não encontrado ou nova tabela não pôde ser extraída.');
        }

        if (currentPaginationControls && newPaginationControls) {
            currentPaginationControls.innerHTML = newPaginationControls.innerHTML;
        } else {
            console.warn('AVISO JS: Elemento #paginationControls não encontrado ou nova paginação não pôde ser extraída.');
        }

        console.log('INFO JS: Anúncios recentes e paginação atualizados com sucesso.');

    } catch (error) {
        console.error('ERRO JS: Erro ao carregar anúncios recentes:', error);
        if (typeof window.showFeedbackModal === 'function') {
            window.showFeedbackModal('error', 'Erro ao carregar anúncios recentes. Tente novamente.', 'Erro de Carregamento');
        }
    } finally {
        if (typeof window.hideLoadingModal === 'function') {
            window.hideLoadingModal(); // Oculta o spinner
        }
    }
};


// --- Event Listeners para Botões de Aprovar/Rejeitar (Delegation) ---
// Estes listeners são adicionados no DOMContentLoaded para garantir que o DOM esteja pronto.
document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: DOMContentLoaded disparado em dashboard_anuncios.js. (Adicionando event listeners).');

    // --- Elementos da Seção de Anúncios Recentes ---
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const paginationControls = document.getElementById('paginationControls');

    // --- Event Listener para o Formulário de Busca ---
    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const searchTerm = searchInput.value.trim();
            window.loadLatestAnuncios(1, searchTerm); // Chama a função global
        });
    }

    // --- Event Listener para os Links de Paginação (Delegation) ---
    if (paginationControls) {
        paginationControls.addEventListener('click', function(event) {
            const target = event.target;
            if (target.tagName === 'A' && target.classList.contains('page-link')) {
                event.preventDefault();
                const page = parseInt(target.dataset.page);
                const searchTerm = target.dataset.search || '';
                if (!isNaN(page)) {
                    window.loadLatestAnuncios(page, searchTerm); // Chama a função global
                }
            }
        });
    }

    document.body.addEventListener('click', function(event) {
        const target = event.target;

        // Botão Aprovar (no Dashboard)
        if (target.classList.contains('approve-anuncio-btn') || target.closest('.approve-anuncio-btn')) {
            event.preventDefault();
            const button = target.classList.contains('approve-anuncio-btn') ? target : target.closest('.approve-anuncio-btn');
            const anuncioId = button.dataset.id;
            if (anuncioId) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal(
                        'Aprovar Anúncio',
                        `Tem certeza que deseja aprovar o anúncio ID ${anuncioId}? Ele ficará ativo no site.`,
                        'Sim, Aprovar',
                        'Cancelar'
                    ).then(confirmed => {
                        if (confirmed) {
                            updateAnuncioStatus(anuncioId, 'approve', 'Anúncio aprovado com sucesso!', 'Falha ao aprovar o anúncio.');
                        }
                    });
                } else {
                    console.warn('AVISO JS: showConfirmModal não está disponível.');
                }
            }
        }

        // Botão Rejeitar (no Dashboard)
        if (target.classList.contains('reject-anuncio-btn') || target.closest('.reject-anuncio-btn')) {
            event.preventDefault();
            const button = target.classList.contains('reject-anuncio-btn') ? target : target.closest('.reject-anuncio-btn');
            const anuncioId = button.dataset.id;
            if (anuncioId) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal(
                        'Rejeitar Anúncio',
                        `Tem certeza que deseja rejeitar o anúncio ID ${anuncioId}? Ele não ficará ativo no site.`,
                        'Sim, Rejeitar',
                        'Cancelar'
                    ).then(confirmed => {
                        if (confirmed) {
                            updateAnuncioStatus(anuncioId, 'reject', 'Anúncio rejeitado com sucesso!', 'Falha ao rejeitar o anúncio.');
                        }
                    });
                } else {
                    console.warn('AVISO JS: showConfirmModal não está disponível.');
                }
            }
        }

        // Botão Ativar Anúncio (na página de Visualizar Anúncio)
        if (target.id === 'activateAnuncioBtn' || target.closest('#activateAnuncioBtn')) {
            event.preventDefault();
            const button = target.id === 'activateAnuncioBtn' ? target : target.closest('#activateAnuncioBtn');
            const anuncioId = button.dataset.anuncioId; // Assumindo que o ID está no data-anuncio-id
            if (anuncioId) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal(
                        'Ativar Anúncio',
                        `Tem certeza que deseja ativar o anúncio ID ${anuncioId}? Ele ficará visível no site.`,
                        'Sim, Ativar',
                        'Cancelar'
                    ).then(confirmed => {
                        if (confirmed) {
                            window.activateAnuncio(anuncioId); // Chama a função global
                        }
                    });
                } else {
                    console.warn('AVISO JS: showConfirmModal não está disponível.');
                }
            }
        }

        // Botão Desativar Anúncio (na página de Visualizar Anúncio)
        if (target.id === 'deactivateAnuncioBtn' || target.closest('#deactivateAnuncioBtn')) {
            event.preventDefault();
            const button = target.id === 'deactivateAnuncioBtn' ? target : target.closest('#deactivateAnuncioBtn');
            const anuncioId = button.dataset.anuncioId; // Assumindo que o ID está no data-anuncio-id
            if (anuncioId) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal(
                        'Desativar Anúncio',
                        `Tem certeza que deseja desativar o anúncio ID ${anuncioId}? Ele não ficará visível no site.`,
                        'Sim, Desativar',
                        'Cancelar'
                    ).then(confirmed => {
                        if (confirmed) {
                            window.deactivateAnuncio(anuncioId); // Chama a função global
                        }
                    });
                } else {
                    console.warn('AVISO JS: showConfirmModal não está disponível.');
                }
            }
        }
    });
});
