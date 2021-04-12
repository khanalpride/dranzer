<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ secure_asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ secure_asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ secure_asset('favicon-32x32.png') }}">

    <title>Login - {{ config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .content {
            text-align: center;
        }

        .cta {
            font-size: 36px;
        }

        .m-b-md {
            margin-bottom: 30px;
        }

        .link {
            color: dodgerblue;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="content">
        <div class="cta m-b-md">
            Hi there! You must <a href="{{ route('redirect.to.github') }}" class="link">login with GitHub</a> before you can create / manage projects.
        </div>
    </div>
</div>
</body>
</html>
