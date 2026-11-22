import '../../cases/domain/case_models.dart';

enum PickSource { scanner, pdf, photo }

/// Gets a document from the device: native document scanner (VisionKit / ML Kit), PDF picker or camera.
/// The fake picker returns sample documents; the real one is added with the native plugins (plan §6.5).
abstract interface class DocumentPicker {
  Future<CaseDocument?> pick(PickSource source);
}
