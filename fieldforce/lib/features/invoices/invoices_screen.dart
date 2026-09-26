import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
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
  // null = الكل · unpaid · partial · paid — تُرسل كـ payment_status
  static const _statuses = [null, 'unpaid', 'partial', 'paid'];

  String _search = '';
  Timer? _debounce;
  final _scroll = ScrollController();

  final List<Order> _items = [];
  OrderTotals? _totals;
  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  Object? _error;
  // يتجاهل ردود الطلبات القديمة عند تغيير التبويب/البحث بسرعة.
  int _reqId = 0;

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
    _debounce?.cancel();
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
    final status = _statuses[_tab];
    final q = _search.trim().isEmpty ? null : _search.trim();
    try {
      // فلترة السيرفر (payment_status) مش متطابقة دايماً مع المتبقّي الفعلي،
      // فبنستبعد محلياً أي فاتورة حالتها مختلفة عن التبويب. لو الصفحة كلها
      // اتستبعدت نجيب اللي بعدها (بحد أقصى 5 صفحات في المرة).
      final kept = <Order>[];
      var page = _page;
      var hasMore = true;
      for (var i = 0; i < 5 && hasMore && kept.length < 10; i++) {
        final res = await _repo.list(
          type: 4,
          customerId: widget.customerId,
          paymentStatus: status,
          search: q,
          offset: page,
          limit: 25,
        );
        if (req != _reqId) return;
        kept.addAll(status == null
            ? res.items
            : res.items.where((o) => o.paymentStatus == status));
        hasMore = res.hasMore;
        page++;
      }
      // الإجماليات تُجلب مرة واحدة عند إعادة التحميل (تخص كل النتائج).
      OrderTotals? totals = _totals;
      if (reset) {
        totals = await _repo.totals(
            type: 4,
            customerId: widget.customerId,
            paymentStatus: status);
      }
      if (!mounted || req != _reqId) return;
      setState(() {
        _items.addAll(kept);
        _hasMore = hasMore;
        _page = page;
        if (totals != null) _totals = totals;
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

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () {
      _search = v;
      _fetch(reset: true);
    });
  }

  void _setTab(int i) {
    setState(() => _tab = i);
    _fetch(reset: true);
  }

  Future<void> _refresh() => _fetch(reset: true);

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
    if (_loading) {
      return const Center(
          child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null && _items.isEmpty) {
      final msg = _error is ApiException
          ? (_error as ApiException).message
          : 'تعذّر التحميل';
      return _centered(msg, retry: true);
    }
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: ListView.builder(
        controller: _scroll,
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
        // العناصر: شريط الإجماليات + الفواتير + مؤشر تحميل المزيد.
        itemCount: 1 + (_items.isEmpty ? 1 : _items.length) + (_hasMore ? 1 : 0),
        itemBuilder: (context, i) {
          if (i == 0) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _totals != null
                  ? _totalsBar(_totals!)
                  : const SizedBox.shrink(),
            );
          }
          final idx = i - 1;
          if (_items.isEmpty) {
            return const Padding(
              padding: EdgeInsets.only(top: 60),
              child: Center(
                  child: Text('لا توجد فواتير',
                      style: TextStyle(color: AppColors.muted))),
            );
          }
          if (idx >= _items.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(
                  child: CircularProgressIndicator(color: AppColors.primary)),
            );
          }
          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: _invoiceCard(_items[idx]),
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
