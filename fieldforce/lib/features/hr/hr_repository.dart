import '../../core/network/api_client.dart';
import '../../core/network/api_config.dart';

/// تقييم شهري للمندوب (درجة + ملاحظة المدير).
class HrRating {
  final int id;
  final String month; // مثال 2026-08
  final num score;
  final String note;
  final String? managerName;
  final String? createdAt;

  HrRating({
    required this.id,
    required this.month,
    required this.score,
    required this.note,
    this.managerName,
    this.createdAt,
  });

  factory HrRating.fromJson(Map<String, dynamic> j) => HrRating(
    id: (j['id'] ?? 0) as int,
    month: (j['month'] ?? j['date'] ?? '').toString(),
    score: (j['score'] ?? j['rating'] ?? 0) as num,
    note: (j['note'] ?? '').toString(),
    managerName: (j['manager'] ?? j['manager_name'])?.toString(),
    createdAt: j['created_at']?.toString(),
  );
}

/// طلب موظف / طلب إجازة — نفس الشكل (يميّزهما المسار).
class HrRequest {
  final int id;
  final String note;
  final int status; // 0 قيد المراجعة · 1 مقبول · 2 مرفوض
  final String statusText;
  final String? date;
  final String? manager;
  final String? createdAt;

  HrRequest({
    required this.id,
    required this.note,
    required this.status,
    required this.statusText,
    this.date,
    this.manager,
    this.createdAt,
  });

  factory HrRequest.fromJson(Map<String, dynamic> j) {
    int s(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;
    return HrRequest(
      id: (j['id'] ?? 0) as int,
      note: (j['note'] ?? '').toString(),
      status: s(j['status']),
      statusText: (j['status_text'] ?? '').toString(),
      date: j['date']?.toString(),
      manager: j['manager']?.toString(),
      createdAt: j['created_at']?.toString(),
    );
  }

  String get dateShort => createdAt?.split('T').first ?? (date ?? '');
}

/// ملاحظة تطوير من المدير.
class HrDevelopment {
  final int id;
  final String note;
  final String? manager;
  final String? createdAt;

  HrDevelopment({
    required this.id,
    required this.note,
    this.manager,
    this.createdAt,
  });

  factory HrDevelopment.fromJson(Map<String, dynamic> j) => HrDevelopment(
    id: (j['id'] ?? 0) as int,
    note: (j['note'] ?? '').toString(),
    manager: j['manager']?.toString(),
    createdAt: j['created_at']?.toString(),
  );
}

/// كورس تدريبي مُسنَد للمندوب من المدير (GET /hr/courses) — عرض فقط.
class HrCourse {
  final int id;
  final String name;
  final String? link;
  final String? imageUrl;
  final String? managerName;
  final String? createdAt;

  HrCourse({
    required this.id,
    required this.name,
    this.link,
    this.imageUrl,
    this.managerName,
    this.createdAt,
  });

  factory HrCourse.fromJson(Map<String, dynamic> j) {
    String? nn(dynamic v) {
      final s = v?.toString().trim();
      return (s == null || s.isEmpty || s == 'null') ? null : s;
    }

    // اسم المدير قد يأتي داخل كائن manager/admin.
    String? mgr(dynamic v) {
      if (v is Map) return nn(v['name'] ?? v['f_name']);
      return nn(v);
    }

    // الرابط قد يكون link أو url؛ الصورة image_url أو image.
    String? imgUrl = nn(j['image_url']);
    if (imgUrl == null) {
      final raw = nn(j['image']);
      if (raw != null) {
        imgUrl = raw.startsWith('http')
            ? raw
            : '${ApiConfig.host}/storage/${raw.replaceFirst(RegExp(r'^/+'), '')}';
      }
    }

    return HrCourse(
      id: _int(j['id']),
      name: (j['name'] ?? j['title'] ?? 'كورس').toString(),
      link: nn(j['link'] ?? j['url']),
      imageUrl: imgUrl,
      managerName: mgr(j['manager'] ?? j['admin'] ?? j['manager_name']),
      createdAt: nn(j['created_at'] ?? j['date']),
    );
  }

