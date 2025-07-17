/**
 * Arquivo JavaScript para gerenciar o formulário de criação/edição de anúncios.
 * Inclui validações, manipulação de campos dinâmicos (estados/cidades/bairros),
 * pré-visualização de mídias e lógica de plano de usuário.
 */

// URLADM é definida globalmente pelo main.php e anexada a window.
// Usamos window.URLADM diretamente.
const URLADM = window.URLADM;
const URL = window.URL; // A URL base do projeto (necessária para manipular caminhos de mídia)
console.log('INFO JS: URLADM (global, vinda de main.php) em anuncio.js:', URLADM);
console.log('INFO JS: URL (global, URL base do projeto) em anuncio.js:', URL);


// --- Lógica da Sidebar (desativar/ativar links e texto do botão Pausar/Ativar) ---
// Esta função agora é global para ser chamada de dashboard_custom.js e diretamente
window.updateAnuncioSidebarLinks = function() {
    console.log('DEBUG JS: updateAnuncioSidebarLinks - Iniciado.');
    const navCriarAnuncio = document.getElementById('navCriarAnuncio');
    const navEditarAnuncio = document.getElementById('navEditarAnuncio');
    const navVisualizarAnuncio = document.getElementById('navVisualizarAnuncio');
    const navPausarAnuncio = document.getElementById('navPausarAnuncio');
    const navExcluirAnuncio = document.getElementById('navExcluirAnuncio');

    const body = document.body;
    const hasAnuncio = body.dataset.hasAnuncio === 'true';
    const anuncioStatus = body.dataset.anuncioStatus || ''; // Obtém o status do anúncio

    // Função auxiliar para aplicar/remover classes de desativação
    const toggleLinkDisabled = (element, isDisabled) => {
        if (element) {
            const link = element.querySelector('a');
            if (link) {
                if (isDisabled) {
                    element.classList.add('disabled');
                    link.style.pointerEvents = 'none'; // Desabilita clique
                    link.style.opacity = '0.6'; // Efeito visual
                } else {
                    element.classList.remove('disabled');
                    link.style.pointerEvents = 'auto';
                    link.style.opacity = '1';
                }
            }
        }
    };

    // Lógica para "Criar Anúncio"
    toggleLinkDisabled(navCriarAnuncio, hasAnuncio);

    // Lógica para "Editar Anúncio", "Visualizar Anúncio", "Excluir Anúncio"
    // Estes devem estar ativos SOMENTE se o usuário TIVER um anúncio
    toggleLinkDisabled(navEditarAnuncio, !hasAnuncio);
    toggleLinkDisabled(navVisualizarAnuncio, !hasAnuncio);
    toggleLinkDisabled(navExcluirAnuncio, !hasAnuncio);

    // Lógica para "Pausar/Ativar Anúncio"
    if (navPausarAnuncio) {
        const linkTextElement = navPausarAnuncio.querySelector('a span'); // Assume que o texto está dentro de um <span>
        if (hasAnuncio) {
            // Se o usuário tem um anúncio, o link está visualmente "ativo" (não disabled)
            toggleLinkDisabled(navPausarAnuncio, false); 
            if (anuncioStatus === 'active') {
                if (linkTextElement) linkTextElement.textContent = 'Pausar Anúncio';
                navPausarAnuncio.dataset.action = 'pause'; // Define a ação para o JS
                navPausarAnuncio.dataset.canInteract = 'true'; // Pode interagir
            } else if (anuncioStatus === 'inactive') {
                if (linkTextElement) linkTextElement.textContent = 'Ativar Anúncio';
                navPausarAnuncio.dataset.action = 'activate'; // Define a ação para o JS
                navPausarAnuncio.dataset.canInteract = 'true'; // Pode interagir
            } else if (anuncioStatus === 'pending') {
                if (linkTextElement) linkTextElement.textContent = 'Anúncio Pendente';
                navPausarAnuncio.dataset.action = 'info-pending'; // Ação para mostrar info
                navPausarAnuncio.dataset.canInteract = 'false'; // Não pode interagir com pause/activate
            } else if (anuncioStatus === 'rejected') {
                if (linkTextElement) linkTextElement.textContent = 'Anúncio Rejeitado';
                navPausarAnuncio.dataset.action = 'info-rejected'; // Ação para mostrar info
                navPausarAnuncio.dataset.canInteract = 'false'; // Não pode interagir com pause/activate
            } else {
                // Fallback para status desconhecido, desabilitado e genérico
                toggleLinkDisabled(navPausarAnuncio, true);
                if (linkTextElement) linkTextElement.textContent = 'Pausar/Ativar Anúncio';
                navPausarAnuncio.dataset.action = '';
                navPausarAnuncio.dataset.canInteract = 'false';
            }
        } else {
            // Se não houver anúncio, o link está desabilitado e com texto genérico
            toggleLinkDisabled(navPausarAnuncio, true); 
            if (linkTextElement) linkTextElement.textContent = 'Pausar/Ativar Anúncio'; 
            navPausarAnuncio.dataset.action = '';
            navPausarAnuncio.dataset.canInteract = 'false';
        }
    }


    console.log('INFO JS: Sidebar links atualizados. Has Anuncio:', hasAnuncio, 'Anuncio Status:', anuncioStatus);
};


