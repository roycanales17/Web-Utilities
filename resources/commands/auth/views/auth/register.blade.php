<h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Create an Account</h1>
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <!-- Error Message -->
    @if ($error = error('message'))
        <div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-300 rounded-lg" role="alert">
            ❌ {{ $error }}
        </div>
    @endif

    <input type="text" name="name" placeholder="Full Name"
           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200" required>

    <input type="email" name="email" placeholder="Email"
           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200" required>

    <input type="password" name="password" placeholder="Password"
           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-200" required>

    <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
        Register
    </button>
</form>

<div class="mt-6 text-sm text-center text-gray-600">
    Already have an account?
    <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Sign in</a>
</div>
