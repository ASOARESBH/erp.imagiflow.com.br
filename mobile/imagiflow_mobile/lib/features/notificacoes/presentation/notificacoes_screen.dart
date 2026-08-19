import 'package:flutter/material.dart';

import '../../shared/resource_list_screen.dart';

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) => ResourceListScreen(
        title: 'Notificações',
        path: '/api/mobile/v1/notificacoes',
        itemTitle: (item) => '${item['titulo'] ?? 'Notificação'}',
        itemSubtitle: (item) => '${item['mensagem'] ?? ''} · ${item['created_at'] ?? ''}',
      );
}
