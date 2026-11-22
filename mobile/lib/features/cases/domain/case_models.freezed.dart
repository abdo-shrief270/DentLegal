// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint, type=warning, deprecated_member_use, deprecated_member_use_from_same_package
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'case_models.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$CaseDocument {

 String get id; String get ref; String get name; DocumentKind get kind; int get pages; String get sizeLabel; bool get isImage; bool get lowQuality;
/// Create a copy of CaseDocument
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CaseDocumentCopyWith<CaseDocument> get copyWith => _$CaseDocumentCopyWithImpl<CaseDocument>(this as CaseDocument, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as CaseDocument;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CaseDocument&&(identical(other.id, _this.id) || other.id == _this.id)&&(identical(other.ref, _this.ref) || other.ref == _this.ref)&&(identical(other.name, _this.name) || other.name == _this.name)&&(identical(other.kind, _this.kind) || other.kind == _this.kind)&&(identical(other.pages, _this.pages) || other.pages == _this.pages)&&(identical(other.sizeLabel, _this.sizeLabel) || other.sizeLabel == _this.sizeLabel)&&(identical(other.isImage, _this.isImage) || other.isImage == _this.isImage)&&(identical(other.lowQuality, _this.lowQuality) || other.lowQuality == _this.lowQuality));
}


@override
int get hashCode {
  final _this = this as CaseDocument;
  return Object.hash(runtimeType,_this.id,_this.ref,_this.name,_this.kind,_this.pages,_this.sizeLabel,_this.isImage,_this.lowQuality);
}

@override
String toString() {
  final _this = this as CaseDocument;
  return 'CaseDocument(id: ${_this.id}, ref: ${_this.ref}, name: ${_this.name}, kind: ${_this.kind}, pages: ${_this.pages}, sizeLabel: ${_this.sizeLabel}, isImage: ${_this.isImage}, lowQuality: ${_this.lowQuality})';
}


}

/// @nodoc
abstract mixin class $CaseDocumentCopyWith<$Res>  {
  factory $CaseDocumentCopyWith(CaseDocument value, $Res Function(CaseDocument) _then) = _$CaseDocumentCopyWithImpl;
@useResult
$Res call({
 String id, String ref, String name, DocumentKind kind, int pages, String sizeLabel, bool isImage, bool lowQuality
});




}
/// @nodoc
class _$CaseDocumentCopyWithImpl<$Res>
    implements $CaseDocumentCopyWith<$Res> {
  _$CaseDocumentCopyWithImpl(this._self, this._then);

  final CaseDocument _self;
  final $Res Function(CaseDocument) _then;

/// Create a copy of CaseDocument
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? ref = null,Object? name = null,Object? kind = null,Object? pages = null,Object? sizeLabel = null,Object? isImage = null,Object? lowQuality = null,}) {
  return _then(CaseDocument(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,ref: null == ref ? _self.ref : ref // ignore: cast_nullable_to_non_nullable
as String,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,kind: null == kind ? _self.kind : kind // ignore: cast_nullable_to_non_nullable
as DocumentKind,pages: null == pages ? _self.pages : pages // ignore: cast_nullable_to_non_nullable
as int,sizeLabel: null == sizeLabel ? _self.sizeLabel : sizeLabel // ignore: cast_nullable_to_non_nullable
as String,isImage: null == isImage ? _self.isImage : isImage // ignore: cast_nullable_to_non_nullable
as bool,lowQuality: null == lowQuality ? _self.lowQuality : lowQuality // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [CaseDocument].
extension CaseDocumentPatterns on CaseDocument {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _CaseDocument value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _CaseDocument() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _CaseDocument value)  $default,){
final _that = this;
switch (_that) {
case _CaseDocument():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _CaseDocument value)?  $default,){
final _that = this;
switch (_that) {
case _CaseDocument() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String id,  String ref,  String name,  DocumentKind kind,  int pages,  String sizeLabel,  bool isImage,  bool lowQuality)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CaseDocument() when $default != null:
return $default(_that.id,_that.ref,_that.name,_that.kind,_that.pages,_that.sizeLabel,_that.isImage,_that.lowQuality);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String id,  String ref,  String name,  DocumentKind kind,  int pages,  String sizeLabel,  bool isImage,  bool lowQuality)  $default,) {final _that = this;
switch (_that) {
case _CaseDocument():
return $default(_that.id,_that.ref,_that.name,_that.kind,_that.pages,_that.sizeLabel,_that.isImage,_that.lowQuality);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String id,  String ref,  String name,  DocumentKind kind,  int pages,  String sizeLabel,  bool isImage,  bool lowQuality)?  $default,) {final _that = this;
switch (_that) {
case _CaseDocument() when $default != null:
return $default(_that.id,_that.ref,_that.name,_that.kind,_that.pages,_that.sizeLabel,_that.isImage,_that.lowQuality);case _:
  return null;

}
}

}

/// @nodoc


class _CaseDocument implements CaseDocument {
  const _CaseDocument({required this.id, required this.ref, required this.name, required this.kind, required this.pages, required this.sizeLabel, this.isImage = false, this.lowQuality = false});
  

@override final  String id;
@override final  String ref;
@override final  String name;
@override final  DocumentKind kind;
@override final  int pages;
@override final  String sizeLabel;
@override@JsonKey() final  bool isImage;
@override@JsonKey() final  bool lowQuality;

/// Create a copy of CaseDocument
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CaseDocumentCopyWith<_CaseDocument> get copyWith => __$CaseDocumentCopyWithImpl<_CaseDocument>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _CaseDocument&&(identical(other.id, id) || other.id == id)&&(identical(other.ref, ref) || other.ref == ref)&&(identical(other.name, name) || other.name == name)&&(identical(other.kind, kind) || other.kind == kind)&&(identical(other.pages, pages) || other.pages == pages)&&(identical(other.sizeLabel, sizeLabel) || other.sizeLabel == sizeLabel)&&(identical(other.isImage, isImage) || other.isImage == isImage)&&(identical(other.lowQuality, lowQuality) || other.lowQuality == lowQuality));
}


@override
int get hashCode {
    return Object.hash(runtimeType,id,ref,name,kind,pages,sizeLabel,isImage,lowQuality);
}

@override
String toString() {
    return 'CaseDocument(id: $id, ref: $ref, name: $name, kind: $kind, pages: $pages, sizeLabel: $sizeLabel, isImage: $isImage, lowQuality: $lowQuality)';
}


}

