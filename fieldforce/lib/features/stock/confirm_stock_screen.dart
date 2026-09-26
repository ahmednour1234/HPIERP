import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../products/product_repository.dart';
import 'stock_repository.dart';

/// شاشة صرف البضاعة — المندوب يختار من منتجات عربيته الكميات الراجعة
/// للمخزن، يرسل الطلب للأدمن، وينتظر الموافقة (POST /stocks/confirm).
class ConfirmStockScreen extends StatefulWidget {
  const ConfirmStockScreen({super.key});

  @override
  State<ConfirmStockScreen> createState() => _ConfirmStockScreenState();
}

class _ConfirmStockScreenState extends State<ConfirmStockScreen> {
  final _repo = StockRepository();
  final _productRepo = ProductRepository();
  final _noteCtrl = TextEditingController();

  /// منتجات العربية + الكمية المختارة للإرجاع لكل منتج.
  final List<Product> _stock = [];
  // الكميات المرتجعة أعداد صحيحة — الخادم يشترط integer min 1.
  final Map<int, int> _picked = {};
  final Map<int, TextEditingController> _qtyCtrls = {};

  SettlementRequest? _request; // الطلب المعلّق/المُراجَع الحالي
  String _search = '';
  bool _loading = true;
  bool _saving = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _noteCtrl.dispose();
    for (final c in _qtyCtrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      // الطلب الحالي أولاً — لو فيه طلب معلّق نعرض حالته بدل الفورم.
      final req = await _repo.currentRequest();
      if (!mounted) return;
      if (req != null && req.isPending) {
        setState(() {
          _request = req;
          _loading = false;
        });
        return;
      }
      final res = await _productRepo.stocks(vanStock: true, limit: 200);
      if (!mounted) return;
      setState(() {
        _request = req; // قد يكون موافَق/مرفوض — يُعرض كشريط أعلى الفورم
        _stock
          ..clear()
          ..addAll(res.items.where((p) => p.quantity > 0));
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

  List<Product> get _visible {
    if (_search.isEmpty) return _stock;
    final q = _search.toLowerCase();
    return _stock
        .where((p) =>
            p.name.toLowerCase().contains(q) ||
            p.code.toLowerCase().contains(q))
        .toList();
  }

  int get _selectedCount => _picked.values.where((q) => q > 0).length;
  int get _selectedQty => _picked.values.fold<int>(0, (s, q) => s + q);

  TextEditingController _ctrlFor(Product p) => _qtyCtrls.putIfAbsent(
      p.id,
      () => TextEditingController(
          text: (_picked[p.id] ?? 0) > 0 ? '${_picked[p.id]}' : ''));

  /// أقصى كمية مرتجعة للصنف — عدد صحيح لا يتجاوز المتاح في العربية.
  int _maxOf(Product p) => p.quantity.floor();

  void _setQty(Product p, int qty) {
    final max = _maxOf(p);
    final clamped = qty < 0 ? 0 : (qty > max ? max : qty);
    setState(() {
      if (clamped == 0) {
        _picked.remove(p.id);
      } else {
        _picked[p.id] = clamped;
      }
    });
    final c = _ctrlFor(p);
    final text = clamped == 0 ? '' : '$clamped';
    if (c.text != text) {
      c.value = TextEditingValue(
          text: text, selection: TextSelection.collapsed(offset: text.length));
    }
  }

  void _selectAll() {
    setState(() {
      for (final p in _stock) {
        final max = _maxOf(p);
        if (max > 0) _picked[p.id] = max;
      }
    });
    for (final p in _stock) {
      final text = '${_maxOf(p)}';
      _ctrlFor(p).value = TextEditingValue(
          text: text, selection: TextSelection.collapsed(offset: text.length));
    }
  }

  void _clearAll() {
    setState(() => _picked.clear());
    for (final c in _qtyCtrls.values) {
      c.clear();
    }
  }

  Future<void> _submit() async {
    if (_selectedCount == 0) return;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('إرسال طلب صرف البضاعة',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        content: Text(
            'سيتم إرسال $_selectedCount صنف (إجمالي $_selectedQty وحدة) '
            'إلى الأدمن للمراجعة. لن تُخصم من عربتك إلا بعد الموافقة.',
            style: const TextStyle(fontSize: 13.5, height: 1.6)),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('إرسال الطلب'),
          ),
        ],
      ),
    );
    if (ok != true) return;

    setState(() => _saving = true);
    try {
      final lines = <SettlementLine>[];
      for (final p in _stock) {
        final q = _picked[p.id] ?? 0;
        if (q > 0) {
          lines.add(SettlementLine(
              productId: p.id, name: p.name, code: p.code, quantity: q));
        }
      }
      final req = await _repo.submit(lines: lines, note: _noteCtrl.text);
      if (!mounted) return;
      setState(() {
        _request = req;
        _picked.clear();
        _noteCtrl.clear();
      });
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('تم إرسال الطلب — بانتظار موافقة الأدمن'),
        backgroundColor: AppColors.primaryDeep,
      ));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(e.message), backgroundColor: AppColors.danger));
      // 409 = فيه طلب معلّق بالفعل، 422 = الكميات تغيّرت. الحالتان تتطلبان
      // إعادة تحميل لعرض الواقع الحالي من الخادم.
      if (e.statusCode == 409 || e.statusCode == 422) {
        setState(() => _saving = false);
        await _load();
        return;
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _cancelRequest(SettlementRequest req) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('إلغاء الطلب',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        content: const Text('سيتم سحب الطلب قبل مراجعة الأدمن. متأكد؟',
            style: TextStyle(fontSize: 13.5, height: 1.6)),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('تراجع')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('إلغاء الطلب'),
          ),
        ],
      ),
    );
    if (ok != true) return;
    setState(() => _saving = true);
    try {
      await _repo.cancel(req.id);
      if (!mounted) return;
      setState(() => _request = null);
      await _load();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(e.message), backgroundColor: AppColors.danger));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final pending = _request?.isPending == true;
    return Scaffold(
      appBar: AppBar(
        title: const Text('صرف البضاعة'),
        actions: [
          IconButton(
            onPressed: _loading ? null : _load,
            icon: const Icon(Icons.refresh),
            tooltip: 'تحديث',
          ),
        ],
      ),
      bottomNavigationBar:
          (_loading || pending || _error != null) ? null : _submitBar(),
      body: SafeArea(
        child: RefreshIndicator(onRefresh: _load, child: _buildBody()),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      final e = _error;
      return ListView(
        padding: const EdgeInsets.all(24),
        children: [
          const SizedBox(height: 60),
          const Icon(Icons.cloud_off_outlined,
              size: 44, color: AppColors.muted),
          const SizedBox(height: 12),
          Text(
            e is ApiException ? e.message : 'تعذّر تحميل البيانات',
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 13.5, color: AppColors.inkSoft),
          ),
          const SizedBox(height: 16),
          OutlinedButton(
              onPressed: _load, child: const Text('إعادة المحاولة')),
        ],
      );
    }
    if (_request?.isPending == true) {
      return _pendingView(_request!);
    }
    return _pickerView();
  }

  // ---------- حالة: بانتظار الموافقة ----------

  Widget _pendingView(SettlementRequest req) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
      children: [
        ScreenHeader(
          icon: Icons.hourglass_top_outlined,
          // الخادم يرسل نصاً جاهزاً للحالة؛ نستخدمه إن وُجد.
          title: req.statusText ?? 'بانتظار موافقة الأدمن',
          subtitle: 'طلب صرف البضاعة تحت المراجعة',
        ),
        const SizedBox(height: 18),
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            gradient: AppColors.brandGradient,
            borderRadius: BorderRadius.circular(22),
            boxShadow: AppColors.brandShadow,
          ),
          child: Column(
            children: [
              const Icon(Icons.pending_actions_rounded,
                  color: Colors.white, size: 40),
              const SizedBox(height: 10),
              Text('طلب رقم #${req.id}',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 17,
                      fontWeight: FontWeight.w800)),
              const SizedBox(height: 4),
              Text(
                  '${req.lines.length} صنف · ${req.totalQuantity} وحدة',
                  style:
                      const TextStyle(color: Colors.white70, fontSize: 12.5)),
              if (req.createdAt != null) ...[
                const SizedBox(height: 2),
                Text(req.createdAt!,
                    style: const TextStyle(
                        color: Colors.white60, fontSize: 11.5)),
              ],
            ],
          ),
        ),
        const SizedBox(height: 16),
        _infoCard(
          'لا يمكن إرسال طلب جديد قبل أن يراجع الأدمن هذا الطلب. '
          'الكميات ما زالت في عربتك ولم تُخصم بعد.',
        ),
        if (req.note != null && req.note!.trim().isNotEmpty) ...[
          const SizedBox(height: 16),
          _sectionLabel('ملاحظتك'),
          const SizedBox(height: 8),
          BrandCard(
            child: Text(req.note!,
                style: const TextStyle(fontSize: 13, height: 1.6)),
          ),
        ],
        const SizedBox(height: 16),
        _sectionLabel('الأصناف المرسَلة'),
        const SizedBox(height: 8),
        BrandCard(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          child: Column(
            children: [
              for (int i = 0; i < req.lines.length; i++) ...[
                if (i > 0) const Divider(height: 1, color: AppColors.lineSoft),
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(req.lines[i].name,
                                style: const TextStyle(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w600)),
                            if (req.lines[i].code.isNotEmpty)
                              Text(req.lines[i].code,
                                  style: const TextStyle(
                                      fontSize: 11.5,
                                      color: AppColors.muted)),
                          ],
                        ),
                      ),
                      Text('${req.lines[i].quantity}',
                          style: const TextStyle(
                              fontSize: 14.5, fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 22),
        OutlinedButton.icon(
          onPressed: _saving ? null : () => _cancelRequest(req),
          style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger),
          icon: const Icon(Icons.close, size: 18),
          label: const Text('إلغاء الطلب'),
        ),
      ],
    );
  }

  // ---------- حالة: اختيار المنتجات ----------

  Widget _pickerView() {
    final items = _visible;
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
      children: [
        const ScreenHeader(
          icon: Icons.local_shipping_outlined,
          title: 'صرف البضاعة',
          subtitle: 'اختر المرتجع من عربتك وأرسله للأدمن',
        ),
        const SizedBox(height: 16),
        if (_request != null && !_request!.isPending)
          _reviewedBanner(_request!),
        _infoCard(
          'حدّد المنتجات والكميات الراجعة للمخزن. الطلب يُرسل للأدمن '
          'للمراجعة، ولا يُخصم من عربتك إلا بعد الموافقة.',
        ),
        const SizedBox(height: 16),
        TextField(
          onChanged: (v) => setState(() => _search = v.trim()),
          decoration: const InputDecoration(
            hintText: 'ابحث باسم المنتج أو الكود…',
            prefixIcon: Icon(Icons.search),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Text('${items.length} صنف في العربية',
                style: const TextStyle(
                    fontSize: 12.5,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const Spacer(),
            TextButton(
                onPressed: _stock.isEmpty ? null : _selectAll,
                child: const Text('تحديد الكل')),
            TextButton(
                onPressed: _picked.isEmpty ? null : _clearAll,
                child: const Text('مسح')),
          ],
        ),
        const SizedBox(height: 4),
        if (items.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 40),
            child: Column(
              children: [
                const Icon(Icons.inventory_2_outlined,
                    size: 40, color: AppColors.muted),
                const SizedBox(height: 10),
                Text(
                    _stock.isEmpty
                        ? 'لا توجد بضاعة في عربيتك حالياً'
                        : 'لا توجد نتائج للبحث',
                    style:
                        const TextStyle(fontSize: 13, color: AppColors.muted)),
              ],
            ),
          )
        else
          ...items.map(_productTile),
        const SizedBox(height: 18),
        _sectionLabel('ملاحظة (اختياري)'),
        const SizedBox(height: 8),
        TextField(
          controller: _noteCtrl,
          minLines: 2,
          maxLines: 4,
          decoration: const InputDecoration(
            hintText: 'أي ملاحظة للأدمن على المرتجع...',
            prefixIcon: Icon(Icons.edit_note_outlined),
          ),
        ),
      ],
    );
  }

  Widget _productTile(Product p) {
    final qty = _picked[p.id] ?? 0;
    final selected = qty > 0;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.fromLTRB(14, 10, 10, 10),
      decoration: BoxDecoration(
        color: selected ? AppColors.primaryWash : AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
            color: selected ? AppColors.primaryLight : AppColors.line),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(p.name,
                    style: const TextStyle(
                        fontSize: 13.5, fontWeight: FontWeight.w600)),
                const SizedBox(height: 3),
                Row(
                  children: [
                    if (p.code.isNotEmpty)
                      Text('${p.code} · ',
                          style: const TextStyle(
                              fontSize: 11.5, color: AppColors.muted)),
                    Text('المتاح ${_maxOf(p)}',
                        style: const TextStyle(
                            fontSize: 11.5, color: AppColors.muted)),
                  ],
                ),
              ],
            ),
          ),
          _stepper(p, qty),
        ],
      ),
    );
  }

  Widget _stepper(Product p, int qty) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        _stepBtn(Icons.remove, qty > 0 ? () => _setQty(p, qty - 1) : null),
        SizedBox(
          width: 54,
          child: TextField(
            controller: _ctrlFor(p),
            textAlign: TextAlign.center,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: const InputDecoration(
              hintText: '0',
              isDense: true,
              contentPadding: EdgeInsets.symmetric(vertical: 10),
            ),
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
            onChanged: (v) {
              final parsed = int.tryParse(v) ?? 0;
              if (parsed > _maxOf(p)) {
                _setQty(p, _maxOf(p)); // لا يتجاوز المتاح في العربية
                return;
              }
              setState(() {
                if (parsed <= 0) {
                  _picked.remove(p.id);
                } else {
                  _picked[p.id] = parsed;
                }
              });
            },
          ),
        ),
        _stepBtn(
            Icons.add, qty < _maxOf(p) ? () => _setQty(p, qty + 1) : null),
      ],
    );
  }

  Widget _stepBtn(IconData icon, VoidCallback? onTap) {
    final enabled = onTap != null;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        width: 34,
        height: 34,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: enabled ? AppColors.surface : AppColors.lineSoft,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.line),
        ),
        child: Icon(icon,
            size: 17, color: enabled ? AppColors.primary : AppColors.muted),
      ),
    );
  }

  Widget _submitBar() {
    final enabled = _selectedCount > 0 && !_saving;
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(top: BorderSide(color: AppColors.line)),
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('$_selectedCount صنف',
                      style: const TextStyle(
                          fontSize: 14, fontWeight: FontWeight.w700)),
                  Text('إجمالي $_selectedQty وحدة',
                      style: const TextStyle(
                          fontSize: 11.5, color: AppColors.muted)),
                ],
              ),
            ),
            ElevatedButton.icon(
              onPressed: enabled ? _submit : null,
              icon: _saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.2, color: Colors.white))
                  : const Icon(Icons.send_outlined, size: 18),
              label: const Text('إرسال للأدمن'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _reviewedBanner(SettlementRequest req) {
    final approved = req.status == SettlementStatus.approved;
    final color = approved ? AppColors.good : AppColors.danger;
    final wash = approved ? AppColors.goodWash : AppColors.dangerWash;
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: wash,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(approved ? Icons.check_circle_outline : Icons.cancel_outlined,
              color: color, size: 22),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                    approved
                        ? 'آخر طلب (#${req.id}) تمت الموافقة عليه'
                        : 'آخر طلب (#${req.id}) مرفوض',
                    style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13.5,
                        color: color)),
                if (req.adminNote != null &&
                    req.adminNote!.trim().isNotEmpty) ...[
                  const SizedBox(height: 5),
                  Text(req.adminNote!,
                      style: const TextStyle(
                          fontSize: 12.5,
                          color: AppColors.inkSoft,
                          height: 1.5)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoCard(String text) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.warnWash,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFF0C674)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.info_outline, color: Color(0xFFB4740B), size: 22),
          const SizedBox(width: 10),
          Expanded(
            child: Text(text,
                style: const TextStyle(
                    fontSize: 12.5, color: Color(0xFF8A5A0B), height: 1.6)),
          ),
        ],
      ),
    );
  }

  Widget _sectionLabel(String text) => Text(text,
      style: const TextStyle(
          fontSize: 13, color: AppColors.muted, fontWeight: FontWeight.w600));

}
