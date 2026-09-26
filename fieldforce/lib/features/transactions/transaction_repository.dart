import '../../core/network/api_client.dart';

/// حساب دفع (lookup /accounts).
class Account {
  final int id;
  final String name;
  final double balance;

  Account(this.id, this.name, this.balance);

  factory Account.fromJson(Map<String, dynamic> j) => Account(
        j['id'] as int,
        (j['account'] ?? j['name'] ?? '').toString(),
        (j['balance'] ?? 0).toDouble(),
      );
}

class TransactionRepository {
  final _api = ApiClient.instance;

  /// حسابات الدفع.
  Future<List<Account>> accounts() async {
    final res = await _api.getList<Account>('/accounts',
        query: {'limit': 100}, parse: Account.fromJson);
    return res.items;
  }

  /// إجماليات الحركة (money_in / money_out / net).
  Future<({double moneyIn, double moneyOut, double net})> ledgerTotals(
      {String? from, String? to}) async {
    final data = await _api.get('/transactions/totals', query: {
      if (from != null) 'from': from,
      if (to != null) 'to': to,
    });
    final m = data as Map<String, dynamic>;
    double d(dynamic v) => (v ?? 0).toDouble();
    return (
      moneyIn: d(m['money_in']),
      moneyOut: d(m['money_out']),
      net: d(m['net']),
    );
  }

  /// مصروف (POST /transactions/expense).
  Future<void> expense({
    required int accountId,
    required double amount,
    required String description,
    required String date,
  }) async {
    await _api.post('/transactions/expense', body: {
      'account_id': accountId,
      'amount': amount,
      'description': description,
      'date': date,
    });
  }

  /// دخل (POST /transactions/income).
  Future<void> income({
    required int accountId,
    required double amount,
    required String description,
    required String date,
  }) async {
    await _api.post('/transactions/income', body: {
      'account_id': accountId,
      'amount': amount,
      'description': description,
      'date': date,
    });
  }
}
