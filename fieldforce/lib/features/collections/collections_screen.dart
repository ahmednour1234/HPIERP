import 'dart:io';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../transactions/transaction_repository.dart';
import '../transactions/deposit_repository.dart';

/// صندوق رفع صورة بحدود منقّطة (مُصدَّر للاستخدام في شاشات أخرى).
class DottedBox extends StatelessWidget {
  final Widget child;
  const DottedBox({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 22),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.line, width: 1.4),
      ),
      child: child,
    );
  }
}

/// شاشة التحصيلات = توريد الكاش (POST /deposits).
///
/// المندوب يورّد عُهدته لحساب الشركة؛ التوريد يبقى معلّقاً حتى موافقة الأدمن.
/// (التحصيل من عميل محدّد يتم من ملف العميل عبر add-balance.)
class CollectionsScreen extends StatefulWidget {
  const CollectionsScreen({super.key});

  @override
  State<CollectionsScreen> createState() => _CollectionsScreenState();
}

class _CollectionsScreenState extends State<CollectionsScreen> {
  final _txRepo = TransactionRepository();
  final _depRepo = DepositRepository();
  final _amount = TextEditingController();
  final _note = TextEditingController();

  List<Account> _accounts = [];
  Account? _selected;
  bool _loading = true;
  bool _saving = false;
  late Future<List<Deposit>> _deposits;
  File? _receipt;
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _load();
    _deposits = _depRepo.list();
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
        _accounts = accs;
        _selected = accs.isNotEmpty ? accs.first : null;
      });
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submit() async {
    final amount = double.tryParse(_amount.text.trim());
    if (_selected == null) {
      _snack('اختر الحساب أولاً', AppColors.danger);
      return;
    }
    if (amount == null || amount <= 0) {
      _snack('أدخل مبلغاً صحيحاً', AppColors.danger);
      return;
    }
    setState(() => _saving = true);
    try {
      await _depRepo.submit(
        accountId: _selected!.id,
        amount: amount,
        note: _note.text.trim().isEmpty ? null : _note.text.trim(),
        receiptPath: _receipt?.path,
      );
      if (!mounted) return;
      _snack('تم إرسال التوريد — بانتظار الموافقة', AppColors.primaryDeep);
      _amount.clear();
      _note.clear();
      setState(() {
        _receipt = null;
        _deposits = _depRepo.list();
      });
    } on ApiException catch (e) {
      _snack(e.message, AppColors.danger);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
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

  Widget _receiptBox() {
    return GestureDetector(
      onTap: _pickReceipt,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
              color: _receipt != null ? AppColors.primary : AppColors.line,
              width: 1.4),
        ),
        child: _receipt != null
            ? Column(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.file(_receipt!,
                        height: 120, width: double.infinity, fit: BoxFit.cover),
                  ),
                  const SizedBox(height: 8),
                  TextButton.icon(
                    onPressed: () => setState(() => _receipt = null),
                    icon: const Icon(Icons.close, size: 18),
                    label: const Text('إزالة الصورة'),
                    style: TextButton.styleFrom(
                        foregroundColor: AppColors.danger),
                  ),
                ],
              )
            : const Column(
                children: [
                  Icon(Icons.add_a_photo_outlined,
                      size: 26, color: AppColors.muted),
                  SizedBox(height: 8),
                  Text('صوّر أو ارفع صورة الإيصال',
                      style: TextStyle(fontSize: 13, color: AppColors.muted)),
                ],
              ),
      ),
    );
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
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
          children: [
            const ScreenHeader(
              icon: Icons.payments_outlined,
              title: 'المديونية',
              subtitle: 'ورّد المبالغ لحساب الشركة',
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                _shortcut(Icons.description_outlined, 'الفواتير', '/invoices'),
                const SizedBox(width: 12),
                _shortcut(Icons.account_balance_wallet_outlined,
                    'ملخّص المديونية', '/funds'),
              ],
            ),
            const SizedBox(height: 18),
            const Text('الحساب',
                style: TextStyle(
                    fontSize: 12.5,
                    color: AppColors.inkSoft,
                    fontWeight: FontWeight.w500)),
            const SizedBox(height: 6),
            _accountPicker(),
            const SizedBox(height: 14),
            LabeledField(
              label: 'المبلغ المورّد',
              hint: '0.00',
              controller: _amount,
              keyboardType: TextInputType.number,
              suffix: const Padding(
                padding: EdgeInsets.only(left: 14, top: 16),
                child: Text('ج.م',
                    style: TextStyle(color: AppColors.muted, fontSize: 13)),
              ),
            ),
            const SizedBox(height: 14),
            LabeledField(
                label: 'ملاحظات', hint: 'ملاحظات (اختياري)…', controller: _note),
            const SizedBox(height: 14),
            const Text('صورة الإيصال',
                style: TextStyle(
                    fontSize: 12.5,
                    color: AppColors.inkSoft,
                    fontWeight: FontWeight.w500)),
            const SizedBox(height: 6),
            _receiptBox(),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _saving ? null : _submit,
              child: _saving
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.4, color: Colors.white))
                  : const Text('إرسال التوريد'),
            ),
            const SizedBox(height: 22),
            const Text('توريداتك',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 10),
            _depositsList(),
          ],
        ),
      ),
    );
  }

  Widget _depositsList() {
    return FutureBuilder<List<Deposit>>(
      future: _deposits,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Padding(
            padding: EdgeInsets.all(16),
            child: Center(
                child: CircularProgressIndicator(color: AppColors.primary)),
          );
        }
        final list = snap.data ?? [];
        if (list.isEmpty) {
          return const DottedBox(
            child: Text('لا توجد توريدات بعد',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.muted)),
          );
        }
        return Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 15),
            child: Column(
              children: [
                for (var i = 0; i < list.length; i++) ...[
                  if (i != 0) const Divider(height: 1, color: AppColors.line),
                  _depositRow(list[i]),
                ],
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _depositRow(Deposit d) {
    final badge = switch (d.status) {
      1 => StatusBadge.good('مقبول'),
      2 => StatusBadge.danger('مرفوض'),
      _ => StatusBadge.warn('معلّق'),
    };
    return AppListRow(
      thumb: '↑',
      name: '${_money(d.amount)} ج',
      sub: '${d.accountName} · ${d.statusText}',
      trailing: badge,
    );
  }

  Widget _accountPicker() {
    if (_loading) {
      return const DottedBox(
        child: Center(
            child: SizedBox(
                height: 20,
                width: 20,
                child: CircularProgressIndicator(
                    strokeWidth: 2, color: AppColors.primary))),
      );
    }
    if (_accounts.isEmpty) {
      return const DottedBox(
        child: Text('لا توجد حسابات',
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.muted)),
      );
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: AppColors.line),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<Account>(
          isExpanded: true,
          value: _selected,
          items: _accounts
              .map((a) => DropdownMenuItem(
                    value: a,
                    child: Text(a.name, style: const TextStyle(fontSize: 14)),
                  ))
              .toList(),
          onChanged: (a) => setState(() => _selected = a),
        ),
      ),
    );
  }

  Widget _shortcut(IconData icon, String label, String route) {
    return Expanded(
      child: InkWell(
        onTap: () => context.push(route),
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.line),
          ),
          child: Column(
            children: [
              Icon(icon, color: AppColors.primaryDeep, size: 24),
              const SizedBox(height: 8),
              Text(label,
                  style: const TextStyle(
                      fontSize: 13, fontWeight: FontWeight.w600)),
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
