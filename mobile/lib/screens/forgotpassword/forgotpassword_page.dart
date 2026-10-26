import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';

import '../../services/api.dart';

/// Colors matching the app's existing dark theme with green accent.
class _AppColors {
  static const bg = Color(0xFF0D0D0D);
  static const field = Color(0xFF1C1C1C);
  static const fieldBorder = Color(0xFF2B2B2B);
  static const placeholder = Color(0xFF7A7A7A);
  static const text = Color(0xFFF5F5F5);
  static const muted = Color(0xFF9A9A9A);
  static const accent = Color(0xFF3DDC5B);
  static const inactiveBtn = Color(0xFF2A2A2A);
  static const inactiveBtnText = Color(0xFF6A6A6A);
}

/// Forgot-password flow.
/// Step 1 - user enters their email; sends password reset request via backend API.
/// Step 2 - confirmation screen telling them to check their inbox.
class ForgotPasswordPage extends StatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  State<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends State<ForgotPasswordPage> {
  int _step = 1;
  bool _isLoading = false;
  String? _errorText;

  final _emailController = TextEditingController();

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  String get _email => _emailController.text.trim();

  Future<void> _handleSendCode() async {
    if (_email.isEmpty || !_email.contains('@')) {
      setState(() => _errorText = 'Enter a valid email address');
      return;
    }
    setState(() {
      _isLoading = true;
      _errorText = null;
    });
    try {
      await Api.forgotPassword(_email);
      if (!mounted) return;
      setState(() => _step = 2);
    } on ApiException catch (e) {
      setState(() => _errorText = e.message);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _handleResend() async {
    setState(() => _isLoading = true);
    try {
      await Api.forgotPassword(_email);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reset link resent')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message)),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _goBack() {
    if (_step == 2) {
      setState(() {
        _step = 1;
        _errorText = null;
      });
    } else {
      Navigator.of(context).maybePop();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _AppColors.bg,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(28, 24, 28, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildBackButton(),
              const SizedBox(height: 24),
              _buildStepIndicator(),
              const SizedBox(height: 24),
              Expanded(
                child: SingleChildScrollView(
                  child: _step == 1 ? _buildStep1() : _buildStep2(),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBackButton() {
    return GestureDetector(
      onTap: _isLoading ? null : _goBack,
      child: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: _AppColors.field,
          shape: BoxShape.circle,
          border: Border.all(color: _AppColors.fieldBorder),
        ),
        child: const Icon(Icons.arrow_back, color: _AppColors.text, size: 18),
      ),
    );
  }

  Widget _buildStepIndicator() {
    return Row(
      children: [
        Expanded(child: _dot(active: true)),
        const SizedBox(width: 6),
        Expanded(child: _dot(active: _step == 2)),
      ],
    );
  }

  Widget _dot({required bool active}) {
    return Container(
      height: 4,
      decoration: BoxDecoration(
        color: active ? _AppColors.accent : _AppColors.fieldBorder,
        borderRadius: BorderRadius.circular(2),
      ),
    );
  }

  Widget _buildStep1() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Forgot Password?',
          style: TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.w800,
            color: _AppColors.text,
            letterSpacing: -0.4,
          ),
        ),
        const SizedBox(height: 10),
        const Text(
          "Enter the email linked to your account. We'll send you a "
          'link to reset your password.',
          style: TextStyle(fontSize: 14, height: 1.5, color: _AppColors.muted),
        ),
        const SizedBox(height: 32),
        _buildLabel('Email'),
        const SizedBox(height: 8),
        _buildTextField(
          controller: _emailController,
          hint: 'Email',
          keyboardType: TextInputType.emailAddress,
        ),
        if (_errorText != null) _buildErrorText(),
        const SizedBox(height: 32),
        _buildPrimaryButton(
          label: 'Send reset link',
          onPressed: _handleSendCode,
        ),
        const SizedBox(height: 20),
        _buildFooterLink(
          leading: 'Remembered your password? ',
          action: 'Log in',
          onTap: () => Navigator.of(context).maybePop(),
        ),
      ],
    );
  }

  Widget _buildStep2() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            color: _AppColors.field,
            shape: BoxShape.circle,
            border: Border.all(color: _AppColors.fieldBorder),
          ),
          child: const Icon(Icons.mark_email_read_outlined,
              color: _AppColors.accent, size: 30),
        ),
        const SizedBox(height: 24),
        const Text(
          'Check your email',
          style: TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.w800,
            color: _AppColors.text,
            letterSpacing: -0.4,
          ),
        ),
        const SizedBox(height: 10),
        RichText(
          text: TextSpan(
            style: const TextStyle(
                fontSize: 14, height: 1.5, color: _AppColors.muted),
            children: [
              const TextSpan(text: "We've sent a password reset link to "),
              TextSpan(
                text: _email,
                style: const TextStyle(
                    color: _AppColors.text, fontWeight: FontWeight.w600),
              ),
              const TextSpan(
                  text: '. Open it to choose a new password, then come back '
                      'and log in.'),
            ],
          ),
        ),
        const SizedBox(height: 32),
        _buildPrimaryButton(
          label: 'Back to log in',
          onPressed: () => Navigator.of(context).maybePop(),
        ),
        const SizedBox(height: 20),
        Center(
          child: TextButton(
            onPressed: _isLoading ? null : _handleResend,
            child: const Text(
              "Didn't get it? Resend",
              style: TextStyle(color: _AppColors.accent, fontSize: 13),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(left: 4),
      child: Text(
        text,
        style: const TextStyle(fontSize: 12, color: _AppColors.muted),
      ),
    );
  }

  Widget _buildErrorText() {
    return Padding(
      padding: const EdgeInsets.only(top: 8, left: 4),
      child: Text(
        _errorText!,
        style: const TextStyle(fontSize: 12, color: Color(0xFFFF5C5C)),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    TextInputType? keyboardType,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: _AppColors.field,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _AppColors.fieldBorder),
      ),
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        style: const TextStyle(color: _AppColors.text, fontSize: 15),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: _AppColors.placeholder),
          border: InputBorder.none,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
        ),
      ),
    );
  }

  Widget _buildPrimaryButton({
    required String label,
    required VoidCallback onPressed,
  }) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: _isLoading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: _AppColors.accent,
          disabledBackgroundColor: _AppColors.inactiveBtn,
          foregroundColor: _AppColors.bg,
          disabledForegroundColor: _AppColors.inactiveBtnText,
          padding: const EdgeInsets.symmetric(vertical: 18),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          elevation: 0,
        ),
        child: _isLoading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(_AppColors.bg),
                ),
              )
            : Text(
                label,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
      ),
    );
  }

  Widget _buildFooterLink({
    required String leading,
    required String action,
    required VoidCallback onTap,
  }) {
    return Center(
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontSize: 13, color: _AppColors.muted),
          children: [
            TextSpan(text: leading),
            TextSpan(
              text: action,
              style: const TextStyle(
                color: _AppColors.accent,
                fontWeight: FontWeight.w600,
              ),
              recognizer: TapGestureRecognizer()..onTap = onTap,
            ),
          ],
        ),
      ),
    );
  }
}
