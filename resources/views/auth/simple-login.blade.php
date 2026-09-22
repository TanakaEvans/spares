<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'School Portal') }} - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-700 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-4xl grid md:grid-cols-2 min-h-[600px]">
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white p-8 flex flex-col justify-center items-center text-center relative overflow-hidden hidden md:flex">
            <div class="relative z-10">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center text-3xl mb-6">
                    🎓
                </div>
                <h1 class="text-3xl font-bold mb-2">School Portal</h1>
                <p class="text-xl opacity-90 mb-8">Comprehensive School Management System</p>
                
                <ul class="text-left space-y-3">
                    <li class="flex items-center">
                        <span class="mr-3">👥</span> User & Role Management
                    </li>
                    <li class="flex items-center">
                        <span class="mr-3">📊</span> Academic Performance Tracking
                    </li>
                    <li class="flex items-center">
                        <span class="mr-3">✅</span> Attendance Management
                    </li>
                    <li class="flex items-center">
                        <span class="mr-3">💰</span> Financial Management
                    </li>
                    <li class="flex items-center">
                        <span class="mr-3">💬</span> Communication Tools
                    </li>
                    <li class="flex items-center">
                        <span class="mr-3">🔒</span> Secure & Reliable
                    </li>
                </ul>
            </div>
        </div>

        <!-- Login Form -->
        <div class="p-8 flex flex-col justify-center">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                <p class="text-gray-600">Sign in to your account to continue</p>
            </div>

            <!-- Status Messages -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <strong>Login Failed:</strong> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email/Username -->
                <div>
                    <label for="login" class="block text-sm font-medium text-gray-700 mb-2">
                        Email or Username
                    </label>
                    <input
                        id="login"
                        type="text"
                        name="login"
                        value="{{ old('login') }}"
                        class="w-full px-4 py-3 border-2 rounded-lg transition-colors border-gray-300 focus:border-blue-500 focus:outline-none @error('login') border-red-300 @enderror"
                        placeholder="Enter your email or username"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('login')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="w-full px-4 py-3 border-2 rounded-lg transition-colors border-gray-300 focus:border-blue-500 focus:outline-none @error('password') border-red-300 @enderror"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-700">
                        Remember me for 30 days
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200"
                >
                    Sign In
                </button>
            </form>

            <!-- Demo Accounts -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Demo Accounts for Testing:</h4>
                <div class="space-y-2 text-xs text-gray-600">
                    <div class="cursor-pointer hover:text-blue-600 transition-colors" onclick="fillDemo('admin@schoolportal.com')">
                        <strong>Superuser:</strong> admin@schoolportal.com / password123
                    </div>
                    <div class="cursor-pointer hover:text-blue-600 transition-colors" onclick="fillDemo('teacher@schoolportal.com')">
                        <strong>Teacher:</strong> teacher@schoolportal.com / password123
                    </div>
                    <div class="cursor-pointer hover:text-blue-600 transition-colors" onclick="fillDemo('student@schoolportal.com')">
                        <strong>Student:</strong> student@schoolportal.com / password123
                    </div>
                    <div class="cursor-pointer hover:text-blue-600 transition-colors" onclick="fillDemo('parent@schoolportal.com')">
                        <strong>Parent:</strong> parent@schoolportal.com / password123
                    </div>
                    <div class="cursor-pointer hover:text-blue-600 transition-colors" onclick="fillDemo('staff@schoolportal.com')">
                        <strong>Staff:</strong> staff@schoolportal.com / password123
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} School Portal. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(email) {
            document.getElementById('login').value = email;
            document.getElementById('password').value = 'password123';
        }
    </script>
</body>
</html>
