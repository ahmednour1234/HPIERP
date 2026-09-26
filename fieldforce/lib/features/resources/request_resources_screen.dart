import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../products/product_repository.dart';
import '../reservations/reservation_repository.dart';

/// شاشة طلب موارد (حجز) — المندوب يطلب كميات من الكتالوج.
/// تبويبان: طلب جديد + السجل.
class RequestResourcesScreen extends StatelessWidget {
  const RequestResourcesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('طلب موارد'),
          bottom: const TabBar(
            labelColor: AppColors.primaryDeep,
            unselectedLabelColor: AppColors.muted,
            indicatorColor: AppColors.primary,
            labelStyle:
                TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            tabs: [
              Tab(text: 'طلب جديد'),
              Tab(text: 'السجل'),
            ],
          ),
        ),
        body: const SafeArea(
          child: TabBarView(
            children: [_NewRequestTab(), _RequestsLog()],
          ),
        ),
      ),
    );
  }
}

/// تبويب طلب جديد — اختيار منتجات وكميات من الكتالوج.
class _NewRequestTab extends StatefulWidget {
  const _NewRequestTab();

  @override
  State<_NewRequestTab> createState() => _NewRequestTabState();
}

class _NewRequestTabState extends State<_NewRequestTab>
    with AutomaticKeepAliveClientMixin {
  final _productRepo = ProductRepository();
  final _resRepo = ReservationRepository();

  late Future<List<Product>> _future;
  final Map<int, num> _cart = {};
  final Map<int, Product> _byId = {};
  final _note = TextEditingController();
  String _search = '';
  Timer? _debounce;
  bool _saving = false;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _note.dispose();
    super.dispose();
  }

  Future<List<Product>> _load() async {
    // كتالوج فئات المندوب (/stocks بدون type) — مش الكتالوج كله.
    final res = await _productRepo.stocks(
        vanStock: false, search: _search, limit: 50);
    _byId.addEntries(res.items.map((p) => MapEntry(p.id, p)));
    return res.items;
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      _search = v;
      setState(() => _future = _load());
    });
  }

  int get _count => _cart.values.where((q) => q > 0).length;

  Future<void> _submit() async {
    if (_count == 0) {
      _snack('اختر منتجات وكميات أولاً', AppColors.danger);
      return;
    }
    setState(() => _saving = true);
    try {
      final r = await _resRepo.submit(_cart,
          note: _note.text.trim().isEmpty ? null : _note.text.trim());
      if (!mounted) return;
      _snack('تم إرسال طلب #${r.id} — بانتظار الموافقة', AppColors.primaryDeep);
      setState(() {
        _cart.clear();
        _note.clear();
      });
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
    super.build(context);
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
          child: Column(
            children: [
              const ScreenHeader(
                icon: Icons.inventory_2_outlined,
                title: 'طلب موارد',
                subtitle: 'اطلب أي كمية من المخزن الرئيسي',
              ),
              const SizedBox(height: 14),
              TextField(
                onChanged: _onSearch,
                decoration: const InputDecoration(
                  hintText: 'ابحث عن منتج…',
                  prefixIcon: Icon(Icons.search, color: AppColors.muted),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: FutureBuilder<List<Product>>(
            future: _future,
            builder: (context, snap) {
              if (snap.connectionState == ConnectionState.waiting) {
                return const Center(
                    child: CircularProgressIndicator(color: AppColors.primary));
              }
              if (snap.hasError) {
                final msg = snap.error is ApiException
                    ? (snap.error as ApiException).message
                    : 'تعذّر التحميل';
                return Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.cloud_off,
                          size: 48, color: AppColors.muted),
                      const SizedBox(height: 10),
                      Text(msg,
                          style: const TextStyle(color: AppColors.muted)),
                      const SizedBox(height: 14),
                      OutlinedButton(
                        onPressed: () => setState(() => _future = _load()),
                        child: const Text('إعادة المحاولة'),
                      ),
                    ],
                  ),
                );
              }
              final items = snap.data!;
              if (items.isEmpty) {
                return const Center(
                    child: Text('لا توجد منتجات',
                        style: TextStyle(color: AppColors.muted)));
              }
              return ListView.separated(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
                itemCount: items.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (context, i) => _productRow(items[i]),
              );
            },
          ),
        ),
        // شريط الإرسال
        Container(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
          decoration: BoxDecoration(
            color: AppColors.surface,
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.06),
                blurRadius: 16,
                offset: const Offset(0, -4),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (_count > 0) ...[
                TextField(
                  controller: _note,
                  decoration: const InputDecoration(
                    hintText: 'ملاحظة على الطلب (اختياري)…',
                    prefixIcon:
                        Icon(Icons.edit_note_rounded, color: AppColors.muted),
                  ),
                ),
                const SizedBox(height: 10),
              ],
              ElevatedButton.icon(
                onPressed: _saving || _count == 0 ? null : _submit,
                icon: _saving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2.2, color: Colors.white))
                    : const Icon(Icons.send_rounded, size: 20),
                label: Text(_count == 0
                    ? 'اختر منتجات للطلب'
                    : 'إرسال الطلب ($_count أصناف)'),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _productRow(Product p) {
    final qty = _cart[p.id] ?? 0;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(
            color: qty > 0 ? AppColors.primary : AppColors.line),
        boxShadow: AppColors.softShadow,
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: AppColors.primaryWash,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.medication_outlined,
                color: AppColors.primary, size: 21),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(p.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w600)),
                const SizedBox(height: 2),
                Text(p.code,
                    style:
                        const TextStyle(fontSize: 11.5, color: AppColors.muted)),
              ],
            ),
          ),
          // عدّاد الكمية + إدخال يدوي
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
                        color: qty > 0 ? AppColors.inkSoft : AppColors.muted),
                  ),
                ),
                GestureDetector(
                  onTap: () => _editQty(p),
                  child: SizedBox(
                    width: 36,
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
                    child: Icon(Icons.add, size: 18, color: AppColors.inkSoft),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// إدخال كمية يدوي (لأي كمية كبيرة).
  void _editQty(Product p) {
    final ctrl = TextEditingController(
        text: (_cart[p.id] ?? 0) > 0 ? '${_cart[p.id]}' : '');
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('كمية ${p.name}',
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        content: TextField(
          controller: ctrl,
          keyboardType: TextInputType.number,
          autofocus: true,
          decoration: const InputDecoration(hintText: 'أدخل الكمية'),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () {
              final v = int.tryParse(ctrl.text.trim()) ?? 0;
              setState(() {
                v > 0 ? _cart[p.id] = v : _cart.remove(p.id);
              });
              Navigator.pop(ctx);
            },
            child: const Text('تأكيد'),
          ),
        ],
      ),
    );
  }
}

