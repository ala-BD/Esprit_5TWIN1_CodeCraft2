{{--
    Layout guest — utilisé par les composants Breeze hérités.
    Redirige vers notre layout principal app.blade.php.
--}}
@extends('layouts.app')

@section('content')
    {{ $slot ?? '' }}
@endsection
