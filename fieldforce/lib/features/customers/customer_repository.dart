import '../../core/network/api_client.dart';

/// تصنيفات العملاء (`customers.specialist`) — نفس ترقيم وتسميات اللوحة.
/// الخادم يرجّع `specialist_name` جاهزاً مع كل عميل؛ هذه الخريطة لبناء
/// قوائم الاختيار فقط (فلتر التصنيف وإضافة عميل).
const customerSpecialists = <int, String>{
  1: 'صيدلية',
  2: 'مركز طبي',
  3: 'مستشفى',
  4: 'طبيب',
};

/// نموذج العميل (v2 /customers).
class Customer {
  final int id;
  final String name;
  final String mobile;
  final String? pharmacyName;
  final String? address;
  final double balance;
  final double creditLimit;
  final int? regionId;
  final String? regionName;
  final int? categoryId; // التخصص الطبي (أطفال، باطنة…)
  final int? specialist; // التصنيف: 1 صيدلية، 2 مركز طبي، 3 مستشفى، 4 طبيب
  final String? specialistName; // اسم التصنيف كما يرسله الخادم
  final double? latitude;
  final double? longitude;
  final int orderCount;
  final int executedVisits;

  const Customer({
    required this.id,
    required this.name,
    required this.mobile,
    this.pharmacyName,
    this.address,
    this.balance = 0,
    this.creditLimit = 0,
    this.regionId,
    this.regionName,
    this.categoryId,
    this.specialist,
    this.specialistName,
    this.latitude,
    this.longitude,
    this.orderCount = 0,
    this.executedVisits = 0,
  });

  factory Customer.fromJson(Map<String, dynamic> j) {
    final region = j['region'] as Map<String, dynamic>?;
    return Customer(
      id: j['id'] as int,
      name: (j['name'] ?? '').toString(),
      mobile: (j['mobile'] ?? '').toString(),
      pharmacyName: j['pharmacy_name']?.toString(),
      address: j['address']?.toString(),
      balance: (j['balance'] ?? 0).toDouble(),
      creditLimit: (j['limit'] ?? 0).toDouble(),
      regionId: region?['id'] as int? ?? j['region_id'] as int?,
      regionName: region?['name']?.toString(),
      categoryId: j['category_id'] is int
          ? j['category_id'] as int
          : int.tryParse('${j['category_id']}'),
      specialist: j['specialist'] is int
          ? j['specialist'] as int
          : int.tryParse('${j['specialist']}'),
      specialistName: j['specialist_name']?.toString(),
      latitude: (j['latitude'] as num?)?.toDouble(),
      longitude: (j['longitude'] as num?)?.toDouble(),
      orderCount: (j['order_count'] ?? 0) as int,
      executedVisits: (j['executed_visits'] ?? 0) as int,
    );
  }

  bool get hasLocation => latitude != null && longitude != null;

  String get area => regionName ?? address ?? '—';
}

class CustomerRepository {
  final _api = ApiClient.instance;

  Future<Paginated<Customer>> list({
    String? search,
    int? categoryId, // التخصص الطبي
    List<int>? specialist, // التصنيف (صيدلية/مركز طبي/مستشفى/طبيب) — متعدد
    List<int>? regionIds, // مناطق (AND مع التخصص)
    int offset = 1,
    int limit = 25,
  }) {
    return _api.getList<Customer>(
      '/customers',
      query: {
        'offset': offset,
        'limit': limit,
        if (search != null && search.isNotEmpty) 'search': search,
        if (categoryId != null) 'category_id': categoryId,
        if (specialist != null && specialist.isNotEmpty)
          'specialist': specialist,
        if (regionIds != null && regionIds.isNotEmpty) 'region_ids': regionIds,
      },
      parse: Customer.fromJson,
    );
  }

  Future<Customer> byId(int id) async {
    final data = await _api.get('/customers/$id');
    return Customer.fromJson(data as Map<String, dynamic>);
  }

  Future<Customer> create(Map<String, dynamic> body) async {
    final data = await _api.post('/customers', body: body);
    return Customer.fromJson(data as Map<String, dynamic>);
  }

  /// تسجيل دفعة/تحصيل على رصيد العميل (add-balance).
  /// مرّر orderId للتحصيل ضد فاتورة معيّنة، وreceiptPath لإرفاق صورة الإيصال.
  Future<void> addBalance({
    required int customerId,
    required int accountId,
    required double amount,
    required String date,
    String? description,
    int? orderId,
    String? receiptPath,
  }) async {
    await _api.post(
      '/customers/add-balance',
      body: {
        'customer_id': customerId,
        'account_id': accountId,
        'amount': amount,
        'date': date,
        if (description != null) 'description': description,
        if (orderId != null) 'order_id': orderId,
      },
      filePath: receiptPath,
      fileField: 'img',
    );
  }
}
