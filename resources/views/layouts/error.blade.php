@extends('layouts.app')
@section('content-class', 'align-middle d-flex flex-column justify-content-center align-items-center')
@section('content')
    @if(Auth::admin())
        <div class="fs-2 text-danger">
            Errore @yield('code'):
        </div>
    @endif
    <div class="fs-2">
        @yield('message')
    </div>
@endsection