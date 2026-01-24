@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ isset($document) ? 'Update' : 'Upload' }} Documents for {{ $employee->full_name }}
        </h1>
        <p class="text-gray-600 mt-1">Employee Code: {{ $employee->employee_code }} | Department: {{ $employee->department->name }}</p>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($document))
                @method('PUT')
            @endif

            <!-- Next of Kin Section -->
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Next of Kin Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Next of Kin Name *</label>
                        <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name', $document->next_of_kin_name ?? '') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship *</label>
                        <input type="text" name="next_of_kin_relationship" value="{{ old('next_of_kin_relationship', $document->next_of_kin_relationship ?? '') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                               placeholder="e.g., Spouse, Parent, Sibling">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" name="next_of_kin_phone" value="{{ old('next_of_kin_phone', $document->next_of_kin_phone ?? '') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="next_of_kin_email" value="{{ old('next_of_kin_email', $document->next_of_kin_email ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="next_of_kin_address" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">{{ old('next_of_kin_address', $document->next_of_kin_address ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Statutory Documents</h2>
                <p class="text-sm text-gray-500 mb-6">Upload scanned copies or photos of the following documents (JPEG, PNG, PDF, max 2MB each)</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach([
                        'national_id_photo' => 'National ID Photo',
                        'passport_photo' => 'Passport Photo (if applicable)',
                        'passport_size_photo' => 'Passport Size Photo',
                        'nssf_card_photo' => 'NSSF Card Photo',
                        'sha_card_photo' => 'SHA Card Photo',
                        'kra_certificate_photo' => 'KRA Certificate Photo'
                    ] as $field => $label)
                    <div class="border rounded-lg p-4 hover:border-primary-red transition-colors">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>

                        <div class="mb-3">
                            <input type="file" name="{{ $field }}"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-red file:text-white hover:file:bg-red-700">
                        </div>

                        @if(isset($document) && $document->$field)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-green-600">
                                <i class="fas fa-check-circle mr-1"></i> Uploaded
                            </span>
                            <a href="{{ route('employees.documents.download', [$employee, $field]) }}"
                               class="text-sm text-secondary-blue hover:text-blue-700">
                                <i class="fas fa-download mr-1"></i> Download
                            </a>
                        </div>
                        @else
                        <span class="text-sm text-yellow-600">
                            <i class="fas fa-exclamation-circle mr-1"></i> Not uploaded
                        </span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Submit Section -->
            <div class="p-6 border-t bg-gray-50">
                <div class="flex justify-between items-center">
                    <a href="{{ route('admin.employees.index') }}" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Employees
                    </a>

                    <div class="flex space-x-3">
                        <button type="reset" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Reset
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700">
                            <i class="fas fa-save mr-2"></i>{{ isset($document) ? 'Update Documents' : 'Save Documents' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