// Define uma função global para inicializar a página de anúncio
// Esta função será chamada pelo dashboard_custom.js APÓS o conteúdo do formulário ser injetado via AJAX.
window.initializeAnuncioPage = function() {
    console.log('INFO JS: initializeAnuncioPage (Versão 32) - Iniciando inicialização do formulário.'); 

    const form = document.getElementById('formCriarAnuncio');
    // Para a página de visualização, o form pode não existir, mas o card-header e o título sim.
    // O form é usado para o data-form-mode e data-user-plan-type.
    // Se não for um form, vamos tentar ler o modo de outro lugar ou assumir 'view'.
    let formMode = 'view'; // Assume 'view' por padrão para páginas que não são formulários de submissão
    let userPlanType = 'free'; // Assume 'free' por padrão

    if (form) {
        formMode = form.dataset.formMode || 'create';
        userPlanType = form.dataset.userPlanType || 'free';
        console.log('DEBUG JS: Formulário encontrado. Modo:', formMode, 'Plano:', userPlanType);
    } else {
        console.warn('AVISO JS: Formulário com ID "formCriarAnuncio" não encontrado. Assumindo modo "view".');
    }

    // --- Lógica de Cores Dinâmicas para Título e Botão ---
    const cardHeader = document.querySelector('.card-header');
    const submitButton = document.getElementById('btnSubmitAnuncio');
    const formTitle = document.getElementById('formAnuncioTitle'); // Adicionado ID ao h5/h2 do título do formulário

    if (cardHeader && formTitle) { // Removido submitButton da condição pois ele pode não existir no modo 'view'
        console.log('DEBUG JS: Elementos de cabeçalho e título encontrados. Aplicando cores dinâmicas.');
        // Limpa classes de cores anteriores
        cardHeader.classList.remove('bg-info', 'bg-warning', 'bg-primary', 'text-dark', 'text-white', 'bg-secondary');
        if (submitButton) submitButton.classList.remove('btn-info', 'btn-warning', 'btn-primary', 'btn-secondary');

        switch (formMode) {
            case 'create':
                cardHeader.classList.add('bg-info', 'text-dark'); // Azul claro com texto escuro
                if (submitButton) submitButton.classList.add('btn-info');
                formTitle.textContent = 'Criar Anúncio';
                break;
            case 'edit':
                cardHeader.classList.add('bg-warning', 'text-white'); // Laranja com texto branco
                if (submitButton) submitButton.classList.add('btn-warning');
                formTitle.textContent = 'Editar Anúncio';
                break;
            case 'view': // Modo de visualização, sem botão de submit, mas com cores
                cardHeader.classList.add('bg-primary', 'text-white'); // Roxo com texto branco
                formTitle.textContent = 'Visualizar Anúncio';
                if (submitButton) submitButton.style.display = 'none'; // Esconde o botão de submit em modo de visualização
                break;
            default:
                // Fallback para modo padrão ou desconhecido
                cardHeader.classList.add('bg-secondary', 'text-white');
                if (submitButton) submitButton.classList.add('btn-secondary');
                formTitle.textContent = 'Anúncio';
                break;
        }
    } else {
        console.warn('AVISO JS: Elementos de cabeçalho/título do formulário de anúncio não encontrados para aplicar cores dinâmicas. (Botão de submit pode estar ausente no modo de visualização, o que é esperado).');
    }

    // --- Elementos do Formulário ---
    // Estes elementos só existem se for um formulário de fato (create/edit)
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const neighborhoodInput = document.getElementById('neighborhood_id'); 

    // Valores iniciais para pré-preenchimento (vindos do PHP via data-initial-value)
    const initialDataState = stateSelect ? stateSelect.dataset.initialValue : '';
    const initialDataCity = citySelect ? citySelect.dataset.initialValue : '';
    const initialDataNeighborhood = neighborhoodInput ? neighborhoodInput.dataset.initialValue : '';
    const nacionalidadeSelect = document.getElementById('nacionalidade'); 
    const initialDataNacionalidade = nacionalidadeSelect ? nacionalidadeSelect.dataset.initialValue : ''; 
    const etniaSelect = document.getElementById('etnia');
    const initialDataEtnia = etniaSelect ? etniaSelect.dataset.initialValue : '';
    const corOlhosSelect = document.getElementById('cor_olhos');
    const initialDataCorOlhos = corOlhosSelect ? corOlhosSelect.dataset.initialValue : '';


    const idadeInput = document.getElementById('idade');
    const descricaoSobreMimTextarea = document.getElementById('descricao_sobre_mim');

    const alturaInput = document.getElementById('altura');
    const pesoInput = document.getElementById('peso');

    const coverPhotoInput = document.getElementById('foto_capa_input');
    const coverPhotoPreview = document.getElementById('coverPhotoPreview');
    const coverPhotoUploadBox = document.getElementById('coverPhotoUploadBox');
    const coverPhotoPlaceholder = coverPhotoUploadBox ? coverPhotoUploadBox.querySelector('.upload-placeholder') : null;
    const coverPhotoRemoveBtn = coverPhotoUploadBox ? coverPhotoUploadBox.querySelector('.btn-remove-photo') : null;
    // const coverPhotoRemovedInput = document.getElementById('cover_photo_removed'); // Já é tratado genericamente

    // NOVO: Elementos para o vídeo de confirmação do usuário
    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const confirmationVideoPreview = document.getElementById('confirmationVideoPreview');
    const confirmationVideoUploadBox = document.getElementById('confirmationVideoUploadBox');
    const confirmationVideoPlaceholder = confirmationVideoUploadBox ? confirmationVideoUploadBox.querySelector('.upload-placeholder') : null;
    const confirmationVideoRemoveBtn = confirmationVideoUploadBox ? confirmationVideoUploadBox.querySelector('.btn-remove-photo') : null;
    // const confirmationVideoRemovedInput = document.getElementById('confirmation_video_removed'); // Já é tratado genericamente


    const galleryPhotoContainer = document.getElementById('galleryPhotoContainer');
    const galleryPhotoUploadBoxes = galleryPhotoContainer ? galleryPhotoContainer.querySelectorAll('.photo-upload-box') : [];

    const videoUploadBoxes = document.querySelectorAll('.video-upload-box');
    const audioUploadBoxes = document.querySelectorAll('.audio-upload-box');

    // Elementos da sidebar para a funcionalidade de Pausar Anúncio
    const navPausarAnuncioLink = document.querySelector('#navPausarAnuncio a');


    // --- Funções de Feedback de Validação ---

    /**
     * Exibe ou oculta feedback de validação para um elemento de formulário.
     * @param {HTMLElement} element O elemento input/select/textarea.
     * @param {string} message A mensagem de feedback a ser exibida.
     * @param {boolean} isInvalid Se o campo é inválido (true) ou válido (false).
     */
    const showFeedback = (element, message, isInvalid = true) => {
        if (!element) return;
        if (isInvalid) {
            element.classList.add('is-invalid');
            // Para photo-upload-box, adiciona classe específica
            if (element.closest('.photo-upload-box')) {
                element.closest('.photo-upload-box').classList.add('is-invalid-media');
            }
            const feedbackDiv = document.getElementById(element.id + '-feedback') || element.nextElementSibling;
            if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                feedbackDiv.textContent = message;
            }
        } else {
            element.classList.remove('is-invalid');
            if (element.closest('.photo-upload-box')) {
                element.closest('.photo-upload-box').classList.remove('is-invalid-media');
            }
            const feedbackDiv = document.getElementById(element.id + '-feedback') || element.nextElementSibling;
            if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                feedbackDiv.textContent = '';
            }
        }
    };

    /**
     * Exibe ou oculta feedback de validação para grupos de checkboxes.
     * @param {string} containerId O ID do container dos checkboxes (ex: 'aparencia').
     * @param {string} message A mensagem de feedback.
     * @param {boolean} isValid Se o grupo é válido (true) ou inválido (false).
     */
    const toggleCheckboxesFeedback = (containerId, message, isValid) => {
        const feedbackDiv = document.getElementById(containerId + '-feedback');
        if (feedbackDiv) {
            feedbackDiv.textContent = isValid ? '' : message;
            feedbackDiv.style.display = isValid ? 'none' : 'block';
            const firstCheckbox = document.querySelector(`#${containerId} input[type="checkbox"]`);
            if (firstCheckbox) {
                if (isValid) {
                    firstCheckbox.classList.remove('is-invalid');
                } else {
                    firstCheckbox.classList.add('is-invalid');
                }
            }
        }
    };

    // --- Máscaras de Input ---
    // Só aplica máscaras se estivermos em um modo de edição/criação onde os inputs são interativos
    if (formMode !== 'view') {
        const applyInputMasks = () => {
            if (alturaInput) {
                alturaInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9]/g, ''); 
                    if (value.length > 1 && !value.includes(',')) {
                        value = value.substring(0, 1) + ',' + value.substring(1);
                    }
                    if (value.length > 4) {
                        value = value.substring(0, 4);
                    }
                    e.target.value = value;
                });
                alturaInput.addEventListener('blur', function() {
                    if (this.value) {
                        let cleanedValue = this.value.replace(',', '.');
                        let floatValue = parseFloat(cleanedValue);
                        if (!isNaN(floatValue)) {
                            this.value = floatValue.toFixed(2).replace('.', ',');
                        } else {
                            this.value = ''; 
                        }
                    }
                });
            }

            if (pesoInput) {
                pesoInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); 
                    e.target.value = value.substring(0, 3); 
                });
                pesoInput.addEventListener('blur', function() {
                    if (this.value) {
                        this.value = parseInt(this.value, 10);
                        if (isNaN(this.value)) {
                            this.value = ''; 
                        }
                    }
                });
            }

            const precoInputs = form.querySelectorAll('input[name^="precos["]');
            precoInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); 
                    if (value.length === 0) {
                        e.target.value = '';
                        return;
                    }
                    value = (parseInt(value, 10) / 100).toFixed(2);
                    value = value.replace('.', ',');
                    e.target.value = value;
                });
                input.addEventListener('blur', function() {
                    if (this.value) {
                        let value = this.value;
                        if (value && !value.includes(',')) {
                            value += ',00';
                        } else if (value.endsWith(',')) {
                            value += '00';
                        } else if (value.includes(',')) {
                            const parts = value.split(',');
                            if (parts[1].length === 0) {
                                this.value += '00';
                            } else if (parts[1].length === 1) {
                                this.value += '0';
                            }
                        }
                        this.value = value;
                    }
                });
            });
        };
        applyInputMasks();
    }


    // --- Carregamento de Localização (Estados e Cidades de JSONs Locais) ---

    let statesData = [];
    let citiesData = [];

    async function loadLocationData() {
        console.log('DEBUG JS: loadLocationData - Iniciando carregamento de dados de localização.');
        if (!URLADM) {
            console.error('ERRO JS: URLADM não está definida. Não é possível carregar dados de localização.');
            window.showFeedbackModal('error', 'URL base da administração não definida. Recarregue a página.', 'Erro de Configuração');
            if (stateSelect) stateSelect.disabled = true;
            if (citySelect) citySelect.disabled = true;
            if (neighborhoodInput) neighborhoodInput.disabled = true;
            return;
        }

        try {
            const statesJsonUrl = `${URLADM}assets/js/data/states.json`;
            const citiesJsonUrl = `${URLADM}assets/js/data/cities.json`;

            console.log('INFO JS: Tentando buscar states.json de:', statesJsonUrl);
            console.log('INFO JS: Tentando buscar cities.json de:', citiesJsonUrl);

            const [statesResponse, citiesResponse] = await Promise.all([
                fetch(statesJsonUrl),
                fetch(citiesJsonUrl)
            ]);

            if (!statesResponse.ok) throw new Error(`HTTP error! status: ${statesResponse.status} for states.json`);
            if (!citiesResponse.ok) throw new Error(`HTTP error! status: ${citiesResponse.status} for cities.json`);

            const rawStatesData = await statesResponse.json();
            const rawCitiesData = await citiesResponse.json();

            statesData = rawStatesData.data.map(state => ({
                id: state.Uf,
                name: state.Nome
            }));
            citiesData = rawCitiesData.data.map(city => ({
                id: city.Codigo,
                name: city.Nome,
                state_uf: city.Uf
            }));

            console.log('INFO JS: Estados carregados e mapeados:', statesData);
            console.log('INFO JS: Cidades carregadas e mapeadas (amostra):', citiesData.slice(0, 5));

            populateStates(); 
            
            // Pré-preencher outros selects
            if (nacionalidadeSelect && initialDataNacionalidade) {
                nacionalidadeSelect.value = initialDataNacionalidade;
            }
            if (etniaSelect && initialDataEtnia) {
                etniaSelect.value = initialDataEtnia;
            }
            if (corOlhosSelect && initialDataCorOlhos) {
                corOlhosSelect.value = initialDataCorOlhos;
            }

        } catch (error) {
            console.error('ERRO JS: Erro fatal ao carregar dados de localização:', error);
            // ATUALIZAÇÃO: Passando o título correto para o modal de erro
            window.showFeedbackModal('error', 'Erro ao carregar dados de localização. Por favor, recarregue a página.', 'Erro de Carregamento de Anúncio');
            if (stateSelect) {
                stateSelect.innerHTML = '<option value="">Erro ao carregar estados</option>';
                stateSelect.disabled = true;
            }
            if (citySelect) citySelect.disabled = true;
            if (neighborhoodInput) neighborhoodInput.disabled = true;
            if (nacionalidadeSelect) nacionalidadeSelect.disabled = true; 
            if (etniaSelect) etniaSelect.disabled = true;
            if (corOlhosSelect) corOlhosSelect.disabled = true;
        }
    }

    function populateStates() {
        if (!stateSelect) {
            console.warn('WARN JS: populateStates - stateSelect não encontrado.');
            return;
        }

        stateSelect.innerHTML = '<option value="">Selecione o Estado</option>';
        stateSelect.disabled = false; 

        statesData.forEach(state => {
            const option = document.createElement('option');
            option.value = state.id;
            option.textContent = state.name;
            stateSelect.appendChild(option);
        });
        console.log('INFO JS: Estados populados. initialDataState:', initialDataState);

        if (initialDataState) {
            stateSelect.value = initialDataState;
            populateCities(initialDataState, initialDataCity);
        }
    }

    function populateCities(stateId, initialCityId = '') {
        if (!citySelect || !neighborhoodInput) {
            console.warn('WARN JS: populateCities - citySelect ou neighborhoodInput não encontrados.');
            return;
        }

        console.log('INFO JS: populateCities chamado para stateId:', stateId, 'initialCityId:', initialCityId);
        citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
        citySelect.disabled = true; 
        neighborhoodInput.value = ''; 
        neighborhoodInput.placeholder = 'Selecione a Cidade primeiro'; 
        neighborhoodInput.disabled = true; 

        if (!stateId) {
            citySelect.innerHTML = '<option value="">Selecione a Cidade</option>';
            console.log('INFO JS: stateId vazio, resetando cidades.');
            return;
        }

        const filteredCities = citiesData.filter(city => city.state_uf === stateId);
        console.log('INFO JS: Cidades filtradas para o estado', stateId, ':', filteredCities);

        if (filteredCities.length > 0) {
            citySelect.innerHTML = '<option value="">Selecione a Cidade</option>';
            filteredCities.sort((a, b) => a.name.localeCompare(b.name)).forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
            citySelect.disabled = false; 

            if (initialCityId) { 
                citySelect.value = initialCityId;
                console.log('INFO JS: Selecionando cidade inicial:', initialCityId);
                populateNeighborhoodInput(initialCityId, initialDataNeighborhood);
            }
        } else {
            citySelect.innerHTML = '<option value="">Nenhuma cidade encontrada</option>';
            console.log('INFO JS: Nenhuma cidade encontrada para o estado:', stateId);
        }
    }

    function populateNeighborhoodInput(cityId, initialNeighborhoodValue = '') {
        if (!neighborhoodInput) {
            console.warn('WARN JS: populateNeighborhoodInput - neighborhoodInput não encontrado.');
            return;
        }

        console.log('INFO JS: populateNeighborhoodInput chamado para cityId:', cityId, 'initialNeighborhoodValue:', initialNeighborhoodValue);
        
        if (cityId) {
            neighborhoodInput.disabled = false; 
            neighborhoodInput.placeholder = 'Digite o Bairro'; 
            neighborhoodInput.value = initialNeighborhoodValue; 
        } else {
            neighborhoodInput.disabled = true;
            neighborhoodInput.placeholder = 'Selecione a Cidade primeiro';
            neighborhoodInput.value = '';
        }
    }

    // Event Listeners para os selects de localização
    if (stateSelect && formMode !== 'view') {
        stateSelect.addEventListener('change', function() {
            populateCities(this.value);
            citySelect.value = ""; 
            neighborhoodInput.value = ""; 
            showFeedback(stateSelect, '', false); 
            showFeedback(citySelect, '', false);
            showFeedback(neighborhoodInput, '', false); 
        });
    }

    if (citySelect && formMode !== 'view') {
        citySelect.addEventListener('change', function() {
            populateNeighborhoodInput(this.value);
            neighborhoodInput.value = ""; 
            showFeedback(citySelect, '', false); 
            showFeedback(neighborhoodInput, '', false); 
        });
    }

    if (neighborhoodInput && formMode !== 'view') {
        neighborhoodInput.addEventListener('input', function() {
            showFeedback(neighborhoodInput, '', false);
        });
    }

    if (nacionalidadeSelect && formMode !== 'view') {
        nacionalidadeSelect.addEventListener('change', function() {
            showFeedback(nacionalidadeSelect, '', false); 
        });
    }
    if (etniaSelect && formMode !== 'view') {
        etniaSelect.addEventListener('change', function() {
            showFeedback(etniaSelect, '', false); 
        });
    }
    if (corOlhosSelect && formMode !== 'view') {
        corOlhosSelect.addEventListener('change', function() {
            showFeedback(corOlhosSelect, '', false); 
        });
    }

    // --- Lógica de Restrições de Plano para Mídia ---
    function applyPlanRestrictions() {
        galleryPhotoUploadBoxes.forEach((box, index) => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('.photo-preview'); // Adicionado
            const placeholderText = box.querySelector('.upload-placeholder p');
            const placeholderIcon = box.querySelector('.upload-placeholder i');
            const lockOverlay = box.querySelector('.premium-lock-overlay');
            // ATUALIZAÇÃO: Apenas o primeiro slot é gratuito
            const isFreeSlot = index === 0; 
            box.dataset.isFreeSlot = isFreeSlot ? 'true' : 'false'; // Atualiza o dataset para refletir a nova lógica

            // Check if there's an existing photo in this slot (from hidden input)
            const hasExistingPhoto = box.querySelector('input[name^="existing_gallery_paths[]"]') !== null;

            if (userPlanType === 'free' && !isFreeSlot && !hasExistingPhoto) {
                box.classList.add('premium-locked');
                if (input) input.disabled = true; // Só desabilita se o input existir
                if (lockOverlay) lockOverlay.style.display = 'flex';
                if (placeholderText) placeholderText.style.display = 'none';
                if (placeholderIcon) placeholderIcon.style.display = 'none';
                if (preview) preview.style.opacity = '0.5'; // Escurece a imagem existente se o slot for bloqueado
            } else {
                box.classList.remove('premium-locked');
                if (input) input.disabled = false;
                if (lockOverlay) lockOverlay.style.display = 'none';
                if (placeholderText) placeholderText.style.display = 'block';
                if (placeholderIcon) placeholderIcon.style.display = 'block';
                if (preview) preview.style.opacity = '1'; // Restaura opacidade
            }
        });

        const premiumMediaBoxes = [...videoUploadBoxes, ...audioUploadBoxes];
        premiumMediaBoxes.forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('video, audio'); // Adicionado
            const placeholderText = box.querySelector('.upload-placeholder p');
            const placeholderIcon = box.querySelector('.upload-placeholder i');
            const lockOverlay = box.querySelector('.premium-lock-overlay');
            
            // Check if there's an an existing media in this slot (from hidden input)
            // This now checks for existing_video_paths[] or existing_audio_paths[]
            const hasExistingMedia = box.querySelector('input[name^="existing_"]') !== null;

            if (userPlanType === 'free' && !hasExistingMedia) {
                box.classList.add('premium-locked');
                if (input) input.disabled = true; // Só desabilita se o input existir
                if (lockOverlay) lockOverlay.style.display = 'flex';
                if (placeholderText) placeholderText.style.display = 'none';
                if (placeholderIcon) placeholderIcon.style.display = 'none';
                if (preview) preview.style.opacity = '0.5'; // Escurece a mídia existente se o slot for bloqueado
            } else {
                box.classList.remove('premium-locked');
                if (input) input.disabled = false;
                if (lockOverlay) lockOverlay.style.display = 'none';
                if (placeholderText) placeholderText.style.display = 'block';
                if (placeholderIcon) placeholderIcon.style.display = 'block';
                if (preview) preview.style.opacity = '1'; // Restaura opacidade
            }
        });
    }

    // --- Manipulação de Upload de Mídia (Fotos, Vídeos, Áudios) ---
    // Só anexa listeners e manipula uploads se não for modo de visualização
    if (formMode !== 'view') {
        // Generic setup for media upload boxes (cover, gallery, video, audio)
        const setupMediaUpload = (input, preview, placeholder, removeBtn, isCover = false) => {
            if (!input || !preview || !placeholder || !removeBtn) {
                console.warn('WARN JS: Elementos de mídia incompletos para setup. Input:', input, 'Preview:', preview, 'Placeholder:', placeholder, 'RemoveBtn:', removeBtn);
                return;
            }

            const fileChangeHandler = (event) => {
                const file = event.target.files[0];
                const uploadBox = input.closest('.photo-upload-box, .video-upload-box, .audio-upload-box'); // Generalize to all media types

                // IMPORTANT: Remove any existing hidden path input for this slot if a new file is uploaded
                // This is crucial: if a new file is uploaded, the old existing path is no longer relevant for this slot.
                const existingHiddenInput = uploadBox.querySelector('input[name^="existing_"]');
                if (existingHiddenInput) {
                    existingHiddenInput.remove();
                    console.log('DEBUG JS: Hidden input removido ao fazer upload de novo arquivo:', existingHiddenInput.name, existingHiddenInput.value);
                }

                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else if (preview.tagName === 'VIDEO' || preview.tagName === 'AUDIO') {
                            preview.src = e.target.result;
                            preview.load(); // Load the new media
                        }
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        removeBtn.classList.remove('d-none');
                        uploadBox.classList.add('has-file'); // Add a class to indicate it has a file
                        const lockOverlay = uploadBox.querySelector('.premium-lock-overlay');
                        if (lockOverlay) lockOverlay.style.display = 'none';
                        if (isCover && formMode === 'edit') {
                            coverPhotoInput.removeAttribute('required');
                        }
                        // Para o vídeo de confirmação, se um arquivo for selecionado, remove o 'required'
                        if (input.id === 'confirmation_video_input' && formMode === 'edit') {
                            input.removeAttribute('required');
                        }
                    };
                    reader.readAsDataURL(file);
                    if (isCover) showFeedback(coverPhotoInput, '', false);
                    if (input.id === 'confirmation_video_input') showFeedback(confirmationVideoInput, '', false);
                } else {
                    // If no file was selected or input was cleared
                    preview.src = ''; // Clear the preview source
                    preview.style.display = 'none';
                    placeholder.style.display = 'flex';
                    removeBtn.classList.add('d-none');
                    uploadBox.classList.remove('has-file'); // Remove has-file class
                    if (isCover) {
                        // In edit mode, if cover is removed, it becomes required again
                        if (formMode === 'edit') {
                            coverPhotoInput.setAttribute('required', 'required');
                        }
                    }
                    // Para o vídeo de confirmação, se for removido em modo de edição, torna-o required novamente
                    if (input.id === 'confirmation_video_input' && formMode === 'edit') {
                        input.setAttribute('required', 'required');
                    }
                    applyPlanRestrictions(); // Re-evaluate locks if gallery photo
                }
            };

            const removeMediaHandler = () => {
                const uploadBox = input.closest('.photo-upload-box, .video-upload-box, .audio-upload-box'); // Generalize
                input.value = ''; // Clear the file input
                preview.src = ''; // Clear the preview source
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none');
                uploadBox.classList.remove('has-file'); // Remove has-file class

                // IMPORTANT: Add a hidden input for removal signal if this was an existing file
                // This tells the backend to delete the existing file.
                const existingHiddenInput = uploadBox.querySelector('input[name^="existing_"]');
                if (existingHiddenInput) {
                    const removedInputName = existingHiddenInput.name.replace('existing_', 'removed_');
                    let removedHiddenInput = uploadBox.querySelector(`input[name="${removedInputName}"]`);
                    if (!removedHiddenInput) {
                        removedHiddenInput = document.createElement('input');
                        removedHiddenInput.type = 'hidden';
                        removedHiddenInput.name = removedInputName;
                        uploadBox.appendChild(removedHiddenInput);
                    }
                    removedHiddenInput.value = 'true'; // Signal for removal
                    console.log('DEBUG JS: Sinal de remoção definido para:', removedHiddenInput.name);
                    existingHiddenInput.remove(); // Remove the existing path input
                }


                if (isCover) {
                    showFeedback(coverPhotoInput, 'Por favor, selecione uma foto de capa.', true);
                    if (formMode === 'edit') {
                        coverPhotoInput.setAttribute('required', 'required');
                    }
                }
                // Para o vídeo de confirmação, se for removido, torna-o required novamente
                if (input.id === 'confirmation_video_input') {
                    showFeedback(confirmationVideoInput, 'O vídeo de confirmação é obrigatório.', true);
                    if (formMode === 'edit') {
                        confirmationVideoInput.setAttribute('required', 'required');
                    }
                }
                applyPlanRestrictions(); // Re-evaluate locks if gallery photo
            };

            // Event listener for clicking the entire upload box to trigger file input
            placeholder.closest('.photo-upload-box, .video-upload-box, .audio-upload-box').addEventListener('click', () => {
                if (!input.disabled) {
                    input.click();
                } else {
                    window.showFeedbackModal('info', 'Este slot de mídia está disponível apenas para planos pagos.', 'Recurso Premium');
                }
            });

            input.addEventListener('change', fileChangeHandler);
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent click from bubbling to uploadBox
                removeMediaHandler();
            });

            // Initial check for existing media when page loads (for edit mode)
            // This ensures the correct display state (preview, placeholder, remove button)
            // and also marks the box as 'has-file' if it contains an existing media.
            // It also handles the 'required' attribute for cover photo/confirmation video in edit mode.
            if (preview.src && !preview.src.includes('undefined') && !preview.src.includes('null') && preview.src !== window.location.href) {
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                removeBtn.classList.remove('d-none');
                input.closest('.photo-upload-box, .video-upload-box, .audio-upload-box').classList.add('has-file'); // Mark as having a file
                const lockOverlay = input.closest('.photo-upload-box, .video-upload-box, .audio-upload-box').querySelector('.premium-lock-overlay');
                if (lockOverlay) lockOverlay.style.display = 'none';
                if (isCover && formMode === 'edit') {
                    input.removeAttribute('required');
                }
                // Para o vídeo de confirmação, se já tiver um vídeo, remove o 'required'
                if (input.id === 'confirmation_video_input' && formMode === 'edit') {
                    input.removeAttribute('required');
                }
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none'); // Should be hidden if no photo
                input.closest('.photo-upload-box, .video-upload-box, .audio-upload-box').classList.remove('has-file'); // Ensure no has-file
                if (isCover && formMode === 'edit') {
                    input.setAttribute('required', 'required');
                }
                // Para o vídeo de confirmação, se não tiver um vídeo, adiciona o 'required'
                if (input.id === 'confirmation_video_input' && formMode === 'edit') {
                    input.setAttribute('required', 'required');
                }
            }
        }; // Fim da função setupMediaUpload

        // Apply setup to all media types
        setupMediaUpload(coverPhotoInput, coverPhotoPreview, coverPhotoPlaceholder, coverPhotoRemoveBtn, true);
        // NOVO: Setup para o vídeo de confirmação do usuário
        if (confirmationVideoInput && confirmationVideoPreview && confirmationVideoPlaceholder && confirmationVideoRemoveBtn) {
            setupMediaUpload(confirmationVideoInput, confirmationVideoPreview, confirmationVideoPlaceholder, confirmationVideoRemoveBtn);
        }

        galleryPhotoUploadBoxes.forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('.photo-preview');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            if (input && preview && placeholder && removeBtn) {
                // A galeria de fotos não usa hiddenRemovedInput da mesma forma que capa/vídeo de confirmação
                setupMediaUpload(input, preview, placeholder, removeBtn);
            }
        });

        videoUploadBoxes.forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('video');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo'); 

            // Ensure elements exist before setting up
            if (input && preview && placeholder && removeBtn) {
                setupMediaUpload(input, preview, placeholder, removeBtn);
            } else {
                console.warn('WARN JS: Elementos de vídeo incompletos para setup em um box.');
            }
        });

        audioUploadBoxes.forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo'); 

            // Ensure elements exist before setting up
            if (input && preview && placeholder && removeBtn) {
                setupMediaUpload(input, preview, placeholder, removeBtn);
            } else {
                console.warn('WARN JS: Elementos de áudio incompletos para setup em um box.');
            }
        });
    } // Fim do if (formMode !== 'view') que engloba os setups de mídia


    // --- Validação do Formulário ao Enviar ---
    console.log('DEBUG JS: Anexando event listener para o formulário de submit.'); 
    if (formMode !== 'view') { // Apenas anexa o listener se não for modo de visualização
        form.addEventListener('submit', async function(event) { // Adicionado 'async'
            console.log('DEBUG JS: Evento de submit do formulário acionado!'); 
            event.preventDefault();
            let formIsValid = true;

            // Reset feedback
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.is-invalid-media').forEach(el => el.classList.remove('is-invalid-media'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            form.querySelectorAll('.text-danger.small').forEach(el => el.textContent = '');


            // Validação de Localização (selects)
            if (stateSelect && !stateSelect.value) {
                showFeedback(stateSelect, 'Por favor, selecione o Estado.');
                formIsValid = false;
            } else {
                showFeedback(stateSelect, '', false);
            }
            if (citySelect && !citySelect.value) {
                showFeedback(citySelect, 'Por favor, selecione a Cidade.');
                formIsValid = false;
            } else {
                showFeedback(citySelect, '', false);
            }
            // Validação do campo de texto do bairro
            if (neighborhoodInput && neighborhoodInput.value.trim() === '') {
                showFeedback(neighborhoodInput, 'Por favor, digite o Bairro.');
                formIsValid = false;
            } else {
                showFeedback(neighborhoodInput, '', false);
            }

            // Validação de Idade
            if (idadeInput) {
                const idade = parseInt(idadeInput.value, 10);
                if (isNaN(idade) || idade < 18 || idade > 99) {
                    idadeInput.classList.add('is-invalid');
                    formIsValid = false;
                } else {
                    idadeInput.classList.remove('is-invalid');
                }
            }

            // Validação de Nacionalidade (agora é um select)
            if (nacionalidadeSelect && !nacionalidadeSelect.value) { 
                showFeedback(nacionalidadeSelect, 'Por favor, selecione a nacionalidade.');
                formIsValid = false;
            } else {
                showFeedback(nacionalidadeSelect, '', false);
            }

            // Validação de Descrição sobre mim
            if (descricaoSobreMimTextarea && descricaoSobreMimTextarea.value.trim() === '') {
                descricaoSobreMimTextarea.classList.add('is-invalid');
                formIsValid = false;
            } else {
                descricaoSobreMimTextarea.classList.remove('is-invalid');
            }

            // Validação de Altura
            if (alturaInput) {
                const altura = parseFloat(alturaInput.value.replace(',', '.'));
                if (isNaN(altura) || altura <= 0 || altura > 3.0) {
                    alturaInput.classList.add('is-invalid');
                    formIsValid = false;
                } else {
                    alturaInput.classList.remove('is-invalid');
                }
            }

            // Validação de Peso (agora espera um número inteiro)
            if (pesoInput) {
                const peso = parseInt(pesoInput.value, 10);
                if (isNaN(peso) || peso <= 0 || peso > 500) {
                    pesoInput.classList.add('is-invalid');
                    formIsValid = false;
                } else {
                    pesoInput.classList.remove('is-invalid');
                }
            }

            // Validação de Checkboxes: Aparência (mínimo 1)
            const aparenciaCheckboxes = form.querySelectorAll('input[name="aparencia[]"]:checked');
            if (aparenciaCheckboxes.length === 0) {
                toggleCheckboxesFeedback('aparencia', 'Selecione pelo menos 1 item de aparência.', false);
                formIsValid = false;
            } else {
                toggleCheckboxesFeedback('aparencia', '', true);
            }

            // Validação de Checkboxes: Idiomas (mínimo 1)
            const idiomasCheckboxes = form.querySelectorAll('input[name="idiomas[]"]:checked');
            if (idiomasCheckboxes.length === 0) {
                toggleCheckboxesFeedback('idiomas', 'Selecione pelo menos 1 idioma.', false);
                formIsValid = false;
            } else {
                toggleCheckboxesFeedback('idiomas', '', true);
            }

            // Validação de Checkboxes: Local de Atendimento (mínimo 1)
            const locaisCheckboxes = form.querySelectorAll('input[name="locais_atendimento[]"]:checked');
            if (locaisCheckboxes.length === 0) {
                toggleCheckboxesFeedback('locais', 'Selecione pelo menos 1 local de atendimento.', false);
                formIsValid = false;
            } else {
                toggleCheckboxesFeedback('locais', '', true);
            }

            // Validação de Checkboxes: Formas de Pagamento (mínimo 1)
            const pagamentosCheckboxes = form.querySelectorAll('input[name="formas_pagamento[]"]:checked');
            if (pagamentosCheckboxes.length === 0) {
                toggleCheckboxesFeedback('pagamentos', 'Selecione pelo menos 1 forma de pagamento.', false);
                formIsValid = false;
            } else {
                toggleCheckboxesFeedback('pagamentos', '', true);
            }

            // Validação de Checkboxes: Serviços Oferecidos (mínimo 2)
            const servicosCheckboxes = form.querySelectorAll('input[name="servicos[]"]:checked');
            if (servicosCheckboxes.length < 2) {
                toggleCheckboxesFeedback('servicos', 'Selecione pelo menos 2 serviços.', false);
                formIsValid = false;
            } else {
                toggleCheckboxesFeedback('servicos', '', true);
            }

            // Validação de Preços (pelo menos um preenchido e > 0)
            const precoInputs = form.querySelectorAll('input[name^="precos["]');
            let anyPriceValid = false;

            precoInputs.forEach(input => {
                const value = parseFloat(input.value.replace(',', '.'));
                const feedbackElement = document.getElementById(input.id + '-feedback');

                if (input.value.trim() !== '') {
                    if (isNaN(value) || value <= 0) {
                        input.classList.add('is-invalid');
                        if (feedbackElement) feedbackElement.textContent = 'O valor deve ser maior que zero.';
                    } else {
                        input.classList.remove('is-invalid');
                        if (feedbackElement) feedbackElement.textContent = '';
                        anyPriceValid = true;
                    }
                } else {
                    input.classList.remove('is-invalid');
                    if (feedbackElement) feedbackElement.textContent = ''; 
                }
            });

            if (!anyPriceValid) {
                toggleCheckboxesFeedback('precos', 'Preencha pelo menos um preço com um valor maior que zero.', false);
                formIsValid = false;
            } else {
                toggleCheckboxesFeedback('precos', '', true);
            }

            // Validação de Foto da Capa
            const currentCoverPhotoSrc = coverPhotoPreview.src;
            const isCoverPhotoCurrentlyDisplayed = currentCoverPhotoSrc && !currentCoverPhotoSrc.includes('undefined') && !currentCoverPhotoSrc.includes('null') && currentCoverPhotoSrc !== window.location.href;

            if (formMode === 'create' && (!coverPhotoInput.files.length || coverPhotoInput.files[0].size === 0)) {
                showFeedback(coverPhotoInput, 'A foto de capa é obrigatória.', true);
                formIsValid = false;
            } else if (formMode === 'edit' && !isCoverPhotoCurrentlyDisplayed && (!coverPhotoInput.files.length || coverPhotoInput.files[0].size === 0)) {
                // In edit mode, if no existing photo is displayed AND no new file is selected
                showFeedback(coverPhotoInput, 'A foto de capa é obrigatória.', true);
                formIsValid = false;
            } else {
                showFeedback(coverPhotoInput, '', false);
            }

            // Validação de Vídeo de Confirmação do Usuário
            const currentConfirmationVideoSrc = confirmationVideoPreview.src;
            const isConfirmationVideoCurrentlyDisplayed = currentConfirmationVideoSrc && !currentConfirmationVideoSrc.includes('undefined') && !currentConfirmationVideoSrc.includes('null') && currentConfirmationVideoSrc !== window.location.href;

            if (formMode === 'create' && (!confirmationVideoInput.files.length || confirmationVideoInput.files[0].size === 0)) {
                showFeedback(confirmationVideoInput, 'O vídeo de confirmação é obrigatório.', true);
                formIsValid = false;
            } else if (formMode === 'edit' && !isConfirmationVideoCurrentlyDisplayed && (!confirmationVideoInput.files.length || confirmationVideoInput.files[0].size === 0)) {
                showFeedback(confirmationVideoInput, 'O vídeo de confirmação é obrigatório.', true);
                formIsValid = false;
            } else {
                showFeedback(confirmationVideoInput, '', false);
            }
            // FIM NOVO: Validação de Vídeo de Confirmação do Usuário

            // Validação de Mídia da Galeria (total de fotos, novas + existentes)
            const newGalleryFiles = Array.from(form.querySelectorAll('input[name="fotos_galeria[]"]'))
                                                 .filter(input => input.files.length > 0 && input.files[0].size > 0);
            const existingGalleryFiles = Array.from(form.querySelectorAll('input[name="existing_gallery_paths[]"]'));
            const totalGalleryFiles = newGalleryFiles.length + existingGalleryFiles.length;
            
            const galleryErrorDiv = document.getElementById('gallery-feedback-error'); 

            // ATUALIZAÇÃO: Limite de 1 foto para plano gratuito
            const FREE_PHOTO_LIMIT = 1;
            const MIN_PHOTOS_REQUIRED = 1; // Mínimo de fotos para qualquer plano

            if (totalGalleryFiles < MIN_PHOTOS_REQUIRED) {
                if (galleryErrorDiv) { 
                    galleryErrorDiv.textContent = `Mínimo de ${MIN_PHOTOS_REQUIRED} foto(s) na galeria.`;
                    galleryErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else if (userPlanType === 'free' && totalGalleryFiles > FREE_PHOTO_LIMIT) {
                if (galleryErrorDiv) { 
                    galleryErrorDiv.textContent = `Seu plano gratuito permite apenas ${FREE_PHOTO_LIMIT} foto na galeria.`;
                    galleryErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else if (userPlanType === 'premium' && totalGalleryFiles > 20) {
                if (galleryErrorDiv) { 
                    galleryErrorDiv.textContent = 'Seu plano premium permite no máximo 20 fotos na galeria.';
                    galleryErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else {
                if (galleryErrorDiv) { 
                    galleryErrorDiv.textContent = '';
                    galleryErrorDiv.style.display = 'none';
                }
            }


            // Validação de Vídeos (total de vídeos, novos + existentes)
            const newVideoFiles = Array.from(form.querySelectorAll('input[name="videos[]"]'))
                                                 .filter(input => input.files.length > 0 && input.files[0].size > 0);
            // CORREÇÃO DE SINTAXE AQUI: Adicionado o colchete ']' que faltava
            const existingVideoFiles = Array.from(form.querySelectorAll('input[name="existing_video_paths[]"]')); 
            const totalVideoFiles = newVideoFiles.length + existingVideoFiles.length;

            const videoErrorDiv = document.getElementById('videos-feedback-error'); 

            if (userPlanType === 'free' && totalVideoFiles > 0) {
                if (videoErrorDiv) {
                    videoErrorDiv.textContent = 'Vídeos são permitidos apenas para planos pagos.';
                    videoErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else if (userPlanType === 'premium' && totalVideoFiles > 3) {
                if (videoErrorDiv) {
                    videoErrorDiv.textContent = 'Seu plano premium permite no máximo 3 vídeos.';
                    videoErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else {
                if (videoErrorDiv) {
                    videoErrorDiv.textContent = '';
                    videoErrorDiv.style.display = 'none';
                }
            }

            // Validação de Áudios (total de áudios, novos + existentes)
            const newAudioFiles = Array.from(form.querySelectorAll('input[name="audios[]"]'))
                                                 .filter(input => input.files.length > 0 && input.files[0].size > 0);
            const existingAudioFiles = Array.from(form.querySelectorAll('input[name="existing_audio_paths[]"]'));
            const totalAudioFiles = newAudioFiles.length + existingAudioFiles.length;

            const audioErrorDiv = document.getElementById('audios-feedback-error'); 

            if (userPlanType === 'free' && totalAudioFiles > 0) {
                if (audioErrorDiv) {
                    audioErrorDiv.textContent = 'Áudios são permitidos apenas para planos pagos.';
                    audioErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else if (userPlanType === 'premium' && totalAudioFiles > 3) {
                if (audioErrorDiv) {
                    audioErrorDiv.textContent = 'Seu plano premium permite no máximo 3 áudios.';
                    audioErrorDiv.style.display = 'block';
                }
                formIsValid = false;
            } else {
                if (audioErrorDiv) {
                    audioErrorDiv.textContent = '';
                    audioErrorDiv.style.display = 'none';
                }
            }


            if (!formIsValid) {
                // ATUALIZAÇÃO: Título dinâmico para o modal de erro de validação
                const modalTitle = formMode === 'create' ? 'Erro ao Criar Anúncio' : 'Erro ao Editar Anúncio';
                window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário antes de enviar.', modalTitle);
                
                const firstInvalid = document.querySelector('.is-invalid, .is-invalid-media, .text-danger[style*="display: block"]');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const formData = new FormData(form);

            // Adiciona o parâmetro ajax=true para que o backend saiba que é uma requisição AJAX
            formData.append('ajax', 'true');

            // ATUALIZAÇÃO: Ativa o spinner no botão e desabilita, usando a função global
            const originalButtonHTML = window.activateButtonLoading(submitButton, (formMode === 'edit' ? 'Atualizando...' : 'Criando...'));
            
            // MOSTRAR MODAL DE CARREGAMENTO AQUI
            window.showLoadingModal();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
                    setTimeout(() => { // Atraso de 2 segundos para o spinner
                        window.hideLoadingModal(); // Esconde o modal de carregamento
                        console.log('INFO JS: Spinner ocultado (Anúncio). Mostrando modal de feedback.'); // Log para depuração

                        if (data.success) {
                            // ATUALIZAÇÃO: Título e tipo dinâmicos para o modal de sucesso
                            const successModalTitle = formMode === 'create' ? 'Anúncio Criado com Sucesso!' : 'Anúncio Atualizado com Sucesso!';
                            // Usar 'info' (azul) para criar, 'warning' (laranja) para editar
                            const successModalType = formMode === 'create' ? 'info' : 'warning'; 
                            window.showFeedbackModal(successModalType, data.message, successModalTitle, 4000); 
                            
                            // Resetar o formulário apenas se for criação, para edição, recarregar os dados
                            if (formMode === 'create') {
                                form.reset();
                                // Limpar previews de fotos/vídeos/áudios
                                if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.click();
                                // NOVO: Limpa o vídeo de confirmação
                                if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.click();

                                galleryPhotoUploadBoxes.forEach(box => {
                                    const btn = box.querySelector('.btn-remove-photo');
                                    if (btn) btn.click();
                                });
                                videoUploadBoxes.forEach(box => {
                                    const btn = box.querySelector('.btn-remove-photo');
                                    if (btn) btn.click();
                                });
                                audioUploadBoxes.forEach(box => {
                                    const btn = box.querySelector('.btn-remove-photo');
                                    if (btn) btn.click();
                                });
                                // Resetar selects de nacionalidade e etnia
                                if (nacionalidadeSelect) nacionalidadeSelect.value = '';
                                if (document.getElementById('etnia')) document.getElementById('etnia').value = '';
                                if (document.getElementById('cor_olhos')) document.getElementById('cor_olhos').value = '';
                            }
                            
                            // Redirecionar após sucesso
                            if (data.redirect) {
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 4000); // Tempo do redirect para 4 segundos (tempo do modal)
                            } else if (formMode === 'edit') {
                                // Se for edição e não houver redirect específico, apenas recarrega a página de edição
                                setTimeout(() => {
                                    window.location.reload(); 
                                }, 4000); // Tempo do reload para 4 segundos (tempo do modal)
                            }

                        } else {
                            // ATUALIZAÇÃO: Título e tipo dinâmicos para o modal de erro
                            const errorModalTitle = formMode === 'create' ? 'Erro ao Criar Anúncio' : 'Erro ao Atualizar Anúncio';
                            const errorModalType = 'error'; // Sempre vermelho para erro
                            window.showFeedbackModal(errorModalType, 'Erro ao publicar/atualizar anúncio: ' + (data.message || 'Erro desconhecido.'), errorModalTitle);
                            if (data.errors) {
                                for (const fieldId in data.errors) {
                                    const element = document.getElementById(fieldId);
                                    if (element) {
                                        showFeedback(element, data.errors[fieldId], true);
                                    } else {
                                        // Para erros que não estão ligados a um input específico (ex: errors.form)
                                        // Tentamos usar o ID direto para os divs de erro de mídia
                                        const feedbackDiv = document.getElementById(fieldId + '-feedback') || 
                                                                         document.getElementById(fieldId); // Tenta o ID direto se não for -feedback
                                        if (feedbackDiv) {
                                            feedbackDiv.textContent = data.errors[fieldId];
                                            feedbackDiv.style.display = 'block';
                                        }
                                    }
                                }
                            }
                        }
                    }, 2000); // 2 segundos de atraso para o spinner
                } else {
                    // Se a resposta não for JSON, trate como erro de comunicação
                    throw new Error(`Resposta inesperada do servidor (não JSON): ${response.status} - ${await response.text()}`);
                }
            } catch (error) {
                console.error('ERRO JS: Erro na requisição Fetch (Anúncio):', error);
                // Garante que o modal de loading seja escondido mesmo em caso de erro
                setTimeout(() => { // Atraso de 2 segundos para o spinner
                    window.hideLoadingModal(); // Esconde o modal de carregamento
                    console.log('INFO JS: Spinner ocultado (Anúncio - Erro). Mostrando modal de feedback (erro).'); // Log para depuração
                    // ATUALIZAÇÃO: Título dinâmico para o modal de erro de comunicação
                    const communicationErrorTitle = formMode === 'create' ? 'Erro de Comunicação ao Criar' : 'Erro de Comunicação ao Editar';
                    window.showFeedbackModal('error', 'Ocorreu um erro ao comunicar com o servidor. Verifique o console para mais detalhes.', communicationErrorTitle);
                }, 2000); // 2 segundos de atraso para o spinner
            } finally {
                // Restaura o botão de submit IMEDIATAMENTE após o feedback ou tentativa de redirecionamento
                if (submitButton) { // Verifica se o botão existe antes de tentar restaurá-lo
                    window.deactivateButtonLoading(submitButton, originalButtonHTML);
                }
            }
        });
    } else {
        console.log('INFO JS: Modo de visualização, formulário de anúncio não terá listener de submit.');
        // No modo de visualização, não há botão de submit para desabilitar
    }

    // --- NOVO: Lógica para Pausar/Ativar Anúncio ---
    if (navPausarAnuncioLink) {
        navPausarAnuncioLink.addEventListener('click', async (event) => {
            event.preventDefault(); // Impede a navegação padrão do SPA
            console.log('DEBUG JS: Link "Pausar/Ativar Anúncio" clicado.');

            const navPausarAnuncioElement = navPausarAnuncioLink.closest('#navPausarAnuncio');
            const currentAction = navPausarAnuncioElement.dataset.action;
            const canInteract = navPausarAnuncioElement.dataset.canInteract === 'true';

            if (!canInteract) {
                let infoMessage = 'Esta ação não está disponível para o status atual do seu anúncio.';
                if (currentAction === 'info-pending') {
                    infoMessage = 'Seu anúncio está pendente de aprovação. Não é possível pausar ou ativar neste momento.';
                } else if (currentAction === 'info-rejected') {
                    infoMessage = 'Seu anúncio foi rejeitado. Não é possível pausar ou ativar. Por favor, edite-o para revisão.';
                } else if (navPausarAnuncioElement.classList.contains('disabled')) {
                    infoMessage = 'Você não possui um anúncio cadastrado.';
                }
                window.showFeedbackModal('info', infoMessage, 'Ação Indisponível');
                return;
            }

            let modalTitle = '';
            let modalMessage = '';
            let confirmButtonText = '';
            let successMessage = '';
            let errorMessage = '';

            if (currentAction === 'pause') {
                modalTitle = 'Pausar Anúncio';
                modalMessage = 'Tem certeza que deseja pausar seu anúncio? Ele ficará invisível para outros usuários.';
                confirmButtonText = 'Sim, Pausar';
                successMessage = 'Anúncio pausado com sucesso!';
                errorMessage = 'Erro ao pausar anúncio.';
            } else if (currentAction === 'activate') {
                modalTitle = 'Ativar Anúncio';
                modalMessage = 'Tem certeza que deseja ativar seu anúncio? Ele voltará a ser visível para outros usuários.';
                confirmButtonText = 'Sim, Ativar';
                successMessage = 'Anúncio ativado com sucesso!';
                errorMessage = 'Erro ao ativar anúncio.';
            } else {
                window.showFeedbackModal('error', 'Ação desconhecida para o anúncio.', 'Erro de Ação');
                return;
            }

            // Exibe o modal de confirmação
            window.showConfirmModal(
                modalTitle,
                modalMessage,
                confirmButtonText,
                'Cancelar'
            ).then(async (confirmed) => { // 'async' aqui para usar await dentro do then
                if (confirmed) {
                    console.log(`DEBUG JS: Confirmação de ${currentAction} de anúncio recebida.`);
                    window.showLoadingModal(`${currentAction === 'pause' ? 'Pausando' : 'Ativando'} anúncio...`); // Mostra modal de carregamento

                    try {
                        const response = await fetch(`${URLADM}anuncio/pausarAnuncio`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest' // Sinaliza que é uma requisição AJAX
                            },
                            body: JSON.stringify({ ajax: true }) // Envia um corpo simples com ajax=true
                        });

                        const data = await response.json();
                        window.hideLoadingModal(); // Esconde o modal de carregamento

                        if (data.success) {
                            window.showFeedbackModal('success', data.message, currentAction === 'pause' ? 'Anúncio Pausado!' : 'Anúncio Ativado!');
                            // Atualiza os links da sidebar e o status no body após a operação
                            // Isso é crucial para que o texto do link mude
                            document.body.dataset.anuncioStatus = currentAction === 'pause' ? 'inactive' : 'active';
                            window.updateAnuncioSidebarLinks();
                            // Recarrega a página para refletir a mudança de status, se necessário
                            setTimeout(() => {
                                window.location.reload(); 
                            }, 2000); // Recarrega após 2 segundos
                        } else {
                            window.showFeedbackModal('error', data.message || errorMessage, currentAction === 'pause' ? 'Erro ao Pausar Anúncio' : 'Erro ao Ativar Anúncio');
                        }
                    } catch (error) {
                        console.error('ERRO JS: Erro na requisição AJAX para pausar/ativar anúncio:', error);
                        window.hideLoadingModal(); // Garante que o modal de loading seja escondido
                        window.showFeedbackModal('error', 'Ocorreu um erro de comunicação ao pausar/ativar o anúncio.', 'Erro de Rede');
                    }
                } else {
                    console.log(`DEBUG JS: ${currentAction} de anúncio cancelada.`);
                }
            });
        });
    }

    // --- Inicialização ---
    // Chama applyInputMasks e loadLocationData apenas se não for modo de visualização
    if (formMode !== 'view') {
        console.log('DEBUG JS: Modo de formulário (create/edit), chamando loadLocationData().');
        loadLocationData();
    } else {
        console.log('INFO JS: Modo de visualização, pulando carregamento de dados e máscaras de input.');
    }
    applyPlanRestrictions(); 
    console.log('INFO JS: initializeAnuncioPage - Finalizado.'); 
};

// Chama a função de atualização da sidebar imediatamente quando o script anuncio.js é carregado.
// Isso garante que a sidebar seja atualizada assim que o DOM estiver pronto,
// independentemente de qual página SPA é carregada inicialmente.
window.updateAnuncioSidebarLinks();
