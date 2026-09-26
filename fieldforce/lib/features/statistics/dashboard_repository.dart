import '../../core/network/api_client.dart';

/// ملخص لوحة الإحصائيات (GET /dashboard/summary).
class DashboardSummary {
  final int salesCount;
  final double salesAmount;
  final int returnsCount;
  final double returnsAmount;
  final double netSales;
  final double collected;
  final int visits;
  final int visitsTarget; // المستهدف الشهري للزيارات (0 = غير متاح)
  final double stockValue;
  final int lowStock;
  final String? periodFrom;
  final String? periodTo;

  DashboardSummary({
    required this.salesCount,
    required this.salesAmount,
    required this.returnsCount,
    required this.returnsAmount,
    required this.netSales,
    required this.collected,
    required this.visits,
    this.visitsTarget = 0,
    required this.stockValue,
    required this.lowStock,
    this.periodFrom,
    this.periodTo,
  });

  /// نسبة إنجاز الزيارات المئوية (0 لو المستهدف غير متاح).
  int get visitsPercent {
    if (visitsTarget <= 0) return 0;
    return ((visits / visitsTarget) * 100).round().clamp(0, 100);
  }

  factory DashboardSummary.fromJson(Map<String, dynamic> j) {
    double d(v) => (v ?? 0).toDouble();
    int i(v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    final sales = j['sales'] as Map<String, dynamic>? ?? {};
    final returns = j['returns'] as Map<String, dynamic>? ?? {};
    final period = j['period'] as Map<String, dynamic>?;
    // visits قد يكون رقماً (قديم) أو object فيه achieved/target (جديد).
    final visitsRaw = j['visits'];
    int visitsCount;
    int visitsTarget = 0;
    if (visitsRaw is Map) {
      visitsCount = i(visitsRaw['achieved'] ?? visitsRaw['count']);
      visitsTarget = i(visitsRaw['target']);
    } else {
      visitsCount = i(visitsRaw);
      visitsTarget = i(j['visits_target']);
    }
    return DashboardSummary(
      salesCount: (sales['count'] ?? 0) as int,
      salesAmount: d(sales['amount']),
      returnsCount: (returns['count'] ?? 0) as int,
      returnsAmount: d(returns['amount']),
      netSales: d(j['net_sales']),
      collected: d(j['collected']),
      visits: visitsCount,
      visitsTarget: visitsTarget,
      stockValue: d(j['stock_value']),
      lowStock: (j['low_stock_products'] ?? 0) as int,
      periodFrom: period?['from']?.toString(),
      periodTo: period?['to']?.toString(),
    );
  }
}

/// نقطة على منحنى الإيراد الشهري.
class MonthlyRevenue {
  final String month;
  final double sales;
  final double returns;
  final int orders;

  MonthlyRevenue(this.month, this.sales, this.returns, this.orders);

  factory MonthlyRevenue.fromJson(Map<String, dynamic> j) => MonthlyRevenue(
        j['month'] as String,
        (j['sales'] ?? 0).toDouble(),
        (j['returns'] ?? 0).toDouble(),
        (j['orders'] ?? 0) as int,
      );
}

/// أفضل منتج مبيعاً.
class TopProduct {
  final String name;
  final String code;
  final num quantity;
  final double amount;
  TopProduct(this.name, this.code, this.quantity, this.amount);

  factory TopProduct.fromJson(Map<String, dynamic> j) => TopProduct(
        (j['name'] ?? '').toString(),
        (j['product_code'] ?? '').toString(),
        (j['quantity'] ?? 0) as num,
        (j['amount'] ?? 0).toDouble(),
      );
}

class DashboardRepository {
  final _api = ApiClient.instance;

  Future<List<TopProduct>> topProducts({int limit = 5}) async {
    final data = await _api.get('/dashboard/top-products', query: {'limit': limit});
    return (data as List)
        .map((e) => TopProduct.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<DashboardSummary> summary() async {
    final data = await _api.get('/dashboard/summary');
    return DashboardSummary.fromJson(data as Map<String, dynamic>);
  }

  Future<List<MonthlyRevenue>> monthlyRevenue({int months = 7}) async {
    final data = await _api.get('/dashboard/monthly-revenue',
        query: {'months': months});
    return (data as List)
        .map((e) => MonthlyRevenue.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
