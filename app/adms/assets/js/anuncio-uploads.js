/**
 * ANÚNCIO UPLOADS - Uploads de mídia
 * Versão: 3.0 (Modular Simples)
 */

console.log('📁 ANÚNCIO UPLOADS carregado');

window.AnuncioUploads = {
    init: function(form, formMode, userPlanType) {
        console.log("✅ ANÚNCIO UPLOADS: Inicializando uploads");
        console.log("🔍 DEBUG: formMode:", formMode, "| userPlanType:", userPlanType);
        console.log("🔍 DEBUG: form element:", form);
        
        if (!form) {
            console.error("❌ ANÚNCIO UPLOADS: Formulário não fornecido");
            return;
        }
        
        this.setupFileUploadHandlers(form, formMode, userPlanType);
    },

    // Função para reinicializar uploads (útil após carregamento SPA)
    reinit: function() {
        console.log("🔄 ANÚNCIO UPLOADS: Reinicializando uploads...");
        const form = document.querySelector('#formAnuncio');
        if (form) {
            const formMode = form.dataset.formMode || 'create';
            const userPlanType = form.dataset.userPlanType || document.body.dataset.userPlanType || 'free';
            this.init(form, formMode, userPlanType);
        } else {
            console.warn("⚠️ ANÚNCIO UPLOADS: Formulário não encontrado para reinicialização");
        }
    },

    setupFileUploadHandlers: function(form, formMode, userPlanType) {
        console.info('INFO JS: setupFileUploadHandlers - Configurando event listeners para uploads de mídia.');
        console.log("🔍 DEBUG: formMode:", formMode, "| userPlanType:", userPlanType);
        
        // CORREÇÃO: Aguardar um pouco para garantir que o DOM esteja completamente renderizado
        setTimeout(() => {
            this._setupFileUploadHandlersInternal(form, formMode, userPlanType);
        }, 200);
    },

    _setupFileUploadHandlersInternal: function(form, formMode, userPlanType) {
        console.log("🔧 ANÚNCIO UPLOADS: Configurando handlers internos...");

        // Throttle para evitar reabertura dupla do seletor de arquivos
        const openThrottle = new WeakMap();
        const canOpenDialog = (inputEl) => {
            const now = Date.now();
            const last = openThrottle.get(inputEl) || 0;
            if (now - last < 450) { // 450ms de proteção
                console.log('⏱️ Throttle ativo: ignorando abertura repetida para', inputEl?.id || inputEl?.name || 'input');
                return false;
            }
            openThrottle.set(inputEl, now);
            return true;
        };
        // Tornar acessível a outros métodos deste módulo
        try { window.AnuncioUploads._canOpenDialog = canOpenDialog; } catch(e) {}
        
        // Verificar se os elementos de upload existem
        const confirmationVideoInputCheck = form.querySelector('#confirmation_video_input');
        const coverPhotoInputCheck = form.querySelector('#foto_capa_input');
        const galleryContainerCheck = form.querySelector('#galleryPhotoContainer');
        
        console.log("🔍 DEBUG UPLOADS: Elementos encontrados:");
        console.log("  - confirmationVideoInput:", !!confirmationVideoInputCheck);
        console.log("  - coverPhotoInput:", !!coverPhotoInputCheck);
        console.log("  - galleryContainer:", !!galleryContainerCheck);
        
        // CORREÇÃO: Se elementos não encontrados, tentar novamente após delay
        if (!confirmationVideoInputCheck && !coverPhotoInputCheck && !galleryContainerCheck) {
            console.warn("⚠️ ANÚNCIO UPLOADS: Elementos de upload não encontrados, tentando novamente em 300ms...");
            setTimeout(() => {
                this._setupFileUploadHandlersInternal(form, formMode, userPlanType);
            }, 300);
            return;
        }

        // Helper para verificar se o caminho é uma URL absoluta
        const isAbsolutePath = (path) => path && (path.startsWith('http://') || path.startsWith('https://'));

        function setupSingleMediaInput(inputElement, previewElement, removeButton, removedHiddenInput, existingPathHiddenInput, type) {
            if (!inputElement || !previewElement || !removeButton) {
                console.warn(`AVISO JS: Elementos de ${type} não encontrados, pulando configuração.`);
                return;
            }

            // Remover listener anterior, se houver
            if (inputElement._changeHandler) {
                inputElement.removeEventListener('change', inputElement._changeHandler);
            }

            const changeHandler = (event) => {
                const file = event.target.files[0];
                if (file) {
                    // Validação de tamanho (100MB para vídeos, 32MB para outros)
                    const isVideo = file.type.startsWith('video/');
                    const MAX_FILE_SIZE = isVideo ? 100 * 1024 * 1024 : 32 * 1024 * 1024; // 100MB para vídeos, 32MB para outros
                    const maxSizeMB = isVideo ? 100 : 32;
                    
                    if (file.size > MAX_FILE_SIZE) {
                        alert(`Arquivo muito grande: ${file.name}. Máximo ${maxSizeMB}MB.`);
                        event.target.value = '';
                        return;
                    }

                    if (type === 'image') {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            previewElement.src = e.target.result;
                            previewElement.style.display = 'block';
                            removeButton.classList.remove('d-none');
                            if (existingPathHiddenInput) existingPathHiddenInput.value = '';
                            console.debug(`DEBUG JS: Preview de ${type} atualizado com novo arquivo. Existing path cleared.`);
                            window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                        };
                        reader.readAsDataURL(file);
                    } else if (type === 'video' || type === 'audio') {
                        // Para vídeos e áudios, usar URL.createObjectURL
                        previewElement.src = URL.createObjectURL(file);
                        previewElement.style.display = 'block';
                        removeButton.classList.remove('d-none');
                        if (existingPathHiddenInput) existingPathHiddenInput.value = '';
                        console.debug(`DEBUG JS: Preview de ${type} atualizado com novo arquivo. Existing path cleared.`);
                        window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                    }
                } else {
                    console.debug(`DEBUG JS: Seleção cancelada, nenhum mídia existente para restaurar.`);
                }
                window.AnuncioUploads.applyPlanRestrictions(userPlanType);
            };
            inputElement.addEventListener('change', changeHandler);
            inputElement._changeHandler = changeHandler;

            // Evitar propagação de clique do input para o container
            try {
                inputElement.addEventListener('click', function(ev){ ev.stopPropagation && ev.stopPropagation(); }, { capture: true });
            } catch(e) {}

            // Remover listener anterior do botão remover, se houver
            if (removeButton._clickHandler) {
                removeButton.removeEventListener('click', removeButton._clickHandler);
            }

            const removeClickHandler = (event) => {
                event.preventDefault();
                inputElement.value = '';
                previewElement.src = '';
                previewElement.style.display = 'none';
                removeButton.classList.add('d-none');
                if (removedHiddenInput) removedHiddenInput.value = '1';
                if (existingPathHiddenInput) existingPathHiddenInput.value = '';
                console.debug(`DEBUG JS: ${type} removido. Existing path cleared.`);
                window.AnuncioUploads.applyPlanRestrictions(userPlanType);
            };
            removeButton.addEventListener('click', removeClickHandler);
            removeButton._clickHandler = removeClickHandler;
        }

        // Configurar vídeo de confirmação
        const confirmationVideoInput = form.querySelector('#confirmation_video_input');
        const confirmationVideoPreview = form.querySelector('#confirmationVideoPreview');
        const confirmationVideoRemoveBtn = form.querySelector('#confirmationVideoUploadBox .btn-remove-photo');
        const confirmationVideoRemovedInput = form.querySelector('#confirmation_video_removed');
        const confirmationVideoExistingPathInput = form.querySelector('#existing_confirmation_video_path');

        setupSingleMediaInput(
            confirmationVideoInput,
            confirmationVideoPreview,
            confirmationVideoRemoveBtn,
            confirmationVideoRemovedInput,
            confirmationVideoExistingPathInput,
            'video'
        );

        // Configurar foto da capa
        const coverPhotoInput = form.querySelector('#foto_capa_input');
        const coverPhotoPreview = form.querySelector('#coverPhotoPreview');
        const coverPhotoRemoveBtn = form.querySelector('#coverPhotoUploadBox .btn-remove-photo');
        const coverPhotoRemovedInput = form.querySelector('#cover_photo_removed');
        const coverPhotoExistingPathInput = form.querySelector('#existing_cover_photo_path');

        setupSingleMediaInput(
            coverPhotoInput,
            coverPhotoPreview,
            coverPhotoRemoveBtn,
            coverPhotoRemovedInput,
            coverPhotoExistingPathInput,
            'image'
        );

        // Configurar fotos da galeria
        const galleryPhotoContainers = form.querySelectorAll('#galleryPhotoContainer .photo-upload-box');
        console.log(`🔍 ANÚNCIO UPLOADS: Encontrados ${galleryPhotoContainers.length} containers de galeria`);
        
        galleryPhotoContainers.forEach((container, index) => {
            const input = container.querySelector('input[type="file"]');
            const preview = container.querySelector(`#galleryPhotoPreview_${index}`);
            const removeBtn = container.querySelector('.btn-remove-photo');
            const removedInput = container.querySelector('input[name="removed_gallery_photos[]"]');
            const existingPathInput = container.querySelector('input[name="existing_gallery_paths[]"]');

            console.log(`🔍 ANÚNCIO UPLOADS: Container ${index} - input: ${!!input}, preview: ${!!preview}, removeBtn: ${!!removeBtn}`);

            if (input && preview && removeBtn) {
                // Remover listeners anteriores se existirem
                if (input._changeHandler) {
                    input.removeEventListener('change', input._changeHandler);
                }
                const changeHandler = (event) => {
                    console.log(`🖼️ ANÚNCIO UPLOADS: Mudança de arquivo detectada na galeria ${index}`);
                    const file = event.target.files[0];
                    if (file) {
                        console.log(`📁 ANÚNCIO UPLOADS: Arquivo selecionado na galeria ${index}:`, file.name, file.type);
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            console.log(`✅ ANÚNCIO UPLOADS: Preview carregado para galeria ${index}`);
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            removeBtn.classList.remove('d-none');
                            if (existingPathInput) existingPathInput.value = '';
                            console.debug(`DEBUG JS: Preview de galeria ${index} atualizado com novo arquivo. Existing path cleared.`);
                            window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                        };
                        if (file.type.startsWith('image/')) {
                            console.log(`🖼️ ANÚNCIO UPLOADS: Lendo imagem para galeria ${index}`);
                            reader.readAsDataURL(file);
                        } else {
                            console.log(`📁 ANÚNCIO UPLOADS: Criando URL para arquivo na galeria ${index}`);
                            preview.src = URL.createObjectURL(file);
                            preview.style.display = 'block';
                            removeBtn.classList.remove('d-none');
                            if (existingPathInput) existingPathInput.value = '';
                            console.debug(`DEBUG JS: Preview de galeria ${index} atualizado com novo arquivo. Existing path cleared.`);
                            window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                        }
                    } else {
                        console.log(`❌ ANÚNCIO UPLOADS: Nenhum arquivo selecionado na galeria ${index}`);
                        if (existingPathInput) existingPathInput.value = '';
                    }
                    window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                };
                input.addEventListener('change', changeHandler);
                input._changeHandler = changeHandler;

                if (removeBtn._clickHandler) {
                    removeBtn.removeEventListener('click', removeBtn._clickHandler);
                }
                const removeClickHandler = (event) => {
                    event.preventDefault();
                    if (input) input.value = '';
                    if (existingPathInput) existingPathInput.value = '';
                    preview.src = '';
                    preview.style.display = 'none';
                    removeBtn.classList.add('d-none');
                    if (removedInput) removedInput.value = '1';
                    console.debug(`DEBUG JS: Galeria ${index} removida. Existing path cleared.`);
                    window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                };
                removeBtn.addEventListener('click', removeClickHandler);
                removeBtn._clickHandler = removeClickHandler;
            }
        });

        // Configurar vídeos da galeria
        const videoContainers = form.querySelectorAll('#videoUploadBoxes .photo-upload-box');
        console.log(`🔍 ANÚNCIO UPLOADS: Configurando ${videoContainers.length} containers de vídeo`);
        
        videoContainers.forEach((container, index) => {
            const input = container.querySelector(`#video_input_${index}`);
            const preview = container.querySelector(`#videoPreview_${index}`);
            const removeBtn = container.querySelector('.btn-remove-photo');
            const existingPathInput = container.querySelector('input[name="existing_video_paths[]"]');

            console.log(`🔍 ANÚNCIO UPLOADS: Container de vídeo ${index} - input: ${!!input}, preview: ${!!preview}, removeBtn: ${!!removeBtn}`);

            if (input && preview && removeBtn) {
                if (input._changeHandler) {
                    input.removeEventListener('change', input._changeHandler);
                }
                const changeHandler = (event) => {
                    console.log(`🎬 ANÚNCIO UPLOADS: Mudança de arquivo detectada no vídeo ${index}`);
                    const file = event.target.files[0];
                    if (file) {
                        console.log(`📁 ANÚNCIO UPLOADS: Arquivo de vídeo selecionado ${index}:`, file.name, file.type);
                        
                        // Validação de tamanho (100MB para vídeos)
                        const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
                        if (file.size > MAX_FILE_SIZE) {
                            alert(`Arquivo muito grande: ${file.name}. Máximo 100MB.`);
                            event.target.value = '';
                            return;
                        }
                        
                        preview.src = URL.createObjectURL(file);
                        preview.style.display = 'block';
                        removeBtn.classList.remove('d-none');
                        if (existingPathInput) existingPathInput.value = '';
                        console.debug(`DEBUG JS: Preview de vídeo ${index} atualizado com novo arquivo. Existing path cleared.`);
                        window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                    }
                };
                input.addEventListener('change', changeHandler);
                input._changeHandler = changeHandler;

                if (removeBtn._clickHandler) {
                    removeBtn.removeEventListener('click', removeBtn._clickHandler);
                }
                const removeClickHandler = (event) => {
                    event.preventDefault();
                    input.value = '';
                    preview.src = '';
                    preview.style.display = 'none';
                    removeBtn.classList.add('d-none');
                    if (existingPathInput) existingPathInput.value = '';
                    console.debug(`DEBUG JS: Vídeo ${index} removido. Existing path cleared.`);
                    window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                };
                removeBtn.addEventListener('click', removeClickHandler);
                removeBtn._clickHandler = removeClickHandler;
            }
        });

        // Configurar áudios da galeria
        const audioContainers = form.querySelectorAll('#audioUploadBoxes .photo-upload-box');
        console.log(`🔍 ANÚNCIO UPLOADS: Configurando ${audioContainers.length} containers de áudio`);
        
        audioContainers.forEach((container, index) => {
            const input = container.querySelector(`#audio_input_${index}`);
            const preview = container.querySelector(`#audioPreview_${index}`);
            const removeBtn = container.querySelector('.btn-remove-photo');
            const existingPathInput = container.querySelector('input[name="existing_audio_paths[]"]');

            console.log(`🔍 ANÚNCIO UPLOADS: Container de áudio ${index} - input: ${!!input}, preview: ${!!preview}, removeBtn: ${!!removeBtn}`);

            if (input && preview && removeBtn) {
                if (input._changeHandler) {
                    input.removeEventListener('change', input._changeHandler);
                }
                const changeHandler = (event) => {
                    console.log(`🎵 ANÚNCIO UPLOADS: Mudança de arquivo detectada no áudio ${index}`);
                    const file = event.target.files[0];
                    if (file) {
                        console.log(`📁 ANÚNCIO UPLOADS: Arquivo de áudio selecionado ${index}:`, file.name, file.type);
                        
                        // Validação de tamanho (32MB para áudios)
                        const MAX_FILE_SIZE = 32 * 1024 * 1024; // 32MB
                        if (file.size > MAX_FILE_SIZE) {
                            alert(`Arquivo muito grande: ${file.name}. Máximo 32MB.`);
                            event.target.value = '';
                            return;
                        }
                        
                        preview.src = URL.createObjectURL(file);
                        preview.style.display = 'block';
                        removeBtn.classList.remove('d-none');
                        if (existingPathInput) existingPathInput.value = '';
                        console.debug(`DEBUG JS: Preview de áudio ${index} atualizado com novo arquivo. Existing path cleared.`);
                        window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                    }
                };
                input.addEventListener('change', changeHandler);
                input._changeHandler = changeHandler;

                if (removeBtn._clickHandler) {
                    removeBtn.removeEventListener('click', removeBtn._clickHandler);
                }
                const removeClickHandler = (event) => {
                    event.preventDefault();
                    input.value = '';
                    preview.src = '';
                    preview.style.display = 'none';
                    removeBtn.classList.add('d-none');
                    if (existingPathInput) existingPathInput.value = '';
                    console.debug(`DEBUG JS: Áudio ${index} removido. Existing path cleared.`);
                    window.AnuncioUploads.applyPlanRestrictions(userPlanType);
                };
                removeBtn.addEventListener('click', removeClickHandler);
                removeBtn._clickHandler = removeClickHandler;
            }
        });

        // Configurar cliques nos botões de upload
        this.setupUploadClickHandlers(form);

        console.log("✅ ANÚNCIO UPLOADS: Handlers de upload configurados");
    },

    setupUploadClickHandlers: function(form) {
        console.log("🖱️ ANÚNCIO UPLOADS: Configurando cliques nos botões de upload");

        // Recuperar canOpenDialog exposto ou fallback no-op (sempre permite)
        const canOpenDialog = (window.AnuncioUploads && window.AnuncioUploads._canOpenDialog)
            ? window.AnuncioUploads._canOpenDialog
            : function(){ return true; };

        // Vídeo de confirmação
        const confirmationVideoBox = form.querySelector('#confirmationVideoUploadBox');
        if (confirmationVideoBox) {
            if (confirmationVideoBox._uploadClickHandler) {
                confirmationVideoBox.removeEventListener('click', confirmationVideoBox._uploadClickHandler);
            }
            const handler = (e) => {
                console.log('🖱️ [CLICK] confirmationVideoBox handler. target=', e.target);
                // Não executar se clicar no botão de remoção
                if (e.target.closest('.btn-remove-photo')) {
                    console.log('↩️ [CLICK] abort: botão remover');
                    return;
                }
                // Evitar reabrir se o clique foi diretamente no input
                if (e.target.matches('input[type="file"]')) {
                    console.log('↩️ [CLICK] abort: clique no próprio input file');
                    return;
                }
                
                // Executar se clicar em qualquer lugar do container (exceto botão de remoção)
                const input = confirmationVideoBox.querySelector('#confirmation_video_input');
                if (input) {
                    if (!canOpenDialog(input)) { console.log('↩️ [CLICK] abort: throttle bloqueou'); return; }
                    input.click();
                    console.log("🖱️ Clique no vídeo de confirmação - abrindo seletor de arquivo");
                }
            };
            confirmationVideoBox.addEventListener('click', handler);
            confirmationVideoBox._uploadClickHandler = handler;
        }

        // Foto da capa
        const coverPhotoBox = form.querySelector('#coverPhotoUploadBox');
        if (coverPhotoBox) {
            if (coverPhotoBox._uploadClickHandler) {
                coverPhotoBox.removeEventListener('click', coverPhotoBox._uploadClickHandler);
            }
            const handler = (e) => {
                console.log('🖱️ [CLICK] coverPhotoBox handler. target=', e.target);
                // Não executar se clicar no botão de remoção
                if (e.target.closest('.btn-remove-photo')) {
                    console.log('↩️ [CLICK] abort: botão remover');
                    return;
                }
                if (e.target.matches('input[type="file"]')) {
                    console.log('↩️ [CLICK] abort: clique no próprio input file');
                    return;
                }
                
                // Executar se clicar em qualquer lugar do container (exceto botão de remoção)
                const input = coverPhotoBox.querySelector('#foto_capa_input');
                if (input) {
                    if (!canOpenDialog(input)) { console.log('↩️ [CLICK] abort: throttle bloqueou'); return; }
                    input.click();
                    console.log("🖱️ Clique na foto da capa - abrindo seletor de arquivo");
                }
            };
            coverPhotoBox.addEventListener('click', handler);
            coverPhotoBox._uploadClickHandler = handler;
        }

        // Fotos da galeria
        const galleryContainers = form.querySelectorAll('#galleryPhotoContainer .photo-upload-box');
        console.log(`🔍 ANÚNCIO UPLOADS: Encontrados ${galleryContainers.length} containers de galeria`);
        galleryContainers.forEach((container, index) => {
            if (container._uploadClickHandler) {
                container.removeEventListener('click', container._uploadClickHandler);
            }
            const handler = (e) => {
                console.log('🖱️ [CLICK] gallery container', index, 'handler. target=', e.target);
                // Não executar se clicar no botão de remoção
                if (e.target.closest('.btn-remove-photo')) {
                    console.log('↩️ [CLICK] abort: botão remover');
                    return;
                }
                if (e.target.matches('input[type="file"]')) {
                    console.log('↩️ [CLICK] abort: clique no próprio input file');
                    return;
                }
                
                // Executar se clicar em qualquer lugar do container (exceto botão de remoção)
                const input = container.querySelector('input[type="file"]');
                if (input && !input.disabled) {
                    if (!canOpenDialog(input)) { console.log('↩️ [CLICK] abort: throttle bloqueou'); return; }
                    input.click();
                    console.log(`🖱️ Clique na galeria ${index} - abrindo seletor de arquivo`);
                }
            };
            container.addEventListener('click', handler);
            container._uploadClickHandler = handler;
        });

        // Vídeos
        const videoContainers = form.querySelectorAll('#videoUploadBoxes .photo-upload-box');
        console.log(`🔍 ANÚNCIO UPLOADS: Encontrados ${videoContainers.length} containers de vídeo`);
        videoContainers.forEach((container, index) => {
            if (container._uploadClickHandler) {
                container.removeEventListener('click', container._uploadClickHandler);
            }
            const handler = (e) => {
                console.log('🖱️ [CLICK] video container', index, 'handler. target=', e.target);
                // Não executar se clicar no botão de remoção
                if (e.target.closest('.btn-remove-photo')) {
                    console.log('↩️ [CLICK] abort: botão remover');
                    return;
                }
                if (e.target.matches('input[type="file"]')) {
                    console.log('↩️ [CLICK] abort: clique no próprio input file');
                    return;
                }
                
                // Executar se clicar em qualquer lugar do container (exceto botão de remoção)
                const input = container.querySelector(`#video_input_${index}`);
                if (input && !input.disabled) {
                    if (!canOpenDialog(input)) { console.log('↩️ [CLICK] abort: throttle bloqueou'); return; }
                    input.click();
                    console.log(`🖱️ Clique no vídeo ${index} - abrindo seletor de arquivo`);
                } else {
                    console.log(`⚠️ Input de vídeo ${index} não encontrado ou desabilitado`);
                }
            };
            container.addEventListener('click', handler);
            container._uploadClickHandler = handler;
        });

        // Áudios
        const audioContainers = form.querySelectorAll('#audioUploadBoxes .photo-upload-box');
        console.log(`🔍 ANÚNCIO UPLOADS: Encontrados ${audioContainers.length} containers de áudio`);
        audioContainers.forEach((container, index) => {
            if (container._uploadClickHandler) {
                container.removeEventListener('click', container._uploadClickHandler);
            }
            const handler = (e) => {
                console.log('🖱️ [CLICK] audio container', index, 'handler. target=', e.target);
                // Não executar se clicar no botão de remoção
                if (e.target.closest('.btn-remove-photo')) {
                    console.log('↩️ [CLICK] abort: botão remover');
                    return;
                }
                if (e.target.matches('input[type="file"]')) {
                    console.log('↩️ [CLICK] abort: clique no próprio input file');
                    return;
                }
                
                // Executar se clicar em qualquer lugar do container (exceto botão de remoção)
                const input = container.querySelector(`#audio_input_${index}`);
                if (input && !input.disabled) {
                    if (!canOpenDialog(input)) { console.log('↩️ [CLICK] abort: throttle bloqueou'); return; }
                    input.click();
                    console.log(`🖱️ Clique no áudio ${index} - abrindo seletor de arquivo`);
                } else {
                    console.log(`⚠️ Input de áudio ${index} não encontrado ou desabilitado`);
                }
            };
            container.addEventListener('click', handler);
            container._uploadClickHandler = handler;
        });

        console.log("✅ ANÚNCIO UPLOADS: Cliques nos botões de upload configurados");
    },

    loadExistingMedia: function(anuncioData) {
        console.log('🎬 ANÚNCIO UPLOADS: Carregando mídias existentes...');
        console.log("🔍 DEBUG: anuncioData:", anuncioData);
        console.log("🔍 DEBUG: anuncioData.videos:", anuncioData.videos);
        console.log("🔍 DEBUG: anuncioData.audios:", anuncioData.audios);
        console.log("🔍 DEBUG: anuncioData.fotos_galeria:", anuncioData.fotos_galeria);
        console.log("🔍 DEBUG: anuncioData.videos é array?", Array.isArray(anuncioData.videos));
        console.log("🔍 DEBUG: anuncioData.audios é array?", Array.isArray(anuncioData.audios));

        if (!anuncioData) {
            console.log("⚠️ ANÚNCIO UPLOADS: Nenhum dado de anúncio fornecido");
            return;
        }

        // Verificar se o formulário está disponível
        const form = document.querySelector('#formAnuncio');
        if (!form) {
            console.warn('⚠️ ANÚNCIO UPLOADS: Formulário não encontrado, aguardando...');
            setTimeout(() => {
                this.loadExistingMedia(anuncioData);
            }, 500);
            return;
        }

        // Aguardar um pouco mais para garantir que o DOM esteja completamente renderizado
        // Aumentar timeout para admin e adicionar verificação mais robusta
        const timeoutDelay = document.body.dataset.userRole === 'admin' ? 500 : 200;
        setTimeout(() => {
            this._loadExistingMediaInternal(anuncioData);
            
            // Forçar exibição após carregamento (especialmente para admin)
            setTimeout(() => {
                this._forcePreviewDisplay();
            }, 100);
        }, timeoutDelay);
    },

    _loadExistingMediaInternal: function(anuncioData) {
        console.log('🎬 ANÚNCIO UPLOADS: Executando carregamento interno de mídias...');

        // Verificar se os elementos existem no DOM antes de prosseguir
        const confirmationVideoPreview = document.querySelector('#confirmationVideoPreview');
        const coverPhotoPreview = document.querySelector('#coverPhotoPreview');
        
        if (!confirmationVideoPreview || !coverPhotoPreview) {
            console.warn('⚠️ ANÚNCIO UPLOADS: Elementos de preview não encontrados no DOM, tentando novamente em 100ms...');
            setTimeout(() => {
                this._loadExistingMediaInternal(anuncioData);
            }, 100);
            return;
        }

        // Carregar vídeo de confirmação
        console.log('🔍 DEBUG: Verificando vídeo de confirmação...');
        console.log('🔍 DEBUG: anuncioData.confirmation_video_path:', anuncioData.confirmation_video_path);
        
        if (anuncioData.confirmation_video_path) {
            const confirmationVideoPreview = document.querySelector('#confirmationVideoPreview');
            const confirmationVideoRemoveBtn = document.querySelector('#confirmationVideoUploadBox .btn-remove-photo');
            const confirmationVideoExistingPathInput = document.querySelector('input[name="existing_confirmation_video_path"]');
            
            console.log('🔍 DEBUG: Elementos encontrados:');
            console.log('  - confirmationVideoPreview:', !!confirmationVideoPreview);
            console.log('  - confirmationVideoRemoveBtn:', !!confirmationVideoRemoveBtn);
            console.log('  - confirmationVideoExistingPathInput:', !!confirmationVideoExistingPathInput);
            
        if (confirmationVideoPreview && confirmationVideoRemoveBtn) {
            // Adicionar timestamp para forçar reload
            const videoUrl = anuncioData.confirmation_video_path + '?t=' + Date.now();
            confirmationVideoPreview.src = videoUrl;
            confirmationVideoPreview.style.display = 'block';
            confirmationVideoPreview.style.visibility = 'visible';
            confirmationVideoPreview.style.opacity = '1';
            confirmationVideoRemoveBtn.classList.remove('d-none');
            if (confirmationVideoExistingPathInput) {
                confirmationVideoExistingPathInput.value = anuncioData.confirmation_video_path;
            }
            console.log("✅ Vídeo de confirmação carregado:", videoUrl);
        } else {
            console.warn("⚠️ Elementos do vídeo de confirmação não encontrados no DOM");
        }
        } else {
            console.log('⚠️ Nenhum vídeo de confirmação encontrado nos dados');
        }

        // Carregar foto da capa
        console.log('🔍 DEBUG: Verificando foto da capa...');
        console.log('🔍 DEBUG: anuncioData.cover_photo_path:', anuncioData.cover_photo_path);
        
        if (anuncioData.cover_photo_path) {
            const coverPhotoPreview = document.querySelector('#coverPhotoPreview');
            const coverPhotoRemoveBtn = document.querySelector('#coverPhotoUploadBox .btn-remove-photo');
            const coverPhotoExistingPathInput = document.querySelector('input[name="existing_cover_photo_path"]');
            
            console.log('🔍 DEBUG: Elementos da foto encontrados:');
            console.log('  - coverPhotoPreview:', !!coverPhotoPreview);
            console.log('  - coverPhotoRemoveBtn:', !!coverPhotoRemoveBtn);
            console.log('  - coverPhotoExistingPathInput:', !!coverPhotoExistingPathInput);
            
        if (coverPhotoPreview && coverPhotoRemoveBtn) {
            // Adicionar timestamp para forçar reload
            const photoUrl = anuncioData.cover_photo_path + '?t=' + Date.now();
            coverPhotoPreview.src = photoUrl;
            coverPhotoPreview.style.display = 'block';
            coverPhotoPreview.style.visibility = 'visible';
            coverPhotoPreview.style.opacity = '1';
            coverPhotoRemoveBtn.classList.remove('d-none');
            if (coverPhotoExistingPathInput) {
                coverPhotoExistingPathInput.value = anuncioData.cover_photo_path;
            }
            console.log("✅ Foto da capa carregada:", photoUrl);
        } else {
            console.warn("⚠️ Elementos da foto da capa não encontrados no DOM");
        }
        } else {
            console.log('⚠️ Nenhuma foto da capa encontrada nos dados');
        }

        // Carregar fotos da galeria
        console.log('🖼️ ANÚNCIO UPLOADS: Verificando fotos da galeria...');
        console.log('🔍 DEBUG: anuncioData.fotos_galeria:', anuncioData.fotos_galeria);
        
        if (anuncioData.fotos_galeria && Array.isArray(anuncioData.fotos_galeria)) {
            // Aguardar um pouco mais para garantir que o DOM esteja completamente renderizado
            setTimeout(() => {
                const galleryContainers = document.querySelectorAll('#galleryPhotoContainer .photo-upload-box');
                console.log('🔍 DEBUG: Encontrados', galleryContainers.length, 'containers de galeria');
                
                if (galleryContainers.length === 0) {
                    console.warn('⚠️ ANÚNCIO UPLOADS: Nenhum container de galeria encontrado no DOM');
                    return;
                }
                
                anuncioData.fotos_galeria.forEach((photoPath, index) => {
                    console.log(`🔍 DEBUG: Processando foto ${index}:`, photoPath);
                    
                    if (index < galleryContainers.length) {
                        const container = galleryContainers[index];
                        const preview = container.querySelector('.photo-preview');
                        const removeBtn = container.querySelector('.btn-remove-photo');
                        const existingPathInput = container.querySelector('input[name="existing_gallery_paths[]"]');
                        
                        console.log(`🔍 DEBUG: Container ${index} - preview:`, !!preview, 'removeBtn:', !!removeBtn, 'existingPathInput:', !!existingPathInput);
                        
                        if (preview && removeBtn) {
                            preview.src = photoPath;
                            preview.style.display = 'block';
                            removeBtn.classList.remove('d-none');
                            if (existingPathInput) {
                                existingPathInput.value = photoPath;
                            }
                            console.log(`✅ Foto da galeria ${index} carregada:`, photoPath);
                        } else {
                            console.warn(`⚠️ Elementos não encontrados para foto ${index}:`, {
                                preview: !!preview,
                                removeBtn: !!removeBtn
                            });
                        }
                    } else {
                        console.warn(`⚠️ Índice ${index} excede o número de containers disponíveis (${galleryContainers.length})`);
                    }
                });
            }, 300); // Aumentar o delay para 300ms
        } else {
            console.log('⚠️ ANÚNCIO UPLOADS: Nenhuma foto da galeria encontrada ou dados inválidos');
        }

        // Carregar vídeos da galeria
        console.log('🎬 ANÚNCIO UPLOADS: Verificando vídeos da galeria...');
        console.log('🔍 DEBUG: anuncioData.videos:', anuncioData.videos);
        console.log('🔍 DEBUG: anuncioData.videos é array?', Array.isArray(anuncioData.videos));
        console.log('🔍 DEBUG: anuncioData.videos length:', anuncioData.videos?.length);
        
        if (anuncioData.videos && Array.isArray(anuncioData.videos) && anuncioData.videos.length > 0) {
            console.log('✅ ANÚNCIO UPLOADS: Encontrados', anuncioData.videos.length, 'vídeos para carregar');
            
            const loadVideos = () => {
                const videoContainers = document.querySelectorAll('#videoUploadBoxes .photo-upload-box');
                console.log('🔍 DEBUG: Encontrados', videoContainers.length, 'containers de vídeo');
                
                if (videoContainers.length === 0) {
                    console.warn('⚠️ ANÚNCIO UPLOADS: Nenhum container de vídeo encontrado no DOM');
                    return false;
                }
                
                return true;
            };
            
            // Tentar carregar imediatamente
            if (loadVideos()) {
                // Containers encontrados, processar vídeos
                const videoContainers = document.querySelectorAll('#videoUploadBoxes .photo-upload-box');
                
                anuncioData.videos.forEach((videoPath, index) => {
                    console.log(`🔍 DEBUG: Processando vídeo ${index}:`, videoPath);
                    
                    if (index < videoContainers.length) {
                        const container = videoContainers[index];
                        const preview = container.querySelector(`#videoPreview_${index}`);
                        const removeBtn = container.querySelector('.btn-remove-photo');
                        const existingPathInput = container.querySelector('input[name="existing_video_paths[]"]');
                        
                        console.log(`🔍 DEBUG: Container ${index} - preview:`, !!preview, 'removeBtn:', !!removeBtn, 'existingPathInput:', !!existingPathInput);
                        
                        if (preview && removeBtn) {
                            preview.src = videoPath;
                            preview.style.display = 'block !important';
                            preview.style.visibility = 'visible';
                            preview.style.opacity = '1';
                            preview.classList.remove('d-none');
                            removeBtn.classList.remove('d-none');
                            removeBtn.style.display = 'block';
                            if (existingPathInput) {
                                existingPathInput.value = videoPath;
                            }
                            console.log(`✅ Vídeo da galeria ${index} carregado:`, videoPath);
                        } else {
                            console.warn(`⚠️ Elementos não encontrados para vídeo ${index}:`, {
                                preview: !!preview,
                                removeBtn: !!removeBtn
                            });
                        }
                    } else {
                        console.warn(`⚠️ Índice ${index} excede o número de containers de vídeo disponíveis (${videoContainers.length})`);
                    }
                });
            } else {
                // Containers não encontrados, tentar novamente
                console.log('⏳ ANÚNCIO UPLOADS: Containers de vídeo não encontrados, tentando novamente...');
                setTimeout(() => {
                    if (loadVideos()) {
                        const videoContainers = document.querySelectorAll('#videoUploadBoxes .photo-upload-box');
                        anuncioData.videos.forEach((videoPath, index) => {
                            if (index < videoContainers.length) {
                                const container = videoContainers[index];
                                const preview = container.querySelector(`#videoPreview_${index}`);
                                const removeBtn = container.querySelector('.btn-remove-photo');
                                const existingPathInput = container.querySelector('input[name="existing_video_paths[]"]');
                                
                                if (preview && removeBtn) {
                                    preview.src = videoPath;
                                    preview.style.display = 'block';
                                    removeBtn.classList.remove('d-none');
                                    removeBtn.style.display = 'block';
                                    if (existingPathInput) {
                                        existingPathInput.value = videoPath;
                                    }
                                    console.log(`✅ Vídeo da galeria ${index} carregado (retry):`, videoPath);
                                }
                            }
                        });
                    }
                }, 500);
            }
        } else {
            console.log('⚠️ ANÚNCIO UPLOADS: Nenhum vídeo da galeria encontrado ou dados inválidos');
        }

        // Carregar áudios da galeria
        console.log('🎵 ANÚNCIO UPLOADS: Verificando áudios da galeria...');
        console.log('🔍 DEBUG: anuncioData.audios:', anuncioData.audios);
        console.log('🔍 DEBUG: anuncioData.audios é array?', Array.isArray(anuncioData.audios));
        console.log('🔍 DEBUG: anuncioData.audios length:', anuncioData.audios?.length);
        
        if (anuncioData.audios && Array.isArray(anuncioData.audios) && anuncioData.audios.length > 0) {
            console.log('✅ ANÚNCIO UPLOADS: Encontrados', anuncioData.audios.length, 'áudios para carregar');
            
            setTimeout(() => {
                const audioContainers = document.querySelectorAll('#audioUploadBoxes .photo-upload-box');
                console.log('🔍 DEBUG: Encontrados', audioContainers.length, 'containers de áudio');
                
                if (audioContainers.length === 0) {
                    console.warn('⚠️ ANÚNCIO UPLOADS: Nenhum container de áudio encontrado no DOM');
                    return;
                }
                
                anuncioData.audios.forEach((audioPath, index) => {
                    console.log(`🔍 DEBUG: Processando áudio ${index}:`, audioPath);
                    
                    if (index < audioContainers.length) {
                        const container = audioContainers[index];
                        const preview = container.querySelector(`#audioPreview_${index}`);
                        const removeBtn = container.querySelector('.btn-remove-photo');
                        const existingPathInput = container.querySelector('input[name="existing_audio_paths[]"]');
                        
                        console.log(`🔍 DEBUG: Container ${index} - preview:`, !!preview, 'removeBtn:', !!removeBtn, 'existingPathInput:', !!existingPathInput);
                        
                        if (preview && removeBtn) {
                            preview.src = audioPath;
                            preview.style.display = 'block';
                            removeBtn.classList.remove('d-none');
                            if (existingPathInput) {
                                existingPathInput.value = audioPath;
                            }
                            console.log(`✅ Áudio da galeria ${index} carregado:`, audioPath);
                        } else {
                            console.warn(`⚠️ Elementos não encontrados para áudio ${index}:`, {
                                preview: !!preview,
                                removeBtn: !!removeBtn
                            });
                        }
                    } else {
                        console.warn(`⚠️ Índice ${index} excede o número de containers de áudio disponíveis (${audioContainers.length})`);
                    }
                });
            }, 500);
        } else {
            console.log('⚠️ ANÚNCIO UPLOADS: Nenhum áudio da galeria encontrado ou dados inválidos');
        }

        console.log("✅ ANÚNCIO UPLOADS: Mídias existentes carregadas");
    },

    applyPlanRestrictions: function(planType) {
        console.info('INFO JS: Aplicando restrições de plano para mídias. Plano:', planType);

        const galleryPhotoContainers = document.querySelectorAll('#galleryPhotoContainer .photo-upload-box');
        const videoUploadBoxes = document.querySelectorAll('#videoUploadBoxes .photo-upload-box');
        const audioUploadBoxes = document.querySelectorAll('#audioUploadBoxes .photo-upload-box');

        console.log(`🔍 DEBUG RESTRIÇÕES: Encontrados ${galleryPhotoContainers.length} containers de galeria`);
        console.log(`🔍 DEBUG RESTRIÇÕES: Encontrados ${videoUploadBoxes.length} containers de vídeo`);
        console.log(`🔍 DEBUG RESTRIÇÕES: Encontrados ${audioUploadBoxes.length} containers de áudio`);

        // Restrições para plano FREE
        if (planType === 'free') {
            // Galeria: máximo 2 fotos
            galleryPhotoContainers.forEach((container, index) => {
                const input = container.querySelector('input[type="file"]');
                const preview = container.querySelector('.photo-preview');
                const isUsed = preview && preview.src && preview.style.display !== 'none';
                
                if (index >= 2) {
                    container.style.display = 'none';
                    if (input) input.disabled = true;
                } else {
                    container.style.display = 'block';
                    if (input) input.disabled = false;
                }
            });

            // Vídeos: máximo 0 (desabilitado)
            videoUploadBoxes.forEach(container => {
                container.style.display = 'none';
                const input = container.querySelector('input[type="file"]');
                if (input) input.disabled = true;
            });

            // Áudios: máximo 0 (desabilitado)
            audioUploadBoxes.forEach(container => {
                container.style.display = 'none';
                const input = container.querySelector('input[type="file"]');
                if (input) input.disabled = true;
            });

            console.log("🔒 ANÚNCIO UPLOADS: Restrições do plano FREE aplicadas");
        } else if (planType === 'basic') {
            // Restrições para plano BASIC
            // Galeria: máximo 20 fotos
            galleryPhotoContainers.forEach((container, index) => {
                if (index < 20) {
                    container.style.display = 'block';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = false;
                } else {
                    container.style.display = 'none';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = true;
                }
            });

            // Vídeos: máximo 0 (desabilitado)
            videoUploadBoxes.forEach(container => {
                container.style.display = 'none';
                const input = container.querySelector('input[type="file"]');
                if (input) input.disabled = true;
            });

            // Áudios: máximo 0 (desabilitado)
            audioUploadBoxes.forEach(container => {
                container.style.display = 'none';
                const input = container.querySelector('input[type="file"]');
                if (input) input.disabled = true;
            });

            console.log("🔒 ANÚNCIO UPLOADS: Restrições do plano BASIC aplicadas - 20 fotos, 0 vídeos, 0 áudios");
        } else if (planType === 'premium') {
            // Restrições para plano PREMIUM
            // Galeria: máximo 20 fotos
            galleryPhotoContainers.forEach((container, index) => {
                if (index < 20) {
                    container.style.display = 'block';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = false;
                } else {
                    container.style.display = 'none';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = true;
                }
            });

            // Vídeos: máximo 3 vídeos
            videoUploadBoxes.forEach((container, index) => {
                if (index < 3) {
                    container.style.display = 'block';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = false;
                } else {
                    container.style.display = 'none';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = true;
                }
            });

            // Áudios: máximo 3 áudios
            audioUploadBoxes.forEach((container, index) => {
                if (index < 3) {
                    container.style.display = 'block';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = false;
                } else {
                    container.style.display = 'none';
                    const input = container.querySelector('input[type="file"]');
                    if (input) input.disabled = true;
                }
            });

            console.log("🔓 ANÚNCIO UPLOADS: Restrições do plano PREMIUM aplicadas - 20 fotos, 3 vídeos, 3 áudios");
        }
    },

    _forcePreviewDisplay: function() {
        console.log('🔧 ANÚNCIO UPLOADS: Forçando exibição de previews...');
        
        // Forçar exibição do vídeo de confirmação
        const confirmationVideo = document.querySelector('#confirmationVideoPreview');
        if (confirmationVideo && confirmationVideo.src) {
            confirmationVideo.style.display = 'block';
            confirmationVideo.style.visibility = 'visible';
            confirmationVideo.style.opacity = '1';
            confirmationVideo.style.maxWidth = '100%';
            confirmationVideo.style.maxHeight = '200px';
            confirmationVideo.style.objectFit = 'contain';
            confirmationVideo.classList.add('d-block');
            confirmationVideo.classList.remove('d-none');
            console.log('✅ Vídeo de confirmação forçado a aparecer');
        }
        
        // Forçar exibição da foto da capa
        const coverPhoto = document.querySelector('#coverPhotoPreview');
        if (coverPhoto && coverPhoto.src) {
            coverPhoto.style.display = 'block';
            coverPhoto.style.visibility = 'visible';
            coverPhoto.style.opacity = '1';
            coverPhoto.style.maxWidth = '100%';
            coverPhoto.style.maxHeight = '200px';
            coverPhoto.style.objectFit = 'contain';
            coverPhoto.classList.add('d-block');
            coverPhoto.classList.remove('d-none');
            console.log('✅ Foto da capa forçada a aparecer');
        }
        
        // Forçar exibição de todos os previews com src
        const allPreviews = document.querySelectorAll('.photo-preview[src]:not([src=""])');
        allPreviews.forEach((preview, index) => {
            preview.style.display = 'block';
            preview.style.visibility = 'visible';
            preview.style.opacity = '1';
            preview.classList.add('d-block');
            preview.classList.remove('d-none');
        });
        
        if (allPreviews.length > 0) {
            console.log(`✅ ${allPreviews.length} previews forçados a aparecer`);
        }
        
        // Verificar se os elementos estão realmente visíveis
        setTimeout(() => {
            const videoVisible = confirmationVideo ? getComputedStyle(confirmationVideo).display !== 'none' : false;
            const photoVisible = coverPhoto ? getComputedStyle(coverPhoto).display !== 'none' : false;
            console.log('🔍 VERIFICAÇÃO FINAL:');
            console.log('  - Vídeo visível:', videoVisible);
            console.log('  - Foto visível:', photoVisible);
        }, 100);
    }
};

console.log("✅ ANÚNCIO UPLOADS: Módulo carregado e pronto");
