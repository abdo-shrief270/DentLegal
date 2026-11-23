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

String _$feedbackRepositoryHash() => r'1486de85773f1de8e7ff8d11fe4accaf60ea76fa';
