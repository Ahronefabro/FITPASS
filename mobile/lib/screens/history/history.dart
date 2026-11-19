import 'package:flutter/material.dart';

import '../../services/api.dart';

enum HistoryPeriod { week, month, year }

/// A single gym visit. [timeIn] and [timeOut] are full DateTimes so
/// duration, grouping, and range filtering can all be computed instead
/// of hand-typed.
class HistoryEntry {
  final String id;
  final DateTime timeIn;
  final DateTime? timeOut;

  const HistoryEntry({
    required this.id,
    required this.timeIn,
    this.timeOut,
  });

  bool get isActive => timeOut == null;

  Duration? get duration => timeOut?.difference(timeIn);

  factory HistoryEntry.fromRecord(CheckInRecord record) {
    return HistoryEntry(
      id: record.id.toString(),
      timeIn: record.timeIn,
      timeOut: record.timeOut,
    );
  }
}

/// The single seam HistoryTab loads its visits through.
class HistoryApi {
  static Future<List<HistoryEntry>> fetch({
    required DateTime from,
    required DateTime to,
  }) async {
    final records = await Api.checkIns(from: from, to: to);
    return records.map(HistoryEntry.fromRecord).toList();
  }
}

class HistoryTab extends StatefulWidget {
  const HistoryTab({super.key});

  @override
  State<HistoryTab> createState() => _HistoryTabState();
}

class _HistoryTabState extends State<HistoryTab> {
  HistoryPeriod _selectedPeriod = HistoryPeriod.week;
  DateTime _anchorDate = DateTime.now();

  bool _isLoading = true;
  String? _errorMessage;
  List<HistoryEntry> _entries = [];

  /// Ids of visit cards that are currently expanded.
  final Set<String> _expandedIds = {};

  static const _accentGreen = Color(0xFF3ECF4A);
  static const _warningAmber = Color(0xFFE0A22B);
  static const _cardColor = Color(0xFF1C1C1C);

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  // ---- Date range math ----

  /// Start of the current range (inclusive), based on _selectedPeriod
  /// and _anchorDate.
  DateTime get _rangeStart {
    switch (_selectedPeriod) {
      case HistoryPeriod.week:
        // Monday as the start of the week.
        final weekday = _anchorDate.weekday; // 1 = Mon ... 7 = Sun
        final monday = _anchorDate.subtract(Duration(days: weekday - 1));
        return DateTime(monday.year, monday.month, monday.day);
      case HistoryPeriod.month:
        return DateTime(_anchorDate.year, _anchorDate.month, 1);
      case HistoryPeriod.year:
        return DateTime(_anchorDate.year, 1, 1);
    }
  }

  /// End of the current range (inclusive, end-of-day).
  DateTime get _rangeEnd {
    switch (_selectedPeriod) {
      case HistoryPeriod.week:
        final start = _rangeStart;
        return DateTime(start.year, start.month, start.day + 6, 23, 59, 59);
      case HistoryPeriod.month:
        final firstOfNextMonth = _anchorDate.month == 12
            ? DateTime(_anchorDate.year + 1, 1, 1)
            : DateTime(_anchorDate.year, _anchorDate.month + 1, 1);
        return firstOfNextMonth.subtract(const Duration(seconds: 1));
      case HistoryPeriod.year:
        return DateTime(_anchorDate.year, 12, 31, 23, 59, 59);
    }
  }

  String get _rangeLabel {
    const months = [
      'JAN',
      'FEB',
      'MAR',
      'APR',
      'MAY',
      'JUN',
      'JUL',
      'AUG',
      'SEP',
      'OCT',
      'NOV',
      'DEC',
    ];
    final start = _rangeStart;
    final end = _rangeEnd;
    switch (_selectedPeriod) {
      case HistoryPeriod.week:
        final sameMonth = start.month == end.month;
        final startStr = '${months[start.month - 1]} ${start.day}';
        final endStr =
            sameMonth ? '${end.day}' : '${months[end.month - 1]} ${end.day}';
        return '$startStr - $endStr, ${end.year}';
      case HistoryPeriod.month:
        return '${months[start.month - 1]} ${start.year}';
      case HistoryPeriod.year:
        return '${start.year}';
    }
  }

  // ---- Actions ----

