(() => {
    'use strict';

    const picker = document.querySelector('[data-fornecedor-picker]');
    if (!picker) {
        return;
    }

    const input = document.getElementById('fornecedor_busca');
    const hiddenInput = document.getElementById('fornecedor_id');
    const results = document.getElementById('fornecedor_resultados');
    const mainForm = document.getElementById('contaPagarFormGeral');
    const csrfToken = mainForm?.querySelector('input[name="csrf_token"]')?.value || '';
    const quickForm = document.getElementById('formNovoFornecedorRapido');
    const feedback = document.getElementById('fornecedor_rapido_feedback');
    const saveQuickButton = document.getElementById('btnSalvarFornecedorRapido');
    let debounceId = null;
    let activeRequest = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));

    const closeResults = () => {
        results.classList.remove('show');
        input.setAttribute('aria-expanded', 'false');
    };

    const selectSupplier = (supplier) => {
        hiddenInput.value = supplier.id || '';
        input.value = supplier.nome || '';
        closeResults();
    };

    const renderResults = (items) => {
        results.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            results.innerHTML = '<div class="dropdown-item-text text-muted small">Nenhum fornecedor encontrado.</div>';
            results.classList.add('show');
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        items.forEach((supplier) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dropdown-item text-wrap';
            const details = [supplier.nome_fantasia, supplier.documento, supplier.email, supplier.telefone]
                .filter(Boolean)
                .map(escapeHtml)
                .join(' · ');
            button.innerHTML = `<strong>${escapeHtml(supplier.nome)}</strong>${details ? `<br><small class="text-muted">${details}</small>` : ''}`;
            button.addEventListener('click', () => selectSupplier(supplier));
            results.appendChild(button);
        });
        results.classList.add('show');
        input.setAttribute('aria-expanded', 'true');
    };

    const loadResults = async (query = '', preferredId = null) => {
        if (activeRequest) {
            activeRequest.abort();
        }
        activeRequest = new AbortController();
        const params = new URLSearchParams();
        if (query) params.set('q', query);
        if (preferredId) params.set('preferido', String(preferredId));

        try {
            const response = await fetch(`/fornecedores/busca-rapida?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
                signal: activeRequest.signal,
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Não foi possível pesquisar fornecedores.');
            }
            renderResults(payload.data);
        } catch (error) {
            if (error.name === 'AbortError') return;
            results.innerHTML = '<div class="dropdown-item-text text-danger small">Não foi possível pesquisar fornecedores. Tente novamente.</div>';
            results.classList.add('show');
            input.setAttribute('aria-expanded', 'true');
        }
    };

    input.addEventListener('focus', () => loadResults(input.value.trim()));
    input.addEventListener('input', () => {
        hiddenInput.value = '';
        clearTimeout(debounceId);
        debounceId = window.setTimeout(() => loadResults(input.value.trim()), 250);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeResults();
        }
    });
    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            closeResults();
        }
    });

    const showFeedback = (message, type = 'danger') => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = `alert alert-${type}`;
    };

    if (quickForm) {
        quickForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!quickForm.reportValidity()) return;
            if (!csrfToken) {
                showFeedback('Sessão expirada. Atualize a página e tente novamente.');
                return;
            }

            const fields = {
                tipo: document.getElementById('fornecedor_rapido_tipo')?.value,
                nome: document.getElementById('fornecedor_rapido_nome')?.value,
                documento: document.getElementById('fornecedor_rapido_documento')?.value,
                nome_fantasia: document.getElementById('fornecedor_rapido_nome_fantasia')?.value,
                email: document.getElementById('fornecedor_rapido_email')?.value,
                telefone: document.getElementById('fornecedor_rapido_telefone')?.value,
            };
            saveQuickButton.disabled = true;
            saveQuickButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Salvando...';
            if (feedback) feedback.classList.add('d-none');

            try {
                const response = await fetch('/fornecedores/criar-rapido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify(fields),
                });
                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Não foi possível cadastrar o fornecedor.');
                }

                selectSupplier(payload.data);
                quickForm.reset();
                const modalElement = document.getElementById('modalNovoFornecedor');
                if (modalElement && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                }
                await loadResults('', payload.data.id);
            } catch (error) {
                showFeedback(error.message || 'Não foi possível cadastrar o fornecedor.');
            } finally {
                saveQuickButton.disabled = false;
                saveQuickButton.innerHTML = '<i class="fas fa-save me-1"></i>Salvar e selecionar';
            }
        });
    }
})();
