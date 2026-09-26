import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../orders/order_repository.dart';
import '../transactions/transaction_repository.dart';

/// تفاصيل الفاتورة — تحمّل الطلب من GET /orders/{id}.
class InvoiceDetailScreen extends StatefulWidget {
  final int orderId;
  const InvoiceDetailScreen({super.key, required this.orderId});

  @override
  State<InvoiceDetailScreen> createState() => _InvoiceDetailScreenState();
}

class _InvoiceDetailScreenState extends State<InvoiceDetailScreen> {
  final _repo = OrderRepository();
  late Future<Order> _future;

  @override
  void initState() {
    super.initState();
    _future = _repo.byId(widget.orderId);
  }

  void _reload() {
    setState(() {
      _future = _repo.byId(widget.orderId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('فاتورة #${widget.orderId}')),
      body: SafeArea(
        child: FutureBuilder<Order>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const Center(
                  child: CircularProgressIndicator(color: AppColors.primary));
            }
            if (snap.hasError) {
              final msg = snap.error is ApiException
                  ? (snap.error as ApiException).message
                  : 'تعذّر تحميل الفاتورة';
              return Center(
                  child: Text(msg,
                      style: const TextStyle(color: AppColors.muted)));
            }
            return _content(snap.data!);
          },
        ),
      ),
    );
  }

  Widget _content(Order o) {
    // الفرعي قبل الخصم = مجموع أسعار البنود (line_total)؛
    // fallback لـ (الإجمالي − الضريبة + الخصم) لو مفيش بنود.
    final subtotal = o.lines.isNotEmpty
        ? o.lines.fold<double>(0, (s, l) => s + l.lineTotal)
        : (o.amount - o.totalTax + _totalDiscount(o));
    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
            children: [
              Container(
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: AppColors.line),
                ),
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(11),
                                gradient: const LinearGradient(colors: [
                                  AppColors.primary,
                                  AppColors.primaryDeep
                                ]),
                              ),
                              child: const Icon(Icons.location_on_rounded,
                                  color: Colors.white, size: 22),
                            ),
                            const SizedBox(width: 10),
                            const Text('HPI',
                                style: TextStyle(
                                    fontSize: 17, fontWeight: FontWeight.w800)),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text('#${o.id}',
                                style: const TextStyle(
                                    fontWeight: FontWeight.w700, fontSize: 15)),
                            Text(_date(o.createdAt),
                                style: const TextStyle(
                                    color: AppColors.muted, fontSize: 12)),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const Divider(color: AppColors.line),
                    const SizedBox(height: 6),
                    _kv('العميل', o.customerName),
                    _kv('النوع', o.typeLabel),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      decoration: const BoxDecoration(
                        border: Border(
                          top: BorderSide(color: AppColors.line),
                          bottom: BorderSide(color: AppColors.line),
                        ),
                      ),
                      child: const Row(
                        children: [
                          Expanded(
                              flex: 4, child: Text('الصنف', style: _th)),
                          Expanded(
                              child: Text('كمية',
                                  textAlign: TextAlign.center, style: _th)),
                          Expanded(
                              flex: 2,
                              child: Text('إجمالي',
                                  textAlign: TextAlign.end, style: _th)),
                        ],
                      ),
                    ),
                    if (o.lines.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 16),
                        child: Text('لا توجد بنود',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: AppColors.muted)),
                      )
                    else
                      for (final l in o.lines) _lineRow(l),
                    const SizedBox(height: 10),
                    const Divider(color: AppColors.line),
                    _totalRow('الإجمالي الفرعي', _money(subtotal)),
                    if (_totalDiscount(o) > 0)
                      _totalRow(
                          subtotal > 0
                              ? 'الخصم (${((_totalDiscount(o) / subtotal) * 100).round()}%)'
                              : 'الخصم',
                          '- ${_money(_totalDiscount(o))}'),
                    _totalRow('الضريبة', _money(o.totalTax)),
                    const SizedBox(height: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: AppColors.primaryWash,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('الإجمالي',
                              style: TextStyle(
                                  fontWeight: FontWeight.w700, fontSize: 15)),
                          MoneyText(_money(o.amount),
                              size: 20, color: AppColors.primaryDeep),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        // شريط حالة التحصيل
        if (!o.isReturn && o.paymentStatus != 'paid')
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 20),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: AppColors.warnWash,
              borderRadius: BorderRadius.circular(13),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _payCell('محصّل', _money(o.collectedCash), AppColors.good),
                _payCell('متبقّي', _money(o.remaining), AppColors.danger),
              ],
            ),
          ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 16),
          child: Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('جارٍ الطباعة…')),
                  ),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primaryDeep,
                    side: const BorderSide(color: AppColors.primary),
                    minimumSize: const Size.fromHeight(52),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14)),
                  ),
                  icon: const Icon(Icons.print_rounded, size: 20),
                  label: const Text('طباعة'),
                ),
              ),
              if (!o.isReturn && o.paymentStatus != 'paid') ...[
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _collect(context, o),
                    style: ElevatedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52)),
                    icon: const Icon(Icons.payments_outlined, size: 20),
                    label: const Text('تحصيل'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _payCell(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: const TextStyle(fontSize: 11, color: AppColors.muted)),
        const SizedBox(height: 2),
        MoneyText(value, size: 16, color: color),
      ],
    );
  }

  Future<void> _collect(BuildContext context, Order o) async {
    final done = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => CollectInvoiceSheet(order: o),
    );
    if (done == true) _reload(); // أعد تحميل الفاتورة
  }

  static const _th = TextStyle(
      fontSize: 12, color: AppColors.muted, fontWeight: FontWeight.w600);

  Widget _kv(String k, String v) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(k,
                style: const TextStyle(color: AppColors.muted, fontSize: 13)),
            Text(v,
                style: const TextStyle(
                    fontWeight: FontWeight.w600, fontSize: 13.5)),
          ],
        ),
      );

  Widget _lineRow(OrderLine l) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 9),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
                flex: 4,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(l.productName,
                        style: const TextStyle(
                            fontSize: 13.5, fontWeight: FontWeight.w500)),
                    if (l.hasDiscount)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(
                          'خصم ${_money(l.discount)}${l.discountType == 'percent' ? '%' : ' ج'}',
                          style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: AppColors.good),
                        ),
                      ),
                  ],
                )),
            Expanded(
                child: Text('${l.quantity}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 13))),
            Expanded(
                flex: 2,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    if (l.hasDiscount)
                      Text(_money(l.lineTotal),
                          style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.muted,
                              decoration: TextDecoration.lineThrough,
                              fontFeatures: [FontFeature.tabularFigures()])),
                    Text(_money(l.netLineTotal),
                        textAlign: TextAlign.end,
                        style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            fontFeatures: [FontFeature.tabularFigures()])),
                  ],
                )),
          ],
        ),
      );

  Widget _totalRow(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label,
                style: const TextStyle(color: AppColors.muted, fontSize: 13)),
            MoneyText(value, size: 13, color: AppColors.inkSoft),
          ],
        ),
      );

  double _totalDiscount(Order o) {
    double t = o.extraDiscount; // الخصم الإجمالي على الطلب
    for (final l in o.lines) {
      t += l.lineTotal - l.netLineTotal; // + خصومات السطور
    }
    return t;
  }

  String _date(String? iso) {
    if (iso == null) return '';
    return iso.split('T').first;
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}

