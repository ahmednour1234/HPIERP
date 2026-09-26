import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'manager_repository.dart';

/// شاشة المدير — طلبات وإجازات المناديب مع القبول/الرفض.
class TeamRequestsScreen extends StatefulWidget {
  const TeamRequestsScreen({super.key});

  @override
  State<TeamRequestsScreen> createState() => _TeamRequestsScreenState();
}

class _TeamRequestsScreenState extends State<TeamRequestsScreen> {
  final _repo = ManagerRepository();
  final _scroll = ScrollController();

  int _typeTab = 0; // 0 طلبات · 1 إجازات
  int _statusTab = 0;
  // قيد المراجعة · مقبول · مرفوض · الكل
  static const _statuses = [0, 1, 2, null];

  final List<TeamHrRequest> _items = [];
  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  Object? _error;
  int _reqId = 0;
  final Set<int> _deciding = {};

  @override
  void initState() {
    super.initState();
    _fetch(reset: true);
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 300 &&
          !_loading &&
          !_loadingMore &&
          _hasMore) {
        _fetch();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _fetch({bool reset = false}) async {
    if (!reset && _loadingMore) return;
    final req = reset ? ++_reqId : _reqId;
    if (reset) {
      setState(() {
        _loading = true;
        _error = null;
        _page = 1;
        _hasMore = true;
        _items.clear();
      });
    } else {
      setState(() => _loadingMore = true);
    }
    try {
      final page = await _repo.teamRequests(
        type: _typeTab == 0 ? 1 : 2,
        status: _statuses[_statusTab],
        offset: _page,
      );
      if (!mounted || req != _reqId) return;
      setState(() {
        _items.addAll(page.items);
        _hasMore = page.hasMore;
        _page++;
      });
    } catch (e) {
      if (mounted && req == _reqId) setState(() => _error = e);
    } finally {
      if (mounted && req == _reqId) {
        setState(() {
          _loading = false;
          _loadingMore = false;
        });
      }
    }
  }

  Future<void> _decide(TeamHrRequest r, bool approve) async {
    String? reply;
    if (!approve) {
      // الرفض يطلب سبباً اختيارياً يظهر للمندوب.
      final ctrl = TextEditingController();
      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('رفض الطلب'),
          content: TextField(
            controller: ctrl,
            minLines: 2,
            maxLines: 4,
            decoration: const InputDecoration(hintText: 'سبب الرفض (اختياري)'),
          ),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(ctx, false),
                child: const Text('إلغاء')),
            TextButton(
                onPressed: () => Navigator.pop(ctx, true),
                child: const Text('رفض',
                    style: TextStyle(color: AppColors.danger))),
          ],
        ),
      );
      if (ok != true) return;
      reply = ctrl.text;
    }
    setState(() => _deciding.add(r.id));
    try {
      await _repo.decideRequest(r.id, approve: approve, reply: reply);
      _snack(approve ? 'تم قبول الطلب' : 'تم رفض الطلب',
          approve ? AppColors.good : AppColors.primaryDeep);
      _fetch(reset: true);
    } on ApiException catch (e) {
      _snack(e.message, AppColors.danger);
    } finally {
      if (mounted) setState(() => _deciding.remove(r.id));
    }
  }

  void _snack(String m, Color c) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(m), backgroundColor: c));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('طلبات المناديب')),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
              child: Column(
                children: [
                  const ScreenHeader(
                    icon: Icons.assignment_ind_outlined,
                    title: 'طلبات المناديب',
                    subtitle: 'الطلبات والإجازات بانتظار قرارك',
                  ),
                  const SizedBox(height: 14),
                  SegmentedTabs(
                    items: const ['طلبات', 'إجازات'],
                    selected: _typeTab,
                    onChanged: (i) {
                      setState(() => _typeTab = i);
                      _fetch(reset: true);
                    },
                  ),
                  const SizedBox(height: 10),
                  SegmentedTabs(
                    items: const ['قيد المراجعة', 'مقبول', 'مرفوض', 'الكل'],
                    selected: _statusTab,
                    onChanged: (i) {
                      setState(() => _statusTab = i);
                      _fetch(reset: true);
                    },
                  ),
                ],
              ),
            ),
            Expanded(child: _body()),
          ],
        ),
      ),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(
          child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null && _items.isEmpty) {
      final e = _error;
      final msg = e is ApiException
          ? (e.statusCode == 404
              ? 'الخدمة غير متاحة على السيرفر بعد'
              : e.message)
          : 'تعذّر التحميل';
      return _centered(Icons.cloud_off, msg, retry: true);
    }
    return RefreshIndicator(
      onRefresh: () => _fetch(reset: true),
      color: AppColors.primary,
      child: _items.isEmpty
          ? _centered(Icons.inbox_outlined,
              _typeTab == 0 ? 'لا توجد طلبات' : 'لا توجد طلبات إجازة')
          : ListView.separated(
              controller: _scroll,
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
              itemCount: _items.length + (_hasMore ? 1 : 0),
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                if (i >= _items.length) {
                  return const Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(
                        child: CircularProgressIndicator(
                            color: AppColors.primary)),
                  );
                }
                return _card(_items[i]);
              },
            ),
    );
  }

  Widget _card(TeamHrRequest r) {
    final badge = switch (r.status) {
      1 => StatusBadge.good('مقبول'),
      2 => StatusBadge.danger('مرفوض'),
      _ => StatusBadge.warn('قيد المراجعة'),
    };
    final busy = _deciding.contains(r.id);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.line),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.person_outline,
                  size: 18, color: AppColors.primary),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  r.sellerCode.isEmpty
                      ? r.sellerName
                      : '${r.sellerName} · ${r.sellerCode}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                      fontSize: 14, fontWeight: FontWeight.w700),
                ),
              ),
              const SizedBox(width: 8),
              badge,
            ],
          ),
          const SizedBox(height: 8),
          Text(r.note.isEmpty ? '—' : r.note,
              style: const TextStyle(fontSize: 13.5, height: 1.5)),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.schedule, size: 13, color: AppColors.muted),
              const SizedBox(width: 4),
              Text(r.dateShort,
                  style:
                      const TextStyle(fontSize: 11.5, color: AppColors.muted)),
              if (r.date != null && r.date!.isNotEmpty) ...[
                const SizedBox(width: 12),
                const Icon(Icons.event_outlined,
                    size: 13, color: AppColors.muted),
                const SizedBox(width: 4),
                Text(r.date!.split('T').first,
                    style: const TextStyle(
                        fontSize: 11.5, color: AppColors.muted)),
              ],
            ],
          ),
          if (r.reply != null && r.reply!.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text('ردّك: ${r.reply}',
                style: const TextStyle(
                    fontSize: 12.5, color: AppColors.inkSoft)),
          ],
          if (r.isPending) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: busy ? null : () => _decide(r, false),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.danger,
                      side: const BorderSide(color: AppColors.danger),
                      minimumSize: const Size(0, 40),
                    ),
                    child: const Text('رفض'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton(
                    onPressed: busy ? null : () => _decide(r, true),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size(0, 40),
                    ),
                    child: busy
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white))
                        : const Text('قبول'),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _centered(IconData icon, String text, {bool retry = false}) {
    return ListView(
      children: [
        const SizedBox(height: 110),
        Icon(icon, size: 48, color: AppColors.muted),
        const SizedBox(height: 10),
        Center(
            child: Text(text, style: const TextStyle(color: AppColors.muted))),
        if (retry) ...[
          const SizedBox(height: 14),
          Center(
              child: OutlinedButton(
                  onPressed: () => _fetch(reset: true),
                  child: const Text('إعادة المحاولة'))),
        ],
      ],
    );
  }
}
