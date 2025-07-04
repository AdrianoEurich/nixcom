// app/adms/assets/js/anuncio.js

// Define uma função global para inicializar a página de anúncio
// Esta função será chamada pelo dashboard_custom.js APÓS o conteúdo do formulário ser injetado via AJAX.
window.initializeAnuncioPage = function() {
    // MENSAGEM DE VERIFICAÇÃO DE CACHE: Se você vir esta mensagem, o arquivo mais recente foi carregado!
    console.log('INFO JS: anuncio.js (Versão 6.3 - Altura com Vírgula Automática) - Carregado e inicializando!');

    const form = document.getElementById('formCriarAnuncio');

    if (!form) {
        console.error('ERRO JS: Formulário com ID "formCriarAnuncio" não encontrado APÓS a injeção do conteúdo. Verifique o HTML da view anuncio.php.');
        return; // Sai da função se o formulário não for encontrado
    }

    // Garante que as constantes PHP URL e URLADM estejam definidas no JS
    // Elas são definidas no main.php e globalmente acessíveis.
    let URLADM = window.URLADM || '/'; 
    
    // --- Lógica de fallback para URLADM ---
    // Se URLADM for apenas '/', tenta reconstruir o caminho completo
    if (URLADM === '/') {
        const pathParts = window.location.pathname.split('/');
        if (pathParts.length >= 3 && pathParts[1] === 'nixcom' && pathParts[2] === 'adms') {
            URLADM = window.location.origin + '/' + pathParts[1] + '/' + pathParts[2] + '/';
            console.log('INFO JS: URLADM reconstruída para:', URLADM);
        } else {
            console.warn('AVISO JS: URLADM ainda é "/" e não pôde ser reconstruída automaticamente. Verifique a configuração PHP.');
        }
    }
    console.log('INFO JS: URLADM (final para rotas PHP e assets) em anuncio.js:', URLADM);


    // --- Elementos do Formulário ---
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const neighborhoodInput = document.getElementById('neighborhood_id'); // Campo de texto para bairro

    // Valores iniciais para pré-preenchimento (vindos do PHP via data-initial-value)
    const initialDataState = stateSelect ? stateSelect.dataset.initialValue : '';
    const initialDataCity = citySelect ? citySelect.dataset.initialValue : '';
    const initialDataNeighborhood = neighborhoodInput ? neighborhoodInput.dataset.initialValue : '';

    // Variáveis para armazenar os dados dos JSONs
    let statesData = [];
    let citiesData = [];

    const idadeInput = document.getElementById('idade');
    const nacionalidadeSelect = document.getElementById('nacionalidade'); // Select para nacionalidade
    const descricaoSobreMimTextarea = document.getElementById('descricao_sobre_mim');

    const alturaInput = document.getElementById('altura');
    const pesoInput = document.getElementById('peso');

    const coverPhotoInput = document.getElementById('foto_capa_input');
    const coverPhotoPreview = document.getElementById('coverPhotoPreview');
    const coverPhotoUploadBox = document.getElementById('coverPhotoUploadBox');
    const coverPhotoPlaceholder = coverPhotoUploadBox ? coverPhotoUploadBox.querySelector('.upload-placeholder') : null;
    const coverPhotoRemoveBtn = coverPhotoUploadBox ? coverPhotoUploadBox.querySelector('.btn-remove-photo') : null;

    const galleryPhotoContainer = document.getElementById('galleryPhotoContainer');
    const galleryPhotoUploadBoxes = galleryPhotoContainer ? galleryPhotoContainer.querySelectorAll('.photo-upload-box') : [];

    const videoUploadBoxes = document.querySelectorAll('.video-upload-box');
    const audioUploadBoxes = document.querySelectorAll('.audio-upload-box');

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
            // Procura por um div de feedback específico ou usa o nextElementSibling
            const feedbackDiv = document.getElementById(element.id + '-feedback') || element.nextElementSibling;
            if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                feedbackDiv.textContent = message;
            }
        } else {
            element.classList.remove('is-invalid');
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
            // Adiciona/remove classe is-invalid para o primeiro checkbox do grupo para estilização visual
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
        // Máscara para altura (ex: 1,70) - ATUALIZADO PARA VÍRGULA AUTOMÁTICA
        if (alturaInput) {
            alturaInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^0-9]/g, ''); // Permite apenas números
                
                if (value.length > 1 && !value.includes(',')) {
                    value = value.substring(0, 1) + ',' + value.substring(1);
                }
                // Limita a 4 caracteres (1,xx)
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }
                e.target.value = value;
            });
            alturaInput.addEventListener('blur', function() {
                // Garante que tenha duas casas decimais após a vírgula
                if (this.value) {
                    let cleanedValue = this.value.replace(',', '.');
                    let floatValue = parseFloat(cleanedValue);
                    if (!isNaN(floatValue)) {
                        this.value = floatValue.toFixed(2).replace('.', ',');
                    } else {
                        this.value = ''; // Limpa se não for um número válido
                    }
                }
            });
        }

        // Máscara para peso (apenas números inteiros)
        if (pesoInput) {
            pesoInput.addEventListener('input', function(e) {
                // Remove tudo que não é dígito
                let value = e.target.value.replace(/\D/g, ''); 
                // Limita a 3 dígitos (ex: 999)
                e.target.value = value.substring(0, 3); 
            });
            pesoInput.addEventListener('blur', function() {
                // Garante que o valor seja um número inteiro
                if (this.value) {
                    this.value = parseInt(this.value, 10);
                    if (isNaN(this.value)) {
                        this.value = ''; // Limpa se não for um número válido
                    }
                }
            });
        }

        // Máscara para preços (R$ 0,00)
        const precoInputs = form.querySelectorAll('input[name^="precos["]');
        precoInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
                if (value.length === 0) {
                    e.target.value = '';
                    return;
                }
                // Formata para R$ 0,00
                value = (parseInt(value, 10) / 100).toFixed(2);
                value = value.replace('.', ',');
                e.target.value = value;
            });
            input.addEventListener('blur', function() {
                // Garante que tenha duas casas decimais após a vírgula
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

    /**
     * Carrega todos os dados de estados e cidades dos arquivos JSON locais.
     */
    async function loadLocationData() {
        try {
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

            populateStates(); // Inicia o preenchimento dos selects
            
            // Pré-seleciona a nacionalidade se houver um valor inicial
            // initialDataNacionalidade precisa ser definida para ser usada aqui
            const initialDataNacionalidade = nacionalidadeSelect ? nacionalidadeSelect.dataset.initialValue : '';
            if (nacionalidadeSelect && initialDataNacionalidade) {
                nacionalidadeSelect.value = initialDataNacionalidade;
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
            if (nacionalidadeSelect) nacionalidadeSelect.disabled = true; // Desabilita nacionalidade em caso de erro
        }
    }

    /**
     * Popula o select de Estados.
     */
    function populateStates() {
        if (!stateSelect) return;

        stateSelect.innerHTML = '<option value="">Selecione o Estado</option>';
        stateSelect.disabled = false; // Habilita o select de estados

        statesData.forEach(state => {
            const option = document.createElement('option');
            option.value = state.id;
            option.textContent = state.name;
            stateSelect.appendChild(option);
        });
        console.log('INFO JS: Estados populados. initialDataState:', initialDataState);

        // Se houver um valor inicial de estado, selecione-o e carregue as cidades
        if (initialDataState) {
            stateSelect.value = initialDataState;
            populateCities(initialDataState, initialDataCity);
        }
    }

    /**
     * Popula o select de Cidades baseado no Estado selecionado.
     * @param {string} stateId O ID do estado (Uf).
     * @param {string} initialCityId O ID da cidade inicial (para edição).
     */
    function populateCities(stateId, initialCityId = '') {
        if (!citySelect || !neighborhoodInput) return;

        console.log('INFO JS: populateCities chamado para stateId:', stateId, 'initialCityId:', initialCityId);
        citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
        citySelect.disabled = true; // Desabilita enquanto carrega
        neighborhoodInput.value = ''; // Limpa o campo de texto do bairro
        neighborhoodInput.placeholder = 'Selecione a Cidade primeiro'; // Define placeholder
        neighborhoodInput.disabled = true; // Desabilita o bairro também

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
            citySelect.disabled = false; // Habilita o select de cidades

            // Se houver um valor inicial de cidade, selecione-o e prepare o campo de bairro
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

    /**
     * Prepara o campo de texto do Bairro baseado na Cidade selecionada.
     * @param {string} cityId O ID da cidade.
     * @param {string} initialNeighborhoodValue O valor inicial do bairro (para edição).
     */
    function populateNeighborhoodInput(cityId, initialNeighborhoodValue = '') {
        if (!neighborhoodInput) return;

        console.log('INFO JS: populateNeighborhoodInput chamado para cityId:', cityId, 'initialNeighborhoodValue:', initialNeighborhoodValue);
        
        if (cityId) {
            neighborhoodInput.disabled = false; // Habilita o campo de texto do bairro
            neighborhoodInput.placeholder = 'Digite o Bairro'; // Define placeholder
            neighborhoodInput.value = initialNeighborhoodValue; // Preenche com valor inicial se houver
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
            // Limpar seleções de cidade e bairro se o estado mudar
            citySelect.value = ""; 
            neighborhoodInput.value = ""; // Limpa o campo de texto do bairro
            showFeedback(stateSelect, '', false); // Remover feedback de erro ao mudar
            showFeedback(citySelect, '', false);
            showFeedback(neighborhoodInput, '', false); // Remover feedback de erro ao mudar
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', function() {
            populateNeighborhoodInput(this.value);
            neighborhoodInput.value = ""; // Limpa o campo de texto do bairro
            showFeedback(citySelect, '', false); // Remover feedback de erro ao mudar
            showFeedback(neighborhoodInput, '', false); // Remover feedback de erro ao mudar
        });
    }

    if (neighborhoodInput) {
        neighborhoodInput.addEventListener('input', function() {
            showFeedback(neighborhoodInput, '', false);
        });
    }

    // Event Listener para o select de Nacionalidade
    if (nacionalidadeSelect) {
        nacionalidadeSelect.addEventListener('change', function() {
            showFeedback(nacionalidadeSelect, '', false); // Remove feedback de erro ao mudar
        });
    }

    // --- Manipulação de Upload de Mídia (Fotos, Vídeos, Áudios) ---

    /**
     * Configura a lógica para upload e pré-visualização de arquivos de mídia.
     * @param {HTMLInputElement} input O input de arquivo.
     * @param {HTMLImageElement|HTMLVideoElement|HTMLAudioElement} preview O elemento de pré-visualização.
     * @param {HTMLElement} placeholder O elemento placeholder.
     * @param {HTMLButtonElement} removeBtn O botão de remover.
     * @param {boolean} isCover Indica se é a foto de capa (para validação específica).
     */
    const setupMediaUpload = (input, preview, placeholder, removeBtn, isCover = false) => {
        if (!input || !preview || !placeholder || !removeBtn) return;

        const fileChangeHandler = (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeBtn.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
                if (isCover) showFeedback(coverPhotoInput, '', false);
            } else {
                if (!preview.src || preview.src.includes('undefined') || preview.src.includes('null') || preview.src === window.location.href) {
                     preview.style.display = 'none';
                     placeholder.style.display = 'flex';
                     removeBtn.classList.add('d-none');
                }
            }
        };

        const removeMediaHandler = () => {
            input.value = '';
            preview.src = '';
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            removeBtn.classList.add('d-none');
            if (isCover) {
                showFeedback(coverPhotoInput, 'Por favor, selecione uma foto de capa.', true);
            }
            if (!isCover && input.closest('.premium-locked')) {
                input.disabled = true;
                input.closest('.photo-upload-box').classList.add('premium-locked');
            }
        };

        placeholder.closest('.photo-upload-box').addEventListener('click', () => {
            if (!input.disabled) {
                input.click();
            }
        });

        input.addEventListener('change', fileChangeHandler);
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            removeMediaHandler();
        });

        if (preview.src && !preview.src.includes('undefined') && !preview.src.includes('null') && preview.src !== window.location.href) {
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            removeBtn.classList.remove('d-none');
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            removeBtn.classList.add('d-none');
        }
    };

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

        if (input && preview && placeholder && removeBtn) {
             const removeMediaHandler = () => {
                input.value = '';
                preview.src = '';
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none');
            };

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeMediaHandler();
            });

            if (preview.src && !preview.src.includes('undefined') && !preview.src.includes('null') && preview.src !== window.location.href) {
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                removeBtn.classList.remove('d-none');
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none');
            }
        }
    });

    audioUploadBoxes.forEach(box => {
        const input = box.querySelector('input[type="file"]');
        const preview = box.querySelector('audio');
        const placeholder = box.querySelector('.upload-placeholder');
        const removeBtn = box.querySelector('.btn-remove-photo');

        if (input && preview && placeholder && removeBtn) {
             const removeMediaHandler = () => {
                input.value = '';
                preview.src = '';
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none');
            };

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeMediaHandler();
            });

            if (preview.src && !preview.src.includes('undefined') && !preview.src.includes('null') && preview.src !== window.location.href) {
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                removeBtn.classList.remove('d-none');
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none');
            }
        }
    });


    // --- Validação do Formulário ao Enviar ---
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        let formIsValid = true;

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
        if (nacionalidadeSelect && !nacionalidadeSelect.value) { // Verifica se um valor foi selecionado
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
                if (feedbackElement) feedbackDiv.textContent = ''; 
            }
        });

        if (!anyPriceValid) {
            toggleCheckboxesFeedback('precos', 'Preencha pelo menos um preço com um valor maior que zero.', false);
            formIsValid = false;
        } else {
            toggleCheckboxesFeedback('precos', '', true);
        }

        // Validação de Foto da Capa
        if (coverPhotoInput && (!coverPhotoInput.files.length && (!coverPhotoPreview.src || coverPhotoPreview.src.includes('undefined') || coverPhotoPreview.src.includes('null') || coverPhotoPreview.src === window.location.href))) {
            showFeedback(coverPhotoInput, 'Por favor, selecione uma foto de capa.', true);
            formIsValid = false;
        } else {
            showFeedback(coverPhotoInput, '', false);
        }

        if (!formIsValid) {
            window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário antes de enviar.', 'Erro de Validação');
            
            console.log('DEBUG JS: Procurando o primeiro elemento inválido para rolagem.');
            const firstInvalid = document.querySelector('.is-invalid, .text-danger[style*="display: block"]');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const formData = new FormData(form);

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
                window.showFeedbackModal('success', 'Anúncio publicado com sucesso! ' + data.message, 'Sucesso!');
                form.reset();
                populateStates(); // Recarrega os estados para limpar cidades/bairro
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

                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500); 
                }
            } else {
                window.showFeedbackModal('error', 'Erro ao publicar anúncio: ' + (data.message || 'Erro desconhecido.'), 'Erro na Publicação');
                if (data.errors) {
                    for (const fieldId in data.errors) {
                        const element = document.getElementById(fieldId);
                        if (element) {
                            showFeedback(element, data.errors[fieldId], true);
                        } else {
                            const feedbackDiv = document.getElementById(fieldId + '-feedback');
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
                submitButton.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
            }
        });
    });

    // --- Inicialização ---
    applyInputMasks();
    loadLocationData();
};
