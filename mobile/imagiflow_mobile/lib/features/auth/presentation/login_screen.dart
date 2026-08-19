import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/imagiflow_api_client.dart';
import '../../../core/auth/auth_controller.dart';
import '../../../core/theme/app_theme.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _hidden = true;
  bool _loading = false;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final authenticated = await ref.read(authControllerProvider.notifier).login(
            email: _email.text.trim(),
            password: _password.text,
            deviceName: Platform.isIOS ? 'iPhone/iPad' : 'Android',
            platform: Platform.isIOS ? 'ios' : 'android',
          );
      if (authenticated || !mounted) return;
      await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const TwoFactorScreen()));
    } on ApiFailure catch (failure) {
      if (mounted) _message(failure.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _forgotPassword() async {
    final controller = TextEditingController(text: _email.text.trim());
    final email = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Recuperar senha'),
        content: TextField(controller: controller, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'E-mail')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, controller.text), child: const Text('Enviar')),
        ],
      ),
    );
    if (email == null || !email.contains('@')) return;
    try {
      await ref.read(authControllerProvider.notifier).requestPasswordReset(email);
      if (mounted) _message('Se o e-mail estiver cadastrado, você receberá as instruções de recuperação.', success: true);
    } on ApiFailure catch (failure) {
      if (mounted) _message(failure.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final baseUrl = ref.watch(authControllerProvider).baseUrl ?? '';
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Form(
                key: _formKey,
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  const Image(image: AssetImage('assets/branding/logo-imagiflow.png'), height: 68),
                  const SizedBox(height: 36),
                  Text('Entrar', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  Text(baseUrl.replaceFirst(RegExp(r'^https?://'), ''), style: const TextStyle(color: AppColors.muted)),
                  const SizedBox(height: 26),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    autofillHints: const [AutofillHints.username, AutofillHints.email],
                    decoration: const InputDecoration(labelText: 'E-mail', prefixIcon: Icon(Icons.mail_outline)),
                    validator: (value) => value == null || !value.contains('@') ? 'Informe um e-mail válido.' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _password,
                    obscureText: _hidden,
                    autofillHints: const [AutofillHints.password],
                    decoration: InputDecoration(
                      labelText: 'Senha',
                      prefixIcon: const Icon(Icons.lock_outline),
                      suffixIcon: IconButton(icon: Icon(_hidden ? Icons.visibility_outlined : Icons.visibility_off_outlined), onPressed: () => setState(() => _hidden = !_hidden)),
                    ),
                    validator: (value) => value == null || value.isEmpty ? 'Informe a senha.' : null,
                    onFieldSubmitted: (_) => _login(),
                  ),
                  Align(alignment: Alignment.centerRight, child: TextButton(onPressed: _forgotPassword, child: const Text('Esqueci minha senha'))),
                  const SizedBox(height: 10),
                  ElevatedButton(
                    onPressed: _loading ? null : _login,
                    child: _loading ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Entrar'),
                  ),
                  TextButton.icon(
                    onPressed: () => ref.read(authControllerProvider.notifier).logout(changeTenant: true),
                    icon: const Icon(Icons.domain_outlined),
                    label: const Text('Trocar empresa'),
                  ),
                ]),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _message(String value, {bool success = false}) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(value), backgroundColor: success ? AppColors.success : AppColors.danger));
}

class TwoFactorScreen extends ConsumerStatefulWidget {
  const TwoFactorScreen({super.key});

  @override
  ConsumerState<TwoFactorScreen> createState() => _TwoFactorScreenState();
}

class _TwoFactorScreenState extends ConsumerState<TwoFactorScreen> {
  final _code = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    if (!RegExp(r'^\d{4}$').hasMatch(_code.text)) {
      _message('Informe os quatro dígitos do código.');
      return;
    }
    setState(() => _loading = true);
    try {
      await ref.read(authControllerProvider.notifier).verifyTwoFactor(_code.text);
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    } on ApiFailure catch (failure) {
      if (mounted) _message(failure.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resend() async {
    try {
      await ref.read(authControllerProvider.notifier).resendTwoFactor();
      if (mounted) _message('Código reenviado.', success: true);
    } on ApiFailure catch (failure) {
      if (mounted) _message(failure.message);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(),
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 440),
                child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  const Icon(Icons.mark_email_read_outlined, color: AppColors.primary, size: 58),
                  const SizedBox(height: 20),
                  Text('Verificação em dois fatores', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold), textAlign: TextAlign.center),
                  const SizedBox(height: 8),
                  const Text('Digite o código de quatro dígitos enviado ao seu e-mail.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted)),
                  const SizedBox(height: 28),
                  TextField(
                    controller: _code,
                    autofocus: true,
                    keyboardType: TextInputType.number,
                    maxLength: 4,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w700, letterSpacing: 12),
                    decoration: const InputDecoration(counterText: '', labelText: 'Código'),
                    onSubmitted: (_) => _verify(),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(onPressed: _loading ? null : _verify, child: _loading ? const CircularProgressIndicator(color: Colors.white) : const Text('Verificar e entrar')),
                  TextButton(onPressed: _resend, child: const Text('Reenviar código')),
                ]),
              ),
            ),
          ),
        ),
      );

  void _message(String value, {bool success = false}) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(value), backgroundColor: success ? AppColors.success : AppColors.danger));
}
