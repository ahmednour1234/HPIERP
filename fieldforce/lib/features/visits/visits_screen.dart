import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/location/location_service.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../customers/customer_repository.dart';
import '../customers/customer_picker.dart';
import '../reference/reference_repository.dart';
import 'visit_repository.dart';

/// شاشة تسجيل نتيجة زيارة — اختيار عميل + نتيجة + ملاحظة + صورة + GPS.
class VisitsScreen extends StatefulWidget {
  final int? customerId;
  const VisitsScreen({super.key, this.customerId});

  @override
  State<VisitsScreen> createState() => _VisitsScreenState();
}

class _VisitsScreenState extends State<VisitsScreen> {
  final _repo = VisitRepository();
  final _custRepo = CustomerRepository();
  final _note = TextEditingController();
  final _picker = ImagePicker();

  Customer? _customer;
  File? _photo;
  int _result = 0;
  bool _saving = false;

  static const _labels = ['تمّت الزيارة', 'المحل مغلق', 'العميل غير موجود'];

  @override
  void initState() {
    super.initState();
    if (widget.customerId != null) _preloadCustomer();
  }

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
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

  Future<void> _save() async {
    if (_customer == null) {
      _snack('اختر العميل أولاً', AppColors.danger);
      return;
    }
    final note = _note.text.trim().isEmpty
        ? _labels[_result]
        : '${_labels[_result]} — ${_note.text.trim()}';
    // الصورة إجبارية.
    if (_photo == null) {
      _snack('إرفاق صورة الزيارة مطلوب', AppColors.danger);
      return;
    }
    setState(() => _saving = true);
    try {
      // الموقع اختياري: نحاول جلبه بسرعة، ولو تعذّر نكمل الحفظ بدونه
      // حتى لا تتعطّل الزيارة (المندوب داخل مبنى/GPS ضعيف).
      double? lat;
      double? lng;
      try {
        final geo = await LocationService.current();
        lat = geo.lat;
        lng = geo.lng;
      } on LocationException {
        // نكمل بدون موقع.
      }
      await _repo.saveResult(
        customerId: _customer!.id,
        note: note,
        lat: lat,
        lng: lng,
        photoPath: _photo?.path,
      );
      if (!mounted) return;
      _snack('تم حفظ الزيارة', AppColors.primaryDeep);
      setState(() {
        _note.clear();
        _photo = null;
        _result = 0;
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
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('الزيارات'),
          bottom: const TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            labelColor: AppColors.primaryDeep,
            unselectedLabelColor: AppColors.muted,
            indicatorColor: AppColors.primary,
            labelStyle:
                TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            tabs: [
              Tab(text: 'تسجيل زيارة'),
              Tab(text: 'المجدولة'),
              Tab(text: 'زياراتي'),
            ],
          ),
        ),
        body: SafeArea(
          child: TabBarView(
            children: [
              _recordTab(),
              const _PlannedVisitsTab(),
              const _MyVisitsTab(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _recordTab() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
      children: [
        const ScreenHeader(
          icon: Icons.location_on_outlined,
          title: 'تسجيل زيارة',
          subtitle: 'سجّل نتيجة زيارتك للعميل',
        ),
            const SizedBox(height: 16),
            _customerBar(),
            const SizedBox(height: 16),
            const Text('نتيجة الزيارة',
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.muted,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            SegmentedTabs(
              items: const ['تمّت', 'مغلق', 'غير موجود'],
              selected: _result,
              onChanged: (i) => setState(() => _result = i),
            ),
            const SizedBox(height: 14),
            LabeledField(
                label: 'ملاحظات', hint: 'ملاحظات الزيارة…', controller: _note),
            const SizedBox(height: 14),
            const Text('صورة الزيارة',
                style: TextStyle(
                    fontSize: 12.5,
                    color: AppColors.inkSoft,
                    fontWeight: FontWeight.w500)),
            const SizedBox(height: 6),
            _photoBox(),
            const SizedBox(height: 14),
            const GpsNote('يُسجّل الموقع والوقت مع الزيارة'),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.4, color: Colors.white))
                  : const Text('حفظ الزيارة'),
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
}

/// زيارة مُثراة ببيانات العميل (منطقة + تخصص) للفلترة والعرض.
class _EnrichedVisit {
  final VisitResult visit;
  final int? regionId;
  final String? regionName;
  final int? categoryId;
  final String? categoryName;
  final String? customerMobile;
  const _EnrichedVisit(this.visit,
      {this.regionId,
      this.regionName,
      this.categoryId,
      this.categoryName,
      this.customerMobile});
}

/// حزمة بيانات التبويب: الزيارات مُثراة + قوائم الفلاتر.
class _VisitsData {
  final List<_EnrichedVisit> visits;
  final List<RefItem> regions; // للفلترة
  final List<RefItem> specialties; // للفلترة
  const _VisitsData(this.visits, this.regions, this.specialties);
}

/// تبويب "زياراتي" — نتائج الزيارات (GET /visits/results) مع فلاتر وتفاصيل.
class _MyVisitsTab extends StatefulWidget {
  const _MyVisitsTab();

  @override
  State<_MyVisitsTab> createState() => _MyVisitsTabState();
}

class _MyVisitsTabState extends State<_MyVisitsTab>
    with AutomaticKeepAliveClientMixin {
  final _repo = VisitRepository();
  late Future<_VisitsData> _future;

  // الفلاتر النشطة
  DateTime? _fromDate;
  DateTime? _toDate;
  int? _regionId;
  int? _categoryId;
  String _search = '';
  final _searchCtrl = TextEditingController();

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<_VisitsData> _load() async {
    // الخادم يرجّع المنطقة والتخصص داخل `customer` مع كل زيارة.
    final visits = await _repo.results();

    final enriched = visits
        .map((v) => _EnrichedVisit(
              v,
              regionId: v.regionId,
              regionName: v.regionName,
              categoryId: v.categoryId,
              categoryName: v.categoryName,
              customerMobile: v.customerMobile,
            ))
        .toList();

    // قوائم الفلاتر تُشتق من الزيارات الفعلية فقط (لا نعرض مناطق/تخصصات فارغة).
    final regionsInUse = <int, String>{};
    final catsInUse = <int, String>{};
    for (final e in enriched) {
      if (e.regionId != null && e.regionName != null) {
        regionsInUse[e.regionId!] = e.regionName!;
      }
      if (e.categoryId != null && e.categoryName != null) {
        catsInUse[e.categoryId!] = e.categoryName!;
      }
    }
    final usedRegions = regionsInUse.entries
        .map((e) => RefItem(e.key, e.value))
        .toList();
    final usedSpecialties = catsInUse.entries
        .map((e) => RefItem(e.key, e.value))
        .toList();

    return _VisitsData(enriched, usedRegions, usedSpecialties);
  }

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  /// تطبيق الفلاتر + البحث على القائمة.
  List<_EnrichedVisit> _apply(List<_EnrichedVisit> all) {
    final q = _search.trim().toLowerCase();
    return all.where((e) {
      // البحث (اسم العميل أو الملاحظة أو الهاتف)
      if (q.isNotEmpty) {
        final hay =
            '${e.visit.customerName} ${e.visit.note} ${e.customerMobile ?? ''}'
                .toLowerCase();
        if (!hay.contains(q)) return false;
      }
      // التاريخ
      final d = DateTime.tryParse(e.visit.createdAt ?? '');
      if (_fromDate != null && d != null) {
        final day = DateTime(d.year, d.month, d.day);
        if (day.isBefore(_fromDate!)) return false;
      }
      if (_toDate != null && d != null) {
        final day = DateTime(d.year, d.month, d.day);
        if (day.isAfter(_toDate!)) return false;
      }
      // المنطقة
      if (_regionId != null && e.regionId != _regionId) return false;
      // التخصص
      if (_categoryId != null && e.categoryId != _categoryId) return false;
      return true;
    }).toList();
  }

  int get _activeFilters =>
      (_fromDate != null || _toDate != null ? 1 : 0) +
      (_regionId != null ? 1 : 0) +
      (_categoryId != null ? 1 : 0);

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return FutureBuilder<_VisitsData>(
      future: _future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Center(
              child: CircularProgressIndicator(color: AppColors.primary));
        }
        if (snap.hasError) {
          final msg = snap.error is ApiException
              ? (snap.error as ApiException).message
              : 'تعذّر تحميل الزيارات';
          return _centered(Icons.cloud_off, msg);
        }
        final data = snap.data!;
        final filtered = _apply(data.visits);
        return Column(
          children: [
            // حقل البحث (اسم العميل / الملاحظة / الهاتف)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              child: TextField(
                controller: _searchCtrl,
                onChanged: (v) => setState(() => _search = v),
                decoration: InputDecoration(
                  hintText: 'ابحث في الزيارات…',
                  prefixIcon:
                      const Icon(Icons.search, color: AppColors.muted),
                  suffixIcon: _search.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.close, size: 18),
                          onPressed: () {
                            _searchCtrl.clear();
                            setState(() => _search = '');
                          },
                        )
                      : null,
                  isDense: true,
                ),
              ),
            ),
            _filterBar(data),
            Expanded(
              child: RefreshIndicator(
                onRefresh: _refresh,
                color: AppColors.primary,
                child: filtered.isEmpty
                    ? _centered(Icons.location_off_outlined,
                        data.visits.isEmpty
                            ? 'لم تسجّل زيارات بعد'
                            : 'لا زيارات مطابقة للبحث/الفلتر')
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
                        itemCount: filtered.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 10),
                        itemBuilder: (context, i) => _visitCard(filtered[i]),
                      ),
              ),
            ),
          ],
        );
      },
    );
  }

  /// شريط الفلاتر (تاريخ · منطقة · تخصص).
  Widget _filterBar(_VisitsData data) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
      child: Row(
        children: [
          Expanded(
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _chip(
                    label: _dateLabel,
                    active: _fromDate != null || _toDate != null,
                    icon: Icons.event_outlined,
                    onTap: _pickDateRange,
                  ),
                  const SizedBox(width: 8),
                  _chip(
                    label: _regionId == null
                        ? 'المنطقة'
                        : data.regions
                            .firstWhere((r) => r.id == _regionId,
                                orElse: () => const RefItem(0, 'المنطقة'))
                            .name,
                    active: _regionId != null,
                    icon: Icons.place_outlined,
                    onTap: () => _pickRef(
                        'اختر المنطقة', data.regions, _regionId,
                        (id) => setState(() => _regionId = id)),
                  ),
                  const SizedBox(width: 8),
                  _chip(
                    label: _categoryId == null
                        ? 'التخصص'
                        : data.specialties
                            .firstWhere((s) => s.id == _categoryId,
                                orElse: () => const RefItem(0, 'التخصص'))
                            .name,
                    active: _categoryId != null,
                    icon: Icons.medical_services_outlined,
                    onTap: () => _pickRef(
                        'اختر التخصص', data.specialties, _categoryId,
                        (id) => setState(() => _categoryId = id)),
                  ),
                ],
              ),
            ),
          ),
          if (_activeFilters > 0)
            TextButton(
              onPressed: () => setState(() {
                _fromDate = null;
                _toDate = null;
                _regionId = null;
                _categoryId = null;
              }),
              child: const Text('مسح', style: TextStyle(fontSize: 12.5)),
            ),
        ],
      ),
    );
  }

  String get _dateLabel {
    String f(DateTime d) => '${d.day}/${d.month}';
    if (_fromDate != null && _toDate != null) {
      return '${f(_fromDate!)} - ${f(_toDate!)}';
    }
    if (_fromDate != null) return 'من ${f(_fromDate!)}';
    if (_toDate != null) return 'حتى ${f(_toDate!)}';
    return 'التاريخ';
  }

  Widget _chip({
    required String label,
    required bool active,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: active ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(20),
          border:
              Border.all(color: active ? AppColors.primary : AppColors.line),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon,
                size: 16,
                color: active ? Colors.white : AppColors.inkSoft),
            const SizedBox(width: 6),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 110),
              child: Text(label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: active ? Colors.white : AppColors.inkSoft)),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickDateRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      initialEntryMode: DatePickerEntryMode.calendarOnly,
      context: context,
      firstDate: DateTime(now.year - 2),
      lastDate: now,
      initialDateRange: (_fromDate != null && _toDate != null)
          ? DateTimeRange(start: _fromDate!, end: _toDate!)
          : null,
    );
    if (range != null) {
      setState(() {
        _fromDate = DateTime(range.start.year, range.start.month, range.start.day);
        _toDate = DateTime(range.end.year, range.end.month, range.end.day);
      });
    }
  }

  void _pickRef(String title, List<RefItem> items, int? current,
      ValueChanged<int?> onPick) {
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
            Padding(
              padding: const EdgeInsets.all(16),
              child: Text(title,
                  style: const TextStyle(
                      fontSize: 16, fontWeight: FontWeight.w700)),
            ),
            ListTile(
              title: const Text('الكل'),
              trailing: current == null
                  ? const Icon(Icons.check_circle, color: AppColors.primary)
                  : null,
              onTap: () {
                onPick(null);
                Navigator.pop(ctx);
              },
            ),
            for (final it in items)
              ListTile(
                title: Text(it.name),
                trailing: current == it.id
                    ? const Icon(Icons.check_circle,
                        color: AppColors.primary)
                    : null,
                onTap: () {
                  onPick(it.id);
                  Navigator.pop(ctx);
                },
              ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  Widget _visitCard(_EnrichedVisit e) {
    final v = e.visit;
    return GestureDetector(
      onTap: () => _showDetails(e),
      child: Container(
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
              child: const Icon(Icons.location_on_rounded,
                  color: AppColors.primaryDeep, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(v.customerName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 14, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 3),
                  Text(v.note,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 12.5, color: AppColors.inkSoft)),
                  if (e.regionName != null || e.categoryName != null) ...[
                    const SizedBox(height: 5),
                    Wrap(
                      spacing: 6,
                      children: [
                        if (e.regionName != null)
                          _tag(Icons.place, e.regionName!),
                        if (e.categoryName != null)
                          _tag(Icons.medical_services, e.categoryName!),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(v.date,
                    style: const TextStyle(
                        fontSize: 11.5, color: AppColors.muted)),
                if (v.time.isNotEmpty)
                  Text(v.time,
                      style: const TextStyle(
                          fontSize: 11, color: AppColors.muted)),
                const SizedBox(height: 6),
                const Icon(Icons.chevron_left,
                    size: 18, color: AppColors.muted),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _tag(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: AppColors.primaryWash,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: AppColors.primaryDeep),
          const SizedBox(width: 3),
          Text(text,
              style: const TextStyle(
                  fontSize: 10.5,
                  color: AppColors.primaryDeep,
                  fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  /// تفاصيل الزيارة في bottom sheet.
  void _showDetails(_EnrichedVisit e) {
    final v = e.visit;
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
                  Container(
                    width: 48,
                    height: 48,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.primaryWash,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: const Icon(Icons.location_on_rounded,
                        color: AppColors.primaryDeep),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(v.customerName,
                            style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800)),
                        Text('${v.date}${v.time.isNotEmpty ? ' · ${v.time}' : ''}',
                            style: const TextStyle(
                                fontSize: 12.5, color: AppColors.muted)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              _detailRow(Icons.notes_rounded, 'ملاحظة الزيارة',
                  v.note.isEmpty ? '—' : v.note),
              if (e.customerMobile != null && e.customerMobile!.isNotEmpty)
                _detailRow(Icons.phone_rounded, 'الهاتف', e.customerMobile!),
              if (e.regionName != null)
                _detailRow(Icons.place_rounded, 'المنطقة', e.regionName!),
              if (e.categoryName != null)
                _detailRow(Icons.medical_services_rounded, 'التخصص',
                    e.categoryName!),
            ],
          ),
        ),
      ),
    );
  }

  Widget _detailRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 9),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 19, color: AppColors.muted),
          const SizedBox(width: 12),
          SizedBox(
            width: 90,
            child: Text(label,
                style: const TextStyle(
                    fontSize: 13, color: AppColors.muted)),
          ),
          Expanded(
            child: Text(value,
                style: const TextStyle(
                    fontSize: 13.5, fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }

  Widget _centered(IconData icon, String text) => ListView(children: [
        const SizedBox(height: 120),
        Icon(icon, size: 48, color: AppColors.muted),
        const SizedBox(height: 10),
        Center(
            child: Text(text,
                style: const TextStyle(color: AppColors.muted))),
      ]);
}

/// تبويب الزيارات المجدولة — عرض الخطة + جدولة زيارة جديدة.
class _PlannedVisitsTab extends StatefulWidget {
  const _PlannedVisitsTab();

  @override
  State<_PlannedVisitsTab> createState() => _PlannedVisitsTabState();
}

/// زيارة مجدولة مُثراة (منطقة + تخصص من العميل).
class _EnrichedPlanned {
  final PlannedVisit visit;
  final int? regionId;
  final String? regionName;
  final int? categoryId;
  final String? categoryName;
  const _EnrichedPlanned(this.visit,
      {this.regionId, this.regionName, this.categoryId, this.categoryName});
}

class _PlannedData {
  final List<_EnrichedPlanned> visits;
  final List<RefItem> regions;
  final List<RefItem> specialties;
  const _PlannedData(this.visits, this.regions, this.specialties);
}

class _PlannedVisitsTabState extends State<_PlannedVisitsTab>
    with AutomaticKeepAliveClientMixin {
  final _repo = VisitRepository();
  final _custRepo = CustomerRepository();
  final _refRepo = ReferenceRepository();
  late Future<_PlannedData> _future;

  // الفلاتر النشطة
  DateTime? _fromDate;
  DateTime? _toDate;
  int? _regionId;
  int? _categoryId;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_PlannedData> _load() async {
    final planned = await _repo.planned();

    // لو أرجع الخادم المنطقة/التخصص داخل `customer`، لا نحتاج دمج العملاء.
    final serverHasData =
        planned.isNotEmpty && planned.every((v) => v.regionName != null);

    Map<int, Customer> byId = const {};
    Map<int, String> catName = const {};
    if (!serverHasData) {
      final customersPage = await _custRepo.list(limit: 200);
      byId = {for (final c in customersPage.items) c.id: c};
      try {
        final specialties = await _refRepo.specialties();
        catName = {for (final s in specialties) s.id: s.name};
      } catch (_) {}
    }

    final enriched = planned.map((v) {
      final c = byId[v.customerId];
      final catId = v.categoryId ?? c?.categoryId;
      return _EnrichedPlanned(
        v,
        regionId: v.regionId ?? c?.regionId,
        regionName: v.regionName ?? c?.regionName,
        categoryId: catId,
        categoryName:
            v.categoryName ?? (catId != null ? catName[catId] : null),
      );
    }).toList();

    // قوائم الفلاتر من الزيارات المجدولة الفعلية فقط.
    final regionsInUse = <int, String>{};
    final catsInUse = <int, String>{};
    for (final e in enriched) {
      if (e.regionId != null && e.regionName != null) {
        regionsInUse[e.regionId!] = e.regionName!;
      }
      if (e.categoryId != null && e.categoryName != null) {
        catsInUse[e.categoryId!] = e.categoryName!;
      }
    }
    return _PlannedData(
      enriched,
      regionsInUse.entries.map((e) => RefItem(e.key, e.value)).toList(),
      catsInUse.entries.map((e) => RefItem(e.key, e.value)).toList(),
    );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  List<_EnrichedPlanned> _apply(List<_EnrichedPlanned> all) {
    return all.where((e) {
      final d = DateTime.tryParse(e.visit.date);
      if (_fromDate != null && d != null) {
        final day = DateTime(d.year, d.month, d.day);
        if (day.isBefore(_fromDate!)) return false;
      }
      if (_toDate != null && d != null) {
        final day = DateTime(d.year, d.month, d.day);
        if (day.isAfter(_toDate!)) return false;
      }
      if (_regionId != null && e.regionId != _regionId) return false;
      if (_categoryId != null && e.categoryId != _categoryId) return false;
      return true;
    }).toList();
  }

  int get _activeFilters =>
      (_fromDate != null || _toDate != null ? 1 : 0) +
      (_regionId != null ? 1 : 0) +
      (_categoryId != null ? 1 : 0);

  String get _dateLabel {
    String f(DateTime d) => '${d.day}/${d.month}';
    if (_fromDate != null && _toDate != null) {
      return '${f(_fromDate!)} - ${f(_toDate!)}';
    }
    if (_fromDate != null) return 'من ${f(_fromDate!)}';
    if (_toDate != null) return 'حتى ${f(_toDate!)}';
    return 'التاريخ';
  }

  Future<void> _schedule() async {
    final customer = await pickCustomer(context);
    if (customer == null || !mounted) return;
    // اختيار التاريخ
    final now = DateTime.now();
    final date = await showDatePicker(
      initialEntryMode: DatePickerEntryMode.calendarOnly,
      context: context,
      initialDate: now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (date == null || !mounted) return;
    final noteCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('جدولة زيارة',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('${customer.name} · ${_fmtDate(date)}',
                style: const TextStyle(fontSize: 13, color: AppColors.muted)),
            const SizedBox(height: 12),
            TextField(
              controller: noteCtrl,
              decoration:
                  const InputDecoration(hintText: 'ملاحظة (اختياري)…'),
            ),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('إلغاء')),
          ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('جدولة')),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await _repo.plan(
        customerId: customer.id,
        date: _fmtDate(date),
        note: noteCtrl.text.trim().isEmpty ? null : noteCtrl.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('تم جدولة الزيارة'),
        backgroundColor: AppColors.primaryDeep,
      ));
      _refresh();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(e.message), backgroundColor: AppColors.danger));
      }
    }
  }

  String _fmtDate(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _schedule,
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('جدولة زيارة',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
      ),
      body: FutureBuilder<_PlannedData>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(
                child: CircularProgressIndicator(color: AppColors.primary));
          }
          final data = snap.data;
          if (data == null) {
            return RefreshIndicator(
              onRefresh: _refresh,
              color: AppColors.primary,
              child: ListView(children: const [
                SizedBox(height: 120),
                Icon(Icons.cloud_off, size: 48, color: AppColors.muted),
                SizedBox(height: 10),
                Center(
                    child: Text('تعذّر تحميل الزيارات المجدولة',
                        style: TextStyle(color: AppColors.muted))),
              ]),
            );
          }
          final filtered = _apply(data.visits);
          return Column(
            children: [
              _plannedFilterBar(data),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: _refresh,
                  color: AppColors.primary,
                  child: filtered.isEmpty
                      ? ListView(children: [
                          const SizedBox(height: 120),
                          const Icon(Icons.event_note_outlined,
                              size: 48, color: AppColors.muted),
                          const SizedBox(height: 10),
                          Center(
                              child: Text(
                                  data.visits.isEmpty
                                      ? 'لا توجد زيارات مجدولة'
                                      : 'لا زيارات مطابقة للفلتر',
                                  style: const TextStyle(
                                      color: AppColors.muted))),
                        ])
                      : ListView.separated(
                          padding:
                              const EdgeInsets.fromLTRB(20, 4, 20, 90),
                          itemCount: filtered.length,
                          separatorBuilder: (_, _) =>
                              const SizedBox(height: 10),
                          itemBuilder: (context, i) =>
                              _plannedCard(filtered[i]),
                        ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  /// شريط الفلاتر للزيارات المجدولة.
  Widget _plannedFilterBar(_PlannedData data) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 6),
      child: Row(
        children: [
          Expanded(
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _chip(
                    label: _dateLabel,
                    active: _fromDate != null || _toDate != null,
                    icon: Icons.event_outlined,
                    onTap: _pickDateRange,
                  ),
                  const SizedBox(width: 8),
                  if (data.regions.isNotEmpty)
                    _chip(
                      label: _regionId == null
                          ? 'المنطقة'
                          : data.regions
                              .firstWhere((r) => r.id == _regionId,
                                  orElse: () => const RefItem(0, 'المنطقة'))
                              .name,
                      active: _regionId != null,
                      icon: Icons.place_outlined,
                      onTap: () => _pickRef('اختر المنطقة', data.regions,
                          _regionId, (id) => setState(() => _regionId = id)),
                    ),
                  if (data.regions.isNotEmpty) const SizedBox(width: 8),
                  if (data.specialties.isNotEmpty)
                    _chip(
                      label: _categoryId == null
                          ? 'التخصص'
                          : data.specialties
                              .firstWhere((s) => s.id == _categoryId,
                                  orElse: () => const RefItem(0, 'التخصص'))
                              .name,
                      active: _categoryId != null,
                      icon: Icons.medical_services_outlined,
                      onTap: () => _pickRef('اختر التخصص', data.specialties,
                          _categoryId,
                          (id) => setState(() => _categoryId = id)),
                    ),
                ],
              ),
            ),
          ),
          if (_activeFilters > 0)
            TextButton(
              onPressed: () => setState(() {
                _fromDate = null;
                _toDate = null;
                _regionId = null;
                _categoryId = null;
              }),
              child: const Text('مسح', style: TextStyle(fontSize: 12.5)),
            ),
        ],
      ),
    );
  }

  Widget _chip({
    required String label,
    required bool active,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: active ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(20),
          border:
              Border.all(color: active ? AppColors.primary : AppColors.line),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon,
                size: 16,
                color: active ? Colors.white : AppColors.inkSoft),
            const SizedBox(width: 6),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 110),
              child: Text(label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: active ? Colors.white : AppColors.inkSoft)),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickDateRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
      initialEntryMode: DatePickerEntryMode.calendarOnly,
      context: context,
      firstDate: DateTime(now.year - 1),
      lastDate: now.add(const Duration(days: 365)),
      initialDateRange: (_fromDate != null && _toDate != null)
          ? DateTimeRange(start: _fromDate!, end: _toDate!)
          : null,
    );
    if (range != null) {
      setState(() {
        _fromDate =
            DateTime(range.start.year, range.start.month, range.start.day);
        _toDate = DateTime(range.end.year, range.end.month, range.end.day);
      });
    }
  }

  void _pickRef(String title, List<RefItem> items, int? current,
      ValueChanged<int?> onPick) {
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
            Padding(
              padding: const EdgeInsets.all(16),
              child: Text(title,
                  style: const TextStyle(
                      fontSize: 16, fontWeight: FontWeight.w700)),
            ),
            ListTile(
              title: const Text('الكل'),
              trailing: current == null
                  ? const Icon(Icons.check_circle, color: AppColors.primary)
                  : null,
              onTap: () {
                onPick(null);
                Navigator.pop(ctx);
              },
            ),
            for (final it in items)
              ListTile(
                title: Text(it.name),
                trailing: current == it.id
                    ? const Icon(Icons.check_circle,
                        color: AppColors.primary)
                    : null,
                onTap: () {
                  onPick(it.id);
                  Navigator.pop(ctx);
                },
              ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  Widget _plannedCard(_EnrichedPlanned e) {
    final v = e.visit;
    return BrandCard(
      padding: const EdgeInsets.all(14),
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => VisitsScreen(customerId: v.customerId),
          ),
        );
      },
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(13)),
            child: const Icon(Icons.event_available_rounded,
                color: AppColors.primary, size: 23),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(v.customerName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w700)),
                const SizedBox(height: 2),
                Text(v.note.isNotEmpty ? v.note : v.customerMobile,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.muted)),
                if (e.regionName != null || e.categoryName != null) ...[
                  const SizedBox(height: 5),
                  Wrap(
                    spacing: 6,
                    children: [
                      if (e.regionName != null)
                        _tag(Icons.place, e.regionName!),
                      if (e.categoryName != null)
                        _tag(Icons.medical_services, e.categoryName!),
                    ],
                  ),
                ],
              ],
            ),
          ),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
                color: AppColors.primaryWash,
                borderRadius: BorderRadius.circular(999)),
            child: Text(v.date,
                style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary)),
          ),
        ],
      ),
    );
  }

  Widget _tag(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: AppColors.primaryWash,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: AppColors.primaryDeep),
          const SizedBox(width: 3),
          Text(text,
              style: const TextStyle(
                  fontSize: 10.5,
                  color: AppColors.primaryDeep,
                  fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
