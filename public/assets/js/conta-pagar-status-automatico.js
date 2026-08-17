(() => {
    'use strict';

    const dueDate = document.getElementById('data_vencimento');
    const paymentDate = document.getElementById('data_pagamento');
    const status = document.getElementById('status');
    const notice = document.getElementById('aviso_status_pago_automatico');

    if (!dueDate || !paymentDate || !status || !notice) {
        return;
    }

    const applyAutomaticStatus = () => {
        const due = dueDate.value;
        const paid = paymentDate.value;
        const shouldMarkPaid = due !== '' && paid !== '' && paid >= due;

        notice.classList.toggle('d-none', !shouldMarkPaid);
        if (shouldMarkPaid) {
            status.value = 'paga';
        }
    };

    dueDate.addEventListener('change', applyAutomaticStatus);
    paymentDate.addEventListener('change', applyAutomaticStatus);
    applyAutomaticStatus();
})();
