import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class ClientesScreen extends StatelessWidget {
  const ClientesScreen({super.key});

  @override
  Widget build(BuildContext context) => ResourceListScreen(
        title: 'Clientes',
        path: '/api/mobile/v1/clientes',
        itemTitle: (item) => '${item['nome_fantasia'] ?? item['razao_social'] ?? 'Cliente'}',
        itemSubtitle: (item) => [item['cpf_cnpj'], item['cidade'], item['status']].where((value) => value != null && '$value'.isNotEmpty).join(' · '),
        fab: FloatingActionButton.extended(onPressed: () => _notice(context), icon: const Icon(Icons.person_add_alt_1_outlined), label: const Text('Novo')),
      );

  void _notice(BuildContext context) => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('O cadastro de cliente pode ser iniciado pelo menu de ações rápidas.')));
}
