import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';

class PlansScreen extends StatefulWidget {
  const PlansScreen({super.key});

  @override
  State<PlansScreen> createState() => _PlansScreenState();
}

class _PlansScreenState extends State<PlansScreen> {
  Map<String, dynamic>? _data;
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
      final data = await context.read<CustomerRepository>().plans();
      if (!mounted) return;
      setState(() {
        _data = data;
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

  Future<void> _request(int? planId, String? name) async {
    final noteCtrl = TextEditingController(text: name != null ? 'طلب باقة: $name' : null);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('طلب تجديد / تغيير باقة'),
        content: TextField(
          controller: noteCtrl,
          maxLines: 3,
          decoration: const InputDecoration(labelText: 'ملاحظة (اختياري)'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('إرسال')),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      final res = await context.read<CustomerRepository>().renewalRequest(
            note: noteCtrl.text.trim(),
            planId: planId,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'تم الإرسال')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text(_error!));

    final current = (_data?['current'] as Map?)?.cast<String, dynamic>() ?? {};
    final plans = (_data?['plans'] as List?) ?? [];

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            color: MaddTheme.mist,
            child: ListTile(
              title: const Text('باقتك الحالية', style: TextStyle(fontWeight: FontWeight.w700)),
              subtitle: Text(
                '${current['plan_name'] ?? current['name'] ?? '—'}'
                ' · ${_money(current['price'] ?? current['monthly_price'])}',
              ),
              trailing: FilledButton(
                onPressed: () => _request(null, null),
                child: const Text('تجديد'),
              ),
            ),
          ),
          const SizedBox(height: 12),
          const Text('باقات أخرى', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          const SizedBox(height: 8),
          ...plans.map((raw) {
            final p = (raw as Map).cast<String, dynamic>();
            final id = p['id'] is int ? p['id'] as int : int.tryParse('${p['id']}');
            return Card(
              child: ListTile(
                title: Text(p['name']?.toString() ?? 'باقة', style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(
                  [
                    if (p['speed_mbps'] != null) '${p['speed_mbps']} Mbps',
                    if (p['speed'] != null) p['speed'].toString(),
                    _money(p['price'] ?? p['monthly_price']),
                  ].where((e) => e.toString().isNotEmpty).join(' · '),
                ),
                trailing: OutlinedButton(
                  onPressed: () => _request(id, p['name']?.toString()),
                  child: const Text('طلب'),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  String _money(Object? v) {
    if (v == null) return '—';
    final n = double.tryParse(v.toString());
    if (n == null) return v.toString();
    return '${n.toStringAsFixed(0)} ₪';
  }
}
