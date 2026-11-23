import '../../../core/api/api_client.dart';
import '../domain/case_models.dart';

/// JSON from the Laravel API → the app's domain models (shapes in backend/app/Modules/*/Http).
abstract final class ApiMappers {
  static T _enum<T extends Enum>(List<T> values, String? wire, T fallback) {
    if (wire == null) return fallback;
    final camel = wire.replaceAllMapped(RegExp(r'_([a-z])'), (m) => m[1]!.toUpperCase());
    return values.where((v) => v.name == camel).firstOrNull ?? fallback;
  }

  static String wire(Enum value) => value.name.replaceAllMapped(RegExp('[A-Z]'), (m) => '_${m[0]!.toLowerCase()}');

  static DateTime _date(Object? v) => v is String ? DateTime.parse(v).toLocal() : DateTime.fromMillisecondsSinceEpoch(0);

  static DateTime? _dateOrNull(Object? v) => v is String ? DateTime.parse(v).toLocal() : null;

  static String sizeLabel(int bytes) => bytes >= 1024 * 1024 ? '${(bytes / (1024 * 1024)).toStringAsFixed(1)} م.ب' : '${(bytes / 1024).ceil()} ك.ب';

  static CaseDocument document(Json d, int index) => CaseDocument(
    id: d['id'] as String,
    ref: 'D${index + 1}',
    name: d['name'] as String? ?? '',
    kind: _enum(DocumentKind.values, d['kind'] as String?, DocumentKind.other),
    pages: (d['page_count'] as num?)?.toInt() ?? 0,
    sizeLabel: sizeLabel((d['size_bytes'] as num?)?.toInt() ?? 0),
    isImage: (d['mime'] as String? ?? '').startsWith('image/'),
    lowQuality: d['low_quality'] as bool? ?? false,
    mime: d['mime'] as String?,
  );

  static DentalCase dentalCase(Json c) {
    final docs = (c['documents'] as List?)?.cast<Json>() ?? const [];
    return DentalCase(
      id: c['id'] as String,
      code: c['code'] as String,
      title: c['title'] as String? ?? '',
      caseType: _enum(CaseType.values, c['case_type'] as String?, CaseType.complaint),
      claimType: _enum(ClaimType.values, c['claim_type'] as String?, ClaimType.unknown),
      authority: c['authority'] as String? ?? '',
      complaintNo: c['complaint_no'] as String? ?? '',
      receivedOn: _date(c['received_on']),
      procedure: c['procedure'] as String? ?? '',
      procedureDate: _date(c['procedure_date'] ?? c['received_on']),
      patientCode: c['patient_code'] as String? ?? '',
      facts: c['facts'] as String? ?? '',
      status: _enum(CaseStatus.values, c['status'] as String?, CaseStatus.draft),
      createdAt: _date(c['created_at']),
      updatedAt: _date(c['updated_at']),
      documents: [for (var i = 0; i < docs.length; i++) document(docs[i], i)],
      risk: c['risk'] == null ? null : _enum(RiskLevel.values, c['risk'] as String?, RiskLevel.medium),
      positionStrength: (c['position_strength'] as num?)?.toInt(),
      lawyerName: c['lawyer_name'] as String?,
      submittedAt: _dateOrNull(c['submitted_for_review_at']),
      approvedAt: _dateOrNull(c['approved_at']),
    );
  }

  /// A citation as the verifier returns it: `{ref, kind, label, quote, verified, chunk_id?}`.
  static Citation citation(Json c) => Citation(
    ref: c['ref'] as String? ?? '',
    label: (c['label'] as String?) ?? (c['ref'] as String? ?? ''),
    verified: c['verified'] as bool? ?? false,
    chunkId: (c['chunk_id'] as num?)?.toInt(),
  );

  static List<Citation> citations(Object? list) => [for (final c in (list as List?)?.cast<Json>() ?? const <Json>[]) citation(c)];

  static RegulationSource source(Json s) => RegulationSource(
    ref: s['ref'] as String? ?? '',
    law: s['law'] as String? ?? '',
    label: s['label'] as String? ?? '',
    title: s['title'] as String? ?? '',
    text: s['text'] as String? ?? '',
    score: (s['score'] as num?)?.toDouble() ?? 0,
  );

  static CaseAnalysis analysis(String caseId, Json a) {
    List<AnalysisPoint> points(String key) => [
      for (final p in (a[key] as List?)?.cast<Json>() ?? const <Json>[])
        AnalysisPoint(
          text: p['text'] as String? ?? '',
          citations: citations(p['citations']),
          severity: p['severity'] == null ? null : _enum(RiskLevel.values, p['severity'] as String?, RiskLevel.medium),
          urgency: p['urgency'] == null ? null : _enum(Urgency.values, p['urgency'] as String?, Urgency.normal),
        ),
    ];
    return CaseAnalysis(
      caseId: caseId,
      id: a['id'] as String? ?? '',
      positionStrength: (a['position_strength'] as num?)?.toInt() ?? 0,
      risk: _enum(RiskLevel.values, a['risk_level'] as String?, RiskLevel.medium),
      summary: a['summary'] as String? ?? '',
      strengths: points('strengths'),
      weaknesses: points('weaknesses'),
      actions: points('recommended_actions'),
      sources: [for (final s in (a['sources'] as List?)?.cast<Json>() ?? const <Json>[]) source(s)],
      sourcesSufficient: a['sources_sufficient'] as bool? ?? true,
    );
  }

  static DraftParagraph paragraph(Json p) =>
      DraftParagraph(text: p['text'] as String? ?? '', legalClaim: p['legal_claim'] as bool? ?? false, citations: citations(p['citations']));

  static CaseDraft draft(String caseId, Json d) {
    final v = (d['version'] as Json?) ?? const {};
    final approval = d['approval'] as Json?;
    return CaseDraft(
      caseId: caseId,
      versionId: v['id'] as String? ?? '',
      versionNo: (v['no'] as num?)?.toInt() ?? 1,
      byLawyer: v['author'] == 'lawyer',
      title: v['title'] as String? ?? '',
      addressee: v['addressee'] as String? ?? '',
      salutation: v['salutation'] as String? ?? '',
      intro: paragraph((v['intro'] as Json?) ?? const {}),
      sections: [
        for (final s in (v['sections'] as List?)?.cast<Json>() ?? const <Json>[])
          DraftSection(heading: s['heading'] as String? ?? '', paragraphs: [for (final p in (s['paragraphs'] as List).cast<Json>()) paragraph(p)]),
      ],
      closing: v['closing'] as String? ?? '',
      attachments: ((v['attachments'] as List?) ?? const []).cast<String>(),
      approvedBy: approval?['approved_by'] as String?,
      approvedAt: _dateOrNull(approval?['approved_at']),
    );
  }

  /// `GET /cases/{id}/analysis/status` → progress. Failed steps count as not done; the error is the API's code.
  static PipelineProgress progress(Json s) {
    final steps = {for (final step in (s['steps'] as List?)?.cast<Json>() ?? const <Json>[]) step['key'] as String: step['status'] as String?};
    PipelineStepState state(PipelineStep step) => switch (steps[step.name]) {
      'done' => PipelineStepState.done,
      'running' => PipelineStepState.running,
      _ => PipelineStepState.pending,
    };
    return PipelineProgress(
      steps: {for (final step in PipelineStep.values) step: state(step)},
      finished: s['status'] == 'succeeded',
      error: s['status'] == 'failed' ? (s['error'] as String? ?? 'internal_error') : null,
    );
  }
}
