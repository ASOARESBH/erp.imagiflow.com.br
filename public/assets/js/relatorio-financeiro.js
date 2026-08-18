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

    const setDropdownVisible = (input, dropdown, visible) => {
        dropdown.classList.toggle('show', visible);
        input.setAttribute('aria-expanded', visible ? 'true' : 'false');
    };

    const ensureOption = (select, item) => {
        const id = String(item.id);
        let option = Array.from(select.options).find((current) => current.value === id);
        if (!option) {
            option = document.createElement('option');
            option.value = id;
            option.textContent = item.label;
            select.appendChild(option);
        }
        return option;
    };

    const renderSelectedTags = (type, select) => {
        const container = document.querySelector(`[data-relatorio-selecionados="${type}"]`);
        if (!container) {
            return;
        }
        container.innerHTML = '';
        Array.from(select.selectedOptions).forEach((option) => {
            const tag = document.createElement('span');
            tag.className = 'badge bg-primary d-inline-flex align-items-center gap-1 text-wrap text-start';
            tag.textContent = option.text;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn-close btn-close-white ms-1';
            remove.setAttribute('aria-label', `Remover ${option.text}`);
            remove.style.fontSize = '0.55rem';
            remove.addEventListener('click', () => {
                option.selected = false;
                renderSelectedTags(type, select);
            });
            tag.appendChild(remove);
            container.appendChild(tag);
        });
    };

    const renderDropdown = (type, input, select, dropdown, items) => {
        dropdown.innerHTML = '';
        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'dropdown-item-text text-muted small';
            empty.textContent = 'Nenhum registro encontrado.';
            dropdown.appendChild(empty);
            setDropdownVisible(input, dropdown, true);
            return;
        }

        items.forEach((item) => {
            const option = ensureOption(select, item);
            const result = document.createElement('button');
            result.type = 'button';
            result.className = 'dropdown-item text-wrap d-flex justify-content-between align-items-center gap-2';
            result.setAttribute('role', 'option');
            result.textContent = item.label;
            if (option.selected) {
                const selected = document.createElement('i');
                selected.className = 'fas fa-check text-success';
                selected.setAttribute('aria-label', 'Selecionado');
                result.appendChild(selected);
            }
            result.addEventListener('click', () => {
                option.selected = true;
                renderSelectedTags(type, select);
                input.value = '';
                dropdown.innerHTML = '';
                setDropdownVisible(input, dropdown, false);
                input.focus();
            });
            dropdown.appendChild(result);
        });
        setDropdownVisible(input, dropdown, true);
    };

    const createAutocomplete = (input) => {
        const type = input.dataset.relatorioBusca;
        const select = document.querySelector(`[data-relatorio-opcoes="${type}"]`);
        const dropdown = document.querySelector(`[data-relatorio-resultados="${type}"]`);
        if (!type || !select || !dropdown) {
            return;
        }

        renderSelectedTags(type, select);
        let debounceTimer = null;
        let controller = null;
        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            const query = input.value.trim();
            if (query.length < 2) {
                dropdown.innerHTML = '';
                setDropdownVisible(input, dropdown, false);
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
                    renderDropdown(type, input, select, dropdown, Array.isArray(payload.data) ? payload.data : []);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Erro ao buscar opções do relatório:', error);
                        dropdown.innerHTML = '<div class="dropdown-item-text text-danger small">Não foi possível buscar opções agora.</div>';
                        setDropdownVisible(input, dropdown, true);
                    }
                } finally {
                    input.removeAttribute('aria-busy');
                }
            }, 300);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setDropdownVisible(input, dropdown, false);
                input.blur();
            }
        });
    };

    document.querySelectorAll('[data-relatorio-busca]').forEach(createAutocomplete);
    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-relatorio-resultados]').forEach((dropdown) => {
            const wrapper = dropdown.parentElement;
            const input = wrapper ? wrapper.querySelector('[data-relatorio-busca]') : null;
            if (wrapper && input && !wrapper.contains(event.target)) {
                setDropdownVisible(input, dropdown, false);
            }
        });
    });
    if (reportType) {
        reportType.addEventListener('change', syncContextFilters);
    }
    syncContextFilters();
})();
