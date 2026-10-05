import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/network/api_client.dart';
import '../transactions/deposit_repository.dart';

/// شاشة المديونية — ملخص حقيقي للحركة النقدية للمندوب.
///
/// الأرقام كلها من GET /finance/summary (السيرفر هو اللي بيحسبها):
/// العهدة (اللي مع المندوب/المورّد/قيد المراجعة) + التحصيل + المبيعات.
class FundListScreen extends StatefulWidget {
  const FundListScreen({super.key});

  @override
  State<FundListScreen> createState() => _FundListScreenState();
}

class _FundListScreenState extends State<FundListScreen> {
  final _api = ApiClient.instance;
  final _depRepo = DepositRepository();
  late Future<_Custody> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_Custody> _load() async {
    final results = await Future.wait([
      _api.get('/finance/summary'),
      _depRepo.list(),
    ]);
    final m = (results[0] as Map).cast<String, dynamic>();
    Map<String, dynamic> sec(String k) =>
        (m[k] as Map?)?.cast<String, dynamic>() ?? const {};
    double d(dynamic v) =>
        v is num ? v.toDouble() : double.tryParse('${v ?? 0}') ?? 0;
    final sales = sec('sales'), col = sec('collection'), cus = sec('custody');
    return _Custody(
      inHand: d(cus['in_hand']),
      deposited: d(cus['deposited']),
      pending: d(cus['pending']),
      collected: d(col['collected']),
      outstanding: d(col['outstanding']),
      rate: d(col['rate']),
      netSales: d(sales['net']),
      openInvoices: d(m['open_invoices']).round(),
      deposits: results[1] as List<Deposit>,
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
    return Scaffold(
      appBar: AppBar(title: const Text('المديونية والأموال')),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _refresh,
          color: AppColors.primary,
          child: FutureBuilder<_Custody>(
            future: _future,
            builder: (context, snap) {
              if (snap.connectionState == ConnectionState.waiting) {
                return const Center(
                    child:
                        CircularProgressIndicator(color: AppColors.primary));
              }
              if (snap.hasError) {
                final msg = snap.error is ApiException
                    ? (snap.error as ApiException).message
                    : 'تعذّر تحميل المديونية';
                return ListView(children: [
                  const SizedBox(height: 120),
                  const Icon(Icons.cloud_off, size: 48, color: AppColors.muted),
                  const SizedBox(height: 10),
                  Center(
                      child: Text(msg,
                          style: const TextStyle(color: AppColors.muted))),
                  const SizedBox(height: 14),
                  Center(
                      child: OutlinedButton(
                          onPressed: _refresh,
                          child: const Text('إعادة المحاولة'))),
                ]);
              }
              return _content(snap.data!);
            },
          ),
        ),
      ),
    );
  }

  Widget _content(_Custody c) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
      children: [
        const ScreenHeader(
          icon: Icons.account_balance_wallet_outlined,
          title: 'المديونية',
          subtitle: 'ملخص عهدتك وتحصيلك',
        ),
        const SizedBox(height: 16),
        // بطاقة الرصيد
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            gradient: const LinearGradient(
              colors: [AppColors.primary, AppColors.primaryDeep],
              begin: Alignment.topRight,
              end: Alignment.bottomLeft,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('المبالغ التي يجب توريدها',
                  style: TextStyle(color: Colors.white70, fontSize: 13)),
              const SizedBox(height: 8),
              Text('${_money(c.inHand)} ج',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 30,
                      fontWeight: FontWeight.w800,
                      fontFeatures: [FontFeature.tabularFigures()])),
              const SizedBox(height: 14),
              Row(
                children: [
                  _pill(Icons.arrow_downward, 'محصّل', _money(c.collected)),
                  const SizedBox(width: 10),
                  _pill(Icons.upload_rounded, 'مورّد', _money(c.deposited)),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        // تفصيل
        Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 15),
            child: Column(
              children: [
                _line('إجمالي المحصّل', _money(c.collected), AppColors.good),
                const Divider(height: 1, color: AppColors.line),
                _line('المورّد للشركة', _money(c.deposited),
                    AppColors.primaryDeep),
                const Divider(height: 1, color: AppColors.line),
                _line('توريدات قيد المراجعة', _money(c.pending),
                    AppColors.warn),
                const Divider(height: 1, color: AppColors.line),
                _line('المتبقّي على العملاء', _money(c.outstanding),
                    AppColors.danger),
                const Divider(height: 1, color: AppColors.line),
                _line('نسبة التحصيل', '${c.rate.toStringAsFixed(1)}%',
                    AppColors.primaryDeep),
                const Divider(height: 1, color: AppColors.line),
                _line('فواتير مفتوحة', '${c.openInvoices}', AppColors.inkSoft),
              ],
            ),
          ),
        ),
        const SizedBox(height: 18),
        const Text('توريداتك',
            style: TextStyle(
                fontSize: 13,
                color: AppColors.muted,
                fontWeight: FontWeight.w600)),
        const SizedBox(height: 10),
        if (c.deposits.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 20),
            child: Center(
                child: Text('لا توجد توريدات',
                    style: TextStyle(color: AppColors.muted))),
          )
        else
          Card(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              child: Column(
                children: [
                  for (var i = 0; i < c.deposits.length; i++) ...[
                    if (i != 0) const Divider(height: 1, color: AppColors.line),
                    _depositRow(c.deposits[i]),
                  ],
                ],
              ),
            ),
          ),
      ],
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

  Widget _line(String label, String value, Color color) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 13),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: const TextStyle(fontSize: 13.5, color: AppColors.inkSoft)),
          MoneyText(value, size: 15, color: color),
        ],
      ),
    );
  }

  Widget _pill(IconData icon, String label, String value) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.15),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          children: [
            Icon(icon, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label,
                      style:
                          const TextStyle(color: Colors.white70, fontSize: 11)),
                  Text(value,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          fontFeatures: [FontFeature.tabularFigures()])),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}

class _Custody {
  final double inHand; // اللي مع المندوب ولازم يتورّد
  final double deposited;
  final double pending; // توريدات لسه بتتراجع
  final double collected;
  final double outstanding; // متبقّي على العملاء
  final double rate; // نسبة التحصيل %
  final double netSales;
  final int openInvoices;
  final List<Deposit> deposits;

  _Custody({
    required this.inHand,
    required this.deposited,
    required this.pending,
    required this.collected,
    required this.outstanding,
    required this.rate,
    required this.netSales,
    required this.openInvoices,
    required this.deposits,
  });
}
