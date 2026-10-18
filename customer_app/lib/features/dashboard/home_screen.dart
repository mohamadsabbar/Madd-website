import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';
import '../invoices/invoices_screen.dart';
import '../plans/plans_screen.dart';
import '../renewal/grace_extend_screen.dart';
import '../settings/settings_screen.dart';
import '../tickets/tickets_screen.dart';
import '../usage/usage_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, this.onOpenTab});

  final ValueChanged<int>? onOpenTab;

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

  void _openGrace() {
    final renewal = (_data?['renewal'] as Map?)?.cast<String, dynamic>() ?? {};
    final self = (renewal['self_extend'] as Map?)?.cast<String, dynamic>() ?? {};
    final next = self['next_days'] is int
        ? self['next_days'] as int
        : int.tryParse('${self['next_days']}') ?? 3;
    final used = self['used_count'] is int ? self['used_count'] as int : 0;
    final max = self['max_count'] is int ? self['max_count'] as int : 2;
    final schedule = ((self['schedule'] as List?) ?? const [3, 2])
        .map((e) => int.tryParse('$e') ?? 0)
        .where((e) => e > 0)
        .toList();

    final canSelf = renewal['show_extend_cta'] == true;
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => GraceExtendScreen(
          nextDays: next,
          usedCount: used,
          maxCount: max,
          canSelfExtend: canSelf,
          schedule: schedule.isEmpty ? const [3, 2] : schedule,
        ),
      ),
    ).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              FilledButton(onPressed: _load, child: const Text('إعادة المحاولة')),
            ],
          ),
        ),
      );
    }

    final auth = context.watch<AuthState>();
    final account = (_data?['account'] as Map?)?.cast<String, dynamic>() ?? {};
    final service = (_data?['service'] as Map?)?.cast<String, dynamic>() ?? {};
    final renewal = (_data?['renewal'] as Map?)?.cast<String, dynamic>() ?? {};
    final name = account['full_name']?.toString() ?? auth.user?['name']?.toString() ?? 'مشترك';
    final first = name.split(RegExp(r'\s+')).first;
    final subNo = account['subscription_number']?.toString();
    final expired = account['is_expired'] == true || renewal['needs_attention'] == true;
    final online = account['is_online'] == true;
    final statusLabel = account['status_label']?.toString() ?? account['status']?.toString() ?? '—';
    final daysLeft = account['days_remaining'];
    final canExtend = renewal['show_extend_cta'] == true;
    final companyRequest = renewal['show_company_request_cta'] == true;
    final pending = renewal['pending_request'] == true;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: MaddTheme.mist,
                child: Text(
                  first.isNotEmpty ? first.substring(0, 1) : 'م',
                  style: const TextStyle(color: MaddTheme.primaryDeep, fontWeight: FontWeight.w800),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('مرحباً، $first', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
                    Text(
                      [
                        if (subNo != null && subNo.isNotEmpty) subNo,
                        online ? 'متصل الآن' : 'غير متصل',
                      ].join(' · '),
                      style: TextStyle(color: online ? MaddTheme.success : MaddTheme.muted, fontSize: 13),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const SettingsScreen()),
                ),
                icon: const Icon(Icons.notifications_none_rounded),
              ),
            ],
          ),
          const SizedBox(height: 16),
          if (expired || canExtend || companyRequest || pending)
            MaddCard(
              borderColor: expired ? MaddTheme.danger.withValues(alpha: 0.35) : null,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        pending ? Icons.hourglass_top : Icons.error_outline,
                        color: pending ? MaddTheme.warning : MaddTheme.danger,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          pending
                              ? 'طلب قيد المعالجة'
                              : 'الاشتراك ${account['end_date'] != null ? 'انتهى بتاريخ ${account['end_date']}' : 'يحتاج انتباهاً'}',
                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    canExtend
                        ? 'يمكنك تفعيل فترة سماح مجانية الآن — بدون أي تكلفة.'
                        : companyRequest
                            ? 'انتهت فترات السماح الذاتية. يمكنك إرسال طلب للشركة.'
                            : pending
                                ? 'سنراجع طلبك ونتواصل معك قريباً.'
                                : statusLabel,
                    style: const TextStyle(color: MaddTheme.muted, height: 1.4),
                  ),
                  if (canExtend || companyRequest) ...[
                    const SizedBox(height: 14),
                    FilledButton(
                      onPressed: _openGrace,
                      child: Text(canExtend ? 'تفعيل فترة السماح مجاناً' : 'طلب تمديد من الشركة'),
                    ),
                  ],
                ],
              ),
            )
          else
            MaddCard(
              color: MaddTheme.mist,
              child: Row(
                children: [
                  const Icon(Icons.verified, color: MaddTheme.primary),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(statusLabel, style: const TextStyle(fontWeight: FontWeight.w800)),
                        Text(
                          daysLeft != null ? 'متبقي $daysLeft يوم · ينتهي ${account['end_date'] ?? ''}' : 'اشتراكك نشط',
                          style: const TextStyle(color: MaddTheme.muted, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                  StatusPill(label: online ? 'أونلاين' : 'أوفلاين', tone: online ? StatusTone.success : StatusTone.info),
                ],
              ),
            ),
          const SizedBox(height: 14),
          MaddCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('باقتك الحالية', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _stat(
                        'السرعة',
                        service['speed_mbps'] != null ? '${service['speed_mbps']} Mbps' : '—',
                      ),
                    ),
                    Expanded(
                      child: _stat('السعر الشهري', _money(service['price'])),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  service['name']?.toString() ?? '—',
                  style: const TextStyle(color: MaddTheme.muted),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          const Text('اختصارات', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          const SizedBox(height: 10),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            childAspectRatio: 1.25,
            children: [
              QuickActionTile(
                icon: Icons.speed,
                label: 'طلب تغيير السرعة',
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const PlansScreen()),
                ),
              ),
              QuickActionTile(
                icon: Icons.receipt_long,
                label: 'سجل الفواتير',
                onTap: () {
                  if (widget.onOpenTab != null) {
                    widget.onOpenTab!(1);
                  } else {
                    Navigator.of(context).push(MaterialPageRoute(builder: (_) => const InvoicesScreen()));
                  }
                },
              ),
              QuickActionTile(
                icon: Icons.support_agent,
                label: 'الدعم الفني',
                onTap: () {
                  if (widget.onOpenTab != null) {
                    widget.onOpenTab!(3);
                  } else {
                    Navigator.of(context).push(MaterialPageRoute(builder: (_) => const TicketsScreen()));
                  }
                },
              ),
              QuickActionTile(
                icon: Icons.pie_chart_outline,
                label: 'تفاصيل الاشتراك',
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const UsageScreen()),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stat(String k, String v) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(k, style: const TextStyle(color: MaddTheme.muted, fontSize: 12)),
        const SizedBox(height: 2),
        Text(v, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18, color: MaddTheme.primaryDeep)),
      ],
    );
  }

  String _money(Object? v) {
    if (v == null) return '—';
    final n = double.tryParse(v.toString());
    if (n == null) return v.toString();
    return '${n.toStringAsFixed(0)} ₪';
  }
}
