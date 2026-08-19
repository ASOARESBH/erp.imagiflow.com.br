import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/auth/auth_controller.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/auth/presentation/tenant_screen.dart';
import 'features/dashboard/presentation/dashboard_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const ProviderScope(child: ImagiFlowApp()));
}

class ImagiFlowApp extends ConsumerStatefulWidget {
  const ImagiFlowApp({super.key});

  @override
  ConsumerState<ImagiFlowApp> createState() => _ImagiFlowAppState();
}

class _ImagiFlowAppState extends ConsumerState<ImagiFlowApp> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(authControllerProvider.notifier).restore());
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(authControllerProvider);
    return MaterialApp(
      title: 'ImagiFlow',
      debugShowCheckedModeBanner: false,
      theme: buildAppTheme(),
      home: switch ((session.initializing, session.baseUrl, session.authenticated)) {
        (true, _, _) => const _SplashScreen(),
        (false, null, _) => const TenantScreen(),
        (false, _, true) => const DashboardScreen(),
        _ => const LoginScreen(),
      },
    );
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) => const Scaffold(
        body: Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Image(image: AssetImage('assets/branding/logo-imagiflow.png'), width: 160),
            SizedBox(height: 24),
            SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2)),
          ]),
        ),
      );
}
