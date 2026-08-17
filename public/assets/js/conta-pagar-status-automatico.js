(() => {
    'use strict';

    const dueDate = document.getElementById('data_vencimento');
    const paymentDate = document.getElementById('data_pagamento');
    const status = document.getElementById('status');
    const paymentNotice = document.getElementById('aviso_status_pago_automatico');
    const recurring = document.getElementById('recorrente');
    const recurrenceType = document.getElementById('recorrencia_tipo');
    const installments = document.getElementById('recorrencia_intervalo');
    const recurrenceNotice = document.getElementById('aviso_recorrencia_parcelas');

    const applyAutomaticStatus = () => {
        if (!dueDate || !paymentDate || !status || !paymentNotice) {
            return;
        }

        const due = dueDate.value;
        const paid = paymentDate.value;
        const shouldMarkPaid = due !== '' && paid !== '' && paid >= due;

        paymentNotice.classList.toggle('d-none', !shouldMarkPaid);
        if (shouldMarkPaid) {
            status.value = 'paga';
        }
    };

    const syncRecurring = () => {
        if (!recurring || !recurrenceType || !installments) {
            return;
        }

        const total = Number.parseInt(installments.value, 10) || 0;
        const active = recurrenceType.value !== '' && total > 1;
        recurring.checked = active || recurring.checked;

        if (recurrenceNotice) {
            recurrenceNotice.classList.toggle('d-none', !active);
            if (active) {
                recurrenceNotice.textContent = `${total} parcelas serão geradas a partir da data de vencimento informada.`;
            }
        }
    };

    if (dueDate) {
        dueDate.addEventListener('change', applyAutomaticStatus);
    }
    if (paymentDate) {
        paymentDate.addEventListener('change', applyAutomaticStatus);
    }
    if (recurrenceType) {
        recurrenceType.addEventListener('change', syncRecurring);
    }
    if (installments) {
        installments.addEventListener('input', syncRecurring);
        installments.addEventListener('change', syncRecurring);
    }

    applyAutomaticStatus();
    syncRecurring();
})();
