import 'package:flutter/services.dart';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../orders/order_repository.dart';
import '../products/product_repository.dart';
import '../reference/reference_repository.dart';
import '../customers/customer_repository.dart';
import '../customers/customer_picker.dart';

/// شاشة المبيعات (POS) — تحمّل مخزون المندوب، تبني سلة، وترسل POST /orders.
///
/// ملاحظة: اختيار العميل هنا ثابت للعرض؛ يُمرَّر `customerId` عند الدخول من
/// ملف العميل. الأسعار حاكمة من الخادم — ما نرسله تلميح فقط.
class SalesScreen extends StatefulWidget {
  final int? customerId;
  const SalesScreen({super.key, this.customerId});

  @override
  State<SalesScreen> createState() => _SalesScreenState();
}

class _SalesScreenState extends State<SalesScreen> {
  final _stockRepo = ProductRepository();
  final _orderRepo = OrderRepository();
  final _refRepo = ReferenceRepository();

  int _payType = 0; // 0 كاش (cash=1) · 1 آجل (cash=2)

  late Future<List<Product>> _future;
  List<Category> _categories = [];
  int? _categoryId; // null = الكل
  final Map<int, num> _cart = {}; // productId -> quantity
  final Map<int, double> _lineDiscount = {}; // productId -> discount value
  final Map<int, String> _lineDiscountType = {}; // productId -> percent|amount
  final Map<int, Product> _byId = {};
  bool _placing = false;

  // خصم إجمالي على الطلب
  double _extraDiscount = 0;
  String _extraDiscountType = 'percent';

  File? _receipt; // صورة الإيصال
  final _picker = ImagePicker();

  Customer? _customer;
  final _custRepo = CustomerRepository();

  @override
  void initState() {
    super.initState();
    _future = _load();
    _loadCategories();
    _preloadCustomer();
  }

  Future<void> _loadCategories() async {
    try {
      // فئات المندوب فقط — لا كل فئات النظام.
      final cats = await _refRepo.myCategories();
      if (mounted) setState(() => _categories = cats);
    } catch (_) {}
  }

  Future<List<Product>> _load() async {
    // نمرّر customerId ليرجع السيرفر سعر العميل مباشرة (customer_price).
    // مخزون العربة محدود بطبيعته؛ حد مرتفع يضمن ظهوره كاملاً.
    final res = await _stockRepo.stocks(
      vanStock: true,
      categoryId: _categoryId,
      customerId: _customer?.id,
      limit: 200,
    );
    // نعرض فقط المنتجات التي عندها كمية متاحة (متاح > 0).
    final available = res.items.where((p) => p.quantity > 0).toList();
    _byId
      ..clear()
      ..addEntries(available.map((p) => MapEntry(p.id, p)));
    return available;
  }

  void _selectCategory(int? id) {
    setState(() {
      _categoryId = id;
      _future = _load();
    });
  }

  /// سحب لتحت لإعادة تحميل المخزون من السيرفر.
  Future<void> _refresh() async {
    final f = _load();
    setState(() {
      _future = f;
    });
    await f;
  }


  /// إجمالي قبل الخصومات (تقديري — السيرفر هو الحاكم).
  double get _total {
    double t = 0;
    _cart.forEach((id, qty) {
      if (_byId[id] != null) t += _priceOf(id) * qty;
    });
    return t;
  }

  /// سعر السطر بعد خصم الصنف (تقديري).
  double _lineNet(int id, num qty) {
    final p = _byId[id];
    if (p == null) return 0;
    double line = _priceOf(id) * qty;
    final disc = _lineDiscount[id] ?? 0;
    if (disc > 0) {
      if (_lineDiscountType[id] == 'amount') {
        line -= disc * qty; // مبلغ لكل وحدة
      } else {
        line -= line * (disc / 100); // نسبة
      }
    }
    return line < 0 ? 0 : line;
  }

  /// الإجمالي بعد خصومات الأصناف + الخصم الإجمالي (تقديري).
  double get _netTotal {
    double t = 0;
    _cart.forEach((id, qty) => t += _lineNet(id, qty));
    if (_extraDiscount > 0) {
      if (_extraDiscountType == 'amount') {
        t -= _extraDiscount;
      } else {
        t -= t * (_extraDiscount / 100);
      }
    }
    return t < 0 ? 0 : t;
  }

