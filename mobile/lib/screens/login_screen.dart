import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../brand.dart';
import '../state/app_state.dart';
import '../widgets/common.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController(text: 'rudi@oasis.test');
  final _password = TextEditingController();
  bool _busy = false;

  Future<void> _submit() async {
    setState(() => _busy = true);
    try {
      await context.read<AppState>().login(_email.text.trim(), _password.text);
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 380),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                const Center(child: OasisLogo(height: 110, padding: EdgeInsets.all(12))),
                const SizedBox(height: 16),
                Text('Oasis Sales', textAlign: TextAlign.center, style: t.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800, color: t.colorScheme.primary)),
                Text('Motoris & sales outlet', textAlign: TextAlign.center, style: t.textTheme.bodyMedium?.copyWith(color: OasisColors.teal, fontWeight: FontWeight.w600)),
                const SizedBox(height: 28),
                TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email')),
                const SizedBox(height: 12),
                TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Kata sandi'), onSubmitted: (_) => _submit()),
                const SizedBox(height: 20),
                FilledButton(onPressed: _busy ? null : _submit, child: Padding(padding: const EdgeInsets.all(12), child: Text(_busy ? 'Masuk…' : 'Masuk'))),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
