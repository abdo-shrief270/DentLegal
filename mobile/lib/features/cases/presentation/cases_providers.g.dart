// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cases_providers.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(casesRepository)
final casesRepositoryProvider = CasesRepositoryProvider._();

final class CasesRepositoryProvider extends $FunctionalProvider<CasesRepository, CasesRepository, CasesRepository> with $Provider<CasesRepository> {
  CasesRepositoryProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'casesRepositoryProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$casesRepositoryHash();

  @$internal
  @override
  $ProviderElement<CasesRepository> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  CasesRepository create(Ref ref) {
    return casesRepository(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(CasesRepository value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<CasesRepository>(value));
  }
}

String _$casesRepositoryHash() => r'4f1e58f090a9d0cc5f444d657b8cd6e90e1619f1';

@ProviderFor(casesList)
final casesListProvider = CasesListProvider._();

final class CasesListProvider extends $FunctionalProvider<AsyncValue<List<DentalCase>>, List<DentalCase>, FutureOr<List<DentalCase>>>
    with $FutureModifier<List<DentalCase>>, $FutureProvider<List<DentalCase>> {
  CasesListProvider._()
    : super(from: null, argument: null, retry: null, name: r'casesListProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$casesListHash();

  @$internal
  @override
  $FutureProviderElement<List<DentalCase>> $createElement($ProviderPointer pointer) => $FutureProviderElement(pointer);

  @override
  FutureOr<List<DentalCase>> create(Ref ref) {
    return casesList(ref);
  }
}

String _$casesListHash() => r'a6eec25a77be783142236107b83c8a4e3d1d4434';

@ProviderFor(caseDetail)
final caseDetailProvider = CaseDetailFamily._();

final class CaseDetailProvider extends $FunctionalProvider<AsyncValue<DentalCase>, DentalCase, FutureOr<DentalCase>>
    with $FutureModifier<DentalCase>, $FutureProvider<DentalCase> {
  CaseDetailProvider._({required CaseDetailFamily super.from, required String super.argument})
    : super(retry: null, name: r'caseDetailProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$caseDetailHash();

  @override
  String toString() {
    return r'caseDetailProvider'
        ''
        '($argument)';
  }

  @$internal
  @override
  $FutureProviderElement<DentalCase> $createElement($ProviderPointer pointer) => $FutureProviderElement(pointer);

  @override
  FutureOr<DentalCase> create(Ref ref) {
    final argument = this.argument as String;
    return caseDetail(ref, argument);
  }

  @override
  bool operator ==(Object other) {
    return other is CaseDetailProvider && other.argument == argument;
  }

  @override
  int get hashCode {
    return argument.hashCode;
  }
}

String _$caseDetailHash() => r'a39d9521190a242f62cc7e9dca88b1333bdf93d8';

final class CaseDetailFamily extends $Family with $FunctionalFamilyOverride<FutureOr<DentalCase>, String> {
  CaseDetailFamily._() : super(retry: null, name: r'caseDetailProvider', dependencies: null, $allTransitiveDependencies: null, isAutoDispose: true);

  CaseDetailProvider call(String id) => CaseDetailProvider._(argument: id, from: this);

  @override
  String toString() => r'caseDetailProvider';
}

@ProviderFor(caseAnalysis)
final caseAnalysisProvider = CaseAnalysisFamily._();

final class CaseAnalysisProvider extends $FunctionalProvider<AsyncValue<CaseAnalysis>, CaseAnalysis, FutureOr<CaseAnalysis>>
    with $FutureModifier<CaseAnalysis>, $FutureProvider<CaseAnalysis> {
  CaseAnalysisProvider._({required CaseAnalysisFamily super.from, required String super.argument})
    : super(retry: null, name: r'caseAnalysisProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$caseAnalysisHash();

  @override
  String toString() {
    return r'caseAnalysisProvider'
        ''
        '($argument)';
  }

  @$internal
  @override
  $FutureProviderElement<CaseAnalysis> $createElement($ProviderPointer pointer) => $FutureProviderElement(pointer);

  @override
  FutureOr<CaseAnalysis> create(Ref ref) {
    final argument = this.argument as String;
    return caseAnalysis(ref, argument);
  }

  @override
  bool operator ==(Object other) {
    return other is CaseAnalysisProvider && other.argument == argument;
  }

  @override
  int get hashCode {
    return argument.hashCode;
  }
}

String _$caseAnalysisHash() => r'6935981b78e353de2da19246a1d59292851b3c3c';

final class CaseAnalysisFamily extends $Family with $FunctionalFamilyOverride<FutureOr<CaseAnalysis>, String> {
  CaseAnalysisFamily._() : super(retry: null, name: r'caseAnalysisProvider', dependencies: null, $allTransitiveDependencies: null, isAutoDispose: true);

  CaseAnalysisProvider call(String id) => CaseAnalysisProvider._(argument: id, from: this);

  @override
  String toString() => r'caseAnalysisProvider';
}

@ProviderFor(caseDraft)
final caseDraftProvider = CaseDraftFamily._();

final class CaseDraftProvider extends $FunctionalProvider<AsyncValue<CaseDraft>, CaseDraft, FutureOr<CaseDraft>>
    with $FutureModifier<CaseDraft>, $FutureProvider<CaseDraft> {
  CaseDraftProvider._({required CaseDraftFamily super.from, required String super.argument})
    : super(retry: null, name: r'caseDraftProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$caseDraftHash();

  @override
  String toString() {
    return r'caseDraftProvider'
        ''
        '($argument)';
  }

  @$internal
  @override
  $FutureProviderElement<CaseDraft> $createElement($ProviderPointer pointer) => $FutureProviderElement(pointer);

  @override
  FutureOr<CaseDraft> create(Ref ref) {
    final argument = this.argument as String;
    return caseDraft(ref, argument);
  }

  @override
  bool operator ==(Object other) {
    return other is CaseDraftProvider && other.argument == argument;
  }

  @override
  int get hashCode {
    return argument.hashCode;
  }
}

String _$caseDraftHash() => r'ce5fba0722025b56d74aba013fe7877eb0de5b02';

final class CaseDraftFamily extends $Family with $FunctionalFamilyOverride<FutureOr<CaseDraft>, String> {
  CaseDraftFamily._() : super(retry: null, name: r'caseDraftProvider', dependencies: null, $allTransitiveDependencies: null, isAutoDispose: true);

  CaseDraftProvider call(String id) => CaseDraftProvider._(argument: id, from: this);

  @override
  String toString() => r'caseDraftProvider';
}

@ProviderFor(casePipeline)
final casePipelineProvider = CasePipelineFamily._();

final class CasePipelineProvider extends $FunctionalProvider<AsyncValue<PipelineProgress>, PipelineProgress, Stream<PipelineProgress>>
    with $FutureModifier<PipelineProgress>, $StreamProvider<PipelineProgress> {
  CasePipelineProvider._({required CasePipelineFamily super.from, required String super.argument})
    : super(retry: null, name: r'casePipelineProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$casePipelineHash();

  @override
  String toString() {
    return r'casePipelineProvider'
        ''
        '($argument)';
  }

  @$internal
  @override
  $StreamProviderElement<PipelineProgress> $createElement($ProviderPointer pointer) => $StreamProviderElement(pointer);

  @override
  Stream<PipelineProgress> create(Ref ref) {
    final argument = this.argument as String;
    return casePipeline(ref, argument);
  }

  @override
  bool operator ==(Object other) {
    return other is CasePipelineProvider && other.argument == argument;
  }

  @override
  int get hashCode {
    return argument.hashCode;
  }
}

String _$casePipelineHash() => r'c1cb63fd883aa7cd5c0888a8f3b556df01e9fb0b';

final class CasePipelineFamily extends $Family with $FunctionalFamilyOverride<Stream<PipelineProgress>, String> {
  CasePipelineFamily._() : super(retry: null, name: r'casePipelineProvider', dependencies: null, $allTransitiveDependencies: null, isAutoDispose: true);

  CasePipelineProvider call(String id) => CasePipelineProvider._(argument: id, from: this);

  @override
  String toString() => r'casePipelineProvider';
}

@ProviderFor(regulationSource)
final regulationSourceProvider = RegulationSourceFamily._();

final class RegulationSourceProvider extends $FunctionalProvider<AsyncValue<RegulationSource>, RegulationSource, FutureOr<RegulationSource>>
    with $FutureModifier<RegulationSource>, $FutureProvider<RegulationSource> {
  RegulationSourceProvider._({required RegulationSourceFamily super.from, required Citation super.argument})
    : super(retry: null, name: r'regulationSourceProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$regulationSourceHash();

  @override
  String toString() {
    return r'regulationSourceProvider'
        ''
        '($argument)';
  }

  @$internal
  @override
  $FutureProviderElement<RegulationSource> $createElement($ProviderPointer pointer) => $FutureProviderElement(pointer);

  @override
  FutureOr<RegulationSource> create(Ref ref) {
    final argument = this.argument as Citation;
    return regulationSource(ref, argument);
  }

  @override
  bool operator ==(Object other) {
    return other is RegulationSourceProvider && other.argument == argument;
  }

  @override
  int get hashCode {
    return argument.hashCode;
  }
}

String _$regulationSourceHash() => r'68a74e5d4b46a64b39401ea3c5db26e86b87a023';

final class RegulationSourceFamily extends $Family with $FunctionalFamilyOverride<FutureOr<RegulationSource>, Citation> {
  RegulationSourceFamily._() : super(retry: null, name: r'regulationSourceProvider', dependencies: null, $allTransitiveDependencies: null, isAutoDispose: true);

  RegulationSourceProvider call(Citation citation) => RegulationSourceProvider._(argument: citation, from: this);

  @override
  String toString() => r'regulationSourceProvider';
}
