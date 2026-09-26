import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import 'manager_repository.dart';

/// شاشة المدير — خريطة المناديب + قائمتهم مع حالة اليوم.
class SellersMapScreen extends StatefulWidget {
  const SellersMapScreen({super.key});

  @override
  State<SellersMapScreen> createState() => _SellersMapScreenState();
}

class _SellersMapScreenState extends State<SellersMapScreen> {
  final _repo = ManagerRepository();
  late Future<List<SellerInfo>> _future;
  final _mapController = MapController();

  @override
  void initState() {
    super.initState();
    _future = _repo.sellers();
  }

  Future<void> _refresh() async {
    setState(() => _future = _repo.sellers());
    await _future;
  }

  Color _statusColor(String s) => switch (s) {
        'present' => AppColors.good,
        'checked_out' => AppColors.primaryDeep,
        _ => AppColors.danger,
      };

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('المناديب'),
          bottom: const TabBar(
            labelColor: AppColors.primaryDeep,
            unselectedLabelColor: AppColors.muted,
            indicatorColor: AppColors.primary,
            labelStyle: TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            tabs: [
              Tab(text: 'الخريطة', icon: Icon(Icons.map_outlined, size: 20)),
              Tab(text: 'القائمة', icon: Icon(Icons.list_rounded, size: 20)),
            ],
          ),
        ),
        body: FutureBuilder<List<SellerInfo>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const Center(
                  child: CircularProgressIndicator(color: AppColors.primary));
            }
            if (snap.hasError) {
              final msg = snap.error is ApiException
                  ? (snap.error as ApiException).message
                  : 'تعذّر تحميل المناديب';
              return _errorView(msg);
            }
            final sellers = snap.data!;
            return TabBarView(
              children: [
                _mapTab(sellers),
                _listTab(sellers),
              ],
            );
          },
        ),
      ),
    );
  }

  // ---------- الخريطة ----------
  Widget _mapTab(List<SellerInfo> sellers) {
    final located = sellers.where((s) => s.hasLocation).toList();
    if (located.isEmpty) {
      return _centered(Icons.location_off_outlined,
          'لا توجد مواقع مسجّلة للمناديب بعد');
    }
    // مركز الخريطة = متوسط المواقع.
    final avgLat =
        located.map((s) => s.lat!).reduce((a, b) => a + b) / located.length;
    final avgLng =
        located.map((s) => s.lng!).reduce((a, b) => a + b) / located.length;

    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: LatLng(avgLat, avgLng),
        initialZoom: 6.5,
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.hpii.app',
        ),
        MarkerLayer(
          markers: [
            for (final s in located)
              Marker(
                point: LatLng(s.lat!, s.lng!),
                width: 44,
                height: 54,
                child: GestureDetector(
                  onTap: () => _openSeller(s),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: _statusColor(s.todayStatus),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                          boxShadow: AppColors.softShadow,
                        ),
                        child: const Icon(Icons.person,
                            color: Colors.white, size: 18),
                      ),
                      Icon(Icons.arrow_drop_down,
                          color: _statusColor(s.todayStatus), size: 20),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }

  // ---------- القائمة ----------
  Widget _listTab(List<SellerInfo> sellers) {
    if (sellers.isEmpty) {
      return _centered(Icons.people_outline, 'لا يوجد مناديب');
    }
    return RefreshIndicator(
      onRefresh: _refresh,
      color: AppColors.primary,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
        itemCount: sellers.length,
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (context, i) => _sellerCard(sellers[i]),
      ),
    );
  }

  Widget _sellerCard(SellerInfo s) {
    return GestureDetector(
      onTap: () => _openSeller(s),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.line),
        ),
        child: Row(
          children: [
            Stack(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: AppColors.primaryWash,
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: const Icon(Icons.person_rounded,
                      color: AppColors.primaryDeep, size: 24),
                ),
                Positioned(
                  right: 0,
                  bottom: 0,
                  child: Container(
                    width: 14,
                    height: 14,
                    decoration: BoxDecoration(
                      color: _statusColor(s.todayStatus),
                      shape: BoxShape.circle,
                      border: Border.all(color: AppColors.surface, width: 2),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(s.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 14.5, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      Text(s.code,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.muted)),
                      if (s.todayCheckIn != null) ...[
                        const SizedBox(width: 8),
                        const Icon(Icons.login_rounded,
                            size: 12, color: AppColors.muted),
                        const SizedBox(width: 2),
                        Text(s.todayCheckIn!,
                            style: const TextStyle(
                                fontSize: 11.5, color: AppColors.muted)),
                      ],
                      // درجة التقييم — تظهر فقط لو المندوب مُقيَّم.
                      if (s.rating != null) ...[
                        const SizedBox(width: 8),
                        Icon(Icons.star_rounded,
                            size: 13, color: _ratingColor(s.rating!)),
                        const SizedBox(width: 2),
                        Text('${s.rating}',
                            style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: FontWeight.w700,
                                color: _ratingColor(s.rating!))),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                switch (s.todayStatus) {
                  'present' => StatusBadge.good('حاضر'),
                  'checked_out' => StatusBadge.warn('منصرف'),
                  _ => StatusBadge.danger('غائب'),
                },
                const SizedBox(height: 6),
                const Icon(Icons.chevron_left, size: 18, color: AppColors.muted),
              ],
            ),
          ],
        ),
      ),
    );
  }

  static Color _ratingColor(num score) {
    if (score >= 75) return AppColors.good;
    if (score >= 50) return AppColors.warn;
    return AppColors.danger;
  }

  void _openSeller(SellerInfo s) {
    context.push('/manager/seller', extra: s);
  }

  Widget _errorView(String msg) => Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, size: 48, color: AppColors.muted),
            const SizedBox(height: 10),
            Text(msg, style: const TextStyle(color: AppColors.muted)),
            const SizedBox(height: 14),
            OutlinedButton(
                onPressed: _refresh, child: const Text('إعادة المحاولة')),
          ],
        ),
      );

  Widget _centered(IconData icon, String text) => ListView(children: [
        const SizedBox(height: 120),
        Icon(icon, size: 48, color: AppColors.muted),
        const SizedBox(height: 10),
        Center(
            child: Text(text, style: const TextStyle(color: AppColors.muted))),
      ]);
}
