import '../../core/network/api_client.dart';
import '../../core/network/api_exception.dart';

/// مندوب تابع للمدير مع آخر موقع وحالة اليوم.
class SellerInfo {
  final int id;
  final String name;
  final String code;
  final String? phone;
  final String? imageUrl;
  final double? lat;
  final double? lng;
  final String? locationUpdatedAt;
  final String todayStatus; // present · absent · checked_out
  final String? todayCheckIn;
  final String? todayCheckOut;
  // تقييم المدير — يرجّعه /manager/sellers مباشرة (null لو لم يُقيَّم).
  final num? rating;
  final String? ratingNote;

  SellerInfo({
    required this.id,
    required this.name,
    required this.code,
    this.phone,
    this.imageUrl,
    this.lat,
    this.lng,
    this.locationUpdatedAt,
    this.todayStatus = 'absent',
    this.todayCheckIn,
    this.todayCheckOut,
    this.rating,
    this.ratingNote,
  });

  bool get hasLocation => lat != null && lng != null;

  String get statusLabel => switch (todayStatus) {
        'present' => 'حاضر',
        'checked_out' => 'منصرف',
        _ => 'غائب',
      };

  factory SellerInfo.fromJson(Map<String, dynamic> j) {
    final loc = j['last_location'] as Map<String, dynamic>?;
    double? d(dynamic v) => v == null ? null : (v as num).toDouble();
    return SellerInfo(
      id: j['id'] as int,
      name: (j['name'] ?? '').toString(),
      code: (j['mandob_code'] ?? '').toString(),
      phone: j['phone']?.toString(),
      imageUrl: j['image_url']?.toString(),
      lat: d(loc?['latitude']),
      lng: d(loc?['longitude']),
      locationUpdatedAt: loc?['updated_at']?.toString(),
      todayStatus: (j['today_status'] ?? 'absent').toString(),
      todayCheckIn: j['today_check_in']?.toString(),
      todayCheckOut: j['today_check_out']?.toString(),
      rating: j['rating'] as num?,
      ratingNote: j['rating_note']?.toString(),
    );
  }
}

/// سجل بصمة مندوب (كما يراه المدير).
class SellerAttendance {
  final int id;
  final String date;
  final String? checkIn;
  final String? checkOut;
  final int status;
  final double? lat;
  final double? lng;
  final num timeLate;
  final num workedHours;
  final num expectedHours;
  final String? note;

  SellerAttendance({
    required this.id,
    required this.date,
    this.checkIn,
    this.checkOut,
    this.status = 1,
    this.lat,
    this.lng,
    this.timeLate = 0,
    this.workedHours = 0,
    this.expectedHours = 0,
    this.note,
  });

  bool get hasCheckOut => checkOut != null && checkOut!.isNotEmpty;

  factory SellerAttendance.fromJson(Map<String, dynamic> j) {
    final loc = j['location'] as Map<String, dynamic>?;
    double? d(dynamic v) => v == null ? null : (v as num).toDouble();
    num n(dynamic v) => v is num ? v : num.tryParse('${v ?? 0}') ?? 0;
    return SellerAttendance(
      id: j['id'] as int,
      date: (j['date'] ?? '').toString(),
      checkIn: j['check_in']?.toString(),
      checkOut: j['check_out']?.toString(),
      status: (j['status'] ?? 1) is int
          ? (j['status'] ?? 1) as int
          : int.tryParse('${j['status']}') ?? 1,
      lat: d(loc?['latitude']),
      lng: d(loc?['longitude']),
      timeLate: n(j['time_late']),
      workedHours: n(j['worked_hours']),
      expectedHours: n(j['expected_hours']),
      note: j['note']?.toString(),
    );
  }
}

/// ملاحظة المدير على مندوب.
class ManagerNote {
  final int id;
  final String note;
  final String? managerName;
  final String? date;
  final String? createdAt;

  ManagerNote({
    required this.id,
    required this.note,
    this.managerName,
    this.date,
    this.createdAt,
  });

  factory ManagerNote.fromJson(Map<String, dynamic> j) => ManagerNote(
        id: (j['id'] ?? 0) as int,
        note: (j['note'] ?? '').toString(),
        managerName: (j['manager_name'] ?? j['manager'])?.toString(),
        date: j['date']?.toString(),
        createdAt: j['created_at']?.toString(),
      );
}

/// تقييم المدير لمندوب (score 0-100 + ملاحظة اختيارية).
class SellerRating {
  final int sellerId;
  final String sellerName;
  final num score;
  final String? note;
  final String? updatedAt;

  const SellerRating({
    required this.sellerId,
    this.sellerName = '',
    required this.score,
    this.note,
    this.updatedAt,
  });

  /// تقدير نصّي للدرجة — للعرض جنب الرقم.
  String get label => switch (score) {
        >= 90 => 'ممتاز',
        >= 75 => 'جيد جداً',
        >= 60 => 'جيد',
        >= 50 => 'مقبول',
        _ => 'ضعيف',
      };

  factory SellerRating.fromJson(Map<String, dynamic> j) {
    // الرد يغلّف المندوب في `seller`، وقد يأتي مسطّحاً في مسارات أخرى.
    final seller = j['seller'] as Map<String, dynamic>?;
    int asInt(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    return SellerRating(
      sellerId: asInt(seller?['id'] ?? j['seller_id'] ?? j['id']),
      sellerName: (seller?['name'] ?? j['seller_name'] ?? '').toString(),
      score: (j['score'] ?? j['rating'] ?? 0) as num,
      note: (j['note'] ?? j['rating_note'])?.toString(),
      updatedAt: j['updated_at']?.toString(),
    );
  }
}

/// طلب موظف / إجازة مقدَّم من مندوب — كما يراه المدير.
class TeamHrRequest {
  final int id;
  final int type; // 1 طلب موظف · 2 إجازة
  final int sellerId;
  final String sellerName;
  final String sellerCode;
  final String note;
  final int status; // 0 قيد المراجعة · 1 مقبول · 2 مرفوض
  final String? date;
  final String? reply; // رد المدير
  final String? createdAt;