  void _selectPeriod(HistoryPeriod period) {
    if (period == _selectedPeriod) return;
    setState(() => _selectedPeriod = period);
    _loadHistory();
  }

  void _goToPreviousRange() {
    setState(() {
      switch (_selectedPeriod) {
        case HistoryPeriod.week:
          _anchorDate = _anchorDate.subtract(const Duration(days: 7));
          break;
        case HistoryPeriod.month:
          _anchorDate = DateTime(_anchorDate.year, _anchorDate.month - 1, 1);
          break;
        case HistoryPeriod.year:
          _anchorDate = DateTime(_anchorDate.year - 1, _anchorDate.month, 1);
          break;
      }
    });
    _loadHistory();
  }

  void _goToNextRange() {
    setState(() {
      switch (_selectedPeriod) {
        case HistoryPeriod.week:
          _anchorDate = _anchorDate.add(const Duration(days: 7));
          break;
        case HistoryPeriod.month:
          _anchorDate = DateTime(_anchorDate.year, _anchorDate.month + 1, 1);
          break;
        case HistoryPeriod.year:
          _anchorDate = DateTime(_anchorDate.year + 1, _anchorDate.month, 1);
          break;
      }
    });
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    try {
      final entries = await HistoryApi.fetch(from: _rangeStart, to: _rangeEnd);
      entries.sort((a, b) => b.timeIn.compareTo(a.timeIn)); // newest first
      if (!mounted) return;
      setState(() {
        _entries = entries;
        _expandedIds.clear();
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _errorMessage = "Couldn't load history. Pull down to retry.";
        _isLoading = false;
      });
    }
  }

  // ---- Derived stats ----

  int get _visitCount => _entries.length;

  Duration get _totalDuration => _entries.fold(
        Duration.zero,
        (sum, e) => sum + (e.duration ?? Duration.zero),
      );

  String _formatDuration(Duration d) {
    final hours = d.inHours;
    final minutes = d.inMinutes % 60;
    if (hours == 0) return '${minutes}m';
    if (minutes == 0) return '${hours}h';
    return '${hours}h ${minutes}m';
  }

  String _formatDate(DateTime date) {
    const months = [
      'JAN',
      'FEB',
      'MAR',
      'APR',
      'MAY',
      'JUN',
      'JUL',
      'AUG',
      'SEP',
      'OCT',
      'NOV',
      'DEC',
    ];
    return '${months[date.month - 1]} ${date.day}, ${date.year}';
  }

