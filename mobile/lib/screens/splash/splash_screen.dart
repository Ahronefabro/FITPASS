import 'package:flutter/material.dart';
import '../../models/member.dart';
import '../../services/api.dart';
import '../dashboard/dashboard.dart';
import '../landing/landing.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with TickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _logoScale;
  late final Animation<double> _ringScale;
  late final Animation<double> _ringFade;
  late final Animation<Offset> _textSlide;
  late final Animation<double> _textFade;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    );

    // Logo pops in with an elastic bounce.
    _logoScale = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.55, curve: Curves.elasticOut),
      ),
    );

    // A glowing ring pulses outward from behind the logo and fades.
    _ringScale = Tween<double>(begin: 0.5, end: 1.8).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.15, 0.75, curve: Curves.easeOut),
      ),
    );

    _ringFade = Tween<double>(begin: 0.6, end: 0.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.15, 0.75, curve: Curves.easeOut),
      ),
    );

    // Wordmark and tagline slide up from below and fade in.
    _textSlide =
        Tween<Offset>(begin: const Offset(0, 0.6), end: Offset.zero).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.55, 1.0, curve: Curves.easeOutCubic),
      ),
    );

    _textFade = CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.55, 1.0, curve: Curves.easeIn),
    );

    _controller.forward();

    // Navigate after 3.5 seconds total: straight to the dashboard when a saved
    // login still works, otherwise to the landing screen.
    Future.wait([
      _restoreMember(),
      Future.delayed(const Duration(milliseconds: 3500)),
    ]).then((results) {
      final member = results.first as Member?;
      if (mounted) {
        Navigator.of(context).pushReplacement(
          PageRouteBuilder(
            transitionDuration: const Duration(milliseconds: 500),
            pageBuilder: (context, animation, secondaryAnimation) =>
                member == null
                    ? const WelcomeScreen()
                    : Dashboard(member: member),
            transitionsBuilder:
                (context, animation, secondaryAnimation, child) {
              return FadeTransition(opacity: animation, child: child);
            },
          ),
        );
      }
    });
  }

  /// The member to open the dashboard for, or null to show the landing
  /// screen. A saved login stays until the member logs out or the server
  /// turns the token down; being offline only means showing the last copy.
  Future<Member?> _restoreMember() async {
    if (!await Api.restoreSession()) return null;
    try {
      return await Api.me();
    } on ApiException catch (e) {
      // A 401 has already cleared the saved login.
      if (e.isUnauthorized) return null;
      return Api.savedMember();
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF121212),
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 160,
              width: 160,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Pulsing glow ring behind the logo.
                  AnimatedBuilder(
                    animation: _controller,
                    builder: (context, child) {
                      return Opacity(
                        opacity: _ringFade.value,
                        child: Transform.scale(
                          scale: _ringScale.value,
                          child: Container(
                            height: 120,
                            width: 120,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              border: Border.all(
                                color: const Color(0xFF38C152),
                                width: 3,
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                  ScaleTransition(
                    scale: _logoScale,
                    child: Image.asset(
                      'assets/logo.png',
                      height: 140,
                      errorBuilder: (context, error, stackTrace) {
                        return const Icon(
                          Icons.fitness_center_rounded,
                          color: Color(0xFF38C152),
                          size: 100,
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            SlideTransition(
              position: _textSlide,
              child: FadeTransition(
                opacity: _textFade,
                child: Column(
                  children: [
                    RichText(
                      text: const TextSpan(
                        children: [
                          TextSpan(
                            text: 'FIT',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 30,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          TextSpan(
                            text: 'PASS',
                            style: TextStyle(
                              color: Color(0xFF38C152),
                              fontSize: 30,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'STRONGER EVERY DAY',
                      style: TextStyle(
                        color: Color(0xFF38C152),
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 2.0,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
