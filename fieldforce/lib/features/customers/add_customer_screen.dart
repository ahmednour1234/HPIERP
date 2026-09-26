import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/location/location_service.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../reference/reference_repository.dart';
import 'customer_repository.dart';

/// شاشة إضافة عميل — POST /customers مع region_id والإحداثيات.
class AddCustomerScreen extends StatefulWidget {
  const AddCustomerScreen({super.key});

  @override
  State<AddCustomerScreen> createState() => _AddCustomerScreenState();
}

class _AddCustomerScreenState extends State<AddCustomerScreen> {
  final _repo = CustomerRepository();
  final _refRepo = ReferenceRepository();
  final _name = TextEditingController();
  final _mobile = TextEditingController();
  final _pharmacy = TextEditingController();
  final _address = TextEditingController();

  List<Region> _regions = [];
  Region? _region;
  // إحداثيات موقع العميل — تُقرأ فعلياً من الـ GPS لحظة الحفظ.
  double? _lat;
  double? _lng;

  // نوع الجهة (specialist): قائمة ثابتة 1-4.
  int? _placeType;
  static const _placeTypes = customerSpecialists;

  // التخصص الطبي (category_id): من /specialties الحقيقي.
  List<RefItem> _specialties = [];
  RefItem? _specialty;

  bool _saving = false;
  Map<String, List<String>>? _errors;

  @override
  void initState() {
    super.initState();
    _loadRegions();
    _loadSpecialties();
  }

  Future<void> _loadSpecialties() async {
    try {
      final list = await _refRepo.specialties();
      if (mounted) setState(() => _specialties = list);
    } catch (_) {}
  }

  @override
  void dispose() {
    _name.dispose();
    _mobile.dispose();
    _pharmacy.dispose();
    _address.dispose();
    super.dispose();
  }

