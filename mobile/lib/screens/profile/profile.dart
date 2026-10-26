import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../models/member.dart';
import '../../services/api.dart';
import '../../widgets/member_avatar.dart';
import '../auth/log_in.dart';

class ProfileScreen extends StatefulWidget {
  final Member member;

  const ProfileScreen({super.key, required this.member});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  static const Color _bgColor = Color(0xFF0E0E0E);
  static const Color _cardColor = Color(0xFF1C1C1C);
  static const Color _colorGreen = Color(0xFF3ECF4A);

  /// Kept here so a new profile picture shows at once.
  late Member _member = widget.member;
  bool _photoBusy = false;

  bool _personalInfoExpanded = false;
  bool _notificationsEnabled = true;

  // ---- Change password controllers/state ---- //
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _obscureCurrentPassword = true;
  bool _obscureNewPassword = true;
  bool _obscureConfirmPassword = true;

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  void _handleLogout(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: _cardColor,
        title: const Text('Log out', style: TextStyle(color: Colors.white)),
        content: const Text(
          'Are you sure you want to log out?',
          style: TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.pop(context);
              await Api.logout();
              if (!context.mounted) return;
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const LoginScreen()),
                (route) => false,
              );
            },
            child: const Text('Log out', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  // TODO: persist this to MySQL backend API (e.g. users.notificationsEnabled)
  Future<void> _toggleNotifications(bool value) async {
    setState(() {
      _notificationsEnabled = value;
    });
  }

  // ---- Profile picture ----

  void _showPhotoOptions() {
    showModalBottomSheet(
      context: context,
      backgroundColor: _cardColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (sheetContext) {
        Widget option(IconData icon, String label, VoidCallback onTap,
            {Color color = Colors.white}) {
          return ListTile(
            leading: Icon(icon, color: color),
            title: Text(label,
                style: TextStyle(color: color, fontWeight: FontWeight.w600)),
            onTap: () {
              Navigator.pop(sheetContext);
              onTap();
            },
          );
        }

        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                option(Icons.photo_camera_outlined, 'Take a photo',
                    () => _pickPhoto(ImageSource.camera)),
                option(Icons.photo_library_outlined, 'Choose from gallery',
                    () => _pickPhoto(ImageSource.gallery)),
                if (_member.photoUrl != null)
                  option(Icons.delete_outline, 'Remove photo', _removePhoto,
                      color: Colors.redAccent),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<void> _pickPhoto(ImageSource source) async {
    final XFile? photo;
    try {
      // Scaled down on the phone so the upload stays small and quick.
      photo = await ImagePicker().pickImage(
        source: source,
        maxWidth: 800,
        maxHeight: 800,
        imageQuality: 85,
        preferredCameraDevice: CameraDevice.front,
      );
    } catch (_) {
      _showSnack('Could not open the camera or gallery.');
      return;
    }
    if (photo == null) return; // Cancelled.

    await _updatePhoto(() => Api.uploadPhoto(photo!), 'Profile picture updated.');
  }

  Future<void> _removePhoto() =>
      _updatePhoto(Api.removePhoto, 'Profile picture removed.');

  Future<void> _updatePhoto(
      Future<Member> Function() change, String doneMessage) async {
    setState(() => _photoBusy = true);
    try {
      final member = await change();
      if (!mounted) return;
      setState(() => _member = member);
      _showSnack(doneMessage);
    } on ApiException catch (e) {
      _showSnack(e.message);
    } finally {
      if (mounted) setState(() => _photoBusy = false);
    }
  }

  // ---- Change password flow ----

  void _showChangePasswordForm(BuildContext context) {
    _currentPasswordController.clear();
    _newPasswordController.clear();
    _confirmPasswordController.clear();
    _obscureCurrentPassword = true;
    _obscureNewPassword = true;
    _obscureConfirmPassword = true;

    showDialog(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (dialogContext, setDialogState) {
            return AlertDialog(
              backgroundColor: _cardColor,
              title: const Text('Change password',
                  style: TextStyle(color: Colors.white)),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: _currentPasswordController,
                      obscureText: _obscureCurrentPassword,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Current password',
                        labelStyle: const TextStyle(color: Colors.white54),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureCurrentPassword
                                ? Icons.visibility_off
                                : Icons.visibility,
                            color: Colors.white54,
                          ),
                          onPressed: () => setDialogState(() =>
                              _obscureCurrentPassword =
                                  !_obscureCurrentPassword),
                        ),
                        enabledBorder: const UnderlineInputBorder(
                          borderSide: BorderSide(color: Colors.white24),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _newPasswordController,
                      obscureText: _obscureNewPassword,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'New password',
                        labelStyle: const TextStyle(color: Colors.white54),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureNewPassword
                                ? Icons.visibility_off
                                : Icons.visibility,
                            color: Colors.white54,
                          ),
                          onPressed: () => setDialogState(
                              () => _obscureNewPassword = !_obscureNewPassword),
                        ),
                        enabledBorder: const UnderlineInputBorder(
                          borderSide: BorderSide(color: Colors.white24),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _confirmPasswordController,
                      obscureText: _obscureConfirmPassword,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        labelText: 'Confirm new password',
                        labelStyle: const TextStyle(color: Colors.white54),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureConfirmPassword
                                ? Icons.visibility_off
                                : Icons.visibility,
                            color: Colors.white54,
                          ),
                          onPressed: () => setDialogState(() =>
                              _obscureConfirmPassword =
                                  !_obscureConfirmPassword),
                        ),
                        enabledBorder: const UnderlineInputBorder(
                          borderSide: BorderSide(color: Colors.white24),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(dialogContext),
                  child: const Text('Cancel'),
                ),
                TextButton(
                  onPressed: () => _submitNewPassword(dialogContext),
                  child: const Text('Confirm',
                      style: TextStyle(color: _colorGreen)),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _submitNewPassword(BuildContext dialogContext) async {
    final currentPassword = _currentPasswordController.text.trim();
    final newPassword = _newPasswordController.text.trim();
    final confirmPassword = _confirmPasswordController.text.trim();

    if (currentPassword.isEmpty ||
        newPassword.isEmpty ||
        confirmPassword.isEmpty) {
      _showSnack('Please fill in all fields.');
      return;
    }
    if (newPassword.length < 8) {
      _showSnack('New password must be at least 8 characters.');
      return;
    }
    if (newPassword != confirmPassword) {
      _showSnack('New passwords do not match.');
      return;
    }

    try {
      await Api.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
        confirmation: confirmPassword,
      );

      if (!dialogContext.mounted) return;
      Navigator.pop(dialogContext);
      _showSnack('Password updated successfully.');
    } on ApiException catch (e) {
      _showSnack(e.message);
    }
  }

  void _showSnack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final username = _member.name;
    final email = _member.email;
    final phoneNumber = _member.contact ?? '';
    final memberType = _member.plan;
    final notificationsEnabled = _notificationsEnabled;

    return Scaffold(
      backgroundColor: _bgColor,
      body: SafeArea(
        child: Column(
          children: [
            _buildTopBar(context),
            const SizedBox(height: 12),
            _buildAvatarSection(username, email),
            const SizedBox(height: 28),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: _buildSettingsCard(
                  context,
                  username: username,
                  email: email,
                  phoneNumber: phoneNumber,
                  memberType: memberType,
                  notificationsEnabled: notificationsEnabled,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTopBar(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: Stack(
        alignment: Alignment.center,
        children: [
          const Text(
            'Profile',
            style: TextStyle(
                color: Colors.white70,
                fontSize: 15,
                fontWeight: FontWeight.w600),
          ),
          Align(
            alignment: Alignment.centerLeft,
            child: IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.arrow_back, color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAvatarSection(String name, String email) {
    return Column(
      children: [
        GestureDetector(
          onTap: _photoBusy ? null : _showPhotoOptions,
          child: Stack(
            children: [
              Container(
                width: 104,
                height: 104,
                padding: const EdgeInsets.all(3),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white24, width: 2),
                ),
                child: _photoBusy
                    ? const CircleAvatar(
                        backgroundColor: Colors.white24,
                        child: SizedBox(
                          width: 26,
                          height: 26,
                          child: CircularProgressIndicator(
                              color: _colorGreen, strokeWidth: 2.5),
                        ),
                      )
                    : MemberAvatar(member: _member, radius: 49),
              ),
              Positioned(
                right: 0,
                bottom: 0,
                child: Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: _colorGreen,
                    shape: BoxShape.circle,
                    border: Border.all(color: _bgColor, width: 3),
                  ),
                  child: const Icon(Icons.camera_alt,
                      color: Colors.black, size: 16),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Text(name,
            style: const TextStyle(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Text(email,
            style: const TextStyle(color: Colors.white54, fontSize: 12)),
      ],
    );
  }

  Widget _buildSettingsCard(
    BuildContext context, {
    required String username,
    required String email,
    required String phoneNumber,
    required String memberType,
    required bool notificationsEnabled,
  }) {
    return Container(
      decoration: BoxDecoration(
          color: _cardColor, borderRadius: BorderRadius.circular(16)),
      child: Column(
        children: [
          // ---- Personal Information (expandable) ----
          _buildRow(
            label: 'Personal Information',
            trailing: AnimatedRotation(
              turns: _personalInfoExpanded ? 0.5 : 0,
              duration: const Duration(milliseconds: 200),
              child: const Icon(Icons.keyboard_arrow_down,
                  color: Colors.white54, size: 20),
            ),
            onTap: () {
              setState(() {
                _personalInfoExpanded = !_personalInfoExpanded;
              });
            },
          ),
          AnimatedCrossFade(
            duration: const Duration(milliseconds: 200),
            crossFadeState: _personalInfoExpanded
                ? CrossFadeState.showFirst
                : CrossFadeState.showSecond,
            firstChild: _buildPersonalInfoDetails(
                username, email, phoneNumber, memberType),
            secondChild: const SizedBox(width: double.infinity),
          ),
          _buildDivider(),

          _buildRow(
              label: 'Change Password',
              onTap: () => _showChangePasswordForm(context)),
          _buildDivider(),

          // ---- Notification (on/off toggle) ---- //
          _buildRow(
            label: 'Notification',
            trailing: Switch(
              value: notificationsEnabled,
              activeThumbColor: _colorGreen,
              onChanged: _toggleNotifications,
            ),
            onTap: () => _toggleNotifications(!_notificationsEnabled),
          ),
          _buildDivider(),

          _buildRow(
            label: 'Help & Support',
            trailing:
                const Icon(Icons.help_outline, color: Colors.white54, size: 18),
            onTap: () {},
          ),
          _buildDivider(),
          _buildRow(
              label: 'Log out',
              labelColor: Colors.redAccent,
              onTap: () => _handleLogout(context)),
        ],
      ),
    );
  }

  Widget _buildPersonalInfoDetails(
      String username, String email, String phoneNumber, String memberType) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 0, 18, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildDetailLine('Username', username),
          const SizedBox(height: 10),
          _buildDetailLine('Email', email),
          const SizedBox(height: 10),
          _buildDetailLine('Phone', phoneNumber),
          const SizedBox(height: 10),
          _buildDetailLine('Type', memberType),
        ],
      ),
    );
  }

  Widget _buildDetailLine(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 80,
          child: Text(
            '$label:',
            style: const TextStyle(
                color: Colors.white54,
                fontSize: 13,
                fontWeight: FontWeight.w500),
          ),
        ),
        Expanded(
          child: Text(
            value.isEmpty ? '—' : value,
            style: const TextStyle(
                color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
          ),
        ),
      ],
    );
  }

  Widget _buildRow({
    required String label,
    Widget? trailing,
    Color labelColor = Colors.white,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label,
                style: TextStyle(
                    color: labelColor,
                    fontSize: 14,
                    fontWeight: FontWeight.w600)),
            if (trailing != null) trailing,
          ],
        ),
      ),
    );
  }

  Widget _buildDivider() => const Divider(color: Colors.white12, height: 1);
}