/// @nodoc
abstract mixin class _$CaseDocumentCopyWith<$Res> implements $CaseDocumentCopyWith<$Res> {
  factory _$CaseDocumentCopyWith(_CaseDocument value, $Res Function(_CaseDocument) _then) = __$CaseDocumentCopyWithImpl;
@override @useResult
$Res call({
 String id, String ref, String name, DocumentKind kind, int pages, String sizeLabel, bool isImage, bool lowQuality
});




}
/// @nodoc
class __$CaseDocumentCopyWithImpl<$Res>
    implements _$CaseDocumentCopyWith<$Res> {
  __$CaseDocumentCopyWithImpl(this._self, this._then);

  final _CaseDocument _self;
  final $Res Function(_CaseDocument) _then;

/// Create a copy of CaseDocument
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? ref = null,Object? name = null,Object? kind = null,Object? pages = null,Object? sizeLabel = null,Object? isImage = null,Object? lowQuality = null,}) {
  return _then(_CaseDocument(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,ref: null == ref ? _self.ref : ref // ignore: cast_nullable_to_non_nullable
as String,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,kind: null == kind ? _self.kind : kind // ignore: cast_nullable_to_non_nullable
as DocumentKind,pages: null == pages ? _self.pages : pages // ignore: cast_nullable_to_non_nullable
as int,sizeLabel: null == sizeLabel ? _self.sizeLabel : sizeLabel // ignore: cast_nullable_to_non_nullable
as String,isImage: null == isImage ? _self.isImage : isImage // ignore: cast_nullable_to_non_nullable
as bool,lowQuality: null == lowQuality ? _self.lowQuality : lowQuality // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}

/// @nodoc
mixin _$DentalCase {

 String get id; String get code; String get title; CaseType get caseType; ClaimType get claimType; String get authority; String get complaintNo; DateTime get receivedOn; String get procedure; DateTime get procedureDate; String get patientCode; String get facts; CaseStatus get status; DateTime get createdAt; DateTime get updatedAt; List<CaseDocument> get documents; RiskLevel? get risk; int? get positionStrength; String? get lawyerName; DateTime? get submittedAt; DateTime? get approvedAt;
/// Create a copy of DentalCase
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$DentalCaseCopyWith<DentalCase> get copyWith => _$DentalCaseCopyWithImpl<DentalCase>(this as DentalCase, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as DentalCase;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is DentalCase&&(identical(other.id, _this.id) || other.id == _this.id)&&(identical(other.code, _this.code) || other.code == _this.code)&&(identical(other.title, _this.title) || other.title == _this.title)&&(identical(other.caseType, _this.caseType) || other.caseType == _this.caseType)&&(identical(other.claimType, _this.claimType) || other.claimType == _this.claimType)&&(identical(other.authority, _this.authority) || other.authority == _this.authority)&&(identical(other.complaintNo, _this.complaintNo) || other.complaintNo == _this.complaintNo)&&(identical(other.receivedOn, _this.receivedOn) || other.receivedOn == _this.receivedOn)&&(identical(other.procedure, _this.procedure) || other.procedure == _this.procedure)&&(identical(other.procedureDate, _this.procedureDate) || other.procedureDate == _this.procedureDate)&&(identical(other.patientCode, _this.patientCode) || other.patientCode == _this.patientCode)&&(identical(other.facts, _this.facts) || other.facts == _this.facts)&&(identical(other.status, _this.status) || other.status == _this.status)&&(identical(other.createdAt, _this.createdAt) || other.createdAt == _this.createdAt)&&(identical(other.updatedAt, _this.updatedAt) || other.updatedAt == _this.updatedAt)&&const DeepCollectionEquality().equals(other.documents, _this.documents)&&(identical(other.risk, _this.risk) || other.risk == _this.risk)&&(identical(other.positionStrength, _this.positionStrength) || other.positionStrength == _this.positionStrength)&&(identical(other.lawyerName, _this.lawyerName) || other.lawyerName == _this.lawyerName)&&(identical(other.submittedAt, _this.submittedAt) || other.submittedAt == _this.submittedAt)&&(identical(other.approvedAt, _this.approvedAt) || other.approvedAt == _this.approvedAt));
}


@override
int get hashCode {
  final _this = this as DentalCase;
  return Object.hashAll([runtimeType,_this.id,_this.code,_this.title,_this.caseType,_this.claimType,_this.authority,_this.complaintNo,_this.receivedOn,_this.procedure,_this.procedureDate,_this.patientCode,_this.facts,_this.status,_this.createdAt,_this.updatedAt,const DeepCollectionEquality().hash(_this.documents),_this.risk,_this.positionStrength,_this.lawyerName,_this.submittedAt,_this.approvedAt]);
}

@override
String toString() {
  final _this = this as DentalCase;
  return 'DentalCase(id: ${_this.id}, code: ${_this.code}, title: ${_this.title}, caseType: ${_this.caseType}, claimType: ${_this.claimType}, authority: ${_this.authority}, complaintNo: ${_this.complaintNo}, receivedOn: ${_this.receivedOn}, procedure: ${_this.procedure}, procedureDate: ${_this.procedureDate}, patientCode: ${_this.patientCode}, facts: ${_this.facts}, status: ${_this.status}, createdAt: ${_this.createdAt}, updatedAt: ${_this.updatedAt}, documents: ${_this.documents}, risk: ${_this.risk}, positionStrength: ${_this.positionStrength}, lawyerName: ${_this.lawyerName}, submittedAt: ${_this.submittedAt}, approvedAt: ${_this.approvedAt})';
}


}

/// @nodoc
abstract mixin class $DentalCaseCopyWith<$Res>  {
  factory $DentalCaseCopyWith(DentalCase value, $Res Function(DentalCase) _then) = _$DentalCaseCopyWithImpl;
@useResult
$Res call({
 String id, String code, String title, CaseType caseType, ClaimType claimType, String authority, String complaintNo, DateTime receivedOn, String procedure, DateTime procedureDate, String patientCode, String facts, CaseStatus status, DateTime createdAt, DateTime updatedAt, List<CaseDocument> documents, RiskLevel? risk, int? positionStrength, String? lawyerName, DateTime? submittedAt, DateTime? approvedAt
});




}
/// @nodoc
class _$DentalCaseCopyWithImpl<$Res>
    implements $DentalCaseCopyWith<$Res> {
  _$DentalCaseCopyWithImpl(this._self, this._then);

  final DentalCase _self;
  final $Res Function(DentalCase) _then;

/// Create a copy of DentalCase
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? code = null,Object? title = null,Object? caseType = null,Object? claimType = null,Object? authority = null,Object? complaintNo = null,Object? receivedOn = null,Object? procedure = null,Object? procedureDate = null,Object? patientCode = null,Object? facts = null,Object? status = null,Object? createdAt = null,Object? updatedAt = null,Object? documents = null,Object? risk = freezed,Object? positionStrength = freezed,Object? lawyerName = freezed,Object? submittedAt = freezed,Object? approvedAt = freezed,}) {
  return _then(DentalCase(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,code: null == code ? _self.code : code // ignore: cast_nullable_to_non_nullable
as String,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,caseType: null == caseType ? _self.caseType : caseType // ignore: cast_nullable_to_non_nullable
as CaseType,claimType: null == claimType ? _self.claimType : claimType // ignore: cast_nullable_to_non_nullable
as ClaimType,authority: null == authority ? _self.authority : authority // ignore: cast_nullable_to_non_nullable
as String,complaintNo: null == complaintNo ? _self.complaintNo : complaintNo // ignore: cast_nullable_to_non_nullable
as String,receivedOn: null == receivedOn ? _self.receivedOn : receivedOn // ignore: cast_nullable_to_non_nullable
as DateTime,procedure: null == procedure ? _self.procedure : procedure // ignore: cast_nullable_to_non_nullable
as String,procedureDate: null == procedureDate ? _self.procedureDate : procedureDate // ignore: cast_nullable_to_non_nullable
as DateTime,patientCode: null == patientCode ? _self.patientCode : patientCode // ignore: cast_nullable_to_non_nullable
as String,facts: null == facts ? _self.facts : facts // ignore: cast_nullable_to_non_nullable
as String,status: null == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as CaseStatus,createdAt: null == createdAt ? _self.createdAt : createdAt // ignore: cast_nullable_to_non_nullable
as DateTime,updatedAt: null == updatedAt ? _self.updatedAt : updatedAt // ignore: cast_nullable_to_non_nullable
as DateTime,documents: null == documents ? _self.documents : documents // ignore: cast_nullable_to_non_nullable
as List<CaseDocument>,risk: freezed == risk ? _self.risk : risk // ignore: cast_nullable_to_non_nullable
as RiskLevel?,positionStrength: freezed == positionStrength ? _self.positionStrength : positionStrength // ignore: cast_nullable_to_non_nullable
as int?,lawyerName: freezed == lawyerName ? _self.lawyerName : lawyerName // ignore: cast_nullable_to_non_nullable
as String?,submittedAt: freezed == submittedAt ? _self.submittedAt : submittedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,approvedAt: freezed == approvedAt ? _self.approvedAt : approvedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}

}


/// Adds pattern-matching-related methods to [DentalCase].
extension DentalCasePatterns on DentalCase {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _DentalCase value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _DentalCase() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _DentalCase value)  $default,){
final _that = this;
switch (_that) {
case _DentalCase():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _DentalCase value)?  $default,){
final _that = this;
switch (_that) {
case _DentalCase() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String id,  String code,  String title,  CaseType caseType,  ClaimType claimType,  String authority,  String complaintNo,  DateTime receivedOn,  String procedure,  DateTime procedureDate,  String patientCode,  String facts,  CaseStatus status,  DateTime createdAt,  DateTime updatedAt,  List<CaseDocument> documents,  RiskLevel? risk,  int? positionStrength,  String? lawyerName,  DateTime? submittedAt,  DateTime? approvedAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _DentalCase() when $default != null:
return $default(_that.id,_that.code,_that.title,_that.caseType,_that.claimType,_that.authority,_that.complaintNo,_that.receivedOn,_that.procedure,_that.procedureDate,_that.patientCode,_that.facts,_that.status,_that.createdAt,_that.updatedAt,_that.documents,_that.risk,_that.positionStrength,_that.lawyerName,_that.submittedAt,_that.approvedAt);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String id,  String code,  String title,  CaseType caseType,  ClaimType claimType,  String authority,  String complaintNo,  DateTime receivedOn,  String procedure,  DateTime procedureDate,  String patientCode,  String facts,  CaseStatus status,  DateTime createdAt,  DateTime updatedAt,  List<CaseDocument> documents,  RiskLevel? risk,  int? positionStrength,  String? lawyerName,  DateTime? submittedAt,  DateTime? approvedAt)  $default,) {final _that = this;
switch (_that) {
case _DentalCase():
return $default(_that.id,_that.code,_that.title,_that.caseType,_that.claimType,_that.authority,_that.complaintNo,_that.receivedOn,_that.procedure,_that.procedureDate,_that.patientCode,_that.facts,_that.status,_that.createdAt,_that.updatedAt,_that.documents,_that.risk,_that.positionStrength,_that.lawyerName,_that.submittedAt,_that.approvedAt);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String id,  String code,  String title,  CaseType caseType,  ClaimType claimType,  String authority,  String complaintNo,  DateTime receivedOn,  String procedure,  DateTime procedureDate,  String patientCode,  String facts,  CaseStatus status,  DateTime createdAt,  DateTime updatedAt,  List<CaseDocument> documents,  RiskLevel? risk,  int? positionStrength,  String? lawyerName,  DateTime? submittedAt,  DateTime? approvedAt)?  $default,) {final _that = this;
switch (_that) {
case _DentalCase() when $default != null:
return $default(_that.id,_that.code,_that.title,_that.caseType,_that.claimType,_that.authority,_that.complaintNo,_that.receivedOn,_that.procedure,_that.procedureDate,_that.patientCode,_that.facts,_that.status,_that.createdAt,_that.updatedAt,_that.documents,_that.risk,_that.positionStrength,_that.lawyerName,_that.submittedAt,_that.approvedAt);case _:
  return null;

}
}

}

/// @nodoc


class _DentalCase implements DentalCase {
  const _DentalCase({required this.id, required this.code, required this.title, required this.caseType, required this.claimType, required this.authority, required this.complaintNo, required this.receivedOn, required this.procedure, required this.procedureDate, required this.patientCode, required this.facts, required this.status, required this.createdAt, required this.updatedAt,  List<CaseDocument> documents = const [], this.risk, this.positionStrength, this.lawyerName, this.submittedAt, this.approvedAt}): _documents = documents;
  

@override final  String id;
@override final  String code;
@override final  String title;
@override final  CaseType caseType;
@override final  ClaimType claimType;
@override final  String authority;
@override final  String complaintNo;
@override final  DateTime receivedOn;
@override final  String procedure;
@override final  DateTime procedureDate;
@override final  String patientCode;
@override final  String facts;
@override final  CaseStatus status;
@override final  DateTime createdAt;
@override final  DateTime updatedAt;
 final  List<CaseDocument> _documents;
@override@JsonKey() List<CaseDocument> get documents {
  if (_documents is EqualUnmodifiableListView) return _documents;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_documents);
}

@override final  RiskLevel? risk;
@override final  int? positionStrength;
@override final  String? lawyerName;
@override final  DateTime? submittedAt;
@override final  DateTime? approvedAt;

/// Create a copy of DentalCase
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$DentalCaseCopyWith<_DentalCase> get copyWith => __$DentalCaseCopyWithImpl<_DentalCase>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _DentalCase&&(identical(other.id, id) || other.id == id)&&(identical(other.code, code) || other.code == code)&&(identical(other.title, title) || other.title == title)&&(identical(other.caseType, caseType) || other.caseType == caseType)&&(identical(other.claimType, claimType) || other.claimType == claimType)&&(identical(other.authority, authority) || other.authority == authority)&&(identical(other.complaintNo, complaintNo) || other.complaintNo == complaintNo)&&(identical(other.receivedOn, receivedOn) || other.receivedOn == receivedOn)&&(identical(other.procedure, procedure) || other.procedure == procedure)&&(identical(other.procedureDate, procedureDate) || other.procedureDate == procedureDate)&&(identical(other.patientCode, patientCode) || other.patientCode == patientCode)&&(identical(other.facts, facts) || other.facts == facts)&&(identical(other.status, status) || other.status == status)&&(identical(other.createdAt, createdAt) || other.createdAt == createdAt)&&(identical(other.updatedAt, updatedAt) || other.updatedAt == updatedAt)&&const DeepCollectionEquality().equals(other.documents, _documents)&&(identical(other.risk, risk) || other.risk == risk)&&(identical(other.positionStrength, positionStrength) || other.positionStrength == positionStrength)&&(identical(other.lawyerName, lawyerName) || other.lawyerName == lawyerName)&&(identical(other.submittedAt, submittedAt) || other.submittedAt == submittedAt)&&(identical(other.approvedAt, approvedAt) || other.approvedAt == approvedAt));
}


@override
int get hashCode {
    return Object.hashAll([runtimeType,id,code,title,caseType,claimType,authority,complaintNo,receivedOn,procedure,procedureDate,patientCode,facts,status,createdAt,updatedAt,const DeepCollectionEquality().hash(_documents),risk,positionStrength,lawyerName,submittedAt,approvedAt]);
}

@override
String toString() {
    return 'DentalCase(id: $id, code: $code, title: $title, caseType: $caseType, claimType: $claimType, authority: $authority, complaintNo: $complaintNo, receivedOn: $receivedOn, procedure: $procedure, procedureDate: $procedureDate, patientCode: $patientCode, facts: $facts, status: $status, createdAt: $createdAt, updatedAt: $updatedAt, documents: $documents, risk: $risk, positionStrength: $positionStrength, lawyerName: $lawyerName, submittedAt: $submittedAt, approvedAt: $approvedAt)';
}


}