  /// الرابط الجاهز للفتح — يفضّل link ثم صورة الكورس.
  Uri? get openUri {
    final target = (link != null && link!.isNotEmpty) ? link! : imageUrl;
    if (target == null || target.isEmpty) return null;
    final parsed = Uri.tryParse(target);
    if (parsed == null) return null;
    if (parsed.hasScheme) return parsed;
    final path = target.startsWith('/') ? target : '/$target';
    return Uri.tryParse('${ApiConfig.host}$path');
  }

  bool get canOpen => openUri != null;

  String get dateShort => createdAt?.split('T').first ?? '';
}

/// نوع المرفق — يحدّد الأيقونة وسلوك الفتح.
enum AttachmentKind { pdf, image, link, file }

/// مرفق واحد داخل وثيقة (رابط ملف: PDF / صورة / لينك).
class DocAttachment {
  final String name;
  final String url; // الرابط الكامل الجاهز للفتح

  const DocAttachment(this.name, this.url);

  bool get hasUrl => url.trim().isNotEmpty;

  /// نوع المرفق من امتداد الرابط.
  AttachmentKind get kind {
    final u = url.split('?').first.toLowerCase();
    if (u.endsWith('.pdf')) return AttachmentKind.pdf;
    if (u.endsWith('.png') ||
        u.endsWith('.jpg') ||
        u.endsWith('.jpeg') ||
        u.endsWith('.gif') ||
        u.endsWith('.webp') ||
        u.endsWith('.bmp')) {
      return AttachmentKind.image;
    }
    // له امتداد ملف معروف آخر → ملف، وإلا لينك.
    final dot = u.lastIndexOf('.');
    final lastSlash = u.lastIndexOf('/');
    if (dot > lastSlash && dot < u.length - 1 && (u.length - dot) <= 6) {
      return AttachmentKind.file;
    }
    return AttachmentKind.link;
  }

  String get typeLabel => switch (kind) {
        AttachmentKind.pdf => 'PDF',
        AttachmentKind.image => 'صورة',
        AttachmentKind.link => 'رابط',
        AttachmentKind.file => _ext(),
      };

  String _ext() {
    final u = url.split('?').first;
    final dot = u.lastIndexOf('.');
    if (dot >= 0 && dot < u.length - 1) return u.substring(dot + 1).toUpperCase();
    return 'ملف';
  }

  Uri? get uri {
    final value = url.trim();
    if (value.isEmpty) return null;
    final parsed = Uri.tryParse(value);
    if (parsed == null) return null;
    if (parsed.hasScheme) return parsed;
    final path = value.startsWith('/') ? value : '/$value';
    return Uri.tryParse('${ApiConfig.host}$path');
  }
}

/// وثيقة للمندوب/المدير من GET /documents (تحتوي مرفقات).
class HrDocument {
  final int id;
  final String title;
  final String description;
  final List<DocAttachment> attachments;
  final String? createdAt;

  HrDocument({
    required this.id,
    required this.title,
    required this.description,
    required this.attachments,
    this.createdAt,
  });

  factory HrDocument.fromJson(Map<String, dynamic> j) {
    String firstString(List<dynamic> values) {
      for (final value in values) {
        if (value == null) continue;
        final text = value.toString().trim();
        if (text.isNotEmpty && text != 'null') return text;
      }
      return '';
    }

    // نستخرج رابطاً من قيمة قد تكون نصاً أو Map.
    String urlOf(dynamic v) {
      if (v == null) return '';
      if (v is String) return v.trim();
      if (v is Map) {
        return firstString([
          v['url'],
          v['file_url'],
          v['path'],
          v['file'],
          v['attachment'],
          v['link'],
        ]);
      }
      return v.toString().trim();
    }

    String nameOf(dynamic v, String fallback) {
      if (v is Map) {
        final n = firstString([v['name'], v['title'], v['file_name']]);
        if (n.isNotEmpty) return n;
      }
      return fallback;
    }

    final atts = <DocAttachment>[];
    // 1) مصفوفة attachments (الشكل الرسمي).
    final raw = j['attachments'] ?? j['files'] ?? j['media'];
    if (raw is List) {
      for (var i = 0; i < raw.length; i++) {
        final url = urlOf(raw[i]);
        if (url.isNotEmpty) {
          atts.add(DocAttachment(nameOf(raw[i], 'مرفق ${i + 1}'), url));
        }
      }
    }
    // 2) توافق: حقل ملف مفرد.
    if (atts.isEmpty) {
      final single = firstString([
        j['file_url'],
        j['document_url'],
        j['url'],
        j['file'],
        j['path'],
        j['attachment'],
      ]);
      if (single.isNotEmpty) atts.add(DocAttachment('الملف', single));
    }

    final title =
        firstString([j['title'], j['name'], j['document_name'], j['file_name']]);
    return HrDocument(
      id: _int(j['id']),
      title: title.isEmpty ? 'وثيقة' : title,
      description: firstString([j['description'], j['note'], j['details']]),
      attachments: atts,
      createdAt: firstString([j['created_at'], j['date']]),
    );
  }

