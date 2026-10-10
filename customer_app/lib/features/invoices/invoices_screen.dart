import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';

class InvoicesScreen extends StatefulWidget {
  const InvoicesScreen({super.key});

  @override
  State<InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends State<InvoicesScreen> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await context.read<CustomerRepository>().invoices();
      if (!mounted) return;
      setState(() {
        _items = (res['invoices'] as List?) ?? [];
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!),
            FilledButton(onPressed: _load, child: const Text('إعادة')),
          ],
        ),
      );
    }
    if (_items.isEmpty) {
      return const Center(child: Text('لا توجد فواتير'));
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        itemBuilder: (_, i) {
          final inv = (_items[i] as Map).cast<String, dynamic>();
          final status = inv['status']?.toString() ?? '';
          final paid = status == 'paid';
          return Card(
            child: ListTile(
              title: Text(
                _money(inv['amount']),
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              subtitle: Text([
                if (inv['due_date'] != null) 'الاستحقاق: ${inv['due_date']}',
                if (inv['remaining'] != null) 'المتبقي: ${_money(inv['remaining'])}',
                if (inv['period'] != null) inv['period'].toString(),
              ].where((e) => e.toString().isNotEmpty).join('\n')),
              trailing: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: paid ? Colors.green.shade50 : Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  paid ? 'مدفوعة' : 'غير مدفوعة',
                  style: TextStyle(
                    color: paid ? Colors.green.shade800 : Colors.orange.shade800,
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                ),
              ),
              leading: CircleAvatar(
                backgroundColor: MaddTheme.mist,
                child: Icon(
                  paid ? Icons.check_circle : Icons.receipt_long,
                  color: MaddTheme.primary,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  String _money(Object? v) {
    if (v == null) return '—';
    final n = double.tryParse(v.toString());
    if (n == null) return v.toString();
    return '${n.toStringAsFixed(2)} ₪';
  }
}
