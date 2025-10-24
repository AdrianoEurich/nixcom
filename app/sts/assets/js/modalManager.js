(function (window, document) {
    'use strict';

    const ModalManager = {
        instances: {},

        // Cria ou recupera a instância do modal global
        getModalInstance(id) {
            if (!id) return null;
            const el = document.getElementById(id);
            if (!el) return null;

            let inst = this.instances[id];
            if (!inst) {
                inst = new bootstrap.Modal(el);
                this.instances[id] = inst;
            }
            return inst;
        },

        // Mostra o modal com conteúdo dinâmico (title, body, iconClass, headerClass)
        showFeedback({ id = 'feedbackModal', title = '', message = '', iconClass = '', headerClass = '' , autoHide = 4000 } = {}) {
            const el = document.getElementById(id);
            if (!el) return;

            const titleEl = el.querySelector('.modal-title');
            const bodyEl = el.querySelector('.modal-body');
            const iconEl = el.querySelector('.nixcom-modal-icon');
            const headerEl = el.querySelector('.modal-header');

            if (titleEl) titleEl.textContent = title;
            if (bodyEl) bodyEl.innerHTML = message;

            if (iconEl) {
                iconEl.className = 'nixcom-modal-icon';
                if (iconClass) iconEl.classList.add(...iconClass.split(' '));
            }

            if (headerEl) {
                headerEl.classList.remove('success','error','warning','info','primary');
                if (headerClass) headerEl.classList.add(headerClass);
            }

            // Garantir que backdrops antigos sejam limpos somente se necessário
            this.cleanOrphans();

            const inst = this.getModalInstance(id);
            inst.show();

            if (autoHide && typeof autoHide === 'number') {
                setTimeout(() => inst.hide(), autoHide);
            }

            return inst;
        },

        // Remove backdrops órfãos somente quando não houver modais visíveis
        cleanOrphans() {
            const openModals = document.querySelectorAll('.modal.show');
            if (openModals.length === 0) {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        },

        // Utility wrapper para compatibilidade com código existente
        showSimple(type, message, title = null, autoHide = 4000) {
            const map = {
                success: { title: title || 'Sucesso!', icon: 'text-success', header: 'success' },
                error: { title: title || 'Erro!', icon: 'text-danger', header: 'error' },
                warning: { title: title || 'Atenção!', icon: 'text-warning', header: 'warning' },
                info: { title: title || 'Informação', icon: 'text-info', header: 'info' }
            };
            const cfg = map[type] || map.info;
            return this.showFeedback({ title: cfg.title, message, iconClass: cfg.icon, headerClass: cfg.header, autoHide });
        }
    };

    // Expor global
    window.NixcomModalManager = ModalManager;

})(window, document);
