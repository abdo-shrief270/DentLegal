// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint, type=warning, deprecated_member_use, deprecated_member_use_from_same_package
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'auth_models.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$DentistProfile {

 String get name; String get specialty; String get city; String get facility; String? get licenseNo;
/// Create a copy of DentistProfile
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$DentistProfileCopyWith<DentistProfile> get copyWith => _$DentistProfileCopyWithImpl<DentistProfile>(this as DentistProfile, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as DentistProfile;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is DentistProfile&&(identical(other.name, _this.name) || other.name == _this.name)&&(identical(other.specialty, _this.specialty) || other.specialty == _this.specialty)&&(identical(other.city, _this.city) || other.city == _this.city)&&(identical(other.facility, _this.facility) || other.facility == _this.facility)&&(identical(other.licenseNo, _this.licenseNo) || other.licenseNo == _this.licenseNo));
}


@override
int get hashCode {
  final _this = this as DentistProfile;
  return Object.hash(runtimeType,_this.name,_this.specialty,_this.city,_this.facility,_this.licenseNo);
}

@override
String toString() {
  final _this = this as DentistProfile;
  return 'DentistProfile(name: ${_this.name}, specialty: ${_this.specialty}, city: ${_this.city}, facility: ${_this.facility}, licenseNo: ${_this.licenseNo})';
}


}

/// @nodoc
abstract mixin class $DentistProfileCopyWith<$Res>  {
  factory $DentistProfileCopyWith(DentistProfile value, $Res Function(DentistProfile) _then) = _$DentistProfileCopyWithImpl;
@useResult
$Res call({
 String name, String specialty, String city, String facility, String? licenseNo
});




}
/// @nodoc
class _$DentistProfileCopyWithImpl<$Res>
    implements $DentistProfileCopyWith<$Res> {
  _$DentistProfileCopyWithImpl(this._self, this._then);

  final DentistProfile _self;
  final $Res Function(DentistProfile) _then;

/// Create a copy of DentistProfile
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? name = null,Object? specialty = null,Object? city = null,Object? facility = null,Object? licenseNo = freezed,}) {
  return _then(DentistProfile(
name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,specialty: null == specialty ? _self.specialty : specialty // ignore: cast_nullable_to_non_nullable
as String,city: null == city ? _self.city : city // ignore: cast_nullable_to_non_nullable
as String,facility: null == facility ? _self.facility : facility // ignore: cast_nullable_to_non_nullable
as String,licenseNo: freezed == licenseNo ? _self.licenseNo : licenseNo // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [DentistProfile].
extension DentistProfilePatterns on DentistProfile {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _DentistProfile value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _DentistProfile() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _DentistProfile value)  $default,){
final _that = this;
switch (_that) {
case _DentistProfile():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _DentistProfile value)?  $default,){
final _that = this;
switch (_that) {
case _DentistProfile() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String name,  String specialty,  String city,  String facility,  String? licenseNo)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _DentistProfile() when $default != null:
return $default(_that.name,_that.specialty,_that.city,_that.facility,_that.licenseNo);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String name,  String specialty,  String city,  String facility,  String? licenseNo)  $default,) {final _that = this;
switch (_that) {
case _DentistProfile():
return $default(_that.name,_that.specialty,_that.city,_that.facility,_that.licenseNo);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String name,  String specialty,  String city,  String facility,  String? licenseNo)?  $default,) {final _that = this;
switch (_that) {
case _DentistProfile() when $default != null:
return $default(_that.name,_that.specialty,_that.city,_that.facility,_that.licenseNo);case _:
  return null;

}
}

}

/// @nodoc


class _DentistProfile extends DentistProfile {
  const _DentistProfile({required this.name, required this.specialty, required this.city, required this.facility, this.licenseNo}): super._();
  

@override final  String name;
@override final  String specialty;
@override final  String city;
@override final  String facility;
@override final  String? licenseNo;

/// Create a copy of DentistProfile
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$DentistProfileCopyWith<_DentistProfile> get copyWith => __$DentistProfileCopyWithImpl<_DentistProfile>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _DentistProfile&&(identical(other.name, name) || other.name == name)&&(identical(other.specialty, specialty) || other.specialty == specialty)&&(identical(other.city, city) || other.city == city)&&(identical(other.facility, facility) || other.facility == facility)&&(identical(other.licenseNo, licenseNo) || other.licenseNo == licenseNo));
}


@override
int get hashCode {
    return Object.hash(runtimeType,name,specialty,city,facility,licenseNo);
}

@override
String toString() {
    return 'DentistProfile(name: $name, specialty: $specialty, city: $city, facility: $facility, licenseNo: $licenseNo)';
}


}

