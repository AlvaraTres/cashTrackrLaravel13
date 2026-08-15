@extends('layouts.auth')

@section('title')
    Administra tus presupuestos
@endsection

@section('auth-contents')
    @if(session('success'))
        <x-alert :message="session('success')" />
    @endif
@endsection