  String _formatTime(DateTime dt) {
    final hour12 = dt.hour % 12 == 0 ? 12 : dt.hour % 12;
    final minute = dt.minute.toString().padLeft(2, '0');
    final suffix = dt.hour >= 12 ? 'PM' : 'AM';
    return '$hour12:$minute $suffix';
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SizedBox(height: 8),
        _buildPeriodToggle(),
        const SizedBox(height: 16),
        _buildRangeNavigator(),
        const SizedBox(height: 16),
        if (!_isLoading && _errorMessage == null && _entries.isNotEmpty)
          _buildStatsRow(),
        Expanded(child: _buildBody()),
      ],
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: _accentGreen),
      );
    }
    if (_errorMessage != null) {
      return _buildMessageState(
        icon: Icons.wifi_off_rounded,
        message: _errorMessage!,
        actionLabel: 'Retry',
        onAction: _loadHistory,
      );
    }
    if (_entries.isEmpty) {
      return _buildMessageState(
        icon: Icons.fitness_center,
        message: 'No visits in this period',
      );
    }
    return RefreshIndicator(
      color: _accentGreen,
      backgroundColor: _cardColor,
      onRefresh: _loadHistory,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(
          parent: BouncingScrollPhysics(),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
        itemCount: _entries.length,
        separatorBuilder: (_, __) => const Divider(
          color: Colors.white12,
          height: 1,
        ),
        itemBuilder: (context, index) => _buildEntryCard(_entries[index]),
      ),
    );
  }

  Widget _buildMessageState({
    required IconData icon,
    required String message,
    String? actionLabel,
    VoidCallback? onAction,
  }) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white24, size: 40),
          const SizedBox(height: 12),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: Colors.white54, fontSize: 14),
          ),
          if (actionLabel != null && onAction != null) ...[
            const SizedBox(height: 12),
            TextButton(
              onPressed: onAction,
              child: Text(
                actionLabel,
                style: const TextStyle(color: _accentGreen),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ---- Week / Month / Year toggle ----
  Widget _buildPeriodToggle() {
    Widget periodButton(String label, HistoryPeriod period) {
      final bool isSelected = _selectedPeriod == period;
      return GestureDetector(
        onTap: () => _selectPeriod(period),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? _accentGreen : Colors.white54,
            fontWeight: FontWeight.w700,
            fontSize: 13,
            letterSpacing: 0.5,
          ),
        ),
      );
    }

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        periodButton('WEEK', HistoryPeriod.week),
        periodButton('MONTH', HistoryPeriod.month),
        periodButton('YEAR', HistoryPeriod.year),
      ],
    );
  }

  // ---- "< OCT 05 - OCT 16, 2025 >" ----
  Widget _buildRangeNavigator() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        IconButton(
          onPressed: _goToPreviousRange,
          icon: const Icon(Icons.chevron_left, color: Colors.white),
        ),
        Text(
          _rangeLabel,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w700,
            fontSize: 14,
            letterSpacing: 0.5,
          ),
        ),
        IconButton(
          onPressed: _goToNextRange,
          icon: const Icon(Icons.chevron_right, color: Colors.white),
        ),
      ],
    );
  }

  // ---- Visits / total time summary ----
  Widget _buildStatsRow() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        children: [
          Expanded(child: _statCard('Visits', '$_visitCount')),
          const SizedBox(width: 12),
          Expanded(
            child: _statCard('Total time', _formatDuration(_totalDuration)),
          ),
        ],
      ),
    );
  }

  Widget _statCard(String label, String value) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label,
              style: const TextStyle(color: Colors.white54, fontSize: 12)),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  // ---- Individual log card (tap to expand) ----
  void _toggleEntry(String id) {
    setState(() {
      if (!_expandedIds.remove(id)) _expandedIds.add(id);
    });
  }

  Widget _buildEntryCard(HistoryEntry entry) {
    final isActive = entry.isActive;
    final isExpanded = _expandedIds.contains(entry.id);

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 6),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(12),
        border: isActive
            ? Border.all(color: _warningAmber.withValues(alpha: 0.4))
            : null,
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => _toggleEntry(entry.id),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Always visible: date, duration, and the arrow.
                Row(
                  children: [
                    Expanded(
                      child: Row(
                        children: [
                          Flexible(
                            child: Text(
                              _formatDate(entry.timeIn),
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                                fontSize: 14,
                              ),
                            ),
                          ),
                          if (isActive) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: _warningAmber.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: const Text(
                                'ACTIVE',
                                style: TextStyle(
                                  color: _warningAmber,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    Text(
                      entry.duration != null
                          ? _formatDuration(entry.duration!)
                          : 'In progress',
                      style: TextStyle(
                        color: isActive ? _warningAmber : Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(width: 6),
                    AnimatedRotation(
                      turns: isExpanded ? 0.25 : 0, // right arrow -> down arrow
                      duration: const Duration(milliseconds: 200),
                      child: const Icon(Icons.chevron_right,
                          color: Colors.white38),
                    ),
                  ],
                ),

                // Revealed on tap: time in / time out.
                AnimatedSize(
                  duration: const Duration(milliseconds: 200),
                  curve: Curves.easeInOut,
                  alignment: Alignment.topCenter,
                  child: isExpanded
                      ? Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Column(
                            children: [
                              const Divider(color: Colors.white12, height: 1),
                              const SizedBox(height: 12),
                              _timeRow(
                                Icons.login_rounded,
                                'Time in',
                                _formatTime(entry.timeIn),
                                Colors.white,
                              ),
                              const SizedBox(height: 8),
                              _timeRow(
                                Icons.logout_rounded,
                                'Time out',
                                entry.timeOut != null
                                    ? _formatTime(entry.timeOut!)
                                    : 'Not yet',
                                isActive ? _warningAmber : Colors.white,
                              ),
                            ],
                          ),
                        )
                      : const SizedBox(width: double.infinity),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _timeRow(IconData icon, String label, String value, Color valueColor) {
    return Row(
      children: [
        Icon(icon, color: Colors.white38, size: 16),
        const SizedBox(width: 8),
        Text(label,
            style: const TextStyle(color: Colors.white54, fontSize: 13)),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            color: valueColor,
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
