import '../../../core/fake/fake_latency.dart';
import '../../cases/data/fake_data.dart';
import '../../cases/domain/case_models.dart';
import '../domain/document_picker.dart';

/// Hands out the prototype's sample documents in order, as if the dentist scanned or picked them.
class FakeDocumentPicker implements DocumentPicker {
  int _next = 0;

  @override
  Future<CaseDocument?> pick(PickSource source) async {
    await fakeLatency(900);
    final sample = fakeDocuments[_next % fakeDocuments.length];
    _next++;
    return sample.copyWith(id: 'new-$_next', ref: 'D$_next', isImage: source != PickSource.pdf || sample.isImage);
  }
}
