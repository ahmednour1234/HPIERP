import '../../core/network/api_client.dart';

/// سطر في الطلب.
class OrderLine {
  final String productName;
  final String productCode;
  final num quantity;
  final double price;
  final double lineTotal;
  final double discount; // discount_on_product (قيمة محسوبة)
  final String? discountType; // percent | amount
  final double tax;

  OrderLine(this.productName, this.productCode, this.quantity, this.price,
      this.lineTotal,
      {this.discount = 0, this.discountType, this.tax = 0});

  factory OrderLine.fromJson(Map<String, dynamic> j) => OrderLine(
        (j['product_name'] ?? '').toString(),
        (j['product_code'] ?? '').toString(),
        (j['quantity'] ?? 0) as num,
        (j['price'] ?? 0).toDouble(),
        (j['line_total'] ?? 0).toDouble(),
        discount: (j['discount_on_product'] ?? 0).toDouble(),
        discountType: j['discount_type']?.toString(),
        tax: (j['tax_amount'] ?? 0).toDouble(),
      );

  bool get hasDiscount => discount > 0;

  /// صافي السطر بعد الخصم (للعرض) — line_total هو قبل الخصم.
  double get netLineTotal {
    if (discount <= 0) return lineTotal;
    if (discountType == 'amount') {
      return lineTotal - (discount * quantity);
    }
    return lineTotal - (lineTotal * discount / 100);
  }
}

/// طلب/فاتورة.
class Order {
  final int id;
  final int type; // 4 بيع · 7 مرتجع · 12/24 تقسيط
  final int customerId;
  final String customerName;
  final double amount;
  final double totalTax;
  final double extraDiscount; // خصم إجمالي على الطلب
  final double collectedCash;
  // net_remaining من الخادم هو المتبقّي الفعلي (بيحسب المرتجعات).
  final double? netRemaining;
  final int cash; // 1 نقدي (كاش) · 2 آجل
  final String? createdAt;
  final List<OrderLine> lines;

  Order({
    required this.id,
    required this.type,
    required this.customerId,
    required this.customerName,
    required this.amount,
    this.extraDiscount = 0,
    this.totalTax = 0,
    this.collectedCash = 0,
    this.netRemaining,
    this.cash = 1,
    this.createdAt,
    this.lines = const [],
  });

  bool get isReturn => type == 7;
  bool get isSample => type == 12; // عيّنة
  bool get isDonation => type == 24; // تبرع
  bool get isFree => isSample || isDonation;
  bool get isCredit => cash == 2; // آجل

  /// نوع الفاتورة: مرتجع/عيّنة/تبرع، أو كاش/آجل للبيع حسب حقل cash.
  String get typeLabel => switch (type) {
        7 => 'مرتجع',
        12 => 'عيّنة',
        24 => 'تبرع',
        _ => isCredit ? 'آجل' : 'كاش',
      };

  /// المتبقّي الفعلي — `net_remaining` من الخادم هو المعتمد لأنه يحسب
  /// المرتجعات والتحصيل من اللوحة. الطرح المحلي احتياطي فقط.
  double get remaining {
    final n = netRemaining;
    if (n != null) return n < 0 ? 0 : n;
    final r = amount - collectedCash;
    return r < 0 ? 0 : r;
  }

  /// حالة التحصيل — تُحسب محلياً من collected_cash و order_amount.
  String get paymentStatus {
    if (collectedCash >= amount) return 'paid';
    if (collectedCash > 0) return 'partial';
    return 'unpaid';
  }

  String get paymentLabel => switch (paymentStatus) {
        'paid' => 'محصّلة',
        'partial' => 'محصّلة جزئياً',
        _ => 'غير محصّلة',
      };

