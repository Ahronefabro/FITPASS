import 'package:flutter/material.dart';

import '../../models/member.dart';

class MembershipTab extends StatelessWidget {
  final Member member;
  final Future<void> Function() onRefresh;

  const MembershipTab({
    super.key,
    required this.member,
    required this.onRefresh,
  });

  String _formatDate(DateTime date) {
    const months = [
      'JANUARY',
      'FEBRUARY',
      'MARCH',
      'APRIL',
      'MAY',
      'JUNE',
      'JULY',
      'AUGUST',
      'SEPTEMBER',
      'OCTOBER',
      'NOVEMBER',
      'DECEMBER',
    ];
    return '${months[date.month - 1]} ${date.day}, ${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      color: const Color(0xFF3ECF4A),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(
            parent: BouncingScrollPhysics()),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildPremiumCard(),
            const SizedBox(height: 16),
            _buildDetailsCard(),
            const SizedBox(height: 16),
            _buildRemindersCard(),
          ],
        ),
      ),
    );
  }

  // ---- Green "Premium Member" card with expiry + days left ----
  Widget _buildPremiumCard() {
    final daysLeft = member.daysLeft;
    final expires = member.expiresAt;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF4CAF6E), Color(0xFF3F9E5F)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${member.plan.toUpperCase()} MEMBER',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 24),
          Text(
            expires == null ? 'NOT YET ACTIVE' : _formatDate(expires),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            daysLeft == null
                ? 'Starts once your payment is confirmed'
                : daysLeft >= 0
                    ? '$daysLeft days left'
                    : 'Expired',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 13,
            ),
          ),
        ],
      ),
    );
  }

  // ---- Dark card: Member Type / Member ID / Status ----
  Widget _buildDetailsCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1C1C),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildLabel('Member Type'),
          const SizedBox(height: 4),
          Text(
            member.plan,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 18),
          _buildLabel('Member ID'),
          const SizedBox(height: 4),
          Text(
            member.memberId,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 18),
          _buildLabel('Status'),
          const SizedBox(height: 4),
          Text(
            member.status,
            style: TextStyle(
              color: switch (member.status) {
                'Active' => const Color(0xFF3ECF4A),
                'Expired' => Colors.redAccent,
                _ => const Color(0xFFE0A22B),
              },
              fontSize: 15,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  // ---- Reminders card ----
  Widget _buildRemindersCard() {
    final expires = member.expiresAt;
    final daysLeft = member.daysLeft;
    final String reminder;
    if (expires == null) {
      reminder = 'Pay at the front desk so staff can activate your membership.';
    } else if (daysLeft! < 0) {
      reminder = 'Your membership expired on '
          '${_formatDate(expires).toLowerCase()}. Renew at the front desk.';
    } else {
      reminder = 'Your membership will expire on '
          '${_formatDate(expires).toLowerCase()}';
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1C1C),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Reminders',
            style: TextStyle(
              color: Colors.white,
              fontSize: 15,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            reminder,
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 14,
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Text(
      text,
      style: const TextStyle(
        color: Colors.white54,
        fontSize: 13,
        fontWeight: FontWeight.w500,
      ),
    );
  }
}
