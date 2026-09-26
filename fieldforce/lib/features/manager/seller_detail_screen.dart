import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'manager_repository.dart';

/// شاشة المدير لمندوب واحد — بصمته + ملاحظات المدير + كتابة ملاحظة.
class SellerDetailScreen extends StatefulWidget {
  final SellerInfo seller;
  const SellerDetailScreen({super.key, required this.seller});

  @override
  State<SellerDetailScreen> createState() => _SellerDetailScreenState();
}

class _SellerDetailScreenState extends State<SellerDetailScreen> {
  final _repo = ManagerRepository();
  late Future<List<SellerAttendance>> _attendance;
  late Future<List<ManagerNote>> _notes;
  late Future<SellerRating?> _rating;

  @override
  void initState() {
    super.initState();
    _attendance = _repo.attendance(widget.seller.id);
    _notes = _repo.notes(widget.seller.id);
    _rating = _loadRating();
  }

  /// التقييم من المسار المخصّص، ولو لم يُقيَّم بعد نستخدم ما جاء مع قائمة
  /// المناديب (rating/rating_note) حتى لا تظهر الشاشة فارغة بلا داعٍ.
  Future<SellerRating?> _loadRating() async {
    final r = await _repo.rating(widget.seller.id);
    if (r != null) return r;
    final s = widget.seller;
    if (s.rating == null) return null;
    return SellerRating(
      sellerId: s.id,
      sellerName: s.name,
      score: s.rating!,
      note: s.ratingNote,
    );
  }

  void _reloadNotes() {
    setState(() => _notes = _repo.notes(widget.seller.id));
  }

