@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- Progress -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Complete Your Onboarding</h2>
                @if($onboarding->isEditable())
                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Draft
                </span>
                @endif
            </div>

            <div class="relative">
                <div class="absolute top-4 left-0 right-0 h-1 bg-gray-200"></div>
                <div class="absolute top-4 left-0 h-1 bg-green-500" style="width: 50%;"></div>

                <div class="flex justify-between relative">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center mb-2 border-4 border-white shadow">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Step 1</span>
                    </div>

                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center mb-2 border-4 border-white shadow">
                            <span class="font-bold">2</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Step 2</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
        <div class="mb-6">
            <div class="bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6">
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-white">Complete Your Profile</h2>
                        <p class="text-blue-100 text-sm mt-1">Provide additional details and upload required documents</p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-100 text-sm">Ref: <strong>{{ substr($onboarding->token, 0, 8) }}...</strong></p>
                        <p class="text-blue-100 text-xs">{{ $onboarding->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

          <form method="POST" action="{{ route('employee.onboarding.update', $onboarding->token) }}" enctype="multipart/form-data" class="p-6">
                @csrf
               

                <!-- Personal Details -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Personal Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Middle Name
                            </label>
                            <input type="text" name="middle_name" value="{{ old('middle_name', $onboarding->middle_name) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Date of Birth
                            </label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $onboarding->date_of_birth ? $onboarding->date_of_birth->format('Y-m-d') : '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Gender
                            </label>
                            <select name="gender"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender', $onboarding->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $onboarding->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $onboarding->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Employment Type
                            </label>
                            <select name="employment_type"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="Regular" {{ old('employment_type', $onboarding->employment_type) == 'Regular' ? 'selected' : '' }}>Regular</option>
                                <option value="Contract" {{ old('employment_type', $onboarding->employment_type) == 'Contract' ? 'selected' : '' }}>Contract</option>
                                <option value="Temporary" {{ old('employment_type', $onboarding->employment_type) == 'Temporary' ? 'selected' : '' }}>Temporary</option>
                                <option value="Intern" {{ old('employment_type', $onboarding->employment_type) == 'Intern' ? 'selected' : '' }}>Intern</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Unit
                            </label>
                            <select name="unit_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id', $onboarding->unit_id) == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statutory Numbers -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Statutory Registration
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                National ID Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="national_id_number" value="{{ old('national_id_number', $onboarding->national_id_number) }}" required
                                   placeholder="e.g., 12345678"
                                   class="w-full px-4 py-3 border {{ $errors->has('national_id_number') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('national_id_number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Passport Number
                            </label>
                            <input type="text" name="passport_number" value="{{ old('passport_number', $onboarding->passport_number) }}"
                                   placeholder="e.g., A1234567"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                NSSF Number
                            </label>
                            <input type="text" name="nssf_number" value="{{ old('nssf_number', $onboarding->nssf_number) }}"
                                   placeholder="e.g., NSSF123456"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                SHA Number
                            </label>
                            <input type="text" name="sha_number" value="{{ old('sha_number', $onboarding->sha_number) }}"
                                   placeholder="e.g., SHA123456"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                KRA PIN
                            </label>
                            <input type="text" name="kra_pin" value="{{ old('kra_pin', $onboarding->kra_pin) }}"
                                   placeholder="e.g., A123456789B"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>
                    </div>
                </div>

                <!-- Next of Kin Additional Info -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Emergency Contact Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" name="next_of_kin_email" value="{{ old('next_of_kin_email', $onboarding->next_of_kin_email) }}"
                                   placeholder="contact@example.com"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Address
                            </label>
                            <textarea name="next_of_kin_address" rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">{{ old('next_of_kin_address', $onboarding->next_of_kin_address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Required Documents
                        <span class="text-sm font-normal text-gray-500 ml-2">(Max: 2MB each)</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach([
                            ['field' => 'national_id_photo', 'label' => 'National ID Photo', 'required' => true, 'hint' => 'Clear photo of your National ID'],
                            ['field' => 'passport_photo', 'label' => 'Passport Photo'],
                            ['field' => 'passport_size_photo', 'label' => 'Passport Size Photo', 'hint' => 'White background, formal attire'],
                            ['field' => 'nssf_card_photo', 'label' => 'NSSF Card'],
                            ['field' => 'sha_card_photo', 'label' => 'SHA Card'],
                            ['field' => 'kra_certificate_photo', 'label' => 'KRA Certificate']
                        ] as $doc)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $doc['label'] }}
                                @if($doc['required'] ?? false)
                                <span class="text-red-500">*</span>
                                @endif
                                @if($onboarding->{$doc['field']})
                                <span class="text-green-600 ml-2">
                                    <i class="fas fa-check-circle"></i> Uploaded
                                </span>
                                @endif
                            </label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <input type="file" name="{{ $doc['field'] }}" accept="image/*,.pdf"
                                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                @if($onboarding->{$doc['field']})
                                <a href="{{ Storage::url($onboarding->{$doc['field']}) }}" target="_blank"
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endif
                            </div>
                            @if($doc['hint'] ?? false)
                            <p class="text-xs text-gray-500 mt-1">{{ $doc['hint'] }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t pt-6">
                    <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                        <div class="text-sm text-gray-500">
                            <p><i class="fas fa-info-circle text-blue-500 mr-1"></i> Fields marked with <span class="text-red-500">*</span> are required</p>
                        </div>

                        <div class="flex space-x-4">
                            <!-- Save Button -->
                            <button type="submit"
                                    class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow flex items-center space-x-2">
                                <i class="fas fa-save"></i>
                                <span>Save Progress</span>
                            </button>

                            <!-- Submit Button - Using POST form -->
                           <form method="POST" action="{{ route('employee.onboarding.submit', $onboarding->token) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        onclick="return confirm('Are you sure you want to submit? Make sure National ID and other required fields are completed.')"
                                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow flex items-center space-x-2">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Submit Application</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Requirements -->
        <div class="mt-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Submission Requirements</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Required Information</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                All personal information
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                National ID Number
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                National ID Photo
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">After Submission</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-clock text-yellow-500 mt-1 mr-2"></i>
                                HR verification (3-5 days)
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-user-check text-blue-500 mt-1 mr-2"></i>
                                Status update via email
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // File upload indicators
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                if (this.files[0]) {
                    const label = this.closest('div').querySelector('label');
                    const existingSpan = label.querySelector('.file-selected');

                    if (existingSpan) {
                        existingSpan.textContent = 'Selected';
                    } else {
                        const span = document.createElement('span');
                        span.className = 'file-selected text-green-600 ml-2 text-sm';
                        span.innerHTML = '<i class="fas fa-check-circle"></i> Selected';
                        label.appendChild(span);
                    }
                }
            });
        });
    });
</script>
@endsection
