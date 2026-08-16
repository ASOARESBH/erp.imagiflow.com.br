<?php

namespace App\Services;

use App\Core\Logger;
use App\Models\PlanoConta;
use RuntimeException;

/**
 * Copia um plano de contas inicial para cada tenant sem criar dependência entre
 * empresas. Após a cópia, cada conta pertence exclusivamente ao tenant e pode
 * ser editada, inativada ou complementada no ERP.
 */
class PlanoContasPadraoService
{
    private PlanoConta $planoContaModel;
    private Logger $logger;

    public function __construct()
    {
        $this->planoContaModel = new PlanoConta();
        $this->logger = new Logger();
    }

    /**
     * Retorna o modelo inicial. Os códigos de modelo são imutáveis e usados
     * somente para impedir a duplicação na importação; não restringem edições.
     */
    public static function template(): array
    {
        return [
            ['modelo' => 'R1', 'pai' => null, 'codigo' => '1', 'nome' => 'RECEITAS', 'tipo' => 'Receita', 'nivel' => 1],
            ['modelo' => 'R101', 'pai' => 'R1', 'codigo' => '1.01', 'nome' => 'Serviços de Saúde', 'tipo' => 'Receita', 'nivel' => 2],
            ['modelo' => 'R10101', 'pai' => 'R101', 'codigo' => '1.01.001', 'nome' => 'Diagnóstico por Imagem e Laudos', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10102', 'pai' => 'R101', 'codigo' => '1.01.002', 'nome' => 'Consultas e Procedimentos', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10103', 'pai' => 'R101', 'codigo' => '1.01.003', 'nome' => 'Telemedicina e Segunda Opinião', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10104', 'pai' => 'R101', 'codigo' => '1.01.004', 'nome' => 'Locação de Equipamentos Médicos', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R102', 'pai' => 'R1', 'codigo' => '1.02', 'nome' => 'Tecnologia e Sistemas de Gestão', 'tipo' => 'Receita', 'nivel' => 2],
            ['modelo' => 'R10201', 'pai' => 'R102', 'codigo' => '1.02.001', 'nome' => 'Licenças e Assinaturas de Software', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10202', 'pai' => 'R102', 'codigo' => '1.02.002', 'nome' => 'Implantação e Parametrização', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10203', 'pai' => 'R102', 'codigo' => '1.02.003', 'nome' => 'Suporte e Manutenção de Software', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10204', 'pai' => 'R102', 'codigo' => '1.02.004', 'nome' => 'Integrações, APIs e Projetos Especiais', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R103', 'pai' => 'R1', 'codigo' => '1.03', 'nome' => 'Comercialização de Equipamentos Médicos', 'tipo' => 'Receita', 'nivel' => 2],
            ['modelo' => 'R10301', 'pai' => 'R103', 'codigo' => '1.03.001', 'nome' => 'Venda de Equipamentos Médicos', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10302', 'pai' => 'R103', 'codigo' => '1.03.002', 'nome' => 'Manutenção de Equipamentos Médicos', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10303', 'pai' => 'R103', 'codigo' => '1.03.003', 'nome' => 'Venda de Peças e Acessórios', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R104', 'pai' => 'R1', 'codigo' => '1.04', 'nome' => 'Outras Receitas', 'tipo' => 'Receita', 'nivel' => 2],
            ['modelo' => 'R10401', 'pai' => 'R104', 'codigo' => '1.04.001', 'nome' => 'Receitas Financeiras', 'tipo' => 'Receita', 'nivel' => 3],
            ['modelo' => 'R10402', 'pai' => 'R104', 'codigo' => '1.04.002', 'nome' => 'Reembolsos e Recuperações', 'tipo' => 'Receita', 'nivel' => 3],

            ['modelo' => 'D2', 'pai' => null, 'codigo' => '2', 'nome' => 'DESPESAS', 'tipo' => 'Despesa', 'nivel' => 1],
            ['modelo' => 'D201', 'pai' => 'D2', 'codigo' => '2.01', 'nome' => 'Custos Assistenciais Diretos', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20101', 'pai' => 'D201', 'codigo' => '2.01.001', 'nome' => 'Profissionais de Saúde e Plantões', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20102', 'pai' => 'D201', 'codigo' => '2.01.002', 'nome' => 'Laudos e Serviços Terceirizados', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20103', 'pai' => 'D201', 'codigo' => '2.01.003', 'nome' => 'Materiais, Insumos e Contrastes', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20104', 'pai' => 'D201', 'codigo' => '2.01.004', 'nome' => 'Exames e Serviços Diagnósticos Terceirizados', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20105', 'pai' => 'D201', 'codigo' => '2.01.005', 'nome' => 'Comissões e Repasses Clínicos', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D202', 'pai' => 'D2', 'codigo' => '2.02', 'nome' => 'Equipamentos Médicos', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20201', 'pai' => 'D202', 'codigo' => '2.02.001', 'nome' => 'Aquisição e Locação de Equipamentos', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20202', 'pai' => 'D202', 'codigo' => '2.02.002', 'nome' => 'Manutenção Preventiva e Corretiva', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20203', 'pai' => 'D202', 'codigo' => '2.02.003', 'nome' => 'Calibração e Controle de Qualidade', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20204', 'pai' => 'D202', 'codigo' => '2.02.004', 'nome' => 'Peças, Acessórios e Suprimentos Técnicos', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20205', 'pai' => 'D202', 'codigo' => '2.02.005', 'nome' => 'Depreciação de Equipamentos', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D203', 'pai' => 'D2', 'codigo' => '2.03', 'nome' => 'Tecnologia da Informação', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20301', 'pai' => 'D203', 'codigo' => '2.03.001', 'nome' => 'Licenças e Assinaturas de Software', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20302', 'pai' => 'D203', 'codigo' => '2.03.002', 'nome' => 'Nuvem, Hospedagem e Infraestrutura', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20303', 'pai' => 'D203', 'codigo' => '2.03.003', 'nome' => 'Integrações, APIs e Comunicação', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20304', 'pai' => 'D203', 'codigo' => '2.03.004', 'nome' => 'Desenvolvimento, Suporte e Consultoria de TI', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20305', 'pai' => 'D203', 'codigo' => '2.03.005', 'nome' => 'Segurança da Informação e LGPD', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D204', 'pai' => 'D2', 'codigo' => '2.04', 'nome' => 'Pessoas e Encargos', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20401', 'pai' => 'D204', 'codigo' => '2.04.001', 'nome' => 'Salários e Pró-Labore', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20402', 'pai' => 'D204', 'codigo' => '2.04.002', 'nome' => 'Encargos Trabalhistas e Benefícios', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20403', 'pai' => 'D204', 'codigo' => '2.04.003', 'nome' => 'Treinamentos e Desenvolvimento Profissional', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D205', 'pai' => 'D2', 'codigo' => '2.05', 'nome' => 'Despesas Administrativas', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20501', 'pai' => 'D205', 'codigo' => '2.05.001', 'nome' => 'Aluguel, Condomínio e IPTU', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20502', 'pai' => 'D205', 'codigo' => '2.05.002', 'nome' => 'Energia, Água, Gases Medicinais e Utilidades', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20503', 'pai' => 'D205', 'codigo' => '2.05.003', 'nome' => 'Telefonia, Internet e Correios', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20504', 'pai' => 'D205', 'codigo' => '2.05.004', 'nome' => 'Serviços Contábeis, Jurídicos e Administrativos', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20505', 'pai' => 'D205', 'codigo' => '2.05.005', 'nome' => 'Seguros, Limpeza e Segurança Patrimonial', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D206', 'pai' => 'D2', 'codigo' => '2.06', 'nome' => 'Comercial e Marketing', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20601', 'pai' => 'D206', 'codigo' => '2.06.001', 'nome' => 'Marketing e Publicidade', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20602', 'pai' => 'D206', 'codigo' => '2.06.002', 'nome' => 'Comissões Comerciais', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20603', 'pai' => 'D206', 'codigo' => '2.06.003', 'nome' => 'Eventos, Congressos e Relacionamento', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20604', 'pai' => 'D206', 'codigo' => '2.06.004', 'nome' => 'Viagens, RDV e Representação', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D207', 'pai' => 'D2', 'codigo' => '2.07', 'nome' => 'Tributos e Despesas Financeiras', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20701', 'pai' => 'D207', 'codigo' => '2.07.001', 'nome' => 'Impostos sobre Serviços e Vendas', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20702', 'pai' => 'D207', 'codigo' => '2.07.002', 'nome' => 'Tarifas Bancárias e Meios de Pagamento', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20703', 'pai' => 'D207', 'codigo' => '2.07.003', 'nome' => 'Juros, Multas e Encargos Financeiros', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D208', 'pai' => 'D2', 'codigo' => '2.08', 'nome' => 'Outras Despesas Operacionais', 'tipo' => 'Despesa', 'nivel' => 2],
            ['modelo' => 'D20801', 'pai' => 'D208', 'codigo' => '2.08.001', 'nome' => 'Perdas, Ajustes e Baixas', 'tipo' => 'Despesa', 'nivel' => 3],
            ['modelo' => 'D20802', 'pai' => 'D208', 'codigo' => '2.08.002', 'nome' => 'Despesas Não Recorrentes', 'tipo' => 'Despesa', 'nivel' => 3],
        ];
    }

    /**
     * Importação idempotente: preserva contas existentes e acrescenta somente
     * itens ausentes do modelo padrão para o tenant informado.
     */
    public function seedForTenant(int $tenantId, int $ownerUserId): array
    {
        if ($tenantId <= 0 || $ownerUserId <= 0) {
            throw new RuntimeException('Tenant e usuário proprietário são obrigatórios para importar o plano padrão.');
        }

        $inserted = 0;
        $skipped = 0;
        foreach (self::template() as $item) {
            if ($this->planoContaModel->findByTenantAndTemplateCode($tenantId, $item['modelo'])) {
                $skipped++;
                continue;
            }

            $parentId = null;
            if ($item['pai'] !== null) {
                $parent = $this->planoContaModel->findByTenantAndTemplateCode($tenantId, $item['pai']);
                if (!$parent) {
                    throw new RuntimeException('Conta-pai do modelo padrão não localizada: ' . $item['pai']);
                }
                $parentId = (int) $parent->id;
            }

            $id = $this->planoContaModel->createForTenant([
                'tenant_id' => $tenantId,
                'usuario_id' => $ownerUserId,
                'codigo' => $item['codigo'],
                'nome' => $item['nome'],
                'tipo' => $item['tipo'],
                'nivel' => $item['nivel'],
                'conta_pai_id' => $parentId,
                'modelo_padrao_codigo' => $item['modelo'],
                'status' => 'ativo',
            ]);
            if (!$id) {
                throw new RuntimeException('Não foi possível inserir a conta padrão ' . $item['codigo'] . '.');
            }
            $inserted++;
        }

        $this->logger->info('Plano de contas padrão processado.', [
            'tenant_id' => $tenantId,
            'owner_user_id' => $ownerUserId,
            'inserted' => $inserted,
            'skipped' => $skipped,
        ]);

        return ['inserted' => $inserted, 'skipped' => $skipped, 'total' => count(self::template())];
    }
}
