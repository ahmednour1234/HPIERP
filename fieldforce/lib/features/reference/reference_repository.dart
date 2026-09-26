import '../../core/network/api_client.dart';
import '../../core/network/api_exception.dart';

/// عنصر مرجعي بسيط (id + اسم) — تخصص، منطقة، فئة.
class RefItem {
  final int id;
  final String name;
  const RefItem(this.id, this.name);

  factory RefItem.fromJson(Map<String, dynamic> j) =>
      RefItem(j['id'] as int, (j['name'] ?? j['name_en'] ?? '').toString());

  @override
  bool operator ==(Object other) => other is RefItem && other.id == id;
  @override
  int get hashCode => id;
}

/// منطقة (توافق مع الكود القديم) — نفس RefItem.
typedef Region = RefItem;

/// فئة منتجات (type=1) — للتصفية في المبيعات/الموارد.
class Category {
  final int id;
  final String name;
  const Category(this.id, this.name,
      [this.parentId = 0, this.status = true, this.imageUrl]);
  final int parentId;
  final bool status;
  final String? imageUrl;

  factory Category.fromJson(Map<String, dynamic> j) => Category(
        j['id'] as int,
        (j['name'] ?? '').toString(),
        (j['parent_id'] ?? 0) is int
            ? (j['parent_id'] ?? 0) as int
            : int.tryParse('${j['parent_id']}') ?? 0,
        // status قد يأتي 1/0 أو true/false.
        (j['status'] ?? 1).toString() == '1' ||
            (j['status'] ?? true) == true,
        j['image_url']?.toString(),
      );
}

class ReferenceRepository {
  final _api = ApiClient.instance;

  /// التخصصات الطبية — الأساسي `/specialties`، ولو غير منشور (404) نرجع
  /// إلى `/categories` + فلترة type=0 (توافق مع السيرفر القديم).
  Future<List<RefItem>> specialties() async {
    try {
      final data = await _api.get('/specialties');
      return (data as List)
          .cast<Map<String, dynamic>>()
          .map((c) => RefItem(c['id'] as int, (c['name'] ?? '').toString()))
          .toList();
    } on ApiException catch (e) {
      if (e.statusCode != 404) rethrow;
      // fallback: القديم
      final data = await _api.get('/categories', query: {'limit': 200});
      return (data as List)
          .cast<Map<String, dynamic>>()
          .where((c) => _asInt(c['type']) == 0)
          .map((c) => RefItem(c['id'] as int, (c['name'] ?? '').toString()))
          .toList();
    }
  }

  static int _asInt(dynamic v) =>
      v is int ? v : int.tryParse('${v ?? 0}') ?? 0;

  /// فئات المنتجات — الأساسي `/product-categories`، ولو 404 نرجع لـ
  /// `/categories` + فلترة type=1.
  Future<List<Category>> productCategories() async {
    try {
      final data = await _api.get('/product-categories');
      return (data as List)
          .cast<Map<String, dynamic>>()
          .map((c) => Category.fromJson(c))
          .toList();
    } on ApiException catch (e) {
      if (e.statusCode != 404) rethrow;
      final data = await _api.get('/categories', query: {'limit': 200});
      return (data as List)
          .cast<Map<String, dynamic>>()
          .where((c) => _asInt(c['type']) == 1)
          .map((c) => Category.fromJson(c))
          .toList();
    }
  }

  /// أعلى مستوى الفئات (توافق قديم) — نفس product-categories.
  Future<List<Category>> topCategories() => productCategories();

  /// فئات المنتجات المسنَدة للمندوب فقط (GET /categories/mine).
  /// `status: 1` يستبعد الفئات المعطّلة — المندوب لا يبيعها أصلاً.
  /// لو المسار غير منشور (404) نرجع إلى كل فئات النظام.
  Future<List<Category>> myCategories() async {
    try {
      final data =
          await _api.get('/categories/mine', query: {'type': 1, 'status': 1});
      return (data as List)
          .cast<Map<String, dynamic>>()
          .map(Category.fromJson)
          .toList();
    } on ApiException catch (e) {
      if (e.statusCode != 404) rethrow;
      return productCategories();
    }
  }

  /// التخصصات الطبية المسنَدة للمندوب (GET /categories/mine?type=0).
  /// لو المندوب مالوش تخصصات مسنَدة (قائمة فاضية) أو المسار غير منشور،
  /// نرجع لكل التخصصات عشان الفلتر ما يبقاش فاضي.
  Future<List<RefItem>> mySpecialties() async {
    try {
      final data =
          await _api.get('/categories/mine', query: {'type': 0, 'status': 1});
      final list = (data as List)
          .cast<Map<String, dynamic>>()
          .map((c) => RefItem(c['id'] as int, (c['name'] ?? '').toString()))
          .toList();
      return list.isEmpty ? specialties() : list;
    } on ApiException catch (e) {
      if (e.statusCode != 404) rethrow;
      return specialties();
    }
  }

  /// مناطق المندوب.
  Future<List<RefItem>> regions() async {
    final data = await _api.get('/regions/mine');
    return (data as List)
        .map((e) => RefItem.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// توافق قديم.
  Future<List<RefItem>> myRegions() => regions();
}
