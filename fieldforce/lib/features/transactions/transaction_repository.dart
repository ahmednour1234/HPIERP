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

/// حركة واحدة في دفتر المندوب (GET /transactions).
class LedgerEntry {
  final int id;
  final double moneyIn;
  final double moneyOut;
  final String description;
  final String? date;

  LedgerEntry(this.id, this.moneyIn, this.moneyOut, this.description, this.date);

  factory LedgerEntry.fromJson(Map<String, dynamic> j) {
    double d(dynamic v) =>
        v is num ? v.toDouble() : double.tryParse('${v ?? 0}') ?? 0;
    String txt(List<dynamic> vs) {
      for (final v in vs) {
        final t = v?.toString().trim() ?? '';
        if (t.isNotEmpty && t != 'null') return t;
      }
      return '';
    }

    return LedgerEntry(
      j['id'] is int ? j['id'] as int : int.tryParse('${j['id']}') ?? 0,
      d(j['money_in'] ?? j['credit']),
      d(j['money_out'] ?? j['debit']),
      txt([j['description'], j['note'], j['tran_type'], j['type']]),
      txt([j['date'], j['created_at']]),
    );
  }
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

  /// حركات الفترة (GET /transactions) — كل الصفحات.
  Future<List<LedgerEntry>> entries({String? from, String? to}) async {
    final all = <LedgerEntry>[];
    for (var page = 1; page <= 20; page++) {
      final res = await _api.getList<LedgerEntry>('/transactions',
          query: {
            if (from != null) 'from': from,
            if (to != null) 'to': to,
            'offset': page,
            'limit': 100,
          },
          parse: LedgerEntry.fromJson);
      all.addAll(res.items);
      if (!res.hasMore) break;
    }
    return all;
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
