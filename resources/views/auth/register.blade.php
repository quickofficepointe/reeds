@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto w-20 h-20 bg-secondary-blue rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-user-plus text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">
                Create Account
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Join as a vendor partner
            </p>
        </div>

        <!-- Register Form -->
        <form class="mt-8 space-y-6 bg-white p-8 rounded-xl shadow-lg border border-gray-100" method="POST" action="{{ route('register') }}">
            @csrf

            <div class="space-y-4">
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input id="name" type="text" name="name" required
                           class="relative block w-full px-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue focus:z-10 transition duration-150 @error('name') border-red-500 @enderror"
                           placeholder="Enter your full name" value="{{ old('name') }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input id="email" type="email" name="email" required
                           class="relative block w-full px-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue focus:z-10 transition duration-150 @error('email') border-red-500 @enderror"
                           placeholder="Enter your email" value="{{ old('email') }}">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required
                               class="relative block w-full px-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue focus:z-10 transition duration-150 pr-10 @error('password') border-red-500 @enderror"
                               placeholder="Create a password">

                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                onclick="togglePassword('password')">
                            <svg class="h-5 w-5 hidden" id="password-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg class="h-5 w-5" id="password-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.564-3.418M18.885 8.618A10.05 10.05 0 0122 12c-1.275 4.057-5.065 7-9.543 7a9.97 9.97 0 01-1.564-.176m1.144-4.881a3 3 0 11-4.243-4.243m4.243 4.243l-4.243-4.243" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                               class="relative block w-full px-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue focus:z-10 transition duration-150 pr-10"
                               placeholder="Confirm your password">

                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                onclick="togglePassword('password_confirmation')">
                            <svg class="h-5 w-5 hidden" id="password_confirmation-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg class="h-5 w-5" id="password_confirmation-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.564-3.418M18.885 8.618A10.05 10.05 0 0122 12c-1.275 4.057-5.065 7-9.543 7a9.97 9.97 0 01-1.564-.176m1.144-4.881a3 3 0 11-4.243-4.243m4.243 4.243l-4.243-4.243" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-secondary-blue hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-blue transition duration-150 shadow-md">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-white text-opacity-70 group-hover:text-opacity-100"></i>
                    </span>
                    Create Account
                </button>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-secondary-blue hover:text-blue-600 transition duration-150">
                        Sign in here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const openIcon = document.getElementById(inputId + '-open');
        const closedIcon = document.getElementById(inputId + '-closed');

        if (input.type === 'password') {
            input.type = 'text';
            openIcon.classList.remove('hidden');
            closedIcon.classList.add('hidden');
        } else {
            input.type = 'password';
            openIcon.classList.add('hidden');
            closedIcon.classList.remove('hidden');
        }
    }
</script>
@endsection