  Future<void> _addNote() async {
    final ctrl = TextEditingController();
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.fromLTRB(
            20, 18, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('ملاحظة للمندوب — ${widget.seller.name}',
                style:
                    const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            const Text('ستظهر عند المندوب في «ملاحظات المدير».',
                style: TextStyle(fontSize: 12.5, color: AppColors.muted)),
            const SizedBox(height: 14),
            TextField(
              controller: ctrl,
              minLines: 3,
              maxLines: 6,
              autofocus: true,
              decoration: const InputDecoration(
                hintText: 'اكتب ملاحظتك...',
                prefixIcon: Icon(Icons.edit_note_outlined),
              ),
            ),
            const SizedBox(height: 18),
            ElevatedButton(
              onPressed: () {
                if (ctrl.text.trim().isEmpty) return;
                Navigator.pop(ctx, true);
              },
              child: const Text('إرسال الملاحظة'),
            ),
          ],
        ),
      ),
    );
    if (ok != true) return;
    final note = ctrl.text.trim();
    if (note.isEmpty) return;
    try {
      final now = DateTime.now();
      final date =
          '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
      await _repo.addNote(widget.seller.id, note: note, date: date);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('تم إرسال الملاحظة للمندوب'),
        backgroundColor: AppColors.primaryDeep,
      ));
      _reloadNotes();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.message), backgroundColor: AppColors.danger));
      }
    }
  }

  /// شيت تقييم المندوب — درجة 0-100 + ملاحظة اختيارية.
  Future<void> _editRating(SellerRating? current) async {
    final scoreCtrl =
        TextEditingController(text: current == null ? '' : '${current.score}');
    final noteCtrl = TextEditingController(text: current?.note ?? '');
    // قيمة حيّة للسلايدر تتزامن مع الحقل الرقمي.
    double score = (current?.score ?? 75).toDouble().clamp(0, 100);
    String? error;
    bool saving = false;

    final saved = await showModalBottomSheet<SellerRating>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) {
          return Padding(
              padding: EdgeInsets.fromLTRB(
                  20, 18, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text('تقييم ${widget.seller.name}',
                      style: const TextStyle(
                          fontSize: 16, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  const Text('الدرجة من 0 إلى 100 — يراها المندوب في شؤون الموظف.',
                      style:
                          TextStyle(fontSize: 12.5, color: AppColors.muted)),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      SizedBox(
                        width: 74,
                        child: TextField(
                          controller: scoreCtrl,
                          textAlign: TextAlign.center,
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly
                          ],
                          style: const TextStyle(
                              fontSize: 18, fontWeight: FontWeight.w800),
                          decoration: const InputDecoration(
                            hintText: '0',
                            isDense: true,
                            contentPadding:
                                EdgeInsets.symmetric(vertical: 12),
                          ),
                          onChanged: (v) {
                            final n = int.tryParse(v);
                            if (n == null) return;
                            setSheet(() {
                              score = n.clamp(0, 100).toDouble();
                              error = null;
                            });
                          },
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Slider(
                          value: score,
                          min: 0,
                          max: 100,
                          divisions: 100,
                          label: '${score.round()}',
                          activeColor: _scoreColor(score),
                          onChanged: (v) => setSheet(() {
                            score = v;
                            scoreCtrl.text = '${v.round()}';
                            error = null;
                          }),
                        ),
                      ),
                    ],
                  ),
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: Text(
                      SellerRating(sellerId: 0, score: score.round()).label,
                      style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: _scoreColor(score)),
                    ),
                  ),
                  if (error != null) ...[
                    const SizedBox(height: 8),
                    Text(error!,
                        style: const TextStyle(
                            fontSize: 12.5, color: AppColors.danger)),
                  ],
                  const SizedBox(height: 14),
                  const Text('ملاحظة (اختياري)',
                      style: TextStyle(
                          fontSize: 13,
                          color: AppColors.muted,
                          fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: noteCtrl,
                    minLines: 2,
                    maxLines: 5,
                    decoration: const InputDecoration(
                      hintText: 'ملاحظة على الأداء...',
                      prefixIcon: Icon(Icons.edit_note_outlined),
                    ),
                  ),
                  const SizedBox(height: 18),
                  ElevatedButton(
                    onPressed: saving
                        ? null
                        : () async {
                            final n = int.tryParse(scoreCtrl.text.trim());
                            if (n == null || n < 0 || n > 100) {
                              setSheet(() => error = 'أدخل درجة صحيحة من 0 إلى 100');
                              return;
                            }
                            setSheet(() {
                              saving = true;
                              error = null;
                            });
                            try {
                              final res = await _repo.saveRating(
                                widget.seller.id,
                                score: n,
                                note: noteCtrl.text,
                              );
                              if (ctx.mounted) Navigator.pop(ctx, res);
                            } on ApiException catch (e) {
                              setSheet(() {
                                saving = false;
                                error = e.message;
                              });
                            }
                          },
                    child: saving
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                                strokeWidth: 2.2, color: Colors.white))
                        : Text(current == null
                            ? 'حفظ التقييم'
                            : 'تحديث التقييم'),
                  ),
                ],
              ),
            );
        },
      ),
    );

    if (saved == null || !mounted) return;
    setState(() => _rating = Future.value(saved));
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
      content: Text('تم حفظ التقييم'),
      backgroundColor: AppColors.primaryDeep,
    ));
  }

  static Color _scoreColor(num score) {
    if (score >= 75) return AppColors.good;
    if (score >= 50) return AppColors.warn;
    return AppColors.danger;
  }

  Widget _ratingSection() {
    return FutureBuilder<SellerRating?>(
      future: _rating,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Padding(
            padding: EdgeInsets.all(16),
            child: Center(
                child: CircularProgressIndicator(color: AppColors.primary)),
          );
        }
        final r = snap.data;
        if (r == null) {
          return InkWell(
            onTap: () => _editRating(null),
            borderRadius: BorderRadius.circular(16),
            child: Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.line),
              ),
              child: Row(
                children: [
                  const Icon(Icons.star_border_rounded,
                      color: AppColors.muted, size: 26),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text('لم يتم تقييم هذا المندوب بعد — اضغط للتقييم',
                        style: TextStyle(
                            fontSize: 13, color: AppColors.inkSoft)),
                  ),
                  const Icon(Icons.chevron_left, color: AppColors.muted),
                ],
              ),
            ),
          );
        }
        final color = _scoreColor(r.score);
        return BrandCard(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 52,
                    height: 52,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Text('${r.score}',
                        style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: color)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(r.label,
                            style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w800,
                                color: color)),
                        if (r.updatedAt != null)
                          Text('آخر تحديث ${r.updatedAt!.split('T').first}',
                              style: const TextStyle(
                                  fontSize: 11.5, color: AppColors.muted)),
                      ],
                    ),
                  ),
                  TextButton.icon(
                    onPressed: () => _editRating(r),
                    icon: const Icon(Icons.edit_outlined, size: 17),
                    label: const Text('تعديل'),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              ClipRRect(
                borderRadius: BorderRadius.circular(999),
                child: LinearProgressIndicator(
                  value: (r.score / 100).clamp(0, 1).toDouble(),
                  minHeight: 7,
                  backgroundColor: AppColors.lineSoft,
                  valueColor: AlwaysStoppedAnimation(color),
                ),
              ),
              if (r.note != null && r.note!.trim().isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(r.note!,
                    style: const TextStyle(fontSize: 13, height: 1.6)),
              ],
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.seller;
    return Scaffold(
      appBar: AppBar(title: const Text('ملف المندوب')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addNote,
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add_comment_outlined, color: Colors.white),
        label: const Text('ملاحظة',
            style:
                TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 90),
          children: [
            // رأس بطاقة المندوب
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                gradient: AppColors.brandGradient,
                borderRadius: BorderRadius.circular(20),
                boxShadow: AppColors.brandShadow,
              ),
              child: Row(
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(15),
                    ),
                    child: const Icon(Icons.person_rounded,
                        color: Colors.white, size: 28),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(s.name,
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 17,
                                fontWeight: FontWeight.w800)),
                        const SizedBox(height: 3),
                        Text('${s.code} · ${s.statusLabel}',
                            style: const TextStyle(
                                color: Colors.white70, fontSize: 12.5)),
                      ],
                    ),
                  ),
                  if (s.phone != null && s.phone!.isNotEmpty)
                    const Icon(Icons.phone_rounded, color: Colors.white70),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // التقييم
            const Text('التقييم',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 10),
            _ratingSection(),
            const SizedBox(height: 18),

            // ملاحظات المدير
            const Text('ملاحظات المدير',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 10),
            FutureBuilder<List<ManagerNote>>(
              future: _notes,
              builder: (context, snap) {
                if (snap.connectionState == ConnectionState.waiting) {
                  return const Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(
                        child: CircularProgressIndicator(
                            color: AppColors.primary)),
                  );
                }
                final list = snap.data ?? [];
                if (list.isEmpty) {
                  return _emptyCard('لا توجد ملاحظات — أضف واحدة بالزر أدناه');
                }
                return Column(
                  children: [for (final n in list) _noteCard(n)],
                );
              },
            ),
            const SizedBox(height: 18),

            // سجل البصمة
            const Text('سجل البصمة',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 10),
            FutureBuilder<List<SellerAttendance>>(
              future: _attendance,
              builder: (context, snap) {
                if (snap.connectionState == ConnectionState.waiting) {
                  return const Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(
                        child: CircularProgressIndicator(
                            color: AppColors.primary)),
                  );
                }
                if (snap.hasError) {
                  return _emptyCard('تعذّر تحميل البصمة');
                }
                final list = snap.data!;
                if (list.isEmpty) {
                  return _emptyCard('لا يوجد سجل بصمة');
                }
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 15),
                    child: Column(
                      children: [
                        for (var i = 0; i < list.length; i++) ...[
                          if (i != 0)
                            const Divider(height: 1, color: AppColors.line),
                          _attendanceRow(list[i]),
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
    );
  }

  Widget _noteCard(ManagerNote n) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.primaryWash,
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: AppColors.line),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.sticky_note_2_outlined,
                  size: 16, color: AppColors.primaryDeep),
              const SizedBox(width: 6),
              Text(n.managerName ?? 'المدير',
                  style: const TextStyle(
                      fontSize: 12.5, fontWeight: FontWeight.w700)),
              const Spacer(),
              Text(n.date ?? n.createdAt?.split('T').first ?? '',
                  style: const TextStyle(fontSize: 11, color: AppColors.muted)),
            ],
          ),
          const SizedBox(height: 8),
          Text(n.note,
              style: const TextStyle(
                  fontSize: 13, color: AppColors.ink, height: 1.5)),
        ],
      ),
    );
  }

  Widget _attendanceRow(SellerAttendance a) {
    final left = a.hasCheckOut || a.status == 2;
    final note = a.note?.trim() ?? '';
    return AppListRow(
      thumb: left ? '↩' : '✓',
      thumbBg: left ? AppColors.dangerWash : AppColors.goodWash,
      thumbColor: left ? AppColors.danger : AppColors.good,
      name: a.date,
      sub: 'حضور ${a.checkIn ?? '—'}'
          '${left ? '  ·  انصراف ${a.checkOut ?? '—'}' : ''}'
          '${a.timeLate > 0 ? '  ·  تأخير ${a.timeLate}د' : ''}',
      // ملاحظة المندوب وقت البصمة — غالباً اسم المكان.
      note: note.isEmpty ? null : note,
      trailing: left
          ? StatusBadge.danger('انصراف')
          : StatusBadge.good('حضور'),
    );
  }

  Widget _emptyCard(String text) => Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Center(
              child: Text(text,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted))),
        ),
      );
}
