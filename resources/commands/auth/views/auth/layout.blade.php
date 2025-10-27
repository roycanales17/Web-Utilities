<html lang="{{ $g_page_lang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:title" content="{{ $g_page_title }}">
    <meta property="og:description" content="{{ $g_page_description }}">
    <meta property="og:url" content="{{ $g_page_url }}">
    <meta name="twitter:card" content="summary_large_image">

    <title>{{ $g_page_title }}</title>

    <!-- Favicon and Icons -->
    <link rel="icon" type="image/png" sizes="16x16" href="/resources/images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/resources/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/resources/images/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/resources/images/android-chrome-512x512.png">
    <link rel="apple-touch-icon" href="/resources/images/apple-touch-icon.png">
    <link rel="shortcut icon" href="favicon.ico">

    <!-- Utilities -->
    <link rel="stylesheet" href="/build/main.css" />
    <link rel="stylesheet" href="/build/utilities.css" />
    <script src="/build/main.js"></script>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">
<nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
        <a href="/" class="text-xl font-semibold text-gray-700">MyApp</a>
        <div class="space-x-4">
            @if(session('user_id'))
                <a href="/dashboard" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                <a href="/logout" class="text-red-500 hover:text-red-700">Logout</a>
            @else
                <a href="/login" class="text-gray-600 hover:text-gray-900">Login</a>
                <a href="/register" class="text-gray-600 hover:text-gray-900">Register</a>
            @endif
        </div>
    </div>
</nav>

<main class="flex-grow flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
        {!! $g_page_content !!}
    </div>
</main>

<footer class="bg-white shadow-inner py-4 text-center text-sm text-gray-500">
    © {{ date('Y') }} MyApp — All rights reserved.
</footer>
<noscript>
    <p>JavaScript is required for this website to function properly.</p>
</noscript>
</body>
</html>
