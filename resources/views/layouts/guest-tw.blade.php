<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'iQuaPharma+'))</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml"
        href="{{ setting()->favicon != '' ? asset('setting/favicon/' . setting()->favicon) : asset('assets-tw/img/icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets-tw/img/icon-192.png') }}">

    {{-- Tailwind CSS (pre-built) --}}
    <link href="{{ asset('assets-tw/css/tailwind-output.css') }}" rel="stylesheet">

    {{-- Fonts (local) --}}
    <link href="{{ asset('assets-tw/vendor/fonts/inter/inter.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}" rel="stylesheet">

    {{-- Icons --}}
    <link href="{{ asset('assets-tw/vendor/css/fontawesome/all.min.css') }}" rel="stylesheet">

    {{-- Flatpickr --}}
    <link href="{{ asset('assets-tw/vendor/css/flatpickr.min.css') }}" rel="stylesheet">

    {{-- Theme --}}
    <link href="{{ asset('assets-tw/css/erp-themes.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/css/erp-overrides.css') }}" rel="stylesheet">
    <link href="{{ route('css.config') }}" rel="stylesheet">

    {{-- Dark mode: apply before paint to prevent flash --}}
    <script>
        if (localStorage.getItem('erp-dark-mode') === 'true' ||
            (!localStorage.getItem('erp-dark-mode') &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @yield('pagecss')
</head>

<body class="bg-zinc-50 font-sans antialiased">

    @yield('content')

    {{-- Core JS --}}
    <script src="{{ asset('assets-tw/vendor/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets-tw/vendor/js/flatpickr.min.js') }}"></script>

    @yield('pagescript')
    @stack('page-scripts')
</body>

</html>
