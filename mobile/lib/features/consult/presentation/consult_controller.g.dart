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

String _$consultRepositoryHash() => r'79e979a37b0592fb5217bce82e94f9eb09db98c5';

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

String _$consultControllerHash() => r'fcfa8a24ffc4cacebc34f5eeece3f2cb5e8644e5';

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
