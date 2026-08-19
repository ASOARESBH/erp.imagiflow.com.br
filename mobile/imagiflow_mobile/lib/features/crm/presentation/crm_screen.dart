import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class CrmScreen extends StatelessWidget {
  const CrmScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('CRM')),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          _entry(context, 'Leads', Icons.person_add_alt_outlined, '/api/mobile/v1/crm/leads', (item) => '${item['nome_lead'] ?? 'Lead'}', (item) => '${item['cidade'] ?? ''} · ${item['status_lead'] ?? ''}'),
          _entry(context, 'Oportunidades', Icons.handshake_outlined, '/api/mobile/v1/crm/oportunidades', (item) => '${item['titulo_oportunidade'] ?? 'Oportunidade'}', (item) => '${item['nome_lead'] ?? item['cliente_nome'] ?? ''} · ${item['etapa_funil'] ?? ''}'),
          _entry(context, 'Propostas', Icons.request_quote_outlined, '/api/mobile/v1/crm/propostas', (item) => '${item['numero'] ?? ''} ${item['titulo'] ?? 'Proposta'}', (item) => '${item['cliente_nome'] ?? ''} · ${item['status'] ?? ''}'),
          Card(child: ListTile(leading: const CircleAvatar(child: Icon(Icons.account_tree_outlined)), title: const Text('Funil de vendas'), subtitle: const Text('Qualificação, proposta, negociação e fechamento'), trailing: const Icon(Icons.chevron_right), onTap: () => _showPipeline(context))),
        ]),
      );

  Widget _entry(BuildContext context, String title, IconData icon, String path, ResourceTitle itemTitle, ResourceSubtitle itemSubtitle) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Card(child: ListTile(leading: CircleAvatar(child: Icon(icon)), title: Text(title), trailing: const Icon(Icons.chevron_right), onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ResourceListScreen(title: title, path: path, itemTitle: itemTitle, itemSubtitle: itemSubtitle)))),
      );

  void _showPipeline(BuildContext context) => showDialog<void>(context: context, builder: (_) => const AlertDialog(title: Text('Funil de vendas'), content: Text('A visualização do funil é atualizada pelos dados do CRM e respeita as permissões do usuário.')));
}
