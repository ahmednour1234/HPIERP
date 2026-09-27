import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_client.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../orders/order_repository.dart';

/// شاشة الفواتير — فواتير البيع بحالة التحصيل (محصّلة/غير محصّلة/جزئي).
/// عند تمرير [customerId] تعرض فواتير هذا العميل فقط.
class InvoicesScreen extends StatefulWidget {
  final int? customerId;
  final String? customerName;
  const InvoicesScreen({super.key, this.customerId, this.customerName});

  @override
  State<InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends State<InvoicesScreen> {
  final _repo = OrderRepository();
  int _tab = 0;
  // null = الكل · unpaid · partial · paid — حسب Order.paymentStatus
  static const _statuses = [null, 'unpaid', 'partial', 'paid'];

  String _search = '';
  Timer? _debounce;

  // كل فواتير المندوب تُحمَّل مرة واحدة، والتبويبات والبحث والإجماليات
  // تُحسب محلياً بنفس معادلة الشارة (من net_remaining) — لأن فلترة
  // payment_status وإجماليات /orders/totals على السيرفر مش متطابقة معاها.
  List<Order> _all = const [];
  bool _loading = true;
  Object? _error;
  int _reqId = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final req = ++_reqId;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      const limit = 200;
      Future<Paginated<Order>> page(int n) => _repo.list(
            type: 4,
            customerId: widget.customerId,
            offset: n,
            limit: limit,
          );
      final first = await page(1);
      final all = <Order>[...first.items];
      // باقي الصفحات بالتوازي على دفعات (4 طلبات في المرة).
      for (var n = 2; n <= first.lastPage; n += 4) {
        final last = (n + 3).clamp(n, first.lastPage);
        final batch = await Future.wait(
            [for (var k = n; k <= last; k++) page(k)]);
        if (req != _reqId) return;
        for (final r in batch) {
          all.addAll(r.items);
        }
      }
      // إزالة أي تكرار لو الترقيم اتزحزح أثناء التحميل.
      final seen = <int>{};
      all.retainWhere((o) => seen.add(o.id));
      if (!mounted || req != _reqId) return;
      setState(() => _all = all);
    } catch (e) {
      if (mounted && req == _reqId) setState(() => _error = e);
    } finally {
      if (mounted && req == _reqId) setState(() => _loading = false);
    }
  }

  /// الفواتير الظاهرة = حالة التبويب + البحث (رقم الفاتورة أو اسم العميل).
  List<Order> get _visible {
    final status = _statuses[_tab];
    final q = _search.trim().replaceAll('#', '').toLowerCase();
    return _all.where((o) {
      if (status != null && o.paymentStatus != status) return false;
      if (q.isEmpty) return true;
      return '${o.id}'.contains(q) || o.customerName.toLowerCase().contains(q);
    }).toList();
  }

  /// إجماليات نفس الفواتير الظاهرة — تطابق الكروت بالظبط.
  OrderTotals _totalsOf(List<Order> list) {
    var total = 0.0, collected = 0.0, remaining = 0.0;
    for (final o in list) {
      total += o.amount;
      collected += o.collected;
      remaining += o.remaining;
    }
    return OrderTotals(list.length, total, collected, remaining);
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 250), () {
      if (mounted) setState(() => _search = v);
    });
  }

  void _setTab(int i) => setState(() => _tab = i);

  Future<void> _refresh() => _load();

  @override
  Widget build(BuildContext context) {
    final forCustomer = widget.customerId != null;
    return Scaffold(
      appBar: AppBar(
          title: Text(forCustomer ? 'فواتير العميل' : 'الطلبات')),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
              child: Column(
                children: [
                  ScreenHeader(
                    icon: Icons.description_outlined,
                    title: forCustomer
                        ? (widget.customerName ?? 'فواتير العميل')
                        : 'الطلبات',
                    subtitle: forCustomer
                        ? 'كل فواتير هذا العميل'
                        : 'حسب حالة التحصيل',
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    onChanged: _onSearch,
                    decoration: const InputDecoration(
                      hintText: 'ابحث برقم الفاتورة أو اسم العميل…',
                      prefixIcon:
                          Icon(Icons.search, color: AppColors.muted),
                    ),
                  ),
                  const SizedBox(height: 12),
                  SegmentedTabs(
                    items: const ['الكل', 'غير محصّلة', 'جزئي', 'محصّلة'],
                    selected: _tab,
                    onChanged: _setTab,
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
    if (_loading && _all.isEmpty) {
      return const Center(
          child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null && _all.isEmpty) {
      final msg = _error is ApiException
          ? (_error as ApiException).message
          : 'تعذّر التحميل';
      return _centered(msg, retry: true);
    }
    final items = _visible;
    final totals = _totalsOf(items);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
        // العناصر: شريط الإجماليات + الفواتير.
        itemCount: 1 + (items.isEmpty ? 1 : items.length),
        itemBuilder: (context, i) {
          if (i == 0) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _totalsBar(totals),
            );
          }
          if (items.isEmpty) {
            return const Padding(
              padding: EdgeInsets.only(top: 60),
              child: Center(
                  child: Text('لا توجد فواتير',
                      style: TextStyle(color: AppColors.muted))),
            );
          }
          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: _invoiceCard(items[i - 1]),
          );
        },
      ),
    );
  }

  Widget _totalsBar(OrderTotals t) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.primaryWash,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          _totalCell('الفواتير', '${t.orders}', AppColors.primaryDeep),
          _sep(),
          _totalCell('المحصّل', _money(t.collected), AppColors.good),
          _sep(),
          _totalCell('المتبقّي', _money(t.remaining),
              t.remaining > 0 ? AppColors.danger : AppColors.good),
        ],
      ),
    );
  }

  Widget _totalCell(String label, String value, Color color) {
    return Expanded(
      child: Column(
        children: [
          Text(label,
              style: const TextStyle(fontSize: 11, color: AppColors.muted)),
          const SizedBox(height: 4),
          MoneyText(value, size: 15, color: color),
        ],
      ),
    );
  }

  Widget _sep() =>
      Container(width: 1, height: 30, color: const Color(0x332563EB));

  Widget _invoiceCard(Order o) {
    final status = o.paymentStatus;
    final badge = switch (status) {
      'paid' => StatusBadge.good('محصّلة'),
      'partial' => StatusBadge.warn('جزئي'),
      _ => StatusBadge.danger('غير محصّلة'),
    };
    return GestureDetector(
      onTap: () async {
        await context.push('/order', extra: o.id);
        _refresh(); // حدّث بعد الرجوع (ربما حصّل)
      },
      child: Container(
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.line),
          boxShadow: AppColors.softShadow,
        ),
        child: Column(
          children: [
            Row(
              children: [
                Container(
                  width: 46,
                  height: 46,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: AppColors.primaryWash,
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: const Icon(Icons.receipt_long_rounded,
                      color: AppColors.primary, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(o.customerName,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                    fontSize: 14.5,
                                    fontWeight: FontWeight.w700)),
                          ),
                          const SizedBox(width: 6),
                          Text('#${o.id}',
                              style: const TextStyle(
                                  fontSize: 11.5,
                                  color: AppColors.muted,
                                  fontWeight: FontWeight.w600)),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Text(_date(o.createdAt),
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.muted)),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    MoneyText(_money(o.amount), size: 16),
                    const SizedBox(height: 5),
                    badge,
                  ],
                ),
              ],
            ),
            // صف المحصّل / المتبقّي — يظهر للغير محصّلة فقط
            if (status != 'paid') ...[
              const SizedBox(height: 12),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.bg,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Row(
                  children: [
                    _miniStat('محصّل', _money(o.collected), AppColors.good),
                    Container(
                        width: 1,
                        height: 26,
                        margin: const EdgeInsets.symmetric(horizontal: 10),
                        color: AppColors.line),
                    _miniStat('متبقّي', _money(o.remaining), AppColors.danger),
                    const Spacer(),
                    ElevatedButton(
                      onPressed: () async {
                        await context.push('/order', extra: o.id);
                        _refresh();
                      },
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size(0, 38),
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        textStyle: const TextStyle(
                            fontSize: 13, fontWeight: FontWeight.w700),
                      ),
                      child: const Text('تحصيل'),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _miniStat(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: const TextStyle(fontSize: 11, color: AppColors.muted)),
        const SizedBox(height: 2),
        MoneyText(value, size: 14, color: color),
      ],
    );
  }

  Widget _centered(String text, {bool retry = false}) {
    return ListView(
      children: [
        const SizedBox(height: 120),
        const Icon(Icons.cloud_off, size: 48, color: AppColors.muted),
        const SizedBox(height: 10),
        Center(
            child: Text(text, style: const TextStyle(color: AppColors.muted))),
        if (retry) ...[
          const SizedBox(height: 14),
          Center(
              child: OutlinedButton(
                  onPressed: _refresh,
                  child: const Text('إعادة المحاولة'))),
        ],
      ],
    );
  }

  String _date(String? iso) => iso == null ? '' : iso.split('T').first;

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
