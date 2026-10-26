import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../models/member.dart';
import '../../services/api.dart';
import '../auth/log_in.dart';
import '../history/history.dart';
import '../membership/membership.dart';
import '../profile/profile.dart';

class Dashboard extends StatefulWidget {
  /// The logged-in member, as returned by the login or a restored session.
  final Member member;

  const Dashboard({super.key, required this.member});

  @override
  State<Dashboard> createState() => _DashboardState();
}

class _DashboardState extends State<Dashboard>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  final List<String> _tabs = const ['Home', 'History', 'Membership'];

  late Member _member = widget.member;
  int _refreshCount = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _tabs.length, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  /// Fetches the member again, e.g. to see staff confirm a pending sign-up.
  Future<void> _refresh() async {
    try {
      final member = await Api.me();
      if (!mounted) return;
      setState(() {
        _member = member;
        _refreshCount++;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.isUnauthorized) {
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (context) => const LoginScreen()),
          (route) => false,
        );
        return;
      }
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0E0E0E),
      body: SafeArea(
        child: Column(
          children: [
            _buildTopBar(),
            const SizedBox(height: 8),
            _buildNavBar(),
            const Divider(color: Colors.white12, height: 1),
            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  HomeTab(
                    // A new key reloads the latest check-in after a refresh.
                    key: ValueKey(_refreshCount),
                    member: _member,
                    onRefresh: _refresh,
                  ),
                  const HistoryTab(),
                  MembershipTab(member: _member, onRefresh: _refresh),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---- Top app bar: "Hello, <name>!" + avatar ----
  Widget _buildTopBar() {
    final firstName = _member.name.split(' ').first;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              'Hello, $firstName!',
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color.fromARGB(255, 250, 250, 250),
              ),
            ),
          ),
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                    builder: (context) => ProfileScreen(member: _member)),
              );
            },
            child: const CircleAvatar(
              radius: 18,
              backgroundColor: Colors.white24,
              child: Icon(Icons.person, color: Colors.white70, size: 20),
            ),
          ),
        ],
      ),
    );
  }

  // ---- Clickable navbar: Home | History | Membership ----
  Widget _buildNavBar() {
    return TabBar(
      controller: _tabController,
      isScrollable: false,
      indicatorColor: Colors.transparent,
      labelColor: const Color(0xFF3ECF4A),
      unselectedLabelColor: Colors.white54,
      labelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
      unselectedLabelStyle:
          const TextStyle(fontWeight: FontWeight.w500, fontSize: 14),
      tabs: _tabs.map((t) => Tab(text: t)).toList(),
    );
  }
}

class HomeTab extends StatefulWidget {
  final Member member;
  final Future<void> Function() onRefresh;

  const HomeTab({super.key, required this.member, required this.onRefresh});

  @override
  State<HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<HomeTab> {
  late final Future<CheckInRecord?> _latest = widget.member.isPending
      ? Future.value(null)
      : Api.latestCheckIn();

  static const _months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];

  String _formatTime(DateTime time) {
    final hour = time.hour % 12 == 0 ? 12 : time.hour % 12;
    final minute = time.minute.toString().padLeft(2, '0');
    final period = time.hour < 12 ? 'AM' : 'PM';
    return '${_months[time.month - 1]} ${time.day}, $hour:$minute $period';
  }

  @override
  Widget build(BuildContext context) {
    final qrCode = widget.member.qrCode;

    return RefreshIndicator(
      onRefresh: widget.onRefresh,
      color: const Color(0xFF3ECF4A),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(
            parent: BouncingScrollPhysics()),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
        child: Column(
          children: [
            const SizedBox(height: 24),
            if (qrCode == null)
              _buildPendingNotice()
            else ...[
              const Text(
                'Scan this QR',
                style: TextStyle(
                  color: Colors.white70,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: QrImageView(
                  data: qrCode,
                  version: QrVersions.auto,
                  size: 220,
                  backgroundColor: Colors.white,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                qrCode,
                style: const TextStyle(
                  color: Colors.white54,
                  fontSize: 13,
                  letterSpacing: 2,
                ),
              ),
              const SizedBox(height: 32),
              _buildLatestCheckIn(),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildPendingNotice() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1C1C),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE0A22B)),
      ),
      child: const Column(
        children: [
          Icon(Icons.hourglass_top_rounded, color: Color(0xFFE0A22B), size: 40),
          SizedBox(height: 12),
          Text(
            'Waiting for payment',
            style: TextStyle(
              color: Colors.white,
              fontSize: 17,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 8),
          Text(
            'Pay at the front desk to activate your membership. Your QR code '
            'appears here once staff confirm it.\n\nPull down to check again.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _buildLatestCheckIn() {
    return FutureBuilder<CheckInRecord?>(
      future: _latest,
      builder: (context, snapshot) {
        const style = TextStyle(color: Colors.white54, fontSize: 13);

        if (snapshot.connectionState != ConnectionState.done) {
          return const SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(strokeWidth: 2),
          );
        }
        if (snapshot.hasError) {
          return const Text('Could not load your latest check-in', style: style);
        }

        final visit = snapshot.data;
        if (visit == null) return const Text('No check-ins yet', style: style);

        final timeOut = visit.timeOut;
        return Text(
          timeOut == null
              ? 'Checked in since ${_formatTime(visit.timeIn)}'
              : 'Last visit: ${_formatTime(visit.timeIn)} – ${_formatTime(timeOut)}',
          textAlign: TextAlign.center,
          style: style,
        );
      },
    );
  }
}
