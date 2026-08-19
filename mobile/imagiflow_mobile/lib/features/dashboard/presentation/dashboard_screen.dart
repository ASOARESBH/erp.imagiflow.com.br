import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/imagiflow_api_client.dart';
import '../../../core/auth/auth_controller.dart';
import '../../../core/theme/app_theme.dart';
import '../../clientes/presentation/clientes_screen.dart';
import '../../contratos/presentation/contratos_screen.dart';
import '../../crm/presentation/crm_screen.dart';
import '../../financeiro/presentation/financeiro_screen.dart';
import '../../fornecedores/presentation/fornecedores_screen.dart';
import '../../manutencao/presentation/manutencao_screen.dart';
import '../../notificacoes/presentation/notificacoes_screen.dart';
import '../../perfil/presentation/perfil_screen.dart';
import '../../rdv/presentation/rdv_screen.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  int _index = 0;
  Future<Map<String, dynamic>>? _summary;

  @override
  void initState() {
    super.initState();
    _summary = ref.read(apiClientProvider).get('/api/mobile/v1/dashboard/resumo');
  }

  void _refresh() => setState(() => _summary = ref.read(apiClientProvider).get('/api/mobile/v1/dashboard/resumo'));

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(authControllerProvider).profile ?? const <String, dynamic>{};
    final pages = [
      _DashboardContent(summary: _summary!, onRefresh: _refresh, profile: profile),
      _ModulesScreen(profile: profile),
      const NotificationsScreen(),
      const ProfileScreen(),
    ];
    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) => setState(() => _index = index),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Início'),
          NavigationDestination(icon: Icon(Icons.grid_view_outlined), selectedIcon: Icon(Icons.grid_view), label: 'Módulos'),
          NavigationDestination(icon: Icon(Icons.notifications_outlined), selectedIcon: Icon(Icons.notifications), label: 'Alertas'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Perfil'),
        ],
      ),
    );
  }
}

class _DashboardContent extends StatelessWidget {
  const _DashboardContent({required this.summary, required this.onRefresh, required this.profile});
  final Future<Map<String, dynamic>> summary;
  final VoidCallback onRefresh;
  final Map<String, dynamic> profile;

  @override
  Widget build(BuildContext context) => SafeArea(
        child: FutureBuilder<Map<String, dynamic>>(
          future: summary,
          builder: (context, snapshot) {
            final content = snapshot.connectionState == ConnectionState.waiting
                ? const Center(child: CircularProgressIndicator())
                : snapshot.hasError
                    ? _ErrorState(message: _readError(snapshot.error), onRetry: onRefresh)
                    : _DashboardBody(profile: profile, data: snapshot.data ?? const {});
            return RefreshIndicator(onRefresh: () async => onRefresh(), child: content);
          },
        ),
      );
}

