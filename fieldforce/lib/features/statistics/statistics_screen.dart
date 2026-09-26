import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_exception.dart';
import '../../core/network/token_store.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'dashboard_repository.dart';
import 'widgets/kpi_card.dart';
import 'widgets/revenue_chart.dart';

/// لوحة الإحصائيات — أقسام: هيدر · بانر · KPIs · وصول سريع · رسم · أفضل المنتجات.
class StatisticsScreen extends StatefulWidget {
  const StatisticsScreen({super.key});

  @override
  State<StatisticsScreen> createState() => _StatisticsScreenState();
}

class _StatisticsScreenState extends State<StatisticsScreen> {
  final _repo = DashboardRepository();
  late Future<_DashboardData> _future;
  String _sellerName = ''; // اسم المندوب المسجّل (من التوكن).

  @override
  void initState() {
    super.initState();
    _future = _load();
    _loadSellerName();
  }

  Future<void> _loadSellerName() async {
    final name = await TokenStore.instance.sellerName();
    if (mounted && name != null && name.trim().isNotEmpty) {
      setState(() => _sellerName = name.trim());
    }
  }

  /// أول اسم للترحيب (أو "مندوب" مبدئياً).
  String get _greetingName {
    if (_sellerName.isEmpty) return 'مندوب';
    return _sellerName.split(' ').first;
  }

  /// أول حرف من الاسم للأفاتار.
  String get _avatarLetter =>
      _greetingName.isNotEmpty ? _greetingName.characters.first : 'م';