/// @nodoc
abstract mixin class _$DentalCaseCopyWith<$Res> implements $DentalCaseCopyWith<$Res> {
  factory _$DentalCaseCopyWith(_DentalCase value, $Res Function(_DentalCase) _then) = __$DentalCaseCopyWithImpl;
@override @useResult
$Res call({
 String id, String code, String title, CaseType caseType, ClaimType claimType, String authority, String complaintNo, DateTime receivedOn, String procedure, DateTime procedureDate, String patientCode, String facts, CaseStatus status, DateTime createdAt, DateTime updatedAt, List<CaseDocument> documents, RiskLevel? risk, int? positionStrength, String? lawyerName, DateTime? submittedAt, DateTime? approvedAt
});




}
/// @nodoc
class __$DentalCaseCopyWithImpl<$Res>
    implements _$DentalCaseCopyWith<$Res> {
  __$DentalCaseCopyWithImpl(this._self, this._then);

  final _DentalCase _self;
  final $Res Function(_DentalCase) _then;

/// Create a copy of DentalCase
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? code = null,Object? title = null,Object? caseType = null,Object? claimType = null,Object? authority = null,Object? complaintNo = null,Object? receivedOn = null,Object? procedure = null,Object? procedureDate = null,Object? patientCode = null,Object? facts = null,Object? status = null,Object? createdAt = null,Object? updatedAt = null,Object? documents = null,Object? risk = freezed,Object? positionStrength = freezed,Object? lawyerName = freezed,Object? submittedAt = freezed,Object? approvedAt = freezed,}) {
  return _then(_DentalCase(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,code: null == code ? _self.code : code // ignore: cast_nullable_to_non_nullable
as String,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,caseType: null == caseType ? _self.caseType : caseType // ignore: cast_nullable_to_non_nullable
as CaseType,claimType: null == claimType ? _self.claimType : claimType // ignore: cast_nullable_to_non_nullable
as ClaimType,authority: null == authority ? _self.authority : authority // ignore: cast_nullable_to_non_nullable
as String,complaintNo: null == complaintNo ? _self.complaintNo : complaintNo // ignore: cast_nullable_to_non_nullable
as String,receivedOn: null == receivedOn ? _self.receivedOn : receivedOn // ignore: cast_nullable_to_non_nullable
as DateTime,procedure: null == procedure ? _self.procedure : procedure // ignore: cast_nullable_to_non_nullable
as String,procedureDate: null == procedureDate ? _self.procedureDate : procedureDate // ignore: cast_nullable_to_non_nullable
as DateTime,patientCode: null == patientCode ? _self.patientCode : patientCode // ignore: cast_nullable_to_non_nullable
as String,facts: null == facts ? _self.facts : facts // ignore: cast_nullable_to_non_nullable
as String,status: null == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as CaseStatus,createdAt: null == createdAt ? _self.createdAt : createdAt // ignore: cast_nullable_to_non_nullable
as DateTime,updatedAt: null == updatedAt ? _self.updatedAt : updatedAt // ignore: cast_nullable_to_non_nullable
as DateTime,documents: null == documents ? _self._documents : documents // ignore: cast_nullable_to_non_nullable
as List<CaseDocument>,risk: freezed == risk ? _self.risk : risk // ignore: cast_nullable_to_non_nullable
as RiskLevel?,positionStrength: freezed == positionStrength ? _self.positionStrength : positionStrength // ignore: cast_nullable_to_non_nullable
as int?,lawyerName: freezed == lawyerName ? _self.lawyerName : lawyerName // ignore: cast_nullable_to_non_nullable
as String?,submittedAt: freezed == submittedAt ? _self.submittedAt : submittedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,approvedAt: freezed == approvedAt ? _self.approvedAt : approvedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}


}

/// @nodoc
mixin _$RegulationSource {

 String get ref; String get law; String get label; String get title; String get text; double get score;
/// Create a copy of RegulationSource
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$RegulationSourceCopyWith<RegulationSource> get copyWith => _$RegulationSourceCopyWithImpl<RegulationSource>(this as RegulationSource, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as RegulationSource;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is RegulationSource&&(identical(other.ref, _this.ref) || other.ref == _this.ref)&&(identical(other.law, _this.law) || other.law == _this.law)&&(identical(other.label, _this.label) || other.label == _this.label)&&(identical(other.title, _this.title) || other.title == _this.title)&&(identical(other.text, _this.text) || other.text == _this.text)&&(identical(other.score, _this.score) || other.score == _this.score));
}


@override
int get hashCode {
  final _this = this as RegulationSource;
  return Object.hash(runtimeType,_this.ref,_this.law,_this.label,_this.title,_this.text,_this.score);
}

@override
String toString() {
  final _this = this as RegulationSource;
  return 'RegulationSource(ref: ${_this.ref}, law: ${_this.law}, label: ${_this.label}, title: ${_this.title}, text: ${_this.text}, score: ${_this.score})';
}


}

/// @nodoc
abstract mixin class $RegulationSourceCopyWith<$Res>  {
  factory $RegulationSourceCopyWith(RegulationSource value, $Res Function(RegulationSource) _then) = _$RegulationSourceCopyWithImpl;
@useResult
$Res call({
 String ref, String law, String label, String title, String text, double score
});




}
/// @nodoc
class _$RegulationSourceCopyWithImpl<$Res>
    implements $RegulationSourceCopyWith<$Res> {
  _$RegulationSourceCopyWithImpl(this._self, this._then);

  final RegulationSource _self;
  final $Res Function(RegulationSource) _then;

/// Create a copy of RegulationSource
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? ref = null,Object? law = null,Object? label = null,Object? title = null,Object? text = null,Object? score = null,}) {
  return _then(RegulationSource(
ref: null == ref ? _self.ref : ref // ignore: cast_nullable_to_non_nullable
as String,law: null == law ? _self.law : law // ignore: cast_nullable_to_non_nullable
as String,label: null == label ? _self.label : label // ignore: cast_nullable_to_non_nullable
as String,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,score: null == score ? _self.score : score // ignore: cast_nullable_to_non_nullable
as double,
  ));
}

}


/// Adds pattern-matching-related methods to [RegulationSource].
extension RegulationSourcePatterns on RegulationSource {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _RegulationSource value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _RegulationSource() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _RegulationSource value)  $default,){
final _that = this;
switch (_that) {
case _RegulationSource():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _RegulationSource value)?  $default,){
final _that = this;
switch (_that) {
case _RegulationSource() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String ref,  String law,  String label,  String title,  String text,  double score)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _RegulationSource() when $default != null:
return $default(_that.ref,_that.law,_that.label,_that.title,_that.text,_that.score);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String ref,  String law,  String label,  String title,  String text,  double score)  $default,) {final _that = this;
switch (_that) {
case _RegulationSource():
return $default(_that.ref,_that.law,_that.label,_that.title,_that.text,_that.score);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String ref,  String law,  String label,  String title,  String text,  double score)?  $default,) {final _that = this;
switch (_that) {
case _RegulationSource() when $default != null:
return $default(_that.ref,_that.law,_that.label,_that.title,_that.text,_that.score);case _:
  return null;

}
}

}

/// @nodoc


class _RegulationSource implements RegulationSource {
  const _RegulationSource({required this.ref, required this.law, required this.label, required this.title, required this.text, required this.score});
  

@override final  String ref;
@override final  String law;
@override final  String label;
@override final  String title;
@override final  String text;
@override final  double score;

/// Create a copy of RegulationSource
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$RegulationSourceCopyWith<_RegulationSource> get copyWith => __$RegulationSourceCopyWithImpl<_RegulationSource>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _RegulationSource&&(identical(other.ref, ref) || other.ref == ref)&&(identical(other.law, law) || other.law == law)&&(identical(other.label, label) || other.label == label)&&(identical(other.title, title) || other.title == title)&&(identical(other.text, text) || other.text == text)&&(identical(other.score, score) || other.score == score));
}


@override
int get hashCode {
    return Object.hash(runtimeType,ref,law,label,title,text,score);
}

@override
String toString() {
    return 'RegulationSource(ref: $ref, law: $law, label: $label, title: $title, text: $text, score: $score)';
}


}

/// @nodoc
abstract mixin class _$RegulationSourceCopyWith<$Res> implements $RegulationSourceCopyWith<$Res> {
  factory _$RegulationSourceCopyWith(_RegulationSource value, $Res Function(_RegulationSource) _then) = __$RegulationSourceCopyWithImpl;
@override @useResult
$Res call({
 String ref, String law, String label, String title, String text, double score
});




}
/// @nodoc
class __$RegulationSourceCopyWithImpl<$Res>
    implements _$RegulationSourceCopyWith<$Res> {
  __$RegulationSourceCopyWithImpl(this._self, this._then);

  final _RegulationSource _self;
  final $Res Function(_RegulationSource) _then;

/// Create a copy of RegulationSource
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? ref = null,Object? law = null,Object? label = null,Object? title = null,Object? text = null,Object? score = null,}) {
  return _then(_RegulationSource(
ref: null == ref ? _self.ref : ref // ignore: cast_nullable_to_non_nullable
as String,law: null == law ? _self.law : law // ignore: cast_nullable_to_non_nullable
as String,label: null == label ? _self.label : label // ignore: cast_nullable_to_non_nullable
as String,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,score: null == score ? _self.score : score // ignore: cast_nullable_to_non_nullable
as double,
  ));
}


}

/// @nodoc
mixin _$Citation {

 String get ref; String get label; bool get verified;
/// Create a copy of Citation
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CitationCopyWith<Citation> get copyWith => _$CitationCopyWithImpl<Citation>(this as Citation, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as Citation;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Citation&&(identical(other.ref, _this.ref) || other.ref == _this.ref)&&(identical(other.label, _this.label) || other.label == _this.label)&&(identical(other.verified, _this.verified) || other.verified == _this.verified));
}


@override
int get hashCode {
  final _this = this as Citation;
  return Object.hash(runtimeType,_this.ref,_this.label,_this.verified);
}

@override
String toString() {
  final _this = this as Citation;
  return 'Citation(ref: ${_this.ref}, label: ${_this.label}, verified: ${_this.verified})';
}


}

/// @nodoc
abstract mixin class $CitationCopyWith<$Res>  {
  factory $CitationCopyWith(Citation value, $Res Function(Citation) _then) = _$CitationCopyWithImpl;
@useResult
$Res call({
 String ref, String label, bool verified
});




}
/// @nodoc
class _$CitationCopyWithImpl<$Res>
    implements $CitationCopyWith<$Res> {
  _$CitationCopyWithImpl(this._self, this._then);

  final Citation _self;
  final $Res Function(Citation) _then;

/// Create a copy of Citation
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? ref = null,Object? label = null,Object? verified = null,}) {
  return _then(Citation(
ref: null == ref ? _self.ref : ref // ignore: cast_nullable_to_non_nullable
as String,label: null == label ? _self.label : label // ignore: cast_nullable_to_non_nullable
as String,verified: null == verified ? _self.verified : verified // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [Citation].
extension CitationPatterns on Citation {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Citation value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Citation() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Citation value)  $default,){
final _that = this;
switch (_that) {
case _Citation():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Citation value)?  $default,){
final _that = this;
switch (_that) {
case _Citation() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String ref,  String label,  bool verified)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Citation() when $default != null:
return $default(_that.ref,_that.label,_that.verified);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String ref,  String label,  bool verified)  $default,) {final _that = this;
switch (_that) {
case _Citation():
return $default(_that.ref,_that.label,_that.verified);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String ref,  String label,  bool verified)?  $default,) {final _that = this;
switch (_that) {
case _Citation() when $default != null:
return $default(_that.ref,_that.label,_that.verified);case _:
  return null;

}
}

}

/// @nodoc


class _Citation extends Citation {
  const _Citation({required this.ref, required this.label, this.verified = true}): super._();
  

@override final  String ref;
@override final  String label;
@override@JsonKey() final  bool verified;

/// Create a copy of Citation
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CitationCopyWith<_Citation> get copyWith => __$CitationCopyWithImpl<_Citation>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _Citation&&(identical(other.ref, ref) || other.ref == ref)&&(identical(other.label, label) || other.label == label)&&(identical(other.verified, verified) || other.verified == verified));
}