class _DashboardBody extends StatelessWidget {
  const _DashboardBody({required this.profile, required this.data});
  final Map<String, dynamic> profile;
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final kpis = (data['kpis'] as Map?)?.cast<String, dynamic>() ?? const <String, dynamic>{};
    final finance = (kpis['financeiro'] as Map?)?.cast<String, dynamic>() ?? const <String, dynamic>{};
    final payables = (finance['contas_pagar'] as Map?)?.cast<String, dynamic>() ?? const <String, dynamic>{};
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(20),
      children: [
        Text('Olá, ${profile['name'] ?? 'usuário'}', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Text(profile['tenant'] is Map ? '${(profile['tenant'] as Map)['name'] ?? ''}' : '', style: const TextStyle(color: AppColors.muted)),
        const SizedBox(height: 24),
        Text('Resumo do dia', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        const SizedBox(height: 12),
        GridView.count(
          crossAxisCount: MediaQuery.sizeOf(context).width > 600 ? 3 : 2,
          childAspectRatio: 1.6,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          children: [
            _KpiCard(icon: Icons.people_outline, label: 'Clientes ativos', value: '${kpis['clientes_ativos'] ?? 0}'),
            _KpiCard(icon: Icons.trending_up_outlined, label: 'Leads abertos', value: '${kpis['leads_abertos'] ?? 0}'),
            _KpiCard(icon: Icons.warning_amber_outlined, label: 'Contas a pagar vencidas', value: '${kpis['contas_pagar_vencidas'] ?? 0}', danger: true),
            _KpiCard(icon: Icons.build_outlined, label: 'OS pendentes', value: '${kpis['os_pendentes'] ?? 0}'),
            _KpiCard(icon: Icons.route_outlined, label: 'Viagens em aberto', value: '${kpis['viagens_abertas'] ?? 0}'),
            _KpiCard(icon: Icons.account_balance_wallet_outlined, label: 'A pagar em aberto', value: _money(payables['open_total'])),
          ],
        ),
        const SizedBox(height: 24),
        Text('Ações rápidas', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        for (final action in (data['quick_actions'] as List? ?? const []))
          Card(
            child: ListTile(
              leading: const CircleAvatar(backgroundColor: Color(0x1A00529B), foregroundColor: AppColors.primary, child: Icon(Icons.add)),
              title: Text('${(action as Map)['label'] ?? 'Ação'}'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Ação disponível no módulo correspondente.'))),
            ),
          ),
      ],
    );
  }

  String _money(dynamic value) => 'R$ ${double.tryParse('$value')?.toStringAsFixed(2) ?? '0,00'}';
}

class _ModulesScreen extends StatelessWidget {
  const _ModulesScreen({required this.profile});
  final Map<String, dynamic> profile;

  @override
  Widget build(BuildContext context) {
    final permissions = ((profile['permissions'] as List?) ?? const []).map((value) => '$value').toSet();
    final modules = <_ModuleDestination>[
      if (permissions.contains('view_clients')) _ModuleDestination('Clientes', Icons.people_outline, const ClientesScreen()),
      if (permissions.contains('view_fornecedores')) _ModuleDestination('Fornecedores', Icons.local_shipping_outlined, const FornecedoresScreen()),
      if (permissions.contains('view_finance')) _ModuleDestination('Financeiro', Icons.account_balance_wallet_outlined, const FinanceiroScreen()),
      if (permissions.contains('view_faturamento')) _ModuleDestination('Contratos e apuração', Icons.description_outlined, const ContratosScreen()),
      if (permissions.contains('view_crm')) _ModuleDestination('CRM', Icons.hub_outlined, const CrmScreen()),
      if (permissions.contains('view_rdv')) _ModuleDestination('RDV e despesas', Icons.route_outlined, const RdvScreen()),
      if (permissions.contains('view_manutencao')) _ModuleDestination('Manutenção', Icons.build_outlined, const ManutencaoScreen()),
    ];
    return SafeArea(
      child: ListView(padding: const EdgeInsets.all(20), children: [
        Text('Módulos', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
        const SizedBox(height: 6),
        const Text('Acesse somente as áreas liberadas para seu perfil.', style: TextStyle(color: AppColors.muted)),
        const SizedBox(height: 20),
        for (final module in modules)
          Card(
            margin: const EdgeInsets.only(bottom: 10),
            child: ListTile(
              leading: CircleAvatar(backgroundColor: const Color(0x1A00529B), foregroundColor: AppColors.primary, child: Icon(module.icon)),
              title: Text(module.title),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => module.screen)),
            ),
          ),
      ]),
    );
  }
}

class _ModuleDestination {
  _ModuleDestination(this.title, this.icon, this.screen);
  final String title;
  final IconData icon;
  final Widget screen;
}

class _KpiCard extends StatelessWidget {
  const _KpiCard({required this.icon, required this.label, required this.value, this.danger = false});
  final IconData icon;
  final String label;
  final String value;
  final bool danger;

  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            Icon(icon, color: danger ? AppColors.danger : AppColors.primary),
            Text(value, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
            Text(label, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
          ]),
        ),
      );
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => ListView(physics: const AlwaysScrollableScrollPhysics(), children: [
        const SizedBox(height: 150),
        const Icon(Icons.cloud_off_outlined, size: 52, color: AppColors.muted),
        const SizedBox(height: 16),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 16),
        Center(child: OutlinedButton(onPressed: onRetry, child: const Text('Tentar novamente'))),
      ]);
}

String _readError(Object? error) => error is ApiFailure ? error.message : 'Não foi possível carregar o resumo.';
