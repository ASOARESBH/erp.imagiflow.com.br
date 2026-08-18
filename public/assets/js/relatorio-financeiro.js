(() => {
    'use strict';

    const type = document.getElementById('tipo_relatorio');
    const supplierGroup = document.getElementById('grupoFornecedor');
    const customerGroup = document.getElementById('grupoCliente');
    const supplierSelect = document.getElementById('fornecedor_ids');
    const customerSelect = document.getElementById('cliente_ids');

    const syncEntityFilters = () => {
        if (!type || !supplierGroup || !customerGroup) {
            return;
        }

        const reportType = type.value;
        const showSupplier = reportType === 'pagar' || reportType === 'comparativo';
        const showCustomer = reportType === 'receber' || reportType === 'comparativo';

        supplierGroup.classList.toggle('d-none', !showSupplier);
        customerGroup.classList.toggle('d-none', !showCustomer);
        if (supplierSelect) {
            supplierSelect.disabled = !showSupplier;
        }
        if (customerSelect) {
            customerSelect.disabled = !showCustomer;
        }
    };

    document.querySelectorAll('[data-filter-select]').forEach((input) => {
        const select = document.getElementById(input.dataset.filterSelect || '');
        if (!select) {
            return;
        }

        input.addEventListener('input', () => {
            const term = input.value.trim().toLocaleLowerCase('pt-BR');
            Array.from(select.options).forEach((option) => {
                const matches = option.text.toLocaleLowerCase('pt-BR').includes(term);
                option.hidden = !matches && !option.selected;
            });
        });
    });

    if (type) {
        type.addEventListener('change', syncEntityFilters);
    }
    syncEntityFilters();
})();
