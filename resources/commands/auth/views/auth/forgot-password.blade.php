<h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Forgot Your Password?</h1>

<p class="text-gray-600 text-center mb-6">
    Enter your registered email address and we’ll send you a link to reset your password.
</p>

<form method="POST" action="/forgot-password" class="space-y-4">
    @csrf

    <input type="email"
           name="email"
           placeholder="Enter your email"
           value="{{ $email ?: old('email') }}"
           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-200"
           required>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
        Send Reset Link
    </button>
</form>

<div class="mt-6 text-sm text-center text-gray-600">
    <a href="/login" class="text-blue-600 hover:underline">Back to Login</a>
</div>
