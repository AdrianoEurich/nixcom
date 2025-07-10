// app/adms/assets/js/anuncio.js

// MENSAGEM DE VERIFICAÇÃO DE CACHE: Se você vir esta mensagem, o arquivo mais recente foi carregado!
console.log('INFO JS: anuncio.js (Versão 17 - Tempo da Modal e Limite de Fotos Ajustados) - Carregado.');

// URLADM é definida globalmente pelo main.php e anexada a window.
// Usamos window.URLADM diretamente.
const URLADM = window.URLADM; 
const URL = window.URL; // A URL base do projeto (necessária para manipular caminhos de mídia)
console.log('INFO JS: URLADM (global, vinda de main.php) em anuncio.js:', URLADM);
console.log('INFO JS: URL (global, URL base do projeto) em anuncio.js:', URL);


// --- Lógica da Sidebar (desativar/ativar links) ---
// Esta função agora é global para ser chamada de dashboard_custom.js e diretamente
window.updateAnuncioSidebarLinks = function() {
    console.log('DEBUG JS: updateAnuncioSidebarLinks - Iniciado.');
    const navCriarAnuncio = document.getElementById('navCriarAnuncio');
    const navEditarAnuncio = document.getElementById('navEditarAnuncio');
    const body = document.body;
    const hasAnuncio = body.dataset.hasAnuncio === 'true'; // Lendo do body

    if (navCriarAnuncio) {
        if (hasAnuncio) {
            navCriarAnuncio.classList.add('disabled');
            navCriarAnuncio.querySelector('a').style.pointerEvents = 'none'; // Desabilita clique
            navCriarAnuncio.querySelector('a').style.opacity = '0.6'; // Efeito visual
        } else {
            navCriarAnuncio.classList.remove('disabled');
            navCriarAnuncio.querySelector('a').style.pointerEvents = 'auto';
            navCriarAnuncio.querySelector('a').style.opacity = '1';
        }
    }
    if (navEditarAnuncio) {
        if (hasAnuncio) {
            navEditarAnuncio.classList.remove('disabled');
            navEditarAnuncio.querySelector('a').style.pointerEvents = 'auto';
            navEditarAnuncio.querySelector('a').style.opacity = '1';
        } else {
            navEditarAnuncio.classList.add('disabled');
            navEditarAnuncio.querySelector('a').style.pointerEvents = 'none';
            navEditarAnuncio.querySelector('a').style.opacity = '0.6';
        }
    }
    console.log('INFO JS: Sidebar links atualizados. Has Anuncio:', hasAnuncio);
};


