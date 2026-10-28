import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/member.dart';
import 'api.dart';

/// Phone notifications for the two things a member wants to know: their
/// payment was confirmed, and their membership is about to end.
///
/// There is no push server. The app already asks the backend for news while
/// it is open, and these compare each answer with what this phone saw last
/// (kept in SharedPreferences, so it survives a restart). Nothing shows when
/// the member has turned notifications off on the profile screen.
class Notifications {
  Notifications._();

  static final _plugin = FlutterLocalNotificationsPlugin();
  static bool _ready = false;

  /// How many days before the end of a membership to warn about it.
  static const expiryWarningDays = 3;

  // One id per kind, so a newer notice replaces an older one of the same kind.
  static const _approvedId = 1;
  static const _expiringId = 2;

  /// Replaces the real notification in tests.
  @visibleForTesting
  static void Function(int id, String title, String body)? showOverride;

  /// Sets up the plugin and asks for permission, once, when the member has
  /// notifications on. Safe to call again.
  static Future<void> prepare(Member member) async {
    if (_ready || kIsWeb || !member.notificationsEnabled) return;
    try {
      await _plugin.initialize(
        settings: const InitializationSettings(
          android: AndroidInitializationSettings('@mipmap/ic_launcher'),
          iOS: DarwinInitializationSettings(),
        ),
      );
      // Android 13 and later ask the member before any notification shows.
      await _plugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.requestNotificationsPermission();
      _ready = true;
    } catch (_) {
      // No notifications on this device; the app works the same without them.
    }
  }

  /// Call with every member the server sends.
  static Future<void> onMember(Member member) async {
    final prefs = await SharedPreferences.getInstance();
    final pendingKey = 'notify_pending_${member.id}';
    final expiryKey = 'notify_expiry_${member.id}';

    // Approved: this phone saw the sign-up pending, and now it is not.
    final wasPending = prefs.getBool(pendingKey);
    await prefs.setBool(pendingKey, member.isPending);
    if (wasPending == true && !member.isPending) {
      await _show(member, _approvedId, 'Membership approved',
          'Your payment was confirmed. Your QR code is ready.');
    }

    // Ending soon: once for each end date, so a renewal warns again later.
    final daysLeft = member.daysLeft;
    final expires = member.expiresAt?.toIso8601String().substring(0, 10);
    if (expires != null &&
        daysLeft != null &&
        daysLeft >= 0 &&
        daysLeft <= expiryWarningDays &&
        prefs.getString(expiryKey) != expires) {
      await prefs.setString(expiryKey, expires);
      await _show(member, _expiringId, 'Membership ending soon',
          'Your membership ends ${_inDays(daysLeft)}.');
    }
  }


  static String _inDays(int days) => switch (days) {
        0 => 'today',
        1 => 'tomorrow',
        _ => 'in $days days',
      };

  static Future<void> _show(
      Member member, int id, String title, String body) async {
    // The switch may have changed on the profile screen since [member] was
    // read; the copy saved with the login is always the latest.
    final latest = await Api.savedMember();
    if (!(latest?.notificationsEnabled ?? member.notificationsEnabled)) return;

    final override = showOverride;
    if (override != null) {
      override(id, title, body);
      return;
    }

    if (!_ready) await prepare(member);
    if (!_ready) return;
    try {
      await _plugin.show(
        id: id,
        title: title,
        body: body,
        notificationDetails: const NotificationDetails(
          android: AndroidNotificationDetails(
            'fitpass_membership',
            'Membership',
            channelDescription:
                'Payment confirmed and membership ending soon.',
            importance: Importance.high,
            priority: Priority.high,
          ),
          iOS: DarwinNotificationDetails(),
        ),
      );
    } catch (_) {
      // A notification that cannot show is not worth interrupting the app for.
    }
  }
}
