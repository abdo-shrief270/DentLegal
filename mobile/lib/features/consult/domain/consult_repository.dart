import '../../cases/domain/case_models.dart';
import 'consult_models.dart';

/// AI legal consultant (PROJECT1_PLAN.md §5.5–5.7). Answers cite regulation articles or refuse.
abstract interface class ConsultRepository {
  Future<List<String>> suggestedQuestions();
  Future<ConsultMessage> ask(String question, {required List<ConsultMessage> history});
  Future<RegulationSource> getSource(String ref);
}
