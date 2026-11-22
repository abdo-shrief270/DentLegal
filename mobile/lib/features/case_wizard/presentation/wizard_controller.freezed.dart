// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint, type=warning, deprecated_member_use, deprecated_member_use_from_same_package
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'wizard_controller.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$WizardState {

 int get step; CaseType get caseType; String get authority; String get complaintNo; DateTime? get receivedOn; String get title; String get procedure; DateTime? get procedureDate; String get patientCode; ClaimType get claimType; String get facts; List<CaseDocument> get documents; bool get picking; bool get confirmAccuracy; bool get confirmProcessing; bool get submitting;
/// Create a copy of WizardState
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$WizardStateCopyWith<WizardState> get copyWith => _$WizardStateCopyWithImpl<WizardState>(this as WizardState, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as WizardState;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is WizardState&&(identical(other.step, _this.step) || other.step == _this.step)&&(identical(other.caseType, _this.caseType) || other.caseType == _this.caseType)&&(identical(other.authority, _this.authority) || other.authority == _this.authority)&&(identical(other.complaintNo, _this.complaintNo) || other.complaintNo == _this.complaintNo)&&(identical(other.receivedOn, _this.receivedOn) || other.receivedOn == _this.receivedOn)&&(identical(other.title, _this.title) || other.title == _this.title)&&(identical(other.procedure, _this.procedure) || other.procedure == _this.procedure)&&(identical(other.procedureDate, _this.procedureDate) || other.procedureDate == _this.procedureDate)&&(identical(other.patientCode, _this.patientCode) || other.patientCode == _this.patientCode)&&(identical(other.claimType, _this.claimType) || other.claimType == _this.claimType)&&(identical(other.facts, _this.facts) || other.facts == _this.facts)&&const DeepCollectionEquality().equals(other.documents, _this.documents)&&(identical(other.picking, _this.picking) || other.picking == _this.picking)&&(identical(other.confirmAccuracy, _this.confirmAccuracy) || other.confirmAccuracy == _this.confirmAccuracy)&&(identical(other.confirmProcessing, _this.confirmProcessing) || other.confirmProcessing == _this.confirmProcessing)&&(identical(other.submitting, _this.submitting) || other.submitting == _this.submitting));
}


@override
int get hashCode {
  final _this = this as WizardState;
  return Object.hash(runtimeType,_this.step,_this.caseType,_this.authority,_this.complaintNo,_this.receivedOn,_this.title,_this.procedure,_this.procedureDate,_this.patientCode,_this.claimType,_this.facts,const DeepCollectionEquality().hash(_this.documents),_this.picking,_this.confirmAccuracy,_this.confirmProcessing,_this.submitting);
}

@override
String toString() {
  final _this = this as WizardState;
  return 'WizardState(step: ${_this.step}, caseType: ${_this.caseType}, authority: ${_this.authority}, complaintNo: ${_this.complaintNo}, receivedOn: ${_this.receivedOn}, title: ${_this.title}, procedure: ${_this.procedure}, procedureDate: ${_this.procedureDate}, patientCode: ${_this.patientCode}, claimType: ${_this.claimType}, facts: ${_this.facts}, documents: ${_this.documents}, picking: ${_this.picking}, confirmAccuracy: ${_this.confirmAccuracy}, confirmProcessing: ${_this.confirmProcessing}, submitting: ${_this.submitting})';
}


}

