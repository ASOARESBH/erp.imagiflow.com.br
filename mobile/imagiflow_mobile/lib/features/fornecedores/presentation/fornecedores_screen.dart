import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class FornecedoresScreen extends StatelessWidget {
  const FornecedoresScreen({super.key});

  @override
  Widget build(BuildContext context) => ResourceListScreen(
        title: 'Fornecedores',
        path: '/api/mobile/v1/fornecedores',
        itemTitle: (item) => '${item['nome_fantasia'] ?? item['nome'] ?? 'Fornecedor'}',
        itemSubtitle: (item) => [item['documento'], item['email'], item['status']].where((value) => value != null && '$value'.isNotEmpty).join(' · '),
        fab: FloatingActionButton.extended(onPressed: () => _notice(context), icon: const Icon(Icons.add_business_outlined), label: const Text('Novo')),
      );

  void _notice(BuildContext context) => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Use o formulário de fornecedor disponível no próximo ciclo de atualização.')));
}
