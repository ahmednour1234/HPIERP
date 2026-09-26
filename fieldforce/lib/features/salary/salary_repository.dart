import '../../core/network/api_client.dart';

/// بند في كشف الراتب (إضافة/خصم).
class SalaryLine {
  final String label;
  final double amount;
  final bool isDeduct;

  SalaryLine(this.label, this.amount, this.isDeduct);

  factory SalaryLine.fromJson(Map<String, dynamic> j) => SalaryLine(
        (j['label'] ?? '').toString(),
        (j['amount'] ?? 0).toDouble(),
        (j['type'] ?? 'add') == 'deduct',
      );
}

/// كشف راتب شهري.
class Salary {
  final String month;
  final double basic;
  final double transport;
  final double visitsPay;
  final double other;
  final double deductions;
  final double net;
  final double commission;
  final int visitsTarget;
  final int visitsAchieved;
  final int workingDays;
  final int score;
  final String? managerNote;
  final String statusText;
  final List<SalaryLine> details;

  Salary({
    required this.month,
    required this.basic,
    required this.transport,
    required this.visitsPay,
    required this.other,
    required this.deductions,
    required this.net,
    required this.commission,
    required this.visitsTarget,
    required this.visitsAchieved,
    required this.workingDays,
    required this.score,
    this.managerNote,
    required this.statusText,
    required this.details,
  });

  factory Salary.fromJson(Map<String, dynamic> j) {
    double d(v) => (v ?? 0).toDouble();
    int i(v) => (v ?? 0) is int ? (v ?? 0) as int : int.tryParse('$v') ?? 0;
    final visits = j['visits'] as Map<String, dynamic>? ?? {};
    final det = j['details'] as List? ?? [];
    return Salary(
      month: (j['month'] ?? '').toString(),
      basic: d(j['basic']),
      transport: d(j['transport']),
      visitsPay: d(j['visits_pay']),
      other: d(j['other']),
      deductions: d(j['deductions']),
      net: d(j['net']),
      commission: d(j['commission']),
      visitsTarget: i(visits['target']),
      visitsAchieved: i(visits['achieved']),
      workingDays: i(j['working_days']),
      score: i(j['score']),
      managerNote: j['manager_note']?.toString(),
      statusText: (j['status_text'] ?? '').toString(),
      details:
          det.map((e) => SalaryLine.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }
}

class SalaryRepository {
  final _api = ApiClient.instance;

  /// راتب شهر معيّن. يرجّع null لو مفيش كشف للشهر ده.
  Future<Salary?> forMonth(String month) async {
    final data = await _api.get('/salary', query: {'month': month});
    final map = data as Map<String, dynamic>;
    // لو مفيش كشف: {"month": "...", "salary": null}
    if (map.containsKey('salary') && map['salary'] == null) return null;
    return Salary.fromJson(map);
  }

  /// آخر الكشوف.
  Future<List<Salary>> history({int limit = 12}) async {
    final data = await _api.get('/salary/history', query: {'limit': limit});
    return (data as List)
        .map((e) => Salary.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
