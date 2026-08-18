(() => {
    'use strict';

    const type = document.getElementById('tipo_relatorio');
    const supplierGroup = document.getElementById('grupoFornecedor');
    const customerGroup = document.getElementById('grupoCliente');
    const supplierSelect = document.getElementById('fornecedor_ids');
    const customerSelect = document.getElementById('cliente_ids');

    if (!type || !supplierGroup || !customerGroup) {
        return;
    }

    const syncEntityFilters = () => {
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

    type.addEventListener('change', syncEntityFilters);
    syncEntityFilters();
})();
