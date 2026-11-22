// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wizard_controller.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(documentPicker)
final documentPickerProvider = DocumentPickerProvider._();

final class DocumentPickerProvider extends $FunctionalProvider<DocumentPicker, DocumentPicker, DocumentPicker> with $Provider<DocumentPicker> {
  DocumentPickerProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'documentPickerProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$documentPickerHash();

  @$internal
  @override
  $ProviderElement<DocumentPicker> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  DocumentPicker create(Ref ref) {
    return documentPicker(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(DocumentPicker value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<DocumentPicker>(value));
  }
}

String _$documentPickerHash() => r'c01ba8556dbfc510538c57fe11ca419ef6598539';

@ProviderFor(WizardController)
final wizardControllerProvider = WizardControllerProvider._();

final class WizardControllerProvider extends $NotifierProvider<WizardController, WizardState> {
  WizardControllerProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'wizardControllerProvider',
        isAutoDispose: true,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$wizardControllerHash();

  @$internal
  @override
  WizardController create() => WizardController();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(WizardState value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<WizardState>(value));
  }
}

String _$wizardControllerHash() => r'f7ce8fc2d59c1b148510690ce71c6a9af491c79c';

abstract class _$WizardController extends $Notifier<WizardState> {
  WizardState build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<WizardState, WizardState>;
    final element = ref.element as $ClassProviderElement<AnyNotifier<WizardState, WizardState>, WizardState, Object?, Object?>;
    return element.handleCreate(ref, build);
  }
}
