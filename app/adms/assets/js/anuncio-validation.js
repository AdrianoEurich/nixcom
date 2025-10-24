/**
 * ANÚNCIO VALIDATION - Validações de formulário
 * Versão: 3.0 (Modular Simples)
 */

console.log('✅ ANÚNCIO VALIDATION carregado');

window.AnuncioValidation = {
    init: function(form, formMode, userPlanType) {
        console.log("✅ ANÚNCIO VALIDATION: Inicializando validações");
        console.log("🔍 DEBUG: formMode:", formMode, "| userPlanType:", userPlanType);
        this.setupFormValidation(form);
    },

    setupFormValidation: function(form) {
        console.log("✅ ANÚNCIO VALIDATION: Configurando validação");
        
        // Configurar validação em tempo real
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', () => this.validateField(field));
            field.addEventListener('input', () => this.clearFieldError(field));
        });
        
        console.log("✅ ANÚNCIO VALIDATION: Validação configurada");
    },

    validateForm: function() {
        console.log("✅ ANÚNCIO VALIDATION: Validando formulário");
        
        const form = document.getElementById('formAnuncio');
        if (!form) {
            console.error("❌ ANÚNCIO VALIDATION: Formulário não encontrado");
            return false;
        }

        let isValid = true;
        const errors = [];

        // Validar campos obrigatórios
        const requiredFields = [
            'service_name', 'age', 'phone_number', 'description', 'gender',
            'nationality', 'ethnicity', 'eye_color', 'state_id', 'city_id',
            'neighborhood_name', 'height_m', 'weight_kg', 'price_15min',
            'price_30min', 'price_1h'
        ];

        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            console.log(`🔍 ANÚNCIO VALIDATION: Verificando campo ${fieldName}:`, field ? field.value : 'NÃO ENCONTRADO');
            if (field && !this.validateField(field)) {
                isValid = false;
                errors.push(`O campo ${this.getFieldLabel(fieldName)} é obrigatório.`);
            }
        });

        // Validar mídias obrigatórias
        if (!this.validateRequiredMedia()) {
            isValid = false;
            errors.push('Vídeo de confirmação e foto da capa são obrigatórios.');
        }

        // Validar checkboxes obrigatórios
        if (!this.validateRequiredCheckboxes()) {
            isValid = false;
            errors.push('Selecione pelo menos uma opção em cada categoria de características.');
        }

        if (!isValid) {
            console.log("❌ ANÚNCIO VALIDATION: Formulário inválido:", errors);
            window.showFeedbackModal('error', errors.join('\n'), 'Erro!');
        } else {
            console.log("✅ ANÚNCIO VALIDATION: Formulário válido");
        }

        return isValid;
    },

    validateField: function(field) {
        const value = field.value.trim();
        const isValid = value !== '';
        
        if (!isValid) {
            this.showFieldError(field, 'Este campo é obrigatório.');
        } else {
            this.clearFieldError(field);
        }
        
        return isValid;
    },

    validateRequiredMedia: function() {
        const confirmationVideo = document.querySelector('#confirmation_video_input');
        const coverPhoto = document.querySelector('#foto_capa_input');
        
        // Verificar se é modo de edição
        const form = document.getElementById('formAnuncio');
        const isEditMode = form && form.dataset.formMode === 'edit';
        
        if (isEditMode) {
            // No modo de edição, verificar se há arquivos selecionados OU se há mídia existente
            const hasVideo = confirmationVideo && (confirmationVideo.files.length > 0 || this.hasExistingMedia(confirmationVideo) || this.hasExistingVideoInData());
            const hasPhoto = coverPhoto && (coverPhoto.files.length > 0 || this.hasExistingMedia(coverPhoto) || this.hasExistingPhotoInData());
            
            console.log("🔍 ANÚNCIO VALIDATION: Modo edição - vídeo:", hasVideo, "foto:", hasPhoto);
            console.log("🔍 ANÚNCIO VALIDATION: Vídeo files:", confirmationVideo?.files.length, "existing preview:", this.hasExistingMedia(confirmationVideo), "existing data:", this.hasExistingVideoInData());
            console.log("🔍 ANÚNCIO VALIDATION: Foto files:", coverPhoto?.files.length, "existing preview:", this.hasExistingMedia(coverPhoto), "existing data:", this.hasExistingPhotoInData());
            
            return hasVideo && hasPhoto;
        } else {
            // No modo de criação, verificar apenas se há arquivos selecionados
            const hasVideo = confirmationVideo && confirmationVideo.files.length > 0;
            const hasPhoto = coverPhoto && coverPhoto.files.length > 0;
            
            console.log("🔍 ANÚNCIO VALIDATION: Modo criação - vídeo:", hasVideo, "foto:", hasPhoto);
            
            return hasVideo && hasPhoto;
        }
    },

    hasExistingMedia: function(input) {
        if (!input) return false;
        
        // Verificar se há preview visível (indica mídia existente)
        const container = input.closest('.photo-upload-box');
        if (container) {
            const preview = container.querySelector('.photo-preview');
            const isVisible = preview && preview.style.display !== 'none' && preview.src && preview.src !== '';
            console.log("🔍 ANÚNCIO VALIDATION: Preview encontrado:", !!preview, "visível:", isVisible, "src:", preview?.src);
            return isVisible;
        }
        
        return false;
    },

    hasExistingVideoInData: function() {
        const form = document.getElementById('formAnuncio');
        if (!form || !form.dataset.anuncioData) return false;
        
        try {
            const anuncioData = JSON.parse(form.dataset.anuncioData);
            return anuncioData.confirmation_video_path && anuncioData.confirmation_video_path !== '';
        } catch (e) {
            return false;
        }
    },

    hasExistingPhotoInData: function() {
        const form = document.getElementById('formAnuncio');
        if (!form || !form.dataset.anuncioData) return false;
        
        try {
            const anuncioData = JSON.parse(form.dataset.anuncioData);
            return anuncioData.cover_photo_path && anuncioData.cover_photo_path !== '';
        } catch (e) {
            return false;
        }
    },

    validateRequiredCheckboxes: function() {
        const checkboxGroups = ['aparencia', 'idiomas', 'locais_atendimento', 'formas_pagamento', 'servicos'];
        
        for (const groupName of checkboxGroups) {
            const checkboxes = document.querySelectorAll(`input[name="${groupName}[]"]:checked`);
            console.log(`🔍 ANÚNCIO VALIDATION: Verificando checkboxes ${groupName}:`, checkboxes.length, 'selecionados');
            if (checkboxes.length === 0) {
                console.log(`❌ ANÚNCIO VALIDATION: Nenhuma opção selecionada em ${groupName}`);
                return false;
            }
        }
        
        return true;
    },

    showFieldError: function(field, message) {
        field.classList.add('is-invalid');
        const feedback = document.getElementById(`${field.name}-feedback`);
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    },

    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        const feedback = document.getElementById(`${field.name}-feedback`);
        if (feedback) {
            feedback.style.display = 'none';
        }
    },

    getFieldLabel: function(fieldName) {
        const labels = {
            'service_name': 'Nome do Serviço',
            'age': 'Idade',
            'phone_number': 'Telefone',
            'description': 'Descrição',
            'gender': 'Gênero',
            'nationality': 'Nacionalidade',
            'ethnicity': 'Etnia',
            'eye_color': 'Cor dos Olhos',
            'state_id': 'Estado',
            'city_id': 'Cidade',
            'neighborhood_name': 'Bairro',
            'height_m': 'Altura',
            'weight_kg': 'Peso',
            'price_15min': 'Preço 15min',
            'price_30min': 'Preço 30min',
            'price_1h': 'Preço 1h'
        };
        return labels[fieldName] || fieldName;
    }
};

console.log("✅ ANÚNCIO VALIDATION: Módulo carregado e pronto");
