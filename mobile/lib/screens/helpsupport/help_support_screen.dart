import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../auth/legal_documents.dart';

/// Help & Support screen: FAQ, contact options, and app version / terms / privacy.
///
/// Add to pubspec.yaml:
///   url_launcher: ^6.3.0
///   package_info_plus: ^8.0.0
///
/// Open it from your Profile "Help & Support" row:
///   Navigator.push(context,
///     MaterialPageRoute(builder: (_) => const HelpSupportScreen()));
class HelpSupportScreen extends StatefulWidget {
  const HelpSupportScreen({super.key});

  @override
  State<HelpSupportScreen> createState() => _HelpSupportScreenState();
}

class _HelpSupportScreenState extends State<HelpSupportScreen> {
  // ---- Change these to your real values ----
  static const _supportEmail = 'support@yourapp.com';
  static const _supportPhone = '+639000000000';

  static const _bg = Color(0xFF0E0E0E);
  static const _card = Color(0xFF1C1C1C);
  static const _accent = Color(0xFF3ECF4A);

  String _version = '';

  final _faqs = const [
    (
      'How do I change my password?',
      'Go to Profile > Change Password, enter your current password, then your new one.'
    ),
    (
      'How do I turn notifications on or off?',
      'Use the Notification toggle on your Profile screen.'
    ),
    (
      'How do I update my personal information?',
      'Open Profile > Personal Information, edit the fields, then save.'
    ),
    (
      'I forgot my password. What should I do?',
      'On the login screen tap "Forgot password" and follow the email instructions.'
    ),
  ];

  @override
  void initState() {
    super.initState();
    PackageInfo.fromPlatform().then((info) {
      if (mounted) {
        setState(() => _version = '${info.version} (${info.buildNumber})');
      }
    });
  }

  Future<void> _open(Uri uri) async {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open link')),
      );
    }
  }

  Widget _sectionTitle(String text) => Padding(
        padding: const EdgeInsets.fromLTRB(4, 24, 4, 10),
        child: Text(text,
            style: const TextStyle(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.w700)),
      );

  Widget _contactTile(
      IconData icon, String title, String subtitle, VoidCallback onTap) {
    return ListTile(
      onTap: onTap,
      leading: Icon(icon, color: _accent),
      title: Text(title,
          style: const TextStyle(
              color: Colors.white, fontWeight: FontWeight.w600)),
      subtitle: Text(subtitle, style: const TextStyle(color: Colors.white54)),
      trailing: const Icon(Icons.chevron_right, color: Colors.white38),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        backgroundColor: _bg,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Colors.white),
        title: const Text('Help & Support',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
          children: [
            // ---- FAQ ----
            _sectionTitle('Frequently asked questions'),
            Container(
              decoration: BoxDecoration(
                  color: _card, borderRadius: BorderRadius.circular(16)),
              clipBehavior: Clip.antiAlias,
              child: Theme(
                data: Theme.of(context).copyWith(dividerColor: Colors.white12),
                child: Column(
                  children: [
                    for (final faq in _faqs)
                      ExpansionTile(
                        iconColor: Colors.white70,
                        collapsedIconColor: Colors.white70,
                        title: Text(faq.$1,
                            style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w600)),
                        childrenPadding:
                            const EdgeInsets.fromLTRB(16, 0, 16, 16),
                        expandedAlignment: Alignment.centerLeft,
                        children: [
                          Text(faq.$2,
                              style: const TextStyle(color: Colors.white70)),
                        ],
                      ),
                  ],
                ),
              ),
            ),

            // ---- Contact ----
            _sectionTitle('Contact us'),
            Container(
              decoration: BoxDecoration(
                  color: _card, borderRadius: BorderRadius.circular(16)),
              child: Column(
                children: [
                  _contactTile(
                      Icons.email_outlined,
                      'Email',
                      _supportEmail,
                      () => _open(Uri(
                          scheme: 'mailto',
                          path: _supportEmail,
                          query: 'subject=Support request'))),
                  const Divider(height: 1, color: Colors.white12),
                  _contactTile(Icons.phone_outlined, 'Call', _supportPhone,
                      () => _open(Uri(scheme: 'tel', path: _supportPhone))),
                ],
              ),
            ),

            // ---- About ----
            _sectionTitle('About'),
            Container(
              decoration: BoxDecoration(
                  color: _card, borderRadius: BorderRadius.circular(16)),
              child: Column(
                children: [
                  ListTile(
                    onTap: () => showAboutDialog(
                      context: context,
                      applicationName: 'FitPass',
                      applicationVersion: _version,
                      applicationLegalese: '© 2026 FitPass',
                    ),
                    title: const Text('App version',
                        style: TextStyle(color: Colors.white)),
                    trailing: Text(_version,
                        style: const TextStyle(color: Colors.white54)),
                  ),
                  const Divider(height: 1, color: Colors.white12),
                  ListTile(
                    onTap: () => showLegalSheet(context, LegalDocument.terms),
                    title: const Text('Terms of Service',
                        style: TextStyle(color: Colors.white)),
                    trailing:
                        const Icon(Icons.chevron_right, color: Colors.white38),
                  ),
                  const Divider(height: 1, color: Colors.white12),
                  ListTile(
                    onTap: () => showLegalSheet(context, LegalDocument.privacy),
                    title: const Text('Privacy Policy',
                        style: TextStyle(color: Colors.white)),
                    trailing:
                        const Icon(Icons.chevron_right, color: Colors.white38),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
