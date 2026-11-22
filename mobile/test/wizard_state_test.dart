import 'package:dentlegal/features/case_wizard/presentation/wizard_controller.dart';
import 'package:dentlegal/features/cases/domain/case_models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const doc = CaseDocument(id: 'd1', ref: 'D1', name: 'خطاب الشكوى.pdf', kind: DocumentKind.complaintLetter, pages: 2, sizeLabel: '240 ك.ب');

  test('flags national IDs and phone numbers in the facts', () {
    expect(const WizardState(facts: 'راجع المريض بتاريخ 12/3 وطلب التعويض').factsContainIdentifier, isFalse);
    expect(const WizardState(facts: 'هوية المريض 1023456789 حسب الملف').factsContainIdentifier, isTrue);
    expect(const WizardState(facts: 'اتصل من الرقم 0551234567 مساءً').factsContainIdentifier, isTrue);
    expect(const WizardState(facts: 'الرقم المرجعي 1023456789012 للملف').factsContainIdentifier, isFalse);
  });

  test('each step needs its own fields', () {
    expect(const WizardState(complaintNo: '02871/1448').stepValid, isTrue);
    expect(const WizardState().stepValid, isFalse);
    expect(const WizardState(step: 3).stepValid, isFalse);
    expect(const WizardState(step: 3, documents: [doc]).stepValid, isTrue);
    expect(const WizardState(step: 4, confirmAccuracy: true).stepValid, isFalse);
    expect(const WizardState(step: 4, confirmAccuracy: true, confirmProcessing: true).stepValid, isTrue);
  });
}
