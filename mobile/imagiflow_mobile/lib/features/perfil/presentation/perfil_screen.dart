import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/auth/auth_controller.dart';
import '../../../core/theme/app_theme.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profile = ref.watch(authControllerProvider).profile ?? const <String, dynamic>{};
    final tenant = (profile['tenant'] as Map?)?.cast<String, dynamic>() ?? const <String, dynamic>{};
    return SafeArea(
      child: ListView(padding: const EdgeInsets.all(20), children: [
        Row(children: [
          CircleAvatar(radius: 32, backgroundColor: const Color(0x1A00529B), foregroundColor: AppColors.primary, child: Text('${profile['name'] ?? 'U'}'.substring(0, 1).toUpperCase(), style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold))),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('${profile['name'] ?? 'Usuário'}', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)), Text('${profile['email'] ?? ''}', style: const TextStyle(color: AppColors.muted)), Text('${tenant['name'] ?? ''}', style: const TextStyle(color: AppColors.muted))]))
        ]),
        const SizedBox(height: 28),
        _tile(context, Icons.fingerprint_outlined, 'Entrar com biometria', 'Ative nas configurações do dispositivo', () async {
          await ref.read(authControllerProvider.notifier).setBiometrics(true);
          if (context.mounted) _message(context, 'Biometria habilitada para este dispositivo.', true);
        }),
        _tile(context, Icons.devices_outlined, 'Dispositivos conectados', 'Consulte e revogue sessões ativas', () => _message(context, 'Gerenciamento disponível na API de dispositivos.', true)),
        _tile(context, Icons.lock_reset_outlined, 'Alterar senha', 'Atualize sua senha de acesso', () => _message(context, 'A alteração de senha está disponível no endpoint de perfil.', true)),
        const Divider(height: 32),
        _tile(context, Icons.logout_outlined, 'Sair', 'Encerrar esta sessão', () => ref.read(authControllerProvider.notifier).logout(), danger: true),
        _tile(context, Icons.domain_outlined, 'Trocar empresa', 'Limpar sessão e selecionar outro domínio', () => ref.read(authControllerProvider.notifier).logout(changeTenant: true), danger: true),
      ]),
    );
  }

  Widget _tile(BuildContext context, IconData icon, String title, String subtitle, VoidCallback onTap, {bool danger = false}) => Card(
        margin: const EdgeInsets.only(bottom: 10),
        child: ListTile(onTap: onTap, leading: Icon(icon, color: danger ? AppColors.danger : AppColors.primary), title: Text(title, style: TextStyle(color: danger ? AppColors.danger : null)), subtitle: Text(subtitle), trailing: const Icon(Icons.chevron_right)),
      );

  void _message(BuildContext context, String text, bool success) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text), backgroundColor: success ? AppColors.success : AppColors.danger));
}
