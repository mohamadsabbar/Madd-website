import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/customer_repository.dart';
import '../../core/theme.dart';

class TicketDetailScreen extends StatefulWidget {
  const TicketDetailScreen({super.key, required this.ticketId});
  final int ticketId;

  @override
  State<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends State<TicketDetailScreen> {
  Map<String, dynamic>? _ticket;
  bool _loading = true;
  final _reply = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _reply.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await context.read<CustomerRepository>().ticket(widget.ticketId);
      if (!mounted) return;
      setState(() {
        _ticket = (res['ticket'] as Map?)?.cast<String, dynamic>();
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _send() async {
    if (_reply.text.trim().isEmpty) return;
    try {
      final res = await context.read<CustomerRepository>().replyTicket(widget.ticketId, _reply.text.trim());
      _reply.clear();
      if (!mounted) return;
      setState(() {
        _ticket = (res['ticket'] as Map?)?.cast<String, dynamic>() ?? _ticket;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final closed = _ticket?['status']?.toString() == 'closed';

    return Scaffold(
      appBar: AppBar(title: Text(_ticket?['subject']?.toString() ?? 'التذكرة')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      ...(((_ticket?['messages'] as List?) ?? []).map((raw) {
                        final m = (raw as Map).cast<String, dynamic>();
                        final staff = m['sender'] == 'staff';
                        return Align(
                          alignment: staff ? Alignment.centerLeft : Alignment.centerRight,
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(12),
                            constraints: const BoxConstraints(maxWidth: 320),
                            decoration: BoxDecoration(
                              color: staff ? MaddTheme.mist : MaddTheme.primary.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  staff ? 'الدعم' : 'أنت',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: Colors.black.withValues(alpha: 0.5),
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(m['body']?.toString() ?? ''),
                              ],
                            ),
                          ),
                        );
                      })),
                    ],
                  ),
                ),
                if (!closed)
                  SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _reply,
                              decoration: const InputDecoration(hintText: 'اكتب رداً…'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          IconButton.filled(
                            onPressed: _send,
                            icon: const Icon(Icons.send),
                          ),
                        ],
                      ),
                    ),
                  )
                else
                  const SafeArea(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: Text('التذكرة مغلقة', textAlign: TextAlign.center),
                    ),
                  ),
              ],
            ),
    );
  }
}
