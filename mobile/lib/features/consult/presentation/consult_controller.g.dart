// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consult_controller.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(consultRepository)
final consultRepositoryProvider = ConsultRepositoryProvider._();

final class ConsultRepositoryProvider extends $FunctionalProvider<ConsultRepository, ConsultRepository, ConsultRepository> with $Provider<ConsultRepository> {
  ConsultRepositoryProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'consultRepositoryProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$consultRepositoryHash();

  @$internal
  @override
  $ProviderElement<ConsultRepository> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  ConsultRepository create(Ref ref) {
    return consultRepository(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(ConsultRepository value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<ConsultRepository>(value));
  }
}

String _$consultRepositoryHash() => r'e07badda675f499d46eb5a4dc5202d3262076867';

@ProviderFor(suggestedQuestions)
final suggestedQuestionsProvider = SuggestedQuestionsProvider._();

final class SuggestedQuestionsProvider extends $FunctionalProvider<AsyncValue<List<String>>, List<String>, FutureOr<List<String>>>
    with $FutureModifier<List<String>>, $FutureProvider<List<String>> {
  SuggestedQuestionsProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'suggestedQuestionsProvider',
        isAutoDispose: true,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$suggestedQuestionsHash();

  @$internal
  @override
  $FutureProviderElement<List<String>> $createElement($ProviderPointer pointer) => $FutureProviderElement(pointer);

  @override
  FutureOr<List<String>> create(Ref ref) {
    return suggestedQuestions(ref);
  }
}

String _$suggestedQuestionsHash() => r'60f77c11a92cca1fe007e8c9950bb4aeb5886e1d';

@ProviderFor(ConsultController)
final consultControllerProvider = ConsultControllerProvider._();

final class ConsultControllerProvider extends $NotifierProvider<ConsultController, ConsultState> {
  ConsultControllerProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'consultControllerProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$consultControllerHash();

  @$internal
  @override
  ConsultController create() => ConsultController();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(ConsultState value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<ConsultState>(value));
  }
}

String _$consultControllerHash() => r'529cdd93a910ce0d382af9e6b967324dd967ce5f';

abstract class _$ConsultController extends $Notifier<ConsultState> {
  ConsultState build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<ConsultState, ConsultState>;
    final element = ref.element as $ClassProviderElement<AnyNotifier<ConsultState, ConsultState>, ConsultState, Object?, Object?>;
    return element.handleCreate(ref, build);
  }
}
