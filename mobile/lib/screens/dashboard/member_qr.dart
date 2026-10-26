import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../models/member.dart';

/// The member's code, drawn the way scanners read best off a phone screen:
/// plain black squares on white, a wide white margin, and medium error
/// correction so glare or a scratch on the screen does not spoil it.
class MemberQr extends StatelessWidget {
  final String code;

  /// The whole white square, margin included.
  final double size;

  const MemberQr({super.key, required this.code, required this.size});

  @override
  Widget build(BuildContext context) {
    // The quiet zone: scanners need white space all around the code.
    final margin = size * 0.08;

    return Container(
      width: size,
      height: size,
      color: Colors.white,
      padding: EdgeInsets.all(margin),
      child: QrImageView(
        data: code,
        version: QrVersions.auto,
        errorCorrectionLevel: QrErrorCorrectLevel.M,
        size: size - margin * 2,
        padding: EdgeInsets.zero,
        gapless: true,
        backgroundColor: Colors.white,
        eyeStyle: const QrEyeStyle(
          eyeShape: QrEyeShape.square,
          color: Colors.black,
        ),
        dataModuleStyle: const QrDataModuleStyle(
          dataModuleShape: QrDataModuleShape.square,
          color: Colors.black,
        ),
      ),
    );
  }
}

/// Full screen, white, as large as the phone allows: what to hold up to the
/// front desk camera.
class MemberQrScreen extends StatelessWidget {
  final Member member;

  const MemberQrScreen({super.key, required this.member});

  @override
  Widget build(BuildContext context) {
    final code = member.qrCode!;

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: LayoutBuilder(
            builder: (context, constraints) {
              // As wide as the screen allows, leaving room for the text.
              final size = math.min(
                constraints.maxWidth - 64,
                constraints.maxHeight * 0.55,
              );

              return Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    member.name.toUpperCase(),
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.black,
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${member.plan} Member  •  ${member.memberId}',
                    style: const TextStyle(color: Colors.black54, fontSize: 13),
                  ),
                  const SizedBox(height: 20),
                  MemberQr(code: code, size: size),
                  const SizedBox(height: 16),
                  SelectableText(
                    code,
                    style: const TextStyle(
                      color: Colors.black,
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 3,
                    ),
                  ),
                  const SizedBox(height: 28),
                  const Text(
                    'Turn your screen brightness up and hold the phone\n'
                    'steady about a hand\'s length from the camera.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.black54, fontSize: 13),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'Tap anywhere to close',
                    style: TextStyle(color: Colors.black38, fontSize: 12),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}