  /// إجمالي خصومات الأصناف (فرق قبل/بعد خصم كل سطر).
  double get _lineDiscountTotal {
    double t = 0;
    _cart.forEach((id, qty) {
      if (_byId[id] != null) t += (_priceOf(id) * qty) - _lineNet(id, qty);
    });
    return t;
  }

  /// قيمة الخصم الإجمالي (بعد خصومات الأصناف).
  double get _extraDiscountValue {
    if (_extraDiscount <= 0) return 0;
    double afterLines = 0;
    _cart.forEach((id, qty) => afterLines += _lineNet(id, qty));
    return _extraDiscountType == 'amount'
        ? _extraDiscount
        : afterLines * (_extraDiscount / 100);
  }

  /// الضريبة = لكل صنف: (سعر السطر بعد الخصم) × نسبة ضريبة الصنف%.
  /// نسبة الخصم الإجمالي تُطبَّق على السطر أولاً (توزيع تناسبي).
  double get _taxTotal {
    // معامل الخصم الإجمالي (لو نسبة).
    double factor = 1.0;
    if (_extraDiscount > 0 && _extraDiscountType == 'percent') {
      factor = 1 - (_extraDiscount / 100);
    }
    double t = 0;
    _cart.forEach((id, qty) {
      final p = _byId[id];
      if (p != null && p.tax > 0) {
        t += _lineNet(id, qty) * factor * (p.tax / 100);
      }
    });
    return t;
  }

  /// الإجمالي النهائي = الصافي بعد الخصم + الضريبة.
  double get _grandTotal => _netTotal + _taxTotal;

  int get _lineCount => _cart.values.where((q) => q > 0).length;

  Future<void> _preloadCustomer() async {
    if (widget.customerId == null) return;
    try {
      final c = await _custRepo.byId(widget.customerId!);
      if (mounted) setState(() => _customer = c);
      _reloadForCustomer();
    } catch (_) {}
  }

  Future<void> _pickCustomer() async {
    final c = await pickCustomer(context);
    if (c != null && mounted) {
      setState(() => _customer = c);
      _reloadForCustomer();
    }
  }

  /// إعادة تحميل المخزون بسعر العميل — `/stocks?customer_id=` يرجّع
  /// `customer_price` في نفس الطلب، فلا حاجة لاستدعاء منفصل.
  void _reloadForCustomer() {
    if (!mounted) return;
    setState(() => _future = _load());
  }

  /// السعر الفعّال للصنف: سعر العميل إن وُجد، وإلا selling_price العام.
  double _priceOf(int id) => _byId[id]?.effectivePrice ?? 0;

  Future<void> _place() async {
    if (_customer == null) {
      _snack('اختر العميل أولاً', AppColors.danger);
      return;
    }
    if (_lineCount == 0) {
      _snack('أضف أصنافاً أولاً', AppColors.danger);
      return;
    }
    setState(() => _placing = true);
    try {
      final cart = _cart.entries.where((e) => e.value > 0).map((e) {
        final disc = _lineDiscount[e.key];
        return CartItem(
          e.key,
          e.value,
          _priceOf(e.key),
          disc != null && disc > 0 ? disc : null,
          disc != null && disc > 0 ? _lineDiscountType[e.key] : null,
        );
      }).toList();
      // لا نرسل collected_cash — السيرفر يحسب الإجمالي، والمندوب يحصّل
      // من الفاتورة بالرقم الصحيح (زر تحصيل). يفصل البيع عن التحصيل.
      final order = await _orderRepo.place(
        customerId: _customer!.id,
        cart: cart,
        orderType: 4, // بيع
        cash: _payType == 0 ? 1 : 2, // 1 نقدي · 2 آجل
        extraDiscount: _extraDiscount > 0 ? _extraDiscount : null,
        extraDiscountType: _extraDiscount > 0 ? _extraDiscountType : null,
        receiptPath: _receipt?.path,
      );
      if (!mounted) return;
      setState(() {
        _cart.clear();
        _lineDiscount.clear();
        _lineDiscountType.clear();
        _extraDiscount = 0;
        _receipt = null;
      });
      // افتح الفاتورة النهائية (بأرقام السيرفر الصحيحة)
      context.push('/order', extra: order.id);
    } on ApiException catch (e) {
      _snack(e.message, AppColors.danger); // مثلاً "Not enough stock…"
    } finally {
      if (mounted) setState(() => _placing = false);
    }
  }

