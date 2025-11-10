@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto w-20 h-20 bg-primary-red rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-key text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">
                Reset Password
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your email to receive a reset link
            </p>
        </div>

        <!-- Reset Form -->
        <form class="mt-8 space-y-6 bg-white p-8 rounded-xl shadow-lg border border-gray-100" method="POST" action="{{ route('password.email') }}">
            @csrf

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center space-x-3">
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    <div>
                        <p class="text-green-800 font-medium">Email Sent!</p>
                        <p class="text-green-700 text-sm">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                           class="relative block w-full px-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue focus:z-10 transition duration-150 @error('email') border-red-500 @enderror"
                           placeholder="Enter your email address">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Info Text -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-secondary-blue mt-1"></i>
                    <div class="text-sm text-blue-700">
                        <p>We'll send a password reset link to your email address. Click the link to create a new password.</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-red hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-red transition duration-150 shadow-md">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-paper-plane text-white text-opacity-70 group-hover:text-opacity-100"></i>
                    </span>
                    Send Password Reset Link
                </button>
            </div>

            <!-- Back to Login -->
            <div class="text-center pt-4 border-t border-gray-200">
                <a href="{{ route('login') }}" class="inline-flex items-center text-secondary-blue hover:text-blue-600 transition duration-150 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Login
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
