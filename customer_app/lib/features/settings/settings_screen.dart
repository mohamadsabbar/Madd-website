import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/customer_repository.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _saving = false;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _changePassword() async {
    if (_password.text != _confirm.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تأكيد كلمة المرور غير متطابق')),
      );
      return;
    }
    setState(() => _saving = true);
    try {
      final res = await context.read<CustomerRepository>().changePassword(
            current: _current.text,
            password: _password.text,
            confirmation: _confirm.text,
          );
      _current.clear();
      _password.clear();
      _confirm.clear();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'تم')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final name = auth.user?['name']?.toString() ?? '';
    final phone = auth.user?['phone']?.toString() ?? '';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: ListTile(
            leading: const CircleAvatar(child: Icon(Icons.person)),
            title: Text(name.isEmpty ? 'مشترك' : name),
            subtitle: Text(phone),
          ),
        ),
        const SizedBox(height: 16),
        const Text('تغيير كلمة المرور', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        const SizedBox(height: 8),
        TextField(
          controller: _current,
          obscureText: true,
          decoration: const InputDecoration(labelText: 'كلمة المرور الحالية'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _password,
          obscureText: true,
          decoration: const InputDecoration(labelText: 'كلمة المرور الجديدة'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _confirm,
          obscureText: true,
          decoration: const InputDecoration(labelText: 'تأكيد كلمة المرور'),
        ),
        const SizedBox(height: 12),
        FilledButton(
          onPressed: _saving ? null : _changePassword,
          child: Text(_saving ? 'جاري الحفظ…' : 'حفظ كلمة المرور'),
        ),
        const SizedBox(height: 24),
        OutlinedButton.icon(
          onPressed: () => context.read<AuthState>().refreshPushRegistration(),
          icon: const Icon(Icons.notifications_active_outlined),
          label: const Text('تفعيل الإشعارات'),
        ),
        const SizedBox(height: 12),
        TextButton.icon(
          onPressed: () => context.read<AuthState>().logout(),
          icon: const Icon(Icons.logout, color: Colors.red),
          label: const Text('تسجيل الخروج', style: TextStyle(color: Colors.red)),
        ),
      ],
    );
  }
}
