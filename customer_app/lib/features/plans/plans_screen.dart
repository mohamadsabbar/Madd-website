import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

class PlansScreen extends StatefulWidget {
  const PlansScreen({super.key});

  @override
  State<PlansScreen> createState() => _PlansScreenState();
}

class _PlansScreenState extends State<PlansScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;
  int? _selectedId;

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
      final current = (data['current'] as Map?)?.cast<String, dynamic>() ?? {};
      final currentId = current['id'] is int ? current['id'] as int : int.tryParse('${current['id']}');
      setState(() {
        _data = data;
        _selectedId = currentId;
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

  Future<void> _submit() async {
    final plans = (_data?['plans'] as List?) ?? [];
    Map<String, dynamic>? selected;
    for (final raw in plans) {
      final p = (raw as Map).cast<String, dynamic>();
      final id = p['id'] is int ? p['id'] as int : int.tryParse('${p['id']}');
      if (id == _selectedId) selected = p;
    }
    if (selected == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('اختر سرعة مختلفة عن الحالية')),
      );
      return;
    }

    try {
      final res = await context.read<CustomerRepository>().renewalRequest(
            note: 'طلب تغيير سرعة إلى ${selected['name']} (${selected['speed_mbps']} Mbps)',
            planId: _selectedId,
          );
      if (!mounted) return;
      showModalBottomSheet(
        context: context,
        builder: (ctx) => Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.hourglass_top, color: MaddTheme.warning, size: 40),
              const SizedBox(height: 12),
              Text(res['message']?.toString() ?? 'تم إرسال الطلب', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
              const SizedBox(height: 8),
              const StatusPill(label: 'بانتظار موافقة الإدارة', tone: StatusTone.warning),
              const SizedBox(height: 16),
              FilledButton(onPressed: () => Navigator.pop(ctx), child: const Text('حسناً')),
            ],
          ),
        ),
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
    final currentId = current['id'] is int ? current['id'] as int : int.tryParse('${current['id']}');

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          const Text('طلب تغيير السرعة', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 22)),
          const SizedBox(height: 6),
          const Text('اختر الباقة المطلوبة — سيتم مراجعة الطلب من الإدارة.', style: TextStyle(color: MaddTheme.muted)),
          const SizedBox(height: 14),
          MaddCard(
            color: MaddTheme.mist,
            child: Text(
              'سرعتك الحالية: ${current['speed_mbps'] != null ? '${current['speed_mbps']} Mbps' : (current['name'] ?? '—')}',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
          const SizedBox(height: 12),
          ...plans.map((raw) {
            final p = (raw as Map).cast<String, dynamic>();
            final id = p['id'] is int ? p['id'] as int : int.tryParse('${p['id']}');
            final isCurrent = id == currentId || p['is_current'] == true;
            final selected = id == _selectedId;
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: InkWell(
                onTap: isCurrent ? null : () => setState(() => _selectedId = id),
                borderRadius: BorderRadius.circular(18),
                child: MaddCard(
                  borderColor: selected ? MaddTheme.primary : null,
                  child: Row(
                    children: [
                      Icon(
                        selected ? Icons.radio_button_checked : Icons.radio_button_off,
                        color: selected ? MaddTheme.primary : MaddTheme.muted,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              p['speed_mbps'] != null ? '${p['speed_mbps']} Mbps' : (p['name']?.toString() ?? 'باقة'),
                              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                            ),
                            Text(
                              '${p['name'] ?? ''} · ${_money(p['price'])}/شهر',
                              style: const TextStyle(color: MaddTheme.muted, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                      if (isCurrent) const StatusPill(label: 'الحالية', tone: StatusTone.info),
                    ],
                  ),
                ),
              ),
            );
          }),
          const SizedBox(height: 8),
          FilledButton(
            onPressed: (_selectedId == null || _selectedId == currentId) ? null : _submit,
            child: const Text('إرسال طلب التغيير'),
          ),
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
