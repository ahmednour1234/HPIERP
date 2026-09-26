import '../../core/network/api_client.dart';

/// توريد نقدي من المندوب لحساب الشركة (بانتظار موافقة الأدمن).
class Deposit {
  final int id;
  final double amount;
  final String? note;
  final int status; // 0 معلّق · 1 مقبول · 2 مرفوض
  final String accountName;
  final String? createdAt;

  Deposit({
    required this.id,
    required this.amount,
    this.note,
    required this.status,
    required this.accountName,
    this.createdAt,
  });

  factory Deposit.fromJson(Map<String, dynamic> j) {
    final acc = j['account'] as Map<String, dynamic>?;
    return Deposit(
      id: j['id'] as int,
      amount: (j['amount'] ?? 0).toDouble(),
      note: j['note']?.toString(),
      status: (j['status'] ?? 0) as int,
      accountName: (acc?['account'] ?? '—').toString(),
      createdAt: j['created_at']?.toString(),
    );
  }

  String get statusText => switch (status) {
        1 => 'مقبول',
        2 => 'مرفوض',
        _ => 'بانتظار الموافقة',
      };
}

class DepositRepository {
  final _api = ApiClient.instance;

  /// توريد كاش (POST /deposits) — يبقى معلّقاً حتى موافقة الأدمن.
  /// receiptPath يُرسل صورة الإيصال multipart (img).
  Future<Deposit> submit({
    required int accountId,
    required double amount,
    String? note,
    String? receiptPath,
  }) async {
    final data = await _api.post(
      '/deposits',
      body: {
        'account_id': accountId,
        'amount': amount,
        if (note != null) 'note': note,
      },
      filePath: receiptPath,
      fileField: 'img',
    );
    return Deposit.fromJson(data as Map<String, dynamic>);
  }

  Future<List<Deposit>> list({int? status, String? from, String? to}) async {
    final res = await _api.getList<Deposit>(
      '/deposits',
      query: {
        if (status != null) 'status': status,
        if (from != null) 'from': from,
        if (to != null) 'to': to,
        'limit': 50,
      },
      parse: Deposit.fromJson,
    );
    return res.items;
  }
}
