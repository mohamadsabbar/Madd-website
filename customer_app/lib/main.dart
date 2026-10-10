import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';

import 'core/api_client.dart';
import 'core/auth_state.dart';
import 'core/customer_repository.dart';
import 'core/push_service.dart';
import 'core/theme.dart';
import 'features/auth/login_screen.dart';
import 'features/shell/main_shell.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final push = PushService();
  await push.init();

  final api = ApiClient();
  final auth = AuthState(api, push: push);
  final repo = CustomerRepository(api);
  await auth.bootstrap();

  runApp(
    MultiProvider(
      providers: [
        Provider.value(value: api),
        Provider.value(value: repo),
        Provider.value(value: push),
        ChangeNotifierProvider.value(value: auth),
      ],
      child: const MaddCustomerApp(),
    ),
  );
}

class MaddCustomerApp extends StatelessWidget {
  const MaddCustomerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'مدد — بوابة المشترك',
      debugShowCheckedModeBanner: false,
      theme: MaddTheme.light(),
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      builder: (context, child) {
        return Directionality(
          textDirection: TextDirection.rtl,
          child: child ?? const SizedBox.shrink(),
        );
      },
      home: Consumer<AuthState>(
        builder: (context, auth, _) {
          if (auth.bootstrapping) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }
          return auth.isLoggedIn ? const MainShell() : const LoginScreen();
        },
      ),
    );
  }
}
