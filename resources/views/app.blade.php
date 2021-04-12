<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="base-url" content="{{ route('home') }}">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ secure_asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ secure_asset('favicon-32x32.png') }}">

    <title>{{ config('app.name') }}</title>

    <link rel="stylesheet" href="{{ mix('css/vendor.css') }}" />
    <link rel="stylesheet" href="{{ mix('css/pages.css') }}" />
    <link rel="stylesheet" href="{{ mix('css/app.css') }}" />
</head>
<body class="bg-white">
<template id="app"></template>
<script>
window.env = {
    csrfToken: '{{ csrf_token() }}',
}
window.app = {
    name: '{{ config('app.name') }}',
    @if(config('app.in_beta') !== true)
    version: '{{ config('app.version') }}',
    @endif
    baseURL: '{{ route('home') }}',
};
window.user = {
    name: '{{ auth()->user()->name }}',
    username: '{{ auth()->user()->username }}',
    email: '{{ auth()->user()->email }}',
};
</script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script src="{{ mix('js/modules/main.js') }}"></script>
</body>
</html>
