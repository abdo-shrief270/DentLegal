// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'feedback_providers.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(feedbackRepository)
final feedbackRepositoryProvider = FeedbackRepositoryProvider._();

final class FeedbackRepositoryProvider extends $FunctionalProvider<FeedbackRepository, FeedbackRepository, FeedbackRepository>
    with $Provider<FeedbackRepository> {
  FeedbackRepositoryProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'feedbackRepositoryProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$feedbackRepositoryHash();

  @$internal
  @override
  $ProviderElement<FeedbackRepository> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  FeedbackRepository create(Ref ref) {
    return feedbackRepository(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(FeedbackRepository value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<FeedbackRepository>(value));
  }
}

String _$feedbackRepositoryHash() => r'be3d20f64557de6e69a3a592323d8ed74bbf831e';
