import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'customer_repository.dart';

/// يفتح نافذة اختيار عميل ويرجّع العميل المختار (أو null لو أُلغيت).
Future<Customer?> pickCustomer(BuildContext context) {
  return showModalBottomSheet<Customer>(
    context: context,
    isScrollControlled: true,
    backgroundColor: AppColors.surface,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (_) => const _CustomerPickerSheet(),
  );
}

class _CustomerPickerSheet extends StatefulWidget {
  const _CustomerPickerSheet();

  @override
  State<_CustomerPickerSheet> createState() => _CustomerPickerSheetState();
}

class _CustomerPickerSheetState extends State<_CustomerPickerSheet> {
  final _repo = CustomerRepository();
  final List<Customer> _items = [];
  String _query = '';
  Timer? _debounce;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _fetch() async {
    setState(() => _loading = true);
    try {
      final res = await _repo.list(search: _query, limit: 30);
      if (!mounted) return;
      setState(() {
        _items
          ..clear()
          ..addAll(res.items);
      });
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      _query = v;
      _fetch();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SizedBox(
        height: MediaQuery.of(context).size.height * 0.75,
        child: Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                  color: AppColors.line,
                  borderRadius: BorderRadius.circular(2)),
            ),
            const Padding(
              padding: EdgeInsets.all(16),
              child: Text('اختر العميل',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: TextField(
                autofocus: false,
                onChanged: _onSearch,
                decoration: const InputDecoration(
                  hintText: 'ابحث بالاسم أو الهاتف…',
                  prefixIcon: Icon(Icons.search, color: AppColors.muted),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: _loading
                  ? const Center(
                      child:
                          CircularProgressIndicator(color: AppColors.primary))
                  : _items.isEmpty
                      ? const Center(
                          child: Text('لا يوجد عملاء',
                              style: TextStyle(color: AppColors.muted)))
                      : ListView.separated(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 20, vertical: 4),
                          itemCount: _items.length,
                          separatorBuilder: (_, _) =>
                              const Divider(height: 1, color: AppColors.line),
                          itemBuilder: (context, i) {
                            final c = _items[i];
                            return AppListRow(
                              thumb: c.name.isEmpty
                                  ? '؟'
                                  : c.name.characters.first,
                              name: c.name,
                              sub: '${c.mobile} · ${c.area}',
                              onTap: () => Navigator.pop(context, c),
                              trailing: const Icon(Icons.chevron_left,
                                  color: AppColors.muted, size: 18),
                            );
                          },
                        ),
            ),
          ],
        ),
      ),
    );
  }
}
