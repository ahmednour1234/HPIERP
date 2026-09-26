import 'package:flutter_test/flutter_test.dart';
import 'package:fieldforce/main.dart';

void main() {
  testWidgets('App boots to splash', (WidgetTester tester) async {
    await tester.pumpWidget(const FieldForceApp());
    await tester.pump();
    // شاشة البداية تعرض مؤشر تحميل قبل تقرير الوجهة.
    expect(find.byType(FieldForceApp), findsOneWidget);
  });
}
