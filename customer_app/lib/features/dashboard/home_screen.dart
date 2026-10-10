import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? _data;
  String? _error;
  bool _loading = true;

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
      final data = await context.read<CustomerRepository>().dashboard();
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

  Future<void> _renewal() async {
    try {
      final res = await context.read<CustomerRepository>().renewalRequest();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'تم')),
      );
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(onPressed: _load, child: const Text('إعادة المحاولة')),
          ],
        ),
      );
    }

    final account = (_data?['account'] as Map?)?.cast<String, dynamic>() ?? {};
    final service = (_data?['service'] as Map?)?.cast<String, dynamic>() ?? {};
    final renewal = (_data?['renewal'] as Map?)?.cast<String, dynamic>() ?? {};
    final online = account['is_online'] == true;
    final status = account['status']?.toString() ?? '—';

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              gradient: const LinearGradient(
                colors: [MaddTheme.primaryDeep, MaddTheme.primary],
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  account['full_name']?.toString() ?? 'مشترك مدد',
                  style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 6),
                Text(
                  account['phone']?.toString() ?? '',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.9)),
                ),
                const SizedBox(height: 14),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _chip(online ? 'متصل' : 'غير متصل', online ? Colors.greenAccent : Colors.orangeAccent),
                    _chip(status, Colors.white70),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _infoCard('الباقة', [
            _row('الاسم', service['plan_name'] ?? service['name'] ?? '—'),
            _row(
              'السرعة',
              service['speed_mbps'] != null
                  ? '${service['speed_mbps']} Mbps'
                  : (service['speed'] ?? service['download_speed'] ?? '—'),
            ),
            _row('السعر', _money(service['price'] ?? service['monthly_price'])),
          ]),
          _infoCard('الاشتراك', [
            _row('البداية', account['start_date'] ?? '—'),
            _row('الانتهاء', account['end_date'] ?? '—'),
            _row('PPPoE', account['pppoe_username'] ?? '—'),
            _row('العنوان', account['address'] ?? '—'),
          ]),
          if (renewal['show_extend_cta'] == true || renewal['show_company_request_cta'] == true) ...[
            const SizedBox(height: 8),
            FilledButton.icon(
              onPressed: _renewal,
              icon: const Icon(Icons.autorenew),
              label: Text(
                renewal['show_extend_cta'] == true ? 'تمديد الاشتراك الآن' : 'طلب تجديد من الشركة',
              ),
            ),
          ],
          if (renewal['pending_request'] == true)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Card(
                color: MaddTheme.mist,
                child: const ListTile(
                  leading: Icon(Icons.hourglass_top, color: MaddTheme.primary),
                  title: Text('طلب التجديد قيد المعالجة'),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _chip(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.6)),
      ),
      child: Text(text, style: TextStyle(color: color, fontWeight: FontWeight.w600)),
    );
  }

  Widget _infoCard(String title, List<Widget> rows) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            const SizedBox(height: 8),
            ...rows,
          ],
        ),
      ),
    );
  }

  Widget _row(String k, Object? v) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(child: Text(k, style: TextStyle(color: Colors.black.withValues(alpha: 0.55)))),
          Text(v?.toString() ?? '—', style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
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
