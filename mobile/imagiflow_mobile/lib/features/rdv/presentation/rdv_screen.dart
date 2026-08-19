import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class RdvScreen extends StatelessWidget {
  const RdvScreen({super.key});

  @override
  Widget build(BuildContext context) => ResourceListScreen(
        title: 'RDV e despesas',
        path: '/api/mobile/v1/rdv/viagens',
        itemTitle: (item) => '${item['codigo'] ?? ''} ${item['nome'] ?? 'Viagem'}',
        itemSubtitle: (item) => '${item['cidade'] ?? ''} · ${item['periodo_inicio'] ?? ''} a ${item['periodo_fim'] ?? ''} · ${item['status'] ?? ''}',
        fab: FloatingActionButton.extended(onPressed: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Registre despesas e comprovantes a partir do detalhe da viagem.'))), icon: const Icon(Icons.add_road_outlined), label: const Text('Nova viagem')),
      );
}
