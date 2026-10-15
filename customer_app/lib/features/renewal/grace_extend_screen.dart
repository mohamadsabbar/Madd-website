import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';
import '../../core/widgets.dart';

/// تمديد فترة سماح مجانية — بدون أي تكلفة
class GraceExtendScreen extends StatefulWidget {
  const GraceExtendScreen({
    super.key,
    required this.nextDays,
    required this.usedCount,
    required this.maxCount,
    this.canSelfExtend = true,
    this.schedule = const [3, 2],
  });

  final int nextDays;
  final int usedCount;
  final int maxCount;
  final bool canSelfExtend;
  final List<int> schedule;

  @override
  State<GraceExtendScreen> createState() => _GraceExtendScreenState();
}

class _GraceExtendScreenState extends State<GraceExtendScreen> {
  bool _loading = false;

  Future<void> _confirm() async {
    setState(() => _loading = true);
    try {
      if (widget.canSelfExtend) {
        final res = await context.read<CustomerRepository>().selfExtend();
        if (!mounted) return;
        await Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => GraceExtendResultScreen(
              message: res['message']?.toString() ?? 'تم تفعيل فترة السماح مجاناً.',
              days: res['days'] is int ? res['days'] as int : widget.nextDays,
              newEndDate: res['new_end_date']?.toString(),
              instant: true,
            ),
          ),
        );
      } else {
        final res = await context.read<CustomerRepository>().renewalRequest(
              note: 'طلب فترة سماح مجانية — بدون أي تكلفة',
            );
        if (!mounted) return;
        await Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => GraceExtendResultScreen(
              message: res['message']?.toString() ?? 'تم إرسال طلب فترة السماح.',
              days: widget.nextDays > 0 ? widget.nextDays : 0,
              instant: false,
            ),
          ),
        );
      }
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final remaining = (widget.maxCount - widget.usedCount).clamp(0, widget.maxCount);
    final daysLabel = widget.canSelfExtend ? '${widget.nextDays} أيام سماح' : 'طلب من الشركة';

    return Scaffold(
      appBar: AppBar(title: const Text('فترة السماح')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          MaddCard(
            color: MaddTheme.mist,
            borderColor: MaddTheme.primary.withValues(alpha: 0.2),
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'تمديد مجاني — بدون أي تكلفة',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: MaddTheme.primaryDeep),
                ),
                SizedBox(height: 6),
                Text(
                  'هذه فترة سماح مؤقتة بعد انتهاء الاشتراك. لا يُحسب عليها مبلغ، وليست باقة مدفوعة.',
                  style: TextStyle(color: MaddTheme.muted, height: 1.45),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          const Text('الخيار المتاح', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          const SizedBox(height: 10),
          MaddCard(
            borderColor: MaddTheme.primary,
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: MaddTheme.mist,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(Icons.calendar_month, color: MaddTheme.primary),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(daysLabel, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                      const SizedBox(height: 4),
                      const StatusPill(label: 'مجاناً · 0 ₪', tone: StatusTone.success),
                    ],
                  ),
                ),
                const Icon(Icons.check_circle, color: MaddTheme.primary),
              ],
            ),
          ),
          const SizedBox(height: 14),
          MaddCard(
            child: Column(
              children: [
                _meta('المتبقي من فترات السماح', '$remaining من ${widget.maxCount}'),
                const Divider(height: 20),
                _meta('المستخدم سابقاً', '${widget.usedCount}'),
                if (widget.schedule.isNotEmpty) ...[
                  const Divider(height: 20),
                  _meta('جدول السماح', widget.schedule.map((d) => '$d أيام').join(' ← ')),
                ],
              ],
            ),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _loading ? null : _confirm,
            child: Text(
              _loading
                  ? 'جاري التنفيذ…'
                  : widget.canSelfExtend
                      ? 'تفعيل فترة السماح مجاناً'
                      : 'إرسال طلب فترة السماح',
            ),
          ),
        ],
      ),
    );
  }

  Widget _meta(String k, String v) {
    return Row(
      children: [
        Expanded(child: Text(k, style: const TextStyle(color: MaddTheme.muted))),
        Text(v, style: const TextStyle(fontWeight: FontWeight.w700)),
      ],
    );
  }
}

class GraceExtendResultScreen extends StatelessWidget {
  const GraceExtendResultScreen({
    super.key,
    required this.message,
    required this.days,
    required this.instant,
    this.newEndDate,
  });

  final String message;
  final int days;
  final bool instant;
  final String? newEndDate;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(instant ? 'تم التفعيل' : 'تم إرسال الطلب')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Spacer(),
            Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(
                color: MaddTheme.mist,
                borderRadius: BorderRadius.circular(28),
              ),
              child: Icon(
                instant ? Icons.check_circle_outline : Icons.hourglass_top,
                size: 48,
                color: MaddTheme.primary,
              ),
            ),
            const SizedBox(height: 20),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18, height: 1.4),
            ),
            const SizedBox(height: 12),
            StatusPill(
              label: instant ? 'مفعّل فوراً · مجاناً' : 'بانتظار تأكيد الإدارة · مجاناً',
              tone: instant ? StatusTone.success : StatusTone.warning,
            ),
            const SizedBox(height: 20),
            MaddCard(
              child: Column(
                children: [
                  _row('النوع', 'فترة سماح'),
                  if (days > 0) _row('المدة', '$days أيام'),
                  _row('التكلفة', 'مجاناً — بدون رسوم'),
                  if (newEndDate != null) _row('الانتهاء الجديد', newEndDate!),
                ],
              ),
            ),
            const Spacer(),
            FilledButton(
              onPressed: () => Navigator.of(context).popUntil((r) => r.isFirst),
              child: const Text('العودة للرئيسية'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(String k, String v) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(child: Text(k, style: const TextStyle(color: MaddTheme.muted))),
          Text(v, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}
