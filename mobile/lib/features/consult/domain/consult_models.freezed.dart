// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint, type=warning, deprecated_member_use, deprecated_member_use_from_same_package
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'consult_models.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$ConsultMessage {

 String get id; bool get fromDentist; String get text; List<Citation> get citations; bool get refused; bool? get ratedUseful;
/// Create a copy of ConsultMessage
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$ConsultMessageCopyWith<ConsultMessage> get copyWith => _$ConsultMessageCopyWithImpl<ConsultMessage>(this as ConsultMessage, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as ConsultMessage;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is ConsultMessage&&(identical(other.id, _this.id) || other.id == _this.id)&&(identical(other.fromDentist, _this.fromDentist) || other.fromDentist == _this.fromDentist)&&(identical(other.text, _this.text) || other.text == _this.text)&&const DeepCollectionEquality().equals(other.citations, _this.citations)&&(identical(other.refused, _this.refused) || other.refused == _this.refused)&&(identical(other.ratedUseful, _this.ratedUseful) || other.ratedUseful == _this.ratedUseful));
}


@override
int get hashCode {
  final _this = this as ConsultMessage;
  return Object.hash(runtimeType,_this.id,_this.fromDentist,_this.text,const DeepCollectionEquality().hash(_this.citations),_this.refused,_this.ratedUseful);
}

@override
String toString() {
  final _this = this as ConsultMessage;
  return 'ConsultMessage(id: ${_this.id}, fromDentist: ${_this.fromDentist}, text: ${_this.text}, citations: ${_this.citations}, refused: ${_this.refused}, ratedUseful: ${_this.ratedUseful})';
}


}

/// @nodoc
abstract mixin class $ConsultMessageCopyWith<$Res>  {
  factory $ConsultMessageCopyWith(ConsultMessage value, $Res Function(ConsultMessage) _then) = _$ConsultMessageCopyWithImpl;
@useResult
$Res call({
 String id, bool fromDentist, String text, List<Citation> citations, bool refused, bool? ratedUseful
});




}
/// @nodoc
class _$ConsultMessageCopyWithImpl<$Res>
    implements $ConsultMessageCopyWith<$Res> {
  _$ConsultMessageCopyWithImpl(this._self, this._then);

  final ConsultMessage _self;
  final $Res Function(ConsultMessage) _then;

/// Create a copy of ConsultMessage
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? fromDentist = null,Object? text = null,Object? citations = null,Object? refused = null,Object? ratedUseful = freezed,}) {
  return _then(ConsultMessage(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,fromDentist: null == fromDentist ? _self.fromDentist : fromDentist // ignore: cast_nullable_to_non_nullable
as bool,text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,citations: null == citations ? _self.citations : citations // ignore: cast_nullable_to_non_nullable
as List<Citation>,refused: null == refused ? _self.refused : refused // ignore: cast_nullable_to_non_nullable
as bool,ratedUseful: freezed == ratedUseful ? _self.ratedUseful : ratedUseful // ignore: cast_nullable_to_non_nullable
as bool?,
  ));
}

}


/// Adds pattern-matching-related methods to [ConsultMessage].
extension ConsultMessagePatterns on ConsultMessage {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _ConsultMessage value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _ConsultMessage() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _ConsultMessage value)  $default,){
final _that = this;
switch (_that) {
case _ConsultMessage():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _ConsultMessage value)?  $default,){
final _that = this;
switch (_that) {
case _ConsultMessage() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String id,  bool fromDentist,  String text,  List<Citation> citations,  bool refused,  bool? ratedUseful)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _ConsultMessage() when $default != null:
return $default(_that.id,_that.fromDentist,_that.text,_that.citations,_that.refused,_that.ratedUseful);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String id,  bool fromDentist,  String text,  List<Citation> citations,  bool refused,  bool? ratedUseful)  $default,) {final _that = this;
switch (_that) {
case _ConsultMessage():
return $default(_that.id,_that.fromDentist,_that.text,_that.citations,_that.refused,_that.ratedUseful);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String id,  bool fromDentist,  String text,  List<Citation> citations,  bool refused,  bool? ratedUseful)?  $default,) {final _that = this;
switch (_that) {
case _ConsultMessage() when $default != null:
return $default(_that.id,_that.fromDentist,_that.text,_that.citations,_that.refused,_that.ratedUseful);case _:
  return null;

}
}

}

/// @nodoc


class _ConsultMessage implements ConsultMessage {
  const _ConsultMessage({required this.id, required this.fromDentist, required this.text,  List<Citation> citations = const [], this.refused = false, this.ratedUseful}): _citations = citations;
  

@override final  String id;
@override final  bool fromDentist;
@override final  String text;
 final  List<Citation> _citations;
@override@JsonKey() List<Citation> get citations {
  if (_citations is EqualUnmodifiableListView) return _citations;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_citations);
}

@override@JsonKey() final  bool refused;
@override final  bool? ratedUseful;

/// Create a copy of ConsultMessage
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$ConsultMessageCopyWith<_ConsultMessage> get copyWith => __$ConsultMessageCopyWithImpl<_ConsultMessage>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _ConsultMessage&&(identical(other.id, id) || other.id == id)&&(identical(other.fromDentist, fromDentist) || other.fromDentist == fromDentist)&&(identical(other.text, text) || other.text == text)&&const DeepCollectionEquality().equals(other.citations, _citations)&&(identical(other.refused, refused) || other.refused == refused)&&(identical(other.ratedUseful, ratedUseful) || other.ratedUseful == ratedUseful));
}


@override
int get hashCode {
    return Object.hash(runtimeType,id,fromDentist,text,const DeepCollectionEquality().hash(_citations),refused,ratedUseful);
}

@override
String toString() {
    return 'ConsultMessage(id: $id, fromDentist: $fromDentist, text: $text, citations: $citations, refused: $refused, ratedUseful: $ratedUseful)';
}


}

/// @nodoc
abstract mixin class _$ConsultMessageCopyWith<$Res> implements $ConsultMessageCopyWith<$Res> {
  factory _$ConsultMessageCopyWith(_ConsultMessage value, $Res Function(_ConsultMessage) _then) = __$ConsultMessageCopyWithImpl;
@override @useResult
$Res call({
 String id, bool fromDentist, String text, List<Citation> citations, bool refused, bool? ratedUseful
});




}
/// @nodoc
class __$ConsultMessageCopyWithImpl<$Res>
    implements _$ConsultMessageCopyWith<$Res> {
  __$ConsultMessageCopyWithImpl(this._self, this._then);

  final _ConsultMessage _self;
  final $Res Function(_ConsultMessage) _then;

/// Create a copy of ConsultMessage
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? fromDentist = null,Object? text = null,Object? citations = null,Object? refused = null,Object? ratedUseful = freezed,}) {
  return _then(_ConsultMessage(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,fromDentist: null == fromDentist ? _self.fromDentist : fromDentist // ignore: cast_nullable_to_non_nullable
as bool,text: null == text ? _self.text : text // ignore: cast_nullable_to_non_nullable
as String,citations: null == citations ? _self._citations : citations // ignore: cast_nullable_to_non_nullable
as List<Citation>,refused: null == refused ? _self.refused : refused // ignore: cast_nullable_to_non_nullable
as bool,ratedUseful: freezed == ratedUseful ? _self.ratedUseful : ratedUseful // ignore: cast_nullable_to_non_nullable
as bool?,
  ));
}


}

// dart format on