  void _snack(String msg, Color bg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(msg), backgroundColor: bg));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const ScreenHeader(
                icon: Icons.shopping_bag_outlined,
                title: 'نقطة البيع',
                subtitle: 'اختر العميل والأصناف',
              ),
              const SizedBox(height: 12),
              _customerBar(),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: SegmentedTabs(
                      items: const ['كاش', 'آجل'],
                      selected: _payType,
                      onChanged: (i) => setState(() => _payType = i),
                    ),
                  ),
                  if (_categories.isNotEmpty) ...[
                    const SizedBox(width: 10),
                    _filterButton(),
                  ],
                ],
              ),
              const SizedBox(height: 12),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: _refresh,
                  color: AppColors.primary,
                  child: FutureBuilder<List<Product>>(
                    future: _future,
                    builder: (context, snap) {
                      if (snap.connectionState == ConnectionState.waiting) {
                        return const Center(
                            child: CircularProgressIndicator(
                                color: AppColors.primary));
                      }
                      if (snap.hasError) {
                        final msg = snap.error is ApiException
                            ? (snap.error as ApiException).message
                            : 'تعذّر تحميل المخزون';
                        // ListView حتى يعمل السحب لإعادة المحاولة.
                        return ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            const SizedBox(height: 100),
                            Center(
                                child: Text(msg,
                                    style: const TextStyle(
                                        color: AppColors.muted))),
                            const SizedBox(height: 10),
                            const Center(
                                child: Text('اسحب لأسفل لإعادة المحاولة',
                                    style: TextStyle(
                                        fontSize: 12,
                                        color: AppColors.muted))),
                          ],
                        );
                      }
                      final products = snap.data!;
                      if (products.isEmpty) {
                        return ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: const [
                            SizedBox(height: 100),
                            Center(
                                child: Text('لا يوجد مخزون',
                                    style:
                                        TextStyle(color: AppColors.muted))),
                            SizedBox(height: 10),
                            Center(
                                child: Text('اسحب لأسفل للتحديث',
                                    style: TextStyle(
                                        fontSize: 12,
                                        color: AppColors.muted))),
                          ],
                        );
                      }
                      return ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 15),
                              child: Column(
                                children: [
                                  for (var i = 0;
                                      i < products.length;
                                      i++) ...[
                                    if (i != 0)
                                      const Divider(
                                          height: 1, color: AppColors.line),
                                    _productRow(products[i]),
                                  ],
                                ],
                              ),
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ),
              const SizedBox(height: 10),
              _receiptRow(),
              const SizedBox(height: 10),
              _totalBar(),
              const SizedBox(height: 12),
              ElevatedButton.icon(
                onPressed: _placing ? null : _place,
                icon: _placing
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2.2, color: Colors.white))
                    : const Icon(Icons.check_rounded, size: 20),
                label: const Text('تأكيد الطلب'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// بطاقة اختيار العميل — تفتح قائمة العملاء.
  Widget _customerBar() {
    final has = _customer != null;
    return GestureDetector(
      onTap: _pickCustomer,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: has ? AppColors.primaryWash : AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
              color: has ? AppColors.primary : AppColors.line),
        ),
        child: Row(
          children: [
            Icon(has ? Icons.store_rounded : Icons.person_search_rounded,
                color: has ? AppColors.primaryDeep : AppColors.muted,
                size: 22),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(has ? _customer!.name : 'اختر العميل',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          color: has ? AppColors.ink : AppColors.muted)),
                  if (has)
                    Text(_customer!.mobile,
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.muted)),
                ],
              ),
            ),
            Icon(has ? Icons.swap_horiz_rounded : Icons.add_circle_outline,
                color: AppColors.primaryDeep, size: 20),
          ],
        ),
      ),
    );
  }

  /// زر فلترة أنيق جنب التبويبات — يعرض التصنيف المختار ويفتح قائمة.
  Widget _filterButton() {
    final active = _categoryId != null;
    final label = active
        ? _categories
            .firstWhere((c) => c.id == _categoryId,
                orElse: () => Category(0, 'تصنيف', 0, true))
            .name
        : 'تصنيف';
    return GestureDetector(
      onTap: _openCategorySheet,
      child: Container(
        height: 46,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: BoxDecoration(
          color: active ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(13),
          border:
              Border.all(color: active ? AppColors.primary : AppColors.line),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.tune_rounded,
                size: 18,
                color: active ? Colors.white : AppColors.inkSoft),
            const SizedBox(width: 6),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 70),
              child: Text(label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: active ? Colors.white : AppColors.inkSoft)),
            ),
          ],
        ),
      ),
    );
  }

  void _openCategorySheet() {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                    color: AppColors.line,
                    borderRadius: BorderRadius.circular(2)),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(16, 16, 16, 4),
                child: Text('اختر التصنيف',
                    style:
                        TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: Text('التصنيفات المسنَدة لك فقط',
                    style:
                        TextStyle(fontSize: 12.5, color: AppColors.muted)),
              ),
              // قائمة قابلة للتمرير — لا تتجاوز نصف الشاشة مهما كثرت الفئات.
              Flexible(
                child: ListView(
                  shrinkWrap: true,
                  padding: EdgeInsets.zero,
                  children: [
                    _sheetTile('كل تصنيفاتي', _categoryId == null, () {
                      Navigator.pop(ctx);
                      _selectCategory(null);
                    }),
                    for (final c in _categories)
                      _sheetTile(c.name, _categoryId == c.id, () {
                        Navigator.pop(ctx);
                        _selectCategory(c.id);
                      }),
                  ],
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        );
      },
    );
  }

  Widget _sheetTile(String label, bool active, VoidCallback onTap) {
    return ListTile(
      onTap: onTap,
      title: Text(label,
          style: TextStyle(
              fontSize: 14,
              fontWeight: active ? FontWeight.w700 : FontWeight.w500,
              color: active ? AppColors.primaryDeep : AppColors.ink)),
      trailing: active
          ? const Icon(Icons.check_circle,
              color: AppColors.primary, size: 20)
          : null,
    );
  }

  Widget _productRow(Product p) {
    final qty = _cart[p.id] ?? 0;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(p.name,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w600)),
                const SizedBox(height: 2),
                Row(
                  children: [
                    Text('${_money(_priceOf(p.id))} · متاح ${p.quantity}',
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.muted)),
                    // سعر خاص بالعميل — نعرض السعر العام مشطوباً ليظهر الفرق.
                    if (p.hasCustomerPrice) ...[
                      const SizedBox(width: 6),
                      Text(_money(p.sellingPrice),
                          style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.muted,
                              decoration: TextDecoration.lineThrough)),
                    ],
                  ],
                ),
                if (qty > 0) ...[
                  const SizedBox(height: 4),
                  GestureDetector(
                    onTap: () => _lineDiscountDialog(p),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.local_offer_outlined,
                            size: 13,
                            color: (_lineDiscount[p.id] ?? 0) > 0
                                ? AppColors.good
                                : AppColors.primaryDeep),
                        const SizedBox(width: 4),
                        Text(
                          (_lineDiscount[p.id] ?? 0) > 0
                              ? 'خصم ${_money(_lineDiscount[p.id]!)}${_lineDiscountType[p.id] == 'percent' ? '%' : ' ج'}'
                              : 'إضافة خصم',
                          style: TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600,
                              color: (_lineDiscount[p.id] ?? 0) > 0
                                  ? AppColors.good
                                  : AppColors.primaryDeep),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
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
                            n <= 0 ? _cart.remove(p.id) : _cart[p.id] = n;
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
                // الضغط على الرقم يفتح خانة لكتابة الكمية مباشرة.
                InkWell(
                  onTap: () => _editQty(p.id, qty),
                  child: Container(
                    constraints: const BoxConstraints(minWidth: 40),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 6, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(7),
                      border: Border.all(color: AppColors.line),
                    ),
                    child: Text('$qty',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: qty > 0
                                ? AppColors.primaryDeep
                                : AppColors.muted)),
                  ),
                ),
                InkWell(
                  onTap: () => setState(() => _cart[p.id] = qty + 1),
                  child: const Padding(
                    padding: EdgeInsets.all(8),
                    child:
                        Icon(Icons.add, size: 18, color: AppColors.inkSoft),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// كتابة الكمية بالكيبورد (بدل الضغط على + مئات المرات). 0 = شيل الصنف.
  Future<void> _editQty(int productId, num current) async {
    final ctrl = TextEditingController(text: current > 0 ? '$current' : '');
    ctrl.selection =
        TextSelection(baseOffset: 0, extentOffset: ctrl.text.length);
    final v = await showDialog<int>(
      context: context,
      builder: (ctx) {
        void submit() =>
            Navigator.pop(ctx, int.tryParse(ctrl.text.trim()) ?? 0);
        return AlertDialog(
          title: const Text('الكمية'),
          content: TextField(
            controller: ctrl,
            autofocus: true,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
            decoration: const InputDecoration(hintText: '0'),
            onSubmitted: (_) => submit(),
          ),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('إلغاء')),
            ElevatedButton(onPressed: submit, child: const Text('تم')),
          ],
        );
      },
    );
    if (v == null || !mounted) return;
    setState(() => v <= 0 ? _cart.remove(productId) : _cart[productId] = v);
  }

  /// ملخص الفاتورة التفصيلي — فرعي / خصم / ضريبة / إجمالي.
  Widget _totalBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.primaryWash,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: AppColors.primary),
      ),
      child: Column(
        children: [
          _summaryLine('الإجمالي الفرعي ($_lineCount أصناف)', _money(_total)),
          if (_lineDiscountTotal > 0)
            _summaryLine('خصم الأصناف', '- ${_money(_lineDiscountTotal)}',
                color: AppColors.good),
          // زر الخصم الإجمالي
          GestureDetector(
            onTap: _extraDiscountDialog,
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Row(
                children: [
                  const Icon(Icons.local_offer_outlined,
                      size: 15, color: AppColors.primaryDeep),
                  const SizedBox(width: 6),
                  Text(
                    _extraDiscount > 0 ? 'خصم إجمالي' : 'إضافة خصم إجمالي',
                    style: const TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: AppColors.primaryDeep),
                  ),
                  const Spacer(),
                  if (_extraDiscount > 0)
                    Text('- ${_money(_extraDiscountValue)}',
                        style: const TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.good,
                            fontFeatures: [FontFeature.tabularFigures()]))
                  else
                    const Icon(Icons.add_circle_outline,
                        size: 16, color: AppColors.primaryDeep),
                ],
              ),
            ),
          ),
          _summaryLine('الضريبة', _money(_taxTotal)),
          const Divider(height: 14, color: Color(0x332563EB)),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('الإجمالي النهائي',
                  style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: AppColors.ink)),
              MoneyText(_money(_grandTotal),
                  size: 22, color: AppColors.primaryDeep),
            ],
          ),
        ],
      ),
    );
  }

  Widget _summaryLine(String label, String value, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: const TextStyle(
                  fontSize: 12.5, color: AppColors.inkSoft)),
          Text(value,
              style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: color ?? AppColors.ink,
                  fontFeatures: const [FontFeature.tabularFigures()])),
        ],
      ),
    );
  }

  Future<void> _pickReceipt() async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined,
                  color: AppColors.primaryDeep),
              title: const Text('التقاط صورة'),
              onTap: () => Navigator.pop(ctx, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined,
                  color: AppColors.primaryDeep),
              title: const Text('من المعرض'),
              onTap: () => Navigator.pop(ctx, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;
    final x = await _picker.pickImage(source: source, maxWidth: 1600);
    if (x != null && mounted) setState(() => _receipt = File(x.path));
  }

  Widget _receiptRow() {
    final has = _receipt != null;
    return GestureDetector(
      onTap: _pickReceipt,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(
              color: has ? AppColors.primary : AppColors.line,
              style: has ? BorderStyle.solid : BorderStyle.solid),
        ),
        child: Row(
          children: [
            if (has)
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.file(_receipt!,
                    width: 38, height: 38, fit: BoxFit.cover),
              )
            else
              const Icon(Icons.receipt_long_outlined,
                  color: AppColors.muted, size: 24),
            const SizedBox(width: 12),
            Expanded(
              child: Text(has ? 'تم إرفاق الإيصال' : 'إرفاق صورة الإيصال',
                  style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w600,
                      color: has ? AppColors.ink : AppColors.muted)),
            ),
            if (has)
              GestureDetector(
                onTap: () => setState(() => _receipt = null),
                child: const Icon(Icons.close,
                    size: 20, color: AppColors.muted),
              )
            else
              const Icon(Icons.add_a_photo_outlined,
                  size: 20, color: AppColors.primaryDeep),
          ],
        ),
      ),
    );
  }

  /// نافذة خصم لصنف معيّن.
  void _lineDiscountDialog(Product p) {
    final ctrl = TextEditingController(
        text: (_lineDiscount[p.id] ?? 0) > 0
            ? _money(_lineDiscount[p.id]!)
            : '');
    String type = _lineDiscountType[p.id] ?? 'percent';
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(
              20, 18, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('خصم — ${p.name}',
                  style: const TextStyle(
                      fontSize: 16, fontWeight: FontWeight.w700)),
              const SizedBox(height: 16),
              SegmentedTabs(
                items: const ['نسبة %', 'مبلغ ج'],
                selected: type == 'percent' ? 0 : 1,
                onChanged: (i) =>
                    setSheet(() => type = i == 0 ? 'percent' : 'amount'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: ctrl,
                keyboardType: TextInputType.number,
                autofocus: true,
                decoration: InputDecoration(
                    hintText: type == 'percent' ? 'نسبة الخصم' : 'مبلغ الخصم',
                    suffixText: type == 'percent' ? '%' : 'ج'),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () {
                        setState(() {
                          _lineDiscount.remove(p.id);
                          _lineDiscountType.remove(p.id);
                        });
                        Navigator.pop(ctx);
                      },
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(50),
                        side: const BorderSide(color: AppColors.line),
                        foregroundColor: AppColors.inkSoft,
                      ),
                      child: const Text('إلغاء الخصم'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () {
                        final v = double.tryParse(ctrl.text.trim()) ?? 0;
                        setState(() {
                          if (v > 0) {
                            _lineDiscount[p.id] = v;
                            _lineDiscountType[p.id] = type;
                          } else {
                            _lineDiscount.remove(p.id);
                            _lineDiscountType.remove(p.id);
                          }
                        });
                        Navigator.pop(ctx);
                      },
                      child: const Text('تطبيق'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// نافذة الخصم الإجمالي على الطلب.
  void _extraDiscountDialog() {
    final ctrl = TextEditingController(
        text: _extraDiscount > 0 ? _money(_extraDiscount) : '');
    String type = _extraDiscountType;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(
              20, 18, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text('خصم إجمالي على الطلب',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
              const SizedBox(height: 16),
              SegmentedTabs(
                items: const ['نسبة %', 'مبلغ ج'],
                selected: type == 'percent' ? 0 : 1,
                onChanged: (i) =>
                    setSheet(() => type = i == 0 ? 'percent' : 'amount'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: ctrl,
                keyboardType: TextInputType.number,
                autofocus: true,
                decoration: InputDecoration(
                    hintText: 'قيمة الخصم',
                    suffixText: type == 'percent' ? '%' : 'ج'),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  final v = double.tryParse(ctrl.text.trim()) ?? 0;
                  setState(() {
                    _extraDiscount = v;
                    _extraDiscountType = type;
                  });
                  Navigator.pop(ctx);
                },
                child: const Text('تطبيق'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