@override
int get hashCode {
    return Object.hash(runtimeType,ref,label,verified);
}

@override
String toString() {
    return 'Citation(ref: $ref, label: $label, verified: $verified)';
}


}

/// @nodoc
abstract mixin class _$CitationCopyWith<$Res> implements $CitationCopyWith<$Res> {
  factory _$CitationCopyWith(_Citation value, $Res Function(_Citation) _then) = __$CitationCopyWithImpl;
@override @useResult
$Res call({
 String ref, String label, bool verified
});




}
/// @nodoc
class __$CitationCopyWithImpl<$Res>
    implements _$CitationCopyWith<$Res> {
  __$CitationCopyWithImpl(this._self, this._then);

  final _Citation _self;
  final $Res Function(_Citation) _then;

/// Create a copy of Citation
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? ref = null,Object? label = null,Object? verified = null,}) {
  return _then(_Citation(
ref: null == ref ? _self.ref : ref // ignore: cast_nullable_to_non_nullable
as String,label: null == label ? _self.label : label // ignore: cast_nullable_to_non_nullable
as String,verified: null == verified ? _self.verified : verified // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}

/// @nodoc
mixin _$AnalysisPoint {

 String get text; List<Citation> get citations; RiskLevel? get severity; Urgency? get urgency;
/// Create a copy of AnalysisPoint
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$AnalysisPointCopyWith<AnalysisPoint> get copyWith => _$AnalysisPointCopyWithImpl<AnalysisPoint>(this as AnalysisPoint, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as AnalysisPoint;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is AnalysisPoint&&(identical(other.text, _this.text) || other.text == _this.text)&&const DeepCollectionEquality().equals(other.citations, _this.citations)&&(identical(other.severity, _this.severity) || other.severity == _this.severity)&&(identical(other.urgency, _this.urgency) || other.urgency == _this.urgency));
}


@override
int get hashCode {
  final _this = this as AnalysisPoint;
  return Object.hash(runtimeType,_this.text,const DeepCollectionEquality().hash(_this.citations),_this.severity,_this.urgency);
}

@override
String toString() {
  final _this = this as AnalysisPoint;
  return 'AnalysisPoint(text: ${_this.text}, citations: ${_this.citations}, severity: ${_this.severity}, urgency: ${_this.urgency})';
}


}

/// @nodoc
abstract mixin class $AnalysisPointCopyWith<$Res>  {
  factory $AnalysisPointCopyWith(AnalysisPoint value, $Res Function(AnalysisPoint) _then) = _$AnalysisPointCopyWithImpl;
@useResult
$Res call({
 String text, List<Citation> citations, RiskLevel? severity, Urgency? urgency
});




}
/// @nodoc
class _$AnalysisPointCopyWithImpl<$Res>
    implements $AnalysisPointCopyWith<$Res> {
  _$AnalysisPointCopyWithImpl(this._self, this._then);

  final AnalysisPoint _self;
  final $Res Function(AnalysisPoint) _then;

/// Create a copy of AnalysisPoint
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? text = null,Object? citations = null,Object? severity = freezed,Object? urgency = freezed,}) {
  return _then(AnalysisPoint(
text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,citations: null == citations ? _self.citations : citations // ignore: cast_nullable_to_non_nullable
as List<Citation>,severity: freezed == severity ? _self.severity : severity // ignore: cast_nullable_to_non_nullable
as RiskLevel?,urgency: freezed == urgency ? _self.urgency : urgency // ignore: cast_nullable_to_non_nullable
as Urgency?,
  ));
}

}


/// Adds pattern-matching-related methods to [AnalysisPoint].
extension AnalysisPointPatterns on AnalysisPoint {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _AnalysisPoint value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _AnalysisPoint() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _AnalysisPoint value)  $default,){
final _that = this;
switch (_that) {
case _AnalysisPoint():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _AnalysisPoint value)?  $default,){
final _that = this;
switch (_that) {
case _AnalysisPoint() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String text,  List<Citation> citations,  RiskLevel? severity,  Urgency? urgency)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _AnalysisPoint() when $default != null:
return $default(_that.text,_that.citations,_that.severity,_that.urgency);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String text,  List<Citation> citations,  RiskLevel? severity,  Urgency? urgency)  $default,) {final _that = this;
switch (_that) {
case _AnalysisPoint():
return $default(_that.text,_that.citations,_that.severity,_that.urgency);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String text,  List<Citation> citations,  RiskLevel? severity,  Urgency? urgency)?  $default,) {final _that = this;
switch (_that) {
case _AnalysisPoint() when $default != null:
return $default(_that.text,_that.citations,_that.severity,_that.urgency);case _:
  return null;

}
}

}

/// @nodoc


class _AnalysisPoint implements AnalysisPoint {
  const _AnalysisPoint({required this.text,  List<Citation> citations = const [], this.severity, this.urgency}): _citations = citations;
  

@override final  String text;
 final  List<Citation> _citations;
@override@JsonKey() List<Citation> get citations {
  if (_citations is EqualUnmodifiableListView) return _citations;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_citations);
}

@override final  RiskLevel? severity;
@override final  Urgency? urgency;

/// Create a copy of AnalysisPoint
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$AnalysisPointCopyWith<_AnalysisPoint> get copyWith => __$AnalysisPointCopyWithImpl<_AnalysisPoint>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _AnalysisPoint&&(identical(other.text, text) || other.text == text)&&const DeepCollectionEquality().equals(other.citations, _citations)&&(identical(other.severity, severity) || other.severity == severity)&&(identical(other.urgency, urgency) || other.urgency == urgency));
}


@override
int get hashCode {
    return Object.hash(runtimeType,text,const DeepCollectionEquality().hash(_citations),severity,urgency);
}

@override
String toString() {
    return 'AnalysisPoint(text: $text, citations: $citations, severity: $severity, urgency: $urgency)';
}


}

/// @nodoc
abstract mixin class _$AnalysisPointCopyWith<$Res> implements $AnalysisPointCopyWith<$Res> {
  factory _$AnalysisPointCopyWith(_AnalysisPoint value, $Res Function(_AnalysisPoint) _then) = __$AnalysisPointCopyWithImpl;
@override @useResult
$Res call({
 String text, List<Citation> citations, RiskLevel? severity, Urgency? urgency
});




}
/// @nodoc
class __$AnalysisPointCopyWithImpl<$Res>
    implements _$AnalysisPointCopyWith<$Res> {
  __$AnalysisPointCopyWithImpl(this._self, this._then);

  final _AnalysisPoint _self;
  final $Res Function(_AnalysisPoint) _then;

/// Create a copy of AnalysisPoint
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? text = null,Object? citations = null,Object? severity = freezed,Object? urgency = freezed,}) {
  return _then(_AnalysisPoint(
text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,citations: null == citations ? _self._citations : citations // ignore: cast_nullable_to_non_nullable
as List<Citation>,severity: freezed == severity ? _self.severity : severity // ignore: cast_nullable_to_non_nullable
as RiskLevel?,urgency: freezed == urgency ? _self.urgency : urgency // ignore: cast_nullable_to_non_nullable
as Urgency?,
  ));
}


}

/// @nodoc
mixin _$CaseAnalysis {

 String get caseId; int get positionStrength; RiskLevel get risk; String get summary; List<AnalysisPoint> get strengths; List<AnalysisPoint> get weaknesses; List<AnalysisPoint> get actions; List<RegulationSource> get sources; bool get sourcesSufficient;
/// Create a copy of CaseAnalysis
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CaseAnalysisCopyWith<CaseAnalysis> get copyWith => _$CaseAnalysisCopyWithImpl<CaseAnalysis>(this as CaseAnalysis, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as CaseAnalysis;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CaseAnalysis&&(identical(other.caseId, _this.caseId) || other.caseId == _this.caseId)&&(identical(other.positionStrength, _this.positionStrength) || other.positionStrength == _this.positionStrength)&&(identical(other.risk, _this.risk) || other.risk == _this.risk)&&(identical(other.summary, _this.summary) || other.summary == _this.summary)&&const DeepCollectionEquality().equals(other.strengths, _this.strengths)&&const DeepCollectionEquality().equals(other.weaknesses, _this.weaknesses)&&const DeepCollectionEquality().equals(other.actions, _this.actions)&&const DeepCollectionEquality().equals(other.sources, _this.sources)&&(identical(other.sourcesSufficient, _this.sourcesSufficient) || other.sourcesSufficient == _this.sourcesSufficient));
}


@override
int get hashCode {
  final _this = this as CaseAnalysis;
  return Object.hash(runtimeType,_this.caseId,_this.positionStrength,_this.risk,_this.summary,const DeepCollectionEquality().hash(_this.strengths),const DeepCollectionEquality().hash(_this.weaknesses),const DeepCollectionEquality().hash(_this.actions),const DeepCollectionEquality().hash(_this.sources),_this.sourcesSufficient);
}

@override
String toString() {
  final _this = this as CaseAnalysis;
  return 'CaseAnalysis(caseId: ${_this.caseId}, positionStrength: ${_this.positionStrength}, risk: ${_this.risk}, summary: ${_this.summary}, strengths: ${_this.strengths}, weaknesses: ${_this.weaknesses}, actions: ${_this.actions}, sources: ${_this.sources}, sourcesSufficient: ${_this.sourcesSufficient})';
}


}

