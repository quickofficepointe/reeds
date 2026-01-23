@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Success Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-10 text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-white text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Application Submitted!</h1>
                <p class="text-green-100">Your onboarding information has been received successfully.</p>
            </div>

            <!-- Body -->
            <div class="p-8">
                <!-- Application Details -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Application Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Applicant Name</p>
                            <p class="font-medium text-gray-900">{{ $onboarding->full_name }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Designation</p>
                            <p class="font-medium text-gray-900">{{ $onboarding->designation }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Application ID</p>
                            <p class="font-medium text-gray-900">{{ $onboarding->token }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Status</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
    @if($onboarding->status === 'submitted')
        bg-yellow-100 text-yellow-800
    @elseif($onboarding->status === 'verified')
        bg-blue-100 text-blue-800
    @else
        bg-green-100 text-green-800
    @endif">
    {{ ucfirst($onboarding->status) }}
</span>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">What Happens Next?</h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mr-3">
                                <i class="fas fa-search text-secondary-blue"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Document Verification</h4>
                                <p class="text-sm text-gray-600">HR will review your uploaded documents within 2-3 business days.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mr-3">
                                <i class="fas fa-envelope text-purple-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Notification</h4>
                                <p class="text-sm text-gray-600">You'll receive an email when your application is processed.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mr-3">
                                <i class="fas fa-qrcode text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">QR Code Generation</h4>
                                <p class="text-sm text-gray-600">Upon approval, you'll receive your employee QR code for meal access.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-8">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-exclamation-circle text-secondary-blue mr-2"></i>
                        <h3 class="font-semibold text-gray-900">Important Information</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-circle text-blue-500 text-xs mt-1 mr-2"></i>
                            Keep your application ID safe for reference: <code class="bg-gray-100 px-2 py-1 rounded ml-2 font-mono">{{ $onboarding->token }}</code>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-circle text-blue-500 text-xs mt-1 mr-2"></i>
                            You cannot edit your application once submitted. Contact HR for any changes.
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-circle text-blue-500 text-xs mt-1 mr-2"></i>
                            Processing typically takes 3-5 business days.
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('employee.onboarding.start') }}"
                       class="flex-1 bg-secondary-blue text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-600 transition duration-300 text-center flex items-center justify-center space-x-2">
                        <i class="fas fa-home"></i>
                        <span>Return to Home</span>
                    </a>

                    <button onclick="window.print()"
                            class="flex-1 bg-white border border-gray-300 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-50 transition duration-300 flex items-center justify-center space-x-2">
                        <i class="fas fa-print"></i>
                        <span>Print Confirmation</span>
                    </button>
                </div>

                <!-- Contact Info -->
                <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-600 mb-2">Need to contact HR?</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-4">
                        <a href="mailto:hr@reedsafricaconsult.com" class="text-secondary-blue hover:underline">
                            <i class="fas fa-envelope mr-1"></i> hr@reedsafricaconsult.com
                        </a>
                        <span class="hidden sm:inline text-gray-300">•</span>
                        <a href="tel:+254700000000" class="text-secondary-blue hover:underline">
                            <i class="fas fa-phone mr-1"></i> +254 741 266 845
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Preview -->
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 text-center">Application Timeline</h3>
            <div class="flex items-center justify-between">
                @php
                    $timelineSteps = [
                        ['icon' => 'fa-user-check', 'label' => 'Submitted', 'status' => 'completed'],
                        ['icon' => 'fa-search', 'label' => 'Verification', 'status' => $onboarding->status === 'verified' || $onboarding->status === 'processed' ? 'completed' : 'pending'],
                        ['icon' => 'fa-qrcode', 'label' => 'QR Generated', 'status' => $onboarding->status === 'processed' ? 'completed' : 'pending'],
                        ['icon' => 'fa-trophy', 'label' => 'Completed', 'status' => $onboarding->status === 'processed' ? 'completed' : 'pending'],
                    ];
                @endphp

                @foreach($timelineSteps as $step)
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2
                        {{ $step['status'] === 'completed' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                        <i class="fas {{ $step['icon'] }}"></i>
                    </div>
                    <span class="text-xs font-medium {{ $step['status'] === 'completed' ? 'text-green-700' : 'text-gray-500' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 h-1 mx-2 {{ $step['status'] === 'completed' ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Print styling
    @media print {
        nav, footer, button {
            display: none !important;
        }
        body {
            background: white !important;
        }
        .bg-gradient-to-br {
            background: white !important;
        }
    }
</script>
@endsection
