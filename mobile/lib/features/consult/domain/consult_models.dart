import 'package:freezed_annotation/freezed_annotation.dart';

import '../../cases/domain/case_models.dart';

part 'consult_models.freezed.dart';

@freezed
abstract class ConsultMessage with _$ConsultMessage {
  const factory ConsultMessage({
    required String id,
    required bool fromDentist,
    required String text,
    @Default([]) List<Citation> citations,
    @Default(false) bool refused,
    bool? ratedUseful,
  }) = _ConsultMessage;
}
