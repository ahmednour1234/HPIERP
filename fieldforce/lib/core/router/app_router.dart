import 'package:go_router/go_router.dart';
import '../../features/auth/splash_screen.dart';
import '../../features/auth/login_screen.dart';
import '../../features/home/home_shell.dart';
import '../../features/attendance/attendance_screen.dart';
import '../../features/visits/visits_screen.dart';
import '../../features/customers/add_customer_screen.dart';
import '../../features/customers/customers_screen.dart';
import '../../features/customers/customer_detail_screen.dart';
import '../../features/customers/customer_repository.dart';
import '../../features/invoices/invoices_screen.dart';
import '../../features/invoices/invoice_detail_screen.dart';
import '../../features/collections/fund_list_screen.dart';
import '../../features/returns/returns_screen.dart';
import '../../features/resources/request_resources_screen.dart';
import '../../features/reseat/reseat_screen.dart';
import '../../features/reservations/issued_orders_screen.dart';
import '../../features/sales/sales_screen.dart';
import '../../features/salary/salary_screen.dart';
import '../../features/hr/hr_screen.dart';
import '../../features/manager/sellers_map_screen.dart';
import '../../features/manager/seller_detail_screen.dart';
import '../../features/manager/team_requests_screen.dart';
import '../../features/manager/manager_repository.dart';
import '../../features/stock/confirm_stock_screen.dart';

/// مسارات التطبيق.
class AppRouter {
  AppRouter._();

  static final GoRouter router = GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(
        path: '/',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/home',
        builder: (context, state) => const HomeShell(),
      ),
      GoRoute(
        path: '/attendance',
        builder: (context, state) => const AttendanceScreen(),
      ),
      GoRoute(
        path: '/visits',
        builder: (context, state) => const VisitsScreen(),
      ),
      GoRoute(
        path: '/add-customer',
        builder: (context, state) => const AddCustomerScreen(),
      ),
      GoRoute(
        path: '/customers',
        builder: (context, state) => const CustomersScreen(),
      ),
      GoRoute(
        path: '/customer',
        builder: (context, state) =>
            CustomerDetailScreen(customer: state.extra as Customer),
      ),
      GoRoute(
        path: '/invoices',
        builder: (context, state) {
          // extra يمكن أن يكون int (customerId) لعرض فواتير عميل محدّد.
          final extra = state.extra;
          return InvoicesScreen(customerId: extra is int ? extra : null);
        },
      ),
      GoRoute(
        path: '/order',
        builder: (context, state) =>
            InvoiceDetailScreen(orderId: state.extra as int),
      ),
      GoRoute(
        path: '/funds',
        builder: (context, state) => const FundListScreen(),
      ),
      GoRoute(
        path: '/salary',
        builder: (context, state) => const SalaryScreen(),
      ),
      GoRoute(
        path: '/hr',
        builder: (context, state) => const HrScreen(),
      ),
      GoRoute(
        path: '/manager/sellers',
        builder: (context, state) => const SellersMapScreen(),
      ),
      GoRoute(
        path: '/manager/requests',
        builder: (context, state) => const TeamRequestsScreen(),
      ),
      GoRoute(
        path: '/manager/seller',
        builder: (context, state) =>
            SellerDetailScreen(seller: state.extra as SellerInfo),
      ),
      GoRoute(
        path: '/confirm-stock',
        builder: (context, state) => const ConfirmStockScreen(),
      ),
      GoRoute(
        path: '/issued-orders',
        builder: (context, state) => const IssuedOrdersScreen(),
      ),
      GoRoute(
        path: '/sell',
        builder: (context, state) =>
            SalesScreen(customerId: state.extra as int?),
      ),
      GoRoute(
        path: '/visit',
        builder: (context, state) =>
            VisitsScreen(customerId: state.extra as int?),
      ),
      GoRoute(
        path: '/returns',
        builder: (context, state) =>
            ReturnsScreen(orderId: state.extra as int?),
      ),
      GoRoute(
        path: '/resources',
        builder: (context, state) => const RequestResourcesScreen(),
      ),
      GoRoute(
        path: '/samples',
        builder: (context, state) => ReseatScreen(
            kind: ReseatKind.sample, customerId: state.extra as int?),
      ),
      GoRoute(
        path: '/donations',
        builder: (context, state) => ReseatScreen(
            kind: ReseatKind.donation, customerId: state.extra as int?),
      ),
    ],
  );
}
