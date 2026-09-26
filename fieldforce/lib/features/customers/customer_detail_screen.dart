import 'dart:io';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../orders/order_repository.dart';
import '../transactions/transaction_repository.dart';
import 'customer_repository.dart';

/// شاشة تفاصيل العميل — بياناته + إجراءات سريعة + سجل الفواتير.
class CustomerDetailScreen extends StatelessWidget {
  final Customer customer;
  const CustomerDetailScreen({super.key, required this.customer});

  @override
  Widget build(BuildContext context) {
    final c = customer;
    final hasBalance = c.balance > 0;
    final balanceText = _money(c.balance);
    return Scaffold(
      appBar: AppBar(title: const Text('ملف العميل')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          children: [
            // رأس البطاقة
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 54,
                          height: 54,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: AppColors.primaryWash,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Text(c.name.characters.first,
                              style: const TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.primaryDeep)),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(c.name,
                                  style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w700)),
                              const SizedBox(height: 4),
                              Text(c.area,
                                  style: const TextStyle(
                                      color: AppColors.muted, fontSize: 12.5)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: _stat('الرصيد', balanceText,
                              hasBalance ? AppColors.danger : AppColors.good),
                        ),
                        Container(
                            width: 1, height: 34, color: AppColors.line),
                        const Expanded(
                            child: _StatColumn('آخر زيارة', 'اليوم')),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 14),

            // إجراءات سريعة
            const Text('إجراء سريع',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 10),
            Row(
              children: [
                _action(Icons.shopping_bag_outlined, 'بيع',
                    () => context.push('/sell', extra: customer.id)),
                const SizedBox(width: 10),
                _action(Icons.payments_outlined, 'تحصيل',
                    () => _collectDialog(context)),
                const SizedBox(width: 10),
                _action(Icons.location_on_outlined, 'زيارة',
                    () => context.push('/visit', extra: customer.id)),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                _action(Icons.card_giftcard_outlined, 'عيّنة',
                    () => context.push('/samples', extra: customer.id)),
                const SizedBox(width: 10),
                _action(Icons.volunteer_activism_outlined, 'تبرع',
                    () => context.push('/donations', extra: customer.id)),
                const SizedBox(width: 10),
                _action(Icons.description_outlined, 'الفواتير',
                    () => context.push('/invoices', extra: customer.id)),
              ],
            ),
            const SizedBox(height: 18),

            // بيانات التواصل
            const Text('بيانات التواصل',
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
                    _info(Icons.phone, 'الهاتف', c.mobile),
                    const Divider(height: 1, color: AppColors.line),
                    _info(Icons.location_on, 'المنطقة', c.area),
                    if (c.pharmacyName != null) ...[
                      const Divider(height: 1, color: AppColors.line),
                      _info(Icons.store_outlined, 'المحل', c.pharmacyName!),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 18),

            // سجل الفواتير
            const Text('آخر الفواتير',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 10),
            _RecentInvoices(customerId: c.id),
          ],
        ),
      ),
    );
  }

  /// تحصيل دفعة من العميل (add-balance) — يقلّل مديونيته.
  void _collectDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => _CollectSheet(customer: customer),
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');

  Widget _stat(String label, String value, Color color) {
    return Column(
      children: [
        Text(label,
            style: const TextStyle(color: AppColors.muted, fontSize: 12)),
        const SizedBox(height: 4),
        MoneyText(value, size: 17, color: color),
      ],
    );
  }

  Widget _action(IconData icon, String label, VoidCallback? onTap) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Column(
          children: [
            Container(
              height: 52,
              decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: AppColors.primaryDeep, size: 24),
            ),
            const SizedBox(height: 6),
            Text(label,
                style: const TextStyle(
                    fontSize: 12, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }

  Widget _info(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 13),
      child: Row(
        children: [
          Icon(icon, size: 19, color: AppColors.muted),
          const SizedBox(width: 12),
          Text(label,
              style: const TextStyle(color: AppColors.muted, fontSize: 13)),
          const Spacer(),
          Text(value,
              style: const TextStyle(
                  fontWeight: FontWeight.w600, fontSize: 13.5)),
        ],
      ),
    );
  }
}

/// آخر فواتير العميل — تُجلب حقيقياً من الخادم (GET /orders?customer_id).
class _RecentInvoices extends StatefulWidget {
  final int customerId;
  const _RecentInvoices({required this.customerId});

  @override
  State<_RecentInvoices> createState() => _RecentInvoicesState();
}

