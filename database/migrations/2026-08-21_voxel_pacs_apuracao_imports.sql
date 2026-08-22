-- Integração ImagiFlow ↔ VOXEL PACS: controle de idempotência da apuração.
-- Data: 2026-08-21 | Compatível com MySQL 5.7 / HostGator.
-- Execute após backup e confirme a inexistência prévia da tabela.

CREATE TABLE IF NOT EXISTS apuracao_voxel_imports (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    apuracao_id INT(11) NOT NULL,
    source_reference VARCHAR(191) NOT NULL,
    request_id VARCHAR(64) NULL,
    item_hash CHAR(64) NOT NULL,
    status ENUM('pendente', 'importado', 'ignorado', 'erro') NOT NULL DEFAULT 'pendente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_apuracao_voxel_usuario_source (usuario_id, source_reference),
    KEY idx_apuracao_voxel_apuracao_status (apuracao_id, status),
    KEY idx_apuracao_voxel_usuario_created (usuario_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- VALIDAÇÃO
SELECT COUNT(*) AS total_importacoes_voxel FROM apuracao_voxel_imports;
SHOW INDEX FROM apuracao_voxel_imports;

-- ROLLBACK (executar somente se a integração ainda não possuir histórico necessário)
-- DROP TABLE apuracao_voxel_imports;