  factory Order.fromJson(Map<String, dynamic> j) {
    final customer = j['customer'] as Map<String, dynamic>?;
    final details = j['details'] as List?;
    return Order(
      id: j['id'] as int,
      type: (j['type'] ?? 4) as int,
      customerId: (j['customer_id'] ?? 0) as int,
      customerName:
          (customer?['name'] ?? j['customer_name'] ?? '—').toString(),
      amount: (j['order_amount'] ?? 0).toDouble(),
      totalTax: (j['total_tax'] ?? 0).toDouble(),
      extraDiscount: (j['extra_discount'] ?? 0).toDouble(),
      collectedCash: (j['collected_cash'] ?? 0).toDouble(),
      netRemaining: (j['net_remaining'] as num?)?.toDouble(),
      cash: (j['cash'] is int)
          ? j['cash'] as int
          : int.tryParse('${j['cash'] ?? 1}') ?? 1,
      createdAt: j['created_at']?.toString(),
      lines: details == null
          ? const []
          : details
              .map((e) => OrderLine.fromJson(e as Map<String, dynamic>))
              .toList(),
    );
  }
}

/// نتيجة تحصيل فاتورة.
class CollectResult {
  final double orderAmount;
  final double collectedCash;
  final double remaining;
  final String paymentStatus;

  CollectResult(this.orderAmount, this.collectedCash, this.remaining,
      this.paymentStatus);

  factory CollectResult.fromJson(Map<String, dynamic> j) => CollectResult(
        (j['order_amount'] ?? 0).toDouble(),
        (j['collected_cash'] ?? 0).toDouble(),
        (j['remaining'] ?? 0).toDouble(),
        (j['payment_status'] ?? 'unpaid').toString(),
      );
}

/// إجماليات نتيجة فلتر الطلبات.
class OrderTotals {
  final int orders;
  final double total;
  final double collected;
  final double remaining;

  OrderTotals(this.orders, this.total, this.collected, this.remaining);

  factory OrderTotals.fromJson(Map<String, dynamic> j) => OrderTotals(
        (j['orders'] ?? 0) as int,
        (j['total'] ?? 0).toDouble(),
        (j['collected'] ?? 0).toDouble(),
        (j['remaining'] ?? 0).toDouble(),
      );
}

/// عنصر عربة لإنشاء طلب (يدعم خصم/ضريبة لكل سطر).
class CartItem {
  final int productId;
  final num quantity;
  final double? price;
  final double? discount;
  final String? discountType; // percent | amount
  final double? tax;

  CartItem(this.productId, this.quantity,
      [this.price, this.discount, this.discountType, this.tax]);

  Map<String, dynamic> toJson() => {
        'id': productId,
        'quantity': quantity,
        if (price != null) 'price': price,
        if (discount != null) 'discount': discount,
        if (discountType != null) 'discount_type': discountType,
        if (tax != null) 'tax': tax,
      };
}

/// سطر قابل للإرجاع من فاتورة (GET /orders/{id}/returnable).
class ReturnableLine {
  final int productId;
  final String productName;
  final String productCode;
  final double price;
  final num quantitySold;
  final num quantityReturned;
  final num quantityReturnable;

  ReturnableLine({
    required this.productId,
    required this.productName,
    required this.productCode,
    required this.price,
    required this.quantitySold,
    required this.quantityReturned,
    required this.quantityReturnable,
  });

  factory ReturnableLine.fromJson(Map<String, dynamic> j) => ReturnableLine(
        productId: j['product_id'] as int,
        productName: (j['product_name'] ?? '').toString(),
        productCode: (j['product_code'] ?? '').toString(),
        price: (j['price'] ?? 0).toDouble(),
        quantitySold: (j['quantity_sold'] ?? 0) as num,
        quantityReturned: (j['quantity_returned'] ?? 0) as num,
        quantityReturnable: (j['quantity_returnable'] ?? 0) as num,
      );
}

/// ملخص الفاتورة القابلة للإرجاع.
class Returnable {
  final int orderId;
  final String customerName;
  final double orderAmount;
  final List<ReturnableLine> lines;
  final bool fullyReturned;

  Returnable({
    required this.orderId,
    required this.customerName,
    required this.orderAmount,
    required this.lines,
    required this.fullyReturned,
  });

  factory Returnable.fromJson(Map<String, dynamic> j) {
    final order = j['order'] as Map<String, dynamic>;
    final customer = order['customer'] as Map<String, dynamic>?;
    return Returnable(
      orderId: order['id'] as int,
      customerName: (customer?['name'] ?? '—').toString(),
      orderAmount: (order['order_amount'] ?? 0).toDouble(),
      lines: (j['lines'] as List)
          .map((e) => ReturnableLine.fromJson(e as Map<String, dynamic>))
          .toList(),
      fullyReturned: (j['fully_returned'] ?? false) == true,
    );
  }
}

