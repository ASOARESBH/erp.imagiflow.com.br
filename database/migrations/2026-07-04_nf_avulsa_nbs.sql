-- ============================================================
-- Migration: 2026-07-04 — NF Avulsa em Contas a Receber + NBS em config_nfs
-- Compatível com: MySQL 5.7 / MariaDB 10.x (HostGator)
-- Descrição:
--   1. Adiciona `emitir_nf_avulsa` em contas_receber
--      → flag que indica que a conta deve gerar uma NFS-e avulsa
--   2. Adiciona `nf_avulsa_status` em contas_receber
--      → rastreia o estado da emissão: pendente | agendada | emitida | erro
--   3. Adiciona `nf_avulsa_nota_id` em contas_receber
--      → ID da nota fiscal no Asaas (ex: nfi_xxx)
--   4. Adiciona `nbs_codigo` em config_nfs
--      → Código NBS (Nomenclatura Brasileira de Serviços) exigido
--        pelo Portal Nacional de NFS-e (ex: 1.07.00.00.00)
-- ============================================================

-- 1. contas_receber: campos de NF avulsa
ALTER TABLE contas_receber
    ADD COLUMN emitir_nf_avulsa  TINYINT(1)   NOT NULL DEFAULT 0
        COMMENT '1 = emitir NFS-e avulsa ao salvar/receber esta conta'
        AFTER observacoes,
    ADD COLUMN nf_avulsa_status  VARCHAR(20)  NULL DEFAULT NULL
        COMMENT 'Status da NF avulsa: pendente | agendada | emitida | erro'
        AFTER emitir_nf_avulsa,
    ADD COLUMN nf_avulsa_nota_id VARCHAR(50)  NULL DEFAULT NULL
        COMMENT 'ID da nota fiscal no Asaas (ex: nfi_xxx)'
        AFTER nf_avulsa_status;

-- Índice para facilitar consultas de NFs pendentes
CREATE INDEX idx_cr_nf_avulsa ON contas_receber (emitir_nf_avulsa, nf_avulsa_status);

-- 2. config_nfs: código NBS (Nomenclatura Brasileira de Serviços)
ALTER TABLE config_nfs
    ADD COLUMN nbs_codigo  VARCHAR(20)  NULL DEFAULT NULL
        COMMENT 'Código NBS (ex: 1.07.00.00.00) exigido pelo Portal Nacional de NFS-e'
        AFTER cnae;
