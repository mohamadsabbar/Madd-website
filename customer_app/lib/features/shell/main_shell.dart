import 'package:flutter/material.dart';

import '../dashboard/home_screen.dart';
import '../invoices/invoices_screen.dart';
import '../plans/plans_screen.dart';
import '../settings/settings_screen.dart';
import '../tickets/tickets_screen.dart';
import '../usage/usage_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;

  static const _titles = [
    'حسابي',
    'الفواتير',
    'الاستهلاك',
    'الباقات',
    'الدعم',
    'الإعدادات',
  ];

  @override
  Widget build(BuildContext context) {
    final pages = const [
      HomeScreen(),
      InvoicesScreen(),
      UsageScreen(),
      PlansScreen(),
      TicketsScreen(),
      SettingsScreen(),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[_index]),
      ),
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index > 4 ? 4 : _index,
        onDestinationSelected: (i) {
          // Map bottom bar: 0 home, 1 invoices, 2 usage, 3 plans, 4 more
          if (i < 4) {
            setState(() => _index = i);
          } else {
            _showMore();
          }
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'حسابي'),
          NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'فواتير'),
          NavigationDestination(icon: Icon(Icons.bar_chart_outlined), selectedIcon: Icon(Icons.bar_chart), label: 'استهلاك'),
          NavigationDestination(icon: Icon(Icons.wifi_outlined), selectedIcon: Icon(Icons.wifi), label: 'باقات'),
          NavigationDestination(icon: Icon(Icons.menu), selectedIcon: Icon(Icons.menu), label: 'المزيد'),
        ],
      ),
    );
  }

  void _showMore() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.support_agent),
              title: const Text('الدعم'),
              onTap: () {
                Navigator.pop(ctx);
                setState(() => _index = 4);
              },
            ),
            ListTile(
              leading: const Icon(Icons.settings_outlined),
              title: const Text('الإعدادات'),
              onTap: () {
                Navigator.pop(ctx);
                setState(() => _index = 5);
              },
            ),
          ],
        ),
      ),
    );
  }
}
