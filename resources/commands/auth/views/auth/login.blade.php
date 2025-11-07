<h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Sign in to your account</h1>

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <!-- Error Message -->
    @if ($error = error('message'))
        <div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-300 rounded-lg" role="alert">
            ❌ {{ $error }}
        </div>
    @endif

    <!-- Success Message -->
    @if ($success = success('message'))
        <div class="p-3 mb-4 text-sm text-green-800 bg-green-100 border border-green-300 rounded-lg" role="alert">
            ✅ {{ $success }}
        </div>
    @endif

    <div class="mb-4">
        <input type="email"
               name="email"
               placeholder="Email address"
               value="{{ old('email') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200" />

        @if($errorEmail = error('email'))
            <span class="text-sm text-red-500 mt-1 block ml-1">
                {{ $errorEmail }}
            </span>
        @endif
    </div>

    <div class="mb-4">
        <input type="password"
               name="password"
               placeholder="Password"
               value="{{ old('password') }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200" />

        @if($errorPassword = error('password'))
            <span class="text-sm text-red-500 mt-1 block ml-1">
                {{ $errorPassword }}
            </span>
        @endif
    </div>

    <label class="flex items-center space-x-2">
        <input type="checkbox" {{ old('remember') ? 'checked' : '' }} name="remember" class="text-blue-600">
        <span class="text-sm text-gray-600">Remember me</span>
    </label>

    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
        Log In
    </button>
</form>

<div class="mt-6 text-sm text-center text-gray-600">
    <a href="{{ route('forgot-password') }}" class="text-blue-600 hover:underline">Forgot password?</a><br>
    Don’t have an account?
    <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Create one</a>
</div>
