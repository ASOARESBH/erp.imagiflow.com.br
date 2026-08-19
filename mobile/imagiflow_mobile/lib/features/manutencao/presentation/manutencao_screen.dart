import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class ManutencaoScreen extends StatelessWidget {
  const ManutencaoScreen({super.key});

  @override
  Widget build(BuildContext context) => ResourceListScreen(
        title: 'Ordens de serviço',
        path: '/api/mobile/v1/manutencao/ordens',
        itemTitle: (item) => '${item['numero_os'] ?? item['numero'] ?? 'OS'} · ${item['cliente_nome'] ?? ''}',
        itemSubtitle: (item) => '${item['motivo_chamado'] ?? item['descricao_problema'] ?? ''} · ${item['status'] ?? ''}',
        fab: FloatingActionButton.extended(onPressed: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('A criação de OS requer os dados do cliente e do chamado.'))), icon: const Icon(Icons.add_task_outlined), label: const Text('Nova OS')),
      );
}
