import '../../core/network/api_client.dart';
import '../../core/network/api_exception.dart';

/// حالة طلب صرف البضاعة.
enum SettlementStatus { pending, approved, rejected }

SettlementStatus _statusFrom(dynamic v) {
  switch ('$v'.toLowerCase()) {
    case 'approved':
    case 'accepted':
    case '1':
      return SettlementStatus.approved;
    case 'rejected':
    case 'refused':
    case '2':
      return SettlementStatus.rejected;
    default:
      return SettlementStatus.pending;
  }
}

/// سطر في طلب الصرف: منتج + الكمية المرتجعة.
class SettlementLine {
  final int productId;
  final String name;
  final String code;
  final int quantity;

  const SettlementLine({
    required this.productId,
    required this.name,
    this.code = '',
    required this.quantity,
  });

  factory SettlementLine.fromJson(Map<String, dynamic> j) {
    final p = (j['product'] is Map)
        ? Map<String, dynamic>.from(j['product'] as Map)
        : const <String, dynamic>{};
    int asInt(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    return SettlementLine(
      productId: asInt(j['product_id'] ?? p['id']),
      name: (p['name'] ?? j['name'] ?? '').toString(),
      code: (p['product_code'] ?? j['product_code'] ?? '').toString(),
      quantity: asInt(j['quantity']),
    );
  }

  Map<String, dynamic> toBody() => {'product_id': productId, 'quantity': quantity};
}

/// طلب صرف بضاعة مُرسَل للأدمن (بانتظار موافقة / موافَق / مرفوض).
class SettlementRequest {
  final int id;
  final SettlementStatus status;
  final String? statusText;
  final String? note;
  final String? adminNote;
  final String? createdAt;
  final String? reviewedAt;
  final List<SettlementLine> lines;

  const SettlementRequest({
    required this.id,
    required this.status,
    this.statusText,
    this.note,
    this.adminNote,
    this.createdAt,
    this.reviewedAt,
    this.lines = const [],
  });

  bool get isPending => status == SettlementStatus.pending;

  int get totalQuantity =>
      lines.fold<int>(0, (sum, l) => sum + l.quantity);

  factory SettlementRequest.fromJson(Map<String, dynamic> j) {
    int asInt(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    final raw = (j['items'] ?? j['lines'] ?? j['products']) as List?;
    return SettlementRequest(
      id: asInt(j['id']),
      status: _statusFrom(j['status']),
      statusText: j['status_text']?.toString(),
      note: j['note']?.toString(),
      adminNote: (j['admin_note'] ?? j['reject_reason'])?.toString(),
      createdAt: j['created_at']?.toString(),
      reviewedAt: j['reviewed_at']?.toString(),
      lines: raw == null
          ? const []
          : raw
              .cast<Map<String, dynamic>>()
              .map(SettlementLine.fromJson)
              .toList(),
    );
  }
}

class StockRepository {
  final _api = ApiClient.instance;

  /// آخر طلب صرف للمندوب — يرجّع null لو مفيش طلب (أو الـ endpoint لسه
  /// غير منشور على السيرفر، فنتعامل مع 404 كـ "مفيش طلب").
  Future<SettlementRequest?> currentRequest() async {
    try {
      final data = await _api.get('/stocks/confirm/current');
      if (data == null) return null;
      if (data is Map && data.isEmpty) return null;
      return SettlementRequest.fromJson(data as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (e.statusCode == 404) return null;
      rethrow;
    }
  }

  /// إرسال طلب صرف البضاعة للأدمن — لا يُصفّر اليوم إلا بعد الموافقة.
  Future<SettlementRequest> submit({
    required List<SettlementLine> lines,
    String? note,
  }) async {
    final data = await _api.post('/stocks/confirm', body: {
      'items': lines.map((l) => l.toBody()).toList(),
      if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
    });
    return SettlementRequest.fromJson(data as Map<String, dynamic>);
  }

  /// إلغاء طلب معلّق (قبل مراجعة الأدمن).
  Future<void> cancel(int id) => _api.post('/stocks/confirm/$id/cancel');
}