/// @nodoc
abstract mixin class $WizardStateCopyWith<$Res>  {
  factory $WizardStateCopyWith(WizardState value, $Res Function(WizardState) _then) = _$WizardStateCopyWithImpl;
@useResult
$Res call({
 int step, CaseType caseType, String authority, String complaintNo, DateTime? receivedOn, String title, String procedure, DateTime? procedureDate, String patientCode, ClaimType claimType, String facts, List<CaseDocument> documents, bool picking, bool confirmAccuracy, bool confirmProcessing, bool submitting
});




}
/// @nodoc
class _$WizardStateCopyWithImpl<$Res>
    implements $WizardStateCopyWith<$Res> {
  _$WizardStateCopyWithImpl(this._self, this._then);

  final WizardState _self;
  final $Res Function(WizardState) _then;

/// Create a copy of WizardState
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? step = null,Object? caseType = null,Object? authority = null,Object? complaintNo = null,Object? receivedOn = freezed,Object? title = null,Object? procedure = null,Object? procedureDate = freezed,Object? patientCode = null,Object? claimType = null,Object? facts = null,Object? documents = null,Object? picking = null,Object? confirmAccuracy = null,Object? confirmProcessing = null,Object? submitting = null,}) {
  return _then(WizardState(
step: null == step ? _self.step : step // ignore: cast_nullable_to_non_nullable
as int,caseType: null == caseType ? _self.caseType : caseType // ignore: cast_nullable_to_non_nullable
as CaseType,authority: null == authority ? _self.authority : authority // ignore: cast_nullable_to_non_nullable
as String,complaintNo: null == complaintNo ? _self.complaintNo : complaintNo // ignore: cast_nullable_to_non_nullable
as String,receivedOn: freezed == receivedOn ? _self.receivedOn : receivedOn // ignore: cast_nullable_to_non_nullable
as DateTime?,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,procedure: null == procedure ? _self.procedure : procedure // ignore: cast_nullable_to_non_nullable
as String,procedureDate: freezed == procedureDate ? _self.procedureDate : procedureDate // ignore: cast_nullable_to_non_nullable
as DateTime?,patientCode: null == patientCode ? _self.patientCode : patientCode // ignore: cast_nullable_to_non_nullable
as String,claimType: null == claimType ? _self.claimType : claimType // ignore: cast_nullable_to_non_nullable
as ClaimType,facts: null == facts ? _self.facts : facts // ignore: cast_nullable_to_non_nullable
as String,documents: null == documents ? _self.documents : documents // ignore: cast_nullable_to_non_nullable
as List<CaseDocument>,picking: null == picking ? _self.picking : picking // ignore: cast_nullable_to_non_nullable
as bool,confirmAccuracy: null == confirmAccuracy ? _self.confirmAccuracy : confirmAccuracy // ignore: cast_nullable_to_non_nullable
as bool,confirmProcessing: null == confirmProcessing ? _self.confirmProcessing : confirmProcessing // ignore: cast_nullable_to_non_nullable
as bool,submitting: null == submitting ? _self.submitting : submitting // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [WizardState].
extension WizardStatePatterns on WizardState {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _WizardState value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _WizardState() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _WizardState value)  $default,){
final _that = this;
switch (_that) {
case _WizardState():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _WizardState value)?  $default,){
final _that = this;
switch (_that) {
case _WizardState() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int step,  CaseType caseType,  String authority,  String complaintNo,  DateTime? receivedOn,  String title,  String procedure,  DateTime? procedureDate,  String patientCode,  ClaimType claimType,  String facts,  List<CaseDocument> documents,  bool picking,  bool confirmAccuracy,  bool confirmProcessing,  bool submitting)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _WizardState() when $default != null:
return $default(_that.step,_that.caseType,_that.authority,_that.complaintNo,_that.receivedOn,_that.title,_that.procedure,_that.procedureDate,_that.patientCode,_that.claimType,_that.facts,_that.documents,_that.picking,_that.confirmAccuracy,_that.confirmProcessing,_that.submitting);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int step,  CaseType caseType,  String authority,  String complaintNo,  DateTime? receivedOn,  String title,  String procedure,  DateTime? procedureDate,  String patientCode,  ClaimType claimType,  String facts,  List<CaseDocument> documents,  bool picking,  bool confirmAccuracy,  bool confirmProcessing,  bool submitting)  $default,) {final _that = this;
switch (_that) {
case _WizardState():
return $default(_that.step,_that.caseType,_that.authority,_that.complaintNo,_that.receivedOn,_that.title,_that.procedure,_that.procedureDate,_that.patientCode,_that.claimType,_that.facts,_that.documents,_that.picking,_that.confirmAccuracy,_that.confirmProcessing,_that.submitting);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int step,  CaseType caseType,  String authority,  String complaintNo,  DateTime? receivedOn,  String title,  String procedure,  DateTime? procedureDate,  String patientCode,  ClaimType claimType,  String facts,  List<CaseDocument> documents,  bool picking,  bool confirmAccuracy,  bool confirmProcessing,  bool submitting)?  $default,) {final _that = this;
switch (_that) {
case _WizardState() when $default != null:
return $default(_that.step,_that.caseType,_that.authority,_that.complaintNo,_that.receivedOn,_that.title,_that.procedure,_that.procedureDate,_that.patientCode,_that.claimType,_that.facts,_that.documents,_that.picking,_that.confirmAccuracy,_that.confirmProcessing,_that.submitting);case _:
  return null;

}
}

}

