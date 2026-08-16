document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const normalize = (value) => value.replace(/[^a-zA-Z0-9\s-]/g, '').trim().toLowerCase()
        .replace(/\s+/g, '-').replace(/-+/g, '-');

    const companyName = $('nome_fantasia');
    const slug = $('slug');
    if (companyName && slug) {
        companyName.addEventListener('blur', () => {
            if (!slug.value) {
                slug.value = normalize(companyName.value);
                slug.dispatchEvent(new Event('input'));
            }
        });
    }
    const lookup = async (button, endpoint, field, populate) => {
        if (!button || !field) return;
        button.addEventListener('click', async () => {
            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Consultando';
            try {
                const response = await fetch(`${endpoint}?${field.name}=${encodeURIComponent(field.value)}`, {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
                });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Consulta indisponível.');
                populate(payload.data || {});
            } catch (error) {
                window.alert(error.message || 'Não foi possível realizar a consulta.');
            } finally {
                button.disabled = false;
                button.innerHTML = original;
            }
        });
    };

    lookup($('btnBuscarCnpj'), '/painel/empresas/buscar-cnpj', $('cnpj'), (data) => {
        const set = (id, value) => { const el = $(id); if (el && value) el.value = value; };
        set('razao_social', data.razao_social || data.nome || data.name);
        set('nome_fantasia', data.nome_fantasia || data.fantasia);
        set('email', data.email);
        set('cep', data.cep);
        set('endereco', data.logradouro || data.endereco);
        set('bairro', data.bairro);
        set('cidade', data.municipio || data.cidade);
        set('estado', data.uf || data.estado);
    });
    lookup($('btnBuscarCep'), '/painel/empresas/buscar-cep', $('cep'), (data) => {
        const set = (id, value) => { const el = $(id); if (el && value) el.value = value; };
        set('endereco', data.logradouro || data.endereco);
        set('bairro', data.bairro);
        set('cidade', data.localidade || data.cidade);
        set('estado', data.uf || data.estado);
    });

    const companyForm = $('saasCompanyForm');
    if (companyForm) {
        companyForm.addEventListener('submit', (event) => {
            const invalid = Array.from(companyForm.elements).find((field) => (
                field instanceof HTMLElement && typeof field.checkValidity === 'function' && !field.checkValidity()
            ));

            if (invalid) {
                event.preventDefault();
                const pane = invalid.closest('.tab-pane');
                if (pane && pane.id) {
                    const tab = document.querySelector(`[data-bs-target="#${pane.id}"]`);
                    if (tab && window.bootstrap) {
                        window.bootstrap.Tab.getOrCreateInstance(tab).show();
                    }
                }
                window.setTimeout(() => {
                    invalid.focus();
                    invalid.reportValidity();
                }, 120);
                return;
            }

            const submit = $('btnSalvarEmpresa');
            if (submit) {
                submit.disabled = true;
                submit.dataset.originalText = submit.innerHTML;
                submit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvando cadastro...';
            }
        });
    }

    const modal = $('impersonateModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            $('impersonateTenantName').textContent = trigger.dataset.tenantName;
            $('impersonateForm').action = `/painel/empresas/${trigger.dataset.tenantId}/impersonar`;
        });
    }
});
