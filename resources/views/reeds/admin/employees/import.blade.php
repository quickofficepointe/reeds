@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-text-black">Import Employees</h1>
            <p class="text-gray-600 mt-2">Upload Excel/CSV file to import employee data</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('admin.employees.export') }}" class="bg-secondary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#1e7a9e] transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-download"></i>
                <span>Export Template</span>
            </a>
            <a href="{{ route('admin.employees.index') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-600 transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Employees</span>
            </a>
        </div>
    </div>

    <!-- Import Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-text-black mb-2">Upload Employee Data</h2>
            <p class="text-gray-600">Upload an Excel or CSV file containing employee information.</p>
        </div>

        <!-- File Upload Area -->
        <div id="uploadArea" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-secondary-blue transition duration-150 mb-6">
            <div class="max-w-md mx-auto">
                <i class="fas fa-file-excel text-4xl text-green-500 mb-4"></i>
                <h3 class="text-lg font-semibold text-text-black mb-2">Drop your file here</h3>
                <p class="text-gray-500 text-sm mb-4">or click to browse</p>
                <input type="file" id="fileInput" class="hidden" accept=".xlsx,.xls,.csv">
                <button onclick="document.getElementById('fileInput').click()" class="bg-primary-red text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300">
                    Choose File
                </button>
                <p class="text-xs text-gray-400 mt-3">Supported formats: XLSX, XLS, CSV (Max: 10MB)</p>
            </div>
        </div>

        <!-- Selected File Info -->
        <div id="fileInfo" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-file-excel text-blue-500 text-xl"></i>
                    <div>
                        <p class="font-medium text-text-black" id="fileName"></p>
                        <p class="text-sm text-gray-500" id="fileSize"></p>
                    </div>
                </div>
                <button onclick="removeFile()" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Import Progress -->
        <div id="importProgress" class="hidden mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-text-black">Importing...</span>
                <span id="progressText" class="text-sm text-gray-600">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progressBar" class="bg-primary-red h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <!-- Import Results -->
        <div id="importResults" class="hidden mb-6 p-4 rounded-lg"></div>

        <!-- Import Button -->
        <div class="flex justify-end space-x-3">
            <button onclick="downloadTemplate()" class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-download"></i>
                <span>Download Template</span>
            </button>
            <button id="importBtn" onclick="processImport()" class="bg-primary-red text-white px-8 py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <i class="fas fa-upload"></i>
                <span>Import Employees</span>
            </button>
        </div>
    </div>

    <!-- Instructions & Department Mapping -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Instructions -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Import Instructions</h3>
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-primary-red rounded-full flex items-center justify-center mt-1 flex-shrink-0">
                        <span class="text-white text-xs font-bold">1</span>
                    </div>
                    <div>
                        <p class="font-medium text-text-black">Required Fields</p>
                        <p class="text-sm text-gray-600">Employee Code, First Name, Last Name, Department, Unit</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-primary-red rounded-full flex items-center justify-center mt-1 flex-shrink-0">
                        <span class="text-white text-xs font-bold">2</span>
                    </div>
                    <div>
                        <p class="font-medium text-text-black">File Format</p>
                        <p class="text-sm text-gray-600">Use Excel (.xlsx, .xls) or CSV format with headers</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-primary-red rounded-full flex items-center justify-center mt-1 flex-shrink-0">
                        <span class="text-white text-xs font-bold">3</span>
                    </div>
                    <div>
                        <p class="font-medium text-text-black">Column Headers</p>
                        <p class="text-sm text-gray-600">Use exact column names from template</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-primary-red rounded-full flex items-center justify-center mt-1 flex-shrink-0">
                        <span class="text-white text-xs font-bold">4</span>
                    </div>
                    <div>
                        <p class="font-medium text-text-black">New Fields</p>
                        <p class="text-sm text-gray-600">Email and Phone are optional fields</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-primary-red rounded-full flex items-center justify-center mt-1 flex-shrink-0">
                        <span class="text-white text-xs font-bold">5</span>
                    </div>
                    <div>
                        <p class="font-medium text-text-black">Unit Required</p>
                        <p class="text-sm text-gray-600">Make sure units exist in the system before importing</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department & Unit Mapping -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Available Departments & Units</h3>
            <div class="space-y-4">
                <!-- Departments -->
                <div>
                    <h4 class="font-medium text-text-black mb-2">Departments:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                        @php
                            $departments = \App\Models\Department::active()->get();
                        @endphp
                        @if($departments->count() > 0)
                            @foreach($departments as $department)
                                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">{{ $department->name }}</span>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">ID: {{ $department->id }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    No departments found
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Units -->
                <div>
                    <h4 class="font-medium text-text-black mb-2">Units:</h4>
                    <div class="grid grid-cols-1 gap-2">
                        @php
                            $units = \App\Models\Unit::active()->get();
                        @endphp
                        @if($units->count() > 0)
                            @foreach($units as $unit)
                                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">{{ $unit->name }}</span>
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">ID: {{ $unit->id }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    No units found. Please create units before importing.
                                </p>
                                <a href="{{ route('admin.units.index') }}" class="text-xs text-blue-600 hover:underline mt-1 block">
                                    Create Units →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CSV Format Example -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mt-6">
        <h3 class="text-lg font-semibold text-text-black mb-4">Expected CSV Format</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-gray-50 rounded-lg overflow-hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">employee_code</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">payroll_no</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">first_name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">middle_name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">last_name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">phone</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">icard_number</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">department</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">unit</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">designation</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">gender</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">employment_type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">EMP001</td>
                        <td class="px-4 py-2 text-sm text-gray-900">001</td>
                        <td class="px-4 py-2 text-sm text-gray-900">John</td>
                        <td class="px-4 py-2 text-sm text-gray-900">A.</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Doe</td>
                        <td class="px-4 py-2 text-sm text-gray-900">john.doe@company.com</td>
                        <td class="px-4 py-2 text-sm text-gray-900">254712345678</td>
                        <td class="px-4 py-2 text-sm text-gray-900">1234567</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Internal Finishing</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Blue</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Manager</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Male</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Regular</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">EMP002</td>
                        <td class="px-4 py-2 text-sm text-gray-900">002</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Jane</td>
                        <td class="px-4 py-2 text-sm text-gray-900">M.</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Smith</td>
                        <td class="px-4 py-2 text-sm text-gray-900">jane.smith@company.com</td>
                        <td class="px-4 py-2 text-sm text-gray-900">254723456789</td>
                        <td class="px-4 py-2 text-sm text-gray-900">7654321</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Shell</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Blue</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Engineer</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Female</td>
                        <td class="px-4 py-2 text-sm text-gray-900">Contract</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let selectedFile = null;

    // File input change handler
    document.getElementById('fileInput').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            selectedFile = this.files[0];
            displayFileInfo(selectedFile);
            document.getElementById('importBtn').disabled = false;
        }
    });

    // Drag and drop functionality
    const uploadArea = document.getElementById('uploadArea');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadArea.classList.add('border-secondary-blue', 'bg-blue-50');
    }

    function unhighlight() {
        uploadArea.classList.remove('border-secondary-blue', 'bg-blue-50');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            selectedFile = files[0];
            displayFileInfo(selectedFile);
            document.getElementById('importBtn').disabled = false;
        }
    }

    function displayFileInfo(file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        document.getElementById('fileInfo').classList.remove('hidden');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function removeFile() {
        selectedFile = null;
        document.getElementById('fileInput').value = '';
        document.getElementById('fileInfo').classList.add('hidden');
        document.getElementById('importBtn').disabled = true;
        hideProgress();
        clearResults();
    }

    function hideProgress() {
        document.getElementById('importProgress').classList.add('hidden');
    }

    function clearResults() {
        document.getElementById('importResults').classList.add('hidden');
        document.getElementById('importResults').innerHTML = '';
    }

    function processImport() {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('file', selectedFile);

        // Show loading state
        const importBtn = document.getElementById('importBtn');
        importBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Importing...</span>';
        importBtn.disabled = true;

        // Show progress
        document.getElementById('importProgress').classList.remove('hidden');
        updateProgress(0, 'Starting import...');

        fetch('{{ route("admin.employees.process-import") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProgress(100, 'Import completed!');
                showResults('success', data.success, data);
            } else if (data.error) {
                showResults('error', data.error, data);
            }

            // Reset button
            importBtn.innerHTML = '<i class="fas fa-upload"></i><span>Import Employees</span>';
            importBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            showResults('error', 'An error occurred during import: ' + error.message);
            importBtn.innerHTML = '<i class="fas fa-upload"></i><span>Import Employees</span>';
            importBtn.disabled = false;
        });
    }

    function updateProgress(percent, text) {
        document.getElementById('progressBar').style.width = percent + '%';
        document.getElementById('progressText').textContent = text;
    }

    function showResults(type, message, data = {}) {
        const resultsDiv = document.getElementById('importResults');
        resultsDiv.classList.remove('hidden');

        let html = '';

        if (type === 'success') {
            html = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        <div>
                            <h4 class="font-semibold text-green-800">Import Successful!</h4>
                            <p class="text-green-700">${message}</p>
                            ${data.imported_count ? `<p class="text-sm text-green-600 mt-1">Imported: ${data.imported_count} employees</p>` : ''}
                            ${data.skipped_count ? `<p class="text-sm text-green-600">Skipped: ${data.skipped_count} records</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
        } else {
            html = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        <div>
                            <h4 class="font-semibold text-red-800">Import Failed</h4>
                            <p class="text-red-700">${message}</p>
                            ${data.errors && data.errors.length ? `
                                <div class="mt-3">
                                    <h5 class="font-medium text-red-800 mb-2">Errors:</h5>
                                    <div class="max-h-32 overflow-y-auto">
                                        ${data.errors.map(error => `<p class="text-sm text-red-600">• ${error}</p>`).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        resultsDiv.innerHTML = html;
    }

    function downloadTemplate() {
        const headers = [
            'employee_code', 'payroll_no', 'first_name', 'middle_name', 'last_name',
            'email', 'phone', 'icard_number', 'department', 'unit',
            'designation', 'gender', 'employment_type'
        ];

        const exampleData = [
            'EMP001', '001', 'John', 'A.', 'Doe',
            'john.doe@company.com', '254712345678', '1234567',
            'Internal Finishing', 'Blue', 'Manager', 'Male', 'Regular'
        ];

        let csvContent = headers.join(',') + '\n' + exampleData.join(',');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'employee_import_template.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
</script>
@endsection
