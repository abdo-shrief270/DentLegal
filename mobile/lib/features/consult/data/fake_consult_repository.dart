import '../../../core/fake/fake_latency.dart';
import '../../cases/data/fake_data.dart';
import '../../cases/domain/case_models.dart';
import '../domain/consult_models.dart';
import '../domain/consult_repository.dart';

/// Canned answers from the prototype (site/prototype/js/data.js · CONSULT_QA). Anything else is refused,
/// mirroring the real engine's rule: no answer without supporting regulation text.
class FakeConsultRepository implements ConsultRepository {
  int _id = 0;

  static const _qa = <({String q, List<String> keys, String a, List<String> refs})>[
    (
      q: 'هل يحق لي الإفصاح عن ملف المريض للدفاع عن نفسي؟',
      keys: ['إفصاح', 'سر', 'ملف المريض', 'سرية'],
      refs: ['S4'],
      a: 'نعم، بالقدر اللازم فقط. الأصل وجوب المحافظة على أسرار المريض، لكن النظام استثنى حالة دفع الممارس لاتهام وجّهه إليه المريض أو ذووه يتعلق بكفايته أو بطريقة ممارسته للمهنة. يُنصح بالاقتصار على المعلومات المرتبطة بالشكوى واستخدام رمز المريض بدل اسمه.',
    ),
    (
      q: 'هل التأمين ضد الأخطاء الطبية إلزامي لطبيب الأسنان؟',
      keys: ['تأمين', 'إلزامي'],
      refs: ['S5', 'S6'],
      a: 'نعم. الاشتراك في التأمين التعاوني ضد الأخطاء المهنية الطبية إلزامي على جميع الأطباء وأطباء الأسنان، وتشمل التغطية التعويضات الناشئة عن الأخطاء الواقعة أثناء فترة التغطية. وعند تلقي شكوى، احرص على إبلاغ شركة التأمين كتابيًا والاحتفاظ بإثبات الإبلاغ.',
    ),
    (
      q: 'ما المهلة المتاحة للتظلم من قرار الهيئة الصحية الشرعية؟',
      keys: ['تظلم', 'مهلة', 'ديوان', 'اعتراض'],
      refs: ['S8'],
      a: 'يجوز التظلم من قرار الهيئة الصحية الشرعية أمام ديوان المظالم خلال ستين يومًا من تاريخ إبلاغك بالقرار.',
    ),
    (
      q: 'ما الذي يُعد خطأً مهنيًا صحيًا؟',
      keys: ['خطأ', 'مهني', 'تعويض'],
      refs: ['S1'],
      a: 'من صور الخطأ المهني الصحي وفق النظام: الخطأ في العلاج أو نقص المتابعة، والجهل بأمور فنية يُفترض الإلمام بها، وعدم استشارة من تستدعي حالة المريض الاستعانة به. ويلتزم من ارتكب الخطأ بالتعويض إذا ترتب عليه ضرر للمريض.',
    ),
    (
      q: 'هل يمكن تأجيل سفري بسبب شكوى؟ وكيف أعترض؟',
      keys: ['سفر', 'منع', 'تأجيل'],
      refs: ['S3', 'S6'],
      a: 'قد يُؤجَّل سفر المدعى عليه لاستكمال إجراءات التحقيق. ولك حق الاعتراض بتقديم: وثيقة تأمين سارية وقت الإجراء محل الشكوى، وإثبات إبلاغ شركة التأمين بالشكوى، وكفالة غرم وأداء.',
    ),
  ];

  static const refusal = 'لا تتوفر لديّ مصادر نظامية كافية للإجابة عن هذا السؤال بدقة. ننصحك بالرجوع إلى محامٍ مرخّص.';

  @override
  Future<List<String>> suggestedQuestions() async {
    await fakeLatency(200);
    return _qa.map((e) => e.q).toList();
  }

  @override
  Future<ConsultMessage> ask(String question, {required List<ConsultMessage> history}) async {
    await fakeLatency(1400);
    final match = _qa.where((e) => e.q == question || e.keys.any(question.contains)).firstOrNull;
    if (match == null) return ConsultMessage(id: 'a${_id++}', fromDentist: false, text: refusal, refused: true);
    return ConsultMessage(id: 'a${_id++}', fromDentist: false, text: match.a, citations: [for (final r in match.refs) cite(r)]);
  }

  @override
  Future<RegulationSource> getSource(String ref) async {
    await fakeLatency(150);
    return sourceByRef(ref);
  }
}
