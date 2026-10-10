import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme/app_colors.dart';
import '../../core/network/token_store.dart';
import '../../core/network/api_client.dart';
import '../statistics/statistics_screen.dart';
import '../collections/collections_screen.dart';
import '../sales/sales_screen.dart';
import '../orders/orders_screen.dart';

/// الشاشة الرئيسية — شريط تنقل سفلي بـ 4 تبويبات + قائمة جانبية.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

  static const _tabs = <Widget>[
    StatisticsScreen(),
    CollectionsScreen(),
    SalesScreen(),
    OrdersScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: const _AppDrawer(),
      appBar: _index == 0
          ? null
          : null,
      body: IndexedStack(index: _index, children: _tabs),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          border: const Border(top: BorderSide(color: AppColors.lineSoft)),
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.06),
              blurRadius: 16,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: NavigationBarTheme(
          data: NavigationBarThemeData(
            backgroundColor: AppColors.surface,
            indicatorColor: AppColors.primaryWash,
            labelTextStyle: WidgetStateProperty.resolveWith((states) {
              final selected = states.contains(WidgetState.selected);
              return TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.w600,
                color: selected ? AppColors.primaryDeep : AppColors.muted,
              );
            }),
          ),
          child: NavigationBar(
            height: 66,
            selectedIndex: _index,
            onDestinationSelected: (i) => setState(() => _index = i),
            destinations: const [
              NavigationDestination(
                icon: Icon(Icons.bar_chart_rounded, color: AppColors.muted),
                selectedIcon:
                    Icon(Icons.bar_chart_rounded, color: AppColors.primaryDeep),
                label: 'الإحصائيات',
              ),
              NavigationDestination(
                icon: Icon(Icons.payments_outlined, color: AppColors.muted),
                selectedIcon:
                    Icon(Icons.payments_rounded, color: AppColors.primaryDeep),
                label: 'المديونية',
              ),
              NavigationDestination(
                icon: Icon(Icons.shopping_bag_outlined, color: AppColors.muted),
                selectedIcon: Icon(Icons.shopping_bag_rounded,
                    color: AppColors.primaryDeep),
                label: 'المبيعات',
              ),
              NavigationDestination(
                icon: Icon(Icons.receipt_long_outlined, color: AppColors.muted),
                selectedIcon: Icon(Icons.receipt_long_rounded,
                    color: AppColors.primaryDeep),
                label: 'الفواتير',
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// القائمة الجانبية — الوصول لباقي شاشات الـ flow.
class _AppDrawer extends StatelessWidget {
  const _AppDrawer();

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: AppColors.surface,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(13),
                      gradient: const LinearGradient(
                          colors: [AppColors.primary, AppColors.primaryDeep]),
                    ),
                    child: const Icon(Icons.location_on_rounded,
                        color: Colors.white, size: 26),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(child: _DrawerUser()),
                ],
              ),
            ),
            const Divider(height: 1, color: AppColors.line),
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                children: [
                  // خاص بالمدير: يظهر فقط لو role=manager.
                  FutureBuilder<bool>(
                    future: TokenStore.instance.isManager,
                    builder: (context, snap) {
                      if (snap.data != true) return const SizedBox.shrink();
                      return Column(
                        children: [
                          _tile(context, Icons.map_outlined, 'المناديب',
                              '/manager/sellers'),
                          _tile(context, Icons.assignment_ind_outlined,
                              'طلبات المناديب', '/manager/requests'),
                          const Divider(height: 1, color: AppColors.lineSoft),
                        ],
                      );
                    },
                  ),
                  _tile(context, Icons.groups_outlined, 'العملاء',
                      '/customers'),
                  _tile(context, Icons.fingerprint, 'الحضور والبصمة',
                      '/attendance'),
                  _tile(context, Icons.location_on_outlined, 'الزيارات',
                      '/visits'),
                  _tile(context, Icons.person_add_alt, 'إضافة عميل',
                      '/add-customer'),
                  _tile(context, Icons.assignment_return_outlined,
                      'المرتجعات', '/returns'),
                  _tile(context, Icons.inventory_2_outlined, 'طلب موارد',
                      '/resources'),
                  _tile(context, Icons.card_giftcard_outlined, 'عيّنات',
                      '/samples'),
                  _tile(context, Icons.volunteer_activism_outlined, 'تبرعات',
                      '/donations'),
                  _tile(context, Icons.account_balance_wallet_outlined,
                      'الرواتب', '/salary'),
                  _tile(context, Icons.badge_outlined, 'شؤون الموظف', '/hr'),
                  _tile(context, Icons.local_shipping_outlined,
                      'صرف البضاعة', '/confirm-stock'),
                  _tile(context, Icons.assignment_turned_in_outlined,
                      'أوامر الصرف', '/issued-orders'),
                ],
              ),
            ),
            const Divider(height: 1, color: AppColors.line),
            _tile(context, Icons.logout, 'تسجيل الخروج', '/login',
                danger: true),
          ],
        ),
      ),
    );
  }

  Widget _tile(BuildContext context, IconData icon, String title, String route,
      {bool danger = false}) {
    final color = danger ? AppColors.danger : AppColors.inkSoft;
    return ListTile(
      leading: Icon(icon, color: color, size: 22),
      title: Text(title,
          style: TextStyle(
              color: color, fontWeight: FontWeight.w600, fontSize: 14)),
      onTap: () async {
        Navigator.pop(context);
        if (danger) {
          await TokenStore.instance.clear();
          if (context.mounted) context.go(route);
        } else {
          context.push(route);
        }
      },
    );
  }
}

/// اسم وكود المستخدم الحالي في رأس القائمة الجانبية.
/// يعرض الاسم المحفوظ عند الدخول فوراً، ثم يحدّثه من GET /profile.
class _DrawerUser extends StatefulWidget {
  const _DrawerUser();

  @override
  State<_DrawerUser> createState() => _DrawerUserState();
}

class _DrawerUserState extends State<_DrawerUser> {
  String? _name;
  String? _code;

  bool _manager = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final saved = await TokenStore.instance.sellerName();
    final manager = await TokenStore.instance.isManager;
    if (!mounted) return;
    setState(() {
      _name = saved;
      _manager = manager;
    });
    try {
      final p = await ApiClient.instance.get('/profile');
      if (p is! Map) return;
      String? s(dynamic v) {
        final t = v?.toString().trim();
        return (t == null || t.isEmpty || t == 'null') ? null : t;
      }

      final name = s(p['name']) ??
          [s(p['f_name']), s(p['l_name'])].whereType<String>().join(' ');
      final code = s(p['mandob_code']) ?? s(p['code']);
      if (!mounted) return;
      setState(() {
        if (name.isNotEmpty) _name = name;
        _code = code;
      });
    } catch (_) {
      // من غير نت: نكتفي بالاسم المحفوظ.
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = _manager ? 'مدير' : 'مندوب';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(_name ?? '…',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
        Text(_code == null ? role : '$role · $_code',
            style: const TextStyle(color: AppColors.muted, fontSize: 12)),
      ],
    );
  }
}
