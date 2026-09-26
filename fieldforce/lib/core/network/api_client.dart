import 'dart:io';
import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';
import 'api_config.dart';
import 'api_exception.dart';
import 'token_store.dart';

/// نتيجة قائمة مع بيانات الترقيم.
class Paginated<T> {
  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;

  Paginated(
      {required this.items,
      required this.currentPage,
      required this.lastPage,
      required this.total});

  bool get hasMore => currentPage < lastPage;
}

/// عميل الـ API — يغلّف Dio ويطبّق envelope الـ v2 ومعالجة الأخطاء.
class ApiClient {
  ApiClient._() {
    _dio = Dio(BaseOptions(
      connectTimeout: ApiConfig.timeout,
      receiveTimeout: ApiConfig.timeout,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      // المصفوفات تُرسل كـ key[]=a&key[]=b (متوافق مع Laravel).
      listFormat: ListFormat.multiCompatible,
    ));
    _dio.httpClientAdapter = IOHttpClientAdapter(
      createHttpClient: () {
        final client = HttpClient();
        client.connectionTimeout = ApiConfig.timeout;
        return client;
      },
    );
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await TokenStore.instance.token();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        options.extra['t0'] = DateTime.now();
        if (kDebugMode) debugPrint('[API] -> ${options.uri}');
        handler.next(options);
      },
      onResponse: (res, handler) {
        final t0 = res.requestOptions.extra['t0'] as DateTime?;
        final ms = t0 == null ? -1 : DateTime.now().difference(t0).inMilliseconds;
        final d = res.data;
        final n = d is Map && d['data'] is List ? (d['data'] as List).length : -1;
        if (kDebugMode) debugPrint('[API] <- ${res.statusCode} ${ms}ms items=$n ${res.requestOptions.uri}');
        if (kDebugMode && d is Map && d['data'] is List) {
          for (final o in d['data'] as List) {
            if (o is! Map || !o.containsKey('collected_cash')) continue;
            final amt = (o['order_amount'] ?? 0) as num;
            final col = (o['collected_cash'] ?? 0) as num;
            if (col > 0 && col < amt) {
              debugPrint('[PARTIAL] #${o['id']} amount=$amt collected=$col payment_status=${o['payment_status']}');
            }
          }
        }
        handler.next(res);
      },
      onError: (e, handler) {
        final t0 = e.requestOptions.extra['t0'] as DateTime?;
        final ms = t0 == null ? -1 : DateTime.now().difference(t0).inMilliseconds;
        if (kDebugMode) debugPrint('[API] !! ${e.type} ${e.response?.statusCode} ${ms}ms ${e.requestOptions.uri}');
        handler.next(e);
      },
    ));
  }

  static final ApiClient instance = ApiClient._();
  late final Dio _dio;

  /// يُستدعى عند أي رد 401 — يربطه الـ main بمسح التوكن والرجوع للـ login.
  void Function()? onUnauthorized;
  bool _handlingUnauthorized = false;

  // ---- الطرق العامة ----

  /// GET يرجّع كائن `data`.
  Future<dynamic> get(String path,
      {Map<String, dynamic>? query, bool v1 = false}) async {
    return _unwrap(() => _dio.get(_url(path, v1), queryParameters: query));
  }

  /// GET لقائمة مرقّمة.
  Future<Paginated<T>> getList<T>(
    String path, {
    Map<String, dynamic>? query,
    required T Function(Map<String, dynamic>) parse,
    bool v1 = false,
  }) async {
    final res = await _raw(() => _dio.get(_url(path, v1), queryParameters: query));
    final body = res.data as Map<String, dynamic>;
    final list = (body['data'] as List)
        .map((e) => parse(e as Map<String, dynamic>))
        .toList();
    final meta = body['meta'] as Map<String, dynamic>?;
    return Paginated<T>(
      items: list,
      currentPage: meta?['current_page'] ?? 1,
      lastPage: meta?['last_page'] ?? 1,
      total: meta?['total'] ?? list.length,
    );
  }

  /// POST يرجّع كائن `data`. لو مرّرت filePath يُرسل الطلب كـ multipart.
  Future<dynamic> post(String path,
      {Object? body,
      bool v1 = false,
      String? filePath,
      String fileField = 'img'}) async {
    if (filePath != null && body is Map<String, dynamic>) {
      final form = <String, dynamic>{};
      body.forEach((k, val) {
        // نسطّح المصفوفات لصيغة cart[0][id] المتوقّعة من Laravel
        if (val is List) {
          for (var i = 0; i < val.length; i++) {
            final item = val[i];
            if (item is Map) {
              item.forEach((ik, iv) => form['$k[$i][$ik]'] = iv);
            } else {
              form['$k[$i]'] = item;
            }
          }
        } else {
          form[k] = val;
        }
      });
      form[fileField] = await MultipartFile.fromFile(filePath);
      return _unwrap(
          () => _dio.post(_url(path, v1), data: FormData.fromMap(form)));
    }
    return _unwrap(() => _dio.post(_url(path, v1), data: body));
  }

  Future<dynamic> put(String path, {Object? body, bool v1 = false}) async {
    return _unwrap(() => _dio.put(_url(path, v1), data: body));
  }

  Future<dynamic> delete(String path, {bool v1 = false}) async {
    return _unwrap(() => _dio.delete(_url(path, v1)));
  }

  // ---- داخلي ----

  String _url(String path, bool v1) {
    final base = v1 ? ApiConfig.v1 : ApiConfig.v2;
    return '$base${path.startsWith('/') ? '' : '/'}$path';
  }

  /// ينفّذ الطلب ويرجّع الـ Response خام (بعد فحص الأخطاء).
  Future<Response> _raw(Future<Response> Function() call) async {
    try {
      final res = await call();
      final body = res.data;
      // envelope الـ v2: success:false حتى مع كود 200 نادراً
      if (body is Map && body['success'] == false) {
        throw _fromBody(body, res.statusCode);
      }
      return res;
    } on DioException catch (e) {
      throw _fromDio(e);
    }
  }

  /// ينفّذ الطلب ويرجّع محتوى `data` مباشرة.
  Future<dynamic> _unwrap(Future<Response> Function() call) async {
    final res = await _raw(call);
    final body = res.data;
    if (body is Map && body.containsKey('data')) return body['data'];
    return body; // ردود v1 اللي مش بتتبع الـ envelope
  }

  ApiException _fromDio(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.connectionError) {
      // تشخيص: التفاصيل الفعلية للخطأ.
      final detail = e.error?.toString() ?? e.message ?? '';
      return ApiException('تعذّر الاتصال بالخادم: $detail');
    }
    final res = e.response;
    // أي 401 = انتهت الجلسة → مسح التوكن والرجوع للـ login.
    if (res?.statusCode == 401) _triggerUnauthorized();
    if (res?.data is Map) {
      return _fromBody(res!.data as Map, res.statusCode);
    }
    return ApiException('حدث خطأ ما، حاول لاحقاً.',
        statusCode: res?.statusCode);
  }

  void _triggerUnauthorized() {
    if (_handlingUnauthorized) return; // مرة واحدة فقط
    _handlingUnauthorized = true;
    onUnauthorized?.call();
    // اسمح بمعالجة 401 لاحقة بعد فترة قصيرة.
    Future.delayed(const Duration(seconds: 2), () {
      _handlingUnauthorized = false;
    });
  }

  ApiException _fromBody(Map body, int? code) {
    Map<String, List<String>>? errors;
    final raw = body['errors'];
    if (raw is Map) {
      errors = raw.map((k, v) => MapEntry(
            k.toString(),
            (v as List).map((e) => e.toString()).toList(),
          ));
    }
    return ApiException(
      body['message']?.toString() ?? 'حدث خطأ ما.',
      statusCode: code,
      errors: errors,
    );
  }
}
