import 'package:dentlegal/core/utils/dates.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  final day = DateTime(2026, 10, 29, 10, 5);

  test('Gregorian date in Arabic with Western digits', () {
    expect(AppDates.gregorian(day), '29 أكتوبر 2026');
  });

  test('Hijri date uses Umm al-Qura', () {
    expect(AppDates.hijri(day), '18 جمادى الأولى 1448 هـ');
  });

  test('time uses ص/م', () {
    expect(AppDates.time(day), '10:05 ص');
    expect(AppDates.time(DateTime(2026, 1, 1, 13, 30)), '1:30 م');
  });

  test('relative time uses Arabic number agreement', () {
    expect(AppDates.relative(day, now: day.add(const Duration(hours: 2))), 'منذ ساعتين');
    expect(AppDates.relative(day, now: day.add(const Duration(hours: 5))), 'منذ 5 ساعات');
  });
}
