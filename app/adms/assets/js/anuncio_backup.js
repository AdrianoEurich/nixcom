// anuncio.js (Versão 62 - Correção Botões Admin)
console.log('anuncio.js carregado!');
console.info("anuncio.js (Versão 62 - Correção Botões Admin) carregado.");

// Assegura que URLADM e projectBaseURL (base do projeto) estejam disponíveis globalmente
// Elas devem ser definidas em main.php ou em um script global carregado antes.
if (typeof window.URLADM === 'undefined') {
    console.warn('AVISO JS: window.URLADM não está definida. Certifique-se de que main.php a define.');
    window.URLADM = 'http://localhost/nixcom/adms/'; // Fallback
} else {
    console.log('INFO JS: URLADM (global, vinda de main.php) em anuncio.js:', window.URLADM);
}
if (typeof window.projectBaseURL === 'undefined') {
    console.warn('AVISO JS: window.projectBaseURL não está definida. Certifique-se de que main.php a define.');
    window.projectBaseURL = 'http://localhost/nixcom/'; // Fallback
} else {
    console.log('INFO JS: projectBaseURL (global, URL base do projeto) em anuncio.js:', window.projectBaseURL);
}

// =================================================================================================
// FUNÇÃO DE INICIALIZAÇÃO DA PÁGINA DE ANÚNCIO (PARA SPA)
// =================================================================================================

// =================================================================================================
// FUNÇÃO DE INICIALIZAÇÃO DA PÁGINA DE ANÚNCIO (PARA SPA)
// =================================================================================================

/**
 * Função de inicialização específica para a página de anúncio
 * Chamada pelo sistema SPA quando a página é carregada
 */
window.initializeAnuncioFormPage = async function(fullUrl, initialData = null) {
    console.log('INFO JS: initializeAnuncioFormPage - Iniciando inicialização da página de formulário de anúncio.');
    console.log('DEBUG JS: initializeAnuncioFormPage - fullUrl:', fullUrl, 'initialData:', initialData);

    // Determina o modo do formulário baseado na URL
    const formMode = fullUrl && fullUrl.includes('editarAnuncio') ? 'edit' : 'create';
    const userRole = document.body.dataset.userRole;
    const userPlanType = document.body.dataset.userPlanType || 'basic';
    
    console.log('DEBUG JS: initializeAnuncioFormPage - formMode:', formMode, 'userRole:', userRole, 'userPlanType:', userPlanType);

    // Busca dados do anúncio se estiver no modo de edição
    let anuncioData = null;
    if (formMode === 'edit') {
        const anuncioId = new URLSearchParams(fullUrl.split('?')[1]).get('id');
        console.log('DEBUG JS: initializeAnuncioFormPage - Modo edição detectado. Anúncio ID:', anuncioId);
        
        if (anuncioId) {
            try {
                const response = await fetch(`${window.URLADM}anuncio/editarAnuncio?id=${anuncioId}&ajax_data_only=true`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                if (data.success && data.anuncio) {
                    anuncioData = data.anuncio;
                    console.log('DEBUG JS: initializeAnuncioFormPage - Dados do anúncio carregados:', anuncioData);
                }
            } catch (error) {
                console.error('ERRO JS: initializeAnuncioFormPage - Erro ao carregar dados do anúncio:', error);
            }
        }
    }

    // Inicializar campos do formulário se existirem
    const anuncioForm = document.getElementById('anuncioForm');
    if (anuncioForm) {
        console.log('INFO JS: Formulário de anúncio encontrado, inicializando campos');
        
        // Aplicar máscaras nos campos
        initializeFormMasks();
        
        // Configurar validação em tempo real
        setupFormValidation();
        
        // Configurar upload de arquivos
        setupFileUploads();
        
        // Configurar botões de ação
        setupActionButtons();
        
        // Inicializar campos com dados se estiver no modo de edição
        if (anuncioData) {
            initializeFormFields(anuncioForm, anuncioData, formMode, userPlanType);
        }
    } else {
        console.log('INFO JS: Formulário de anúncio não encontrado');
    }
    
    // Atualizar links da sidebar
    if (typeof window.updateAnuncioSidebarLinks === 'function') {
        window.updateAnuncioSidebarLinks();
    }
    
    console.log('INFO JS: Inicialização da página de anúncio concluída');
};

/**
 * Inicializar máscaras nos campos do formulário
 */
function initializeFormMasks() {
    // Máscara para telefone
    const telefoneField = document.getElementById('telefone');
    if (telefoneField) {
        telefoneField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            value = value.replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3');
            e.target.value = value;
        });
    }
    
    // Máscara para CEP
    const cepField = document.getElementById('cep');
    if (cepField) {
        cepField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
}

/**
 * Configurar validação em tempo real do formulário
 */
function setupFormValidation() {
    const form = document.getElementById('anuncioForm');
    if (!form) return;
    
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', validateField);
        field.addEventListener('input', validateField);
    });
}

/**
 * Validar campo individual
 */
function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    
    // Remover classes de erro anteriores
    field.classList.remove('is-invalid');
    
    if (field.hasAttribute('required') && !value) {
        field.classList.add('is-invalid');
        return false;
    }
    
    // Validações específicas por tipo
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            field.classList.add('is-invalid');
            return false;
        }
    }
    
    return true;
}

/**
 * Configurar upload de arquivos
 */
function setupFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', handleFileUpload);
    });
}

/**
 * Manipular upload de arquivos
 */
function handleFileUpload(event) {
    const input = event.target;
    const files = Array.from(input.files);
    
    // Validar tamanho e tipo dos arquivos
    files.forEach(file => {
        if (file.size > 5 * 1024 * 1024) { // 5MB
            alert('Arquivo muito grande: ' + file.name);
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mp3'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de arquivo não permitido: ' + file.name);
            return;
        }
    });
}

/**
 * Configurar botões de ação
 */
function setupActionButtons() {
    const saveButton = document.getElementById('btnSalvar');
    if (saveButton) {
        saveButton.addEventListener('click', handleSaveAnuncio);
    }
    
    const previewButton = document.getElementById('btnPreview');
    if (previewButton) {
        previewButton.addEventListener('click', handlePreviewAnuncio);
    }
}

/**
 * Manipular salvamento do anúncio
 */
function handleSaveAnuncio(event) {
    event.preventDefault();
    
    const form = document.getElementById('anuncioForm');
    if (!form) return;
    
    // Validar formulário
    const isValid = validateForm(form);
    if (!isValid) {
        alert('Por favor, preencha todos os campos obrigatórios.');
        return;
    }
    
    // Mostrar loading
    const saveButton = event.target;
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    saveButton.disabled = true;
    
    // Simular salvamento (substituir por chamada AJAX real)
    setTimeout(() => {
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
        alert('Anúncio salvo com sucesso!');
    }, 2000);
}

/**
 * Manipular preview do anúncio
 */
function handlePreviewAnuncio(event) {
    event.preventDefault();
    
    const form = document.getElementById('anuncioForm');
    if (!form) return;
    
    // Validar formulário
    const isValid = validateForm(form);
    if (!isValid) {
        alert('Por favor, preencha todos os campos obrigatórios para visualizar o preview.');
        return;
    }
    
    // Abrir preview em nova aba
    window.open(window.URLADM + 'anuncio/preview', '_blank');
}

/**
 * Validar formulário completo
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField({ target: field })) {
            isValid = false;
        }
    });
    
    return isValid;
}

// =================================================================================================
// FUNÇÕES AUXILIARES GERAIS (GLOBALMENTE DISPONÍVEIS, USADAS POR MÚLTIPLAS FUNÇÕES)
// =================================================================================================

/**
 * Helper para habilitar/desabilitar botões/links.
 * @param {HTMLElement} element O elemento (botão ou link) a ser manipulado.
 * @param {boolean} enable True para habilitar, false para desabilitar.
 */
window.toggleButtonState = (element, enable) => {
    if (element) {
        element.classList.toggle('disabled', !enable);
        element.style.opacity = enable ? '1' : '0.5';
        element.style.pointerEvents = enable ? 'auto' : 'none';
        element.style.cursor = enable ? 'pointer' : 'not-allowed';
        // Para links, se desabilitado, impede a navegação via click
        if (element.tagName === 'A' && !enable) {
            element.dataset.spa = 'false'; // Garante que SPA não tente carregar
        } else if (element.tagName === 'A' && enable && element.dataset.originalSpa === 'true') {
            element.dataset.spa = 'true'; // Restaura o comportamento SPA original
        }
    }
};

// Mapeamento para traduzir status
const statusMap = {
    'active': 'Ativo',
    'pending': 'Pendente',
    'rejected': 'Rejeitado',
        'inactive': 'Pausado',
    'deleted': 'Deletado'
};

// Mapeamento para traduzir gênero
const genderMap = {
    'Masculino': 'Masculino',
    'Feminino': 'Feminino',
    'Outro': 'Outro'
};


// =================================================================================================
// FUNÇÕES DE ATUALIZAÇÃO DA SIDEBAR (GLOBALMENTE DISPONÍVEIS)
// Estas funções são definidas no escopo global para serem acessíveis imediatamente após o carregamento do script.
// =================================================================================================

/**
 * Atualiza o estado dos links da sidebar relacionados a anúncios (Criar, Editar, Visualizar, Excluir, Pausar/Ativar).
 * Baseia-se nos atributos `data-has-anuncio`, `data-anuncio-status` e `data-user-role` do `body`.
 * Esta função é chamada na carga inicial da página e após operações SPA que alteram o estado do anúncio.
 */
