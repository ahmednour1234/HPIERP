import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../manager/manager_repository.dart';
import 'hr_repository.dart';

/// شاشة شؤون المندوب (HR) — تبويبات: التقييم · طلباتي · إجازاتي · الوثائق · ملاحظات المدير.
class HrScreen extends StatelessWidget {
  const HrScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 6,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('شؤون الموظف'),
          bottom: const TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            labelColor: AppColors.primaryDeep,
            unselectedLabelColor: AppColors.muted,
            indicatorColor: AppColors.primary,
            labelStyle: TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            tabs: [
              Tab(text: 'التقييم'),
              Tab(text: 'الكورسات'),
              Tab(text: 'طلباتي'),
              Tab(text: 'إجازاتي'),
              Tab(text: 'الوثائق'),
              Tab(text: 'ملاحظات المدير'),
            ],
          ),
        ),
        body: const SafeArea(
          child: TabBarView(
            children: [
              _RatingsTab(),
              _CoursesTab(),
              _RequestsTab(isLeave: false),
              _RequestsTab(isLeave: true),
              _DocumentsTab(),
              _DevelopmentTab(),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------- الوثائق ----------
class _DocumentsTab extends StatefulWidget {
  const _DocumentsTab();

  @override
  State<_DocumentsTab> createState() => _DocumentsTabState();
}

class _DocumentsTabState extends State<_DocumentsTab>
    with AutomaticKeepAliveClientMixin {
  final _repo = HrRepository();
  late Future<List<HrDocument>> _future;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _repo.documents();
  }

  Future<void> _refresh() async {
    setState(() => _future = _repo.documents());
    await _future;
  }

  Future<void> _open(DocAttachment att) async {
    final uri = att.uri;
    if (uri == null) {
      _snack('لا يوجد رابط للمرفق', AppColors.danger);
      return;
    }
    try {
      // نفتح PDF/الروابط في المتصفح/التطبيق الخارجي، والصور كذلك.
      final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!opened) _snack('تعذّر فتح المرفق', AppColors.danger);
    } catch (_) {
      _snack('تعذّر فتح المرفق', AppColors.danger);
    }
  }

  void _snack(String m, Color c) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(m), backgroundColor: c));
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: FutureBuilder<List<HrDocument>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppColors.primary),
            );
          }
          if (snap.hasError) {
            return _centered(Icons.cloud_off, _errMsg(snap.error));
          }
          final list = snap.data ?? [];
          if (list.isEmpty) {
            return _centered(Icons.folder_open_outlined, 'لا توجد وثائق');
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
            itemCount: list.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) => _documentCard(list[i]),
          );
        },
      ),
    );
  }

  IconData _kindIcon(AttachmentKind k) => switch (k) {
        AttachmentKind.pdf => Icons.picture_as_pdf_rounded,
        AttachmentKind.image => Icons.image_rounded,
        AttachmentKind.link => Icons.link_rounded,
        AttachmentKind.file => Icons.insert_drive_file_rounded,
      };

  Color _kindColor(AttachmentKind k) => switch (k) {
        AttachmentKind.pdf => AppColors.danger,
        AttachmentKind.image => AppColors.good,
        AttachmentKind.link => AppColors.primaryDeep,
        AttachmentKind.file => AppColors.primaryDeep,
      };

  Widget _documentCard(HrDocument doc) {
    return BrandCard(
      padding: const EdgeInsets.all(15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // رأس الوثيقة
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppColors.skyWash,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: const Icon(Icons.folder_rounded,
                    color: AppColors.primaryDeep, size: 23),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(doc.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 14.5, fontWeight: FontWeight.w700)),
                    if (doc.description.isNotEmpty ||
                        doc.dateShort.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                          doc.description.isNotEmpty
                              ? doc.description
                              : doc.dateShort,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.muted)),
                    ],
                  ],
                ),
              ),
            ],
          ),
          // المرفقات — كل مرفق بزر عرض حسب نوعه (PDF/صورة/لينك).
          if (doc.attachments.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 10),
              child: Text('لا توجد مرفقات',
                  style: TextStyle(fontSize: 12, color: AppColors.muted)),
            )
          else ...[
            const SizedBox(height: 12),
            const Divider(height: 1, color: AppColors.lineSoft),
            for (final att in doc.attachments) _attachmentRow(att),
          ],
        ],
      ),
    );
  }

  Widget _attachmentRow(DocAttachment att) {
    final color = _kindColor(att.kind);
    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Row(
        children: [
          Icon(_kindIcon(att.kind), size: 20, color: color),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(att.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 13, fontWeight: FontWeight.w600)),
                Text(att.typeLabel,
                    style: const TextStyle(
                        fontSize: 11, color: AppColors.muted)),
              ],
            ),
          ),
          // زر عرض
          TextButton.icon(
            onPressed: att.hasUrl ? () => _open(att) : null,
            icon: const Icon(Icons.visibility_outlined, size: 17),
            label: const Text('عرض'),
            style: TextButton.styleFrom(
              foregroundColor: AppColors.primaryDeep,
              padding: const EdgeInsets.symmetric(horizontal: 10),
              visualDensity: VisualDensity.compact,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------- الكورسات ----------
class _CoursesTab extends StatefulWidget {
  const _CoursesTab();

  @override
  State<_CoursesTab> createState() => _CoursesTabState();
}

class _CoursesTabState extends State<_CoursesTab>
    with AutomaticKeepAliveClientMixin {
  final _repo = HrRepository();
  late Future<List<HrCourse>> _future;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _repo.courses();
  }

  Future<void> _refresh() async {
    setState(() => _future = _repo.courses());
    await _future;
  }

  Future<void> _openLink(HrCourse c) async {
    final uri = c.openUri;
    if (uri == null) {
      _snack('لا يوجد رابط لهذا الكورس', AppColors.danger);
      return;
    }
    try {
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok) _snack('تعذّر فتح الكورس', AppColors.danger);
    } catch (_) {
      _snack('تعذّر فتح الكورس', AppColors.danger);
    }
  }

  void _snack(String m, Color c) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(m), backgroundColor: c));
  }

  /// عرض تفاصيل الكورس (show).
  void _showDetails(HrCourse c) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
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
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            if (c.imageUrl != null && c.imageUrl!.isNotEmpty) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: Image.network(
                  c.imageUrl!,
                  height: 170,
                  width: double.infinity,
                  fit: BoxFit.cover,
                  errorBuilder: (_, _, _) => const SizedBox.shrink(),
                ),
              ),
              const SizedBox(height: 14),
            ],
            Text(
              c.name,
              style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                if (c.managerName != null) ...[
                  const Icon(Icons.person_outline,
                      size: 15, color: AppColors.muted),
                  const SizedBox(width: 4),
                  Text(c.managerName!,
                      style: const TextStyle(
                          fontSize: 12.5, color: AppColors.muted)),
                  const SizedBox(width: 12),
                ],
                if (c.dateShort.isNotEmpty) ...[
                  const Icon(Icons.event_outlined,
                      size: 15, color: AppColors.muted),
                  const SizedBox(width: 4),
                  Text(c.dateShort,
                      style: const TextStyle(
                          fontSize: 12.5, color: AppColors.muted)),
                ],
              ],
            ),
            if (c.link != null && c.link!.isNotEmpty) ...[
              const SizedBox(height: 14),
              const Divider(height: 1, color: AppColors.lineSoft),
              const SizedBox(height: 14),
              Row(
                children: [
                  const Icon(Icons.link_rounded,
                      size: 18, color: AppColors.primaryDeep),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(c.link!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 12.5, color: AppColors.primaryDeep)),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 18),
            ElevatedButton.icon(
              onPressed: c.canOpen
                  ? () {
                      Navigator.pop(ctx);
                      _openLink(c);
                    }
                  : null,
              style: ElevatedButton.styleFrom(
                  minimumSize: const Size.fromHeight(50)),
              icon: const Icon(Icons.open_in_new_rounded, size: 19),
              label: const Text('فتح الكورس'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: FutureBuilder<List<HrCourse>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppColors.primary),
            );
          }
          if (snap.hasError) {
            return _centered(Icons.cloud_off, _errMsg(snap.error));
          }
          final list = snap.data ?? [];
          if (list.isEmpty) {
            return _centered(
                Icons.school_outlined, 'لا توجد كورسات مُسنَدة إليك');
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
            itemCount: list.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) => _courseCard(list[i]),
          );
        },
      ),
    );
  }

  Widget _courseCard(HrCourse c) {
    return BrandCard(
      padding: const EdgeInsets.all(14),
      child: InkWell(
        onTap: () => _showDetails(c),
        borderRadius: BorderRadius.circular(12),
        child: Row(
          children: [
            // مصغّرة الكورس (صورة أو أيقونة).
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: (c.imageUrl != null && c.imageUrl!.isNotEmpty)
                  ? Image.network(
                      c.imageUrl!,
                      width: 54,
                      height: 54,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => _courseIcon(),
                    )
                  : _courseIcon(),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(c.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 14.5, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      if (c.link != null && c.link!.isNotEmpty) ...[
                        const Icon(Icons.link_rounded,
                            size: 13, color: AppColors.primaryDeep),
                        const SizedBox(width: 3),
                        const Text('رابط',
                            style: TextStyle(
                                fontSize: 11.5, color: AppColors.primaryDeep)),
                        const SizedBox(width: 10),
                      ],
                      if (c.dateShort.isNotEmpty)
                        Text(c.dateShort,
                            style: const TextStyle(
                                fontSize: 11.5, color: AppColors.muted)),
                    ],
                  ),
                ],
              ),
            ),
            // زر عرض (show)
            TextButton.icon(
              onPressed: () => _showDetails(c),
              icon: const Icon(Icons.visibility_outlined, size: 17),
              label: const Text('عرض'),
              style: TextButton.styleFrom(
                foregroundColor: AppColors.primaryDeep,
                padding: const EdgeInsets.symmetric(horizontal: 8),
                visualDensity: VisualDensity.compact,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _courseIcon() => Container(
        width: 54,
        height: 54,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: AppColors.skyWash,
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Icon(Icons.school_rounded,
            color: AppColors.primaryDeep, size: 26),
      );
}

// ---------- التقييم ----------
class _RatingsTab extends StatefulWidget {
  const _RatingsTab();
  @override
  State<_RatingsTab> createState() => _RatingsTabState();
}

class _RatingsTabState extends State<_RatingsTab>
    with AutomaticKeepAliveClientMixin {
  final _repo = HrRepository();
  final _mgrRepo = ManagerRepository();
  late Future<List<HrRating>> _future;
  late Future<SellerRating?> _myRating;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _repo.ratings();
    _myRating = _mgrRepo.myRating();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _repo.ratings();
      _myRating = _mgrRepo.myRating();
    });
    await Future.wait([_future, _myRating]);
  }

  static Color _scoreColor(num score) {
    if (score >= 75) return AppColors.good;
    if (score >= 50) return AppColors.warn;
    return AppColors.danger;
  }

  /// تقييم المدير الحالي — بطاقة أعلى سجل التقييمات الشهري.
  Widget _myRatingCard() {
    return FutureBuilder<SellerRating?>(
      future: _myRating,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const SizedBox(
            height: 90,
            child: Center(
                child: CircularProgressIndicator(color: AppColors.primary)),
          );
        }
        final r = snap.data;
        // لا نعرض شيئاً لو لم يُقيَّم بعد — السجل الشهري يكفي.
        if (r == null) return const SizedBox.shrink();
        final color = _scoreColor(r.score);
        return Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            gradient: AppColors.brandGradient,
            borderRadius: BorderRadius.circular(20),
            boxShadow: AppColors.brandShadow,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Text('${r.score}',
                        style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w800,
                            color: Colors.white)),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('تقييم المدير',
                            style: TextStyle(
                                color: Colors.white70, fontSize: 12.5)),
                        const SizedBox(height: 3),
                        Text(r.label,
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.w800)),
                        if (r.updatedAt != null)
                          Text('آخر تحديث ${r.updatedAt!.split('T').first}',
                              style: const TextStyle(
                                  color: Colors.white60, fontSize: 11.5)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              ClipRRect(
                borderRadius: BorderRadius.circular(999),
                child: LinearProgressIndicator(
                  value: (r.score / 100).clamp(0, 1).toDouble(),
                  minHeight: 7,
                  backgroundColor: Colors.white24,
                  valueColor: AlwaysStoppedAnimation(
                      color == AppColors.good ? Colors.white : color),
                ),
              ),
              if (r.note != null && r.note!.trim().isNotEmpty) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(13),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: Text(r.note!,
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          height: 1.6)),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: FutureBuilder<List<HrRating>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppColors.primary),
            );
          }
          if (snap.hasError) {
            return _centered(Icons.cloud_off, _errMsg(snap.error));
          }
          final list = snap.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
            children: [
              _myRatingCard(),
              if (list.isEmpty)
                // البطاقة أعلاه قد تكون ظاهرة، فنبقي الرسالة داخل القائمة.
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 40),
                  child: Column(
                    children: const [
                      Icon(Icons.star_border_rounded,
                          size: 40, color: AppColors.muted),
                      SizedBox(height: 10),
                      Text('لا يوجد سجل تقييمات شهري',
                          style: TextStyle(
                              fontSize: 13, color: AppColors.muted)),
                    ],
                  ),
                )
              else ...[
                const Text('السجل الشهري',
                    style: TextStyle(
                        fontSize: 13,
                        color: AppColors.muted,
                        fontWeight: FontWeight.w600)),
                const SizedBox(height: 10),
                for (var i = 0; i < list.length; i++) ...[
                  if (i != 0) const SizedBox(height: 10),
                  _ratingCard(list[i]),
                ],
              ],
            ],
          );
        },
      ),
    );
  }

  Widget _ratingCard(HrRating r) {
    return BrandCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppColors.primaryWash,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Text(
                  '${r.score}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: AppColors.primaryDeep,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      r.month.isEmpty ? 'تقييم' : r.month,
                      style: const TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    if (r.managerName != null)
                      Text(
                        r.managerName!,
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.muted,
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
          if (r.note.isNotEmpty) ...[
            const Divider(height: 20, color: AppColors.lineSoft),
            Text(
              r.note,
              style: const TextStyle(
                fontSize: 13,
                color: AppColors.inkSoft,
                height: 1.5,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ---------- طلباتي / إجازاتي ----------
class _RequestsTab extends StatefulWidget {
  final bool isLeave;
  const _RequestsTab({required this.isLeave});
  @override
  State<_RequestsTab> createState() => _RequestsTabState();
}

class _RequestsTabState extends State<_RequestsTab>
    with AutomaticKeepAliveClientMixin {
  final _repo = HrRepository();
  late Future<List<HrRequest>> _future;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<HrRequest>> _load() =>
      widget.isLeave ? _repo.leaves() : _repo.requests();

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _submit() async {
    final noteCtrl = TextEditingController();
    DateTime? date;
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            18,
            20,
            MediaQuery.of(ctx).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                widget.isLeave ? 'طلب إجازة جديد' : 'طلب جديد',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: noteCtrl,
                minLines: 3,
                maxLines: 5,
                autofocus: true,
                decoration: InputDecoration(
                  hintText: widget.isLeave
                      ? 'سبب الإجازة...'
                      : 'تفاصيل الطلب...',
                  prefixIcon: const Icon(Icons.edit_note_outlined),
                ),
              ),
              const SizedBox(height: 12),
              // اختيار تاريخ (اختياري)
              InkWell(
                onTap: () async {
                  final now = DateTime.now();
                  final d = await showDatePicker(
                    initialEntryMode: DatePickerEntryMode.calendarOnly,
                    context: ctx,
                    initialDate: now,
                    firstDate: now.subtract(const Duration(days: 7)),
                    lastDate: now.add(const Duration(days: 365)),
                  );
                  if (d != null) setSheet(() => date = d);
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 14,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.bg,
                    borderRadius: BorderRadius.circular(13),
                    border: Border.all(color: AppColors.line),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.event_outlined,
                        size: 19,
                        color: AppColors.muted,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        date == null
                            ? 'التاريخ (اختياري)'
                            : '${date!.year}-${date!.month.toString().padLeft(2, '0')}-${date!.day.toString().padLeft(2, '0')}',
                        style: TextStyle(
                          fontSize: 13.5,
                          color: date == null ? AppColors.muted : AppColors.ink,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 18),
              ElevatedButton(
                onPressed: () {
                  if (noteCtrl.text.trim().isEmpty) return;
                  Navigator.pop(ctx, true);
                },
                child: const Text('إرسال'),
              ),
            ],
          ),
        ),
      ),
    );
    if (ok != true) return;
    final note = noteCtrl.text.trim();
    if (note.isEmpty) return;
    final dateStr = date == null
        ? null
        : '${date!.year}-${date!.month.toString().padLeft(2, '0')}-${date!.day.toString().padLeft(2, '0')}';
    try {
      if (widget.isLeave) {
        await _repo.submitLeave(note: note, date: dateStr);
      } else {
        await _repo.submitRequest(note: note, date: dateStr);
      }
      if (!mounted) return;
      _snack('تم إرسال طلبك — بانتظار موافقة المدير', AppColors.primaryDeep);
      _refresh();
    } on ApiException catch (e) {
      _snack(e.message, AppColors.danger);
    }
  }

  void _snack(String m, Color c) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(m), backgroundColor: c));
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _submit,
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: Text(
          widget.isLeave ? 'طلب إجازة' : 'طلب جديد',
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: AppColors.primary,
        child: FutureBuilder<List<HrRequest>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              );
            }
            if (snap.hasError) {
              return _centered(Icons.cloud_off, _errMsg(snap.error));
            }
            final list = snap.data!;
            if (list.isEmpty) {
              return _centered(
                Icons.inbox_outlined,
                widget.isLeave ? 'لا توجد طلبات إجازة' : 'لا توجد طلبات',
              );
            }
            return ListView.separated(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 90),
              itemCount: list.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, i) => _requestCard(list[i]),
            );
          },
        ),
      ),
    );
  }

  Widget _requestCard(HrRequest r) {
    final badge = switch (r.status) {
      1 => StatusBadge.good('مقبول'),
      2 => StatusBadge.danger('مرفوض'),
      _ => StatusBadge.warn('قيد المراجعة'),
    };
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.line),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  r.note.isEmpty ? '—' : r.note,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              badge,
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.schedule, size: 13, color: AppColors.muted),
              const SizedBox(width: 4),
              Text(
                r.dateShort,
                style: const TextStyle(fontSize: 11.5, color: AppColors.muted),
              ),
              if (r.manager != null && r.manager!.isNotEmpty) ...[
                const SizedBox(width: 12),
                const Icon(
                  Icons.person_outline,
                  size: 13,
                  color: AppColors.muted,
                ),
                const SizedBox(width: 4),
                Text(
                  r.manager!,
                  style: const TextStyle(
                    fontSize: 11.5,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

// ---------- ملاحظات المدير (تطوير) ----------
class _DevelopmentTab extends StatefulWidget {
  const _DevelopmentTab();
  @override
  State<_DevelopmentTab> createState() => _DevelopmentTabState();
}

class _DevelopmentTabState extends State<_DevelopmentTab>
    with AutomaticKeepAliveClientMixin {
  final _mgrRepo = ManagerRepository();
  final _hrRepo = HrRepository();
  late Future<List<_NoteItem>> _future;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  /// نجمع ملاحظات المدير (/manager-notes) + ملاحظات التطوير (/hr/development).
  Future<List<_NoteItem>> _load() async {
    final items = <_NoteItem>[];
    try {
      final notes = await _mgrRepo.myManagerNotes();
      items.addAll(
        notes.map(
          (n) => _NoteItem(
            n.note,
            n.managerName ?? 'المدير',
            n.date ?? n.createdAt?.split('T').first,
          ),
        ),
      );
    } catch (_) {}
    try {
      final dev = await _hrRepo.development();
      items.addAll(
        dev.map(
          (d) => _NoteItem(
            d.note,
            d.manager ?? 'المدير',
            d.createdAt?.split('T').first,
          ),
        ),
      );
    } catch (_) {}
    return items;
  }

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: FutureBuilder<List<_NoteItem>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppColors.primary),
            );
          }
          if (snap.hasError) {
            return _centered(Icons.cloud_off, _errMsg(snap.error));
          }
          final list = snap.data ?? [];
          if (list.isEmpty) {
            return _centered(
              Icons.tips_and_updates_outlined,
              'لا توجد ملاحظات من المدير',
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
            itemCount: list.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final d = list[i];
              return BrandCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(
                          Icons.tips_and_updates_rounded,
                          size: 18,
                          color: AppColors.primaryDeep,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          d.manager,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const Spacer(),
                        if (d.date != null)
                          Text(
                            d.date!,
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.muted,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      d.note,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppColors.inkSoft,
                        height: 1.5,
                      ),
                    ),
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

/// عنصر ملاحظة موحّد (من manager-notes أو development).
class _NoteItem {
  final String note;
  final String manager;
  final String? date;
  const _NoteItem(this.note, this.manager, this.date);
}

// ---------- مساعدات مشتركة ----------
String _errMsg(Object? e) =>
    e is ApiException ? e.message : 'تعذّر تحميل البيانات';

Widget _centered(IconData icon, String text) => ListView(
  children: [
    const SizedBox(height: 120),
    Icon(icon, size: 48, color: AppColors.muted),
    const SizedBox(height: 10),
    Center(
      child: Text(text, style: const TextStyle(color: AppColors.muted)),
    ),
  ],
);
