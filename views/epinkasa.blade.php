@extends('hanoivip::layouts.app')

@section('title', 'Pay with Epinkasa')

@section('content')

{{ wizard_roles('epinkasa.game.do') }}

@endsection