class OrderRepository {
  final _api = ApiClient.instance;

  Future<Paginated<Order>> list({
    int? type,
    int? customerId,
    String? paymentStatus, // paid | partial | unpaid — فلترة التبويبات
    String? search,
    String? sort,
    int offset = 1,
    int limit = 25,
  }) {
    return _api.getList<Order>(
      '/orders',
      query: {
        if (type != null) 'type': type,
        if (customerId != null) 'customer_id': customerId,
        if (paymentStatus != null) 'payment_status': paymentStatus,
        if (search != null && search.isNotEmpty) 'search': search,
        if (sort != null) 'sort': sort,
        'offset': offset,
        'limit': limit,
      },
      parse: Order.fromJson,
    );
  }

  /// إجماليات الفلتر الحالي (GET /orders/totals) — تُحسب على كل النتائج.
  Future<OrderTotals> totals({
    int? type,
    String? paymentStatus,
    int? customerId,
  }) async {
    final data = await _api.get('/orders/totals', query: {
      if (type != null) 'type': type,
      if (paymentStatus != null) 'payment_status': paymentStatus,
      if (customerId != null) 'customer_id': customerId,
    });
    return OrderTotals.fromJson(data as Map<String, dynamic>);
  }

  Future<Order> byId(int id) async {
    final data = await _api.get('/orders/$id');
    return Order.fromJson(data as Map<String, dynamic>);
  }

  /// تحصيل ضد فاتورة معيّنة (POST /orders/{id}/collect).
  /// يحدّث collected_cash على الفاتورة ويرجّع الحالة الجديدة.
  Future<CollectResult> collect({
    required int orderId,
    required double amount,
    required int accountId,
    required String date,
    String? note,
    String? receiptPath,
  }) async {
    final data = await _api.post(
      '/orders/$orderId/collect',
      body: {
        'amount': amount,
        'account_id': accountId,
        'date': date,
        if (note != null) 'note': note,
      },
      filePath: receiptPath,
      fileField: 'img',
    );
    return CollectResult.fromJson(data as Map<String, dynamic>);
  }

  /// ما يمكن إرجاعه من فاتورة (GET /orders/{id}/returnable).
  Future<Returnable> returnable(int orderId) async {
    final data = await _api.get('/orders/$orderId/returnable');
    return Returnable.fromJson(data as Map<String, dynamic>);
  }

  /// تسجيل مرتجع ضد فاتورة (POST /orders/returns) — الطريقة الصحيحة.
  Future<Order> fileReturn({
    required int orderId,
    required List<({int productId, num quantity})> items,
    String? note,
  }) async {
    final data = await _api.post('/orders/returns', body: {
      'order_id': orderId,
      'items': items
          .map((e) => {'product_id': e.productId, 'quantity': e.quantity})
          .toList(),
      if (note != null) 'note': note,
    });
    return Order.fromJson(data as Map<String, dynamic>);
  }

  /// إنشاء طلب (بيع/تقسيط). لو فيه صورة إيصال ترسل multipart.
  Future<Order> place({
    required int customerId,
    required List<CartItem> cart,
    int orderType = 4,
    int? cash, // 1 نقدي · 2 آجل
    double? collectedCash,
    int? accountId,
    double? extraDiscount,
    String? extraDiscountType,
    String? receiptPath,
  }) async {
    final data = await _api.post(
      '/orders',
      body: {
        'user_id': customerId,
        'order_type': orderType,
        if (cash != null) 'cash': cash,
        'cart': cart.map((e) => e.toJson()).toList(),
        if (collectedCash != null) 'collected_cash': collectedCash,
        if (accountId != null) 'type': accountId,
        if (extraDiscount != null) 'extra_discount': extraDiscount,
        if (extraDiscountType != null)
          'extra_discount_type': extraDiscountType,
      },
      filePath: receiptPath,
      fileField: 'img',
    );
    return Order.fromJson(data as Map<String, dynamic>);
  }
}