window.updateAnuncioSidebarLinks = async function() {
    console.log('DEBUG JS: updateAnuncioSidebarLinks - Iniciado.');
    const bodyDataset = document.body.dataset;
    const hasAnuncio = bodyDataset.hasAnuncio === 'true';
    let anuncioStatus = bodyDataset.anuncioStatus || 'not_found';
    const userRole = bodyDataset.userRole || 'normal';
    const userId = bodyDataset.userId;
    // Removido a leitura inicial de anuncioId aqui, será lido após a potencial atualização

    console.log('DEBUG JS: updateAnuncioSidebarLinks - Body Dataset (initial):', bodyDataset);

    // Se o body dataset indica que um anúncio existe, mas o ID ou status está faltando/com erro,
    // tenta buscar novamente. Isso atualizará bodyDataset.anuncioId e bodyDataset.anuncioStatus.
    if (hasAnuncio && (anuncioStatus === 'not_found' || anuncioStatus === 'error_fetching' || !bodyDataset.anuncioId)) {
        console.log('DEBUG JS: updateAnuncioSidebarLinks - Anúncio existe mas ID/Status ausente ou com erro. Tentando buscar novamente.');
        const fetchedStatus = await window.fetchAndApplyAnuncioStatus();
        if (fetchedStatus) {
            anuncioStatus = fetchedStatus; // Atualiza a variável local com o status buscado
            // O bodyDataset.anuncioId já foi atualizado por fetchAndApplyAnuncioStatus
        } else {
            console.warn('AVISO JS: Não foi possível buscar o status/ID do anúncio. Mantendo o estado atual e desabilitando links de edição/visualização.');
            // Se a busca falhar, trata como se não houvesse ID de anúncio disponível para edição/visualização
            document.body.dataset.anuncioId = ''; // Limpa explicitamente se a busca falhou
            anuncioStatus = 'not_found'; // Reverte o status se a busca falhou para evitar estados ativos incorretos
        }
    }

    // LÊ O ANUNCIO ID *DEPOIS* da potencial chamada a fetchAndApplyAnuncioStatus
    const anuncioId = bodyDataset.anuncioId;

    console.log('DEBUG JS: updateAnuncioSidebarLinks - Anuncio ID from body (after potential fetch):', anuncioId);
    console.log('DEBUG JS: updateAnuncioSidebarLinks - Anuncio Status (after potential fetch):', anuncioStatus);


    const navCriarAnuncioLi = document.getElementById('navCriarAnuncio');
    const navEditarAnuncioLi = document.getElementById('navEditarAnuncio');
    const navVisualizarAnuncioLi = document.getElementById('navVisualizarAnuncio');
    const navExcluirAnuncioLi = document.getElementById('navExcluirAnuncio');
    const navPausarAnuncioLi = document.getElementById('navPausarAnuncio'); // O <li> pai do botão Pausar/Ativar
    const navFinanceiroLi = document.getElementById('navFinanceiro'); // O <li> pai do link Financeiro

    // Referências aos links <a> dentro dos <li>
    const navCriarAnuncioLink = navCriarAnuncioLi?.querySelector('a');
    const navEditarAnuncioLink = navEditarAnuncioLi?.querySelector('a');
    const navVisualizarAnuncioLink = navVisualizarAnuncioLi?.querySelector('a');
    const navExcluirAnuncioLink = navExcluirAnuncioLi?.querySelector('a');
    const navPausarAnuncioLink = navPausarAnuncioLi?.querySelector('a');

    // Salva o estado original do data-spa para links que podem ser desabilitados
    if (navCriarAnuncioLink && !navCriarAnuncioLink.dataset.originalSpa) navCriarAnuncioLink.dataset.originalSpa = navCriarAnuncioLink.dataset.spa;
    if (navEditarAnuncioLink && !navEditarAnuncioLink.dataset.originalSpa) navEditarAnuncioLink.dataset.originalSpa = navEditarAnuncioLink.dataset.spa;
    if (navVisualizarAnuncioLink && !navVisualizarAnuncioLink.dataset.originalSpa) navVisualizarAnuncioLink.dataset.originalSpa = navVisualizarAnuncioLink.dataset.spa;


    // Lógica para links de Administrador
    if (userRole === 'admin') {
        // Para administradores, os links com a classe 'user-only-link' já são HIDDEN por CSS.
        // Não precisamos fazer nada aqui no JS para escondê-los.
        // Apenas ajustamos o link "Pausar Anúncio" para "Gerenciar Anúncios" e o Financeiro.

        if (navPausarAnuncioLink) {
            const iconElement = navPausarAnuncioLink.querySelector('i');
            if (iconElement) {
                iconElement.className = 'fas fa-tasks me-2';
            }
            let textNode = Array.from(navPausarAnuncioLink.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '');
            if (textNode) {
                textNode.nodeValue = 'Gerenciar Anúncios';
            } else {
                navPausarAnuncioLink.innerHTML = `<i class="fas fa-tasks me-2"></i>Gerenciar Anúncios`;
            }
            navPausarAnuncioLink.href = `${URLADM}dashboard`;
            navPausarAnuncioLink.dataset.spa = 'true';
            window.toggleButtonState(navPausarAnuncioLink, true); // Admin sempre pode acessar o gerenciamento
            if (navPausarAnuncioLi) navPausarAnuncioLi.style.display = 'block'; // Garante que o item da lista esteja visível
        }

        if (navFinanceiroLi) {
            // Financeiro para admin pode ser diferente ou oculto, dependendo da sua regra de negócio
            navFinanceiroLi.style.display = 'block';
            window.toggleButtonState(navFinanceiroLi.querySelector('a'), true); // Habilita o link Financeiro para admin
        }

        console.log('DEBUG JS: Usuário é ADMIN. Links de usuário ocultos por CSS, "Gerenciar Anúncios" visível.');
        return; // Sai da função, pois o CSS já cuidou da visibilidade principal para admins
    }

    // Lógica para Usuário Normal
    // Garante que os itens de lista de usuário estão visíveis por padrão para usuários normais
    if (navCriarAnuncioLi) navCriarAnuncioLi.style.display = 'block';
    if (navEditarAnuncioLi) navEditarAnuncioLi.style.display = 'block';
    if (navVisualizarAnuncioLi) navVisualizarAnuncioLi.style.display = 'block';
    if (navExcluirAnuncioLi) navExcluirAnuncioLi.style.display = 'block';
    if (navPausarAnuncioLi) navPausarAnuncioLi.style.display = 'block';
    if (navFinanceiroLi) navFinanceiroLi.style.display = 'block'; // Financeiro sempre visível para usuário normal

    // Esconde o link de admin (Gerenciar Usuários) para usuários normais
    const navAdminUsersLi = document.getElementById('navAdminUsers');
    if (navAdminUsersLi) navAdminUsersLi.style.display = 'none';


    if (!hasAnuncio) {
        // Usuário sem anúncio: só pode criar
        window.toggleButtonState(navCriarAnuncioLink, true);
        window.toggleButtonState(navEditarAnuncioLink, false);
        window.toggleButtonState(navVisualizarAnuncioLink, false);
        window.toggleButtonState(navPausarAnuncioLink, false);
        window.toggleButtonState(navExcluirAnuncioLink, false);

        // Resetar texto e ícone do Pausar Anúncio
        if (navPausarAnuncioLink) {
            const iconElement = navPausarAnuncioLink.querySelector('i');
            if (iconElement) {
                iconElement.className = 'fas fa-exclamation-circle me-2';
            }
            let textNode = Array.from(navPausarAnuncioLink.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '');
            if (textNode) {
                textNode.nodeValue = 'Status Desconhecido';
            } else {
                navPausarAnuncioLink.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>Status Desconhecido`;
            }
            navPausarAnuncioLink.href = '#'; // Mantém o href para # para usuários normais
            navPausarAnuncioLink.dataset.spa = 'false'; // Garante que não é SPA para o toggle
            // Remove listener se houver, pois o botão está desabilitado
            if (navPausarAnuncioLink._clickHandler) {
                navPausarAnuncioLink.removeEventListener('click', navPausarAnuncioLink._clickHandler);
                navPausarAnuncioLink._clickHandler = null;
            }
        }
        // Remove listener do excluir se houver
        if (navExcluirAnuncioLink && navExcluirAnuncioLink._clickHandler) {
            navExcluirAnuncioLink.removeEventListener('click', navExcluirAnuncioLink._clickHandler);
            navExcluirAnuncioLink._clickHandler = null;
        }

    } else {
        // Usuário com anúncio: esconde 'Criar Anúncio'
        window.toggleButtonState(navCriarAnuncioLink, false);

        // Mostra e habilita os outros links de anúncio
        // ATUALIZAÇÃO CRÍTICA AQUI: Define o href com o ID do anúncio
        if (navEditarAnuncioLink && anuncioId) {
            navEditarAnuncioLink.href = `${URLADM}anuncio/editarAnuncio?id=${anuncioId}`;
            navEditarAnuncioLink.dataset.spa = 'true'; // Garante que a navegação seja via SPA
            window.toggleButtonState(navEditarAnuncioLink, true);
            console.log(`DEBUG JS: navEditarAnuncioLink href atualizado para: ${navEditarAnuncioLink.href}`);
        } else {
            console.warn('AVISO JS: navEditarAnuncioLink ou anuncioId não disponível para configurar o href. Desabilitando link de edição.');
            window.toggleButtonState(navEditarAnuncioLink, false); // Desabilita se o ID estiver faltando
        }

        if (navVisualizarAnuncioLink && anuncioId) {
            navVisualizarAnuncioLink.href = `${URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`;
            navVisualizarAnuncioLink.dataset.spa = 'true'; // Garante que a navegação seja via SPA
            window.toggleButtonState(navVisualizarAnuncioLink, true);
            console.log(`DEBUG JS: navVisualizarAnuncioLink href atualizado para: ${navVisualizarAnuncioLink.href}`);
        } else {
            console.warn('AVISO JS: navVisualizarAnuncioLink ou anuncioId não disponível para configurar o href. Desabilitando link de visualização.');
            window.toggleButtonState(navVisualizarAnuncioLink, false); // Desabilita se o ID estiver faltando
        }


        // Lógica específica para Pausar Anúncio
        if (navPausarAnuncioLink) {
            // Só mostra o link se o status for 'active' ou 'pausado'
            if (anuncioStatus === 'active' || anuncioStatus === 'pausado') {
            let iconClass = '';
            let buttonText = '';

                if (anuncioStatus === 'active') {
                    iconClass = 'fas fa-pause-circle';
                    buttonText = 'Pausar Anúncio';
                } else {
                    iconClass = 'fas fa-play-circle';
                    buttonText = 'Ativar Anúncio';
            }

            const iconElement = navPausarAnuncioLink.querySelector('i');
            if (iconElement) {
                iconElement.className = iconClass + ' me-2';
            }
            let textNode = Array.from(navPausarAnuncioLink.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '');
            if (textNode) {
                textNode.nodeValue = buttonText;
            } else {
                navPausarAnuncioLink.innerHTML = `<i class="${iconClass} me-2"></i>${buttonText}`;
            }
            navPausarAnuncioLink.href = '#'; // Mantém o href para # para usuários normais
            navPausarAnuncioLink.dataset.spa = 'false'; // Garante que não é SPA para o toggle

                window.toggleButtonState(navPausarAnuncioLink, true); // Habilita o botão
                console.log(`DEBUG JS: navPausarAnuncio status: ${anuncioStatus} - Link habilitado`);

            // Adicionar event listener para o botão Pausar/Ativar Anúncio (apenas para usuários normais)
            // Remove listener antigo para evitar duplicação em navegações SPA
            if (navPausarAnuncioLink._clickHandler) {
                navPausarAnuncioLink.removeEventListener('click', navPausarAnuncioLink._clickHandler);
                navPausarAnuncioLink._clickHandler = null;
            }

                const toggleHandler = function(e) {
                    e.preventDefault(); // Impede o comportamento padrão do link
                    const userId = document.body.dataset.userId; // Pega o ID do usuário logado
                    if (!userId) {
                        console.error('ERRO JS: User ID não encontrado para toggleAnuncioStatus.');
                        window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
                        return;
                    }

                    let confirmTitle = 'Confirmar Ação';
                    let confirmMessage = '';
                    let actionType = '';

                    if (anuncioStatus === 'active') {
                        confirmMessage = 'Tem certeza que deseja PAUSAR seu anúncio? Ele não ficará visível publicamente.';
                        actionType = 'deactivate'; // Ação para o backend (PAUSAR)
                    } else if (anuncioStatus === 'pausado') {
                        confirmMessage = 'Tem certeza que deseja ATIVAR seu anúncio? Ele voltará a ficar visível publicamente.';
                        actionType = 'activate'; // Ação para o backend (ATIVAR)
                    }

                    if (actionType) {
                        window.showConfirmModal(confirmTitle, confirmMessage, async () => {
                            window.showLoadingModal('Processando...');
                            try {
                                const formData = new FormData();
                                formData.append('user_id', userId);
                                formData.append('action', actionType);

                                // ALTERADO AQUI: Chamando o método PHP correto
                                const response = await fetch(`${window.URLADM}anuncio/toggleAnuncioStatus`, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: formData
                                });

                                const responseText = await response.text();
                                console.log('DEBUG JS: Resposta bruta do toggleAnuncioStatus:', responseText);

                                let result;
                                try {
                                    result = JSON.parse(responseText);
                                } catch (jsonError) {
                                    console.error('ERRO JS: Erro ao parsear JSON da resposta:', jsonError, 'Resposta:', responseText);
                                    throw new Error('Resposta inválida do servidor. Verifique os logs do PHP.');
                                }

                                await window.hideLoadingModal();

                                if (result.success) {
                                    window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
                                    document.body.dataset.anuncioStatus = result.new_anuncio_status || anuncioStatus;
                                    document.body.dataset.hasAnuncio = result.has_anuncio !== undefined ? (result.has_anuncio ? 'true' : 'false') : document.body.dataset.hasAnuncio;
                                    document.body.dataset.anuncioId = result.anuncio_id || ''; // Garante que o ID seja atualizado
                                    window.updateAnuncioSidebarLinks();
                                } else {
                                    window.showFeedbackModal('error', result.message || 'Erro ao realizar a ação.', 'Erro!');
                                }
                            } catch (error) {
                                console.error('ERRO JS: Erro na requisição AJAX de toggleAnuncioStatus:', error);
                                await window.hideLoadingModal();
                                window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
                            }
                        });
                    }
                };
                navPausarAnuncioLink.addEventListener('click', toggleHandler);
                navPausarAnuncioLink._clickHandler = toggleHandler; // Armazena a referência
            }
        } else {
            // Para status 'pending', 'rejected', 'deleted' ou qualquer outro, esconde completamente o link
            navPausarAnuncioLink.style.display = 'none';
            console.log(`DEBUG JS: navPausarAnuncio status: ${anuncioStatus} - Link oculto`);
        }

        // Lógica para o botão "Excluir Anúncio" (apenas para usuários normais)
        if (navExcluirAnuncioLink) {
            // Remove listener antigo para evitar duplicação em navegações SPA
            if (navExcluirAnuncioLink._clickHandler) {
                navExcluirAnuncioLink.removeEventListener('click', navExcluirAnuncioLink._clickHandler);
                navExcluirAnuncioLink._clickHandler = null;
            }

            const canDelete = hasAnuncio; // Só pode excluir se tiver um anúncio
            window.toggleButtonState(navExcluirAnuncioLink, canDelete); // Usa a função auxiliar para habilitar/desabilitar

            if (canDelete) {
                const deleteHandler = function(e) {
                    e.preventDefault(); // Impede o comportamento padrão do link
                    const userId = document.body.dataset.userId; // Pega o ID do usuário logado
                    if (!userId) {
                        console.error('ERRO JS: User ID não encontrado para deleteMyAnuncio.');
                        window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
                        return;
                    }

                    window.showConfirmModal('Excluir Anúncio', 'Tem certeza que deseja EXCLUIR seu anúncio? Esta ação é irreversível e removerá todas as suas mídias e dados do anúncio.', async () => {
                        window.showLoadingModal('Excluindo Anúncio...');
                        try {
                            const formData = new FormData();
                            formData.append('user_id', userId); // Envia o user_id para o backend

                            const response = await fetch(`${window.URLADM}anuncio/deleteMyAnuncio`, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            });

                            const result = await response.json();
                            await window.hideLoadingModal();

                            if (result.success) {
                                window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
                                document.body.dataset.hasAnuncio = result.has_anuncio ? 'true' : 'false'; // Deve ser 'false'
                                document.body.dataset.anuncioStatus = result.anuncio_status || 'not_found'; // Deve ser 'not_found'
                                document.body.dataset.anuncioId = ''; // Limpa o ID do anúncio
                                window.updateAnuncioSidebarLinks(); // Atualiza a sidebar

                                // Redireciona para a página de criação de anúncio após a exclusão
                                if (result.redirect) {
                                    setTimeout(() => {
                                        window.loadContent(result.redirect, 'anuncio/index'); // Redireciona via SPA
                                    }, 1500);
                                }
                            } else {
                                window.showFeedbackModal('error', result.message || 'Erro ao excluir o anúncio.', 'Erro!');
                            }
                        } catch (error) {
                            console.error('ERRO JS: Erro na requisição AJAX de deleteMyAnuncio:', error);
                            await window.hideLoadingModal();
                            window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
                        }
                    });
                };
                navExcluirAnuncioLink.addEventListener('click', deleteHandler);
                navExcluirAnuncioLink._clickHandler = deleteHandler; // Armazena a referência
            }
        }
    }

    if (navFinanceiroLi) {
        // Financeiro sempre visível e habilitado para usuário normal
        window.toggleButtonState(navFinanceiroLi.querySelector('a'), true);
    }

    console.log(`INFO JS: Sidebar links atualizados. Has Anuncio: ${hasAnuncio} Anuncio Status: ${anuncioStatus} User Role: ${userRole}`);
};


/**
 * Busca o status do anúncio do servidor e atualiza o dataset do body.
 * @returns {Promise<string|null>} O status do anúncio ou null em caso de erro.
 */
window.fetchAndApplyAnuncioStatus = async function() {
    const userId = document.body.dataset.userId;
    if (!userId) {
        console.error('ERRO JS: fetchAndApplyAnuncioStatus - User ID não encontrado no dataset do body.');
        return null;
    }

    console.log(`DEBUG JS: fetchAndApplyAnuncioStatus - Buscando status atual do anúncio ID: ${userId}. Requisição com ajax_data_only=true.`);
    try {
        const response = await fetch(`${window.URLADM}anuncio/visualizarAnuncio?ajax_data_only=true`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error(`ERRO JS: fetchAndApplyAnuncioStatus - Resposta de rede não OK:`, response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success && data.anuncio) {
            document.body.dataset.anuncioStatus = data.anuncio.status;
            document.body.dataset.hasAnuncio = 'true';
            document.body.dataset.anuncioId = data.anuncio.id; // ATUALIZA O anuncioId AQUI!
            console.log('INFO JS: fetchAndApplyAnuncioStatus - Status do anúncio e ID atualizados:', data.anuncio.status, data.anuncio.id);
            return data.anuncio.status;
        } else {
            document.body.dataset.anuncioStatus = 'not_found';
            document.body.dataset.hasAnuncio = 'false';
            document.body.dataset.anuncioId = ''; // Limpa o ID se o anúncio não for encontrado
            console.warn('AVISO JS: fetchAndApplyAnuncioStatus - Anúncio não encontrado ou dados incompletos:', data.message);
            return 'not_found';
        }
    } catch (error) {
        console.error('ERRO JS: fetchAndApplyAnuncioStatus - Erro ao buscar status do anúncio:', error);
        document.body.dataset.anuncioStatus = 'error_fetching';
        document.body.dataset.hasAnuncio = 'false';
        document.body.dataset.anuncioId = '';
        return null;
    }
};

// =================================================================================================
// FUNÇÕES DE INICIALIZAÇÃO DE PÁGINAS ESPECÍFICAS (GLOBALMENTE DISPONÍVEIS)
// Estas funções são definidas no escopo global para serem acessíveis imediatamente após o carregamento do script.
// =================================================================================================

/**
 * Inicializa a página de perfil.
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'perfil' é detectada.
 * (Esta função está duplicada em perfil.js, mantida aqui para compatibilidade,
 * mas a versão em perfil.js é a que deve ser a principal para a página de perfil.)
 */
window.initializePerfilPage = function() {
    console.log('INFO JS: initializePerfilPage - Iniciando inicialização da página de perfil (de anuncio.js).');
    // Esta função é mais bem tratada em perfil.js.
    // Se você tiver uma função initializePerfilPage em perfil.js, ela deve ser a principal.
    // Aqui, apenas um placeholder para evitar erros se for chamado de dashboard_custom.js
    // antes que perfil.js tenha a chance de definir sua própria versão.
    if (typeof window.initializePerfilPageActual === 'function') {
        window.initializePerfilPageActual();
    } else {
        console.warn('AVISO JS: initializePerfilPageActual não encontrada. A inicialização do perfil pode estar incompleta.');
    }
    console.log('INFO JS: initializePerfilPage - Finalizado (de anuncio.js).');
};


/**
 * Inicializa a página de formulário de anúncio (criar/editar).
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'anuncio/index' ou 'anuncio/editarAnuncio' é detectada.
 * @param {string|null} fullUrl - A URL completa da página.
 * @param {object|null} initialData - Dados iniciais da página (se houver).
 */
window.initializeAnuncioFormPage = async function(fullUrl, initialData = null) {
    console.log('INFO JS: initializeAnuncioFormPage - Iniciando inicialização da página de formulário de anúncio.');
    console.log('DEBUG JS: initializeAnuncioFormPage - fullUrl:', fullUrl, 'initialData:', initialData);

    // Determina o modo do formulário baseado na URL
    const formMode = fullUrl && fullUrl.includes('editarAnuncio') ? 'edit' : 'create';
    const userRole = document.body.dataset.userRole;
    const userPlanType = document.body.dataset.userPlanType || 'basic';
    
    console.log('DEBUG JS: initializeAnuncioFormPage - formMode:', formMode, 'userRole:', userRole, 'userPlanType:', userPlanType);

    // Busca dados do anúncio se estiver no modo de edição
    let anuncioData = null;
    if (formMode === 'edit') {
        const anuncioId = new URLSearchParams(fullUrl.split('?')[1]).get('id');
        console.log('DEBUG JS: initializeAnuncioFormPage - Modo edição detectado. Anúncio ID:', anuncioId);
        
        if (anuncioId) {
            try {
                const response = await fetch(`${window.URLADM}anuncio/editarAnuncio?id=${anuncioId}&ajax_data_only=true`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                console.log('DEBUG JS: initializeAnuncioFormPage - Resposta do servidor:', data);
                
                if (data.success && data.anuncio) {
                    anuncioData = data.anuncio;
                    console.log('DEBUG JS: initializeAnuncioFormPage - Dados do anúncio carregados via AJAX:', anuncioData);

                    // NOVO: Chama setupAdminActionButtons imediatamente após os dados serem carregados e confirmados
                    if (userRole === 'admin' && anuncioData?.id) {
                        console.log('DEBUG JS: initializeAnuncioFormPage - Chamando setupAdminActionButtons imediatamente após o carregamento de dados AJAX.');
                        setupAdminActionButtons(anuncioData.id, anuncioData.user_id, anuncioData.status);
                    }

                } else {
                    throw new Error(data.message || 'Dados do anúncio não encontrados.');
                }
            } catch (error) {
                console.error('ERRO JS: initializeAnuncioFormPage - Erro ao buscar dados do anúncio para edição:', error);
                window.showFeedbackModal('error', `Erro ao carregar dados do anúncio: ${error.message}`, 'Erro de Carregamento');
                // Não retorna aqui para que o formulário ainda possa ser inicializado (vazio ou com dados parciais)
            }
        }
    }
    
    // Certifica-se de que 'form' está definido para setupFormValidation
    const form = document.getElementById('formAnuncio');
    if (!form) {
        console.error('ERRO JS: initializeAnuncioFormPage - Formulário "formAnuncio" não encontrado.');
        window.showFeedbackModal('error', 'Erro interno: Formulário de anúncio não encontrado.', 'Erro de Inicialização');
        return; // Retorna se o formulário principal não for encontrado
    }

    const cardHeader = document.querySelector('[data-page-type="form"] .card-header');
    const formTitleElement = document.getElementById('formAnuncioTitle');
    const btnSubmitAnuncio = document.getElementById('btnSubmitAnuncio');

    if (cardHeader && formTitleElement && btnSubmitAnuncio) {
        cardHeader.classList.remove('bg-warning', 'text-dark', 'bg-primary', 'text-white');
        btnSubmitAnuncio.classList.remove('btn-warning', 'btn-primary');

        if (formMode === 'edit') {
            formTitleElement.innerHTML = '<i class="fas fa-edit me-2"></i>EDITAR ANÚNCIO';
            btnSubmitAnuncio.innerHTML = '<i class="fas fa-save me-2"></i>ATUALIZAR ANÚNCIO';
            cardHeader.classList.add('bg-warning', 'text-dark');
            btnSubmitAnuncio.classList.add('btn-warning');
        } else { // formMode === 'create'
            formTitleElement.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR NOVO ANÚNCIO';
            btnSubmitAnuncio.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
            cardHeader.classList.add('bg-primary', 'text-white');
            btnSubmitAnuncio.classList.add('btn-primary');
        }
        console.log('DEBUG JS: initializeAnuncioFormPage - Cores do cabeçalho e botão aplicadas dinamicamente.');
    } else {
        console.warn('AVISO JS: Elementos de cabeçalho, título ou botão do formulário de anúncio não encontrados.');
    }

    try {
        setupFormValidation(form);
        console.log('DEBUG JS: initializeAnuncioFormPage - setupFormValidation concluído.');

        setupInputMasks();
        console.log('DEBUG JS: initializeAnuncioFormPage - setupInputMasks concluído.');

        await loadAndPopulateLocations(anuncioData);
        console.log('DEBUG JS: initializeAnuncioFormPage - loadAndPopulateLocations concluído.');

        setupCheckboxValidation();
        console.log('DEBUG JS: initializeAnuncioFormPage - setupCheckboxValidation concluído.');

        initializeFormFields(formAnuncio, anuncioData, formMode, userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - initializeFormFields concluído.');

        setupFileUploadHandlers(formAnuncio, anuncioData, formMode, userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - setupFileUploadHandlers concluído.');

        // Aplica as restrições de plano APÓS todos os campos e mídias serem inicializados
        applyPlanRestrictions(userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - applyPlanRestrictions concluído.');

        // Configura os botões de administrador se for admin e estiver editando
        if (userRole === 'admin' && formMode === 'edit' && anuncioData?.id) {
            console.log('DEBUG JS: initializeAnuncioFormPage - Configurando botões de administrador após inicialização completa.');
            setupAdminActionButtons(anuncioData.id, anuncioData.user_id, anuncioData.status);
        }

        // REMOVIDO A CHAMADA ANTIGA AQUI:
        // if (userRole === 'admin' && formMode === 'edit' && anuncioData.id) {
        //     setupAdminActionButtons(anuncioData.id, anuncioData.user_id, anuncioData.status);
        //     console.log('DEBUG JS: initializeAnuncioFormPage - setupAdminActionButtons concluído.');
        // } else if (userRole === 'admin' && formMode === 'edit' && !anuncioData.id) {
        //     console.warn('AVISO JS: Modo de edição para admin, mas anuncioData.id não encontrado. Botões de ação do admin não configurados.');
        // }


        if (typeof window.setupAutoDismissAlerts === 'function') {
            window.setupAutoDismissAlerts();
            console.log('DEBUG JS: initializeAnuncioFormPage - setupAutoDismissAlerts concluído.');
        }
        console.log('INFO JS: initializeAnuncioFormPage - Finalizado.');
    } catch (error) {
        console.error('ERRO JS: initializeAnuncioFormPage - Erro durante a inicialização:', error);
        window.showFeedbackModal('error', `Erro ao inicializar a página: anuncio/editarAnuncio. Detalhes: ${error.message}`, 'Erro de Inicialização');
    }
};

/**
 * Inicializa a página de visualização de anúncio.
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'anuncio/visualizar' é detectada.
 * @param {string|null} fullUrl - A URL completa da página.
 * @param {object|null} initialData - Dados iniciais da página (se houver).
 */
window.initializeVisualizarAnuncioPage = function(fullUrl, initialData = null) {
    console.log('INFO JS: initializeVisualizarAnuncioPage - Iniciando inicialização da página de visualização de anúncio.');
    console.log('DEBUG JS: initializeVisualizarAnuncioPage - fullUrl:', fullUrl, 'initialData:', initialData);

    // Configuração inicial
    setupAnuncioView();

    // Configuração dos botões de administrador (se existirem)
    setupAdminButtons();
};


// =================================================================================================
// FUNÇÕES AUXILIARES GERAIS (NÃO GLOBALIZADAS, CHAMADAS APENAS DENTRO DESTE ARQUIVO)
// =================================================================================================

/**
 * Configura a validação de formulários HTML5.
 * Impede o envio se houver campos inválidos e exibe mensagens de feedback.
 * @param {HTMLFormElement} form O elemento do formulário a ser validado.
 */
function setupFormValidation(form) {
    if (!form) {
        console.warn('AVISO JS: setupFormValidation - Formulário não fornecido.');
        return;
    }
    // Remove o listener antigo antes de adicionar um novo para evitar duplicação
    form.removeEventListener('submit', handleFormSubmit);
    form.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(event) {
    event.preventDefault();
    event.stopPropagation();

    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');

    // Limpa feedbacks de validação anteriores
    Array.from(form.querySelectorAll('.is-invalid')).forEach(el => el.classList.remove('is-invalid'));
    Array.from(form.querySelectorAll('.invalid-feedback')).forEach(el => el.textContent = '');
    Array.from(form.querySelectorAll('.text-danger.small')).forEach(el => {
        if (el) {
            el.textContent = '';
            el.style.display = 'none';
        }
    });
    Array.from(form.querySelectorAll('.photo-upload-box.is-invalid-media')).forEach(el => el.classList.remove('is-invalid-media'));
    Array.from(form.querySelectorAll('.form-check-group.is-invalid-group')).forEach(el => el.classList.remove('is-invalid-group'));


    if (!form.checkValidity()) {
        console.warn('AVISO JS: Formulário inválido. Exibindo feedback de validação HTML5.');
        Array.from(form.querySelectorAll(':invalid')).forEach(el => {
            el.classList.add('is-invalid');
            const feedbackElementById = document.getElementById(`${el.id}-feedback`);
            if (feedbackElementById) {
                feedbackElementById.textContent = el.validationMessage;
                feedbackElementById.style.display = 'block';
            } else {
                const feedbackDiv = el.nextElementSibling;
                if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                    feedbackDiv.textContent = el.validationMessage;
                    feedbackDiv.style.display = 'block';
                }
            }
        });
        form.querySelector(':invalid')?.focus();
        window.showFeedbackModal('error', 'Por favor, preencha todos os campos obrigatórios.', 'Erro de Validação!');
        return;
    }

    let isValidCheckboxes = true;
    const checkboxGroups = [
        { name: 'aparencia[]', min: 1, feedbackId: 'aparencia-feedback', message: 'Por favor, selecione pelo menos 1 item de aparência.' },
        { name: 'idiomas[]', min: 1, feedbackId: 'idiomas-feedback', message: 'Por favor, selecione pelo menos 1 idioma.' },
        { name: 'locais_atendimento[]', min: 1, feedbackId: 'locais_atendimento-feedback', message: 'Por favor, selecione pelo menos 1 local de atendimento.' },
        { name: 'formas_pagamento[]', min: 1, feedbackId: 'formas_pagamento-feedback', message: 'Por favor, selecione pelo menos 1 forma de pagamento.' },
        { name: 'servicos[]', min: 2, feedbackId: 'servicos-feedback', message: 'Por favor, selecione pelo menos 2 serviços.' }
    ];

    checkboxGroups.forEach(group => {
        const checkboxes = form.querySelectorAll(`input[name="${group.name}"]`);
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const feedbackElement = document.getElementById(group.feedbackId);
        const groupContainer = document.getElementById(group.feedbackId.replace('-feedback', '-checkboxes'));

        if (checkedCount < group.min) {
            if (feedbackElement) {
                feedbackElement.textContent = group.message;
                feedbackElement.style.display = 'block';
            }
            if (groupContainer) {
                groupContainer.classList.add('is-invalid-group');
            }
            isValidCheckboxes = false;
        } else {
            if (feedbackElement) {
                feedbackElement.textContent = '';
                feedbackElement.style.display = 'none';
            }
            if (groupContainer) {
                groupContainer.classList.remove('is-invalid-group');
            }
        }
    });

    let isValidPrices = true;
    const price15minInput = document.getElementById('price_15min');
    const price30minInput = document.getElementById('price_30min');
    const price1hInput = document.getElementById('price_1h');
    const pricesFeedback = document.getElementById('precos-feedback');

    // Usar inputmask.unmaskedvalue() para obter o valor numérico puro
    const rawPrice15minUnmasked = price15minInput && price15minInput.inputmask ? price15minInput.inputmask.unmaskedvalue() : '';
    const rawPrice30minUnmasked = price30minInput && price30minInput.inputmask ? price30minInput.inputmask.unmaskedvalue() : '';
    const rawPrice1hUnmasked = price1hInput && price1hInput.inputmask ? price1hInput.inputmask.unmaskedvalue() : '';

    console.log('DEBUG JS: Unmasked Price 15min (string):', rawPrice15minUnmasked);
    console.log('DEBUG JS: Unmasked Price 30min (string):', rawPrice30minUnmasked);
    console.log('DEBUG JS: Unmasked Price 1h (string):', rawPrice1hUnmasked);

    // Converter para float. O replace(',', '.') já é feito pelo onUnMask do Inputmask,
    // mas aqui garantimos que seja um número para a validação.
    const rawPrice15min = parseFloat(rawPrice15minUnmasked);
    const rawPrice30min = parseFloat(rawPrice30minUnmasked);
    const rawPrice1h = parseFloat(rawPrice1hUnmasked);

    console.log('DEBUG JS: Parsed Price 15min (float):', rawPrice15min);
    console.log('DEBUG JS: Parsed Price 30min (float):', rawPrice30min);
    console.log('DEBUG JS: Parsed Price 1h (float):', rawPrice1h);


    if ((isNaN(rawPrice15min) || rawPrice15min <= 0) &&
        (isNaN(rawPrice30min) || rawPrice30min <= 0) &&
        (isNaN(rawPrice1h) || rawPrice1h <= 0)) {
        if (pricesFeedback) {
            pricesFeedback.textContent = 'Pelo menos um preço deve ser preenchido com um valor maior que zero.';
            pricesFeedback.style.display = 'block';
        }
        isValidPrices = false;
        if (price15minInput) price15minInput.classList.add('is-invalid');
        if (price30minInput) price30minInput.classList.add('is-invalid');
        if (price1hInput) price1hInput.classList.add('is-invalid');
    } else {
        if (pricesFeedback) {
            pricesFeedback.textContent = '';
            pricesFeedback.style.display = 'none';
        }
        if (price15minInput) price15minInput.classList.remove('is-invalid');
        if (price30minInput) price30minInput.classList.remove('is-invalid');
        if (price1hInput) price1hInput.classList.remove('is-invalid');
    }

    let isValidMedia = true;
    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const coverPhotoInput = document.getElementById('foto_capa_input');
    const confirmationVideoFeedback = document.getElementById('confirmationVideo-feedback');
    const coverPhotoFeedback = document.getElementById('coverPhoto-feedback');

    const hasNewConfirmationVideo = confirmationVideoInput?.files?.length > 0;
    const existingConfirmationVideoPathInput = document.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]');
    const hasExistingConfirmationVideo = existingConfirmationVideoPathInput && existingConfirmationVideoPathInput.value !== '';
    const confirmationVideoRemoved = document.getElementById('confirmation_video_removed')?.value === 'true';

    if ((!hasNewConfirmationVideo && !hasExistingConfirmationVideo) &&
        (form.dataset.formMode === 'create' || confirmationVideoRemoved)) {
        if (confirmationVideoFeedback) {
            confirmationVideoFeedback.textContent = 'O vídeo de confirmação é obrigatório.';
            confirmationVideoFeedback.style.display = 'block';
        }
        document.getElementById('confirmationVideoUploadBox').classList.add('is-invalid-media');
        isValidMedia = false;
    } else {
        if (confirmationVideoFeedback) {
            confirmationVideoFeedback.textContent = '';
            confirmationVideoFeedback.style.display = 'none';
        }
        document.getElementById('confirmationVideoUploadBox').classList.remove('is-invalid-media');
    }

    const hasNewCoverPhoto = coverPhotoInput?.files?.length > 0;
    const existingCoverPhotoPathInput = document.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]');
    const hasExistingCoverPhoto = existingCoverPhotoPathInput && existingCoverPhotoPathInput.value !== '';
    const coverPhotoRemoved = document.getElementById('cover_photo_removed')?.value === 'true';

    if ((!hasNewCoverPhoto && !hasExistingCoverPhoto) &&
        (form.dataset.formMode === 'create' || coverPhotoRemoved)) {
        if (coverPhotoFeedback) {
            coverPhotoFeedback.textContent = 'A foto da capa é obrigatória.';
            coverPhotoFeedback.style.display = 'block';
        }
        document.getElementById('coverPhotoUploadBox').classList.add('is-invalid-media');
        isValidMedia = false;
    } else {
        if (coverPhotoFeedback) {
            coverPhotoFeedback.textContent = '';
            coverPhotoFeedback.style.display = 'none';
        }
        document.getElementById('coverPhotoUploadBox').classList.remove('is-invalid-media');
    }
let currentValidGalleryPhotos = 0;
    document.querySelectorAll('.gallery-upload-box').forEach((box) => {
        const input = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = input && input.files.length > 0; // Check if input exists before accessing files

        // Se o box tem conteúdo (existente ou novo), incrementa o contador
        if (hasExisting || isNew) {
            currentValidGalleryPhotos++;
        }
        console.log(`DEBUG JS: Galeria - Slot (box): hasExisting=${hasExisting}, isNew=${isNew}, currentValidGalleryPhotos=${currentValidGalleryPhotos}`);
    });

    const minPhotosRequired = 1;
    const freePhotoLimit = 2; // FREE: até 2 fotos
    const basicPhotoLimit = 20; // BASIC: até 20 fotos
    const premiumPhotoLimit = 20; // PREMIUM: até 20 fotos
    
    // Usar o userPlanType correto em vez do form.dataset.userPlanType
    const userPlanType = form.dataset.userPlanType; // Usar o valor real do banco
    
    console.log('DEBUG JS: Galeria - Validação detalhada:');
    console.log('DEBUG JS: Galeria - currentValidGalleryPhotos:', currentValidGalleryPhotos);
    console.log('DEBUG JS: Galeria - userPlanType:', userPlanType);
    console.log('DEBUG JS: Galeria - Limites: FREE=' + freePhotoLimit + ', BASIC=' + basicPhotoLimit + ', PREMIUM=' + premiumPhotoLimit);

    const galleryFeedbackElement = document.getElementById('galleryPhotoContainer-feedback');

    if (currentValidGalleryPhotos < minPhotosRequired) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Mínimo de ${minPhotosRequired} foto(s) na galeria é obrigatório.`;
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'free' && currentValidGalleryPhotos > freePhotoLimit) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Seu plano FREE permite apenas ${freePhotoLimit} fotos na galeria.`;
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'basic' && currentValidGalleryPhotos > basicPhotoLimit) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Seu plano BASIC permite no máximo ${basicPhotoLimit} fotos na galeria.`;
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'premium' && currentValidGalleryPhotos > premiumPhotoLimit) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Seu plano PREMIUM permite no máximo ${premiumPhotoLimit} fotos na galeria.`;
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = '';
            galleryFeedbackElement.style.display = 'none';
        }
    }

    const videoFeedbackElement = document.getElementById('videoUploadBoxes-feedback');

    const currentValidVideos = Array.from(document.querySelectorAll('.video-upload-box')).filter(box => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name^="existing_"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = fileInput && fileInput.files.length > 0;
        return isNew || hasExisting;
    }).length;

    if (userPlanType === 'free' && currentValidVideos > 0) {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = 'Vídeos não são permitidos no plano FREE.';
            videoFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'basic' && currentValidVideos > 0) {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = 'Vídeos não são permitidos no plano BASIC.';
            videoFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'premium' && currentValidVideos > 3) {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = 'Seu plano PREMIUM permite no máximo 3 vídeos.';
            videoFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = '';
            videoFeedbackElement.style.display = 'none';
        }
    }

    const audioFeedbackElement = document.getElementById('audioUploadBoxes-feedback');

    const currentValidAudios = Array.from(document.querySelectorAll('.audio-upload-box')).filter(box => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name^="existing_"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = fileInput && fileInput.files.length > 0;
        return isNew || hasExisting;
    }).length;

    if (userPlanType === 'free' && currentValidAudios > 0) {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = 'Áudios não são permitidos no plano FREE.';
            audioFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'basic' && currentValidAudios > 0) {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = 'Áudios não são permitidos no plano BASIC.';
            audioFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (userPlanType === 'premium' && currentValidAudios > 3) {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = 'Seu plano PREMIUM permite no máximo 3 áudios.';
            audioFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = '';
            audioFeedbackElement.style.display = 'none';
        }
    }

    console.log('DEBUG JS: Validação Personalizada - isValidCheckboxes:', isValidCheckboxes);
    console.log('DEBUG JS: Validação Personalizada - isValidPrices:', isValidPrices);
    console.log('DEBUG JS: Validação Personalizada - isValidMedia:', isValidMedia);


    if (!isValidCheckboxes || !isValidPrices || !isValidMedia) {
        console.warn('AVISO JS: Validação personalizada falhou. O formulário NÃO será enviado.');
        const firstInvalidElement = form.querySelector('.is-invalid, .is-invalid-media, .is-invalid-group');
        if (firstInvalidElement) {
            firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário.', 'Erro de Validação!');
        return;
    }

    console.log('DEBUG JS: Todas as validações passaram. Preparando para enviar o formulário via AJAX.');
    submitAnuncioForm(form);
}

/**
 * Envia o formulário de anúncio via AJAX.
 * @param {HTMLFormElement} form O formulário a ser enviado.
 */
async function submitAnuncioForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonHTML = window.activateButtonLoading(submitButton, 'Salvando...');

    window.showLoadingModal();

    // Inicia um novo FormData para ter controle total sobre os campos
    const formData = new FormData();

    // Adiciona todos os campos do formulário, exceto os de arquivo e os hidden de path existentes/removidos
    // que serão tratados manualmente para garantir os nomes corretos.
    Array.from(form.elements).forEach(element => {
        // Exclui inputs de tipo 'file' e os hidden de paths existentes/removidos
        if (element.name && element.type !== 'file' &&
            !element.name.startsWith('existing_gallery_paths') &&
            !element.name.startsWith('existing_video_paths') &&
            !element.name.startsWith('existing_audio_paths') &&
            !element.name.startsWith('removed_gallery_paths') && // Se você tiver esses campos
            !element.name.startsWith('removed_video_paths') &&   // Se você tiver esses campos
            !element.name.startsWith('removed_audio_paths') &&   // Se você tiver esses campos
            element.name !== 'existing_cover_photo_path' &&
            element.name !== 'cover_photo_removed' &&
            element.name !== 'existing_confirmation_video_path' &&
            element.name !== 'confirmation_video_removed'
        ) {
            if (element.type === 'checkbox' || element.type === 'radio') {
                if (element.checked) {
                    formData.append(element.name, element.value);
                }
            } else if (element.tagName === 'SELECT' && element.multiple) {
                Array.from(element.options).filter(option => option.selected).forEach(option => {
                    formData.append(element.name, option.value);
                });
            } else {
                formData.append(element.name, element.value);
            }
        }
    });

    // Adiciona o modo do formulário
    formData.append('form_mode', form.dataset.formMode);

    // Adiciona o ID do usuário logado, se disponível no dataset do body
    const userId = document.body.dataset.userId;
    if (userId) {
        formData.append('user_id', userId);
        console.log('DEBUG JS: Adicionando user_id ao FormData:', userId);
    } else {
        console.warn('AVISO JS: user_id não encontrado no dataset do body. O formulário pode falhar no backend.');
    }

    // --- Tratamento de campos com máscaras ---
    let heightInput = document.getElementById('height_m');
    if (heightInput && heightInput.inputmask) {
        formData.set('height_m', heightInput.inputmask.unmaskedvalue());
    } else if (heightInput) {
        formData.set('height_m', parseFloat(heightInput.value.replace(',', '.')).toFixed(2));
    }

    let weightInput = document.getElementById('weight_kg');
    if (weightInput && weightInput.inputmask) {
        formData.set('weight_kg', weightInput.inputmask.unmaskedvalue());
    } else if (weightInput) {
        formData.set('weight_kg', parseInt(weightInput.value, 10)); // Garante que seja um inteiro
    }

    const priceInputs = ['price_15min', 'price_30min', 'price_1h'];
    priceInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input && input.inputmask) {
            formData.set(id, input.inputmask.unmaskedvalue());
        } else if (input) {
            formData.set(id, input.value.replace('R$', '').replace(/\./g, '').replace(',', '.'));
        }
    });

    // --- Tratamento de Mídias Principais (Foto de Capa e Vídeo de Confirmação) ---
    const fotoCapaInput = document.getElementById('foto_capa_input');
    const existingCoverPhotoPathInput = form.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]');
    const coverPhotoRemovedInput = document.getElementById('cover_photo_removed');

    // Sempre anexa o caminho existente se ele existir (pode ser vazio para criação)
    if (existingCoverPhotoPathInput) {
        formData.append('existing_cover_photo_path', existingCoverPhotoPathInput.value || '');
        console.log('DEBUG JS: Sempre enviando caminho da foto de capa existente (pode ser vazio):', existingCoverPhotoPathInput.value || '');
    }
    // Sempre anexa a flag de remoção
    if (coverPhotoRemovedInput) {
        formData.append('cover_photo_removed', coverPhotoRemovedInput.value);
        console.log('DEBUG JS: Sempre enviando flag de remoção da foto de capa:', coverPhotoRemovedInput.value);
    }
    // Anexa o novo arquivo se presente (isso substituirá o nome 'foto_capa' se o backend lidar com isso)
    if (fotoCapaInput && fotoCapaInput.files.length > 0) {
        formData.append('foto_capa', fotoCapaInput.files[0]);
        console.log('DEBUG JS: Adicionando nova foto de capa:', fotoCapaInput.files[0].name);
    }


    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const existingConfirmationVideoPathInput = form.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]');
    const confirmationVideoRemovedInput = document.getElementById('confirmation_video_removed');

    // Sempre anexa o caminho existente se ele existir (pode ser vazio para criação)
    if (existingConfirmationVideoPathInput) {
        formData.append('existing_confirmation_video_path', existingConfirmationVideoPathInput.value || '');
        console.log('DEBUG JS: Sempre enviando caminho do vídeo de confirmação existente (pode ser vazio):', existingConfirmationVideoPathInput.value || '');
    }
    // Sempre anexa a flag de remoção
    if (confirmationVideoRemovedInput) {
        formData.append('confirmation_video_removed', confirmationVideoRemovedInput.value);
        console.log('DEBUG JS: Sempre enviando flag de remoção do vídeo de confirmação:', confirmationVideoRemovedInput.value);
    }
    // Anexa o novo arquivo se presente
    if (confirmationVideoInput && confirmationVideoInput.files.length > 0) {
        formData.append('confirmation_video', confirmationVideoInput.files[0]);
        console.log('DEBUG JS: Adicionando novo vídeo de confirmação:', confirmationVideoInput.files[0].name);
    }

    // --- Tratamento de Galerias (Fotos, Vídeos, Áudios) ---
    const galleryPhotoContainers = document.querySelectorAll('.gallery-upload-box');
    galleryPhotoContainers.forEach((box, index) => {
        const fileInput = box.querySelector(`input[name="fotos_galeria_upload_${index}"]`);
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');

        // Sempre anexa o caminho existente se ele existir (pode ser vazio para criação)
        if (existingPathInput) {
            formData.append('fotos_galeria_existing[]', existingPathInput.value || '');
            console.log(`DEBUG JS: Sempre enviando foto de galeria existente [${index}] (pode ser vazio):`, existingPathInput.value || '');
        }
        // Anexa o novo arquivo se presente
        if (fileInput && fileInput.files.length > 0) {
            formData.append('fotos_galeria[]', fileInput.files[0]);
            console.log(`DEBUG JS: Adicionando nova foto de galeria [${index}]:`, fileInput.files[0].name);
        }
    });

    const videoContainers = document.querySelectorAll('.video-upload-box');
    videoContainers.forEach((box, index) => {
        const fileInput = box.querySelector(`input[name="videos_upload_${index}"]`);
        const existingPathInput = box.querySelector('input[name="existing_video_paths[]"]');

        // Sempre anexa o caminho existente se ele existir (pode ser vazio para criação)
        if (existingPathInput) {
            formData.append('videos_existing[]', existingPathInput.value || '');
            console.log(`DEBUG JS: Sempre enviando vídeo de galeria existente [${index}] (pode ser vazio):`, existingPathInput.value || '');
        }
        // Anexa o novo arquivo se presente
        if (fileInput && fileInput.files.length > 0) {
            formData.append('videos[]', fileInput.files[0]);
            console.log(`DEBUG JS: Adicionando novo vídeo de galeria [${index}]:`, fileInput.files[0].name);
        }
    });

    const audioContainers = document.querySelectorAll('.audio-upload-box');
    audioContainers.forEach((box, index) => {
        const fileInput = box.querySelector(`input[name="audios_upload_${index}"]`);
        const existingPathInput = box.querySelector('input[name="existing_audio_paths[]"]');

        // Sempre anexa o caminho existente se ele existir (pode ser vazio para criação)
        if (existingPathInput) {
            formData.append('audios_existing[]', existingPathInput.value || '');
            console.log(`DEBUG JS: Sempre enviando áudio de galeria existente [${index}] (pode ser vazio):`, existingPathInput.value || '');
        }
        // Anexa o novo arquivo se presente
        if (fileInput && fileInput.files.length > 0) {
            formData.append('audios[]', fileInput.files[0]);
            console.log(`DEBUG JS: Adicionando novo áudio de galeria [${index}]:`, fileInput.files[0].name);
        }
    });

    // --- Adicionar flags de remoção para galerias ---
    // Se você tiver inputs hidden para sinalizar remoção de itens específicos da galeria,
    // eles devem ser adicionados aqui. Exemplo:
    // const removedGalleryPhotoInputs = document.querySelectorAll('input[name="removed_gallery_paths[]"]');
    // removedGalleryPhotoInputs.forEach(input => {
    //      if (input.value === 'true') {
    //          formData.append('removed_gallery_paths[]', input.dataset.originalPath); // Envia o caminho da foto removida
    //      }
    // });
    // Repita para vídeos e áudios se aplicável.

    console.log('DEBUG JS: Conteúdo FINAL do FormData antes do envio:');
    for (let pair of formData.entries()) {
        if (pair[1] instanceof File) {
            console.log(`     ${pair[0]}: File - ${pair[1].name} (${pair[1].type})`);
        } else {
            console.log(`     ${pair[0]}: ${pair[1]}`);
        }
    }

    try {
        const url = form.action;
        console.log(`DEBUG JS: Enviando formulário para: ${url}`);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        // AGORA ESPERAMOS O MODAL DE CARREGAMENTO SUMIR COMPLETAMENTE
        await window.hideLoadingModal();
        window.deactivateButtonLoading(submitButton, originalButtonHTML);

        console.log('INFO JS: Modal de carregamento ocultado. Mostrando modal de feedback.');

        if (result.success) {
            // Define autoCloseDelay para 2000ms (2 segundos)
            window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
            document.body.dataset.hasAnuncio = result.has_anuncio ? 'true' : 'false';
            document.body.dataset.anuncioStatus = result.anuncio_status || 'not_found';
            document.body.dataset.anuncioId = result.anuncio_id || '';
            window.updateAnuncioSidebarLinks();

            if (form.dataset.formMode === 'create' && result.anuncio_id) {
                // Para criação de anúncio, redirecionar para dashboard após o modal
                setTimeout(() => {
                    window.location.href = `${window.URLADM}dashboard`;
                }, 2000); // Aguarda 2 segundos para o usuário ver o modal
            } else if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1500);
            }
        } else {
            let errorMessage = result.message || 'Ocorreu um erro ao processar o anúncio.';
            if (result.errors) {
                for (const field in result.errors) {
                    const feedbackElement = document.getElementById(`${field}-feedback`);
                    if (feedbackElement) {
                        feedbackElement.textContent = result.errors[field];
                        feedbackElement.style.display = 'block';
                        const uploadBoxIdMap = {
                            'confirmation_video': 'confirmationVideoUploadBox',
                            'foto_capa': 'coverPhotoUploadBox',
                            'fotos_galeria': 'galleryPhotoContainer',
                            'videos': 'videoUploadBoxes',
                            'audios': 'audioUploadBoxes',
                            'aparencia': 'aparencia-checkboxes',
                            'idiomas': 'idiomas-checkboxes',
                            'locais_atendimento': 'locais_atendimento-checkboxes',
                            'formas_pagamento': 'formas_pagamento-checkboxes',
                            'servicos': 'servicos-checkboxes',
                            'precos': 'precos-feedback'
                        };
                        const targetElementId = uploadBoxIdMap[field] || field;
                        const targetElement = document.getElementById(targetElementId);

                        if (targetElement) {
                            if (targetElement.classList.contains('photo-upload-box') || targetElement.id === 'galleryPhotoContainer' || targetElement.id === 'videoUploadBoxes' || targetElement.id === 'audioUploadBoxes') {
                                targetElement.classList.add('is-invalid-media');
                            } else if (targetElement.classList.contains('row') && targetElement.id.endsWith('-checkboxes')) {
                                targetElement.classList.add('is-invalid-group');
                            } else if (targetElement.id === 'precos-feedback') {
                                // Não é necessário adicionar classe de erro diretamente ao feedback de preço, pois os inputs já são marcados.
                            }
                        }
                    } else {
                        errorMessage += `\n- ${result.errors[field]}`;
                    }
                }
            }
            const firstInvalidElement = form.querySelector('.is-invalid, .is-invalid-media, .is-invalid-group');
            if (firstInvalidElement) {
                firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            window.showFeedbackModal('error', errorMessage, 'Erro!');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX:', error);
        // Garante que o modal de carregamento seja ocultado mesmo em caso de erro de rede
        await window.hideLoadingModal();
        window.deactivateButtonLoading(submitButton, originalButtonHTML);
        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
    }
}

/**
 * Configura máscaras para campos de input.
 */
function setupInputMasks() {
    console.info('INFO JS: setupInputMasks - Aplicando máscaras nos inputs.');

    const phoneNumberInput = document.getElementById('phone_number');
    if (phoneNumberInput) {
        Inputmask({ "mask": "(99) 99999-9999" }).mask(phoneNumberInput);
    }

    const ageInput = document.getElementById('age');
    if (ageInput) {
        Inputmask("99", { numericInput: true, placeholder: "" }).mask(ageInput);
    }

    const heightInput = document.getElementById('height_m');
    if (heightInput) {
        Inputmask({
            mask: "9,99", // Permite um dígito antes da vírgula e dois depois (ex: 1,89)
            numericInput: false,
            placeholder: "0,00",
            rightAlign: false,
            onBeforeMask: function (value, opts) {
                if (value !== null && value !== undefined && value !== '') {
                    let stringValue = String(value);
                    // Substitui ponto por vírgula para exibição da máscara
                    stringValue = stringValue.replace('.', ',');

                    // Preenche com zeros se necessário para a máscara exibir corretamente
                    if (stringValue.match(/^\d$/)) { // Ex: "1" -> "1,00"
                        stringValue += ',00';
                    } else if (stringValue.match(/^\d,$/)) { // Ex: "1," -> "1,00"
                        stringValue += '00';
                    } else if (stringValue.match(/^\d,\d$/)) { // Ex: "1,8" -> "1,80"
                        stringValue += '0';
                    } else if (stringValue.match(/^,\d$/)) { // Ex: ",8" -> "0,80"
                        stringValue = '0' + stringValue + '0';
                    } else if (stringValue.match(/^,\d\d$/)) { // Ex: ",89" -> "0,89"
                        stringValue = '0' + stringValue;
                    }
                    console.debug(`DEBUG JS: height_m onBeforeMask - Valor de entrada: ${value}, Valor formatado para máscara: ${stringValue}`);
                    return stringValue;
                }
                console.debug(`DEBUG JS: height_m onBeforeMask - Valor de entrada vazio/nulo ou inválido. Retornando valor original.`);
                return value;
            },
            onUnMask: function (maskedValue, unmaskedValue) {
                // Para máscara "9,99", unmaskedValue para "1,89" é "189". Divide por 100.
                let num = parseFloat(unmaskedValue) / 100;
                return num.toFixed(2); // Fixa em 2 casas decimais
            }
        }).mask(heightInput);
    }

    const weightInput = document.getElementById('weight_kg');
    if (weightInput) {
        Inputmask({
            mask: "999", // Permite até 3 dígitos, sem casas decimais
            numericInput: true, // Garante que apenas números sejam aceitos e ajusta o comportamento
            placeholder: "", // Sem placeholder para números inteiros
            rightAlign: false,
            clearMaskOnLostFocus: false,
            onBeforeMask: function (value, opts) {
                // Remove qualquer caractere não numérico
                return String(value).replace(/\D/g, '');
            },
            onUnMask: function (maskedValue, unmaskedValue) {
                // Retorna o valor "não mascarado" como uma string de inteiro
                return unmaskedValue;
            }
        }).mask(weightInput);
    }
const priceInputs = ['price_15min', 'price_30min', 'price_1h'];
    priceInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            Inputmask({
                alias: 'numeric',
                groupSeparator: '.',
                radixPoint: ',',
                autoGroup: true,
                digits: 2,
                digitsOptional: false,
                prefix: 'R$ ',
                placeholder: "0,00",
                rightAlign: false,
                clearMaskOnLostFocus: false,
                onBeforeMask: function (value, opts) {
                    const cleanedValue = String(value).replace(/[R$\s.]/g, '').replace(',', '.');
                    return cleanedValue;
                },
                onUnMask: function (maskedValue, unmaskedValue) {
                    return parseFloat(unmaskedValue.replace(',', '.')).toFixed(2);
                }
            }).mask(input);
        }
    });
}

/**
 * Carrega e popula os selects de UF, Cidade e Bairro.
 * @param {object} anuncioData Dados do anúncio para pré-selecionar valores.
 * @returns {Promise<void>} Uma promessa que resolve quando as localizações são carregadas e pré-preenchidas.
 */
async function loadAndPopulateLocations(anuncioData) {
    const ufSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const neighborhoodInput = document.getElementById('neighborhood_name'); // É um input de texto, não select

    if (!ufSelect || !citySelect || !neighborhoodInput) {
        console.warn('AVISO JS: Elementos de localização (state_id, city_id, neighborhood_name) não encontrados. Pulando carga de localização.');
        return;
    }

    const initialUf = anuncioData?.state_uf;
    const initialCityCode = anuncioData?.city_code;
    const initialNeighborhoodName = anuncioData?.neighborhood_name; // Agora é para o input de texto

    console.log('DEBUG JS: loadAndPopulateLocations - Initial UF:', initialUf);
    console.log('DEBUG JS: loadAndPopulateLocations - Initial City Code:', initialCityCode);
    console.log('DEBUG JS: loadAndPopulateLocations - Initial Neighborhood Name:', initialNeighborhoodName);

    try {
        const statesUrl = `${window.projectBaseURL}app/adms/assets/js/data/states.json`;
        console.log('DEBUG JS: Fetching states from URL:', statesUrl);
        const responseStates = await fetch(statesUrl);

        if (!responseStates.ok) {
            const errorText = await responseStates.text();
            console.error(`ERRO JS: loadAndPopulateLocations - Falha ao carregar estados. Status: ${responseStates.status}, Texto: ${errorText}`);
            throw new Error(`Falha ao carregar estados: ${responseStates.statusText}`);
        }
        const dataStates = await responseStates.json();

        if (!dataStates || !dataStates.data || !Array.isArray(dataStates.data)) {
            console.error('ERRO JS: loadAndPopulateLocations - Estrutura de dados de estados inválida:', dataStates);
            throw new Error('Estrutura de dados de estados inválida.');
        }

        ufSelect.innerHTML = '<option value="">Selecione um Estado</option>';
        dataStates.data.forEach(uf => {
            const option = document.createElement('option');
            option.value = uf.Uf;
            option.textContent = uf.Nome;
            ufSelect.appendChild(option);
        });
        console.log('INFO JS: Estados carregados e populados.');

        const loadCitiesForUf = (uf) => new Promise(async (resolve, reject) => {
            citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
            citySelect.disabled = true;
            neighborhoodInput.value = ''; // Limpa o bairro ao mudar de cidade
            neighborhoodInput.disabled = true;

            if (uf) {
                try {
                    const citiesUrl = `${window.projectBaseURL}app/adms/assets/js/data/cities.json`;
                    console.log('DEBUG JS: Fetching cities from URL:', citiesUrl);
                    const responseCities = await fetch(citiesUrl);

                    if (!responseCities.ok) {
                        const errorText = await responseCities.text();
                        console.error(`ERRO JS: loadAndPopulateLocations - Falha ao carregar cidades para UF ${uf}. Status: ${responseCities.status}, Texto: ${errorText}`);
                        throw new Error(`Falha ao carregar cidades: ${responseCities.statusText}`);
                    }
                    const dataCities = await responseCities.json();

                    if (!dataCities || !dataCities.data || !Array.isArray(dataCities.data)) {
                        console.error('ERRO JS: loadAndPopulateLocations - Estrutura de dados de cidades inválida:', dataCities);
                        throw new Error('Estrutura de dados de cidades inválida.');
                    }

                    const cities = dataCities.data.filter(city => city.Uf === uf);

                    citySelect.innerHTML = '<option value="">Selecione uma Cidade</option>';
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.Codigo; // Use Codigo do IBGE
                        option.textContent = city.Nome;
                        citySelect.appendChild(option);
                    });
                    citySelect.disabled = false;
                    console.log(`INFO JS: Cidades para UF ${uf} carregadas e populadas.`);
                    resolve();
                } catch (error) {
                    console.error('ERRO JS: Erro ao carregar cidades:', error);
                    citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                    reject(error);
                }
            } else {
                citySelect.innerHTML = '<option value="">Selecione uma Cidade</option>';
                resolve();
            }
        });

        // Remove listeners antigos para evitar duplicação em navegações SPA
        if (ufSelect._changeHandler) ufSelect.removeEventListener('change', ufSelect._changeHandler);
        if (citySelect._changeHandler) citySelect.removeEventListener('change', citySelect._changeHandler);

        // Adiciona os event listeners e armazena as referências
        const ufChangeHandler = async function() {
            const selectedUf = this.value;
            await loadCitiesForUf(selectedUf);
            // Se o estado foi alterado, limpa a cidade e o bairro
            citySelect.value = '';
            neighborhoodInput.value = '';
            neighborhoodInput.disabled = true;
            // Se houver um initialCityCode e o estado selecionado for o initialUf, tenta pré-selecionar a cidade
            if (initialCityCode && selectedUf === initialUf) {
                citySelect.value = initialCityCode;
                // Dispara o evento change para que o listener da cidade seja acionado
                const event = new Event('change');
                citySelect.dispatchEvent(event);
            }
        };
        ufSelect.addEventListener('change', ufChangeHandler);
        ufSelect._changeHandler = ufChangeHandler;

        const cityChangeHandler = function() {
            const selectedCityCode = this.value;
            if (selectedCityCode) {
                neighborhoodInput.disabled = false;
                neighborhoodInput.placeholder = "Digite o nome do Bairro";
            } else {
                neighborhoodInput.disabled = true;
                neighborhoodInput.placeholder = "Selecione a Cidade primeiro";
                neighborhoodInput.value = ''; // Limpa o bairro se a cidade for deselecionada
            }
            // Se houver um initialNeighborhoodName e a cidade selecionada for a initialCityCode, tenta pré-preencher o bairro
            if (initialNeighborhoodName && selectedCityCode === initialCityCode) {
                neighborhoodInput.value = initialNeighborhoodName;
            }
        };
        citySelect.addEventListener('change', cityChangeHandler);
        citySelect._changeHandler = cityChangeHandler;

        // Lógica de pré-seleção na carga inicial
        if (initialUf) {
            ufSelect.value = initialUf;
            await loadCitiesForUf(initialUf);
            if (initialCityCode) {
                citySelect.value = initialCityCode;
                // Dispara o evento change para que o listener da cidade seja acionado
                const event = new Event('change');
                citySelect.dispatchEvent(event);
                // O bairro já deve ter sido preenchido em initializeFormFields, mas garantir que esteja habilitado
                if (initialNeighborhoodName) {
                    neighborhoodInput.disabled = false;
                    neighborhoodInput.placeholder = "Digite o nome do Bairro";
                }
            }
        }

    } catch (error) {
        console.error('ERRO JS: Erro geral ao carregar localizações:', error);
        if (ufSelect) ufSelect.innerHTML = '<option value="">Erro ao carregar estados</option>';
        if (citySelect) citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
        window.showFeedbackModal('error', 'Não foi possível carregar os dados de localização. Verifique os caminhos dos arquivos JSON e os logs do navegador.', 'Erro de Localização');
        throw error;
    }
    console.log('INFO JS: loadAndPopulateLocations - Localização carregada e populada.');
}


/**
 * Inicializa os campos do formulário com dados existentes (para edição) ou limpa para criação.
 * @param {HTMLFormElement} form O formulário principal.
 * @param {object} anuncioData Dados do anúncio para pré-preenchimento.
 * @param {string} formMode Modo do formulário ('create' ou 'edit').
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function initializeFormFields(form, anuncioData, formMode, userPlanType) {
    console.info('INFO JS: initializeFormFields - Inicializando campos do formulário.');

    const formTitleElement = document.getElementById('formAnuncioTitle');
    if (formTitleElement) {
        // Esta parte já é controlada por initializeAnuncioFormPage
        // formTitleElement.textContent = formMode === 'edit' ? 'Editar Anúncio' : 'Criar Novo Anúncio';
    }

    const textAndNumberFields = [
        'service_name', 'age', 'phone_number', 'description',
        'gender', 'nationality', 'ethnicity', 'eye_color', 'neighborhood_name' // Adicionado neighborhood_name aqui
    ];

    textAndNumberFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && anuncioData?.[field] !== undefined && anuncioData?.[field] !== null) {
            input.value = String(anuncioData[field]);
            console.log(`DEBUG JS: Campo ${field} preenchido com: ${input.value}`);
        } else if (input && formMode === 'create') {
            input.value = '';
            console.log(`DEBUG JS: Campo ${field} limpo para criação.`);
        }
    });

    const heightInput = document.getElementById('height_m');
    if (heightInput && anuncioData?.height_m !== undefined && anuncioData?.height_m !== null) {
        // Para o campo de altura, garantir que o valor seja uma string para o inputmask
        // O onBeforeMask da máscara de altura cuidará da formatação para "X,YY"
        heightInput.value = String(anuncioData.height_m);
        console.log(`DEBUG JS: Campo height_m preenchido com: ${heightInput.value} (anuncioData original: ${anuncioData.height_m})`);
    } else if (heightInput && formMode === 'create') {
        heightInput.value = '';
        console.log(`DEBUG JS: Campo height_m limpo para criação.`);
    }

    const weightInput = document.getElementById('weight_kg');
    if (weightInput && anuncioData?.weight_kg !== undefined && anuncioData?.weight_kg !== null) {
        weightInput.value = String(anuncioData.weight_kg);
        console.log(`DEBUG JS: Campo weight_kg preenchido com: ${weightInput.value} (anuncioData: ${anuncioData.weight_kg})`);
    } else if (weightInput && formMode === 'create') {
        weightInput.value = '';
        console.log(`DEBUG JS: Campo weight_kg limpo para criação.`);
    }


    const priceFields = ['price_15min', 'price_30min', 'price_1h'];
    priceFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && anuncioData?.[field] !== undefined && anuncioData?.[field] !== null) {
            if (input.inputmask) {
                const numericValue = parseFloat(anuncioData[field]);
                if (!isNaN(numericValue)) {
                    // O inputmask espera um valor numérico para aplicar a máscara corretamente
                    // e o onBeforeMask cuidará da formatação para R$ X.XXX,XX
                    input.value = String(numericValue);
                } else {
                    input.value = '';
                }
            } else {
                // Fallback se inputmask não estiver presente
                input.value = parseFloat(anuncioData[field]).toFixed(2).replace('.', ',');
                console.warn(`AVISO JS: Inputmask não aplicado para ${field}. Valor formatado manualmente.`);
            }
            console.log(`DEBUG JS: Campo ${field} preenchido com: ${input.value} (anuncioData: ${anuncioData[field]})`);
        } else if (input && formMode === 'create') {
            input.value = '';
            console.log(`DEBUG JS: Campo ${field} limpo para criação.`);
        }
    });


    const checkboxGroups = {
        'aparencia[]': 'aparencia',
        'idiomas[]': 'idiomas',
        'locais_atendimento[]': 'locais_atendimento',
        'formas_pagamento[]': 'formas_pagamento',
        'servicos[]': 'servicos'
    };

    for (const name in checkboxGroups) {
        const dataKey = checkboxGroups[name];
        const checkboxes = form.querySelectorAll(`input[name="${name}"]`);
        const existingValues = anuncioData?.[dataKey] || [];

        checkboxes.forEach(checkbox => {
            checkbox.checked = existingValues.includes(checkbox.value);
        });
        console.log(`DEBUG JS: Checkboxes para ${dataKey} preenchidos. Valores existentes:`, existingValues);
    }

    const anuncioIdInput = form.querySelector('input[name="anuncio_id"]');
    if (anuncioIdInput) {
        anuncioIdInput.value = anuncioData?.id || '';
        console.log(`DEBUG JS: Campo anuncio_id preenchido com: ${anuncioIdInput.value}`);
    }

    const anuncianteUserIdInput = form.querySelector('input[name="anunciante_user_id"]'); // Adicionado para garantir que o ID do anunciante seja enviado
    if (anuncianteUserIdInput) {
        anuncianteUserIdInput.value = anuncioData?.user_id || '';
        console.log(`DEBUG JS: Campo anunciante_user_id preenchido com: ${anuncianteUserIdInput.value}`);
    }

    // Mídia principal (Vídeo de Confirmação e Foto da Capa)
    const confirmationVideoPreview = document.getElementById('confirmationVideoPreview');
    const coverPhotoPreview = document.getElementById('coverPhotoPreview');
    const confirmationVideoPlaceholder = document.querySelector('#confirmationVideoUploadBox .upload-placeholder');
    const coverPhotoPlaceholder = document.querySelector('#coverPhotoUploadBox .upload-placeholder');
    const confirmationVideoRemoveBtn = document.querySelector('#confirmationVideoUploadBox .btn-remove-photo');
    const coverPhotoRemoveBtn = document.querySelector('#coverPhotoUploadBox .btn-remove-photo');

    const existingConfirmationVideoPathInput = document.querySelector('input[name="existing_confirmation_video_path"]');
    const existingCoverPhotoPathInput = document.querySelector('input[name="existing_cover_photo_path"]');

    // Helper para verificar se o caminho é uma URL absoluta
    const isAbsolutePath = (path) => path && (path.startsWith('http://') || path.startsWith('https://'));

    if (formMode === 'edit') {
        console.log('DEBUG JS: Modo de edição. Tentando preencher mídias principais.');
        console.log(`DEBUG JS: anuncioData.confirmation_video_path: ${anuncioData?.confirmation_video_path}`);
        if (anuncioData?.confirmation_video_path && confirmationVideoPreview) {
            let videoUrl;
            if (isAbsolutePath(anuncioData.confirmation_video_path)) {
                videoUrl = anuncioData.confirmation_video_path;
            } else {
                videoUrl = `${window.projectBaseURL}${anuncioData.confirmation_video_path}`;
            }
            confirmationVideoPreview.src = videoUrl;
            confirmationVideoPreview.style.display = 'block';
            if (confirmationVideoPlaceholder) confirmationVideoPlaceholder.style.display = 'none';
            if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.classList.remove('d-none');
            if (existingConfirmationVideoPathInput) {
                existingConfirmationVideoPathInput.value = anuncioData.confirmation_video_path;
                existingConfirmationVideoPathInput.dataset.originalValue = anuncioData.confirmation_video_path; // Store original
            }
            console.log(`DEBUG JS: Vídeo de confirmação preenchido com URL: ${videoUrl}`);
        } else {
            console.log('DEBUG JS: Vídeo de confirmação não encontrado nos dados ou preview não existe. Limpando.');
            if (confirmationVideoPreview) confirmationVideoPreview.removeAttribute('src');
            if (confirmationVideoPreview) confirmationVideoPreview.style.display = 'none';
            if (confirmationVideoPlaceholder) confirmationVideoPlaceholder.style.display = 'flex';
            if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.classList.add('d-none');
            if (existingConfirmationVideoPathInput) {
                existingConfirmationVideoPathInput.value = '';
                existingConfirmationVideoPathInput.dataset.originalValue = ''; // Clear original
            }
        }

        console.log(`DEBUG JS: anuncioData.cover_photo_path: ${anuncioData?.cover_photo_path}`);
        if (anuncioData?.cover_photo_path && coverPhotoPreview) {
            let photoUrl;
            if (isAbsolutePath(anuncioData.cover_photo_path)) {
                photoUrl = anuncioData.cover_photo_path;
            } else {
                photoUrl = `${window.projectBaseURL}${anuncioData.cover_photo_path}`;
            }
            coverPhotoPreview.src = photoUrl;
            coverPhotoPreview.style.display = 'block';
            if (coverPhotoPlaceholder) coverPhotoPlaceholder.style.display = 'none';
            if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.classList.remove('d-none');
            if (existingCoverPhotoPathInput) {
                existingCoverPhotoPathInput.value = anuncioData.cover_photo_path;
                existingCoverPhotoPathInput.dataset.originalValue = anuncioData.cover_photo_path; // Store original
            }
            console.log(`DEBUG JS: Foto da capa preenchido com URL: ${photoUrl}`);
        } else {
            console.log('DEBUG JS: Foto da capa não encontrada nos dados ou preview não existe. Limpando.');
            if (coverPhotoPreview) coverPhotoPreview.removeAttribute('src');
            if (coverPhotoPreview) coverPhotoPreview.style.display = 'none';
            if (coverPhotoPlaceholder) coverPhotoPlaceholder.style.display = 'flex';
            if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.classList.add('d-none');
            if (existingCoverPhotoPathInput) {
                existingCoverPhotoPathInput.value = '';
                existingCoverPhotoPathInput.dataset.originalValue = '';
            }
        }
    } else { // Modo de criação: garantir que os campos de mídia estejam limpos
        console.log('DEBUG JS: Modo de criação. Limpando mídias principais.');
        if (confirmationVideoPreview) confirmationVideoPreview.removeAttribute('src');
        if (confirmationVideoPreview) confirmationVideoPreview.style.display = 'none';
        if (confirmationVideoPlaceholder) confirmationVideoPlaceholder.style.display = 'flex';
        if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.classList.add('d-none');
        if (existingConfirmationVideoPathInput) {
            existingConfirmationVideoPathInput.value = '';
            existingConfirmationVideoPathInput.dataset.originalValue = '';
        }

        if (coverPhotoPreview) coverPhotoPreview.removeAttribute('src');
        if (coverPhotoPreview) coverPhotoPreview.style.display = 'none';
        if (coverPhotoPlaceholder) coverPhotoPlaceholder.style.display = 'flex';
        if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.classList.add('d-none');
        if (existingCoverPhotoPathInput) {
            existingCoverPhotoPathInput.value = '';
            existingCoverPhotoPathInput.dataset.originalValue = '';
        }
    }

    const mediaMultiUploads = {
        gallery: { container: document.getElementById('galleryPhotoContainer'), dataKey: 'fotos_galeria', type: 'image' },
        video: { container: document.getElementById('videoUploadBoxes'), dataKey: 'videos', type: 'video' },
        audio: { container: document.getElementById('audioUploadBoxes'), dataKey: 'audios', type: 'audio' }
    };

    for (const key in mediaMultiUploads) {
        const { container, dataKey, type } = mediaMultiUploads[key];
        if (!container) {
            console.warn(`AVISO JS: Container para ${key} (ID: ${container?.id}) não encontrado. Pulando.`);
            continue;
        }

        const existingMediaArray = anuncioData?.[dataKey] || [];
        const boxes = container.querySelectorAll('.photo-upload-box');

        console.log(`DEBUG JS: Processando ${dataKey}. Dados existentes (anuncioData.${dataKey}):`, existingMediaArray);
        console.log(`DEBUG JS: Número de boxes encontrados para ${dataKey}:`, boxes.length);

        boxes.forEach((box, index) => {
            const preview = box.querySelector('.photo-preview') || box.querySelector('video') || box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            const existingPathInput = box.querySelector('input[name^="existing_"]'); // existing_gallery_paths[], etc.

            const currentMediaPath = existingMediaArray[index]; // Pega o caminho do array de dados

            console.log(`DEBUG JS: ${dataKey} - Slot ${index}: currentMediaPath =`, currentMediaPath);
            console.log(`DEBUG JS: ${dataKey} - Slot ${index}: existingPathInput =`, existingPathInput);

            if (formMode === 'edit' && currentMediaPath) {
                let mediaUrl;
                if (isAbsolutePath(currentMediaPath)) {
                    mediaUrl = currentMediaPath;
                } else {
                    mediaUrl = `${window.projectBaseURL}${currentMediaPath}`;
                }
                console.debug(`DEBUG JS: Carregando ${type} da galeria slot ${index}: ${currentMediaPath}. URL final: ${mediaUrl}`);
                if (preview) {
                    preview.src = mediaUrl;
                    preview.style.display = 'block';
                } else {
                    console.warn(`AVISO JS: Elemento de preview não encontrado para ${type} no slot ${index}.`);
                }
                if (placeholder) placeholder.style.display = 'none';
                if (removeBtn) removeBtn.classList.remove('d-none');
                if (existingPathInput) {
                    existingPathInput.value = currentMediaPath; // Mantém o caminho relativo no hidden input
                    existingPathInput.dataset.originalValue = currentMediaPath; // Store original
                }
            } else {
                console.debug(`DEBUG JS: Nenhum ${type} existente para slot ${index} ou modo de criação. Limpando.`);
                if (preview) preview.removeAttribute('src');
                if (preview) preview.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
                if (removeBtn) removeBtn.classList.add('d-none');
                if (existingPathInput) {
                    existingPathInput.value = '';
                    existingPathInput.dataset.originalValue = '';
                }
            }
        });
    }

    // applyPlanRestrictions(userPlanType); // Movido para o final de initializeAnuncioFormPage
}


/**
 * Configura os manipuladores de upload de arquivos (fotos, vídeos, áudios).
 * Esta função agora foca apenas em adicionar os event listeners.
 * @param {HTMLFormElement} form O formulário principal.
 * @param {object} anuncioData Dados do anúncio para pré-preenchimento.
 * @param {string} formMode Modo do formulário ('create' ou 'edit').
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function setupFileUploadHandlers(form, anuncioData, formMode, userPlanType) {
    console.info('INFO JS: setupFileUploadHandlers - Configurando event listeners para uploads de mídia.');

    // Helper para verificar se o caminho é uma URL absoluta (duplicado, mas necessário aqui para o escopo)
    const isAbsolutePath = (path) => path && (path.startsWith('http://') || path.startsWith('https://'));

    function setupSingleMediaInput(inputElement, previewElement, removeButton, removedHiddenInput, existingPathHiddenInput, type) {
        const uploadBox = inputElement.closest('.photo-upload-box');
        const placeholder = uploadBox.querySelector('.upload-placeholder');

        if (!inputElement || !previewElement || !removeButton || !uploadBox || !placeholder) {
            console.error('ERRO JS: Elementos de mídia não encontrados para setup de input único.', { inputElement, previewElement, removeButton, uploadBox, placeholder });
            return;
        }

        // Remove listeners antigos para evitar duplicação em navegações SPA
        if (inputElement._changeHandler) inputElement.removeEventListener('change', inputElement._changeHandler);
        if (removeButton._clickHandler) removeButton.removeEventListener('click', removeButton._clickHandler);
        if (uploadBox._clickHandler) uploadBox.removeEventListener('click', uploadBox._clickHandler);


        const clickHandler = function(event) {
            if (!uploadBox.classList.contains('locked') && !inputElement.disabled) {
                inputElement.click();
            } else {
                event.preventDefault();
                event.stopPropagation();
                window.showFeedbackModal('info', 'Este slot está bloqueado para o seu plano atual. Upgrade para Premium para desbloquear.', 'Acesso Restrito');
            }
        };
        uploadBox.addEventListener('click', clickHandler);
        uploadBox._clickHandler = clickHandler; // Armazena a referência para remoção futura
const changeHandler = function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeButton.classList.remove('d-none');
                    if (removedHiddenInput) removedHiddenInput.value = 'false';
                    if (existingPathHiddenInput) existingPathHiddenInput.value = ''; // Clear existing path, as new file is uploaded
                    console.debug(`DEBUG JS: Preview de ${type} atualizado com novo arquivo. Existing path cleared.`);
                    applyPlanRestrictions(userPlanType); // Reaplicar restrições após upload
                };
                if (type === 'image') {
                    reader.readAsDataURL(file);
                } else if (type === 'video' || type === 'audio') {
                    previewElement.src = URL.createObjectURL(file);
                    previewElement.load();
                }
            } else {
                // Se o input de arquivo foi limpo (ex: usuário selecionou e depois cancelou),
                // mas havia um caminho existente, ele deve ser restaurado no preview e no hidden input.
                const originalExistingPath = existingPathHiddenInput ? existingPathHiddenInput.dataset.originalValue : '';
                if (originalExistingPath) {
                    previewElement.src = isAbsolutePath(originalExistingPath) ? originalExistingPath : `${window.projectBaseURL}${originalExistingPath}`;
                    previewElement.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeButton.classList.remove('d-none');
                    if (removedHiddenInput) removedHiddenInput.value = 'false'; // Not removed, restored
                    if (existingPathHiddenInput) existingPathHiddenInput.value = originalExistingPath; // Restore original path
                    console.debug(`DEBUG JS: Seleção cancelada, restaurando mídia existente para ${type}.`);
                } else {
                    // Se não havia mídia existente, limpa tudo
                    previewElement.removeAttribute('src');
                    previewElement.style.display = 'none';
                    placeholder.style.display = 'flex';
                    removeButton.classList.add('d-none');
                    if (removedHiddenInput) removedHiddenInput.value = 'false'; // Not removed, just empty
                    if (existingPathHiddenInput) existingPathHiddenInput.value = ''; // Ensure it's empty
                    console.debug(`DEBUG JS: Seleção cancelada, nenhum mídia existente para restaurar.`);
                }
                applyPlanRestrictions(userPlanType); // Reaplicar restrições após limpar input
            }
        };
        inputElement.addEventListener('change', changeHandler);
        inputElement._changeHandler = changeHandler;

        const removeClickHandler = function(e) {
            e.stopPropagation();
            window.showConfirmModal('Remover Mídia', 'Tem certeza que deseja remover esta mídia?', () => {
                inputElement.value = ''; // Clear the file input
                previewElement.removeAttribute('src');
                previewElement.style.display = 'none';
                placeholder.style.display = 'flex';
                removeButton.classList.add('d-none');
                if (removedHiddenInput) {
                    removedHiddenInput.value = 'true';
                }
                if (existingPathHiddenInput) {
                    existingPathHiddenInput.value = ''; // Clear existing path, as it's removed
                }
                console.debug(`DEBUG JS: ${type} removido. Existing path cleared.`);
                applyPlanRestrictions(userPlanType); // Reaplicar restrições após remover
            });
        };
        removeButton.addEventListener('click', removeClickHandler);
        removeButton._clickHandler = removeClickHandler;
    }

    setupSingleMediaInput(
        document.getElementById('confirmation_video_input'),
        document.getElementById('confirmationVideoPreview'),
        document.querySelector('#confirmationVideoUploadBox .btn-remove-photo'),
        document.getElementById('confirmation_video_removed'),
        document.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]'),
        'video'
    );

    setupSingleMediaInput(
        document.getElementById('foto_capa_input'),
        document.getElementById('coverPhotoPreview'),
        document.querySelector('#coverPhotoUploadBox .btn-remove-photo'),
        document.getElementById('cover_photo_removed'),
        document.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]'),
        'image'
    );

    const mediaMultiUploads = {
        gallery: { container: document.getElementById('galleryPhotoContainer'), type: 'image' },
        video: { container: document.getElementById('videoUploadBoxes'), type: 'video' },
        audio: { container: document.getElementById('audioUploadBoxes'), type: 'audio' }
    };

    for (const key in mediaMultiUploads) {
        const { container, type } = mediaMultiUploads[key];
        if (!container) {
            console.warn(`AVISO JS: Container para ${key} (ID: ${container?.id}) não encontrado. Pulando.`);
            continue;
        }

        container.querySelectorAll('.photo-upload-box').forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('.photo-preview') || box.querySelector('video') || box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            const existingPathInput = box.querySelector('input[name^="existing_"]'); // existing_gallery_paths[], etc.
            const premiumLockOverlay = box.querySelector('.premium-lock-overlay');

            // Remove listeners antigos para evitar duplicação em navegações SPA
            if (box._clickHandler) box.removeEventListener('click', box._clickHandler);
            if (input && input._changeHandler) input.removeEventListener('change', input._changeHandler);
            if (removeBtn && removeBtn._clickHandler) removeBtn.removeEventListener('click', removeBtn._clickHandler);

            const clickHandler = function() {
                if (!premiumLockOverlay || premiumLockOverlay.style.display === 'none') {
                    if (input) input.click();
                } else {
                    window.showFeedbackModal('info', 'Este slot está bloqueado para o seu plano atual.', 'Acesso Restrito');
                }
            };
            box.addEventListener('click', clickHandler);
            box._clickHandler = clickHandler;

            if (input) { // Only add change listener if input exists
                const changeHandler = function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            placeholder.style.display = 'none';
                            removeBtn.classList.remove('d-none');
                            if (existingPathInput) existingPathInput.value = ''; // Clear existing path for new file
                            applyPlanRestrictions(userPlanType); // Reaplicar restrições
                        };
                        if (type === 'image') {
                            reader.readAsDataURL(file);
                        } else if (type === 'video' || type === 'audio') {
                            preview.src = URL.createObjectURL(file);
                            preview.load();
                        }
                    } else {
                        // If user cancels file selection for a gallery slot. Restore previous state.
                        const originalExistingPath = existingPathInput ? existingPathInput.dataset.originalValue : '';
                        if (originalExistingPath) {
                            preview.src = isAbsolutePath(originalExistingPath) ? originalExistingPath : `${window.projectBaseURL}${originalExistingPath}`;
                            preview.style.display = 'block';
                            placeholder.style.display = 'none';
                            removeBtn.classList.remove('d-none');
                            if (existingPathInput) existingPathInput.value = originalExistingPath; // Restore original path
                        } else {
                            preview.removeAttribute('src');
                            preview.style.display = 'none';
                            placeholder.style.display = 'flex';
                            removeBtn.classList.add('d-none');
                            if (existingPathInput) existingPathInput.value = ''; // Ensure it's empty
                        }
                        applyPlanRestrictions(userPlanType); // Reaplicar restrições
                    }
                };
                input.addEventListener('change', changeHandler);
                input._changeHandler = changeHandler;
            }

            if (removeBtn) { // Only add click listener if removeBtn exists
                const removeClickHandler = function(event) {
                    event.stopPropagation();
                    window.showConfirmModal('Remover Mídia', 'Tem certeza que deseja remover esta mídia?', () => {
                        input.value = ''; // Clear the file input
                        preview.removeAttribute('src');
                        preview.style.display = 'none';
                        placeholder.style.display = 'flex';
                        removeBtn.classList.add('d-none');
                        if (input) input.value = ''; // Check if input exists
                        if (existingPathInput) existingPathInput.value = ''; // Clear existing path for removal
                        applyPlanRestrictions(userPlanType); // Reaplicar restrições
                    });
                };
                removeBtn.addEventListener('click', removeClickHandler);
                removeBtn._clickHandler = removeClickHandler;
            }
        });
    }
}

/**
 * Aplica restrições de plano para uploads de mídia (fotos, vídeos, áudios).
 * Bloqueia slots e exibe overlays para recursos não permitidos pelo plano.
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function applyPlanRestrictions(userPlanType) {
    console.info('INFO JS: Aplicando restrições de plano para mídias. Plano:', userPlanType);

    const galleryPhotoContainers = document.querySelectorAll('#galleryPhotoContainer .photo-upload-box');
    const videoUploadBoxes = document.querySelectorAll('#videoUploadBoxes .photo-upload-box');
    const audioUploadBoxes = document.querySelectorAll('#audioUploadBoxes .photo-upload-box');

    const freePhotoLimit = 2; // FREE: 1 foto capa + 2 fotos galeria
    const basicPhotoLimit = 20; // BASIC: 1 foto capa + 20 fotos galeria
    const premiumPhotoLimit = 20; // PREMIUM: 1 foto capa + 20 fotos galeria
    const premiumVideoAudioLimit = 3; // PREMIUM: 3 vídeos + 3 áudios

    let currentGalleryPhotosCount = 0;
    galleryPhotoContainers.forEach(box => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');
        const premiumLockOverlay = box.querySelector('.premium-lock-overlay');
        const inputElement = box.querySelector('input[type="file"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = fileInput && fileInput.files.length > 0;

        // Se o box tem conteúdo (existente ou novo), incrementa o contador
        if (hasExisting || isNew) {
            currentGalleryPhotosCount++;
        }

        // Resetar estado de bloqueio antes de aplicar novas regras
        box.classList.remove('locked');
        if (premiumLockOverlay) premiumLockOverlay.style.display = 'none';
        if (inputElement) inputElement.disabled = false; // Habilitar input por padrão

        // Lógica de bloqueio
        if (userPlanType === 'free') {
            // Para plano FREE, apenas os 2 primeiros slots de galeria são permitidos
            const boxIndex = Array.from(galleryPhotoContainers).indexOf(box);
            console.log(`DEBUG JS: Box ${boxIndex} - Plano: ${userPlanType}, Limite: ${freePhotoLimit}, Index: ${boxIndex}`);
            
            if (boxIndex >= freePhotoLimit) { // Se não for um dos 2 primeiros slots (0 e 1)
                box.classList.add('locked');
                if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                if (inputElement) inputElement.disabled = true;
                // Adicionar cursor not-allowed para indicar que está bloqueado
                box.style.cursor = 'not-allowed';
                box.style.opacity = '0.6';
                console.log(`DEBUG JS: Box ${boxIndex} BLOQUEADO para plano ${userPlanType}`);
            } else {
                // Para os slots liberados, garantir que estão habilitados
                box.classList.remove('locked');
                if (premiumLockOverlay) premiumLockOverlay.style.display = 'none';
                if (inputElement) inputElement.disabled = false;
                box.style.cursor = 'pointer';
                box.style.opacity = '1';
                console.log(`DEBUG JS: Box ${boxIndex} LIBERADO para plano ${userPlanType}`);
            }
        } else if (userPlanType === 'basic' || userPlanType === 'premium') {
            // Para planos BASIC e PREMIUM, todas as fotos são permitidas
            const boxIndex = Array.from(galleryPhotoContainers).indexOf(box);
            console.log(`DEBUG JS: Box ${boxIndex} - Plano: ${userPlanType}, Todas as fotos liberadas`);
            
            box.classList.remove('locked');
            if (premiumLockOverlay) premiumLockOverlay.style.display = 'none';
            if (inputElement) inputElement.disabled = false;
            box.style.cursor = 'pointer';
            box.style.opacity = '1';
            console.log(`DEBUG JS: Box ${boxIndex} LIBERADO para plano ${userPlanType}`);
        } else if (userPlanType === 'premium') {
            // Para plano PREMIUM, todas as fotos são permitidas (já tratado acima)
            // Esta condição nunca será executada devido ao `else if` anterior
        }
    });

    // Lógica para Vídeos e Áudios
    [videoUploadBoxes, audioUploadBoxes].forEach(mediaTypeBoxes => {
        let currentMediaCount = 0;
        mediaTypeBoxes.forEach(box => {
            const fileInput = box.querySelector('input[type="file"]');
            const existingPathInput = box.querySelector('input[name^="existing_"]');
            if ((existingPathInput && existingPathInput.value !== '') || (fileInput && fileInput.files.length > 0)) {
                currentMediaCount++;
            }
        });

        mediaTypeBoxes.forEach(box => {
            const premiumLockOverlay = box.querySelector('.premium-lock-overlay');
            const inputElement = box.querySelector('input[type="file"]');

            // Resetar estado de bloqueio
            box.classList.remove('locked');
            if (premiumLockOverlay) premiumLockOverlay.style.display = 'none';
            if (inputElement) inputElement.disabled = false;

            if (userPlanType === 'free') {
                // FREE: Permitir 1 vídeo de confirmação, bloquear áudios
                if (box.closest('#videoUploadBoxes')) {
                    // Para vídeos no plano FREE, permitir apenas 1
                    if (currentMediaCount >= 1 && !boxHasContent) {
                        box.classList.add('locked');
                        if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                        if (inputElement) inputElement.disabled = true;
                        box.style.cursor = 'not-allowed';
                        box.style.opacity = '0.6';
                    } else {
                        box.style.cursor = 'pointer';
                        box.style.opacity = '1';
                    }
                } else {
                    // Para áudios no plano FREE, bloquear completamente
                    box.classList.add('locked');
                    if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                    if (inputElement) inputElement.disabled = true;
                    box.style.cursor = 'not-allowed';
                    box.style.opacity = '0.6';
                }
            } else if (userPlanType === 'basic') {
                // BASIC: Bloquear vídeos da galeria e áudios
                if (box.closest('#videoUploadBoxes')) {
                    // Para vídeos no plano BASIC, bloquear completamente
                    box.classList.add('locked');
                    if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                    if (inputElement) inputElement.disabled = true;
                    box.style.cursor = 'not-allowed';
                    box.style.opacity = '0.6';
                } else {
                    // Para áudios no plano BASIC, bloquear completamente
                    box.classList.add('locked');
                    if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                    if (inputElement) inputElement.disabled = true;
                    box.style.cursor = 'not-allowed';
                    box.style.opacity = '0.6';
                }
            } else if (userPlanType === 'premium') {
                // Para plano PREMIUM, permitir vídeos e áudios (até 3 cada)
                const boxHasContent = (box.querySelector('input[name^="existing_"]') && box.querySelector('input[name^="existing_"]').value !== '') || (box.querySelector('input[type="file"]') && box.querySelector('input[type="file"]').files.length > 0);
                if (currentMediaCount >= premiumVideoAudioLimit && !boxHasContent) {
                    box.classList.add('locked');
                    if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                    if (inputElement) inputElement.disabled = true;
                    box.style.cursor = 'not-allowed';
                    box.style.opacity = '0.6';
                } else {
                    box.style.cursor = 'pointer';
                    box.style.opacity = '1';
                }
            }
        });
    });
}


/**
 * Configura a validação de checkboxes para grupos específicos.
 */
function setupCheckboxValidation() {
    const checkboxGroups = [
        { containerId: 'aparencia-checkboxes', feedbackId: 'aparencia-feedback', name: 'aparencia[]' },
        { containerId: 'idiomas-checkboxes', feedbackId: 'idiomas-feedback', name: 'idiomas[]' },
        { containerId: 'locais_atendimento-checkboxes', feedbackId: 'locais_atendimento-feedback', name: 'locais_atendimento[]' },
        { containerId: 'formas_pagamento-checkboxes', feedbackId: 'formas_pagamento-feedback', name: 'formas_pagamento[]' },
        { containerId: 'servicos[]', feedbackId: 'servicos-feedback', name: 'servicos[]' }
    ];

    checkboxGroups.forEach(group => {
        const container = document.getElementById(group.containerId);
        if (container) {
            const checkboxes = container.querySelectorAll(`input[name="${group.name}"]`);
            checkboxes.forEach(checkbox => {
                checkbox.removeEventListener('change', handleCheckboxChange);
                checkbox.addEventListener('change', handleCheckboxChange);
            });
        }
    });

    function handleCheckboxChange(event) {
        const checkbox = event.target;
        const groupContainer = checkbox.closest('.row'); // O container é a div.row
        if (groupContainer) {
            groupContainer.classList.remove('is-invalid-group');
            const feedbackId = groupContainer.id.replace('-checkboxes', '-feedback');
            const feedbackElement = document.getElementById(feedbackId);
            if (feedbackElement) {
                feedbackElement.textContent = '';
                feedbackElement.style.display = 'none';
            }
        }
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
    const btnVisualizar = document.getElementById('btnVisualizarAnuncio'); // NOVO: Botão Visualizar
    const btnDeleteAccount = document.getElementById('btnDeleteAccount'); // NOVO: Botão Excluir Conta

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
        window.toggleButtonState(btnApprove, canApprove);
        if (canApprove) {
            const handler = function() {
                window.showConfirmModal(
                    'Aprovar Anúncio',
                    'Tem certeza que deseja APROVAR este anúncio? Ele ficará ativo para o usuário.'
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
        const canReject = currentAnuncioStatus === 'pending' || currentAnuncioStatus === 'active';
        window.toggleButtonState(btnReject, canReject);
        if (canReject) {
            const handler = function() {
                window.showConfirmModal(
                    'Reprovar Anúncio',
                    'Tem certeza que deseja REPROVAR este anúncio? O usuário será notificado.'
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

    if (btnDelete) {
        window.toggleButtonState(btnDelete, true);
        const handler = function() {
            window.showConfirmModal(
                'Excluir Anúncio',
                'Tem certeza que deseja EXCLUIR este anúncio? Esta ação é irreversível.'
            ).then(result => {
                if (result) {
                    performAdminAction('delete', anuncioId, anuncianteUserId);
                }
            });
        };
        btnDelete.addEventListener('click', handler);
        btnDelete._clickHandler = handler;
    }

    if (btnActivate) {
        const canActivate = currentAnuncioStatus === 'inactive' || currentAnuncioStatus === 'pending';
        window.toggleButtonState(btnActivate, canActivate);
        if (canActivate) {
            const handler = function() {
                window.showConfirmModal(
                    'Ativar Anúncio',
                    'Tem certeza que deseja ATIVAR este anúncio? Ele voltará a ficar visível publicamente.'
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
        window.toggleButtonState(btnDeactivate, canDeactivate);
        if (canDeactivate) {
            const handler = function() {
                window.showConfirmModal(
                    'Pausar Anúncio',
                    'Tem certeza que deseja PAUSAR este anúncio? Ele não ficará visível publicamente.'
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

    // NOVO: Lógica para o botão "Visualizar Anúncio" para o administrador
    if (btnVisualizar) {
        window.toggleButtonState(btnVisualizar, true);
        btnVisualizar.href = `${window.URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`;
        btnVisualizar.dataset.spa = 'true';
        console.log(`DEBUG JS: btnVisualizarAnuncio configurado para admin. Href: ${btnVisualizar.href}`);
    }

    // NOVO: Lógica para o botão "Excluir Conta" do usuário (admin)
    if (btnDeleteAccount) {
        const handler = function (event) {
            event.preventDefault();
            const anuncianteUserIdFinal = btnDeleteAccount.dataset.anuncianteUserId || anuncianteUserId;

            if (typeof window.showConfirmModal === 'function') {
                window.showConfirmModal(
                    'Excluir Conta do Usuário',
                    "Tem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível."
                ).then(confirmed => {
                    if (confirmed) {
                        window.showLoadingModal();
                        fetch(`${window.URLADM}usuario/deleteAccount`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ user_id: anuncianteUserIdFinal })
                        })
                        .then(res => res.json())
                        .then(data => {
                            // Primeiro esconde o modal de loading
                            window.hideLoadingModal();
                            
                            // Aguarda um tempo maior para garantir que o modal de loading feche completamente
                            setTimeout(() => {
                                if (data.success) {
                                    window.showFeedbackModal('success', data.message || 'Conta excluída com sucesso!', 'Conta Excluída');
                                    setTimeout(() => {
                                        window.location.href = `${window.URLADM}dashboard`;
                                    }, 2000);
                                } else {
                                    window.showFeedbackModal('error', data.message || 'Erro ao excluir conta.', 'Erro');
                                }
                            }, 300); // Aumentado para 300ms
                        })
                        .catch(() => {
                            // Primeiro esconde o modal de loading
                            window.hideLoadingModal();
                            
                            // Aguarda um tempo maior para garantir que o modal de loading feche completamente
                            setTimeout(() => {
                                 window.showFeedbackModal('error', 'Erro ao excluir conta. Tente novamente.', 'Erro');
                            }, 300); // Aumentado para 300ms
                        });
                    }
                });
            } else {
                if (confirm("Tem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível.")) {
                    window.showLoadingModal();
                    fetch(`${window.URLADM}usuario/deleteAccount`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ user_id: anuncianteUserIdFinal })
                    })
                    .then(res => res.json())
                    .then(data => {
                        // Primeiro esconde o modal de loading
                        window.hideLoadingModal();
                        
                        // Aguarda um tempo maior para garantir que o modal de loading feche completamente
                        setTimeout(() => {
                            if (data.success) {
                                alert(data.message || 'Conta excluída com sucesso!');
                                setTimeout(() => {
                                    window.location.href = `${window.URLADM}dashboard`;
                                }, 2000);
                            } else {
                                alert(data.message || 'Erro ao excluir conta.');
                            }
                        }, 300); // Aumentado para 300ms
                    })
                    .catch(() => {
                        // Primeiro esconde o modal de loading
                        window.hideLoadingModal();
                        
                        // Aguarda um tempo maior para garantir que o modal de loading feche completamente
                        setTimeout(() => {
                            alert('Erro ao excluir conta. Tente novamente.');
                        }, 300); // Aumentado para 300ms
                    });
                }
            }
        };
        btnDeleteAccount.addEventListener('click', handler);
        btnDeleteAccount._clickHandler = handler;
    }
} // <--- A CHAVE QUE FALTAVA ESTAVA AQUI!

/**
 * Realiza a ação do administrador via AJAX.
 * @param {string} action Ação a ser realizada ('approve', 'reject', 'delete', 'activate', 'deactivate').
 * @param {string} anuncioId O ID do anúncio.
 * @param {string} anuncianteUserId O ID do usuário anunciante.
 */
async function performAdminAction(action, anuncioId, anuncianteUserId) {
    console.log(`DEBUG JS: Realizando ação do administrador: ${action} para anúncio ID: ${anuncioId}, usuário: ${anuncianteUserId}`);

    const url = `${window.URLADM}anuncio/${action}Anuncio`; // Ex: anuncio/approveAnuncio
    const formData = new FormData();
    formData.append('anuncio_id', anuncioId);
    formData.append('anunciante_user_id', anuncianteUserId); // Envia o user_id do anunciante

    window.showLoadingModal('Processando...');

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        await window.hideLoadingModal();

        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);

            // CORREÇÃO: Não atualiza o dataset do body para ações de admin
            // O dataset do body deve refletir apenas o status do próprio usuário logado
            // Para admin, não faz sentido atualizar com status do anunciante
            console.log('DEBUG JS: performAdminAction - Ação de admin realizada com sucesso. Não atualizando dataset do body.');

            // Se a ação foi exclusão, redireciona para a dashboard do admin
            if (action === 'delete') {
                setTimeout(() => {
                    if (typeof window.loadContent === 'function') {
                        window.loadContent(`${window.URLADM}dashboard`, 'dashboard');
                    } else {
                        console.error('ERRO JS: window.loadContent não está disponível para redirecionamento.');
                        window.location.href = `${window.URLADM}dashboard`;
                    }
                }, 1500);
            } else {
                setTimeout(() => {
                    if (typeof window.loadContent === 'function') {
                        window.loadContent(`${window.URLADM}anuncio/editarAnuncio?id=${anuncioId}`, 'anuncio/editarAnuncio');
                    } else {
                        console.error('ERRO JS: window.loadContent não está disponível para recarregar a página.');
                        window.location.reload();
                    }
                }, 1500);
            }

        } else {
            window.showFeedbackModal('error', result.message || 'Erro ao realizar a ação.', 'Erro!');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX de ação do administrador:', error);
        await window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
    }
}
