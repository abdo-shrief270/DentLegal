import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../cases/domain/case_models.dart';
import '../../cases/presentation/cases_providers.dart';
import '../data/fake_consult_repository.dart';
import '../domain/consult_models.dart';
import '../domain/consult_repository.dart';

part 'consult_controller.g.dart';

@Riverpod(keepAlive: true)
ConsultRepository consultRepository(Ref ref) => FakeConsultRepository();

@riverpod
Future<List<String>> suggestedQuestions(Ref ref) => ref.watch(consultRepositoryProvider).suggestedQuestions();

class ConsultState {
  const ConsultState({this.messages = const [], this.waiting = false, this.error});
  final List<ConsultMessage> messages;
  final bool waiting;
  final String? error;

  ConsultState copyWith({List<ConsultMessage>? messages, bool? waiting, String? error}) =>
      ConsultState(messages: messages ?? this.messages, waiting: waiting ?? this.waiting, error: error);
}

@Riverpod(keepAlive: true)
class ConsultController extends _$ConsultController {
  int _id = 0;

  @override
  ConsultState build() => const ConsultState();

  Future<void> ask(String question) async {
    final text = question.trim();
    if (text.isEmpty || state.waiting) return;
    final mine = ConsultMessage(id: 'q${_id++}', fromDentist: true, text: text);
    state = state.copyWith(messages: [...state.messages, mine], waiting: true);
    try {
      final answer = await ref.read(consultRepositoryProvider).ask(text, history: state.messages);
      state = state.copyWith(messages: [...state.messages, answer], waiting: false);
    } on Object {
      state = state.copyWith(waiting: false, error: 'تعذّر الحصول على إجابة. حاول مرة أخرى.');
    }
  }

  Future<void> rate(String messageId, {required bool useful, String? comment}) async {
    state = state.copyWith(messages: [for (final m in state.messages) m.id == messageId ? m.copyWith(ratedUseful: useful) : m]);
    await ref.read(casesRepositoryProvider).rate(target: RatingTarget.consultAnswer, targetId: messageId, useful: useful, comment: comment);
  }
}
