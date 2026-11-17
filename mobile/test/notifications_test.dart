import 'package:fitpass/models/member.dart';
import 'package:fitpass/services/notifications.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late List<String> shown;

  setUp(() {
    SharedPreferences.setMockInitialValues({});
    shown = [];
    Notifications.showOverride = (id, title, body) => shown.add(body);
  });

  Member member({
    String status = 'Active',
    int? daysLeft = 20,
    bool notifications = true,
    int? paymentId,
  }) {
    final today = DateTime.now();
    return Member(
      id: 8,
      name: 'Ana Reyes',
      email: 'ana@example.com',
      plan: 'Student',
      membershipType: 'Monthly Unlimited',
      memberId: 'ID #0008',
      status: status,
      expiresAt: daysLeft == null
          ? null
          : DateTime(today.year, today.month, today.day + daysLeft),
      visitsThisMonth: 0,
      notificationsEnabled: notifications,
      lastPayment: paymentId == null
          ? null
          : MemberPayment(
              id: paymentId,
              amount: 1000,
              kind: 'Monthly',
              method: 'Cash',
              paidOn: today,
            ),
    );
  }

  group('payment received', () {
    const received =
        'Staff recorded your ₱1,000 Monthly payment by Cash. Thank you!';

    test('shows when staff record a new payment', () async {
      await Notifications.onMember(member(paymentId: 4));
      await Notifications.onMember(member(paymentId: 5));

      expect(shown, [received]);
    });

    test('shows for the first payment of a member who had none', () async {
      await Notifications.onMember(member());
      await Notifications.onMember(member(paymentId: 1));

      expect(shown, [received]);
    });

    test('stays quiet about payments from before this phone looked', () async {
      await Notifications.onMember(member(paymentId: 4));
      await Notifications.onMember(member(paymentId: 4));

      expect(shown, isEmpty);
    });

    test('is not announced twice when the payment approves a sign-up',
        () async {
      await Notifications.onMember(member(status: 'Pending', daysLeft: null));
      await Notifications.onMember(member(paymentId: 1));

      expect(shown, ['Your payment was confirmed. Your QR code is ready.']);
    });

    test('stays quiet when notifications are off', () async {
      await Notifications.onMember(member(paymentId: 4, notifications: false));
      await Notifications.onMember(member(paymentId: 5, notifications: false));

      expect(shown, isEmpty);
    });
  });

  group('membership approved', () {
    test('shows when a sign-up this phone saw pending is approved', () async {
      await Notifications.onMember(member(status: 'Pending', daysLeft: null));
      await Notifications.onMember(member());

      expect(shown, ['Your payment was confirmed. Your QR code is ready.']);
    });

    test('does not show for a member who was never pending here', () async {
      await Notifications.onMember(member());
      await Notifications.onMember(member());

      expect(shown, isEmpty);
    });
  });

  group('expiring soon', () {
    test('warns once, three days before the end', () async {
      await Notifications.onMember(member(daysLeft: 3));
      await Notifications.onMember(member(daysLeft: 3));

      expect(shown, ['Your membership ends in 3 days.']);
    });

    test('says tomorrow and today', () async {
      await Notifications.onMember(member(daysLeft: 1));
      SharedPreferences.setMockInitialValues({});
      await Notifications.onMember(member(daysLeft: 0));

      expect(shown, [
        'Your membership ends tomorrow.',
        'Your membership ends today.',
      ]);
    });

    test('stays quiet with more than three days left, or once ended', () async {
      await Notifications.onMember(member(daysLeft: 4));
      await Notifications.onMember(member(daysLeft: -1));

      expect(shown, isEmpty);
    });
  });


  test('nothing shows when the member turned notifications off', () async {
    await Notifications.onMember(member(status: 'Pending', daysLeft: null, notifications: false));
    await Notifications.onMember(member(daysLeft: 2, notifications: false));

    expect(shown, isEmpty);
  });
}