/// @nodoc
abstract mixin class $CaseAnalysisCopyWith<$Res>  {
  factory $CaseAnalysisCopyWith(CaseAnalysis value, $Res Function(CaseAnalysis) _then) = _$CaseAnalysisCopyWithImpl;
@useResult
$Res call({
 String caseId, int positionStrength, RiskLevel risk, String summary, List<AnalysisPoint> strengths, List<AnalysisPoint> weaknesses, List<AnalysisPoint> actions, List<RegulationSource> sources, bool sourcesSufficient
});




}
/// @nodoc
class _$CaseAnalysisCopyWithImpl<$Res>
    implements $CaseAnalysisCopyWith<$Res> {
  _$CaseAnalysisCopyWithImpl(this._self, this._then);

  final CaseAnalysis _self;
  final $Res Function(CaseAnalysis) _then;

/// Create a copy of CaseAnalysis
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? caseId = null,Object? positionStrength = null,Object? risk = null,Object? summary = null,Object? strengths = null,Object? weaknesses = null,Object? actions = null,Object? sources = null,Object? sourcesSufficient = null,}) {
  return _then(CaseAnalysis(
caseId: null == caseId ? _self.caseId : caseId // ignore: cast_nullable_to_non_nullable
as String,positionStrength: null == positionStrength ? _self.positionStrength : positionStrength // ignore: cast_nullable_to_non_nullable
as int,risk: null == risk ? _self.risk : risk // ignore: cast_nullable_to_non_nullable
as RiskLevel,summary: null == summary ? _self.summary : summary // ignore: cast_nullable_to_non_nullable
as String,strengths: null == strengths ? _self.strengths : strengths // ignore: cast_nullable_to_non_nullable
as List<AnalysisPoint>,weaknesses: null == weaknesses ? _self.weaknesses : weaknesses // ignore: cast_nullable_to_non_nullable
as List<AnalysisPoint>,actions: null == actions ? _self.actions : actions // ignore: cast_nullable_to_non_nullable
as List<AnalysisPoint>,sources: null == sources ? _self.sources : sources // ignore: cast_nullable_to_non_nullable
as List<RegulationSource>,sourcesSufficient: null == sourcesSufficient ? _self.sourcesSufficient : sourcesSufficient // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [CaseAnalysis].
extension CaseAnalysisPatterns on CaseAnalysis {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _CaseAnalysis value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _CaseAnalysis() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _CaseAnalysis value)  $default,){
final _that = this;
switch (_that) {
case _CaseAnalysis():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _CaseAnalysis value)?  $default,){
final _that = this;
switch (_that) {
case _CaseAnalysis() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String caseId,  int positionStrength,  RiskLevel risk,  String summary,  List<AnalysisPoint> strengths,  List<AnalysisPoint> weaknesses,  List<AnalysisPoint> actions,  List<RegulationSource> sources,  bool sourcesSufficient)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CaseAnalysis() when $default != null:
return $default(_that.caseId,_that.positionStrength,_that.risk,_that.summary,_that.strengths,_that.weaknesses,_that.actions,_that.sources,_that.sourcesSufficient);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String caseId,  int positionStrength,  RiskLevel risk,  String summary,  List<AnalysisPoint> strengths,  List<AnalysisPoint> weaknesses,  List<AnalysisPoint> actions,  List<RegulationSource> sources,  bool sourcesSufficient)  $default,) {final _that = this;
switch (_that) {
case _CaseAnalysis():
return $default(_that.caseId,_that.positionStrength,_that.risk,_that.summary,_that.strengths,_that.weaknesses,_that.actions,_that.sources,_that.sourcesSufficient);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String caseId,  int positionStrength,  RiskLevel risk,  String summary,  List<AnalysisPoint> strengths,  List<AnalysisPoint> weaknesses,  List<AnalysisPoint> actions,  List<RegulationSource> sources,  bool sourcesSufficient)?  $default,) {final _that = this;
switch (_that) {
case _CaseAnalysis() when $default != null:
return $default(_that.caseId,_that.positionStrength,_that.risk,_that.summary,_that.strengths,_that.weaknesses,_that.actions,_that.sources,_that.sourcesSufficient);case _:
  return null;

}
}

}

/// @nodoc


class _CaseAnalysis implements CaseAnalysis {
  const _CaseAnalysis({required this.caseId, required this.positionStrength, required this.risk, required this.summary, required  List<AnalysisPoint> strengths, required  List<AnalysisPoint> weaknesses, required  List<AnalysisPoint> actions, required  List<RegulationSource> sources, this.sourcesSufficient = true}): _strengths = strengths,_weaknesses = weaknesses,_actions = actions,_sources = sources;
  

@override final  String caseId;
@override final  int positionStrength;
@override final  RiskLevel risk;
@override final  String summary;
 final  List<AnalysisPoint> _strengths;
@override List<AnalysisPoint> get strengths {
  if (_strengths is EqualUnmodifiableListView) return _strengths;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_strengths);
}

 final  List<AnalysisPoint> _weaknesses;
@override List<AnalysisPoint> get weaknesses {
  if (_weaknesses is EqualUnmodifiableListView) return _weaknesses;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_weaknesses);
}

 final  List<AnalysisPoint> _actions;
@override List<AnalysisPoint> get actions {
  if (_actions is EqualUnmodifiableListView) return _actions;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_actions);
}

 final  List<RegulationSource> _sources;
@override List<RegulationSource> get sources {
  if (_sources is EqualUnmodifiableListView) return _sources;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_sources);
}

@override@JsonKey() final  bool sourcesSufficient;

/// Create a copy of CaseAnalysis
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CaseAnalysisCopyWith<_CaseAnalysis> get copyWith => __$CaseAnalysisCopyWithImpl<_CaseAnalysis>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _CaseAnalysis&&(identical(other.caseId, caseId) || other.caseId == caseId)&&(identical(other.positionStrength, positionStrength) || other.positionStrength == positionStrength)&&(identical(other.risk, risk) || other.risk == risk)&&(identical(other.summary, summary) || other.summary == summary)&&const DeepCollectionEquality().equals(other.strengths, _strengths)&&const DeepCollectionEquality().equals(other.weaknesses, _weaknesses)&&const DeepCollectionEquality().equals(other.actions, _actions)&&const DeepCollectionEquality().equals(other.sources, _sources)&&(identical(other.sourcesSufficient, sourcesSufficient) || other.sourcesSufficient == sourcesSufficient));
}


@override
int get hashCode {
    return Object.hash(runtimeType,caseId,positionStrength,risk,summary,const DeepCollectionEquality().hash(_strengths),const DeepCollectionEquality().hash(_weaknesses),const DeepCollectionEquality().hash(_actions),const DeepCollectionEquality().hash(_sources),sourcesSufficient);
}

@override
String toString() {
    return 'CaseAnalysis(caseId: $caseId, positionStrength: $positionStrength, risk: $risk, summary: $summary, strengths: $strengths, weaknesses: $weaknesses, actions: $actions, sources: $sources, sourcesSufficient: $sourcesSufficient)';
}


}

/// @nodoc
abstract mixin class _$CaseAnalysisCopyWith<$Res> implements $CaseAnalysisCopyWith<$Res> {
  factory _$CaseAnalysisCopyWith(_CaseAnalysis value, $Res Function(_CaseAnalysis) _then) = __$CaseAnalysisCopyWithImpl;
@override @useResult
$Res call({
 String caseId, int positionStrength, RiskLevel risk, String summary, List<AnalysisPoint> strengths, List<AnalysisPoint> weaknesses, List<AnalysisPoint> actions, List<RegulationSource> sources, bool sourcesSufficient
});




}
/// @nodoc
class __$CaseAnalysisCopyWithImpl<$Res>
    implements _$CaseAnalysisCopyWith<$Res> {
  __$CaseAnalysisCopyWithImpl(this._self, this._then);

  final _CaseAnalysis _self;
  final $Res Function(_CaseAnalysis) _then;

/// Create a copy of CaseAnalysis
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? caseId = null,Object? positionStrength = null,Object? risk = null,Object? summary = null,Object? strengths = null,Object? weaknesses = null,Object? actions = null,Object? sources = null,Object? sourcesSufficient = null,}) {
  return _then(_CaseAnalysis(
caseId: null == caseId ? _self.caseId : caseId // ignore: cast_nullable_to_non_nullable
as String,positionStrength: null == positionStrength ? _self.positionStrength : positionStrength // ignore: cast_nullable_to_non_nullable
as int,risk: null == risk ? _self.risk : risk // ignore: cast_nullable_to_non_nullable
as RiskLevel,summary: null == summary ? _self.summary : summary // ignore: cast_nullable_to_non_nullable
as String,strengths: null == strengths ? _self._strengths : strengths // ignore: cast_nullable_to_non_nullable
as List<AnalysisPoint>,weaknesses: null == weaknesses ? _self._weaknesses : weaknesses // ignore: cast_nullable_to_non_nullable
as List<AnalysisPoint>,actions: null == actions ? _self._actions : actions // ignore: cast_nullable_to_non_nullable
as List<AnalysisPoint>,sources: null == sources ? _self._sources : sources // ignore: cast_nullable_to_non_nullable
as List<RegulationSource>,sourcesSufficient: null == sourcesSufficient ? _self.sourcesSufficient : sourcesSufficient // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}

/// @nodoc
mixin _$DraftParagraph {

 String get text; bool get legalClaim; List<Citation> get citations;
/// Create a copy of DraftParagraph
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$DraftParagraphCopyWith<DraftParagraph> get copyWith => _$DraftParagraphCopyWithImpl<DraftParagraph>(this as DraftParagraph, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as DraftParagraph;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is DraftParagraph&&(identical(other.text, _this.text) || other.text == _this.text)&&(identical(other.legalClaim, _this.legalClaim) || other.legalClaim == _this.legalClaim)&&const DeepCollectionEquality().equals(other.citations, _this.citations));
}


@override
int get hashCode {
  final _this = this as DraftParagraph;
  return Object.hash(runtimeType,_this.text,_this.legalClaim,const DeepCollectionEquality().hash(_this.citations));
}

@override
String toString() {
  final _this = this as DraftParagraph;
  return 'DraftParagraph(text: ${_this.text}, legalClaim: ${_this.legalClaim}, citations: ${_this.citations})';
}


}

/// @nodoc
abstract mixin class $DraftParagraphCopyWith<$Res>  {
  factory $DraftParagraphCopyWith(DraftParagraph value, $Res Function(DraftParagraph) _then) = _$DraftParagraphCopyWithImpl;
@useResult
$Res call({
 String text, bool legalClaim, List<Citation> citations
});




}
/// @nodoc
class _$DraftParagraphCopyWithImpl<$Res>
    implements $DraftParagraphCopyWith<$Res> {
  _$DraftParagraphCopyWithImpl(this._self, this._then);

  final DraftParagraph _self;
  final $Res Function(DraftParagraph) _then;

/// Create a copy of DraftParagraph
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? text = null,Object? legalClaim = null,Object? citations = null,}) {
  return _then(DraftParagraph(
text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,legalClaim: null == legalClaim ? _self.legalClaim : legalClaim // ignore: cast_nullable_to_non_nullable
as bool,citations: null == citations ? _self.citations : citations // ignore: cast_nullable_to_non_nullable
as List<Citation>,
  ));
}

}


/// Adds pattern-matching-related methods to [DraftParagraph].
extension DraftParagraphPatterns on DraftParagraph {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _DraftParagraph value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _DraftParagraph() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _DraftParagraph value)  $default,){
final _that = this;
switch (_that) {
case _DraftParagraph():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _DraftParagraph value)?  $default,){
final _that = this;
switch (_that) {
case _DraftParagraph() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String text,  bool legalClaim,  List<Citation> citations)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _DraftParagraph() when $default != null:
return $default(_that.text,_that.legalClaim,_that.citations);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String text,  bool legalClaim,  List<Citation> citations)  $default,) {final _that = this;
switch (_that) {
case _DraftParagraph():
return $default(_that.text,_that.legalClaim,_that.citations);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String text,  bool legalClaim,  List<Citation> citations)?  $default,) {final _that = this;
switch (_that) {
case _DraftParagraph() when $default != null:
return $default(_that.text,_that.legalClaim,_that.citations);case _:
  return null;

}
}

}

/// @nodoc


class _DraftParagraph extends DraftParagraph {
  const _DraftParagraph({required this.text, this.legalClaim = false,  List<Citation> citations = const []}): _citations = citations,super._();
  

@override final  String text;
@override@JsonKey() final  bool legalClaim;
 final  List<Citation> _citations;
@override@JsonKey() List<Citation> get citations {
  if (_citations is EqualUnmodifiableListView) return _citations;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_citations);
}


/// Create a copy of DraftParagraph
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$DraftParagraphCopyWith<_DraftParagraph> get copyWith => __$DraftParagraphCopyWithImpl<_DraftParagraph>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _DraftParagraph&&(identical(other.text, text) || other.text == text)&&(identical(other.legalClaim, legalClaim) || other.legalClaim == legalClaim)&&const DeepCollectionEquality().equals(other.citations, _citations));
}


@override
int get hashCode {
    return Object.hash(runtimeType,text,legalClaim,const DeepCollectionEquality().hash(_citations));
}

@override
String toString() {
    return 'DraftParagraph(text: $text, legalClaim: $legalClaim, citations: $citations)';
}


}

