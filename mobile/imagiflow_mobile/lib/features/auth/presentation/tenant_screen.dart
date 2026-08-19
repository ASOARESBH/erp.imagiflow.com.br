import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/imagiflow_api_client.dart';
import '../../../core/auth/auth_controller.dart';
import '../../../core/theme/app_theme.dart';

class TenantScreen extends ConsumerStatefulWidget {
  const TenantScreen({super.key});

  @override
  ConsumerState<TenantScreen> createState() => _TenantScreenState();
}

class _TenantScreenState extends ConsumerState<TenantScreen> {
  final _formKey = GlobalKey<FormState>();
  final _companyController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _companyController.dispose();
    super.dispose();
  }

  Future<void> _continue() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      await ref.read(authControllerProvider.notifier).validateTenant(_companyController.text);
    } on ApiFailure catch (failure) {
      if (mounted) _showError(failure.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 440),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Image(image: AssetImage('assets/branding/logo-imagiflow.png'), height: 74),
                      const SizedBox(height: 44),
                      Text('Acesse sua empresa', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      const Text('Informe o domínio ou subdomínio cadastrado para sua empresa no ImagiFlow.', style: TextStyle(color: AppColors.muted)),
                      const SizedBox(height: 28),
                      TextFormField(
                        controller: _companyController,
                        keyboardType: TextInputType.url,
                        autofillHints: const [AutofillHints.url],
                        decoration: const InputDecoration(labelText: 'Empresa', hintText: 'empresa.imagiflow.com.br', prefixIcon: Icon(Icons.business_outlined)),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Informe o domínio da empresa.' : null,
                        onFieldSubmitted: (_) => _continue(),
                      ),
                      const SizedBox(height: 20),
                      ElevatedButton(
                        onPressed: _loading ? null : _continue,
                        child: _loading ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Continuar'),
                      ),
                      const SizedBox(height: 20),
                      const Text('A empresa é validada antes do login. Seus dados não são enviados a domínios não cadastrados.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: AppColors.muted)),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      );

  void _showError(String message) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message), backgroundColor: AppColors.danger));
}
