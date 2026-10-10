import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';

class UsageScreen extends StatefulWidget {
  const UsageScreen({super.key});

  @override
  State<UsageScreen> createState() => _UsageScreenState();
}

class _UsageScreenState extends State<UsageScreen> {
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
      final data = await context.read<CustomerRepository>().usage();
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

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(child: Text(_error!));
    }

    final months = (_data?['months'] as List?) ?? (_data?['usage'] as List?) ?? [];
    if (months.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            _data?['note']?.toString() ??
                _data?['message']?.toString() ??
                'لا تتوفر بيانات استهلاك حالياً',
            textAlign: TextAlign.center,
          ),
        ),
      );
    }

    final bars = <BarChartGroupData>[];
    final labels = <String>[];
    for (var i = 0; i < months.length; i++) {
      final m = (months[i] as Map).cast<String, dynamic>();
      final down = _gb(m['download_bytes'] ?? m['download'] ?? m['download_gb'] ?? m['rx']);
      final up = _gb(m['upload_bytes'] ?? m['upload'] ?? m['upload_gb'] ?? m['tx']);
      labels.add(m['label']?.toString() ?? m['month']?.toString() ?? '${i + 1}');
      bars.add(
        BarChartGroupData(
          x: i,
          barRods: [
            BarChartRodData(toY: down, color: MaddTheme.primary, width: 10),
            BarChartRodData(toY: up, color: MaddTheme.accent, width: 10),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('الاستهلاك الشهري (GB)', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
          const SizedBox(height: 8),
          Row(
            children: [
              _legend(MaddTheme.primary, 'تحميل'),
              const SizedBox(width: 16),
              _legend(MaddTheme.accent, 'رفع'),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 240,
            child: BarChart(
              BarChartData(
                barGroups: bars,
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                titlesData: FlTitlesData(
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 36,
                      getTitlesWidget: (v, _) => Text(v.toInt().toString(), style: const TextStyle(fontSize: 10)),
                    ),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (v, _) {
                        final i = v.toInt();
                        if (i < 0 || i >= labels.length) return const SizedBox.shrink();
                        return Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(labels[i], style: const TextStyle(fontSize: 10)),
                        );
                      },
                    ),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          ...List.generate(months.length, (i) {
            final m = (months[i] as Map).cast<String, dynamic>();
            return Card(
              child: ListTile(
                title: Text(labels[i]),
                subtitle: Text(
                  'تحميل: ${_gb(m['download_bytes'] ?? m['download']).toStringAsFixed(2)} GB'
                  ' · رفع: ${_gb(m['upload_bytes'] ?? m['upload']).toStringAsFixed(2)} GB',
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _legend(Color c, String t) {
    return Row(
      children: [
        Container(width: 12, height: 12, decoration: BoxDecoration(color: c, borderRadius: BorderRadius.circular(3))),
        const SizedBox(width: 6),
        Text(t),
      ],
    );
  }

  double _gb(Object? v) {
    if (v == null) return 0;
    final n = double.tryParse(v.toString()) ?? 0;
    // If looks like bytes, convert
    if (n > 10000) return n / (1024 * 1024 * 1024);
    return n;
  }
}
