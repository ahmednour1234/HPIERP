import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'order_repository.dart';

/// شاشة الطلبات — قائمة طلبات المندوب من الخادم مع فلترة بالنوع.
class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  final _repo = OrderRepository();
  int _filter = 0; // الكل · بيع · مرتجع · عيّنات · تبرعات
  final _scroll = ScrollController();

  final List<Order> _items = [];
  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  Object? _error;

  // null الكل · 4 بيع · 7 مرتجع · 12 عيّنات · 24 تبرعات
  static const _types = [null, 4, 7, 12, 24];

  @override
  void initState() {
    super.initState();
    _fetch(reset: true);
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 300 &&
          !_loadingMore &&
          _hasMore) {
        _fetch();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _fetch({bool reset = false}) async {
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
    try {
      final res =
          await _repo.list(type: _types[_filter], offset: _page, limit: 25);
      if (!mounted) return;
      setState(() {
        _items.addAll(res.items);
        _hasMore = res.hasMore;
        _page++;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
          _loadingMore = false;
        });
      }
    }
  }

  void _setFilter(int i) {
    setState(() => _filter = i);
    _fetch(reset: true);
  }

  Future<void> _refresh() => _fetch(reset: true);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: Column(
                children: [
                  const ScreenHeader(
                    icon: Icons.receipt_long_outlined,
                    title: 'الفواتير',
                    subtitle: 'كل فواتيرك',
                  ),
                  const SizedBox(height: 14),
                  SegmentedTabs(
                    items: const ['الكل', 'بيع', 'مرتجع', 'عيّنات', 'تبرعات'],
                    selected: _filter,
                    onChanged: _setFilter,
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
      return _errorView(_error);
    }
    if (_items.isEmpty) {
      return _centered(Icons.inbox_outlined, 'لا توجد طلبات');
    }
    final total = _items.fold<double>(0, (s, o) => s + o.amount);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: ListView.builder(
        controller: _scroll,
        padding: const EdgeInsets.fromLTRB(20, 6, 20, 24),
        // شريط الإجمالي + بطاقة القائمة + مؤشر تحميل المزيد.
        itemCount: 2 + (_hasMore ? 1 : 0),
        itemBuilder: (context, i) {
          if (i == 0) {
            return Container(
              margin: const EdgeInsets.only(bottom: 10),
              padding:
                  const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(13),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('${_items.length} طلب',
                      style: const TextStyle(
                          fontSize: 13, color: AppColors.inkSoft)),
                  MoneyText(_money(total),
                      size: 17, color: AppColors.primaryDeep),
                ],
              ),
            );
          }
          if (i == 1) {
            return Card(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 15),
                child: Column(
                  children: [
                    for (var j = 0; j < _items.length; j++) ...[
                      if (j != 0)
                        const Divider(height: 1, color: AppColors.line),
                      _orderRow(_items[j]),
                    ],
                  ],
                ),
              ),
            );
          }
          return const Padding(
            padding: EdgeInsets.all(16),
            child: Center(
                child: CircularProgressIndicator(color: AppColors.primary)),
          );
        },
      ),
    );
  }

  Widget _orderRow(Order o) {
    final isReturn = o.isReturn;
    return AppListRow(
      thumb: '#${o.id}',
      thumbBg: isReturn ? AppColors.dangerWash : AppColors.primaryWash,
      thumbColor: isReturn ? AppColors.danger : AppColors.primaryDeep,
      name: o.customerName,
      sub: '${o.lines.isNotEmpty ? '${o.lines.length} أصناف · ' : ''}${o.typeLabel}',
      onTap: () => context.push('/order', extra: o.id),
      trailing: Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisSize: MainAxisSize.min,
        children: [
          MoneyText(_money(o.amount),
              size: 14, color: isReturn ? AppColors.danger : AppColors.ink),
          const SizedBox(height: 5),
          if (isReturn)
            StatusBadge.danger('مرتجع')
          else if (o.isFree)
            StatusBadge.warn(o.typeLabel)
          else
            StatusBadge.good(o.typeLabel),
        ],
      ),
    );
  }

  Widget _errorView(Object? error) {
    final msg =
        error is ApiException ? error.message : 'تعذّر تحميل الطلبات';
    return _centered(Icons.cloud_off, msg, retry: true);
  }

  Widget _centered(IconData icon, String text, {bool retry = false}) {
    return ListView(
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 48, color: AppColors.muted),
        const SizedBox(height: 10),
        Center(child: Text(text, style: const TextStyle(color: AppColors.muted))),
        if (retry) ...[
          const SizedBox(height: 14),
          Center(
            child: OutlinedButton(
                onPressed: _refresh, child: const Text('إعادة المحاولة')),
          ),
        ],
      ],
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
