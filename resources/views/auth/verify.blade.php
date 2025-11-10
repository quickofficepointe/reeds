@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto w-20 h-20 bg-secondary-blue rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-envelope text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">
                Verify Your Email
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                We've sent a verification link to your email
            </p>
        </div>

        <!-- Content -->
        <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 space-y-6">
            @if (session('resent'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center space-x-3">
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    <div>
                        <p class="text-green-800 font-medium">Verification Sent!</p>
                        <p class="text-green-700 text-sm">{{ __('A fresh verification link has been sent to your email address.') }}</p>
                    </div>
                </div>
            @endif

            <div class="text-center space-y-4">
                <div class="flex items-center justify-center text-orange-500 mb-4">
                    <i class="fas fa-exclamation-circle text-4xl"></i>
                </div>

                <p class="text-gray-700 leading-relaxed">
                    {{ __('Before proceeding, please check your email for a verification link.') }}
                </p>

                <p class="text-gray-600 text-sm">
                    {{ __('If you did not receive the email') }},
                </p>

                <form class="inline" method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center text-secondary-blue hover:text-blue-600 font-medium transition duration-150">
                        <i class="fas fa-paper-plane mr-2"></i>
                        {{ __('click here to request another') }}
                    </button>
                </form>
            </div>

            <!-- Help Text -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-secondary-blue mt-1"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium">Check your spam folder</p>
                        <p class="mt-1">If you don't see the email in your inbox, please check your spam or junk folder.</p>
                    </div>
                </div>
            </div>

            <!-- Back to Login -->
            <div class="text-center pt-4 border-t border-gray-200">
                <a href="{{ route('login') }}" class="inline-flex items-center text-gray-600 hover:text-gray-800 transition duration-150 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
