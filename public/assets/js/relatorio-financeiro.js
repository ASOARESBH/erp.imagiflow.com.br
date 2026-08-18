(() => {
    'use strict';

    const reportType = document.getElementById('tipo_relatorio');
    const supplierGroup = document.getElementById('grupoFornecedor');
    const customerGroup = document.getElementById('grupoCliente');
    const payableStatusGroup = document.getElementById('grupoStatusPagar');
    const receivableStatusGroup = document.getElementById('grupoStatusReceber');
    const supplierSelect = document.getElementById('fornecedor_ids');
    const customerSelect = document.getElementById('cliente_ids');
    const payableStatusSelect = document.getElementById('status_pagar');
    const receivableStatusSelect = document.getElementById('status_receber');

    const setVisible = (element, visible) => {
        if (!element) {
            return;
        }
        element.classList.toggle('d-none', !visible);
        element.querySelectorAll('select, input').forEach((field) => {
            field.disabled = !visible;
        });
    };

    const syncContextFilters = () => {
        const type = reportType ? reportType.value : 'pagar';
        const isPayable = type === 'pagar' || type === 'comparativo';
        const isReceivable = type === 'receber' || type === 'comparativo';

        setVisible(supplierGroup, isPayable);
        setVisible(customerGroup, isReceivable);
        setVisible(payableStatusGroup, isPayable);
        setVisible(receivableStatusGroup, isReceivable);

        if (supplierSelect) {
            supplierSelect.disabled = !isPayable;
        }
        if (customerSelect) {
            customerSelect.disabled = !isReceivable;
        }
        if (payableStatusSelect) {
            payableStatusSelect.disabled = !isPayable;
        }
        if (receivableStatusSelect) {
            receivableStatusSelect.disabled = !isReceivable;
        }
    };

    const selectedOptions = (select) => Array.from(select.selectedOptions).map((option) => ({
        id: option.value,
        label: option.text,
    }));

    const renderOptions = (select, items) => {
        const preserved = selectedOptions(select);
        const seen = new Set();
        select.innerHTML = '';

        [...preserved, ...items].forEach((item) => {
            const id = String(item.id);
            if (seen.has(id)) {
                return;
            }
            seen.add(id);
            const option = document.createElement('option');
            option.value = id;
            option.textContent = item.label;
            option.selected = preserved.some((selected) => selected.id === id);
            select.appendChild(option);
        });
    };

    const createAutocomplete = (input) => {
        const type = input.dataset.relatorioBusca;
        const select = document.querySelector(`[data-relatorio-opcoes="${type}"]`);
        if (!type || !select) {
            return;
        }

        let debounceTimer = null;
        let controller = null;
        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            const query = input.value.trim();
            if (query.length < 2) {
                return;
            }

            debounceTimer = window.setTimeout(async () => {
                if (controller) {
                    controller.abort();
                }
                controller = new AbortController();
                input.setAttribute('aria-busy', 'true');
                try {
                    const response = await fetch(`/relatorios/financeiro/opcoes?tipo=${encodeURIComponent(type)}&q=${encodeURIComponent(query)}`, {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Falha ao buscar opções.');
                    }
                    renderOptions(select, Array.isArray(payload.data) ? payload.data : []);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Erro ao buscar opções do relatório:', error);
                    }
                } finally {
                    input.removeAttribute('aria-busy');
                }
            }, 300);
        });
    };

    document.querySelectorAll('[data-relatorio-busca]').forEach(createAutocomplete);
    if (reportType) {
        reportType.addEventListener('change', syncContextFilters);
    }
    syncContextFilters();
})();
