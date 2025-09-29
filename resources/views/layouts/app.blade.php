<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TexaPay') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Assets -->
    @php($manifestPath = public_path('build/manifest.json'))
    @if (file_exists($manifestPath))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback styles while Vite manifest is missing -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <style>
            body { background: #f8fafc; }
        </style>
    @endif
</head>
<body class="font-sans antialiased">
    @php($manifestPath = public_path('build/manifest.json'))
    @php($usingVite = file_exists($manifestPath))

    @if(auth()->check() && !(bool) (auth()->user()->is_admin ?? false))
        <!-- User Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-3">
            <div class="container">
                <a class="navbar-brand" href="{{ route('dashboard') }}">TexaPay</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav" aria-controls="userNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="userNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('transactions.index') }}">Transactions</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Profile Settings</a>
                            <ul class="dropdown-menu" aria-labelledby="profileMenu">
                                <li><a class="dropdown-item" href="{{ route('profile.personal') }}">Personal Information</a></li>
                                <li><a class="dropdown-item" href="{{ route('profile.security') }}">Security Settings</a></li>
                                <li><a class="dropdown-item" href="{{ route('profile.limits') }}">Transaction Limits</a></li>
                                <li><a class="dropdown-item" href="{{ route('profile.notifications') }}">Notification Preferences</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="supportMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Support</a>
                            <ul class="dropdown-menu" aria-labelledby="supportMenu">
                                <li><a class="dropdown-item" href="{{ route('support.help') }}">Help Center (FAQs)</a></li>
                                <li><a class="dropdown-item" href="{{ route('support.contact') }}">Contact Us</a></li>
                                <li><a class="dropdown-item" href="{{ route('support.tickets') }}">My Tickets</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('policies') }}">Policies & Terms</a></li>
                            </ul>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endif

    <div class="min-h-screen bg-gray-100">
        <!-- Page Content -->
        <main>
            @yield('content')
        </main>
    </div>
    @if(class_exists('\\App\\Models\\AdminSetting') 
        && \App\Models\AdminSetting::getValue('live_chat_enabled', false) 
        && \App\Models\AdminSetting::getValue('tawk_to_widget_id'))
        <!-- Tawk.to Live Chat -->
        <script type="text/javascript">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/{{ \App\Models\AdminSetting::getValue('tawk_to_widget_id') }}';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
        })();
        </script>
    @endif

    @if(!$usingVite)
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @endif
</body>
</html>