/// نافذة تحصيل ضد فاتورة — POST /customers/add-balance مع order_id وصورة.
class CollectInvoiceSheet extends StatefulWidget {
  final Order order;
  const CollectInvoiceSheet({super.key, required this.order});

  @override
  State<CollectInvoiceSheet> createState() => _CollectInvoiceSheetState();
}

class _CollectInvoiceSheetState extends State<CollectInvoiceSheet> {
  final _txRepo = TransactionRepository();
  final _orderRepo = OrderRepository();
  final _picker = ImagePicker();
  late final TextEditingController _amount;
  final _note = TextEditingController();

  Account? _account;
  File? _receipt;
  bool _loading = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    // المبلغ يبدأ بالمتبقّي على الفاتورة
    _amount = TextEditingController(
        text: widget.order.remaining.toStringAsFixed(
            widget.order.remaining == widget.order.remaining.roundToDouble()
                ? 0
                : 2));
    _load();
  }

  @override
  void dispose() {
    _amount.dispose();
    _note.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final accs = await _txRepo.accounts();
      if (!mounted) return;
      setState(() {
        // اختيار حساب الكاش تلقائياً (يفضّل اللي فيه "cash").
        _account = accs.isEmpty
            ? null
            : accs.firstWhere(
                (a) => a.name.toLowerCase().contains('cash'),
                orElse: () => accs.first,
              );
      });
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pickReceipt() async {
    final x = await _picker.pickImage(source: ImageSource.camera, maxWidth: 1600);
    if (x != null && mounted) setState(() => _receipt = File(x.path));
  }

  Future<void> _submit() async {
    final amount = double.tryParse(_amount.text.trim());
    if (_account == null) {
      _snack('لا يوجد حساب تحصيل متاح');
      return;
    }
    if (amount == null || amount <= 0) {
      _snack('أدخل مبلغاً صحيحاً');
      return;
    }
    setState(() => _saving = true);
    try {
      final now = DateTime.now();
      final date =
          '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
      await _orderRepo.collect(
        orderId: widget.order.id,
        amount: amount,
        accountId: _account!.id,
        date: date,
        note: _note.text.trim().isEmpty ? null : _note.text.trim(),
        receiptPath: _receipt?.path,
      );
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _snack(String m) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(m), backgroundColor: AppColors.danger));
  }

  @override
  Widget build(BuildContext context) {
    final o = widget.order;
    return Padding(
      padding: EdgeInsets.fromLTRB(
          20, 18, 20, MediaQuery.of(context).viewInsets.bottom + 20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                  color: AppColors.line,
                  borderRadius: BorderRadius.circular(2)),
            ),
          ),
          const SizedBox(height: 16),
          Text('تحصيل فاتورة #${o.id}',
              style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
          const SizedBox(height: 4),
          Text('${o.customerName} · متبقّي ${_fmt(o.remaining)} ج',
              style: const TextStyle(color: AppColors.muted, fontSize: 13)),
          const SizedBox(height: 18),
          if (_loading)
            const Padding(
              padding: EdgeInsets.all(20),
              child:
                  Center(child: CircularProgressIndicator(color: AppColors.primary)),
            )
          else ...[
            TextField(
              controller: _amount,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                  hintText: 'المبلغ المحصّل', suffixText: 'ج.م'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _note,
              decoration: const InputDecoration(hintText: 'ملاحظة (اختياري)…'),
            ),
            const SizedBox(height: 12),
            GestureDetector(
              onTap: _pickReceipt,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(13),
                  border: Border.all(
                      color: _receipt != null
                          ? AppColors.primary
                          : AppColors.line),
                ),
                child: Row(
                  children: [
                    if (_receipt != null)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.file(_receipt!,
                            width: 36, height: 36, fit: BoxFit.cover),
                      )
                    else
                      const Icon(Icons.camera_alt_outlined,
                          color: AppColors.muted, size: 22),
                    const SizedBox(width: 12),
                    Text(
                        _receipt != null
                            ? 'تم إرفاق الإيصال'
                            : 'إرفاق صورة الإيصال',
                        style: const TextStyle(
                            fontSize: 13.5, fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 18),
            ElevatedButton(
              onPressed: _saving ? null : _submit,
              style: ElevatedButton.styleFrom(
                  minimumSize: const Size.fromHeight(52)),
              child: _saving
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.4, color: Colors.white))
                  : const Text('تأكيد التحصيل'),
            ),
          ],
        ],
      ),
    );
  }

  String _fmt(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