  TeamHrRequest({
    required this.id,
    required this.type,
    required this.sellerId,
    required this.sellerName,
    this.sellerCode = '',
    required this.note,
    required this.status,
    this.date,
    this.reply,
    this.createdAt,
  });

  bool get isLeave => type == 2;
  bool get isPending => status == 0;

  String get dateShort => createdAt?.split('T').first ?? (date ?? '');

  factory TeamHrRequest.fromJson(Map<String, dynamic> j) {
    int i(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    final seller = j['seller'] as Map<String, dynamic>?;
    return TeamHrRequest(
      id: i(j['id']),
      type: i(j['type']),
      sellerId: i(seller?['id'] ?? j['seller_id']),
      sellerName: (seller?['name'] ?? j['seller_name'] ?? '—').toString(),
      sellerCode: (seller?['mandob_code'] ?? '').toString(),
      note: (j['note'] ?? '').toString(),
      status: i(j['status']),
      date: j['date']?.toString(),
      reply: j['reply']?.toString(),
      createdAt: j['created_at']?.toString(),
    );
  }
}

class ManagerRepository {
  final _api = ApiClient.instance;

  /// طلبات وإجازات مناديب المدير (GET /manager/hr/requests).
  /// [type] 1 طلبات · 2 إجازات — [status] null = الكل.
  Future<Paginated<TeamHrRequest>> teamRequests({
    required int type,
    int? status,
    int? sellerId,
    int offset = 1,
  }) {
    return _api.getList<TeamHrRequest>(
      '/manager/hr/requests',
      query: {
        'type': type,
        if (status != null) 'status': status,
        if (sellerId != null) 'seller_id': sellerId,
        'offset': offset,
        'limit': 25,
      },
      parse: TeamHrRequest.fromJson,
    );
  }

  /// قبول/رفض طلب (POST /manager/hr/requests/{id}/decision).
  Future<void> decideRequest(int id,
      {required bool approve, String? reply}) async {
    await _api.post('/manager/hr/requests/$id/decision', body: {
      'status': approve ? 1 : 2,
      if (reply != null && reply.trim().isNotEmpty) 'reply': reply.trim(),
    });
  }

  /// مناديب المدير (GET /manager/sellers).
  Future<List<SellerInfo>> sellers({String? search, int? regionId}) async {
    final data = await _api.get('/manager/sellers', query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (regionId != null) 'region_id': regionId,
    });
    return (data as List)
        .map((e) => SellerInfo.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// سجل بصمة مندوب (GET /manager/sellers/{id}/attendance).
  Future<List<SellerAttendance>> attendance(int sellerId,
      {String? from, String? to}) async {
    final res = await _api.getList<SellerAttendance>(
      '/manager/sellers/$sellerId/attendance',
      query: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
        'limit': 60,
      },
      parse: SellerAttendance.fromJson,
    );
    return res.items;
  }

  /// ملاحظات المدير على مندوب (GET /manager/sellers/{id}/notes).
  Future<List<ManagerNote>> notes(int sellerId) async {
    final data = await _api.get('/manager/sellers/$sellerId/notes');
    return (data as List)
        .map((e) => ManagerNote.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// المدير يكتب ملاحظة (POST /manager/sellers/{id}/notes).
  Future<void> addNote(int sellerId,
      {required String note, String? date}) async {
    await _api.post('/manager/sellers/$sellerId/notes', body: {
      'note': note,
      if (date != null) 'date': date,
    });
  }

  /// المندوب يقرأ ملاحظات مديره (GET /manager-notes).
  Future<List<ManagerNote>> myManagerNotes() async {
    final data = await _api.get('/manager-notes');
    return (data as List)
        .map((e) => ManagerNote.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// تقييم مندوب كما سجّله المدير (GET /manager/sellers/{id}/rating).
  /// يرجّع null لو لم يُقيَّم بعد.
  Future<SellerRating?> rating(int sellerId) async {
    try {
      final data = await _api.get('/manager/sellers/$sellerId/rating');
      if (data == null) return null;
      if (data is Map && data.isEmpty) return null;
      return SellerRating.fromJson(data as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (e.statusCode == 404) return null; // لم يُقيَّم بعد
      rethrow;
    }
  }

  /// المدير يكتب/يحدّث التقييم (POST /manager/sellers/{id}/rating).
  /// `score` مطلوب 0-100، و`note` اختياري — لو لم يُمرَّر تبقى الملاحظة القديمة.
  Future<SellerRating> saveRating(
    int sellerId, {
    required int score,
    String? note,
  }) async {
    final data = await _api.post('/manager/sellers/$sellerId/rating', body: {
      'score': score,
      if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
    });
    return SellerRating.fromJson(data as Map<String, dynamic>);
  }

  /// المندوب يشوف تقييمه (GET /hr/my-rating) — null لو لم يُقيَّم بعد.
  Future<SellerRating?> myRating() async {
    try {
      final data = await _api.get('/hr/my-rating');
      if (data == null) return null;
      if (data is Map && data.isEmpty) return null;
      return SellerRating.fromJson(data as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (e.statusCode == 404) return null;
      rethrow;
    }
  }
}
