import 'dart:convert';
import 'dart:io';
import 'dart:math';

import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';

import '../../cases/data/api_mappers.dart';
import '../../cases/domain/case_models.dart';
import '../domain/document_picker.dart';

/// Real files from the device: a PDF from Files, or a photo taken with the camera (the scanner source uses the camera
/// too until the native document scanner is added, plan §6.5). Size and type limits match the API (20 MB; PDF, JPEG, PNG).
class DeviceDocumentPicker implements DocumentPicker {
  static const maxBytes = 20 * 1024 * 1024;

  @override
  Future<CaseDocument?> pick(PickSource source) async {
    final file = switch (source) {
      PickSource.pdf => await _pdf(),
      PickSource.photo || PickSource.scanner => await _photo(),
    };
    if (file == null) return null;
    final bytes = await file.length();
    if (bytes > maxBytes) throw const DocumentTooLarge();
    final isPdf = file.path.toLowerCase().endsWith('.pdf');
    return CaseDocument(
      id: 'local-${DateTime.now().microsecondsSinceEpoch}-${Random().nextInt(1 << 20)}',
      ref: '',
      name: file.uri.pathSegments.last,
      kind: DocumentKind.other,
      pages: isPdf ? await _pdfPages(file) : 1,
      sizeLabel: ApiMappers.sizeLabel(bytes),
      isImage: !isPdf,
      localPath: file.path,
      mime: isPdf ? 'application/pdf' : 'image/jpeg',
    );
  }

  Future<File?> _pdf() async {
    final files = await FilePicker.pickFiles(type: FileType.custom, allowedExtensions: ['pdf']);
    final path = files.isEmpty ? null : files.first.path;
    return path == null ? null : File(path);
  }

  Future<File?> _photo() async {
    final shot = await ImagePicker().pickImage(source: ImageSource.camera, imageQuality: 85, maxWidth: 2400);
    return shot == null ? null : File(shot.path);
  }

  /// A rough page count for the list («3 ص»); the API counts pages exactly when it reads the file.
  Future<int> _pdfPages(File file) async {
    final text = latin1.decode(await file.readAsBytes(), allowInvalid: true);
    final count = RegExp(r'/Type\s*/Page[^s]').allMatches(text).length;
    return max(count, 1);
  }
}

class DocumentTooLarge implements Exception {
  const DocumentTooLarge();

  @override
  String toString() => 'حجم الملف أكبر من 20 م.ب.';
}
