import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'reservation_repository.dart';

/// أوامر الصرف الصادرة للمندوب — عرض فقط (GET /reservations/issued).
class IssuedOrdersScreen extends StatefulWidget {
  const IssuedOrdersScreen({super.key});

  @override
  State<IssuedOrdersScreen> createState() => _IssuedOrdersScreenState();
}

class _IssuedOrdersScreenState extends State<IssuedOrdersScreen> {
  final _repo = ReservationRepository();

  List<IssuedOrder> _items = [];
  DateTime? _from;
  DateTime? _to;
  bool _loading = true;
  Object? _error;

  /// الأوامر المفتوحة (نعرض أصنافها) — العرض افتراضياً مطوي.
  final Set<int> _expanded = {};

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
      final list = await _repo.issued(
        from: _from == null ? null : _ymd(_from!),
        to: _to == null ? null : _ymd(_to!),
      );
      if (!mounted) return;
      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e;
          _loading = false;
        });
      }
    }
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      initialEntryMode: DatePickerEntryMode.calendarOnly,
      context: context,
      firstDate: DateTime(now.year - 2),
      lastDate: now,
      initialDateRange: (_from != null && _to != null)
          ? DateTimeRange(start: _from!, end: _to!)
          : null,
    );
    if (range == null) return;
    setState(() {
      _from = DateTime(range.start.year, range.start.month, range.start.day);
      _to = DateTime(range.end.year, range.end.month, range.end.day);
    });
    _load();
  }

  void _clearRange() {
    setState(() {
      _from = null;
      _to = null;
    });
    _load();
  }

  // ---------- الإجماليات ----------

  int get _ordersCount => _items.length;
  num get _unitsCount =>
      _items.fold<num>(0, (s, o) => s + o.totalQuantity);
  double get _totalValue =>
      _items.fold<double>(0, (s, o) => s + o.total);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('أوامر الصرف'),
        actions: [
          IconButton(
            tooltip: 'تصفية بالتاريخ',
            onPressed: _pickRange,
            icon: const Icon(Icons.date_range_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _load,
          color: AppColors.primary,
          child: _body(),
        ),
      ),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(
          child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null) {
      final e = _error;
      return ListView(
        padding: const EdgeInsets.all(24),
        children: [
          const SizedBox(height: 70),
          const Icon(Icons.cloud_off_outlined,
              size: 44, color: AppColors.muted),
          const SizedBox(height: 12),
          Text(
            e is ApiException ? e.message : 'تعذّر تحميل أوامر الصرف',
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 13.5, color: AppColors.inkSoft),
          ),
          const SizedBox(height: 16),
          Center(
            child: OutlinedButton(
                onPressed: _load, child: const Text('إعادة المحاولة')),
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
      children: [
        const ScreenHeader(
          icon: Icons.assignment_turned_in_outlined,
          title: 'أوامر الصرف',
          subtitle: 'البضاعة التي صُرفت لك من المخزن',
        ),
        const SizedBox(height: 14),
        if (_from != null && _to != null) _rangeChip(),
        if (_items.isNotEmpty) ...[
          _totalsBar(),
          const SizedBox(height: 14),
        ],
        if (_items.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 60),
            child: Column(
              children: [
                const Icon(Icons.inbox_outlined,
                    size: 44, color: AppColors.muted),
                const SizedBox(height: 12),
                Text(
                  _from != null
                      ? 'لا توجد أوامر صرف في هذه الفترة'
                      : 'لا توجد أوامر صرف',
                  style:
                      const TextStyle(fontSize: 13.5, color: AppColors.muted),
                ),
              ],
            ),
          )
        else
          ..._items.map(_orderCard),
      ],
    );
  }

  Widget _rangeChip() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        children: [
          Expanded(
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(Icons.date_range,
                      size: 16, color: AppColors.primaryDeep),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${_ymd(_from!)}  ←  ${_ymd(_to!)}',
                      style: const TextStyle(
                          fontSize: 12.5,
                          color: AppColors.primaryDeep,
                          fontWeight: FontWeight.w600),
                    ),
                  ),
                  InkWell(
                    onTap: _clearRange,
                    borderRadius: BorderRadius.circular(999),
                    child: const Padding(
                      padding: EdgeInsets.all(2),
                      child: Icon(Icons.close,
                          size: 16, color: AppColors.primaryDeep),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _totalsBar() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.primaryWash,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          _totalCell('الأوامر', '$_ordersCount', AppColors.primaryDeep),
          _sep(),
          _totalCell('الوحدات', _qty(_unitsCount), AppColors.good),
          _sep(),
          _totalCell('القيمة', '${_money(_totalValue)} ج', AppColors.ink),
        ],
      ),
    );
  }

  Widget _sep() =>
      Container(width: 1, height: 30, color: const Color(0x332563EB));

  Widget _totalCell(String label, String value, Color color) {
    return Expanded(
      child: Column(
        children: [
          Text(label,
              style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
          const SizedBox(height: 3),
          Text(value,
              style: TextStyle(
                  fontSize: 14.5, fontWeight: FontWeight.w800, color: color)),
        ],
      ),
    );
  }

  Widget _orderCard(IssuedOrder o) {
    final open = _expanded.contains(o.id);
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.line),
        boxShadow: AppColors.softShadow,
      ),
      child: Column(
        children: [
          InkWell(
            onTap: () => setState(() {
              if (open) {
                _expanded.remove(o.id);
              } else {
                _expanded.add(o.id);
              }
            }),
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding: const EdgeInsets.all(15),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.primaryWash,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.local_shipping_outlined,
                        color: AppColors.primaryDeep, size: 21),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('أمر #${o.id}',
                            style: const TextStyle(
                                fontSize: 14, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 3),
                        Text(
                          '${_date(o.createdAt)} · ${o.itemsCount} صنف'
                          ' · ${_qty(o.totalQuantity)} وحدة',
                          style: const TextStyle(
                              fontSize: 11.5, color: AppColors.muted),
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      MoneyText(_money(o.total), size: 14),
                      const SizedBox(height: 5),
                      o.isExecuted
                          ? StatusBadge.good(o.statusLabel)
                          : StatusBadge.warn(o.statusLabel),
                    ],
                  ),
                  Icon(open ? Icons.expand_less : Icons.expand_more,
                      color: AppColors.muted, size: 20),
                ],
              ),
            ),
          ),
          if (open && o.items.isNotEmpty) ...[
            const Divider(height: 1, color: AppColors.lineSoft),
            Padding(
              padding: const EdgeInsets.fromLTRB(15, 4, 15, 8),
              child: Column(
                children: [
                  for (var i = 0; i < o.items.length; i++) ...[
                    if (i != 0)
                      const Divider(height: 1, color: AppColors.lineSoft),
                    _itemRow(o.items[i]),
                  ],
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _itemRow(IssuedItem it) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 11),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(it.productName,
                    style: const TextStyle(
                        fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(height: 3),
                Text(
                  'السعر ${_money(it.price)} ج · رصيد المخزن ${_qty(it.balance)}',
                  style:
                      const TextStyle(fontSize: 11, color: AppColors.muted),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('× ${_qty(it.quantity)}',
                  style: const TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w800,
                      color: AppColors.primaryDeep)),
              const SizedBox(height: 2),
              Text('${_money(it.lineTotal)} ج',
                  style: const TextStyle(
                      fontSize: 11.5, color: AppColors.muted)),
            ],
          ),
        ],
      ),
    );
  }

  // ---------- مساعدات ----------

  static String _ymd(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-'
      '${d.day.toString().padLeft(2, '0')}';

  static String _date(String? iso) =>
      iso == null ? '—' : iso.split('T').first;

  static String _qty(num v) =>
      v == v.roundToDouble() ? v.toInt().toString() : v.toString();

  static String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
