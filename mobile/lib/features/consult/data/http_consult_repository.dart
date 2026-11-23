import 'dart:async';

import '../../../core/api/api_client.dart';
import '../../../core/api/api_error.dart';
import '../../cases/data/api_mappers.dart';
import '../../cases/data/http_cases_repository.dart';
import '../../cases/domain/case_models.dart';
import '../domain/consult_models.dart';
import '../domain/consult_repository.dart';

/// «المستشار القانوني الذكي» against the Laravel API (S2.6). Asking returns at once with a pending answer that is
/// polled until it is answered, refused or failed (usually 10–25 s). The server keeps the thread and its history.
class HttpConsultRepository implements ConsultRepository {
  HttpConsultRepository(this._api, {this.pollEvery = const Duration(seconds: 2), this.giveUpAfter = const Duration(seconds: 120)});

  final ApiClient _api;
  final Duration pollEvery;
  final Duration giveUpAfter;
  String? _consultationId;

  @override
  Future<List<String>> suggestedQuestions() async => (await _api.get<List<dynamic>>('/consultations/suggested-questions')).cast<String>();

  /// A [history] without an earlier answer starts a new thread (the chat passes the question just typed);
  /// otherwise the question follows up in the current one.
  @override
  Future<ConsultMessage> ask(String question, {required List<ConsultMessage> history}) async {
    if (!history.any((m) => !m.fromDentist)) _consultationId = null;
    final pending = _consultationId == null
        ? await _api.post<Json>('/consultations', body: {'question': question})
        : await _api.post<Json>('/consultations/$_consultationId/messages', body: {'question': question});
    _consultationId = pending['consultation_id'] as String?;
    return _await(pending['id'] as String);
  }

  @override
  Future<RegulationSource> getSource(Citation citation) => HttpCasesRepository.fetchRegulationSource(_api, citation);

  Future<ConsultMessage> _await(String messageId) async {
    final deadline = DateTime.now().add(giveUpAfter);
    while (true) {
      final Json m;
      try {
        m = await _api.get<Json>('/consultation-messages/$messageId');
      } on ApiError catch (e) {
        if ((e.code == 'network_error' || e.code == 'timeout') && DateTime.now().isBefore(deadline)) {
          await Future<void>.delayed(pollEvery);
          continue;
        }
        rethrow;
      }
      switch (m['status']) {
        case 'answered' || 'refused':
          return message(m);
        case 'failed':
          throw ApiError(m['error'] as String? ?? 'ai_unavailable', 'تعذّر الحصول على إجابة الآن. حاول مرة أخرى بعد قليل.');
      }
      if (DateTime.now().isAfter(deadline)) throw ApiError.timeout;
      await Future<void>.delayed(pollEvery);
    }
  }

  /// An answer row → one chat bubble: paragraphs joined, each verified source cited once.
  static ConsultMessage message(Json m) {
    final answer = (m['answer'] as Json?) ?? const {};
    final paragraphs = (answer['paragraphs'] as List?)?.cast<Json>() ?? const <Json>[];
    final seen = <String>{};
    final citations = [
      for (final p in paragraphs)
        for (final c in ApiMappers.citations(p['citations']))
          if (c.verified && !c.isDocument && seen.add(c.ref)) c,
    ];
    return ConsultMessage(
      id: m['id'] as String,
      fromDentist: false,
      text: paragraphs.map((p) => p['text'] as String? ?? '').where((t) => t.isNotEmpty).join('\n\n'),
      citations: citations,
      refused: m['status'] == 'refused',
    );
  }
}