class _RecentInvoicesState extends State<_RecentInvoices> {
  final _repo = OrderRepository();
  late Future<List<Order>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<Order>> _load() async {
    // type=4 → فواتير البيع فقط، حتى لا تختلط بالمرتجعات والعيّنات.
    final res =
        await _repo.list(type: 4, customerId: widget.customerId, limit: 8);
    return res.items;
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');

  String _date(String? iso) {
    if (iso == null) return '';
    return iso.split('T').first;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Order>>(
      future: _future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Card(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(
                  child: CircularProgressIndicator(color: AppColors.primary)),
            ),
          );
        }
        final list = snap.data ?? [];
        if (list.isEmpty) {
          return const Card(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(
                  child: Text('لا توجد فواتير لهذا العميل',
                      style: TextStyle(color: AppColors.muted))),
            ),
          );
        }
        return Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 15),
            child: Column(
              children: [
                for (var i = 0; i < list.length; i++) ...[
                  if (i != 0)
                    const Divider(height: 1, color: AppColors.line),
                  _invoiceRow(list[i]),
                ],
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _invoiceRow(Order o) {
    final isReturn = o.isReturn;
    return AppListRow(
      thumb: '#${o.id}',
      thumbBg: isReturn ? AppColors.dangerWash : AppColors.primaryWash,
      thumbColor: isReturn ? AppColors.danger : AppColors.primaryDeep,
      name: 'فاتورة ${o.typeLabel}',
      sub: '${_date(o.createdAt)}'
          '${o.lines.isNotEmpty ? ' · ${o.lines.length} أصناف' : ''}',
      trailing: MoneyText(_money(o.amount),
          color: isReturn ? AppColors.danger : AppColors.ink),
    );
  }
}

/// نافذة تحصيل دفعة من العميل — POST /customers/add-balance.
class _CollectSheet extends StatefulWidget {
  final Customer customer;
  const _CollectSheet({required this.customer});

  @override
  State<_CollectSheet> createState() => _CollectSheetState();
}

class _CollectSheetState extends State<_CollectSheet> {
  final _custRepo = CustomerRepository();
  final _txRepo = TransactionRepository();
  final _amount = TextEditingController();
  final _invoice = TextEditingController(); // رقم الفاتورة (اختياري)
  final _note = TextEditingController(); // ملاحظة (اختياري)
  final _picker = ImagePicker();
  File? _receipt; // صورة الإيصال

  Account? _account; // الحساب الافتراضي (يُحدَّد تلقائياً — مخفي عن الواجهة)
  bool _loading = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _amount.dispose();
    _invoice.dispose();
    _note.dispose();
    super.dispose();
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

  Future<void> _load() async {
    try {
      final accs = await _txRepo.accounts();
      if (!mounted) return;
      setState(() {
        _account = accs.isNotEmpty ? accs.first : null;
      });
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submit() async {
    final amount = double.tryParse(_amount.text.trim());
    if (amount == null || amount <= 0 || _account == null) {
      _snack('أدخل مبلغاً وحساباً صحيحين', AppColors.danger);
      return;
    }
    setState(() => _saving = true);
    try {
      final now = DateTime.now();
      final date =
          '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
      final invoiceId = int.tryParse(_invoice.text.trim());
      final note = _note.text.trim();
      await _custRepo.addBalance(
        customerId: widget.customer.id,
        accountId: _account!.id,
        amount: amount,
        date: date,
        description: note.isNotEmpty ? note : 'تحصيل ميداني',
        orderId: invoiceId,
        receiptPath: _receipt?.path,
      );
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('تم تسجيل التحصيل'),
        backgroundColor: AppColors.primaryDeep,
      ));
    } on ApiException catch (e) {
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
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Text('تحصيل من ${widget.customer.name}',
              style: const TextStyle(
                  fontSize: 17, fontWeight: FontWeight.w700)),
          const SizedBox(height: 4),
          Text('المديونية الحالية: ${_money(widget.customer.balance)} ج',
              style: const TextStyle(color: AppColors.muted, fontSize: 13)),
          const SizedBox(height: 18),
          if (_loading)
            const Padding(
              padding: EdgeInsets.all(20),
              child: Center(
                  child:
                      CircularProgressIndicator(color: AppColors.primary)),
            )
          else ...[
            // المبلغ المحصّل
            TextField(
              controller: _amount,
              keyboardType: TextInputType.number,
              autofocus: true,
              decoration: const InputDecoration(
                labelText: 'المبلغ المحصّل',
                hintText: '0.00',
                suffixText: 'ج.م',
                prefixIcon: Icon(Icons.payments_outlined),
              ),
            ),
            const SizedBox(height: 12),
            // رقم الفاتورة (اختياري)
            TextField(
              controller: _invoice,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'رقم الفاتورة (اختياري)',
                hintText: 'مثال: 1523',
                prefixIcon: Icon(Icons.tag_rounded),
              ),
            ),
            const SizedBox(height: 12),
            // ملاحظة (اختياري)
            TextField(
              controller: _note,
              minLines: 1,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'ملاحظة (اختياري)',
                hintText: 'أي تفاصيل عن التحصيل...',
                prefixIcon: Icon(Icons.edit_note_outlined),
              ),
            ),
            const SizedBox(height: 12),
            // صورة الإيصال
            _receiptRow(),
            const SizedBox(height: 18),
            ElevatedButton(
              onPressed: _saving ? null : _submit,
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
              color: has ? AppColors.primary : AppColors.line),
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

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}

class _StatColumn extends StatelessWidget {
  final String label;
  final String value;
  const _StatColumn(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(label,
            style: const TextStyle(color: AppColors.muted, fontSize: 12)),
        const SizedBox(height: 4),
        Text(value,
            style:
                const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
      ],
    );
  }
}