/// @nodoc
abstract mixin class _$DraftParagraphCopyWith<$Res> implements $DraftParagraphCopyWith<$Res> {
  factory _$DraftParagraphCopyWith(_DraftParagraph value, $Res Function(_DraftParagraph) _then) = __$DraftParagraphCopyWithImpl;
@override @useResult
$Res call({
 String text, bool legalClaim, List<Citation> citations
});




}
/// @nodoc
class __$DraftParagraphCopyWithImpl<$Res>
    implements _$DraftParagraphCopyWith<$Res> {
  __$DraftParagraphCopyWithImpl(this._self, this._then);

  final _DraftParagraph _self;
  final $Res Function(_DraftParagraph) _then;

/// Create a copy of DraftParagraph
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? text = null,Object? legalClaim = null,Object? citations = null,}) {
  return _then(_DraftParagraph(
text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,legalClaim: null == legalClaim ? _self.legalClaim : legalClaim // ignore: cast_nullable_to_non_nullable
as bool,citations: null == citations ? _self._citations : citations // ignore: cast_nullable_to_non_nullable
as List<Citation>,
  ));
}


}

/// @nodoc
mixin _$DraftSection {

 String get heading; List<DraftParagraph> get paragraphs;
/// Create a copy of DraftSection
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$DraftSectionCopyWith<DraftSection> get copyWith => _$DraftSectionCopyWithImpl<DraftSection>(this as DraftSection, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as DraftSection;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is DraftSection&&(identical(other.heading, _this.heading) || other.heading == _this.heading)&&const DeepCollectionEquality().equals(other.paragraphs, _this.paragraphs));
}


@override
int get hashCode {
  final _this = this as DraftSection;
  return Object.hash(runtimeType,_this.heading,const DeepCollectionEquality().hash(_this.paragraphs));
}

@override
String toString() {
  final _this = this as DraftSection;
  return 'DraftSection(heading: ${_this.heading}, paragraphs: ${_this.paragraphs})';
}


}

/// @nodoc
abstract mixin class $DraftSectionCopyWith<$Res>  {
  factory $DraftSectionCopyWith(DraftSection value, $Res Function(DraftSection) _then) = _$DraftSectionCopyWithImpl;
@useResult
$Res call({
 String heading, List<DraftParagraph> paragraphs
});




}
/// @nodoc
class _$DraftSectionCopyWithImpl<$Res>
    implements $DraftSectionCopyWith<$Res> {
  _$DraftSectionCopyWithImpl(this._self, this._then);

  final DraftSection _self;
  final $Res Function(DraftSection) _then;

/// Create a copy of DraftSection
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? heading = null,Object? paragraphs = null,}) {
  return _then(DraftSection(
heading: null == heading ? _self.heading : heading // ignore: cast_nullable_to_non_nullable
as String,paragraphs: null == paragraphs ? _self.paragraphs : paragraphs // ignore: cast_nullable_to_non_nullable
as List<DraftParagraph>,
  ));
}

}


/// Adds pattern-matching-related methods to [DraftSection].
extension DraftSectionPatterns on DraftSection {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _DraftSection value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _DraftSection() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _DraftSection value)  $default,){
final _that = this;
switch (_that) {
case _DraftSection():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _DraftSection value)?  $default,){
final _that = this;
switch (_that) {
case _DraftSection() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String heading,  List<DraftParagraph> paragraphs)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _DraftSection() when $default != null:
return $default(_that.heading,_that.paragraphs);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String heading,  List<DraftParagraph> paragraphs)  $default,) {final _that = this;
switch (_that) {
case _DraftSection():
return $default(_that.heading,_that.paragraphs);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String heading,  List<DraftParagraph> paragraphs)?  $default,) {final _that = this;
switch (_that) {
case _DraftSection() when $default != null:
return $default(_that.heading,_that.paragraphs);case _:
  return null;

}
}

}

/// @nodoc


class _DraftSection implements DraftSection {
  const _DraftSection({required this.heading, required  List<DraftParagraph> paragraphs}): _paragraphs = paragraphs;
  

@override final  String heading;
 final  List<DraftParagraph> _paragraphs;
@override List<DraftParagraph> get paragraphs {
  if (_paragraphs is EqualUnmodifiableListView) return _paragraphs;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_paragraphs);
}


/// Create a copy of DraftSection
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$DraftSectionCopyWith<_DraftSection> get copyWith => __$DraftSectionCopyWithImpl<_DraftSection>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _DraftSection&&(identical(other.heading, heading) || other.heading == heading)&&const DeepCollectionEquality().equals(other.paragraphs, _paragraphs));
}


@override
int get hashCode {
    return Object.hash(runtimeType,heading,const DeepCollectionEquality().hash(_paragraphs));
}

@override
String toString() {
    return 'DraftSection(heading: $heading, paragraphs: $paragraphs)';
}


}

/// @nodoc
abstract mixin class _$DraftSectionCopyWith<$Res> implements $DraftSectionCopyWith<$Res> {
  factory _$DraftSectionCopyWith(_DraftSection value, $Res Function(_DraftSection) _then) = __$DraftSectionCopyWithImpl;
@override @useResult
$Res call({
 String heading, List<DraftParagraph> paragraphs
});




}
/// @nodoc
class __$DraftSectionCopyWithImpl<$Res>
    implements _$DraftSectionCopyWith<$Res> {
  __$DraftSectionCopyWithImpl(this._self, this._then);

  final _DraftSection _self;
  final $Res Function(_DraftSection) _then;

/// Create a copy of DraftSection
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? heading = null,Object? paragraphs = null,}) {
  return _then(_DraftSection(
heading: null == heading ? _self.heading : heading // ignore: cast_nullable_to_non_nullable
as String,paragraphs: null == paragraphs ? _self._paragraphs : paragraphs // ignore: cast_nullable_to_non_nullable
as List<DraftParagraph>,
  ));
}


}

/// @nodoc
mixin _$CaseDraft {

 String get caseId; int get versionNo; bool get byLawyer; String get title; String get addressee; String get salutation; DraftParagraph get intro; List<DraftSection> get sections; String get closing; List<String> get attachments; String? get approvedBy; DateTime? get approvedAt;
/// Create a copy of CaseDraft
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CaseDraftCopyWith<CaseDraft> get copyWith => _$CaseDraftCopyWithImpl<CaseDraft>(this as CaseDraft, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as CaseDraft;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CaseDraft&&(identical(other.caseId, _this.caseId) || other.caseId == _this.caseId)&&(identical(other.versionNo, _this.versionNo) || other.versionNo == _this.versionNo)&&(identical(other.byLawyer, _this.byLawyer) || other.byLawyer == _this.byLawyer)&&(identical(other.title, _this.title) || other.title == _this.title)&&(identical(other.addressee, _this.addressee) || other.addressee == _this.addressee)&&(identical(other.salutation, _this.salutation) || other.salutation == _this.salutation)&&(identical(other.intro, _this.intro) || other.intro == _this.intro)&&const DeepCollectionEquality().equals(other.sections, _this.sections)&&(identical(other.closing, _this.closing) || other.closing == _this.closing)&&const DeepCollectionEquality().equals(other.attachments, _this.attachments)&&(identical(other.approvedBy, _this.approvedBy) || other.approvedBy == _this.approvedBy)&&(identical(other.approvedAt, _this.approvedAt) || other.approvedAt == _this.approvedAt));
}


@override
int get hashCode {
  final _this = this as CaseDraft;
  return Object.hash(runtimeType,_this.caseId,_this.versionNo,_this.byLawyer,_this.title,_this.addressee,_this.salutation,_this.intro,const DeepCollectionEquality().hash(_this.sections),_this.closing,const DeepCollectionEquality().hash(_this.attachments),_this.approvedBy,_this.approvedAt);
}

@override
String toString() {
  final _this = this as CaseDraft;
  return 'CaseDraft(caseId: ${_this.caseId}, versionNo: ${_this.versionNo}, byLawyer: ${_this.byLawyer}, title: ${_this.title}, addressee: ${_this.addressee}, salutation: ${_this.salutation}, intro: ${_this.intro}, sections: ${_this.sections}, closing: ${_this.closing}, attachments: ${_this.attachments}, approvedBy: ${_this.approvedBy}, approvedAt: ${_this.approvedAt})';
}


}

/// @nodoc
abstract mixin class $CaseDraftCopyWith<$Res>  {
  factory $CaseDraftCopyWith(CaseDraft value, $Res Function(CaseDraft) _then) = _$CaseDraftCopyWithImpl;
@useResult
$Res call({
 String caseId, int versionNo, bool byLawyer, String title, String addressee, String salutation, DraftParagraph intro, List<DraftSection> sections, String closing, List<String> attachments, String? approvedBy, DateTime? approvedAt
});


$DraftParagraphCopyWith<$Res> get intro;

}
/// @nodoc
class _$CaseDraftCopyWithImpl<$Res>
    implements $CaseDraftCopyWith<$Res> {
  _$CaseDraftCopyWithImpl(this._self, this._then);

  final CaseDraft _self;
  final $Res Function(CaseDraft) _then;

/// Create a copy of CaseDraft
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? caseId = null,Object? versionNo = null,Object? byLawyer = null,Object? title = null,Object? addressee = null,Object? salutation = null,Object? intro = null,Object? sections = null,Object? closing = null,Object? attachments = null,Object? approvedBy = freezed,Object? approvedAt = freezed,}) {
  return _then(CaseDraft(
caseId: null == caseId ? _self.caseId : caseId // ignore: cast_nullable_to_non_nullable
as String,versionNo: null == versionNo ? _self.versionNo : versionNo // ignore: cast_nullable_to_non_nullable
as int,byLawyer: null == byLawyer ? _self.byLawyer : byLawyer // ignore: cast_nullable_to_non_nullable
as bool,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,addressee: null == addressee ? _self.addressee : addressee // ignore: cast_nullable_to_non_nullable
as String,salutation: null == salutation ? _self.salutation : salutation // ignore: cast_nullable_to_non_nullable
as String,intro: null == intro ? _self.intro : intro // ignore: cast_nullable_to_non_nullable
as DraftParagraph,sections: null == sections ? _self.sections : sections // ignore: cast_nullable_to_non_nullable
as List<DraftSection>,closing: null == closing ? _self.closing : closing // ignore: cast_nullable_to_non_nullable
as String,attachments: null == attachments ? _self.attachments : attachments // ignore: cast_nullable_to_non_nullable
as List<String>,approvedBy: freezed == approvedBy ? _self.approvedBy : approvedBy // ignore: cast_nullable_to_non_nullable
as String?,approvedAt: freezed == approvedAt ? _self.approvedAt : approvedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}
/// Create a copy of CaseDraft
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$DraftParagraphCopyWith<$Res> get intro {
  
  return $DraftParagraphCopyWith<$Res>(_self.intro, (value) {
    return _then(_self.copyWith(intro: value));
  });
}
}


/// Adds pattern-matching-related methods to [CaseDraft].
extension CaseDraftPatterns on CaseDraft {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _CaseDraft value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _CaseDraft() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _CaseDraft value)  $default,){
final _that = this;
switch (_that) {
case _CaseDraft():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _CaseDraft value)?  $default,){
final _that = this;
switch (_that) {
case _CaseDraft() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String caseId,  int versionNo,  bool byLawyer,  String title,  String addressee,  String salutation,  DraftParagraph intro,  List<DraftSection> sections,  String closing,  List<String> attachments,  String? approvedBy,  DateTime? approvedAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CaseDraft() when $default != null:
return $default(_that.caseId,_that.versionNo,_that.byLawyer,_that.title,_that.addressee,_that.salutation,_that.intro,_that.sections,_that.closing,_that.attachments,_that.approvedBy,_that.approvedAt);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String caseId,  int versionNo,  bool byLawyer,  String title,  String addressee,  String salutation,  DraftParagraph intro,  List<DraftSection> sections,  String closing,  List<String> attachments,  String? approvedBy,  DateTime? approvedAt)  $default,) {final _that = this;
switch (_that) {
case _CaseDraft():
return $default(_that.caseId,_that.versionNo,_that.byLawyer,_that.title,_that.addressee,_that.salutation,_that.intro,_that.sections,_that.closing,_that.attachments,_that.approvedBy,_that.approvedAt);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String caseId,  int versionNo,  bool byLawyer,  String title,  String addressee,  String salutation,  DraftParagraph intro,  List<DraftSection> sections,  String closing,  List<String> attachments,  String? approvedBy,  DateTime? approvedAt)?  $default,) {final _that = this;
switch (_that) {
case _CaseDraft() when $default != null:
return $default(_that.caseId,_that.versionNo,_that.byLawyer,_that.title,_that.addressee,_that.salutation,_that.intro,_that.sections,_that.closing,_that.attachments,_that.approvedBy,_that.approvedAt);case _:
  return null;

}
}

}

