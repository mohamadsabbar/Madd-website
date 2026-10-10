import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';
import 'ticket_detail_screen.dart';

class TicketsScreen extends StatefulWidget {
  const TicketsScreen({super.key});

  @override
  State<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends State<TicketsScreen> {
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
      final res = await context.read<CustomerRepository>().tickets();
      if (!mounted) return;
      setState(() {
        _items = (res['tickets'] as List?) ?? [];
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

  Future<void> _create() async {
    final subject = TextEditingController();
    final body = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تذكرة دعم جديدة'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: subject, decoration: const InputDecoration(labelText: 'الموضوع')),
            const SizedBox(height: 10),
            TextField(controller: body, maxLines: 4, decoration: const InputDecoration(labelText: 'التفاصيل')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('إرسال')),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    if (subject.text.trim().isEmpty || body.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('الموضوع والتفاصيل مطلوبان')),
      );
      return;
    }
    try {
      final res = await context.read<CustomerRepository>().createTicket(
            subject: subject.text.trim(),
            body: body.text.trim(),
          );
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

  String _statusLabel(String? s) => switch (s) {
        'open' => 'مفتوحة',
        'answered' => 'تم الرد',
        'closed' => 'مغلقة',
        _ => s ?? '—',
      };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _create,
        icon: const Icon(Icons.add),
        label: const Text('تذكرة جديدة'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _items.isEmpty
                      ? ListView(
                          children: const [
                            SizedBox(height: 120),
                            Center(child: Text('لا توجد تذاكر بعد')),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 88),
                          itemCount: _items.length,
                          itemBuilder: (_, i) {
                            final t = (_items[i] as Map).cast<String, dynamic>();
                            final id = t['id'] is int ? t['id'] as int : int.parse('${t['id']}');
                            return Card(
                              child: ListTile(
                                leading: const CircleAvatar(
                                  backgroundColor: MaddTheme.mist,
                                  child: Icon(Icons.support_agent, color: MaddTheme.primary),
                                ),
                                title: Text(t['subject']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                                subtitle: Text(_statusLabel(t['status']?.toString())),
                                trailing: const Icon(Icons.chevron_left),
                                onTap: () async {
                                  await Navigator.of(context).push(
                                    MaterialPageRoute(builder: (_) => TicketDetailScreen(ticketId: id)),
                                  );
                                  _load();
                                },
                              ),
                            );
                          },
                        ),
                ),
    );
  }
}