  Future<void> _loadRegions() async {
    try {
      final regs = await _refRepo.myRegions();
      if (!mounted) return;
      setState(() {
        _regions = regs;
        _region = regs.isNotEmpty ? regs.first : null;
      });
    } catch (_) {}
  }

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _errors = null;
    });
    try {
      // نقرأ موقع المندوب (= موقع المحل) لحظة الحفظ.
      final geo = await LocationService.current();
      if (mounted) setState(() { _lat = geo.lat; _lng = geo.lng; });
      await _repo.create({
        'name': _name.text.trim(),
        'mobile': _mobile.text.trim(),
        if (_region != null) 'region_id': _region!.id,
        if (_placeType != null) 'specialist': _placeType,
        if (_specialty != null) 'category_id': _specialty!.id,
        if (_pharmacy.text.trim().isNotEmpty)
          'pharmacy_name': _pharmacy.text.trim(),
        if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(),
        // إحداثيات كأرقام (مش نصوص) حسب دليل الربط.
        'latitude': geo.lat,
        'longitude': geo.lng,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('تم حفظ العميل'),
        backgroundColor: AppColors.primaryDeep,
      ));
      context.pop();
    } on LocationException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(e.message),
        backgroundColor: AppColors.danger,
      ));
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _errors = e.errors);
      if (e.errors == null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(e.message),
          backgroundColor: AppColors.danger,
        ));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('عميل جديد')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
          children: [
            const ScreenHeader(
              icon: Icons.person_add_alt,
              title: 'عميل جديد',
              subtitle: 'سجّل نقطة بيع جديدة',
            ),
            const SizedBox(height: 18),
            _field('اسم العميل', 'الاسم…', _name, 'name'),
            const SizedBox(height: 14),
            _field('رقم الهاتف', '01X XXXX XXXX', _mobile, 'mobile',
                keyboard: TextInputType.phone),
            const SizedBox(height: 14),
            _field('اسم الصيدلية / مخزن', 'صيدلية / مخزن…', _pharmacy,
                'pharmacy_name'),
            const SizedBox(height: 14),
            _regionPicker(),
            const SizedBox(height: 14),
            _intDropdown('نوع الجهة', 'اختر النوع', _placeTypes, _placeType,
                (v) => setState(() => _placeType = v)),
            const SizedBox(height: 14),
            _specialtyPicker(),
            const SizedBox(height: 14),
            _field('العنوان', 'العنوان…', _address, 'address'),
            const SizedBox(height: 14),
            const Text('الموقع على الخريطة',
                style: TextStyle(
                    fontSize: 12.5,
                    color: AppColors.inkSoft,
                    fontWeight: FontWeight.w500)),
            const SizedBox(height: 6),
            _mapBox(),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.4, color: Colors.white))
                  : const Text('حفظ العميل'),
            ),
          ],
        ),
      ),
    );
  }

  /// قائمة منسدلة لقيمة int من خريطة قيم.
  Widget _intDropdown(String label, String hint, Map<int, String> options,
      int? value, ValueChanged<int?> onChanged) {
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
            child: DropdownButton<int>(
              isExpanded: true,
              value: value,
              hint: Text(hint,
                  style: const TextStyle(color: AppColors.muted, fontSize: 14)),
              items: options.entries
                  .map((e) => DropdownMenuItem(
                        value: e.key,
                        child: Text(e.value,
                            style: const TextStyle(fontSize: 14)),
                      ))
                  .toList(),
              onChanged: onChanged,
            ),
          ),
        ),
      ],
    );
  }

  Widget _specialtyPicker() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(bottom: 6, right: 2),
          child: Text('التخصص الطبي',
              style: TextStyle(
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
            child: DropdownButton<RefItem>(
              isExpanded: true,
              value: _specialty,
              hint: const Text('اختر التخصص',
                  style: TextStyle(color: AppColors.muted, fontSize: 14)),
              items: _specialties
                  .map((s) => DropdownMenuItem(
                        value: s,
                        child: Text(s.name,
                            style: const TextStyle(fontSize: 14)),
                      ))
                  .toList(),
              onChanged: (s) => setState(() => _specialty = s),
            ),
          ),
        ),
      ],
    );
  }

  Widget _regionPicker() {
    final err = _errors?['region_id']?.first;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(bottom: 6, right: 2),
          child: Text('المنطقة',
              style: TextStyle(
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
            child: DropdownButton<Region>(
              isExpanded: true,
              value: _region,
              hint: const Text('اختر المنطقة',
                  style: TextStyle(color: AppColors.muted, fontSize: 14)),
              items: _regions
                  .map((r) => DropdownMenuItem(
                        value: r,
                        child: Text(r.name,
                            style: const TextStyle(fontSize: 14)),
                      ))
                  .toList(),
              onChanged: (r) => setState(() => _region = r),
            ),
          ),
        ),
        if (err != null)
          Padding(
            padding: const EdgeInsets.only(top: 6, right: 2),
            child: Text(err,
                style: const TextStyle(color: AppColors.danger, fontSize: 12)),
          ),
      ],
    );
  }

  Widget _field(String label, String hint, TextEditingController ctrl,
      String errorKey,
      {TextInputType? keyboard}) {
    final err = _errors?[errorKey]?.first;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        LabeledField(
            label: label, hint: hint, controller: ctrl, keyboardType: keyboard),
        if (err != null)
          Padding(
            padding: const EdgeInsets.only(top: 6, right: 2),
            child: Text(err,
                style: const TextStyle(color: AppColors.danger, fontSize: 12)),
          ),
      ],
    );
  }

  Widget _mapBox() {
    return ClipRRect(
      borderRadius: BorderRadius.circular(14),
      child: Container(
        height: 120,
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: [Color(0xFFDCE7F6), Color(0xFFEEF2F6)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          border: Border.all(color: AppColors.line),
        ),
        child: Stack(
          children: [
            Positioned.fill(child: CustomPaint(painter: _MapPainter())),
            const Center(
              child: Icon(Icons.location_on_rounded,
                  color: AppColors.primary, size: 32),
            ),
            Positioned(
              bottom: 8,
              right: 12,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: GpsNote(_lat == null
                    ? 'يُلتقط الموقع عند الحفظ'
                    : '${_lat!.toStringAsFixed(4)}, ${_lng!.toStringAsFixed(4)}'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// رسم شوارع بسيطة كخلفية للخريطة.
class _MapPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final road = Paint()
      ..color = const Color(0xFFC3CCD6)
      ..strokeWidth = 6
      ..style = PaintingStyle.stroke;
    final path = Path()
      ..moveTo(0, size.height * .6)
      ..quadraticBezierTo(size.width * .3, size.height * .3,
          size.width * .55, size.height * .5)
      ..quadraticBezierTo(size.width * .8, size.height * .68,
          size.width, size.height * .42);
    canvas.drawPath(path, road);

    final cross = Paint()
      ..color = const Color(0xFFCFD6DF)
      ..strokeWidth = 4;
    canvas.drawLine(Offset(size.width * .18, 0),
        Offset(size.width * .28, size.height), cross);
    canvas.drawLine(Offset(size.width * .68, 0),
        Offset(size.width * .78, size.height), cross);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
