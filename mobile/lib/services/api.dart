import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
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

/// A membership plan a new member can pick, with its monthly price.
class Plan {
  final String name;
  final double price;

  const Plan({required this.name, required this.price});

  factory Plan.fromJson(Map<String, dynamic> json) {
    return Plan(
      name: json['name'] as String,
      price: (json['price'] as num).toDouble(),
    );
  }
}

/// Talks to the FitPass Laravel backend under /api.
///
/// The server address can be set at build time, e.g. for a real phone:
///   flutter run --dart-define=API_URL=http://192.168.1.10:8000
/// Without it, the app looks for `php artisan serve` on this computer: the
/// Android emulator reaches it at 10.0.2.2, everything else at localhost.
class Api {
  Api._();

  static final String baseUrl = _defaultBaseUrl();

  static String _defaultBaseUrl() {
    const configured = String.fromEnvironment('API_URL');
    if (configured.isNotEmpty) return configured;
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000';
    }
    return 'http://localhost:8000';
  }

  static const _tokenKey = 'api_token';

  /// The last member the server sent, so the app can open without the network.
  static const _memberKey = 'api_member';
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
      // Signed out: forget who it was, too.
      await prefs.remove(_tokenKey);
      await prefs.remove(_memberKey);
    } else {
      await prefs.setString(_tokenKey, token);
    }
  }

  /// The member saved with the login, or null when there is none to use.
  static Future<Member?> savedMember() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_memberKey);
    if (_token == null || saved == null) return null;
    try {
      return Member.fromJson(jsonDecode(saved) as Map<String, dynamic>);
    } catch (_) {
      // Saved by an older version of the app; the next call replaces it.
      return null;
    }
  }

  /// Reads the member out of an answer and keeps a copy for [savedMember].
  static Future<Member> _member(Map<String, dynamic> body) async {
    final json = body['member'] as Map<String, dynamic>;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_memberKey, jsonEncode(json));
    return Member.fromJson(json);
  }

  // ---- Accounts ----

  /// The plans on offer and their prices, as the owner has set them.
  static Future<List<Plan>> plans() async {
    final body = await _send('GET', '/plans');
    return (body['plans'] as List)
        .map((e) => Plan.fromJson(e as Map<String, dynamic>))
        .toList();
  }

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
    return _member(body);
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

  /// Saves the profile screen's notification switch.
  static Future<Member> updateSettings({
    required bool notificationsEnabled,
  }) async {
    final body = await _send('PUT', '/me/settings', body: {
      'notifications_enabled': notificationsEnabled,
    });
    return _member(body);
  }

  // ---- Profile picture ----

  /// Uploads a new profile picture and returns the member with its address.
  static Future<Member> uploadPhoto(XFile photo) async {
    final request =
        http.MultipartRequest('POST', Uri.parse('$baseUrl/api/me/photo'))
          ..headers.addAll(_headers())
          ..files.add(http.MultipartFile.fromBytes(
            'photo',
            await photo.readAsBytes(),
            filename: photo.name,
          ));

    final body = await _receive(request, timeout: const Duration(seconds: 60));
    return _member(body);
  }

  static Future<Member> removePhoto() async {
    final body = await _send('DELETE', '/me/photo');
    return _member(body);
  }

  // ---- Member data ----

  static Future<Member> me() async {
    final body = await _send('GET', '/me');
    return _member(body);
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
    Map<String, Object?>? body,
    Map<String, String>? query,
  }) async {
    final uri = Uri.parse('$baseUrl/api$path').replace(queryParameters: query);
    final request = http.Request(method, uri)
      ..headers.addAll(_headers())
      ..headers['Content-Type'] = 'application/json';
    if (body != null) request.body = jsonEncode(body);

    return _receive(request);
  }

  static Map<String, String> _headers() => {
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  /// Sends any request and turns the answer into json or an [ApiException].
  static Future<Map<String, dynamic>> _receive(
    http.BaseRequest request, {
    Duration timeout = _timeout,
  }) async {
    final http.Response response;
    try {
      response = await http.Response.fromStream(
        await request.send().timeout(timeout),
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
