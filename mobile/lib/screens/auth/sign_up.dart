import 'package:flutter/material.dart';

import '../../services/api.dart';
import 'log_in.dart';

class CreateAccountScreen extends StatefulWidget {
  const CreateAccountScreen({super.key});

  @override
  State<CreateAccountScreen> createState() => _CreateAccountScreenState();
}

class _CreateAccountScreenState extends State<CreateAccountScreen> {
  bool _agreedToTerms = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  bool _isLoading = false;

  String? _selectedMemberType;

  /// The plans and prices the owner has set, loaded from the server.
  List<Plan> _plans = const [];
  bool _plansLoading = true;
  String? _plansError;

  static const Color _bgColor = Color(0xFF0A0A0A);
  static const Color _fieldColor = Color(0xFF262626);
  static const Color _greenColor = Color(0xFF4CD964);
  static const Color _hintColor = Color(0xFF8A8A8A);

  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadPlans();
  }

  Future<void> _loadPlans() async {
    setState(() {
      _plansLoading = true;
      _plansError = null;
    });
    try {
      final plans = await Api.plans();
      if (!mounted) return;
      setState(() => _plans = plans);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _plansError = e.message);
    } finally {
      if (mounted) setState(() => _plansLoading = false);
    }
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();
    final confirmPassword = _confirmPasswordController.text.trim();

    if (_usernameController.text.trim().isEmpty ||
        email.isEmpty ||
        password.isEmpty) {
      _showMessage('Please fill in your name, email and password.');
      return;
    }
    if (password.length < 8) {
      _showMessage('Password must be at least 8 characters.');
      return;
    }
    if (password != confirmPassword) {
      _showMessage('Passwords do not match.');
      return;
    }
    if (!_agreedToTerms) {
      _showMessage('Please agree to the Terms of Service and Privacy Policy.');
      return;
    }
    if (_selectedMemberType == null) {
      _showMessage('Please select a member type.');
      return;
    }

    setState(() => _isLoading = true);
    try {
      final message = await Api.register(
        name: _usernameController.text.trim(),
        email: email,
        contact: _phoneController.text.trim(),
        plan: _selectedMemberType!,
        password: password,
        passwordConfirmation: confirmPassword,
      );

      if (!mounted) return;
      // The snack bar outlives this screen, so the member reads it on the login.
      _showMessage(message);
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const LoginScreen()),
      );
    } on ApiException catch (e) {
      _showMessage(e.message);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgColor,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Create Account',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Join us and start\nyour Fitness journey\ntoday!',
                style: TextStyle(
                  color: _hintColor,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 32),
              _buildTextField(
                hint: 'Username',
                controller: _usernameController,
              ),
              const SizedBox(height: 16),
              _buildTextField(hint: 'Email', controller: _emailController),
              const SizedBox(height: 16),
              _buildTextField(
                hint: 'Phone Number',
                controller: _phoneController,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                hint: 'Password',
                controller: _passwordController,
                obscureText: _obscurePassword,
                isPassword: true,
                onToggleObscure: () {
                  setState(() => _obscurePassword = !_obscurePassword);
                },
              ),
              const SizedBox(height: 16),
              _buildTextField(
                hint: 'Confirm Password',
                controller: _confirmPasswordController,
                obscureText: _obscureConfirmPassword,
                isPassword: true,
                onToggleObscure: () {
                  setState(() {
                    _obscureConfirmPassword = !_obscureConfirmPassword;
                  });
                },
              ),
              const SizedBox(height: 16),
              _buildMemberTypeDropdown(),
              const SizedBox(height: 20),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 22,
                    height: 22,
                    child: Checkbox(
                      value: _agreedToTerms,
                      onChanged: (value) {
                        setState(() => _agreedToTerms = value ?? false);
                      },
                      side: const BorderSide(color: _hintColor, width: 1.5),
                      checkColor: Colors.black,
                      activeColor: _greenColor,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.only(top: 3),
                      child: RichText(
                        text: const TextSpan(
                          style: TextStyle(color: Colors.white, fontSize: 13),
                          children: [
                            TextSpan(text: 'I agree to the '),
                            TextSpan(
                              text: 'Terms of Service',
                              style: TextStyle(
                                color: _greenColor,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            TextSpan(text: '\nand '),
                            TextSpan(
                              text: 'Privacy Policy',
                              style: TextStyle(
                                color: _greenColor,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 28),
              SizedBox(
                width: double.infinity,
                height: 54,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _register,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _greenColor,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(28),
                    ),
                    elevation: 0,
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: Colors.black,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          'REGISTER',
                          style: TextStyle(
                            color: Colors.black,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 0.5,
                          ),
                        ),
                ),
              ),
              const SizedBox(height: 20),
              Center(
                child: GestureDetector(
                  onTap: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const LoginScreen(),
                      ),
                    );
                  },
                  child: RichText(
                    text: const TextSpan(
                      style: TextStyle(color: Colors.white, fontSize: 13),
                      children: [
                        TextSpan(text: 'Already have an account? '),
                        TextSpan(
                          text: 'Log in',
                          style: TextStyle(
                            color: _greenColor,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField({
    required String hint,
    required TextEditingController controller,
    bool obscureText = false,
    bool isPassword = false,
    VoidCallback? onToggleObscure,
  }) {
    return Container(
      height: 54,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: _fieldColor,
        borderRadius: BorderRadius.circular(27),
      ),
      child: TextField(
        controller: controller,
        obscureText: obscureText,
        textAlignVertical: TextAlignVertical.center,
        style: const TextStyle(color: Colors.white, fontSize: 14),
        decoration: InputDecoration(
          isDense: true,
          hintText: hint,
          hintStyle: const TextStyle(color: _hintColor, fontSize: 14),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 22,
            vertical: 16,
          ),
          suffixIcon: isPassword
              ? Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: IconButton(
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(
                      minWidth: 36,
                      minHeight: 36,
                    ),
                    icon: Icon(
                      obscureText ? Icons.visibility_off : Icons.visibility,
                      color: _hintColor,
                      size: 20,
                    ),
                    onPressed: onToggleObscure,
                  ),
                )
              : null,
        ),
      ),
    );
  }

  Widget _buildMemberTypeDropdown() {
    // Without the plans there is nothing to pick, so offer to try again.
    if (_plansError != null) {
      return Row(
        children: [
          Expanded(
            child: Text(_plansError!,
                style: const TextStyle(color: _hintColor, fontSize: 13)),
          ),
          TextButton(
            onPressed: _loadPlans,
            child: const Text('Retry', style: TextStyle(color: _greenColor)),
          ),
        ],
      );
    }

    return Container(
      height: 54,
      padding: const EdgeInsets.only(left: 22, right: 16),
      decoration: BoxDecoration(
        color: _fieldColor,
        borderRadius: BorderRadius.circular(27),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: _selectedMemberType,
          isExpanded: true,
          hint: Text(
            _plansLoading ? 'Loading plans…' : 'Select Member type',
            style: const TextStyle(color: _hintColor, fontSize: 14),
          ),
          icon: const Icon(Icons.keyboard_arrow_down, color: _hintColor),
          dropdownColor: _fieldColor,
          style: const TextStyle(color: Colors.white, fontSize: 14),
          borderRadius: BorderRadius.circular(16),
          items: _plans
              .map((plan) => DropdownMenuItem<String>(
                    value: plan.name,
                    child: Text(
                        '${plan.name} · ₱${plan.price.toStringAsFixed(plan.price % 1 == 0 ? 0 : 2)}/month'),
                  ))
              .toList(),
          onChanged: (value) {
            setState(() => _selectedMemberType = value);
          },
        ),
      ),
    );
  }
}