/// @nodoc


class _WizardState extends WizardState {
  const _WizardState({this.step = 1, this.caseType = CaseType.complaint, this.authority = 'المديرية العامة للشؤون الصحية بمحافظة جدة', this.complaintNo = '', this.receivedOn, this.title = '', this.procedure = '', this.procedureDate, this.patientCode = '', this.claimType = ClaimType.compensation, this.facts = '',  List<CaseDocument> documents = const [], this.picking = false, this.confirmAccuracy = false, this.confirmProcessing = false, this.submitting = false}): _documents = documents,super._();
  

@override@JsonKey() final  int step;
@override@JsonKey() final  CaseType caseType;
@override@JsonKey() final  String authority;
@override@JsonKey() final  String complaintNo;
@override final  DateTime? receivedOn;
@override@JsonKey() final  String title;
@override@JsonKey() final  String procedure;
@override final  DateTime? procedureDate;
@override@JsonKey() final  String patientCode;
@override@JsonKey() final  ClaimType claimType;
@override@JsonKey() final  String facts;
 final  List<CaseDocument> _documents;
@override@JsonKey() List<CaseDocument> get documents {
  if (_documents is EqualUnmodifiableListView) return _documents;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_documents);
}

@override@JsonKey() final  bool picking;
@override@JsonKey() final  bool confirmAccuracy;
@override@JsonKey() final  bool confirmProcessing;
@override@JsonKey() final  bool submitting;

/// Create a copy of WizardState
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$WizardStateCopyWith<_WizardState> get copyWith => __$WizardStateCopyWithImpl<_WizardState>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _WizardState&&(identical(other.step, step) || other.step == step)&&(identical(other.caseType, caseType) || other.caseType == caseType)&&(identical(other.authority, authority) || other.authority == authority)&&(identical(other.complaintNo, complaintNo) || other.complaintNo == complaintNo)&&(identical(other.receivedOn, receivedOn) || other.receivedOn == receivedOn)&&(identical(other.title, title) || other.title == title)&&(identical(other.procedure, procedure) || other.procedure == procedure)&&(identical(other.procedureDate, procedureDate) || other.procedureDate == procedureDate)&&(identical(other.patientCode, patientCode) || other.patientCode == patientCode)&&(identical(other.claimType, claimType) || other.claimType == claimType)&&(identical(other.facts, facts) || other.facts == facts)&&const DeepCollectionEquality().equals(other.documents, _documents)&&(identical(other.picking, picking) || other.picking == picking)&&(identical(other.confirmAccuracy, confirmAccuracy) || other.confirmAccuracy == confirmAccuracy)&&(identical(other.confirmProcessing, confirmProcessing) || other.confirmProcessing == confirmProcessing)&&(identical(other.submitting, submitting) || other.submitting == submitting));
}