/// @nodoc


class _CaseDraft implements CaseDraft {
  const _CaseDraft({required this.caseId, required this.versionNo, required this.byLawyer, required this.title, required this.addressee, required this.salutation, required this.intro, required  List<DraftSection> sections, required this.closing, required  List<String> attachments, this.approvedBy, this.approvedAt}): _sections = sections,_attachments = attachments;
  

@override final  String caseId;
@override final  int versionNo;
@override final  bool byLawyer;
@override final  String title;
@override final  String addressee;
@override final  String salutation;
@override final  DraftParagraph intro;
 final  List<DraftSection> _sections;
@override List<DraftSection> get sections {
  if (_sections is EqualUnmodifiableListView) return _sections;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_sections);
}

@override final  String closing;
 final  List<String> _attachments;
@override List<String> get attachments {
  if (_attachments is EqualUnmodifiableListView) return _attachments;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_attachments);
}

@override final  String? approvedBy;
@override final  DateTime? approvedAt;

/// Create a copy of CaseDraft
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CaseDraftCopyWith<_CaseDraft> get copyWith => __$CaseDraftCopyWithImpl<_CaseDraft>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _CaseDraft&&(identical(other.caseId, caseId) || other.caseId == caseId)&&(identical(other.versionNo, versionNo) || other.versionNo == versionNo)&&(identical(other.byLawyer, byLawyer) || other.byLawyer == byLawyer)&&(identical(other.title, title) || other.title == title)&&(identical(other.addressee, addressee) || other.addressee == addressee)&&(identical(other.salutation, salutation) || other.salutation == salutation)&&(identical(other.intro, intro) || other.intro == intro)&&const DeepCollectionEquality().equals(other.sections, _sections)&&(identical(other.closing, closing) || other.closing == closing)&&const DeepCollectionEquality().equals(other.attachments, _attachments)&&(identical(other.approvedBy, approvedBy) || other.approvedBy == approvedBy)&&(identical(other.approvedAt, approvedAt) || other.approvedAt == approvedAt));
}


@override
int get hashCode {
    return Object.hash(runtimeType,caseId,versionNo,byLawyer,title,addressee,salutation,intro,const DeepCollectionEquality().hash(_sections),closing,const DeepCollectionEquality().hash(_attachments),approvedBy,approvedAt);
}

@override
String toString() {
    return 'CaseDraft(caseId: $caseId, versionNo: $versionNo, byLawyer: $byLawyer, title: $title, addressee: $addressee, salutation: $salutation, intro: $intro, sections: $sections, closing: $closing, attachments: $attachments, approvedBy: $approvedBy, approvedAt: $approvedAt)';
}


}

/// @nodoc
abstract mixin class _$CaseDraftCopyWith<$Res> implements $CaseDraftCopyWith<$Res> {
  factory _$CaseDraftCopyWith(_CaseDraft value, $Res Function(_CaseDraft) _then) = __$CaseDraftCopyWithImpl;
@override @useResult
$Res call({
 String caseId, int versionNo, bool byLawyer, String title, String addressee, String salutation, DraftParagraph intro, List<DraftSection> sections, String closing, List<String> attachments, String? approvedBy, DateTime? approvedAt
});


@override $DraftParagraphCopyWith<$Res> get intro;

}
/// @nodoc
class __$CaseDraftCopyWithImpl<$Res>
    implements _$CaseDraftCopyWith<$Res> {
  __$CaseDraftCopyWithImpl(this._self, this._then);

  final _CaseDraft _self;
  final $Res Function(_CaseDraft) _then;

/// Create a copy of CaseDraft
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? caseId = null,Object? versionNo = null,Object? byLawyer = null,Object? title = null,Object? addressee = null,Object? salutation = null,Object? intro = null,Object? sections = null,Object? closing = null,Object? attachments = null,Object? approvedBy = freezed,Object? approvedAt = freezed,}) {
  return _then(_CaseDraft(
caseId: null == caseId ? _self.caseId : caseId // ignore: cast_nullable_to_non_nullable
as String,versionNo: null == versionNo ? _self.versionNo : versionNo // ignore: cast_nullable_to_non_nullable
as int,byLawyer: null == byLawyer ? _self.byLawyer : byLawyer // ignore: cast_nullable_to_non_nullable
as bool,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,addressee: null == addressee ? _self.addressee : addressee // ignore: cast_nullable_to_non_nullable
as String,salutation: null == salutation ? _self.salutation : salutation // ignore: cast_nullable_to_non_nullable
as String,intro: null == intro ? _self.intro : intro // ignore: cast_nullable_to_non_nullable
as DraftParagraph,sections: null == sections ? _self._sections : sections // ignore: cast_nullable_to_non_nullable
as List<DraftSection>,closing: null == closing ? _self.closing : closing // ignore: cast_nullable_to_non_nullable
as String,attachments: null == attachments ? _self._attachments : attachments // ignore: cast_nullable_to_non_nullable
as List<String>,approvedBy: freezed == approvedBy ? _self.approvedBy : approvedBy // ignore: cast_nullable_to_non_nullable
as String?,approvedAt: freezed == approvedAt ? _self.approvedAt : approvedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}

/// Create a copy of CaseDraft
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$DraftParagraphCopyWith<$Res> get intro {
  
  return $DraftParagraphCopyWith<$Res>(_self.intro, (value) {
    return _then(_self.copyWith(intro: value));
  });
}
}

/// @nodoc
mixin _$PipelineProgress {

 Map<PipelineStep, PipelineStepState> get steps; bool get finished; String? get error;
/// Create a copy of PipelineProgress
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$PipelineProgressCopyWith<PipelineProgress> get copyWith => _$PipelineProgressCopyWithImpl<PipelineProgress>(this as PipelineProgress, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as PipelineProgress;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is PipelineProgress&&const DeepCollectionEquality().equals(other.steps, _this.steps)&&(identical(other.finished, _this.finished) || other.finished == _this.finished)&&(identical(other.error, _this.error) || other.error == _this.error));
}


@override
int get hashCode {
  final _this = this as PipelineProgress;
  return Object.hash(runtimeType,const DeepCollectionEquality().hash(_this.steps),_this.finished,_this.error);
}

@override
String toString() {
  final _this = this as PipelineProgress;
  return 'PipelineProgress(steps: ${_this.steps}, finished: ${_this.finished}, error: ${_this.error})';
}


}

