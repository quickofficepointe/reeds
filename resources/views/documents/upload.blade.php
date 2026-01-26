<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - Reeds Africa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-upload text-blue-600 text-2xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Document Upload Portal</h1>
            <p class="text-gray-600 mt-2">Please upload your required documents</p>
        </div>

        <!-- Employee Info Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $employee->formal_name }}</h3>
                    <p class="text-gray-600">Employee Code: {{ $employee->employee_code }}</p>
                    <p class="text-gray-600 text-sm">Department: {{ $employee->department->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <form id="documentUploadForm" class="space-y-6">
            @csrf

            <!-- Next of Kin Section -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user-friends text-blue-600 mr-2"></i>
                    Next of Kin Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="next_of_kin_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship *</label>
                        <select name="next_of_kin_relationship" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Relationship</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Parent">Parent</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Child">Child</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" name="next_of_kin_phone" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="254712345678">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="next_of_kin_email"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="email@example.com">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="next_of_kin_address" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Enter address..."></textarea>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-file-alt text-green-600 mr-2"></i>
                    Required Documents
                </h3>
                <p class="text-gray-600 mb-6">Please upload the following documents (JPG, PNG, or PDF, max 2MB each):</p>

                <div class="space-y-4">
                    @php
                    $documents = [
                        'national_id_photo' => ['label' => 'National ID Photo *', 'required' => true],
                        'passport_photo' => ['label' => 'Passport Photo', 'required' => false],
                        'passport_size_photo' => ['label' => 'Passport Size Photo *', 'required' => true],
                        'nssf_card_photo' => ['label' => 'NSSF Card Photo *', 'required' => true],
                        'sha_card_photo' => ['label' => 'SHA Card Photo *', 'required' => true],
                        'kra_certificate_photo' => ['label' => 'KRA Certificate Photo *', 'required' => true],
                    ];
                    @endphp

                    @foreach($documents as $field => $info)
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition duration-150">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $info['label'] }}
                            @if($info['required'])
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <input type="file" name="{{ $field }}"
                                       {{ $info['required'] ? 'required' : '' }}
                                       accept=".jpg,.jpeg,.png,.pdf"
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-info-circle"></i>
                                <span>Max 2MB</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Submit Section -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shield-alt text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-700">Your documents are securely stored and will only be accessible to authorized HR personnel.</p>
                        </div>
                    </div>
                    <div>
                        <button type="submit"
                                class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300 shadow-md flex items-center space-x-2">
                            <i class="fas fa-upload"></i>
                            <span>Submit Documents</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl p-8 text-center">
                <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-lg font-semibold text-gray-900">Uploading Documents...</p>
                <p class="text-gray-600 mt-2">Please wait while we process your files</p>
            </div>
        </div>

        <!-- Error Modal -->
        <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
                <div class="text-center mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900" id="errorTitle">Upload Error</h3>
                    <p class="text-gray-600 mt-2" id="errorMessage"></p>
                </div>
                <div class="text-center">
                    <button onclick="document.getElementById('errorModal').classList.add('hidden')"
                            class="bg-red-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-red-700 transition duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('documentUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Show loading
            loadingOverlay.classList.remove('hidden');

            fetch("{{ route('documents.process-upload', ['token' => $invitation->token]) }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingOverlay.classList.add('hidden');

                if (data.success) {
                    // Redirect to success page
                    window.location.href = data.redirect_url;
                } else if (data.error) {
                    showError(data.error);
                } else if (data.errors) {
                    // Handle validation errors
                    let errorMessages = [];
                    Object.values(data.errors).forEach(errors => {
                        errorMessages.push(...errors);
                    });
                    showError(errorMessages.join('<br>'));
                }
            })
            .catch(error => {
                loadingOverlay.classList.add('hidden');
                console.error('Error:', error);
                showError('An error occurred. Please try again.');
            });
        });

        function showError(message) {
            document.getElementById('errorMessage').innerHTML = message;
            document.getElementById('errorModal').classList.remove('hidden');
        }

        // File size validation
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file && file.size > 2 * 1024 * 1024) { // 2MB
                    showError('File size must be less than 2MB');
                    this.value = '';
                }
            });
        });
    </script>
</body>
</html>