@override
int get hashCode {
    return Object.hash(runtimeType,step,caseType,authority,complaintNo,receivedOn,title,procedure,procedureDate,patientCode,claimType,facts,const DeepCollectionEquality().hash(_documents),picking,confirmAccuracy,confirmProcessing,submitting);
}

@override
String toString() {
    return 'WizardState(step: $step, caseType: $caseType, authority: $authority, complaintNo: $complaintNo, receivedOn: $receivedOn, title: $title, procedure: $procedure, procedureDate: $procedureDate, patientCode: $patientCode, claimType: $claimType, facts: $facts, documents: $documents, picking: $picking, confirmAccuracy: $confirmAccuracy, confirmProcessing: $confirmProcessing, submitting: $submitting)';
}


}

/// @nodoc
abstract mixin class _$WizardStateCopyWith<$Res> implements $WizardStateCopyWith<$Res> {
  factory _$WizardStateCopyWith(_WizardState value, $Res Function(_WizardState) _then) = __$WizardStateCopyWithImpl;
@override @useResult
$Res call({
 int step, CaseType caseType, String authority, String complaintNo, DateTime? receivedOn, String title, String procedure, DateTime? procedureDate, String patientCode, ClaimType claimType, String facts, List<CaseDocument> documents, bool picking, bool confirmAccuracy, bool confirmProcessing, bool submitting
});




}
/// @nodoc
class __$WizardStateCopyWithImpl<$Res>
    implements _$WizardStateCopyWith<$Res> {
  __$WizardStateCopyWithImpl(this._self, this._then);

  final _WizardState _self;
  final $Res Function(_WizardState) _then;

/// Create a copy of WizardState
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? step = null,Object? caseType = null,Object? authority = null,Object? complaintNo = null,Object? receivedOn = freezed,Object? title = null,Object? procedure = null,Object? procedureDate = freezed,Object? patientCode = null,Object? claimType = null,Object? facts = null,Object? documents = null,Object? picking = null,Object? confirmAccuracy = null,Object? confirmProcessing = null,Object? submitting = null,}) {
  return _then(_WizardState(
step: null == step ? _self.step : step // ignore: cast_nullable_to_non_nullable
as int,caseType: null == caseType ? _self.caseType : caseType // ignore: cast_nullable_to_non_nullable
as CaseType,authority: null == authority ? _self.authority : authority // ignore: cast_nullable_to_non_nullable
as String,complaintNo: null == complaintNo ? _self.complaintNo : complaintNo // ignore: cast_nullable_to_non_nullable
as String,receivedOn: freezed == receivedOn ? _self.receivedOn : receivedOn // ignore: cast_nullable_to_non_nullable
as DateTime?,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,procedure: null == procedure ? _self.procedure : procedure // ignore: cast_nullable_to_non_nullable
as String,procedureDate: freezed == procedureDate ? _self.procedureDate : procedureDate // ignore: cast_nullable_to_non_nullable
as DateTime?,patientCode: null == patientCode ? _self.patientCode : patientCode // ignore: cast_nullable_to_non_nullable
as String,claimType: null == claimType ? _self.claimType : claimType // ignore: cast_nullable_to_non_nullable
as ClaimType,facts: null == facts ? _self.facts : facts // ignore: cast_nullable_to_non_nullable
as String,documents: null == documents ? _self._documents : documents // ignore: cast_nullable_to_non_nullable
as List<CaseDocument>,picking: null == picking ? _self.picking : picking // ignore: cast_nullable_to_non_nullable
as bool,confirmAccuracy: null == confirmAccuracy ? _self.confirmAccuracy : confirmAccuracy // ignore: cast_nullable_to_non_nullable
as bool,confirmProcessing: null == confirmProcessing ? _self.confirmProcessing : confirmProcessing // ignore: cast_nullable_to_non_nullable
as bool,submitting: null == submitting ? _self.submitting : submitting // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}

// dart format on
