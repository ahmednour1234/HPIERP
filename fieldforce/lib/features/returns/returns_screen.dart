import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../orders/order_repository.dart';

/// شاشة المرتجعات — مرتجع ضد فاتورة بيع (الطريقة الصحيحة).
///
/// 1) يختار المندوب فاتورة بيع  2) نجلب /returnable  3) يحدّد الكميات
/// (بحد أقصى quantity_returnable)  4) POST /orders/returns.
class ReturnsScreen extends StatefulWidget {
  /// اختياري: لو فُتحت من فاتورة محددة.
  final int? orderId;
  const ReturnsScreen({super.key, this.orderId, int? customerId});

  @override
  State<ReturnsScreen> createState() => _ReturnsScreenState();
}

class _ReturnsScreenState extends State<ReturnsScreen> {
  final _repo = OrderRepository();

  // مرحلة 1: اختيار فاتورة
  late Future<List<Order>> _salesFuture;
  String _search = '';
  Timer? _debounce;
  // مرحلة 2: تفاصيل القابل للإرجاع
  Returnable? _returnable;
  bool _loadingReturnable = false;
  final Map<int, num> _qty = {}; // productId -> qty to return
  int _reason = 0;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _salesFuture = _loadSales();
    if (widget.orderId != null) _loadReturnable(widget.orderId!);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  Future<List<Order>> _loadSales() async {
    final res = await _repo.list(
      type: 4,
      search: _search.trim().isEmpty ? null : _search.trim(),
      limit: 100,
    );
    return res.items;
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () {
      _search = v;
      setState(() => _salesFuture = _loadSales());
    });
  }

  Future<void> _loadReturnable(int orderId) async {
    setState(() {
      _loadingReturnable = true;
      _returnable = null;
      _qty.clear();
    });
    try {
      final r = await _repo.returnable(orderId);
      if (mounted) setState(() => _returnable = r);
    } on ApiException catch (e) {
      _snack(e.message, AppColors.danger);
    } finally {
      if (mounted) setState(() => _loadingReturnable = false);
    }
  }

  double get _total {
    if (_returnable == null) return 0;
    double t = 0;
    for (final l in _returnable!.lines) {
      t += l.price * (_qty[l.productId] ?? 0);
    }
    return t;
  }

  Future<void> _submit() async {
    if (_returnable == null) return;
    final items = _qty.entries
        .where((e) => e.value > 0)
        .map((e) => (productId: e.key, quantity: e.value))
        .toList();
    if (items.isEmpty) {
      _snack('حدّد كميات للإرجاع', AppColors.danger);
      return;
    }
    setState(() => _saving = true);
    try {
      final order = await _repo.fileReturn(
        orderId: _returnable!.orderId,
        items: items,
        note: const ['تالف', 'منتهي', 'خطأ طلب', 'عدم بيع'][_reason],
      );
      if (!mounted) return;
      _snack('تم حفظ المرتجع #${order.id}', AppColors.danger);
      // إعادة للبداية بعد النجاح
      setState(() {
        _returnable = null;
        _qty.clear();
        _salesFuture = _loadSales();
      });
    } on ApiException catch (e) {
      // مثلاً "Only 3 of X can still be returned"
      _snack(e.message, AppColors.danger);
    } finally {
      if (mounted) setState(() => _saving = false);
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
      appBar: AppBar(
        title: const Text('مرتجع بضاعة'),
        leading: _returnable != null
            ? IconButton(
                icon: const Icon(Icons.arrow_forward),
                onPressed: () => setState(() {
                  _returnable = null;
                  _qty.clear();
                }),
              )
            : null,
      ),
      body: SafeArea(
        child: _returnable != null
            ? _returnStep()
            : _pickInvoiceStep(),
      ),
    );
  }

  // ---- مرحلة 1: اختيار الفاتورة ----
  Widget _pickInvoiceStep() {
    return Column(
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(20, 8, 20, 8),
          child: ScreenHeader(
            icon: Icons.assignment_return_outlined,
            title: 'اختر الفاتورة',
            subtitle: 'المرتجع يكون دائماً ضد فاتورة بيع',
          ),
        ),
        // حقل البحث (رقم الفاتورة أو اسم العميل)
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
          child: TextField(
            onChanged: _onSearch,
            decoration: const InputDecoration(
              hintText: 'ابحث برقم الفاتورة أو اسم العميل…',
              prefixIcon: Icon(Icons.search, color: AppColors.muted),
              isDense: true,
            ),
          ),
        ),
        if (_loadingReturnable)
          const LinearProgressIndicator(
              color: AppColors.primary, minHeight: 2),
        Expanded(
          child: FutureBuilder<List<Order>>(
            future: _salesFuture,
            builder: (context, snap) {
              if (snap.connectionState == ConnectionState.waiting) {
                return const Center(
                    child:
                        CircularProgressIndicator(color: AppColors.primary));
              }
              if (snap.hasError) {
                return Center(
                    child: Text(
                        snap.error is ApiException
                            ? (snap.error as ApiException).message
                            : 'تعذّر التحميل',
                        style: const TextStyle(color: AppColors.muted)));
              }
              final list = snap.data!;
              if (list.isEmpty) {
                return const Center(
                    child: Text('لا توجد فواتير بيع',
                        style: TextStyle(color: AppColors.muted)));
              }
              return ListView.separated(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
                itemCount: list.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (context, i) {
                  final o = list[i];
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      child: AppListRow(
                        thumb: '#${o.id}',
                        name: o.customerName,
                        sub: '${o.typeLabel} · ${_date(o.createdAt)}',
                        onTap: () => _loadReturnable(o.id),
                        trailing: MoneyText(_money(o.amount), size: 14),
                      ),
                    ),
                  );
                },
              );
            },
          ),
        ),
      ],
    );
  }

  // ---- مرحلة 2: تحديد الكميات ----
  Widget _returnStep() {
    final r = _returnable!;
    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
            children: [
              ScreenHeader(
                icon: Icons.assignment_return_outlined,
                title: 'مرتجع فاتورة #${r.orderId}',
                subtitle: r.customerName,
              ),
              const SizedBox(height: 14),
              if (r.fullyReturned)
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.warnWash,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Text('تم إرجاع كل أصناف هذه الفاتورة',
                      style: TextStyle(
                          color: Color(0xFFB4740B),
                          fontWeight: FontWeight.w600)),
                )
              else ...[
                const Text('حدّد الكميات المرتجعة',
                    style: TextStyle(
                        fontSize: 13,
                        color: AppColors.muted,
                        fontWeight: FontWeight.w600)),
                const SizedBox(height: 10),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 15),
                    child: Column(
                      children: [
                        for (var i = 0; i < r.lines.length; i++) ...[
                          if (i != 0)
                            const Divider(height: 1, color: AppColors.line),
                          _lineRow(r.lines[i]),
                        ],
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                SegmentedTabs(
                  items: const ['تالف', 'منتهي', 'خطأ طلب', 'عدم بيع'],
                  selected: _reason,
                  onChanged: (i) => setState(() => _reason = i),
                  activeColor: AppColors.danger,
                ),
              ],
            ],
          ),
        ),
        if (!r.fullyReturned)
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 16),
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 14),
                  decoration: BoxDecoration(
                    color: AppColors.dangerWash,
                    borderRadius: BorderRadius.circular(15),
                    border: Border.all(color: AppColors.danger),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('إجمالي المرتجع (تقديري)',
                          style: TextStyle(
                              fontSize: 13, color: AppColors.inkSoft)),
                      MoneyText(_money(_total),
                          size: 20, color: AppColors.danger),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                ElevatedButton.icon(
                  onPressed: _saving ? null : _submit,
                  style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.danger),
                  icon: _saving
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                              strokeWidth: 2.2, color: Colors.white))
                      : const Icon(Icons.undo, size: 20),
                  label: const Text('تأكيد المرتجع'),
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _lineRow(ReturnableLine l) {
    final qty = _qty[l.productId] ?? 0;
    final max = l.quantityReturnable;
    final canReturn = max > 0;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l.productName,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w600)),
                const SizedBox(height: 2),
                Text(
                    canReturn
                        ? 'متاح للإرجاع: $max · ${_money(l.price)}'
                        : 'تم إرجاعه بالكامل',
                    style: TextStyle(
                        fontSize: 12,
                        color: canReturn
                            ? AppColors.muted
                            : const Color(0xFFB4740B))),
              ],
            ),
          ),
          if (canReturn)
            Container(
              decoration: BoxDecoration(
                color: AppColors.bg,
                borderRadius: BorderRadius.circular(11),
                border: Border.all(color: AppColors.line),
              ),
              child: Row(
                children: [
                  InkWell(
                    onTap: qty > 0
                        ? () => setState(() {
                              final n = qty - 1;
                              n <= 0
                                  ? _qty.remove(l.productId)
                                  : _qty[l.productId] = n;
                            })
                        : null,
                    child: Padding(
                      padding: const EdgeInsets.all(8),
                      child: Icon(Icons.remove,
                          size: 18,
                          color:
                              qty > 0 ? AppColors.inkSoft : AppColors.muted),
                    ),
                  ),
                  SizedBox(
                    width: 30,
                    child: Text('$qty',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: qty > 0
                                ? AppColors.danger
                                : AppColors.muted)),
                  ),
                  InkWell(
                    // لا يتجاوز الحد المتاح للإرجاع
                    onTap: qty < max
                        ? () => setState(() => _qty[l.productId] = qty + 1)
                        : null,
                    child: Padding(
                      padding: const EdgeInsets.all(8),
                      child: Icon(Icons.add,
                          size: 18,
                          color: qty < max
                              ? AppColors.inkSoft
                              : AppColors.muted),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  String _date(String? iso) => iso == null ? '' : iso.split('T').first;

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
