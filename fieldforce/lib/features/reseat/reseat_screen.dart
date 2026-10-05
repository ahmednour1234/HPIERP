import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../orders/order_repository.dart';
import '../products/product_repository.dart';
import '../customers/customer_repository.dart';
import '../customers/customer_picker.dart';

/// نوع الإيصال.
enum ReseatKind { sample, donation }

/// شاشة العيّنات / التبرعات — بيع بسعر صفر عبر POST /orders + اختيار عميل وصورة.
///
/// لا يوجد endpoint مخصص للعيّنات في v2؛ العيّنة منطقياً طلب بسعر صفر.
class ReseatScreen extends StatefulWidget {
  final ReseatKind kind;
  final int? customerId;
  const ReseatScreen({super.key, required this.kind, this.customerId});

  @override
  State<ReseatScreen> createState() => _ReseatScreenState();
}

class _ReseatScreenState extends State<ReseatScreen> {
  final _stockRepo = ProductRepository();
  final _orderRepo = OrderRepository();
  final _custRepo = CustomerRepository();
  final _picker = ImagePicker();

  late Future<List<Product>> _future;
  final Map<int, num> _selected = {};
  Customer? _customer;
  File? _photo;
  bool _saving = false;

  bool get _isSample => widget.kind == ReseatKind.sample;
  String get _title => _isSample ? 'صرف عيّنات' : 'تسجيل تبرع';
  // العيّنة order_type=12، التبرع order_type=24 (أنواع مخصّصة بالـ API).
  int get _orderType => _isSample ? 12 : 24;
  IconData get _icon => _isSample
      ? Icons.card_giftcard_outlined
      : Icons.volunteer_activism_outlined;

  @override
  void initState() {
    super.initState();
    _future = _load();
    if (widget.customerId != null) _preloadCustomer();
  }

  Future<List<Product>> _load() async {
    final res = await _stockRepo.stocks(vanStock: true, limit: 100);
    return res.items;
  }

  Future<void> _preloadCustomer() async {
    try {
      final c = await _custRepo.byId(widget.customerId!);
      if (mounted) setState(() => _customer = c);
    } catch (_) {}
  }

  Future<void> _pickCustomer() async {
    final c = await pickCustomer(context);
    if (c != null && mounted) setState(() => _customer = c);
  }

  Future<void> _pickPhoto() async {
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
    if (x != null && mounted) setState(() => _photo = File(x.path));
  }

  Future<void> _submit() async {
    if (_customer == null) {
      _snack('اختر العميل أولاً', AppColors.danger);
      return;
    }
    final cart = _selected.entries
        .where((e) => e.value > 0)
        .map((e) => CartItem(e.key, e.value, 0)) // سعر صفر = مجاني
        .toList();
    if (cart.isEmpty) {
      _snack('اختر أصنافاً أولاً', AppColors.danger);
      return;
    }
    // الصورة إجبارية.
    if (_photo == null) {
      _snack('إرفاق صورة الإيصال مطلوب', AppColors.danger);
      return;
    }
    setState(() => _saving = true);
    try {
      final order = await _orderRepo.place(
        customerId: _customer!.id,
        cart: cart,
        orderType: _orderType, // 12 عيّنة · 24 تبرع
        receiptPath: _photo?.path,
      );
      if (!mounted) return;
      _snack('تم حفظ $_title (#${order.id})', AppColors.primaryDeep);
      setState(() {
        _selected.clear();
        _photo = null;
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
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: Text(_title),
          bottom: TabBar(
            labelColor: AppColors.primaryDeep,
            unselectedLabelColor: AppColors.muted,
            indicatorColor: AppColors.primary,
            labelStyle:
                const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            tabs: [
              Tab(text: _title),
              const Tab(text: 'السجل'),
            ],
          ),
        ),
        body: SafeArea(
          child: TabBarView(
            children: [
              _createTab(),
              _FreeOrdersLog(orderType: _orderType),
            ],
          ),
        ),
      ),
    );
  }

