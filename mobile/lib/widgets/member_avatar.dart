import 'package:flutter/material.dart';

import '../models/member.dart';

/// The member's profile picture in a circle, or their initials when they have
/// not set one (or it cannot be loaded).
class MemberAvatar extends StatelessWidget {
  final Member member;
  final double radius;

  const MemberAvatar({super.key, required this.member, required this.radius});

  @override
  Widget build(BuildContext context) {
    final url = member.photoUrl;

    return CircleAvatar(
      radius: radius,
      backgroundColor: Colors.white24,
      foregroundImage: url == null ? null : NetworkImage(url),
      // Without this a broken link throws instead of showing the initials.
      onForegroundImageError: url == null ? null : (_, __) {},
      child: Text(
        member.initials,
        style: TextStyle(
          color: Colors.white70,
          fontSize: radius * 0.7,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