/// @nodoc
abstract mixin class $PipelineProgressCopyWith<$Res>  {
  factory $PipelineProgressCopyWith(PipelineProgress value, $Res Function(PipelineProgress) _then) = _$PipelineProgressCopyWithImpl;
@useResult
$Res call({
 Map<PipelineStep, PipelineStepState> steps, bool finished, String? error
});




}
/// @nodoc
class _$PipelineProgressCopyWithImpl<$Res>
    implements $PipelineProgressCopyWith<$Res> {
  _$PipelineProgressCopyWithImpl(this._self, this._then);

  final PipelineProgress _self;
  final $Res Function(PipelineProgress) _then;

/// Create a copy of PipelineProgress
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? steps = null,Object? finished = null,Object? error = freezed,}) {
  return _then(PipelineProgress(
steps: null == steps ? _self.steps : steps // ignore: cast_nullable_to_non_nullable
as Map<PipelineStep, PipelineStepState>,finished: null == finished ? _self.finished : finished // ignore: cast_nullable_to_non_nullable
as bool,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [PipelineProgress].
extension PipelineProgressPatterns on PipelineProgress {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _PipelineProgress value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _PipelineProgress() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _PipelineProgress value)  $default,){
final _that = this;
switch (_that) {
case _PipelineProgress():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _PipelineProgress value)?  $default,){
final _that = this;
switch (_that) {
case _PipelineProgress() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( Map<PipelineStep, PipelineStepState> steps,  bool finished,  String? error)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _PipelineProgress() when $default != null:
return $default(_that.steps,_that.finished,_that.error);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( Map<PipelineStep, PipelineStepState> steps,  bool finished,  String? error)  $default,) {final _that = this;
switch (_that) {
case _PipelineProgress():
return $default(_that.steps,_that.finished,_that.error);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( Map<PipelineStep, PipelineStepState> steps,  bool finished,  String? error)?  $default,) {final _that = this;
switch (_that) {
case _PipelineProgress() when $default != null:
return $default(_that.steps,_that.finished,_that.error);case _:
  return null;

}
}

}

/// @nodoc


class _PipelineProgress extends PipelineProgress {
  const _PipelineProgress({required  Map<PipelineStep, PipelineStepState> steps, this.finished = false, this.error}): _steps = steps,super._();
  

 final  Map<PipelineStep, PipelineStepState> _steps;
@override Map<PipelineStep, PipelineStepState> get steps {
  if (_steps is EqualUnmodifiableMapView) return _steps;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableMapView(_steps);
}

@override@JsonKey() final  bool finished;
@override final  String? error;

/// Create a copy of PipelineProgress
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$PipelineProgressCopyWith<_PipelineProgress> get copyWith => __$PipelineProgressCopyWithImpl<_PipelineProgress>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _PipelineProgress&&const DeepCollectionEquality().equals(other.steps, _steps)&&(identical(other.finished, finished) || other.finished == finished)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode {
    return Object.hash(runtimeType,const DeepCollectionEquality().hash(_steps),finished,error);
}

@override
String toString() {
    return 'PipelineProgress(steps: $steps, finished: $finished, error: $error)';
}


}

/// @nodoc
abstract mixin class _$PipelineProgressCopyWith<$Res> implements $PipelineProgressCopyWith<$Res> {
  factory _$PipelineProgressCopyWith(_PipelineProgress value, $Res Function(_PipelineProgress) _then) = __$PipelineProgressCopyWithImpl;
@override @useResult
$Res call({
 Map<PipelineStep, PipelineStepState> steps, bool finished, String? error
});




}
/// @nodoc
class __$PipelineProgressCopyWithImpl<$Res>
    implements _$PipelineProgressCopyWith<$Res> {
  __$PipelineProgressCopyWithImpl(this._self, this._then);

  final _PipelineProgress _self;
  final $Res Function(_PipelineProgress) _then;

/// Create a copy of PipelineProgress
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? steps = null,Object? finished = null,Object? error = freezed,}) {
  return _then(_PipelineProgress(
steps: null == steps ? _self._steps : steps // ignore: cast_nullable_to_non_nullable
as Map<PipelineStep, PipelineStepState>,finished: null == finished ? _self.finished : finished // ignore: cast_nullable_to_non_nullable
as bool,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}

/// @nodoc
mixin _$NewCaseInput {

 CaseType get caseType; String get authority; String get complaintNo; DateTime get receivedOn; String get title; String get procedure; DateTime get procedureDate; String get patientCode; ClaimType get claimType; String get facts; List<CaseDocument> get documents;
/// Create a copy of NewCaseInput
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$NewCaseInputCopyWith<NewCaseInput> get copyWith => _$NewCaseInputCopyWithImpl<NewCaseInput>(this as NewCaseInput, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as NewCaseInput;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is NewCaseInput&&(identical(other.caseType, _this.caseType) || other.caseType == _this.caseType)&&(identical(other.authority, _this.authority) || other.authority == _this.authority)&&(identical(other.complaintNo, _this.complaintNo) || other.complaintNo == _this.complaintNo)&&(identical(other.receivedOn, _this.receivedOn) || other.receivedOn == _this.receivedOn)&&(identical(other.title, _this.title) || other.title == _this.title)&&(identical(other.procedure, _this.procedure) || other.procedure == _this.procedure)&&(identical(other.procedureDate, _this.procedureDate) || other.procedureDate == _this.procedureDate)&&(identical(other.patientCode, _this.patientCode) || other.patientCode == _this.patientCode)&&(identical(other.claimType, _this.claimType) || other.claimType == _this.claimType)&&(identical(other.facts, _this.facts) || other.facts == _this.facts)&&const DeepCollectionEquality().equals(other.documents, _this.documents));
}


@override
int get hashCode {
  final _this = this as NewCaseInput;
  return Object.hash(runtimeType,_this.caseType,_this.authority,_this.complaintNo,_this.receivedOn,_this.title,_this.procedure,_this.procedureDate,_this.patientCode,_this.claimType,_this.facts,const DeepCollectionEquality().hash(_this.documents));
}

@override
String toString() {
  final _this = this as NewCaseInput;
  return 'NewCaseInput(caseType: ${_this.caseType}, authority: ${_this.authority}, complaintNo: ${_this.complaintNo}, receivedOn: ${_this.receivedOn}, title: ${_this.title}, procedure: ${_this.procedure}, procedureDate: ${_this.procedureDate}, patientCode: ${_this.patientCode}, claimType: ${_this.claimType}, facts: ${_this.facts}, documents: ${_this.documents})';
}


}

/// @nodoc
abstract mixin class $NewCaseInputCopyWith<$Res>  {
  factory $NewCaseInputCopyWith(NewCaseInput value, $Res Function(NewCaseInput) _then) = _$NewCaseInputCopyWithImpl;
@useResult
$Res call({
 CaseType caseType, String authority, String complaintNo, DateTime receivedOn, String title, String procedure, DateTime procedureDate, String patientCode, ClaimType claimType, String facts, List<CaseDocument> documents
});




}
/// @nodoc
class _$NewCaseInputCopyWithImpl<$Res>
    implements $NewCaseInputCopyWith<$Res> {
  _$NewCaseInputCopyWithImpl(this._self, this._then);

  final NewCaseInput _self;
  final $Res Function(NewCaseInput) _then;

/// Create a copy of NewCaseInput
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? caseType = null,Object? authority = null,Object? complaintNo = null,Object? receivedOn = null,Object? title = null,Object? procedure = null,Object? procedureDate = null,Object? patientCode = null,Object? claimType = null,Object? facts = null,Object? documents = null,}) {
  return _then(NewCaseInput(
caseType: null == caseType ? _self.caseType : caseType // ignore: cast_nullable_to_non_nullable
as CaseType,authority: null == authority ? _self.authority : authority // ignore: cast_nullable_to_non_nullable
as String,complaintNo: null == complaintNo ? _self.complaintNo : complaintNo // ignore: cast_nullable_to_non_nullable
as String,receivedOn: null == receivedOn ? _self.receivedOn : receivedOn // ignore: cast_nullable_to_non_nullable
as DateTime,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,procedure: null == procedure ? _self.procedure : procedure // ignore: cast_nullable_to_non_nullable
as String,procedureDate: null == procedureDate ? _self.procedureDate : procedureDate // ignore: cast_nullable_to_non_nullable
as DateTime,patientCode: null == patientCode ? _self.patientCode : patientCode // ignore: cast_nullable_to_non_nullable
as String,claimType: null == claimType ? _self.claimType : claimType // ignore: cast_nullable_to_non_nullable
as ClaimType,facts: null == facts ? _self.facts : facts // ignore: cast_nullable_to_non_nullable
as String,documents: null == documents ? _self.documents : documents // ignore: cast_nullable_to_non_nullable
as List<CaseDocument>,
  ));
}

}


/// Adds pattern-matching-related methods to [NewCaseInput].
extension NewCaseInputPatterns on NewCaseInput {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _NewCaseInput value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _NewCaseInput() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _NewCaseInput value)  $default,){
final _that = this;
switch (_that) {
case _NewCaseInput():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _NewCaseInput value)?  $default,){
final _that = this;
switch (_that) {
case _NewCaseInput() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( CaseType caseType,  String authority,  String complaintNo,  DateTime receivedOn,  String title,  String procedure,  DateTime procedureDate,  String patientCode,  ClaimType claimType,  String facts,  List<CaseDocument> documents)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _NewCaseInput() when $default != null:
return $default(_that.caseType,_that.authority,_that.complaintNo,_that.receivedOn,_that.title,_that.procedure,_that.procedureDate,_that.patientCode,_that.claimType,_that.facts,_that.documents);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( CaseType caseType,  String authority,  String complaintNo,  DateTime receivedOn,  String title,  String procedure,  DateTime procedureDate,  String patientCode,  ClaimType claimType,  String facts,  List<CaseDocument> documents)  $default,) {final _that = this;
switch (_that) {
case _NewCaseInput():
return $default(_that.caseType,_that.authority,_that.complaintNo,_that.receivedOn,_that.title,_that.procedure,_that.procedureDate,_that.patientCode,_that.claimType,_that.facts,_that.documents);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( CaseType caseType,  String authority,  String complaintNo,  DateTime receivedOn,  String title,  String procedure,  DateTime procedureDate,  String patientCode,  ClaimType claimType,  String facts,  List<CaseDocument> documents)?  $default,) {final _that = this;
switch (_that) {
case _NewCaseInput() when $default != null:
return $default(_that.caseType,_that.authority,_that.complaintNo,_that.receivedOn,_that.title,_that.procedure,_that.procedureDate,_that.patientCode,_that.claimType,_that.facts,_that.documents);case _:
  return null;

}
}

}

/// @nodoc


class _NewCaseInput implements NewCaseInput {
  const _NewCaseInput({required this.caseType, required this.authority, required this.complaintNo, required this.receivedOn, required this.title, required this.procedure, required this.procedureDate, required this.patientCode, required this.claimType, required this.facts, required  List<CaseDocument> documents}): _documents = documents;
  

@override final  CaseType caseType;
@override final  String authority;
@override final  String complaintNo;
@override final  DateTime receivedOn;
@override final  String title;
@override final  String procedure;
@override final  DateTime procedureDate;
@override final  String patientCode;
@override final  ClaimType claimType;
@override final  String facts;
 final  List<CaseDocument> _documents;
@override List<CaseDocument> get documents {
  if (_documents is EqualUnmodifiableListView) return _documents;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_documents);
}


/// Create a copy of NewCaseInput
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$NewCaseInputCopyWith<_NewCaseInput> get copyWith => __$NewCaseInputCopyWithImpl<_NewCaseInput>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _NewCaseInput&&(identical(other.caseType, caseType) || other.caseType == caseType)&&(identical(other.authority, authority) || other.authority == authority)&&(identical(other.complaintNo, complaintNo) || other.complaintNo == complaintNo)&&(identical(other.receivedOn, receivedOn) || other.receivedOn == receivedOn)&&(identical(other.title, title) || other.title == title)&&(identical(other.procedure, procedure) || other.procedure == procedure)&&(identical(other.procedureDate, procedureDate) || other.procedureDate == procedureDate)&&(identical(other.patientCode, patientCode) || other.patientCode == patientCode)&&(identical(other.claimType, claimType) || other.claimType == claimType)&&(identical(other.facts, facts) || other.facts == facts)&&const DeepCollectionEquality().equals(other.documents, _documents));
}


@override
int get hashCode {
    return Object.hash(runtimeType,caseType,authority,complaintNo,receivedOn,title,procedure,procedureDate,patientCode,claimType,facts,const DeepCollectionEquality().hash(_documents));
}

@override
String toString() {
    return 'NewCaseInput(caseType: $caseType, authority: $authority, complaintNo: $complaintNo, receivedOn: $receivedOn, title: $title, procedure: $procedure, procedureDate: $procedureDate, patientCode: $patientCode, claimType: $claimType, facts: $facts, documents: $documents)';
}


}

/// @nodoc
abstract mixin class _$NewCaseInputCopyWith<$Res> implements $NewCaseInputCopyWith<$Res> {
  factory _$NewCaseInputCopyWith(_NewCaseInput value, $Res Function(_NewCaseInput) _then) = __$NewCaseInputCopyWithImpl;
@override @useResult
$Res call({
 CaseType caseType, String authority, String complaintNo, DateTime receivedOn, String title, String procedure, DateTime procedureDate, String patientCode, ClaimType claimType, String facts, List<CaseDocument> documents
});




}
/// @nodoc
class __$NewCaseInputCopyWithImpl<$Res>
    implements _$NewCaseInputCopyWith<$Res> {
  __$NewCaseInputCopyWithImpl(this._self, this._then);

  final _NewCaseInput _self;
  final $Res Function(_NewCaseInput) _then;

/// Create a copy of NewCaseInput
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? caseType = null,Object? authority = null,Object? complaintNo = null,Object? receivedOn = null,Object? title = null,Object? procedure = null,Object? procedureDate = null,Object? patientCode = null,Object? claimType = null,Object? facts = null,Object? documents = null,}) {
  return _then(_NewCaseInput(
caseType: null == caseType ? _self.caseType : caseType // ignore: cast_nullable_to_non_nullable
as CaseType,authority: null == authority ? _self.authority : authority // ignore: cast_nullable_to_non_nullable
as String,complaintNo: null == complaintNo ? _self.complaintNo : complaintNo // ignore: cast_nullable_to_non_nullable
as String,receivedOn: null == receivedOn ? _self.receivedOn : receivedOn // ignore: cast_nullable_to_non_nullable
as DateTime,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,procedure: null == procedure ? _self.procedure : procedure // ignore: cast_nullable_to_non_nullable
as String,procedureDate: null == procedureDate ? _self.procedureDate : procedureDate // ignore: cast_nullable_to_non_nullable
as DateTime,patientCode: null == patientCode ? _self.patientCode : patientCode // ignore: cast_nullable_to_non_nullable
as String,claimType: null == claimType ? _self.claimType : claimType // ignore: cast_nullable_to_non_nullable
as ClaimType,facts: null == facts ? _self.facts : facts // ignore: cast_nullable_to_non_nullable
as String,documents: null == documents ? _self._documents : documents // ignore: cast_nullable_to_non_nullable
as List<CaseDocument>,
  ));
}


}

// dart format on