  Future<_DashboardData> _load() async {
    final results = await Future.wait([
      _repo.summary(),
      _repo.monthlyRevenue(months: 7),
      _repo.topProducts(limit: 4),
    ]);
    return _DashboardData(
      results[0] as DashboardSummary,
      results[1] as List<MonthlyRevenue>,
      results[2] as List<TopProduct>,
    );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: FutureBuilder<_DashboardData>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
                child: CircularProgressIndicator(color: AppColors.primary));
          }
          if (snap.hasError) return _error(snap.error);
          return _content(snap.data!);
        },
      ),
    );
  }

  Widget _content(_DashboardData d) {
    final s = d.summary;
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        children: [
          _header(s),
          const SizedBox(height: 16),
          _banner(s),
          const SizedBox(height: 20),
          _sectionTitle('نظرة عامة'),
          const SizedBox(height: 10),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.42,
            children: [
              KpiCard(
                  label: 'صافي المبيعات',
                  value: _money(s.netSales),
                  unit: 'ج',
                  delta: '${s.salesCount} فاتورة',
                  positive: true,
                  icon: Icons.payments_rounded),
              KpiCard(
                  label: 'إجمالي المبيعات',
                  value: _money(s.salesAmount),
                  unit: 'ج',
                  delta: '${s.salesCount} طلب',
                  positive: true,
                  icon: Icons.shopping_bag_rounded),
              KpiCard(
                  label: 'المرتجعات',
                  value: _money(s.returnsAmount),
                  unit: 'ج',
                  delta: s.returnsCount > 0
                      ? '${s.returnsCount} مرتجع'
                      : 'لا مرتجعات',
                  positive: s.returnsCount == 0,
                  icon: Icons.assignment_return_rounded),
              KpiCard(
                  label: 'التحصيلات',
                  value: _money(s.collected),
                  unit: 'ج',
                  delta: 'هذا الشهر',
                  positive: true,
                  icon: Icons.account_balance_wallet_rounded),
              KpiCard(
                  label: 'الزيارات',
                  // لو المستهدف متاح نعرض منفّذة/مستهدف، وإلا العدد فقط.
                  value: s.visitsTarget > 0
                      ? '${s.visits}/${s.visitsTarget}'
                      : '${s.visits}',
                  unit: '',
                  delta: s.visitsTarget > 0
                      ? '${s.visitsPercent}% من المستهدف'
                      : 'منفّذة هذا الشهر',
                  positive: true,
                  icon: Icons.location_on_rounded),
              KpiCard(
                  label: 'قيمة المخزون',
                  value: _money(s.stockValue),
                  unit: 'ج',
                  delta: s.lowStock > 0
                      ? '${s.lowStock} تحت الحد'
                      : 'المخزون سليم',
                  positive: s.lowStock == 0,
                  icon: Icons.inventory_2_rounded),
            ],
          ),
          const SizedBox(height: 22),
          _sectionTitle('وصول سريع'),
          const SizedBox(height: 10),
          _quickActions(),
          const SizedBox(height: 22),
          _sectionTitle('الإيراد الشهري'),
          const SizedBox(height: 10),
          _chartCard(d.monthly),
          if (d.topProducts.isNotEmpty) ...[
            const SizedBox(height: 22),
            _sectionTitle('الأكثر مبيعاً'),
            const SizedBox(height: 10),
            _topProducts(d.topProducts),
          ],
        ],
      ),
    );
  }

  // ===== الهيدر =====
  Widget _header(DashboardSummary s) {
    final period = (s.periodFrom != null && s.periodTo != null)
        ? '${s.periodFrom} — ${s.periodTo}'
        : 'هذا الشهر';
    return Row(
      children: [
        Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
            gradient: AppColors.brandGradient,
            borderRadius: BorderRadius.circular(15),
            boxShadow: AppColors.brandShadow,
          ),
          child: Center(
            child: Text(_avatarLetter,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w800)),
          ),
        ),
        const SizedBox(width: 13),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('أهلاً، $_greetingName 👋',
                  style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      color: AppColors.ink,
                      letterSpacing: -0.3)),
              Text(period,
                  style: const TextStyle(fontSize: 12, color: AppColors.muted)),
            ],
          ),
        ),
        GestureDetector(
          onTap: () => Scaffold.of(context).openDrawer(),
          child: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(13),
              border: Border.all(color: AppColors.line),
              boxShadow: AppColors.softShadow,
            ),
            child: const Icon(Icons.menu_rounded, color: AppColors.inkSoft),
          ),
        ),
      ],
    );
  }

  // ===== البانر =====
  Widget _banner(DashboardSummary s) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: AppColors.brandGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: AppColors.brandShadow,
      ),
      child: Stack(
        children: [
          // زخرفة دائرية
          Positioned(
            left: -20,
            top: -30,
            child: Container(
              width: 110,
              height: 110,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.06),
              ),
            ),
          ),
          Positioned(
            left: 30,
            bottom: -40,
            child: Container(
              width: 90,
              height: 90,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.sky.withValues(alpha: 0.12),
              ),
            ),
          ),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('صافي مبيعاتك هذا الشهر',
                        style:
                            TextStyle(color: Colors.white70, fontSize: 13)),
                    const SizedBox(height: 6),
                    Text('${_money(s.netSales)} ج',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 30,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.5,
                            fontFeatures: [FontFeature.tabularFigures()])),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.trending_up_rounded,
                              color: Colors.white, size: 15),
                          const SizedBox(width: 5),
                          Text('${s.visits} زيارة · ${s.salesCount} فاتورة',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                width: 62,
                height: 62,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Image.asset('assets/images/hpi_logo.png',
                    fit: BoxFit.contain),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ===== وصول سريع =====
  Widget _quickActions() {
    final items = [
      (Icons.shopping_bag_rounded, 'بيع', '/sell', AppColors.primary),
      (Icons.payments_rounded, 'تحصيل', '/invoices', AppColors.good),
      (Icons.location_on_rounded, 'زيارة', '/visits', AppColors.skyDeep),
      (Icons.person_add_alt_1_rounded, 'عميل', '/add-customer', AppColors.warn),
    ];
    return Row(
      children: [
        for (final it in items) ...[
          Expanded(
            child: GestureDetector(
              onTap: () => context.push(it.$3),
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 14),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: AppColors.line),
                  boxShadow: AppColors.softShadow,
                ),
                child: Column(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: it.$4.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: Icon(it.$1, color: it.$4, size: 22),
                    ),
                    const SizedBox(height: 9),
                    Text(it.$2,
                        style: const TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.ink)),
                  ],
                ),
              ),
            ),
          ),
          if (it != items.last) const SizedBox(width: 10),
        ],
      ],
    );
  }

  // ===== الرسم =====
  Widget _chartCard(List<MonthlyRevenue> monthly) {
    final sales = monthly.map((m) => m.sales).toList();
    final max = sales.isEmpty ? 1.0 : sales.reduce((a, b) => a > b ? a : b);
    final points = max == 0
        ? sales.map((_) => 0.0).toList()
        : sales.map((v) => v / max).toList();

    return BrandCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('آخر 7 شهور',
                  style:
                      TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                    color: AppColors.primaryWash,
                    borderRadius: BorderRadius.circular(999)),
                child: Text(
                    monthly.isNotEmpty ? monthly.last.month : '',
                    style: const TextStyle(
                        color: AppColors.primary,
                        fontSize: 11,
                        fontWeight: FontWeight.w700)),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
              height: 120,
              child: points.length < 2
                  ? const Center(
                      child: Text('لا توجد بيانات كافية',
                          style: TextStyle(color: AppColors.muted)))
                  : RevenueChart(points: points)),
        ],
      ),
    );
  }

  // ===== أفضل المنتجات =====
  Widget _topProducts(List<TopProduct> products) {
    return BrandCard(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Column(
        children: [
          for (var i = 0; i < products.length; i++) ...[
            if (i != 0) const Divider(height: 1, color: AppColors.lineSoft),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 11),
              child: Row(
                children: [
                  Container(
                    width: 30,
                    height: 30,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: i == 0
                          ? AppColors.warnWash
                          : AppColors.primaryWash,
                      borderRadius: BorderRadius.circular(9),
                    ),
                    child: Text('${i + 1}',
                        style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 13,
                            color: i == 0
                                ? const Color(0xFFB4740B)
                                : AppColors.primary)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(products[i].name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.w600)),
                        Text('${products[i].quantity} وحدة مباعة',
                            style: const TextStyle(
                                fontSize: 11.5, color: AppColors.muted)),
                      ],
                    ),
                  ),
                  MoneyText(_money(products[i].amount),
                      size: 14, color: AppColors.primaryDeep),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _sectionTitle(String t) => Text(t,
      style: const TextStyle(
          fontSize: 15.5,
          fontWeight: FontWeight.w800,
          color: AppColors.ink,
          letterSpacing: -0.2));

  Widget _error(Object? error) {
    final msg =
        error is ApiException ? error.message : 'تعذّر تحميل البيانات.';
    return ListView(
      children: [
        const SizedBox(height: 120),
        const Icon(Icons.cloud_off, size: 48, color: AppColors.muted),
        const SizedBox(height: 12),
        Center(child: Text(msg, style: const TextStyle(color: AppColors.muted))),
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton(
              onPressed: _refresh, child: const Text('إعادة المحاولة')),
        ),
      ],
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}

class _DashboardData {
  final DashboardSummary summary;
  final List<MonthlyRevenue> monthly;
  final List<TopProduct> topProducts;
  _DashboardData(this.summary, this.monthly, this.topProducts);
}
