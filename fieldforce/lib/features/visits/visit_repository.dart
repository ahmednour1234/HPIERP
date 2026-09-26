import '../../core/network/api_client.dart';

class VisitResult {
  final int customerId;
  final String customerName;
  final String note;
  final String? createdAt;
  // حقول قد يوفّرها السيرفر الجديد مباشرة (وإلا تبقى null ونثريها من العملاء).
  final int? regionId;
  final String? regionName;
  final int? categoryId;
  final String? categoryName;
  final String? customerMobile;
  final String? imgUrl;

  VisitResult(
    this.customerId,
    this.customerName,
    this.note,
    this.createdAt, {
    this.regionId,
    this.regionName,
    this.categoryId,
    this.categoryName,
    this.customerMobile,
    this.imgUrl,
  });

  factory VisitResult.fromJson(Map<String, dynamic> j) {
    final customer = j['customer'] as Map<String, dynamic>?;
    final region = customer?['region'] as Map<String, dynamic>?;
    final category = customer?['category'] as Map<String, dynamic>?;
    int? asIntN(dynamic v) =>
        v == null ? null : (v is int ? v : int.tryParse('$v'));
    return VisitResult(
      (j['customer_id'] ?? 0) is int
          ? (j['customer_id'] ?? 0) as int
          : int.tryParse('${j['customer_id']}') ?? 0,
      (customer?['name'] ?? 'عميل').toString(),
      (j['note'] ?? '').toString(),
      j['created_at']?.toString(),
      regionId: region?['id'] as int? ?? asIntN(customer?['region_id']),
      regionName: region?['name']?.toString(),
      categoryId: category?['id'] as int? ?? asIntN(customer?['category_id']),
      categoryName: category?['name']?.toString(),
      customerMobile: customer?['mobile']?.toString(),
      imgUrl: j['img_url']?.toString(),
    );
  }

  String get date => createdAt?.split('T').first ?? '';
  String get time {
    if (createdAt == null || !createdAt!.contains('T')) return '';
    final t = createdAt!.split('T')[1];
    return t.length >= 5 ? t.substring(0, 5) : '';
  }
}

/// زيارة مجدولة (خطة).
class PlannedVisit {
  final int id;
  final int customerId;
  final String customerName;
  final String customerMobile;
  final String date;
  final String note;
  // تأتي من `customer` المتداخل لو وفّرها الخادم، وإلا تبقى null.
  final int? regionId;
  final String? regionName;
  final int? categoryId;
  final String? categoryName;

  PlannedVisit(
    this.id,
    this.customerId,
    this.customerName,
    this.customerMobile,
    this.date,
    this.note, {
    this.regionId,
    this.regionName,
    this.categoryId,
    this.categoryName,
  });

  factory PlannedVisit.fromJson(Map<String, dynamic> j) {
    final c = j['customer'] as Map<String, dynamic>?;
    final region = c?['region'] as Map<String, dynamic>?;
    final category = c?['category'] as Map<String, dynamic>?;
    int? asIntN(dynamic v) =>
        v == null ? null : (v is int ? v : int.tryParse('$v'));
    return PlannedVisit(
      (j['id'] ?? 0) as int,
      (j['customer_id'] ?? 0) is int
          ? (j['customer_id'] ?? 0) as int
          : int.tryParse('${j['customer_id']}') ?? 0,
      (c?['name'] ?? 'عميل').toString(),
      (c?['mobile'] ?? '').toString(),
      (j['date'] ?? '').toString(),
      (j['note'] ?? '').toString(),
      regionId: region?['id'] as int? ?? asIntN(c?['region_id']),
      regionName: region?['name']?.toString(),
      categoryId: category?['id'] as int? ?? asIntN(c?['category_id']),
      categoryName: category?['name']?.toString(),
    );
  }
}

class VisitRepository {
  final _api = ApiClient.instance;

  /// الزيارات المجدولة (GET /visits).
  Future<List<PlannedVisit>> planned({String? date}) async {
    final res = await _api.getList<PlannedVisit>(
      '/visits',
      query: {'limit': 50, if (date != null) 'date': date},
      parse: PlannedVisit.fromJson,
    );
    return res.items;
  }

  /// تسجيل نتيجة زيارة (POST /visits/results).
  /// ملاحظة: خط الطول اسمه `lang` في الـ API. photoPath يُرسل multipart.
  Future<void> saveResult({
    required int customerId,
    required String note,
    double? lat,
    double? lng,
    String? photoPath,
  }) async {
    await _api.post(
      '/visits/results',
      body: {
        'customer_id': customerId,
        'note': note,
        if (lat != null) 'lat': lat,
        if (lng != null) 'lang': lng,
      },
      filePath: photoPath,
      fileField: 'img',
    );
  }

  /// جدولة زيارة (POST /visits).
  Future<void> plan({
    required int customerId,
    required String date,
    String? note,
  }) async {
    await _api.post('/visits', body: {
      'customer_id': customerId,
      'date': date,
      if (note != null) 'note': note,
    });
  }

  /// نتائج الزيارات. تقبل فلاتر (يطبّقها السيرفر الجديد، ويتجاهلها القديم).
  Future<List<VisitResult>> results({
    String? from,
    String? to,
    int? regionId,
    int? categoryId,
    String? search,
    int limit = 200, // حد مرتفع لإحضار كل زيارات الفترة
  }) async {
    final data = await _api.get('/visits/results', query: {
      if (from != null) 'from': from,
      if (to != null) 'to': to,
      if (regionId != null) 'region_id': regionId,
      if (categoryId != null) 'category_id': categoryId,
      if (search != null && search.isNotEmpty) 'search': search,
      'limit': limit,
    });
    return (data as List)
        .map((e) => VisitResult.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
