import 'package:flutter/material.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'salary_repository.dart';

/// شاشة الرواتب — كشف الشهر الحالي + التفصيل + الأداء + التاريخ.
class SalaryScreen extends StatefulWidget {
  const SalaryScreen({super.key});

  @override
  State<SalaryScreen> createState() => _SalaryScreenState();
}

class _SalaryScreenState extends State<SalaryScreen> {
  final _repo = SalaryRepository();
  late Future<List<Salary>> _future;
  String? _selectedMonth; // null = أحدث شهر

  @override
  void initState() {
    super.initState();
    _future = _repo.history(limit: 12);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _repo.history(limit: 12);
    });
    await _future;
  }

  /// نافذة اختيار الشهر من الكشوف المتاحة.
  void _pickMonth(List<Salary> list) {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: ListView(
          shrinkWrap: true,
          children: [
            const SizedBox(height: 12),
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                    color: AppColors.line,
                    borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const Padding(
              padding: EdgeInsets.all(16),
              child: Text('اختر الشهر',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            ),
            for (final s in list)
              ListTile(
                title: Text(s.month),
                subtitle: Text(s.statusText,
                    style: const TextStyle(fontSize: 12)),
                trailing: (_selectedMonth ?? list.first.month) == s.month
                    ? const Icon(Icons.check_circle, color: AppColors.primary)
                    : null,
                onTap: () {
                  setState(() => _selectedMonth = s.month);
                  Navigator.pop(ctx);
                },
              ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الرواتب')),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _refresh,
          color: AppColors.primary,
          child: FutureBuilder<List<Salary>>(
            future: _future,
            builder: (context, snap) {
              if (snap.connectionState == ConnectionState.waiting) {
                return const Center(
                    child: CircularProgressIndicator(color: AppColors.primary));
              }
              if (snap.hasError) {
                final msg = snap.error is ApiException
                    ? (snap.error as ApiException).message
                    : 'تعذّر تحميل الرواتب';
                return _msg(Icons.cloud_off, msg, retry: true);
              }
              final list = snap.data!;
              if (list.isEmpty) {
                return _msg(Icons.receipt_long_outlined,
                    'لا توجد كشوف رواتب بعد');
              }
              return _content(list);
            },
          ),
        ),
      ),
    );
  }

  Widget _content(List<Salary> list) {
    // الكشف المعروض = الشهر المختار أو الأحدث.
    final latest = _selectedMonth == null
        ? list.first
        : list.firstWhere((s) => s.month == _selectedMonth,
            orElse: () => list.first);
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
      children: [
        const ScreenHeader(
          icon: Icons.account_balance_wallet_outlined,
          title: 'كشف الراتب',
          subtitle: 'اختر الشهر لعرض كشفه',
        ),
        const SizedBox(height: 14),
        // زر اختيار الشهر
        GestureDetector(
          onTap: () => _pickMonth(list),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.line),
            ),
            child: Row(
              children: [
                const Icon(Icons.event_outlined,
                    size: 19, color: AppColors.primaryDeep),
                const SizedBox(width: 10),
                Text('شهر ${latest.month}',
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w700)),
                const Spacer(),
                const Icon(Icons.keyboard_arrow_down_rounded,
                    color: AppColors.muted),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        _netCard(latest),
        const SizedBox(height: 18),
        _breakdown(latest),
        const SizedBox(height: 18),
        _performance(latest),
        if (latest.managerNote != null &&
            latest.managerNote!.isNotEmpty &&
            latest.managerNote != 'لاتوجد ملاحظات') ...[
          const SizedBox(height: 18),
          _managerNote(latest.managerNote!),
        ],
        if (list.length > 1) ...[
          const SizedBox(height: 22),
          const Text('كشوف سابقة',
              style: TextStyle(
                  fontSize: 15.5,
                  fontWeight: FontWeight.w800,
                  color: AppColors.ink)),
          const SizedBox(height: 10),
          for (final s in list.skip(1)) ...[
            _historyRow(s),
            const SizedBox(height: 10),
          ],
        ],
      ],
    );
  }

  // بطاقة الصافي
  Widget _netCard(Salary s) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: AppColors.brandGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: AppColors.brandShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('صافي راتب ${s.month}',
                  style:
                      const TextStyle(color: Colors.white70, fontSize: 13)),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(s.statusText,
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w700)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text('${_money(s.net)} ج',
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.5,
                  fontFeatures: [FontFeature.tabularFigures()])),
          if (s.commission > 0) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.card_giftcard_rounded,
                    color: Colors.white70, size: 15),
                const SizedBox(width: 6),
                Text('+ ${_money(s.commission)} ج عمولة مبيعات',
                    style: const TextStyle(
                        color: Colors.white, fontSize: 12.5)),
              ],
            ),
          ],
        ],
      ),
    );
  }

  // التفصيل
  Widget _breakdown(Salary s) {
    return BrandCard(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Column(
        children: [
          for (var i = 0; i < s.details.length; i++) ...[
            if (i != 0) const Divider(height: 1, color: AppColors.lineSoft),
            _line(s.details[i].label, s.details[i].amount,
                s.details[i].isDeduct),
          ],
          if (s.deductions > 0) ...[
            const Divider(height: 1, color: AppColors.lineSoft),
            _line('الخصومات', s.deductions, true),
          ],
        ],
      ),
    );
  }

  Widget _line(String label, double amount, bool deduct) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Icon(deduct ? Icons.remove_circle_outline : Icons.add_circle_outline,
                  size: 17,
                  color: deduct ? AppColors.danger : AppColors.good),
              const SizedBox(width: 8),
              Text(label,
                  style: const TextStyle(
                      fontSize: 13.5, color: AppColors.inkSoft)),
            ],
          ),
          MoneyText('${deduct ? '- ' : ''}${_money(amount)}',
              size: 14, color: deduct ? AppColors.danger : AppColors.ink),
        ],
      ),
    );
  }

  // الأداء
  Widget _performance(Salary s) {
    return Row(
      children: [
        Expanded(
          child: _statBox(Icons.location_on_rounded, 'الزيارات',
              '${s.visitsAchieved}/${s.visitsTarget}', AppColors.skyDeep),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _statBox(Icons.calendar_today_rounded, 'أيام العمل',
              '${s.workingDays}', AppColors.good),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _statBox(Icons.star_rounded, 'التقييم', '${s.score}%',
              AppColors.warn),
        ),
      ],
    );
  }

  Widget _statBox(IconData icon, String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.line),
        boxShadow: AppColors.softShadow,
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 8),
          Text(value,
              style: const TextStyle(
                  fontSize: 15, fontWeight: FontWeight.w800)),
          const SizedBox(height: 2),
          Text(label,
              style: const TextStyle(fontSize: 11, color: AppColors.muted)),
        ],
      ),
    );
  }

  Widget _managerNote(String note) {
    return BrandCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(11)),
            child: const Icon(Icons.chat_bubble_outline_rounded,
                color: AppColors.primary, size: 19),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('ملاحظة المدير',
                    style: TextStyle(
                        fontSize: 12, color: AppColors.muted)),
                const SizedBox(height: 3),
                Text(note,
                    style: const TextStyle(
                        fontSize: 13.5, fontWeight: FontWeight.w500)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _historyRow(Salary s) {
    return BrandCard(
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(12)),
            child: const Icon(Icons.receipt_long_rounded,
                color: AppColors.primary, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(s.month,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w700)),
                Text(s.statusText,
                    style:
                        const TextStyle(fontSize: 12, color: AppColors.muted)),
              ],
            ),
          ),
          MoneyText('${_money(s.net)} ج',
              size: 15, color: AppColors.primaryDeep),
        ],
      ),
    );
  }

  Widget _msg(IconData icon, String text, {bool retry = false}) {
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
                  onPressed: _refresh,
                  child: const Text('إعادة المحاولة'))),
        ],
      ],
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