// Define uma função global para inicializar a página de anúncio
// Esta função será chamada pelo dashboard_custom.js APÓS o conteúdo do formulário ser injetado via AJAX.
window.initializeAnuncioPage = function() {
    console.log('INFO JS: initializeAnuncioPage (Versão 17) - Iniciando inicialização do formulário.'); 

    const form = document.getElementById('formCriarAnuncio');
    if (!form) {
        console.error('ERRO JS: Formulário com ID "formCriarAnuncio" não encontrado APÓS a injeção do conteúdo. Verifique o HTML da view anuncio.php.');
        return; 
    }
    console.log('DEBUG JS: Formulário encontrado:', form); 

    // --- Elementos do Formulário ---
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
    const coverPhotoRemovedInput = document.getElementById('cover_photo_removed'); // Hidden input for removal signal

    const galleryPhotoContainer = document.getElementById('galleryPhotoContainer');
    const galleryPhotoUploadBoxes = galleryPhotoContainer ? galleryPhotoContainer.querySelectorAll('.photo-upload-box') : [];

    const videoUploadBoxes = document.querySelectorAll('.video-upload-box');
    const audioUploadBoxes = document.querySelectorAll('.audio-upload-box');

    // Obter o tipo de plano do usuário do atributo data-user-plan-type do formulário
    const userPlanType = form.dataset.userPlanType || 'free'; 
    console.log('INFO JS: Tipo de plano do usuário (do formulário):', userPlanType);

    // Obter o modo do formulário (create/edit)
    const formMode = form.dataset.formMode || 'create';
    console.log('INFO JS: Modo do formulário (do formulário):', formMode);

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
            });
        });
    };

    // --- Carregamento de Localização (Estados e Cidades de JSONs Locais) ---

    let statesData = [];
    let citiesData = [];

    async function loadLocationData() {
        try {
            // Usando a variável global URLADM
            console.log('INFO JS: Tentando buscar states.json de:', `${URLADM}assets/js/data/states.json`);
            console.log('INFO JS: Tentando buscar cities.json de:', `${URLADM}assets/js/data/cities.json`);

            const [statesResponse, citiesResponse] = await Promise.all([
                fetch(`${URLADM}assets/js/data/states.json`),
                fetch(`${URLADM}assets/js/data/cities.json`)
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
            window.showFeedbackModal('error', 'Erro ao carregar dados de localização. Por favor, recarregue a página.', 'Erro de Carregamento');
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
        if (!stateSelect) return;

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
        if (!citySelect || !neighborhoodInput) return;

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
        if (!neighborhoodInput) return;

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
    if (stateSelect) {
        stateSelect.addEventListener('change', function() {
            populateCities(this.value);
            citySelect.value = ""; 
            neighborhoodInput.value = ""; 
            showFeedback(stateSelect, '', false); 
            showFeedback(citySelect, '', false);
            showFeedback(neighborhoodInput, '', false); 
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', function() {
            populateNeighborhoodInput(this.value);
            neighborhoodInput.value = ""; 
            showFeedback(citySelect, '', false); 
            showFeedback(neighborhoodInput, '', false); 
        });
    }

    if (neighborhoodInput) {
        neighborhoodInput.addEventListener('input', function() {
            showFeedback(neighborhoodInput, '', false);
        });
    }

    if (nacionalidadeSelect) {
        nacionalidadeSelect.addEventListener('change', function() {
            showFeedback(nacionalidadeSelect, '', false); 
        });
    }
    if (etniaSelect) {
        etniaSelect.addEventListener('change', function() {
            showFeedback(etniaSelect, '', false); 
        });
    }
    if (corOlhosSelect) {
        corOlhosSelect.addEventListener('change', function() {
            showFeedback(corOlhosSelect, '', false); 
        });
    }


    // --- Lógica de Restrições de Plano para Mídia ---
    function applyPlanRestrictions() {
        galleryPhotoUploadBoxes.forEach((box, index) => {
            const input = box.querySelector('input[type="file"]');
            const placeholderText = box.querySelector('.upload-placeholder p');
            const placeholderIcon = box.querySelector('.upload-placeholder i');
            const lockOverlay = box.querySelector('.premium-lock-overlay');
            // ATUALIZAÇÃO: Apenas o primeiro slot é gratuito
            const isFreeSlot = index === 0; 
            box.dataset.isFreeSlot = isFreeSlot ? 'true' : 'false'; // Atualiza o dataset para refletir a nova lógica

            // Check if there's an existing photo in this slot (from hidden input)
            const hasExistingPhoto = box.querySelector('input[name="existing_gallery_paths[]"]') !== null;

            if (userPlanType === 'free' && !isFreeSlot && !hasExistingPhoto) {
                box.classList.add('premium-locked');
                input.disabled = true;
                if (lockOverlay) lockOverlay.style.display = 'flex';
                if (placeholderText) placeholderText.style.display = 'none';
                if (placeholderIcon) placeholderIcon.style.display = 'none';
            } else {
                box.classList.remove('premium-locked');
                input.disabled = false;
                if (lockOverlay) lockOverlay.style.display = 'none';
                if (placeholderText) placeholderText.style.display = 'block';
                if (placeholderIcon) placeholderIcon.style.display = 'block';
            }
        });

        const premiumMediaBoxes = [...videoUploadBoxes, ...audioUploadBoxes];
        premiumMediaBoxes.forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const placeholderText = box.querySelector('.upload-placeholder p');
            const placeholderIcon = box.querySelector('.upload-placeholder i');
            const lockOverlay = box.querySelector('.premium-lock-overlay');
            
            // Check if there's an an existing media in this slot (from hidden input)
            const hasExistingMedia = box.querySelector('input[name^="existing_"]') !== null;

            if (userPlanType === 'free' && !hasExistingMedia) {
                box.classList.add('premium-locked');
                input.disabled = true;
                if (lockOverlay) lockOverlay.style.display = 'flex';
                if (placeholderText) placeholderText.style.display = 'none';
                if (placeholderIcon) placeholderIcon.style.display = 'none';
            } else {
                box.classList.remove('premium-locked');
                input.disabled = false;
                if (lockOverlay) lockOverlay.style.display = 'none';
                if (placeholderText) placeholderText.style.display = 'block';
                if (placeholderIcon) placeholderIcon.style.display = 'block';
            }
        });
    }


    // --- Manipulação de Upload de Mídia (Fotos, Vídeos, Áudios) ---

    // Generic setup for media upload boxes (cover, gallery, video, audio)
    const setupMediaUpload = (input, preview, placeholder, removeBtn, isCover = false) => {
        if (!input || !preview || !placeholder || !removeBtn) {
            console.warn('WARN JS: Elementos de mídia incompletos para setup. Input:', input, 'Preview:', preview, 'Placeholder:', placeholder, 'RemoveBtn:', removeBtn);
            return;
        }

        const fileChangeHandler = (event) => {
            const file = event.target.files[0];
            const uploadBox = input.closest('.photo-upload-box');

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
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeBtn.classList.remove('d-none');
                    uploadBox.classList.add('has-file'); // Add a class to indicate it has a file
                    const lockOverlay = uploadBox.querySelector('.premium-lock-overlay');
                    if (lockOverlay) lockOverlay.style.display = 'none';
                    if (isCover && formMode === 'edit') {
                        coverPhotoInput.removeAttribute('required');
                    }
                };
                reader.readAsDataURL(file);
                if (isCover) showFeedback(coverPhotoInput, '', false);
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
                applyPlanRestrictions(); // Re-evaluate locks if gallery photo
            }
        };

        const removeMediaHandler = () => {
            const uploadBox = input.closest('.photo-upload-box');
            input.value = ''; // Clear the file input
            preview.src = ''; // Clear the preview source
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            removeBtn.classList.add('d-none');
            uploadBox.classList.remove('has-file'); // Remove has-file class

            // IMPORTANT: Remove the hidden input for existing path if this was an existing file
            const existingHiddenInput = uploadBox.querySelector('input[name^="existing_"]');
            if (existingHiddenInput) {
                existingHiddenInput.remove();
                console.log('DEBUG JS: Hidden input removido ao clicar em remover:', existingHiddenInput.name, existingHiddenInput.value);
            }

            if (isCover) {
                showFeedback(coverPhotoInput, 'Por favor, selecione uma foto de capa.', true);
                if (formMode === 'edit') {
                    coverPhotoInput.setAttribute('required', 'required');
                    coverPhotoRemovedInput.value = 'true'; // Set removal flag for cover photo
                }
            }
            applyPlanRestrictions(); // Re-evaluate locks if gallery photo
        };

        // Event listener for clicking the entire upload box to trigger file input
        placeholder.closest('.photo-upload-box').addEventListener('click', () => {
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
        if (preview.src && !preview.src.includes('undefined') && !preview.src.includes('null') && preview.src !== window.location.href) {
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            removeBtn.classList.remove('d-none');
            input.closest('.photo-upload-box').classList.add('has-file'); // Mark as having a file
            const lockOverlay = input.closest('.photo-upload-box').querySelector('.premium-lock-overlay');
            if (lockOverlay) lockOverlay.style.display = 'none';
            if (isCover && formMode === 'edit') {
                input.removeAttribute('required');
            }
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            removeBtn.classList.add('d-none'); // Should be hidden if no photo
            input.closest('.photo-upload-box').classList.remove('has-file'); // Ensure no has-file
            if (isCover && formMode === 'edit') {
                input.setAttribute('required', 'required');
            }
        }
    };

    // Apply setup to all media types
    setupMediaUpload(coverPhotoInput, coverPhotoPreview, coverPhotoPlaceholder, coverPhotoRemoveBtn, true);

    galleryPhotoUploadBoxes.forEach(box => {
        const input = box.querySelector('input[type="file"]');
        const preview = box.querySelector('.photo-preview');
        const placeholder = box.querySelector('.upload-placeholder');
        const removeBtn = box.querySelector('.btn-remove-photo');
        setupMediaUpload(input, preview, placeholder, removeBtn);
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


    // --- Validação do Formulário ao Enviar ---
    console.log('DEBUG JS: Anexando event listener para o formulário de submit.'); 
    form.addEventListener('submit', function(event) {
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
            window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário antes de enviar.', 'Erro de Validação');
            
            const firstInvalid = document.querySelector('.is-invalid, .is-invalid-media, .text-danger[style*="display: block"]');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const formData = new FormData(form);

        // Adiciona o parâmetro ajax=true para que o backend saiba que é uma requisição AJAX
        formData.append('ajax', 'true');

        const submitButton = document.getElementById('btnSubmitAnuncio');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
        }

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    throw new Error(`Resposta inesperada do servidor (não JSON): ${response.status} - ${text}`);
                });
            }
        })
        .then(data => {
            console.log('INFO JS: Resposta do servidor:', data);
            if (data.success) {
                // ATUALIZAÇÃO: Tempo da modal para 4 segundos (4000ms)
                window.showFeedbackModal('success', data.message, 'Sucesso!', 4000); 
                // Resetar o formulário apenas se for criação, para edição, recarregar os dados
                if (formMode === 'create') {
                    form.reset();
                    // Limpar previews de fotos/vídeos/áudios
                    if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.click();
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
                    }, 4000); // ATUALIZAÇÃO: Tempo do redirect para 4 segundos
                } else if (formMode === 'edit') {
                    // Se for edição e não houver redirect específico, apenas recarrega a página de edição
                    setTimeout(() => {
                        window.location.reload(); 
                    }, 4000); // ATUALIZAÇÃO: Tempo do reload para 4 segundos
                }

            } else {
                window.showFeedbackModal('error', 'Erro ao publicar/atualizar anúncio: ' + (data.message || 'Erro desconhecido.'), 'Erro na Operação');
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
        })
        .catch((error) => {
            console.error('ERRO JS: Erro na requisição Fetch:', error);
            window.showFeedbackModal('error', 'Ocorreu um erro ao comunicar com o servidor. Verifique o console para mais detalhes.', 'Erro de Comunicação');
        })
        .finally(() => {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = (formMode === 'edit') ? '<i class="fas fa-save me-2"></i>ATUALIZAR ANÚNCIO' : '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
            }
            // A função global updateAnuncioSidebarLinks é chamada em dashboard_custom.js no carregamento da página.
            // Não precisamos chamá-la aqui, a menos que a lógica de SPA seja mais complexa.
        });
    });

    // --- Inicialização ---
    applyInputMasks();
    loadLocationData();
    applyPlanRestrictions(); 
    console.log('INFO JS: initializeAnuncioPage - Finalizado.'); 
};

// Chama a função de atualização da sidebar imediatamente quando o script anuncio.js é carregado.
// Isso garante que a sidebar seja atualizada assim que o DOM estiver pronto,
// independentemente de qual página SPA é carregada inicialmente.
window.updateAnuncioSidebarLinks();
