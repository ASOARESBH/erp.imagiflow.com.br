(() => {
    'use strict';

    const picker = document.querySelector('[data-plano-picker]');
    if (!picker) {
        return;
    }

    const type = picker.dataset.planoTipo;
    const input = document.getElementById('plano_conta_busca');
    const hiddenInput = document.getElementById('plano_conta_id');
    const results = document.getElementById('plano_conta_resultados');
    const mainForm = document.getElementById('contaPagarFormGeral') || document.getElementById('contaReceberFormGeral');
    const csrfToken = mainForm?.querySelector('input[name="csrf_token"]')?.value || '';
    const quickForm = document.getElementById('formNovoPlanoContaRapido');
    const feedback = document.getElementById('plano_conta_rapido_feedback');
    const saveButton = document.getElementById('btnSalvarPlanoContaRapido');
    let debounceId = null;
    let activeRequest = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));

    const closeResults = () => {
        results.classList.remove('show');
        input.setAttribute('aria-expanded', 'false');
    };

    const validateSelection = () => {
        input.setCustomValidity(hiddenInput.value ? '' : 'Selecione uma conta de ' + type.toLowerCase() + ' da lista ou cadastre uma nova.');
    };

    const selectAccount = (account) => {
        if (!account || account.tipo !== type) {
            return;
        }
        hiddenInput.value = account.id || '';
        input.value = `${account.codigo} - ${account.nome}`;
        validateSelection();
        closeResults();
    };

    const renderResults = (items) => {
        results.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            results.innerHTML = `<div class="dropdown-item-text text-muted small">Nenhuma conta de ${type.toLowerCase()} encontrada.</div>`;
            results.classList.add('show');
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        items.forEach((account) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dropdown-item text-wrap';
            button.innerHTML = `<strong>${escapeHtml(account.codigo)}</strong> — ${escapeHtml(account.nome)}`;
            button.addEventListener('click', () => selectAccount(account));
            results.appendChild(button);
        });
        results.classList.add('show');
        input.setAttribute('aria-expanded', 'true');
    };

    const loadResults = async (query = '') => {
        if (activeRequest) {
            activeRequest.abort();
        }
        activeRequest = new AbortController();
        const params = new URLSearchParams({ tipo: type });
        if (query) params.set('q', query);

        try {
            const response = await fetch(`/financeiro/plano-contas/busca-rapida?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: activeRequest.signal,
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Não foi possível pesquisar planos.');
            }
            renderResults(payload.data);
        } catch (error) {
            if (error.name === 'AbortError') return;
            results.innerHTML = '<div class="dropdown-item-text text-danger small">Não foi possível pesquisar planos. Tente novamente.</div>';
            results.classList.add('show');
            input.setAttribute('aria-expanded', 'true');
        }
    };

    input.addEventListener('focus', () => loadResults(input.value.trim()));
    input.addEventListener('input', () => {
        hiddenInput.value = '';
        validateSelection();
        clearTimeout(debounceId);
        debounceId = window.setTimeout(() => loadResults(input.value.trim()), 250);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeResults();
    });
    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) closeResults();
    });

    const showFeedback = (message, variant = 'danger') => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = `alert alert-${variant}`;
    };

    validateSelection();

    if (quickForm) {
        quickForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!quickForm.reportValidity()) return;
            if (!csrfToken) {
                showFeedback('Sessão expirada. Atualize a página e tente novamente.');
                return;
            }

            const payload = {
                tipo: document.getElementById('plano_conta_rapido_tipo')?.value,
                nome: document.getElementById('plano_conta_rapido_nome')?.value,
                codigo: document.getElementById('plano_conta_rapido_codigo')?.value,
            };
            if (payload.tipo !== type) {
                showFeedback('O tipo do plano não corresponde a este lançamento. Atualize a página e tente novamente.');
                return;
            }

            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Salvando...';
            if (feedback) feedback.classList.add('d-none');
            try {
                const response = await fetch('/financeiro/plano-contas/criar-rapido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Não foi possível cadastrar o plano.');
                }
                selectAccount(result.data);
                quickForm.reset();
                document.getElementById('plano_conta_rapido_tipo').value = type;
                const modal = document.getElementById('modalNovoPlanoConta');
                if (modal && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).hide();
                }
            } catch (error) {
                showFeedback(error.message || 'Não foi possível cadastrar o plano.');
            } finally {
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save me-1"></i>Salvar e selecionar';
            }
        });
    }
})();
