import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../transactions/transaction_repository.dart';
import '../transactions/deposit_repository.dart';

/// شاشة المديونية — ملخص حقيقي للحركة النقدية للمندوب.
///
/// المحصّل = money_in، المصروف = money_out، المورّد = مجموع الـ deposits،
/// الرصيد المتبقّي مع المندوب = المحصّل − المورّد − المصروف.
class FundListScreen extends StatefulWidget {
  const FundListScreen({super.key});

  @override
  State<FundListScreen> createState() => _FundListScreenState();
}

class _FundListScreenState extends State<FundListScreen> {
  final _txRepo = TransactionRepository();
  final _depRepo = DepositRepository();
  late Future<_Custody> _future;
  String _from = '', _to = ''; // نطاق الشهر الحالي

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_Custody> _load() async {
    // نطاق الشهر الحالي فقط (العهدة تُحسب شهرياً).
    final now = DateTime.now();
    String fmt(DateTime d) =>
        '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
    final from = _from = fmt(DateTime(now.year, now.month, 1));
    final to = _to = fmt(DateTime(now.year, now.month + 1, 0)); // آخر يوم بالشهر

    final results = await Future.wait([
      _txRepo.ledgerTotals(from: from, to: to),
      _depRepo.list(from: from, to: to),
    ]);
    final totals = results[0] as ({double moneyIn, double moneyOut, double net});
    final deposits = results[1] as List<Deposit>;
    // المتبقّي يُحسب على التوريدات المقبولة فقط (status=1) — المتوافق عليها.
    // التوريدات المعلّقة/المرفوضة تظهر في القائمة لكنها لا تُخصم من الرصيد.
    final depositedApproved = deposits
        .where((d) => d.status == 1)
        .fold<double>(0, (s, d) => s + d.amount);
    return _Custody(
      collected: totals.moneyIn,
      spent: totals.moneyOut,
      deposited: depositedApproved,
      deposits: deposits,
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
          subtitle: 'ملخص حركتك النقدية لهذا الشهر',
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
              const Text('المبالغ التي يجب تحويلها',
                  style: TextStyle(color: Colors.white70, fontSize: 13)),
              const SizedBox(height: 8),
              Text('${_money(c.balance)} ج',
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
                  _pill(Icons.upload_rounded, 'مورّد (مقبول)',
                      _money(c.deposited)),
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
                // الضغط يعرض تفاصيل المصروف (كل حركة طالعة الشهر ده).
                InkWell(
                  onTap: c.spent > 0 ? _showSpent : null,
                  child: _line('المصروف', _money(c.spent), AppColors.danger,
                      tappable: c.spent > 0),
                ),
                const Divider(height: 1, color: AppColors.line),
                _line('المورّد للشركة (المقبول)', _money(c.deposited),
                    AppColors.primaryDeep),
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

  Widget _line(String label, String value, Color color,
      {bool tappable = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 13),
      child: Row(
        children: [
          Text(label,
              style: const TextStyle(fontSize: 13.5, color: AppColors.inkSoft)),
          if (tappable) ...[
            const SizedBox(width: 4),
            const Icon(Icons.info_outline, size: 15, color: AppColors.muted),
          ],
          const Spacer(),
          MoneyText(value, size: 15, color: color),
          if (tappable)
            const Icon(Icons.chevron_left, size: 18, color: AppColors.muted),
        ],
      ),
    );
  }

  /// تفاصيل المصروف — الحركات الطالعة (money_out) في الشهر الحالي.
  void _showSpent() {
    final future = _txRepo.entries(from: _from, to: _to);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => ConstrainedBox(
        constraints:
            BoxConstraints(maxHeight: MediaQuery.of(ctx).size.height * 0.7),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text('تفاصيل المصروف',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
              const SizedBox(height: 4),
              Text('من $_from إلى $_to',
                  style: const TextStyle(fontSize: 12, color: AppColors.muted)),
              const SizedBox(height: 12),
              Flexible(
                child: FutureBuilder<List<LedgerEntry>>(
                  future: future,
                  builder: (context, snap) {
                    if (snap.connectionState == ConnectionState.waiting) {
                      return const Padding(
                        padding: EdgeInsets.all(30),
                        child: Center(
                            child: CircularProgressIndicator(
                                color: AppColors.primary)),
                      );
                    }
                    if (snap.hasError) {
                      final e = snap.error;
                      return Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                            e is ApiException ? e.message : 'تعذّر التحميل',
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: AppColors.muted)),
                      );
                    }
                    final out =
                        snap.data!.where((t) => t.moneyOut > 0).toList();
                    if (out.isEmpty) {
                      return const Padding(
                        padding: EdgeInsets.all(24),
                        child: Text('لا توجد تفاصيل للمصروف',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: AppColors.muted)),
                      );
                    }
                    return ListView.separated(
                      shrinkWrap: true,
                      itemCount: out.length,
                      separatorBuilder: (_, _) =>
                          const Divider(height: 1, color: AppColors.line),
                      itemBuilder: (context, i) {
                        final t = out[i];
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 11),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                        t.description.isEmpty
                                            ? 'مصروف'
                                            : t.description,
                                        style: const TextStyle(
                                            fontSize: 13.5,
                                            fontWeight: FontWeight.w600)),
                                    if (t.date != null && t.date!.isNotEmpty)
                                      Text(t.date!.split('T').first,
                                          style: const TextStyle(
                                              fontSize: 11.5,
                                              color: AppColors.muted)),
                                  ],
                                ),
                              ),
                              MoneyText(_money(t.moneyOut),
                                  size: 14.5, color: AppColors.danger),
                            ],
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
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
  final double collected;
  final double spent;
  final double deposited;
  final List<Deposit> deposits;

  _Custody({
    required this.collected,
    required this.spent,
    required this.deposited,
    required this.deposits,
  });

  /// المتبقّي مع المندوب = المحصّل − المورّد − المصروف.
  double get balance {
    final b = collected - deposited - spent;
    return b < 0 ? 0 : b;
  }
}
