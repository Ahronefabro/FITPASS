import 'package:flutter/material.dart';

enum LegalDocument { terms, privacy }

class _LegalSection {
  final String title;
  final String body;
  const _LegalSection(this.title, this.body);
}

const String _lastUpdated = 'November 9, 2026';

// TODO: Replace the placeholders (contact details, rules, fees) with your
// gym's real information and have the text reviewed before going live.
const List<_LegalSection> _termsSections = [
  _LegalSection(
    '1. Acceptance of Terms',
    'By creating an account and using the FitPass Gym app, you agree to these '
        'Terms of Service. If you do not agree, please do not register or use '
        'the app.',
  ),
  _LegalSection(
    '2. Your Account',
    'You must provide accurate information when you register. You are '
        'responsible for keeping your password confidential and for all '
        'activity under your account. Your QR code is for your personal use '
        'only and must not be shared or copied.',
  ),
  _LegalSection(
    '3. Membership and Payments',
    'Membership plans and prices are set by the gym and shown during '
        'registration. Your membership becomes active once the gym staff '
        'confirms your payment. Fees are charged per the plan you choose '
        '(for example, monthly) and are non-refundable unless the gym decides '
        'otherwise. Prices may change with reasonable notice.',
  ),
  _LegalSection(
    '4. Gym Rules and Conduct',
    'You agree to follow the gym rules, use equipment properly, respect '
        'staff and other members, and wipe down equipment after use. The gym '
        'may suspend or terminate memberships for unsafe, abusive, or '
        'disruptive behavior without refund.',
  ),
  _LegalSection(
    '5. Health and Assumption of Risk',
    'Exercise involves risk of injury. You confirm that you are physically '
        'able to exercise and that you will consult a doctor if you have any '
        'medical concerns. You use the gym and its equipment at your own '
        'risk. The gym is not liable for injury or loss of personal belongings '
        'except where required by law.',
  ),
  _LegalSection(
    '6. Check-ins and Attendance',
    'The app and gym staff record your check-ins and check-outs for '
        'attendance and membership validation. Misuse of check-in, such as '
        'scanning for another person, may lead to suspension.',
  ),
  _LegalSection(
    '7. Suspension and Termination',
    'The gym may suspend or end your account if you break these terms or if '
        'your membership expires. You may ask to close your account at any '
        'time by contacting the gym.',
  ),
  _LegalSection(
    '8. Changes to These Terms',
    'We may update these Terms from time to time. Continued use of the app '
        'after changes means you accept the updated Terms.',
  ),
  _LegalSection(
    '9. Contact Us',
    'FitPass Gym\n[Gym address]\n[Email address]\n[Phone number]',
  ),
];

const List<_LegalSection> _privacySections = [
  _LegalSection(
    '1. Information We Collect',
    'When you register we collect your name, email address, phone number, '
        'and chosen membership plan. We also record your payments, '
        'check-in and check-out times, and your account password (stored in '
        'encrypted form).',
  ),
  _LegalSection(
    '2. How We Use Your Information',
    'We use your information to create and manage your account, verify your '
        'membership, record attendance and payments, send you notifications '
        '(such as membership reminders), and improve our services.',
  ),
  _LegalSection(
    '3. Sharing of Information',
    'We do not sell your personal information. Your data is accessible only '
        'to authorized gym staff and the gym owner for running the gym. We may '
        'disclose information if required by law.',
  ),
  _LegalSection(
    '4. Data Retention',
    'We keep your information while your account is active and for as long '
        'as needed for record keeping, legal, or accounting purposes. You may '
        'request deletion of your account data, subject to those requirements.',
  ),
  _LegalSection(
    '5. Security',
    'We take reasonable steps to protect your information from unauthorized '
        'access, loss, or misuse. No system is completely secure, so please '
        'keep your password safe.',
  ),
  _LegalSection(
    '6. Your Rights',
    'In accordance with the Data Privacy Act of 2012 (Republic Act 10173), '
        'you have the right to be informed, to access and correct your '
        'personal data, to object to or request removal of your data, and to '
        'file a complaint with the National Privacy Commission.',
  ),
  _LegalSection(
    '7. Changes to This Policy',
    'We may update this Privacy Policy from time to time. We will notify you '
        'of significant changes through the app.',
  ),
  _LegalSection(
    '8. Contact Us',
    'For privacy questions or requests, contact:\nFitPass Gym\n'
        '[Data protection contact]\n[Email address]\n[Phone number]',
  ),
];

/// Opens a scrollable bottom sheet showing the Terms or the Privacy Policy.
void showLegalSheet(BuildContext context, LegalDocument document) {
  final isTerms = document == LegalDocument.terms;
  final title = isTerms ? 'Terms of Service' : 'Privacy Policy';
  final sections = isTerms ? _termsSections : _privacySections;

  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: const Color(0xFF141414),
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (sheetContext) => DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.85,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      builder: (context, scrollController) {
        return Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFF8A8A8A),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 8, 0),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Color(0xFF8A8A8A)),
                    onPressed: () => Navigator.pop(sheetContext),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Last updated: $_lastUpdated',
                  style: const TextStyle(
                    color: Color(0xFF8A8A8A),
                    fontSize: 12,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
            const Divider(color: Color(0xFF262626), height: 1),
            Expanded(
              child: ListView.builder(
                controller: scrollController,
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
                itemCount: sections.length,
                itemBuilder: (context, index) {
                  final section = sections[index];
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          section.title,
                          style: const TextStyle(
                            color: Color(0xFF4CD964),
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          section.body,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 13.5,
                            height: 1.5,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 20),
              child: SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(sheetContext),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4CD964),
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(26),
                    ),
                  ),
                  child: const Text(
                    'CLOSE',
                    style: TextStyle(
                      color: Colors.black,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ),
          ],
        );
      },
    ),
  );
}
