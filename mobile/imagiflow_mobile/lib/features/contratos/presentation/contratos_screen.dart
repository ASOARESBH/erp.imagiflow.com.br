import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class ContratosScreen extends StatelessWidget {
  const ContratosScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Contratos e apuração')),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          _entry(context, 'Contratos', Icons.description_outlined, '/api/mobile/v1/contratos', (item) => '${item['numero'] ?? ''} ${item['nome'] ?? 'Contrato'}', (item) => '${item['cliente_nome'] ?? ''} · ${item['status'] ?? ''}'),
          _entry(context, 'Apuração de clientes', Icons.receipt_long_outlined, '/api/mobile/v1/apuracao/cliente', (item) => '${item['numero'] ?? 'Apuração'}', (item) => '${item['contrato_nome'] ?? ''} · ${item['status'] ?? ''}'),
          _entry(context, 'Apuração de prestadores', Icons.medical_services_outlined, '/api/mobile/v1/apuracao/prestador', (item) => '${item['numero'] ?? 'Apuração'}', (item) => '${item['contrato_nome'] ?? ''} · ${item['status'] ?? ''}'),
        ]),
      );

  Widget _entry(BuildContext context, String title, IconData icon, String path, ResourceTitle itemTitle, ResourceSubtitle itemSubtitle) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Card(child: ListTile(leading: CircleAvatar(child: Icon(icon)), title: Text(title), trailing: const Icon(Icons.chevron_right), onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ResourceListScreen(title: title, path: path, itemTitle: itemTitle, itemSubtitle: itemSubtitle)))),
      );
}
