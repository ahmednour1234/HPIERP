import '../../core/network/api_client.dart';

/// سجل حضور يومي.
class AttendanceRecord {
  final String date;
  final String? checkIn;
  final String? checkOut;
  final int status; // 1 حضور فقط · 2 مكتمل (حضور + انصراف)
  final String? note; // ملاحظة المندوب وقت البصمة (غالباً اسم المكان)

  AttendanceRecord({
    required this.date,
    this.checkIn,
    this.checkOut,
    this.status = 1,
    this.note,
  });

  factory AttendanceRecord.fromJson(Map<String, dynamic> j) {
    int s(dynamic v) => v is int ? v : int.tryParse('${v ?? 1}') ?? 1;
    return AttendanceRecord(
      date: (j['date'] ?? '').toString(),
      checkIn: j['check_in']?.toString(),
      checkOut: j['check_out']?.toString(),
      status: s(j['status']),
      note: j['note']?.toString(),
    );
  }

  bool get hasCheckIn => checkIn != null && checkIn!.isNotEmpty;
  bool get hasCheckOut => checkOut != null && checkOut!.isNotEmpty;
}

class AttendanceRepository {
  final _api = ApiClient.instance;

  /// سجل الحضور (GET /attendance).
  Future<List<AttendanceRecord>> list() async {
    final res = await _api.getList<AttendanceRecord>('/attendance',
        query: {'limit': 30}, parse: AttendanceRecord.fromJson);
    return res.items;
  }

  /// تسجيل بصمة — status: 1 حضور، 2 انصراف. يبقى على v1 حسب المواصفات.
  Future<void> punch(
      {int status = 1, double? lat, double? lng, String? note}) async {
    await _api.post('/attendance/store', v1: true, body: {
      'status': status,
      if (lat != null) 'lat': lat,
      if (lng != null) 'lng': lng,
      if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
    });
  }
}
