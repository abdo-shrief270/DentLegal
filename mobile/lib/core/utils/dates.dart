import 'package:hijri/hijri_calendar.dart';

/// Arabic date display with Western digits: Gregorian and Hijri (Umm al-Qura) together, as the PRD requires.
/// Matches the web dashboard's formatting.
abstract final class AppDates {
  static const _gregorianMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
  static const _hijriMonths = [
    'محرم',
    'صفر',
    'ربيع الأول',
    'ربيع الآخر',
    'جمادى الأولى',
    'جمادى الآخرة',
    'رجب',
    'شعبان',
    'رمضان',
    'شوال',
    'ذو القعدة',
    'ذو الحجة',
  ];

  /// «29 أكتوبر 2026»
  static String gregorian(DateTime date) => '${date.day} ${_gregorianMonths[date.month - 1]} ${date.year}';

  /// «18 جمادى الأولى 1448 هـ» (Umm al-Qura via package:hijri).
  static String hijri(DateTime date) {
    final h = HijriCalendar.fromDate(date);
    return '${h.hDay} ${_hijriMonths[h.hMonth - 1]} ${h.hYear} هـ';
  }

  /// «29 أكتوبر 2026 · 18 جمادى الأولى 1448 هـ»
  static String both(DateTime date) => '${gregorian(date)} · ${hijri(date)}';

  /// «10:05 ص»
  static String time(DateTime date) {
    final hour = date.hour % 12 == 0 ? 12 : date.hour % 12;
    final minute = date.minute.toString().padLeft(2, '0');
    return '$hour:$minute ${date.hour < 12 ? 'ص' : 'م'}';
  }

  /// «منذ 3 ساعات», «منذ يومين», «الآن»
  static String relative(DateTime date, {DateTime? now}) {
    final diff = (now ?? DateTime.now()).difference(date);
    if (diff.inMinutes < 1) return 'الآن';
    if (diff.inMinutes < 60) return 'منذ ${_count(diff.inMinutes, 'دقيقة', 'دقيقتين', 'دقائق')}';
    if (diff.inHours < 24) return 'منذ ${_count(diff.inHours, 'ساعة', 'ساعتين', 'ساعات')}';
    return 'منذ ${_count(diff.inDays, 'يوم', 'يومين', 'أيام')}';
  }

  static String _count(int n, String one, String two, String few) => switch (n) {
    1 => one,
    2 => two,
    >= 3 && <= 10 => '$n $few',
    _ => '$n $one',
  };
}
