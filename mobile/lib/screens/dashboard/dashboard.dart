import 'dart:async';

import 'package:flutter/material.dart';

import '../../models/member.dart';
import '../../services/api.dart';
import '../auth/log_in.dart';
import '../history/history.dart';
import '../membership/membership.dart';
import '../../widgets/member_avatar.dart';
import '../profile/profile.dart';
import 'member_qr.dart';

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
    // The member may be the copy saved at the last login, if the app opened
    // offline; bring it up to date quietly once the server answers.
    _refresh(quiet: true);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  /// Fetches the member again, e.g. to see staff confirm a pending sign-up.
  /// [quiet] skips the error message, for the automatic checks.
  Future<void> _refresh({bool quiet = false}) async {
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
      if (quiet) return;
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
                    onPendingCheck: () => _refresh(quiet: true),
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
            onTap: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(
                    builder: (context) => ProfileScreen(member: _member)),
              );
              // Picks up a profile picture changed on the profile screen.
              // Not mounted when the member logged out from there.
              if (mounted) _refresh();
            },
            child: MemberAvatar(member: _member, radius: 18),
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

  /// Checks quietly whether staff have confirmed a pending sign-up.
  final Future<void> Function() onPendingCheck;

  const HomeTab({
    super.key,
    required this.member,
    required this.onRefresh,
    required this.onPendingCheck,
  });

  @override
  State<HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<HomeTab> {
  static const _green = Color(0xFF3ECF4A);
  static const _amber = Color(0xFFE0A22B);
  static const _cardColor = Color(0xFF1C1C1C);

  /// How often the tab asks the server for news, so a scan at the front desk
  /// shows on the phone within seconds.
  static const _pollEvery = Duration(seconds: 10);

  Timer? _timer;
  CheckInRecord? _latest;
  bool _loaded = false;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    // A pending check rebuilds this tab, so it only ever runs on the timer;
    // running it here as well would rebuild the tab over and over.
    if (!widget.member.isPending) _poll();
    _timer = Timer.periodic(_pollEvery, (_) => _poll());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _poll() async {
    if (widget.member.isPending) {
      await widget.onPendingCheck();
      return;
    }

    try {
      final latest = await Api.latestCheckIn();
      if (!mounted) return;
      setState(() {
        _latest = latest;
        _loaded = true;
        _failed = false;
      });
    } on ApiException {
      if (!mounted) return;
      // Keeps showing the last visit it knew; the next check tries again.
      setState(() => _failed = !_loaded);
    }
  }

  String _formatDuration(Duration d) {
    final hours = d.inHours;
    final minutes = (d.inMinutes % 60).toString().padLeft(2, '0');
    return hours == 0 ? '${d.inMinutes}m' : '${hours}h ${minutes}m';
  }

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

  void _openFullScreen() {
    Navigator.push(
      context,
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (context) => MemberQrScreen(member: widget.member),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: widget.onRefresh,
      color: _green,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(
            parent: BouncingScrollPhysics()),
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
        children: widget.member.qrCode == null
            ? [_buildPendingNotice()]
            : [
                _buildPassCard(),
                const SizedBox(height: 16),
                _buildLatestCheckIn(),
              ],
      ),
    );
  }

  // ---- The member pass: status, QR code and the code as text ----
  Widget _buildPassCard() {
    final member = widget.member;
    final code = member.qrCode!;
    final statusColor = switch (member.status) {
      'Active' => _green,
      'Expired' => Colors.redAccent,
      _ => _amber,
    };

    return Container(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${member.plan} Member',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  member.status.toUpperCase(),
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.8,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          LayoutBuilder(
            builder: (context, constraints) {
              final size =
                  constraints.maxWidth < 300 ? constraints.maxWidth : 300.0;
              return GestureDetector(
                onTap: _openFullScreen,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: MemberQr(code: code, size: size),
                ),
              );
            },
          ),
          const SizedBox(height: 14),
          SelectableText(
            code,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w700,
              letterSpacing: 3,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            member.status == 'Expired'
                ? 'Your membership has expired. Renew at the front desk.'
                : 'Scan at the front desk to check in and out',
            textAlign: TextAlign.center,
            style: const TextStyle(color: Colors.white54, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildPendingNotice() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _amber),
      ),
      child: const Column(
        children: [
          Icon(Icons.hourglass_top_rounded, color: _amber, size: 40),
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

  // ---- Latest visit ----
  Widget _buildLatestCheckIn() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Builder(
        builder: (context) {
          var icon = Icons.history;
          var color = Colors.white38;
          var title = 'Latest visit';
          final String detail;

          final visit = _latest;
          if (_failed) {
            icon = Icons.error_outline;
            detail = 'Could not load your latest check-in';
          } else if (!_loaded) {
            detail = 'Loading…';
          } else if (visit == null) {
            detail = 'No check-ins yet';
          } else if (visit.timeOut == null) {
            // Rebuilt by every check, so the time inside keeps counting up.
            icon = Icons.login;
            color = _green;
            title = 'Checked in  •  '
                '${_formatDuration(DateTime.now().difference(visit.timeIn))}';
            detail = 'Since ${_formatTime(visit.timeIn)}';
          } else {
            icon = Icons.logout;
            color = Colors.white70;
            detail =
                '${_formatTime(visit.timeIn)} – ${_formatTime(visit.timeOut!)}';
          }

          return Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: color.withValues(alpha: 0.15),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      detail,
                      style:
                          const TextStyle(color: Colors.white54, fontSize: 12),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
