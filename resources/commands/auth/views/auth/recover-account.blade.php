<h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">
    Reset Your Password
</h1>

<p class="text-gray-600 mb-6">
    Hi <span class="font-semibold text-gray-800">
        {{ ucfirst(strtolower(strtok(trim($name), ' '))) }}
    </span>, please enter your new password below.
</p>

<form method="POST" action="{{ route('recover-account', ['token' => $token]) }}" class="space-y-5">
    @csrf

    <!-- Error Message -->
    @if ($error = error('message'))
        <div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-300 rounded-lg" role="alert">
            ❌ {{ $error }}
        </div>
    @endif

    <!-- New Password -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            New Password
        </label>
        <input type="password" id="password" name="password"
               placeholder="Enter new password"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition"
               required>
    </div>

    <!-- Confirm Password -->
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
            Confirm New Password
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               placeholder="Re-enter new password"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition"
               required>
    </div>

    <!-- Submit Button -->
    <button type="submit"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg shadow-sm transition">
        Reset Password
    </button>
</form>

<!-- Back to Login -->
<div class="mt-6 text-sm text-center text-gray-600">
    <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">
        ← Back to Login
    </a>
</div>