/// تبويب السجل — طلبات الحجز السابقة.
class _RequestsLog extends StatefulWidget {
  const _RequestsLog();

  @override
  State<_RequestsLog> createState() => _RequestsLogState();
}

class _RequestsLogState extends State<_RequestsLog>
    with AutomaticKeepAliveClientMixin {
  final _repo = ReservationRepository();
  late Future<List<Reservation>> _future;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _repo.list();
  }

  Future<void> _refresh() async {
    setState(() => _future = _repo.list());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: FutureBuilder<List<Reservation>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
                child: CircularProgressIndicator(color: AppColors.primary));
          }
          final list = snap.data ?? [];
          if (list.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 120),
              Icon(Icons.inventory_2_outlined,
                  size: 48, color: AppColors.muted),
              SizedBox(height: 10),
              Center(
                  child: Text('لا توجد طلبات بعد',
                      style: TextStyle(color: AppColors.muted))),
            ]);
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
            itemCount: list.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) => _row(list[i]),
          );
        },
      ),
    );
  }

  Widget _row(Reservation r) {
    final badge = switch (r.statusText) {
      'approved' => StatusBadge.good('مقبول'),
      'rejected' => StatusBadge.danger('مرفوض'),
      _ => StatusBadge.warn('معلّق'),
    };
    return BrandCard(
      padding: const EdgeInsets.all(14),
      onTap: () => _showDetails(r),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                    color: AppColors.primaryWash,
                    borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.inventory_2_rounded,
                    color: AppColors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('طلب #${r.id}',
                        style: const TextStyle(
                            fontSize: 14, fontWeight: FontWeight.w700)),
                    Text('${r.itemsCount} أصناف · ${r.date}',
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.muted)),
                  ],
                ),
              ),
              badge,
            ],
          ),
          if (r.items.isNotEmpty) ...[
            const Divider(height: 18, color: AppColors.lineSoft),
            for (final it in r.items)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 3),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(it.productName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                              fontSize: 12.5, color: AppColors.inkSoft)),
                    ),
                    Text('×${it.quantity}',
                        style: const TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary)),
                  ],
                ),
              ),
          ],
          const SizedBox(height: 8),
          // زر عرض التفاصيل
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton.icon(
              onPressed: () => _showDetails(r),
              icon: const Icon(Icons.visibility_outlined, size: 17),
              label: const Text('عرض التفاصيل'),
              style: TextButton.styleFrom(
                foregroundColor: AppColors.primaryDeep,
                padding: const EdgeInsets.symmetric(horizontal: 8),
                visualDensity: VisualDensity.compact,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// تفاصيل الطلب في bottom sheet.
  void _showDetails(Reservation r) {
    final badge = switch (r.statusText) {
      'approved' => StatusBadge.good('مقبول'),
      'rejected' => StatusBadge.danger('مرفوض'),
      _ => StatusBadge.warn('معلّق'),
    };
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.surface,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 24),
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
              Row(
                children: [
                  Text('طلب #${r.id}',
                      style: const TextStyle(
                          fontSize: 17, fontWeight: FontWeight.w800)),
                  const Spacer(),
                  badge,
                ],
              ),
              const SizedBox(height: 4),
              Text('${r.itemsCount} أصناف · ${r.date}',
                  style:
                      const TextStyle(fontSize: 12.5, color: AppColors.muted)),
              const SizedBox(height: 16),
              const Text('الأصناف',
                  style: TextStyle(
                      fontSize: 13,
                      color: AppColors.muted,
                      fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              if (r.items.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 16),
                  child: Center(
                      child: Text('تفاصيل الأصناف غير متاحة لهذا الطلب',
                          style: TextStyle(color: AppColors.muted))),
                )
              else
                Flexible(
                  child: ListView(
                    shrinkWrap: true,
                    children: [
                      for (final it in r.items)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Row(
                            children: [
                              const Icon(Icons.medication_outlined,
                                  size: 18, color: AppColors.primaryDeep),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.start,
                                  children: [
                                    Text(it.productName,
                                        style: const TextStyle(
                                            fontSize: 13.5,
                                            fontWeight: FontWeight.w600)),
                                    if (it.productCode.isNotEmpty)
                                      Text(it.productCode,
                                          style: const TextStyle(
                                              fontSize: 11,
                                              color: AppColors.muted)),
                                  ],
                                ),
                              ),
                              Text('×${it.quantity}',
                                  style: const TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.primary)),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
