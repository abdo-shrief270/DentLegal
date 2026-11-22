// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'case_actions.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning
/// Commands on a case. Kept out of widgets so screens only render state and call these.

@ProviderFor(CaseActions)
final caseActionsProvider = CaseActionsProvider._();

/// Commands on a case. Kept out of widgets so screens only render state and call these.
final class CaseActionsProvider extends $NotifierProvider<CaseActions, void> {
  /// Commands on a case. Kept out of widgets so screens only render state and call these.
  CaseActionsProvider._()
    : super(from: null, argument: null, retry: null, name: r'caseActionsProvider', isAutoDispose: true, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$caseActionsHash();

  @$internal
  @override
  CaseActions create() => CaseActions();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(void value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<void>(value));
  }
}

String _$caseActionsHash() => r'0468d0b27e4cfb5a8018da7beb72a838b73913e4';

/// Commands on a case. Kept out of widgets so screens only render state and call these.

abstract class _$CaseActions extends $Notifier<void> {
  void build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<void, void>;
    final element = ref.element as $ClassProviderElement<AnyNotifier<void, void>, void, Object?, Object?>;
    return element.handleCreate(ref, build);
  }
}
