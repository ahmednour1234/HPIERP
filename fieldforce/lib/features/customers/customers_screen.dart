import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'customer_repository.dart';
import '../reference/reference_repository.dart';

/// شاشة العملاء — قائمة من الخادم + بحث + فتح تفاصيل.
class CustomersScreen extends StatefulWidget {
  const CustomersScreen({super.key});

  @override
  State<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends State<CustomersScreen> {
  final _repo = CustomerRepository();
  final _refRepo = ReferenceRepository();
  final _scroll = ScrollController();

  final List<Customer> _items = [];
  String _query = '';
  Timer? _debounce;

  // الفلاتر
  List<RefItem> _specialties = [];
  List<RefItem> _regions = [];
  int? _fSpecialty;
  int? _fRegion;
  // التصنيف: اختيار متعدد (الخادم يقبل specialist[]=1&specialist[]=4).
  final Set<int> _fTypes = {};
  int get _activeFilters =>
      (_fSpecialty != null ? 1 : 0) +
      (_fRegion != null ? 1 : 0) +
      (_fTypes.isEmpty ? 0 : 1);

  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _fetch(reset: true);
    _loadFilters();
    _scroll.addListener(() {
      if (_scroll.position.pixels >=
              _scroll.position.maxScrollExtent - 300 &&
          !_loadingMore &&
          _hasMore) {
        _fetch();
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
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
      // الفلترة كلها من الخادم — `specialist` مدعوم ويعمل AND مع باقي الفلاتر.
      final res = await _repo.list(
        search: _query,
        categoryId: _fSpecialty,
        specialist: _fTypes.isEmpty ? null : _fTypes.toList(),
        regionIds: _fRegion != null ? [_fRegion!] : null,
        offset: _page,
        limit: 25,
      );
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

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      _query = v;
      _fetch(reset: true);
    });
  }

  Future<void> _loadFilters() async {
    // كل قائمة لوحدها — فشل واحدة ما يخفيش التانية.
    // تخصصات المندوب أولاً — ولو مالوش تخصصات مسنَدة تظهر كل التخصصات.
    _refRepo.mySpecialties().then((list) {
      if (mounted) setState(() => _specialties = list);
    }).catchError((_) {});
    _refRepo.regions().then((list) {
      if (mounted) setState(() => _regions = list);
    }).catchError((_) {});
  }

  void _openFilters() {
    int? sp = _fSpecialty;
    int? rg = _fRegion;
    final tp = Set<int>.from(_fTypes);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(
              20, 18, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('تصفية العملاء',
                      style: TextStyle(
                          fontSize: 16, fontWeight: FontWeight.w700)),
                  TextButton(
                    onPressed: () => setSheet(() {
                      sp = null;
                      rg = null;
                      tp.clear();
                    }),
                    child: const Text('مسح الكل'),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              _typeChips(tp, setSheet),
              const SizedBox(height: 14),
              _sheetDropdown('التخصص الطبي', _specialties, sp,
                  (v) => setSheet(() => sp = v)),
              const SizedBox(height: 14),
              _sheetDropdown(
                  'المنطقة', _regions, rg, (v) => setSheet(() => rg = v)),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () {
                  setState(() {
                    _fSpecialty = sp;
                    _fRegion = rg;
                    _fTypes
                      ..clear()
                      ..addAll(tp);
                  });
                  Navigator.pop(ctx);
                  _fetch(reset: true);
                },
                child: const Text('تطبيق'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// التصنيف — اختيار متعدد بشرائح؛ فارغ = الكل.
  Widget _typeChips(Set<int> selected, StateSetter setSheet) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 8, right: 2),
          child: Row(
            children: [
              const Text('التصنيف',
                  style: TextStyle(
                      fontSize: 12.5,
                      color: AppColors.inkSoft,
                      fontWeight: FontWeight.w500)),
              const Spacer(),
              Text(
                selected.isEmpty ? 'الكل' : '${selected.length} مختار',
                style: const TextStyle(fontSize: 11.5, color: AppColors.muted),
              ),
            ],
          ),
        ),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: customerSpecialists.entries.map((e) {
            final on = selected.contains(e.key);
            return InkWell(
              onTap: () => setSheet(() {
                if (on) {
                  selected.remove(e.key);
                } else {
                  selected.add(e.key);
                }
              }),
              borderRadius: BorderRadius.circular(999),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                decoration: BoxDecoration(
                  color: on ? AppColors.primary : AppColors.surface,
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                      color: on ? AppColors.primary : AppColors.line),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (on) ...[
                      const Icon(Icons.check, size: 14, color: Colors.white),
                      const SizedBox(width: 5),
                    ],
                    Text(
                      e.value,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: on ? FontWeight.w700 : FontWeight.w500,
                        color: on ? Colors.white : AppColors.inkSoft,
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }

  Widget _sheetDropdown(String label, List<RefItem> items, int? value,
      ValueChanged<int?> onChanged) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 6, right: 2),
          child: Text(label,
              style: const TextStyle(
                  fontSize: 12.5,
                  color: AppColors.inkSoft,
                  fontWeight: FontWeight.w500)),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: AppColors.line),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<int?>(
              isExpanded: true,
              value: value,
              hint: const Text('الكل',
                  style: TextStyle(color: AppColors.muted, fontSize: 14)),
              items: [
                const DropdownMenuItem(value: null, child: Text('الكل')),
                ...items.map((e) => DropdownMenuItem(
                    value: e.id,
                    child: Text(e.name,
                        style: const TextStyle(fontSize: 14)))),
              ],
              onChanged: onChanged,
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('العملاء')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/add-customer'),
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.person_add_alt, color: Colors.white),
        label: const Text('عميل جديد',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
              child: Column(
                children: [
                  const ScreenHeader(
                    icon: Icons.groups_outlined,
                    title: 'العملاء',
                    subtitle: 'كل نقاط البيع المسجّلة',
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          onChanged: _onSearch,
                          decoration: const InputDecoration(
                            hintText: 'ابحث بالاسم أو رقم الهاتف…',
                            prefixIcon:
                                Icon(Icons.search, color: AppColors.muted),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      GestureDetector(
                        onTap: _openFilters,
                        child: Container(
                          height: 54,
                          width: 54,
                          decoration: BoxDecoration(
                            color: _activeFilters > 0
                                ? AppColors.primary
                                : AppColors.surface,
                            borderRadius: BorderRadius.circular(15),
                            border: Border.all(
                                color: _activeFilters > 0
                                    ? AppColors.primary
                                    : AppColors.line),
                          ),
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              Icon(Icons.tune_rounded,
                                  color: _activeFilters > 0
                                      ? Colors.white
                                      : AppColors.inkSoft),
                              if (_activeFilters > 0)
                                Positioned(
                                  top: 8,
                                  right: 8,
                                  child: Container(
                                    width: 16,
                                    height: 16,
                                    decoration: const BoxDecoration(
                                        color: AppColors.warn,
                                        shape: BoxShape.circle),
                                    child: Center(
                                      child: Text('$_activeFilters',
                                          style: const TextStyle(
                                              color: Colors.white,
                                              fontSize: 10,
                                              fontWeight: FontWeight.w700)),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ],
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
      final msg =
          _error is ApiException ? (_error as ApiException).message : 'تعذّر التحميل';
      return _centered(Icons.cloud_off, msg, retry: true);
    }
    if (_items.isEmpty) {
      return _centered(
          Icons.search_off,
          _activeFilters > 0
              ? 'لا يوجد عملاء بهذه الفلاتر'
              : 'لا يوجد عملاء');
    }
    return RefreshIndicator(
      onRefresh: () => _fetch(reset: true),
      color: AppColors.primary,
      child: ListView.separated(
        controller: _scroll,
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 90),
        itemCount: _items.length + (_hasMore ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (context, i) {
          if (i >= _items.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(
                  child: CircularProgressIndicator(color: AppColors.primary)),
            );
          }
          return _customerCard(_items[i]);
        },
      ),
    );
  }

  Widget _customerCard(Customer c) {
    final hasBalance = c.balance > 0;
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14),
        child: AppListRow(
          thumb: c.name.isEmpty ? '؟' : c.name.characters.first,
          name: c.name,
          // اسم التصنيف يأتي جاهزاً من الخادم (specialist_name).
          sub: [
            c.area,
            if (c.specialistName != null && c.specialistName!.isNotEmpty)
              c.specialistName!,
            '${c.orderCount} طلب',
          ].join(' · '),
          onTap: () => context.push('/customer', extra: c),
          trailing: Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisSize: MainAxisSize.min,
            children: [
              if (hasBalance)
                MoneyText(_money(c.balance),
                    size: 13, color: AppColors.danger)
              else
                StatusBadge.good('خالص'),
              const SizedBox(height: 4),
              const Icon(Icons.chevron_left,
                  color: AppColors.muted, size: 18),
            ],
          ),
        ),
      ),
    );
  }

  Widget _centered(IconData icon, String text, {bool retry = false}) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 48, color: AppColors.muted),
          const SizedBox(height: 10),
          Text(text, style: const TextStyle(color: AppColors.muted)),
          if (retry) ...[
            const SizedBox(height: 14),
            OutlinedButton(
              onPressed: () => _fetch(reset: true),
              child: const Text('إعادة المحاولة'),
            ),
          ],
        ],
      ),
    );
  }

  String _money(double v) => v
      .toStringAsFixed(v == v.roundToDouble() ? 0 : 2)
      .replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => ',');
}
