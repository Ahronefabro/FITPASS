/// The logged-in member and their membership, as the backend sends it.
class Member {
  final int id;
  final String name;
  final String email;
  final String? contact;
  final String plan;
  final String membershipType;
  final String memberId;

  /// Pending, Active, Expiring soon or Expired.
  final String status;

  /// Null while the sign-up waits for staff to confirm the payment.
  final String? qrCode;

  /// The profile picture's address, or null when none is set.
  final String? photoUrl;
  final DateTime? joinedAt;
  final DateTime? expiresAt;
  final int visitsThisMonth;

  const Member({
    required this.id,
    required this.name,
    required this.email,
    this.contact,
    required this.plan,
    required this.membershipType,
    required this.memberId,
    required this.status,
    this.qrCode,
    this.photoUrl,
    this.joinedAt,
    this.expiresAt,
    required this.visitsThisMonth,
  });

  bool get isPending => status == 'Pending';

  /// Up to two letters, shown when there is no profile picture.
  String get initials => name
      .split(' ')
      .where((part) => part.isNotEmpty)
      .take(2)
      .map((part) => part[0].toUpperCase())
      .join();

  int? get daysLeft {
    final expires = expiresAt;
    if (expires == null) return null;
    final now = DateTime.now();
    return expires.difference(DateTime(now.year, now.month, now.day)).inDays;
  }

  factory Member.fromJson(Map<String, dynamic> json) {
    DateTime? date(Object? value) =>
        value == null ? null : DateTime.parse(value as String);

    return Member(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String? ?? '',
      contact: json['contact'] as String?,
      plan: json['plan'] as String,
      membershipType: json['membership_type'] as String,
      memberId: json['member_id'] as String,
      status: json['status'] as String,
      qrCode: json['qr_code'] as String?,
      photoUrl: json['photo_url'] as String?,
      joinedAt: date(json['joined_at']),
      expiresAt: date(json['expires_at']),
      visitsThisMonth: json['visits_this_month'] as int? ?? 0,
    );
  }
}
