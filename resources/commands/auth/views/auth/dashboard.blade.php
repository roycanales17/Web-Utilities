<x-layout title="Dashboard">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Welcome, {{ $user['name'] }} 👋</h1>

    <div class="space-y-4">
        <p class="text-gray-600">You're logged in successfully.</p>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm text-gray-500">Email: <span class="font-medium text-gray-800">{{ $user['email'] }}</span></p>
            <p class="text-sm text-gray-500">Member since: <span class="font-medium text-gray-800">{{ $user['created_at'] }}</span></p>
        </div>

        <a href="/logout"
           class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition">
            Log Out
        </a>
    </div>
</x-layout>
