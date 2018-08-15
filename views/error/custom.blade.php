@extends('flarum.forum::layouts.basic')

@section('content')
    <p>
        {{ $error->getMessage() ?? $message }}
    </p>
@endsection
