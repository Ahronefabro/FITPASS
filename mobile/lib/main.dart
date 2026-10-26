import 'package:flutter/material.dart';
import 'screens/splash/splash_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const FitPassApp());
}

class FitPassApp extends StatelessWidget {
  const FitPassApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'FitPass',
      theme: ThemeData.dark(),
      home: const SplashScreen(),
    );
  }
}
