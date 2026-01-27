@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full shadow-lg mb-6">
                <i class="fas fa-user-plus text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-3">Employee Onboarding Portal</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Complete all your employee onboarding information in one form
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                <h2 class="text-xl font-bold text-white">Complete Onboarding Form</h2>
                <p class="text-blue-100 text-sm mt-1">Fill in all required information and upload documents</p>
            </div>

            <form method="POST" action="{{ route('employee.onboarding.store') }}" enctype="multipart/form-data" class="p-6 space-y-8">
                @csrf

                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('first_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('last_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Middle Name
                            </label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="personal_phone" value="{{ old('personal_phone') }}" required
                                   placeholder="0712 345 678"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('personal_phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="personal_email" value="{{ old('personal_email') }}" required
                                   placeholder="you@example.com"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('personal_email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Date of Birth
                            </label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Gender
                            </label>
                            <select name="gender"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
<!-- Education Information -->
<div>
    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
        Education Information
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Education Level -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Highest Education Level
            </label>
            <select name="education_level"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                <option value="">Select Education Level</option>
                <option value="High School" {{ old('education_level') == 'High School' ? 'selected' : '' }}>High School</option>
                <option value="Certificate" {{ old('education_level') == 'Certificate' ? 'selected' : '' }}>Certificate</option>
                <option value="Diploma" {{ old('education_level') == 'Diploma' ? 'selected' : '' }}>Diploma</option>
                <option value="Degree (Undergraduate)" {{ old('education_level') == 'Degree (Undergraduate)' ? 'selected' : '' }}>Degree (Undergraduate)</option>
                <option value="Masters" {{ old('education_level') == 'Masters' ? 'selected' : '' }}>Masters</option>
                <option value="PhD" {{ old('education_level') == 'PhD' ? 'selected' : '' }}>PhD</option>
            </select>
            @error('education_level')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Field of Study -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Field of Study
            </label>
            <input type="text" name="field_of_study" value="{{ old('field_of_study') }}"
                   placeholder="e.g., Computer Science, Business Administration"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
            @error('field_of_study')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Institution -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Institution
            </label>
            <input type="text" name="institution" value="{{ old('institution') }}"
                   placeholder="e.g., University of Nairobi, Kenyatta University"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
            @error('institution')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Year Completed -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Year Completed
            </label>
            <input type="text" name="year_completed" value="{{ old('year_completed') }}"
                   placeholder="e.g., 2020"
                   maxlength="4"
                   pattern="\d{4}"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
            @error('year_completed')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
                <!-- Employment Details -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Employment Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Designation <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="designation" value="{{ old('designation') }}" required
                                   placeholder="e.g., Software Developer"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('designation')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Date of Joining <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_of_joining" value="{{ old('date_of_joining') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('date_of_joining')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Department <span class="text-red-500">*</span>
                            </label>
                            <select name="department_id" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Unit
                            </label>
                            <select name="unit_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Employment Type
                            </label>
                            <select name="employment_type"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="Regular" {{ old('employment_type') == 'Regular' ? 'selected' : '' }}>Regular</option>
                                <option value="Contract" {{ old('employment_type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                                <option value="Temporary" {{ old('employment_type') == 'Temporary' ? 'selected' : '' }}>Temporary</option>
                                <option value="Intern" {{ old('employment_type') == 'Intern' ? 'selected' : '' }}>Intern</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statutory Numbers -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Statutory Registration Numbers <span class="text-sm text-red-500">(All Required)</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                National ID Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="national_id_number" value="{{ old('national_id_number') }}" required
                                   placeholder="e.g., 12345678"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('national_id_number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Passport Number
                            </label>
                            <input type="text" name="passport_number" value="{{ old('passport_number') }}"
                                   placeholder="e.g., A1234567"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                NSSF Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nssf_number" value="{{ old('nssf_number') }}" required
                                   placeholder="e.g., NSSF123456"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('nssf_number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                SHA Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="sha_number" value="{{ old('sha_number') }}" required
                                   placeholder="e.g., SHA123456"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('sha_number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                KRA PIN <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="kra_pin" value="{{ old('kra_pin') }}" required
                                   placeholder="e.g., A123456789B"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('kra_pin')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Emergency Contact
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('next_of_kin_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Relationship <span class="text-red-500">*</span>
                            </label>
                            <select name="next_of_kin_relationship" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                                <option value="">Select Relationship</option>
                                <option value="Spouse" {{ old('next_of_kin_relationship') == 'Spouse' ? 'selected' : '' }}>Spouse</option>
                                <option value="Parent" {{ old('next_of_kin_relationship') == 'Parent' ? 'selected' : '' }}>Parent</option>
                                <option value="Sibling" {{ old('next_of_kin_relationship') == 'Sibling' ? 'selected' : '' }}>Sibling</option>
                                <option value="Child" {{ old('next_of_kin_relationship') == 'Child' ? 'selected' : '' }}>Child</option>
                                <option value="Other" {{ old('next_of_kin_relationship') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('next_of_kin_relationship')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="next_of_kin_phone" value="{{ old('next_of_kin_phone') }}" required
                                   placeholder="0712 345 678"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                            @error('next_of_kin_phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" name="next_of_kin_email" value="{{ old('next_of_kin_email') }}"
                                   placeholder="contact@example.com"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Address
                            </label>
                            <textarea name="next_of_kin_address" rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">{{ old('next_of_kin_address') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
               <div>
    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
        Required Documents <span class="text-sm text-red-500">(All Required - Max: 5MB each)</span>
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach([
            ['field' => 'national_id_photo', 'label' => 'National ID Photo', 'hint' => 'Clear photo of your National ID (PDF or Image)', 'required' => true],
            ['field' => 'passport_photo', 'label' => 'Passport Photo', 'hint' => 'Passport photo page (PDF or Image)', 'required' => true],
            ['field' => 'passport_size_photo', 'label' => 'Passport Size Photo', 'hint' => 'Recent passport size photo (Image only)', 'required' => true],
            ['field' => 'nssf_card_photo', 'label' => 'NSSF Card Photo', 'hint' => 'Clear photo of your NSSF card', 'required' => true],
            ['field' => 'sha_card_photo', 'label' => 'SHA Card Photo', 'hint' => 'Clear photo of your SHA card', 'required' => true],
            ['field' => 'kra_certificate_photo', 'label' => 'KRA Certificate Photo', 'hint' => 'Clear photo of your KRA pin certificate', 'required' => true],
            ['field' => 'cv_upload', 'label' => 'CV/Resume', 'hint' => 'Upload your CV/Resume (PDF, DOC, DOCX - Max: 10MB)', 'required' => false]
        ] as $doc)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $doc['label'] }}
                @if($doc['required'])
                    <span class="text-red-500">*</span>
                @else
                    <span class="text-gray-500 text-xs">(Optional)</span>
                @endif
            </label>
            <input type="file" name="{{ $doc['field'] }}"
                   @if($doc['field'] == 'passport_size_photo')
                       accept="image/*"
                   @elseif($doc['field'] == 'cv_upload')
                       accept=".pdf,.doc,.docx"
                   @else
                       accept="image/*,.pdf"
                   @endif
                   @if($doc['required']) required @endif
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @error($doc['field'])
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            @if($doc['hint'])
            <p class="text-xs text-gray-500 mt-1">{{ $doc['hint'] }}</p>
            @endif
        </div>
        @endforeach
    </div>
</div>

                <!-- Terms -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <input type="checkbox" id="terms" name="terms" value="1" required
                               class="mt-1 mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               {{ old('terms') ? 'checked' : '' }}>
                        <label for="terms" class="text-sm text-gray-700">
                            I confirm that all information provided is accurate and understand that providing false information may have consequences. I agree to the terms and conditions.
                        </label>
                    </div>
                    @error('terms')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                            onclick="return confirm('Are you sure you want to submit? Make sure all fields are completed and documents uploaded.')"
                            class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-4 px-6 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center space-x-3">
                        <i class="fas fa-paper-plane"></i>
                        <span class="text-lg">Submit Application</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Your information is secure and will only be used for employment purposes.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format phone numbers
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.length <= 3) {
                        value = value;
                    } else if (value.length <= 6) {
                        value = value.substring(0, 3) + ' ' + value.substring(3);
                    } else {
                        value = value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6, 9);
                    }
                    e.target.value = value;
                }
            });
        });
// Add this to your script
const yearInput = document.querySelector('input[name="year_completed"]');
if (yearInput) {
    yearInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4) {
            value = value.substring(0, 4);
        }
        e.target.value = value;

        // Optional: Validate year range
        if (value.length === 4) {
            const year = parseInt(value);
            const currentYear = new Date().getFullYear();
            if (year < 1900 || year > currentYear) {
                e.target.setCustomValidity(`Year must be between 1900 and ${currentYear}`);
            } else {
                e.target.setCustomValidity('');
            }
        }
    });
}
        // File size validation
       // File size validation
const fileInputs = document.querySelectorAll('input[type="file"]');
fileInputs.forEach(input => {
    input.addEventListener('change', function(e) {
        const file = this.files[0];

        // Different max sizes for different file types
        let maxSize;
        if (this.name === 'cv_upload') {
            maxSize = 10 * 1024 * 1024; // 10MB for CV
        } else {
            maxSize = 5 * 1024 * 1024; // 5MB for other documents
        }

        if (file && file.size > maxSize) {
            alert(`File size must be less than ${this.name === 'cv_upload' ? '10MB' : '5MB'}`);
            this.value = '';
        }
    });
});
    });
</script>
@endsection