  Widget _createTab() {
    return Column(
          children: [
            Expanded(
              child: FutureBuilder<List<Product>>(
                future: _future,
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting) {
                    return const Center(
                        child: CircularProgressIndicator(
                            color: AppColors.primary));
                  }
                  if (snap.hasError) {
                    return Center(
                        child: Text(
                            snap.error is ApiException
                                ? (snap.error as ApiException).message
                                : 'تعذّر التحميل',
                            style: const TextStyle(color: AppColors.muted)));
                  }
                  final items = snap.data!;
                  return ListView(
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
                    children: [
                      ScreenHeader(
                        icon: _icon,
                        title: _title,
                        subtitle: _isSample
                            ? 'أصناف مجانية للعميل'
                            : 'تبرع من المخزون',
                      ),
                      const SizedBox(height: 14),
                      _customerBar(),
                      const SizedBox(height: 14),
                      const Text('الأصناف',
                          style: TextStyle(
                              fontSize: 13,
                              color: AppColors.muted,
                              fontWeight: FontWeight.w600)),
                      const SizedBox(height: 8),
                      if (items.isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 20),
                          child: Center(
                              child: Text('لا يوجد مخزون',
                                  style: TextStyle(color: AppColors.muted))),
                        )
                      else
                        Card(
                          child: Padding(
                            padding:
                                const EdgeInsets.symmetric(horizontal: 15),
                            child: Column(
                              children: [
                                for (var i = 0; i < items.length; i++) ...[
                                  if (i != 0)
                                    const Divider(
                                        height: 1, color: AppColors.line),
                                  _row(items[i]),
                                ],
                              ],
                            ),
                          ),
                        ),
                      const SizedBox(height: 14),
                      const Text('صورة إثبات',
                          style: TextStyle(
                              fontSize: 12.5,
                              color: AppColors.inkSoft,
                              fontWeight: FontWeight.w500)),
                      const SizedBox(height: 6),
                      _photoBox(),
                    ],
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 16),
              child: ElevatedButton(
                onPressed: _saving ? null : _submit,
                child: _saving
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(
                            strokeWidth: 2.4, color: Colors.white))
                    : Text('تأكيد $_title'),
              ),
            ),
          ],
        );
  }

  Widget _customerBar() {
    final has = _customer != null;
    return GestureDetector(
      onTap: _pickCustomer,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: has ? AppColors.primaryWash : AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border:
              Border.all(color: has ? AppColors.primary : AppColors.line),
        ),
        child: Row(
          children: [
            Icon(has ? Icons.store_rounded : Icons.person_search_rounded,
                color: has ? AppColors.primaryDeep : AppColors.muted, size: 22),
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

  Widget _photoBox() {
    return GestureDetector(
      onTap: _pickPhoto,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
              color: _photo != null ? AppColors.primary : AppColors.line,
              width: 1.4),
        ),
        child: _photo != null
            ? Column(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.file(_photo!,
                        height: 120, width: double.infinity, fit: BoxFit.cover),
                  ),
                  const SizedBox(height: 8),
                  TextButton.icon(
                    onPressed: () => setState(() => _photo = null),
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
                  Text('صوّر أو ارفع صورة إثبات',
                      style: TextStyle(fontSize: 13, color: AppColors.muted)),
                ],
              ),
      ),
    );
  }

  Widget _row(Product p) {
    final qty = _selected[p.id] ?? 0;
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
                Text('متاح ${p.quantity}',
                    style:
                        const TextStyle(fontSize: 12, color: AppColors.muted)),
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
                            n <= 0
                                ? _selected.remove(p.id)
                                : _selected[p.id] = n;
                          })
                      : null,
                  child: Padding(
                    padding: const EdgeInsets.all(8),
                    child: Icon(Icons.remove,
                        size: 18,
                        color: qty > 0 ? AppColors.inkSoft : AppColors.muted),
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
                              ? AppColors.primaryDeep
                              : AppColors.muted)),
                ),
                InkWell(
                  onTap: () => setState(() => _selected[p.id] = qty + 1),
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
}

/// سجل العيّنات (type=12) أو التبرعات (type=24).
class _FreeOrdersLog extends StatefulWidget {
  final int orderType;
  const _FreeOrdersLog({required this.orderType});

  @override
  State<_FreeOrdersLog> createState() => _FreeOrdersLogState();
}

class _FreeOrdersLogState extends State<_FreeOrdersLog>
    with AutomaticKeepAliveClientMixin {
  final _repo = OrderRepository();
  late Future<List<Order>> _future;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<Order>> _load() async {
    final res = await _repo.list(type: widget.orderType, limit: 50);
    return res.items;
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: FutureBuilder<List<Order>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
                child: CircularProgressIndicator(color: AppColors.primary));
          }
          if (snap.hasError) {
            return ListView(children: const [
              SizedBox(height: 120),
              Icon(Icons.cloud_off, size: 48, color: AppColors.muted),
              SizedBox(height: 10),
              Center(
                  child: Text('تعذّر التحميل',
                      style: TextStyle(color: AppColors.muted))),
            ]);
          }
          final list = snap.data!;
          if (list.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 120),
              Icon(Icons.card_giftcard_outlined,
                  size: 48, color: AppColors.muted),
              SizedBox(height: 10),
              Center(
                  child: Text('لا يوجد سجل بعد',
                      style: TextStyle(color: AppColors.muted))),
            ]);
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
            itemCount: list.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final o = list[i];
              return Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.line),
                ),
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
                      child: Text('#${o.id}',
                          style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 12,
                              color: AppColors.primaryDeep)),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(o.customerName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                  fontSize: 14, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 3),
                          Text(
                              '${o.lines.length} أصناف · ${o.createdAt?.split('T').first ?? ''}',
                              style: const TextStyle(
                                  fontSize: 12, color: AppColors.muted)),
                        ],
                      ),
                    ),
                    StatusBadge.warn('مجاني'),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
