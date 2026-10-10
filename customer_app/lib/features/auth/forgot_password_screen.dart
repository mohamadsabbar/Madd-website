import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/customer_repository.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _login = TextEditingController();
  final _code = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _codeSent = false;
  bool _loading = false;

  @override
  void dispose() {
    _login.dispose();
    _code.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _requestCode() async {
    setState(() => _loading = true);
    try {
      final repo = context.read<CustomerRepository>();
      final res = await repo.forgotPassword(_login.text.trim());
      setState(() => _codeSent = true);
      if (!mounted) return;
      final msg = res['message']?.toString() ?? 'تم الإرسال';
      final dev = res['dev_code']?.toString();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(dev != null ? '$msg (تطوير: $dev)' : msg)),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _reset() async {
    if (_password.text != _confirm.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تأكيد كلمة المرور غير متطابق')),
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final repo = context.read<CustomerRepository>();
      final auth = context.read<AuthState>();
      final res = await repo.resetPassword(
        login: _login.text.trim(),
        code: _code.text.trim(),
        password: _password.text,
        confirmation: _confirm.text,
      );
      final token = res['token'] as String?;
      if (token != null) {
        await auth.acceptSession(
          token,
          (res['user'] as Map?)?.cast<String, dynamic>(),
        );
      }
      if (!mounted) return;
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'تم التعيين')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('استعادة كلمة المرور')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          TextField(
            controller: _login,
            decoration: const InputDecoration(labelText: 'رقم الجوال'),
            enabled: !_codeSent,
          ),
          if (_codeSent) ...[
            const SizedBox(height: 12),
            TextField(
              controller: _code,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'رمز التحقق'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _password,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'كلمة المرور الجديدة'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _confirm,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'تأكيد كلمة المرور'),
            ),
          ],
          const SizedBox(height: 20),
          FilledButton(
            onPressed: _loading ? null : (_codeSent ? _reset : _requestCode),
            child: Text(_codeSent ? 'تعيين كلمة المرور' : 'إرسال رمز التحقق'),
          ),
        ],
      ),
    );
  }
}
