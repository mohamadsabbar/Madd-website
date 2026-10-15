import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

class InvoicesScreen extends StatefulWidget {
  const InvoicesScreen({super.key});

  @override
  State<InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends State<InvoicesScreen> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;
  String _filter = 'all';

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

  List<dynamic> get _filtered {
    return _items.where((raw) {
      final inv = (raw as Map).cast<String, dynamic>();
      final s = inv['status']?.toString() ?? '';
      return switch (_filter) {
        'paid' => s == 'paid',
        'unpaid' => s == 'unpaid' || s == 'partial',
        'late' => s == 'unpaid' && _isLate(inv),
        _ => true,
      };
    }).toList();
  }

  bool _isLate(Map<String, dynamic> inv) {
    final due = inv['due_date']?.toString();
    if (due == null) return false;
    final d = DateTime.tryParse(due);
    if (d == null) return false;
    return d.isBefore(DateTime.now()) && inv['status'] != 'paid';
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
          child: Row(
            children: const [
              Text('الفواتير', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 22)),
            ],
          ),
        ),
        const SizedBox(height: 12),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              _chip('all', 'الكل'),
              _chip('paid', 'مدفوعة'),
              _chip('unpaid', 'غير مدفوعة'),
              _chip('late', 'متأخرة'),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? Center(child: Text(_error!))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: _filtered.isEmpty
                          ? ListView(
                              children: const [
                                SizedBox(height: 80),
                                Center(child: Text('لا توجد فواتير في هذا التبويب')),
                              ],
                            )
                          : ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: _filtered.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 10),
                              itemBuilder: (_, i) {
                                final inv = (_filtered[i] as Map).cast<String, dynamic>();
                                final paid = inv['status'] == 'paid';
                                final late = !paid && _isLate(inv);
                                return MaddCard(
                                  child: Row(
                                    children: [
                                      Container(
                                        width: 46,
                                        height: 46,
                                        decoration: BoxDecoration(
                                          color: MaddTheme.mist,
                                          borderRadius: BorderRadius.circular(14),
                                        ),
                                        child: const Icon(Icons.description_outlined, color: MaddTheme.primary),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              _money(inv['amount']),
                                              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                                            ),
                                            Text(
                                              inv['due_date']?.toString() ?? inv['created_at']?.toString() ?? '',
                                              style: const TextStyle(color: MaddTheme.muted, fontSize: 13),
                                            ),
                                          ],
                                        ),
                                      ),
                                      StatusPill(
                                        label: paid
                                            ? 'مدفوعة'
                                            : late
                                                ? 'متأخرة'
                                                : (inv['status_label']?.toString() ?? 'غير مدفوعة'),
                                        tone: paid
                                            ? StatusTone.success
                                            : late
                                                ? StatusTone.danger
                                                : StatusTone.warning,
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                    ),
        ),
      ],
    );
  }

  Widget _chip(String id, String label) {
    final selected = _filter == id;
    return Padding(
      padding: const EdgeInsets.only(left: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => setState(() => _filter = id),
        selectedColor: MaddTheme.primary,
        labelStyle: TextStyle(
          color: selected ? Colors.white : MaddTheme.ink,
          fontWeight: FontWeight.w600,
        ),
        checkmarkColor: Colors.white,
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
