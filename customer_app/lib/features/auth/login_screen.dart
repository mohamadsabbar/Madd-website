import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/theme.dart';
import 'forgot_password_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _login = TextEditingController();
  final _password = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _obscure = true;
  bool _showForm = false;

  @override
  void dispose() {
    _login.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final auth = context.read<AuthState>();
    try {
      await auth.login(_login.text, _password.text);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  void _contact() {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('طلب اشتراك جديد'),
        content: const Text(
          'افتح اشتراكك عبر التواصل مع فريق مدد أو زيارة madd.ps — لا يوجد تسجيل ذاتي في التطبيق.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('حسناً')),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();

    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  MaddTheme.primaryDark,
                  MaddTheme.primaryDeep,
                  Color(0xFF123F8C),
                  MaddTheme.primary,
                ],
              ),
            ),
          ),
          // Atmosphere / network feel
          Positioned.fill(
            child: CustomPaint(painter: _NetGlowPainter()),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(24, 28, 24, 20),
              child: Column(
                children: [
                  const Spacer(flex: 2),
                  Image.asset(
                    'assets/brand/logo.png',
                    height: 72,
                    fit: BoxFit.contain,
                    errorBuilder: (_, __, ___) => const Text(
                      'مدد',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 48,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    'إنترنت أقرب.. لحياة أفضل',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      height: 1.35,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'مدد للاتصالات — بوابة المشترك',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.8),
                      fontSize: 14,
                    ),
                  ),
                  const Spacer(flex: 3),
                  if (!_showForm) ...[
                    FilledButton(
                      onPressed: () => setState(() => _showForm = true),
                      style: FilledButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: MaddTheme.primaryDeep,
                      ),
                      child: const Text('تسجيل الدخول'),
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton(
                      onPressed: _contact,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.white,
                        side: const BorderSide(color: Colors.white70, width: 1.5),
                      ),
                      child: const Text('طلب اشتراك جديد'),
                    ),
                  ] else
                    _LoginFormCard(
                      formKey: _formKey,
                      login: _login,
                      password: _password,
                      obscure: _obscure,
                      loading: auth.loading,
                      onToggleObscure: () => setState(() => _obscure = !_obscure),
                      onForgot: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const ForgotPasswordScreen()),
                        );
                      },
                      onSubmit: _submit,
                      onBack: () => setState(() => _showForm = false),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LoginFormCard extends StatelessWidget {
  const _LoginFormCard({
    required this.formKey,
    required this.login,
    required this.password,
    required this.obscure,
    required this.loading,
    required this.onToggleObscure,
    required this.onForgot,
    required this.onSubmit,
    required this.onBack,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController login;
  final TextEditingController password;
  final bool obscure;
  final bool loading;
  final VoidCallback onToggleObscure;
  final VoidCallback onForgot;
  final VoidCallback onSubmit;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
      ),
      child: Form(
        key: formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                IconButton(onPressed: onBack, icon: const Icon(Icons.arrow_forward)),
                const Expanded(
                  child: Text(
                    'دخول المشترك',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
            const SizedBox(height: 8),
            TextFormField(
              controller: login,
              textDirection: TextDirection.ltr,
              textAlign: TextAlign.right,
              decoration: const InputDecoration(
                labelText: 'رقم الجوال أو اسم المستخدم',
                prefixIcon: Icon(Icons.person_outline),
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'مطلوب' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: password,
              obscureText: obscure,
              decoration: InputDecoration(
                labelText: 'كلمة المرور',
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  onPressed: onToggleObscure,
                  icon: Icon(obscure ? Icons.visibility : Icons.visibility_off),
                ),
              ),
              validator: (v) => (v == null || v.isEmpty) ? 'مطلوب' : null,
            ),
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton(onPressed: onForgot, child: const Text('نسيت كلمة المرور؟')),
            ),
            FilledButton(
              onPressed: loading ? null : onSubmit,
              child: loading
                  ? const SizedBox(
                      height: 22,
                      width: 22,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('دخول'),
            ),
          ],
        ),
      ),
    );
  }
}

class _NetGlowPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.white.withValues(alpha: 0.06)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.2;

    for (var i = 0; i < 6; i++) {
      final y = size.height * (0.35 + i * 0.08);
      final path = Path()
        ..moveTo(0, y)
        ..quadraticBezierTo(size.width * 0.35, y - 40, size.width * 0.55, y + 10)
        ..quadraticBezierTo(size.width * 0.75, y + 50, size.width, y - 10);
      canvas.drawPath(path, paint);
    }

    final glow = Paint()
      ..shader = RadialGradient(
        colors: [
          MaddTheme.accent.withValues(alpha: 0.25),
          Colors.transparent,
        ],
      ).createShader(Rect.fromCircle(center: Offset(size.width * 0.7, size.height * 0.35), radius: 160));
    canvas.drawCircle(Offset(size.width * 0.7, size.height * 0.35), 160, glow);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