  bool get hasFiles => attachments.any((a) => a.hasUrl);

  String get dateShort => createdAt?.split('T').first ?? '';
}

class HrRepository {
  final _api = ApiClient.instance;

  /// التقييم الشهري (GET /hr/ratings).
  Future<List<HrRating>> ratings({String? month}) async {
    final res = await _api.getList<HrRating>(
      '/hr/ratings',
      query: {if (month != null) 'month': month, 'limit': 50},
      parse: HrRating.fromJson,
    );
    return res.items;
  }

  /// طلبات الموظف (GET /hr/requests).
  Future<List<HrRequest>> requests({int? status}) async {
    final res = await _api.getList<HrRequest>(
      '/hr/requests',
      query: {if (status != null) 'status': status, 'limit': 50},
      parse: HrRequest.fromJson,
    );
    return res.items;
  }

  /// طلبات الإجازة (GET /hr/leaves).
  Future<List<HrRequest>> leaves({int? status}) async {
    final res = await _api.getList<HrRequest>(
      '/hr/leaves',
      query: {if (status != null) 'status': status, 'limit': 50},
      parse: HrRequest.fromJson,
    );
    return res.items;
  }

  /// ملاحظات التطوير من المدير (GET /hr/development).
  Future<List<HrDevelopment>> development() async {
    final res = await _api.getList<HrDevelopment>(
      '/hr/development',
      query: {'limit': 50},
      parse: HrDevelopment.fromJson,
    );
    return res.items;
  }

  /// الكورسات المُسنَدة للمندوب (GET /hr/courses) — عرض فقط.
  Future<List<HrCourse>> courses({String? search}) async {
    final res = await _api.getList<HrCourse>(
      '/hr/courses',
      query: {if (search != null && search.isNotEmpty) 'search': search, 'limit': 50},
      parse: HrCourse.fromJson,
    );
    return res.items;
  }

  /// كورس واحد بتفاصيله (GET /hr/courses/{id}).
  Future<HrCourse> course(int id) async {
    final data = await _api.get('/hr/courses/$id');
    final map = data is Map<String, dynamic>
        ? data
        : <String, dynamic>{'id': id};
    return HrCourse.fromJson(map);
  }

  /// وثائق المستخدم الحالي (GET /documents).
  Future<List<HrDocument>> documents() async {
    final data = await _api.get('/documents');
    return _extractList(data)
        .whereType<Map>()
        .map((e) => HrDocument.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  /// تقديم طلب موظف (POST /hr/requests).
  Future<void> submitRequest({required String note, String? date}) async {
    await _api.post(
      '/hr/requests',
      body: {'note': note, 'type': 1, if (date != null) 'date': date},
    );
  }

  /// تقديم طلب إجازة (POST /hr/leaves).
  Future<void> submitLeave({required String note, String? date}) async {
    await _api.post(
      '/hr/leaves',
      body: {'note': note, 'type': 2, if (date != null) 'date': date},
    );
  }
}

int _int(dynamic v) => v is int ? v : int.tryParse('${v ?? 0}') ?? 0;

List<dynamic> _extractList(dynamic data) {
  if (data is List) return data;
  if (data is Map<String, dynamic>) {
    for (final key in ['documents', 'items', 'records', 'results', 'data']) {
      final value = data[key];
      if (value is List) return value;
      if (value is Map<String, dynamic>) {
        final nested = _extractList(value);
        if (nested.isNotEmpty) return nested;
      }
    }
  }
  return const [];
}
