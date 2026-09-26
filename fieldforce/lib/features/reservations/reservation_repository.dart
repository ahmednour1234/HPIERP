import '../../core/network/api_client.dart';

/// عنصر في طلب الحجز.
class ReservationItem {
  final int productId;
  final String productName;
  final String productCode;
  final num quantity;
  final double lineTotal;

  ReservationItem(this.productId, this.productName, this.productCode,
      this.quantity, this.lineTotal);

  factory ReservationItem.fromJson(Map<String, dynamic> j) => ReservationItem(
        (j['product_id'] ?? 0) as int,
        (j['product_name'] ?? '').toString(),
        (j['product_code'] ?? '').toString(),
        (j['quantity'] ?? 0) as num,
        (j['line_total'] ?? 0).toDouble(),
      );
}

/// طلب حجز (request) منتجات.
class Reservation {
  final int id;
  final String statusText;
  final String date;
  final double total;
  final int itemsCount;
  final List<ReservationItem> items;

  Reservation({
    required this.id,
    required this.statusText,
    required this.date,
    required this.total,
    required this.itemsCount,
    required this.items,
  });

  factory Reservation.fromJson(Map<String, dynamic> j) {
    final items = j['items'] as List? ?? [];
    return Reservation(
      id: j['id'] as int,
      statusText: (j['status_text'] ?? 'pending').toString(),
      date: (j['date'] ?? '').toString(),
      total: (j['total'] ?? 0).toDouble(),
      itemsCount: (j['items_count'] ?? items.length) as int,
      items: items
          .map((e) => ReservationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  bool get isPending => statusText == 'pending';
  bool get isApproved => statusText == 'approved';
}

/// صنف داخل أمر صرف — يحمل السعر والرصيد بعد الصرف.
class IssuedItem {
  final int productId;
  final String productName;
  final num quantity;
  final num balance; // رصيد المخزن بعد الصرف
  final double price;
  final double lineTotal;

  const IssuedItem({
    required this.productId,
    required this.productName,
    required this.quantity,
    this.balance = 0,
    this.price = 0,
    this.lineTotal = 0,
  });

  factory IssuedItem.fromJson(Map<String, dynamic> j) => IssuedItem(
        productId: (j['product_id'] ?? 0) as int,
        productName: (j['product_name'] ?? '').toString(),
        quantity: (j['quantity'] ?? 0) as num,
        balance: (j['balance'] ?? 0) as num,
        price: (j['price'] ?? 0).toDouble(),
        lineTotal: (j['line_total'] ?? 0).toDouble(),
      );
}

/// أمر صرف صادر للمندوب (GET /reservations/issued) — للعرض فقط.
class IssuedOrder {
  final int id;
  final String typeText; // issue
  final String statusText; // executed | pending …
  final int itemsCount;
  final double total;
  final String? createdAt;
  final List<IssuedItem> items;

  const IssuedOrder({
    required this.id,
    this.typeText = 'issue',
    this.statusText = '',
    this.itemsCount = 0,
    this.total = 0,
    this.createdAt,
    this.items = const [],
  });

  bool get isExecuted => statusText == 'executed';

  /// إجمالي الكميات المصروفة في الأمر.
  num get totalQuantity => items.fold<num>(0, (s, i) => s + i.quantity);

  String get statusLabel => switch (statusText) {
        'executed' => 'منفّذ',
        'pending' => 'معلّق',
        'canceled' || 'cancelled' => 'ملغي',
        _ => statusText,
      };

  factory IssuedOrder.fromJson(Map<String, dynamic> j) {
    final raw = j['items'] as List? ?? const [];
    int asInt(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    return IssuedOrder(
      id: asInt(j['id']),
      typeText: (j['type_text'] ?? 'issue').toString(),
      statusText: (j['status_text'] ?? '').toString(),
      itemsCount: asInt(j['items_count'] ?? raw.length),
      total: (j['total'] ?? 0).toDouble(),
      createdAt: (j['created_at'] ?? j['date'])?.toString(),
      items: raw
          .cast<Map<String, dynamic>>()
          .map(IssuedItem.fromJson)
          .toList(),
    );
  }
}

class ReservationRepository {
  final _api = ApiClient.instance;

  /// إرسال طلب حجز — data: [{product_id, stock}] + ملاحظة اختيارية.
  Future<Reservation> submit(Map<int, num> items, {String? note}) async {
    final data = items.entries
        .where((e) => e.value > 0)
        .map((e) => {'product_id': e.key, 'stock': e.value})
        .toList();
    final res = await _api.post('/reservations', body: {
      'data': data,
      if (note != null && note.isNotEmpty) 'note': note,
    });
    return Reservation.fromJson(res as Map<String, dynamic>);
  }

  /// طلبات الحجز السابقة.
  Future<List<Reservation>> list() async {
    final res = await _api.getList<Reservation>('/reservations',
        query: {'limit': 50}, parse: Reservation.fromJson);
    return res.items;
  }

  /// أوامر الصرف الصادرة للمندوب (GET /reservations/issued) — عرض فقط.
  /// `from`/`to` بصيغة YYYY-MM-DD، و`productId` لتصفية صنف بعينه.
  Future<List<IssuedOrder>> issued({
    String? from,
    String? to,
    int? productId,
    int limit = 50,
  }) async {
    final res = await _api.getList<IssuedOrder>(
      '/reservations/issued',
      query: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
        if (productId != null) 'product_id': productId,
        'limit': limit,
      },
      parse: IssuedOrder.fromJson,
    );
    return res.items;
  }
}
