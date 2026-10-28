import 'dart:convert';

import 'package:fitpass/services/api.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A login is kept in SharedPreferences: the token, and the last member the
/// server sent, so the app can open on the dashboard even when offline.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const member = {
    'id': 8,
    'name': 'Ana Reyes',
    'email': 'ana@example.com',
    'plan': 'Student',
    'membership_type': 'Monthly Unlimited',
    'member_id': 'ID #0008',
    'status': 'Active',
    'qr_code': 'FP-ABCDEFGH',
    'expires_at': '2026-11-28',
    'visits_this_month': 3,
    'notifications_enabled': false,
  };

  test('a saved login and member are restored when the app opens', () async {
    SharedPreferences.setMockInitialValues({
      'api_token': '1|secret',
      'api_member': jsonEncode(member),
    });

    expect(await Api.restoreSession(), isTrue);
    final restored = await Api.savedMember();

    expect(restored?.name, 'Ana Reyes');
    expect(restored?.qrCode, 'FP-ABCDEFGH');
    expect(restored?.notificationsEnabled, isFalse);
  });

  test('without a saved login there is nothing to restore', () async {
    SharedPreferences.setMockInitialValues({'api_member': jsonEncode(member)});

    expect(await Api.restoreSession(), isFalse);
    expect(await Api.savedMember(), isNull);
  });

  test('a member saved in an old shape is ignored, not a crash', () async {
    SharedPreferences.setMockInitialValues({
      'api_token': '1|secret',
      'api_member': '{"id": "not a number"}',
    });

    await Api.restoreSession();
    expect(await Api.savedMember(), isNull);
  });
}
