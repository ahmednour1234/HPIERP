import 'package:flutter/material.dart';
import '../../core/location/location_service.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'attendance_repository.dart';

/// شاشة الحضور والبصمة — تحمّل السجل وتسجّل بصمة فعلياً.
class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  final _repo = AttendanceRepository();
  final _noteCtrl = TextEditingController();
  late Future<List<AttendanceRecord>> _future;
  bool _punching = false;

  @override
  void initState() {
    super.initState();
    _future = _repo.list();
  }

  @override
  void dispose() {
    _noteCtrl.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _repo.list();
    });
    await _future;
  }

  // status: 1 حضور · 2 انصراف
  Future<void> _punch(int status) async {
    setState(() => _punching = true);
    try {
      final geo = await LocationService.current();
      await _repo.punch(
        status: status,
        lat: geo.lat,
        lng: geo.lng,
        note: _noteCtrl.text,
      );
      if (!mounted) return;
      _noteCtrl.clear();
      _snack(status == 1 ? 'تم تسجيل الحضور' : 'تم تسجيل الانصراف',
          AppColors.primaryDeep);
      _refresh();
    } on LocationException catch (e) {
      _snack(e.message, AppColors.danger);
    } on ApiException catch (e) {
      _snack(e.message, AppColors.danger);
    } finally {
      if (mounted) setState(() => _punching = false);
    }
  }

  void _snack(String m, Color c) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(m), backgroundColor: c));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الحضور اليومي')),
      body: SafeArea(
        child: Column(
          children: [
            // الجزء القابل للتمرير: العنوان + البطاقة + السجل.
            Expanded(
              child: RefreshIndicator(
                onRefresh: _refresh,
                color: AppColors.primary,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
                  children: [
                    const ScreenHeader(
                      icon: Icons.fingerprint,
                      title: 'الحضور اليومي',
                      subtitle: 'سجّل بصمتك لبدء الرحلة',
                    ),
                    const SizedBox(height: 16),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 26),
                        child: Column(
                          children: [
                            Container(
                              width: 96,
                              height: 96,
                              decoration: const BoxDecoration(
                                shape: BoxShape.circle,
                                color: AppColors.primaryWash,
                              ),
                              child: const Icon(Icons.fingerprint,
                                  size: 48, color: AppColors.primaryDeep),
                            ),
                            const SizedBox(height: 14),
                            const Text('اضغط لتسجيل الحضور',
                                style: TextStyle(
                                    fontSize: 17, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 8),
                            const GpsNote('يُسجّل موقعك مع البصمة'),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('سجل الحضور',
                        style: TextStyle(
                            fontSize: 13,
                            color: AppColors.muted,
                            fontWeight: FontWeight.w600)),
                    const SizedBox(height: 10),
                    FutureBuilder<List<AttendanceRecord>>(
                      future: _future,
                      builder: (context, snap) {
                        if (snap.connectionState == ConnectionState.waiting) {
                          return const Padding(
                            padding: EdgeInsets.all(24),
                            child: Center(
                                child: CircularProgressIndicator(
                                    color: AppColors.primary)),
                          );
                        }
                        if (snap.hasError) {
                          final msg = snap.error is ApiException
                              ? (snap.error as ApiException).message
                              : 'تعذّر التحميل';
                          return _msgCard(msg);
                        }
                        final list = snap.data!;
                        if (list.isEmpty) {
                          return _msgCard('لا يوجد سجل حضور بعد');
                        }
                        return Card(
                          child: Padding(
                            padding:
                                const EdgeInsets.symmetric(horizontal: 15),
                            child: Column(
                              children: [
                                for (var i = 0; i < list.length; i++) ...[
                                  if (i != 0)
                                    const Divider(
                                        height: 1, color: AppColors.line),
                                  _row(list[i]),
                                ],
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
            // شريط سفلي ثابت: الملاحظة + زرّا الحضور والانصراف.
            _actionBar(),
          ],
        ),
      ),
    );
  }

  /// شريط الإجراءات الثابت أسفل الشاشة — يظهر دائماً بلا تمرير.
  Widget _actionBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
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
          TextField(
            controller: _noteCtrl,
            minLines: 1,
            maxLines: 2,
            textInputAction: TextInputAction.done,
            decoration: const InputDecoration(
              hintText: 'المكان أو ملاحظة (اختياري): الفرع الرئيسي...',
              prefixIcon: Icon(Icons.edit_note_outlined),
              isDense: true,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _punching ? null : () => _punch(1),
                  icon: _punching
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                              strokeWidth: 2.2, color: Colors.white))
                      : const Icon(Icons.login_rounded, size: 20),
                  label: const Text('تسجيل حضور'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _punching ? null : () => _punch(2),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.danger,
                    side: const BorderSide(color: AppColors.danger),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  icon: const Icon(Icons.logout_rounded, size: 20),
                  label: const Text('تسجيل انصراف'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _row(AttendanceRecord r) {
    // انصراف = عندنا وقت انصراف فعلي (أو status=2).
    final left = r.hasCheckOut || r.status == 2;
    return AppListRow(
      thumb: left ? '↩' : '✓',
      thumbBg: left ? AppColors.dangerWash : AppColors.goodWash,
      thumbColor: left ? AppColors.danger : AppColors.good,
      name: r.date,
      // نعرض الحضور والانصراف بوضوح بتسمية عربية.
      sub: 'حضور ${r.checkIn ?? '—'}'
          '${left ? '  ·  انصراف ${r.checkOut ?? '—'}' : ''}',
      // الملاحظة اللي كتبها المندوب وقت البصمة — غالباً اسم المكان.
      note: r.note?.trim().isEmpty ?? true ? null : r.note!.trim(),
      trailing: left
          ? StatusBadge.danger('انصراف')
          : StatusBadge.good('حضور'),
    );
  }

  Widget _msgCard(String text) => Card(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
              child: Text(text,
                  style: const TextStyle(color: AppColors.muted))),
        ),
      );
}
