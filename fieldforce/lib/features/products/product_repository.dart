import '../../core/network/api_client.dart';

/// منتج من المخزون/الكتالوج.
class Product {
  final int id;
  final String name;
  final String code;
  final double sellingPrice;
  final double tax;
  final int? stockId;
  final num quantity; // الكمية المتاحة للمندوب (في وضع المخزون)
  /// سعر العميل المتفاوض عليه — يرجّعه `/stocks?customer_id=`، وnull لو لا يوجد.
  final double? customerPrice;

  const Product({
    required this.id,
    required this.name,
    required this.code,
    required this.sellingPrice,
    this.tax = 0,
    this.stockId,
    this.quantity = 0,
    this.customerPrice,
  });

  /// السعر الفعّال: سعر العميل إن وُجد، وإلا السعر العام.
  double get effectivePrice =>
      (customerPrice != null && customerPrice! > 0)
          ? customerPrice!
          : sellingPrice;

  /// هل للعميل سعر مختلف عن السعر العام؟ (لعرض الفرق للمندوب)
  bool get hasCustomerPrice =>
      customerPrice != null && customerPrice! > 0 &&
      customerPrice != sellingPrice;

  /// عنصر من /stocks — عادةً المنتج داخل مفتاح `product`.
  /// دفاعي: يتعامل مع الشكلين (product متداخل، أو حقول المنتج في الجذر)
  /// حتى لا يفشل التحميل لو اختلف شكل الكتالوج.
  factory Product.fromStock(Map<String, dynamic> j) {
    // المنتج قد يكون داخل `product` أو الحقول في الجذر مباشرة.
    final p = (j['product'] is Map)
        ? Map<String, dynamic>.from(j['product'] as Map)
        : j;
    int asInt(dynamic v) =>
        v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    return Product(
      id: asInt(p['id'] ?? j['product_id'] ?? j['id']),
      name: (p['name'] ?? '').toString(),
      code: (p['product_code'] ?? p['code'] ?? '').toString(),
      // السعر العام يبقى كما هو ليظل الفرق ظاهراً للمندوب.
      sellingPrice: (p['selling_price'] ?? 0).toDouble(),
      customerPrice: (p['customer_price'] as num?)?.toDouble(),
      tax: (p['tax'] ?? 0).toDouble(),
      stockId: (j['stock_id'] is int) ? j['stock_id'] as int? : null,
      quantity: (j['quantity'] ?? p['quantity'] ?? 0) as num,
    );
  }

  /// عنصر من /products.
  factory Product.fromJson(Map<String, dynamic> j) => Product(
        id: j['id'] as int,
        name: (j['name'] ?? '').toString(),
        code: (j['product_code'] ?? '').toString(),
        sellingPrice: (j['selling_price'] ?? 0).toDouble(),
        tax: (j['tax'] ?? 0).toDouble(),
        quantity: (j['quantity'] ?? 0) as num,
      );
}

class ProductRepository {
  final _api = ApiClient.instance;

  /// مخزون المندوب (type=4) أو الكتالوج المسموح ببيعه.
  Future<Paginated<Product>> stocks({
    bool vanStock = true,
    int? categoryId,
    int? customerId, // لجلب سعر العميل مباشرة (customer_price) — السيرفر الجديد
    String? search,
    int offset = 1,
    int limit = 25,
  }) {
    return _api.getList<Product>(
      '/stocks',
      query: {
        if (vanStock) 'type': 4,
        if (categoryId != null) 'category_id': categoryId,
        if (customerId != null) 'customer_id': customerId,
        if (search != null && search.isNotEmpty) 'search': search,
        'offset': offset,
        'limit': limit,
      },
      parse: Product.fromStock,
    );
  }

  /// بحث في الكتالوج.
  Future<Paginated<Product>> search({
    String? search,
    int? categoryId,
    int offset = 1,
    int limit = 25,
  }) {
    return _api.getList<Product>(
      '/products',
      query: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (categoryId != null) 'category_id': categoryId,
        'offset': offset,
        'limit': limit,
      },
      parse: Product.fromJson,
    );
  }
}
