import 'package:flutter/material.dart';

import '../../core/theme.dart';
import '../dashboard/home_screen.dart';
import '../invoices/invoices_screen.dart';
import '../plans/plans_screen.dart';
import '../settings/settings_screen.dart';
import '../tickets/tickets_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeScreen(onOpenTab: (i) => setState(() => _index = i)),
      const InvoicesScreen(),
      const PlansScreen(),
      const TicketsScreen(),
      const SettingsScreen(),
    ];

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: IndexedStack(index: _index, children: pages),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home, color: MaddTheme.primary),
            label: 'الرئيسية',
          ),
          NavigationDestination(
            icon: Icon(Icons.receipt_long_outlined),
            selectedIcon: Icon(Icons.receipt_long, color: MaddTheme.primary),
            label: 'الفواتير',
          ),
          NavigationDestination(
            icon: Icon(Icons.wifi_tethering_outlined),
            selectedIcon: Icon(Icons.wifi_tethering, color: MaddTheme.primary),
            label: 'الطلبات',
          ),
          NavigationDestination(
            icon: Icon(Icons.support_agent_outlined),
            selectedIcon: Icon(Icons.support_agent, color: MaddTheme.primary),
            label: 'الدعم',
          ),
          NavigationDestination(
            icon: Icon(Icons.more_horiz),
            selectedIcon: Icon(Icons.more_horiz, color: MaddTheme.primary),
            label: 'المزيد',
          ),
        ],
      ),
    );
  }
}