/// @nodoc
abstract mixin class _$DentistProfileCopyWith<$Res> implements $DentistProfileCopyWith<$Res> {
  factory _$DentistProfileCopyWith(_DentistProfile value, $Res Function(_DentistProfile) _then) = __$DentistProfileCopyWithImpl;
@override @useResult
$Res call({
 String name, String specialty, String city, String facility, String? licenseNo
});




}
/// @nodoc
class __$DentistProfileCopyWithImpl<$Res>
    implements _$DentistProfileCopyWith<$Res> {
  __$DentistProfileCopyWithImpl(this._self, this._then);

  final _DentistProfile _self;
  final $Res Function(_DentistProfile) _then;

/// Create a copy of DentistProfile
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? name = null,Object? specialty = null,Object? city = null,Object? facility = null,Object? licenseNo = freezed,}) {
  return _then(_DentistProfile(
name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,specialty: null == specialty ? _self.specialty : specialty // ignore: cast_nullable_to_non_nullable
as String,city: null == city ? _self.city : city // ignore: cast_nullable_to_non_nullable
as String,facility: null == facility ? _self.facility : facility // ignore: cast_nullable_to_non_nullable
as String,licenseNo: freezed == licenseNo ? _self.licenseNo : licenseNo // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}

/// @nodoc
mixin _$Session {

 String get phone; DentistProfile? get profile;
/// Create a copy of Session
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$SessionCopyWith<Session> get copyWith => _$SessionCopyWithImpl<Session>(this as Session, _$identity);



@override
bool operator ==(Object other) {
  final _this = this as Session;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Session&&(identical(other.phone, _this.phone) || other.phone == _this.phone)&&(identical(other.profile, _this.profile) || other.profile == _this.profile));
}


@override
int get hashCode {
  final _this = this as Session;
  return Object.hash(runtimeType,_this.phone,_this.profile);
}

@override
String toString() {
  final _this = this as Session;
  return 'Session(phone: ${_this.phone}, profile: ${_this.profile})';
}


}

/// @nodoc
abstract mixin class $SessionCopyWith<$Res>  {
  factory $SessionCopyWith(Session value, $Res Function(Session) _then) = _$SessionCopyWithImpl;
@useResult
$Res call({
 String phone, DentistProfile? profile
});


$DentistProfileCopyWith<$Res>? get profile;

}
/// @nodoc
class _$SessionCopyWithImpl<$Res>
    implements $SessionCopyWith<$Res> {
  _$SessionCopyWithImpl(this._self, this._then);

  final Session _self;
  final $Res Function(Session) _then;

/// Create a copy of Session
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? phone = null,Object? profile = freezed,}) {
  return _then(Session(
phone: null == phone ? _self.phone : phone // ignore: cast_nullable_to_non_nullable
as String,profile: freezed == profile ? _self.profile : profile // ignore: cast_nullable_to_non_nullable
as DentistProfile?,
  ));
}
/// Create a copy of Session
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$DentistProfileCopyWith<$Res>? get profile {
    if (_self.profile == null) {
    return null;
  }

  return $DentistProfileCopyWith<$Res>(_self.profile!, (value) {
    return _then(_self.copyWith(profile: value));
  });
}
}


/// Adds pattern-matching-related methods to [Session].
extension SessionPatterns on Session {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Session value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Session() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Session value)  $default,){
final _that = this;
switch (_that) {
case _Session():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Session value)?  $default,){
final _that = this;
switch (_that) {
case _Session() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String phone,  DentistProfile? profile)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Session() when $default != null:
return $default(_that.phone,_that.profile);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String phone,  DentistProfile? profile)  $default,) {final _that = this;
switch (_that) {
case _Session():
return $default(_that.phone,_that.profile);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String phone,  DentistProfile? profile)?  $default,) {final _that = this;
switch (_that) {
case _Session() when $default != null:
return $default(_that.phone,_that.profile);case _:
  return null;

}
}

}

/// @nodoc


class _Session extends Session {
  const _Session({required this.phone, this.profile}): super._();
  

@override final  String phone;
@override final  DentistProfile? profile;

/// Create a copy of Session
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$SessionCopyWith<_Session> get copyWith => __$SessionCopyWithImpl<_Session>(this, _$identity);



@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _Session&&(identical(other.phone, phone) || other.phone == phone)&&(identical(other.profile, profile) || other.profile == profile));
}


@override
int get hashCode {
    return Object.hash(runtimeType,phone,profile);
}

@override
String toString() {
    return 'Session(phone: $phone, profile: $profile)';
}


}

/// @nodoc
abstract mixin class _$SessionCopyWith<$Res> implements $SessionCopyWith<$Res> {
  factory _$SessionCopyWith(_Session value, $Res Function(_Session) _then) = __$SessionCopyWithImpl;
@override @useResult
$Res call({
 String phone, DentistProfile? profile
});


@override $DentistProfileCopyWith<$Res>? get profile;

}
/// @nodoc
class __$SessionCopyWithImpl<$Res>
    implements _$SessionCopyWith<$Res> {
  __$SessionCopyWithImpl(this._self, this._then);

  final _Session _self;
  final $Res Function(_Session) _then;

/// Create a copy of Session
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? phone = null,Object? profile = freezed,}) {
  return _then(_Session(
phone: null == phone ? _self.phone : phone // ignore: cast_nullable_to_non_nullable
as String,profile: freezed == profile ? _self.profile : profile // ignore: cast_nullable_to_non_nullable
as DentistProfile?,
  ));
}

/// Create a copy of Session
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$DentistProfileCopyWith<$Res>? get profile {
    if (_self.profile == null) {
    return null;
  }

  return $DentistProfileCopyWith<$Res>(_self.profile!, (value) {
    return _then(_self.copyWith(profile: value));
  });
}
}

// dart format on
