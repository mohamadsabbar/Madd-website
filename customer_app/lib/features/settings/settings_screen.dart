import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

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
    final name = auth.user?['name']?.toString() ?? 'مشترك';
    final phone = auth.user?['phone']?.toString() ?? '';

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
      children: [
        const Text('حسابي', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 22)),
        const SizedBox(height: 14),
        MaddCard(
          child: Row(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: MaddTheme.mist,
                child: Text(
                  name.isNotEmpty ? name.substring(0, 1) : 'م',
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 20, color: MaddTheme.primaryDeep),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
                    Text(phone, style: const TextStyle(color: MaddTheme.muted)),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        const Text('تغيير كلمة المرور', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        const SizedBox(height: 10),
        MaddCard(
          child: Column(
            children: [
              TextField(controller: _current, obscureText: true, decoration: const InputDecoration(labelText: 'كلمة المرور الحالية')),
              const SizedBox(height: 10),
              TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'كلمة المرور الجديدة')),
              const SizedBox(height: 10),
              TextField(controller: _confirm, obscureText: true, decoration: const InputDecoration(labelText: 'تأكيد كلمة المرور')),
              const SizedBox(height: 14),
              FilledButton(
                onPressed: _saving ? null : _changePassword,
                child: Text(_saving ? 'جاري الحفظ…' : 'حفظ'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        MaddCard(
          child: Column(
            children: [
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.notifications_active_outlined, color: MaddTheme.primary),
                title: const Text('تفعيل الإشعارات'),
                onTap: () => context.read<AuthState>().refreshPushRegistration(),
              ),
              const Divider(),
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.logout, color: MaddTheme.danger),
                title: const Text('تسجيل الخروج', style: TextStyle(color: MaddTheme.danger, fontWeight: FontWeight.w700)),
                onTap: () => context.read<AuthState>().logout(),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
