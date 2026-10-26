import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../models/member.dart';

/// Thrown for any failed call. [message] is ready to show the member.
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  const ApiException(this.message, {this.statusCode});

  bool get isUnauthorized => statusCode == 401;

  @override
  String toString() => message;
}

/// One visit to the gym, as the backend records it.
class CheckInRecord {
  final int id;
  final DateTime timeIn;
  final DateTime? timeOut;
  final String method;

  const CheckInRecord({
    required this.id,
    required this.timeIn,
    this.timeOut,
    required this.method,
  });

  factory CheckInRecord.fromJson(Map<String, dynamic> json) {
    return CheckInRecord(
      id: json['id'] as int,
      // Sent in UTC; shown in the phone's own time.
      timeIn: DateTime.parse(json['time_in'] as String).toLocal(),
      timeOut: json['time_out'] == null
          ? null
          : DateTime.parse(json['time_out'] as String).toLocal(),
      method: json['method'] as String,
    );
  }
}

/// Talks to the FitPass Laravel backend under /api.
///
/// The server address is set at build time:
///   flutter run --dart-define=API_URL=http://192.168.1.10:8000
/// It defaults to the Android emulator's address for the computer running
/// `php artisan serve`.
class Api {
  Api._();

  static const String baseUrl =
      String.fromEnvironment('API_URL', defaultValue: 'http://10.0.2.2:8000');

  static const _tokenKey = 'api_token';
  static const _timeout = Duration(seconds: 15);

  static String? _token;

  // ---- Session ----

  /// Loads a saved login. True when there is one to try.
  static Future<bool> restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
    return _token != null;
  }

  static Future<void> _saveToken(String? token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    if (token == null) {
      await prefs.remove(_tokenKey);
    } else {
      await prefs.setString(_tokenKey, token);
    }
  }

  // ---- Accounts ----

  static Future<String> register({
    required String name,
    required String email,
    required String contact,
    required String plan,
    required String password,
    required String passwordConfirmation,
  }) async {
    final body = await _send('POST', '/register', body: {
      'name': name,
      'email': email,
      'contact': contact,
      'plan': plan,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
    return body['message'] as String;
  }

  static Future<Member> login(String email, String password) async {
    final body = await _send('POST', '/login', body: {
      'email': email,
      'password': password,
      'device_name': 'fitpass-mobile',
    });
    await _saveToken(body['token'] as String);
    return Member.fromJson(body['member'] as Map<String, dynamic>);
  }

  static Future<void> logout() async {
    try {
      await _send('POST', '/logout');
    } on ApiException {
      // Signed out on this phone either way.
    } finally {
      await _saveToken(null);
    }
  }

  static Future<String> forgotPassword(String email) async {
    final body = await _send('POST', '/forgot-password', body: {'email': email});
    return body['message'] as String;
  }

  static Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmation,
  }) async {
    await _send('PUT', '/me/password', body: {
      'current_password': currentPassword,
      'password': newPassword,
      'password_confirmation': confirmation,
    });
  }

  // ---- Member data ----

  static Future<Member> me() async {
    final body = await _send('GET', '/me');
    return Member.fromJson(body['member'] as Map<String, dynamic>);
  }

  static Future<List<CheckInRecord>> checkIns({
    required DateTime from,
    required DateTime to,
  }) async {
    final body = await _send('GET', '/check-ins', query: {
      'from': from.toUtc().toIso8601String(),
      'to': to.toUtc().toIso8601String(),
    });
    return (body['check_ins'] as List)
        .map((e) => CheckInRecord.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  static Future<CheckInRecord?> latestCheckIn() async {
    final body = await _send('GET', '/check-ins/latest');
    final json = body['check_in'];
    return json == null
        ? null
        : CheckInRecord.fromJson(json as Map<String, dynamic>);
  }

  // ---- Plumbing ----

  static Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, String>? body,
    Map<String, String>? query,
  }) async {
    final uri = Uri.parse('$baseUrl/api$path').replace(queryParameters: query);
    final request = http.Request(method, uri)
      ..headers.addAll({
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      });
    if (body != null) request.body = jsonEncode(body);

    final http.Response response;
    try {
      response = await http.Response.fromStream(
        await request.send().timeout(_timeout),
      );
    } catch (_) {
      throw const ApiException(
        'Could not reach the FitPass server. Check your connection.',
      );
    }

    Map<String, dynamic> json = {};
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) json = decoded;
    } catch (_) {
      // Not json; fall through to the status code.
    }

    if (response.statusCode >= 200 && response.statusCode < 300) return json;

    if (response.statusCode == 401) await _saveToken(null);
    throw ApiException(_errorMessage(response.statusCode, json),
        statusCode: response.statusCode);
  }

  /// Laravel answers a failed form with {"message", "errors": {field: [..]}}.
  /// The first field error reads best on a phone.
  static String _errorMessage(int status, Map<String, dynamic> json) {
    final errors = json['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) return first.first.toString();
    }
    return switch (status) {
      401 => 'Your session has ended. Please log in again.',
      429 => 'Too many attempts. Wait a minute and try again.',
      _ => json['message'] as String? ?? 'Something went wrong. Try again.',
    };
  }
}
