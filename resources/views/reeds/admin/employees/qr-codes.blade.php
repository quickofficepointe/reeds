@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="mb-4 lg:mb-0">
                <h1 class="text-3xl font-bold text-gray-900">Employee QR Codes</h1>
                <p class="text-gray-600 mt-2">Manage and download employee meal card QR codes</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.employees.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Employees
                </a>
                <button onclick="downloadAllMealCards()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-download mr-2"></i>
                    Download All
                </button>
                <button onclick="printQRCodes()"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-print mr-2"></i>
                    Print All
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-qrcode text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total QR Codes</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $employees->total() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Employees</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $employees->where('is_active', true)->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-print text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ready for Printing</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $employees->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Codes Grid -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Generated QR Codes
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Click on any card to preview and download individual meal cards
            </p>
        </div>

        <div class="p-6">
            @if($employees->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="qrCodesGrid">
                    @foreach($employees as $employee)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                        <!-- QR Code Container -->
                        <div class="p-4 border-b border-gray-100">
                            <div class="bg-gray-50 rounded-lg p-3 flex items-center justify-center h-40">
                                <div id="qrcode-{{ $employee->id }}" class="qrcode-container flex items-center justify-center">
                                    <!-- QR code will be dynamically generated here -->
                                </div>
                            </div>
                        </div>

                        <!-- Employee Details -->
                        <div class="p-4">
                            <h4 class="font-semibold text-gray-900 text-sm mb-1 truncate">{{ $employee->formal_name }}</h4>
                            <p class="text-xs text-gray-500 mb-2">{{ $employee->employee_code }}</p>
                            <p class="text-xs text-gray-600 mb-3 truncate">{{ $employee->department->name ?? 'N/A' }}</p>

                            <!-- Status Badge -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <!-- Action Button -->
                            <button onclick="downloadMealCard({{ $employee->id }})"
                                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <i class="fas fa-download mr-2 text-xs"></i>
                                Download Card
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($employees->hasPages())
                <div class="mt-6 border-t border-gray-200 pt-4">
                    {{ $employees->links() }}
                </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-qrcode text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No QR codes generated</h3>
                    <p class="text-gray-500 mb-6">Generate QR codes for employees to see them here.</p>
                    <a href="{{ route('admin.employees.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Go to Employees
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Meal Card Preview Modal -->
<div id="mealCardPreviewModal" class="fixed inset-0 overflow-y-auto z-50 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeMealCardPreview()"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Meal Card Preview</h3>
                            <button type="button"
                                    onclick="closeMealCardPreview()"
                                    class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <!-- Meal Card Preview -->
                        <div id="mealCardContainer" class="bg-gradient-to-br from-amber-50 to-amber-100 shadow-2xl overflow-hidden mx-auto"
                             style="width: 600px; height: 1050px;">

                            <div class="p-12 flex flex-col items-center h-full">
                                <!-- Logo -->
                                <div class="mb-8">
                                    <img src="{{ asset('Assets/images/Reeds_Logo.png') }}"
                                         alt="Reeds Africa Consult Logo"
                                         class="h-24 mx-auto object-contain">
                                </div>

                                <!-- Title -->
                                <h1 class="text-3xl font-bold tracking-wider text-gray-900 mt-8 mb-12 text-center">
                                    OFFICIAL MEAL CARD
                                </h1>

                                <!-- QR Code Container -->
                                <div class="w-[450px] h-[450px] border-[15px] border-amber-900 bg-white p-4 flex items-center justify-center mb-8">
                                    <div class="w-full h-full flex items-center justify-center text-center">
                                        <div id="previewQrCode" class="flex items-center justify-center">
                                            <!-- Preview QR code will be generated here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Details -->
                                <div class="bg-red-600 text-white p-8 w-full rounded-lg">
                                    <div class="space-y-3">
                                        <p class="text-xl font-light">
                                            <span class="font-semibold">Employee No:</span>
                                            <span id="previewEmpNo" class="ml-2 italic"></span>
                                        </p>
                                        <p class="text-xl font-light">
                                            <span class="font-semibold">Name:</span>
                                            <span id="previewEmpName" class="ml-2 italic"></span>
                                        </p>
                                        <p class="text-xl font-light">
                                            <span class="font-semibold">Designation:</span>
                                            <span id="previewEmpPos" class="ml-2 italic"></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="flex justify-between items-center w-full mt-auto pt-4 border-t border-gray-300">
                                    <p class="text-sm text-gray-600">
                                        Powered By: <span class="font-semibold text-blue-600">www.biztrak.ke</span>
                                    </p>
                                    <div class="w-16 h-8 bg-blue-600 text-white flex items-center justify-center text-xs font-bold rounded">
                                        BizTrak
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Download Button -->
                        <div class="mt-6 flex justify-center">
                            <button id="downloadPreviewCardBtn"
                                    class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <i class="fas fa-download mr-2"></i>
                                Download Meal Card
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mr-3"></i>
                <span class="text-gray-700 font-medium">Processing your request...</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- QRCode.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- html2canvas CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    // Employee data from backend
    const employees = {!! json_encode($employees->items()) !!};
    const baseUrl = '{{ url("/") }}';
    const csrfToken = '{{ csrf_token() }}';

    // Show loading overlay
    function showLoading() {
        document.getElementById('loadingOverlay').classList.remove('hidden');
    }

    // Hide loading overlay
    function hideLoading() {
        document.getElementById('loadingOverlay').classList.add('hidden');
    }

    // Generate QR codes for each employee dynamically
    document.addEventListener('DOMContentLoaded', function() {
        employees.forEach(employee => {
            generateEmployeeQRCode(employee.id);
        });
    });

    // Function to generate QR code for a specific employee
    async function generateEmployeeQRCode(employeeId) {
        const qrContainer = document.getElementById(`qrcode-${employeeId}`);
        if (!qrContainer) return;

        try {
            // Show loading state
            qrContainer.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-500">Loading QR...</p>
                </div>
            `;

            // Fetch QR data from backend
            const response = await fetch(`${baseUrl}/admin/employees/${employeeId}/qr-data`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.qr_data) {
                // Clear container
                qrContainer.innerHTML = '';

                // Generate QR code with dynamic data
                new QRCode(qrContainer, {
                    text: data.qr_data.qr_data,
                    width: 120,
                    height: 120,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else {
                throw new Error(data.error || 'Failed to load QR data');
            }
        } catch (error) {
            console.error('Error generating QR code for employee', employeeId, ':', error);
            qrContainer.innerHTML = `
                <div class="text-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-lg mb-1"></i>
                    <p class="text-xs">Failed to load</p>
                </div>
            `;
        }
    }

    // Function to download individual meal card
    async function downloadMealCard(employeeId) {
        const employee = employees.find(emp => emp.id === employeeId);
        if (!employee) {
            alert('Employee not found!');
            return;
        }

        showLoading();

        try {
            // Fetch fresh QR data from backend
            const response = await fetch(`${baseUrl}/admin/employees/${employeeId}/qr-data`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error('Failed to fetch QR data');
            }

            // Update preview modal with dynamic data
            document.getElementById('previewEmpNo').textContent = data.qr_data.employee_code;
            document.getElementById('previewEmpName').textContent = data.qr_data.formal_name;
            document.getElementById('previewEmpPos').textContent = data.qr_data.designation;

            // Generate QR code for preview
            const previewContainer = document.getElementById('previewQrCode');
            previewContainer.innerHTML = '';
            new QRCode(previewContainer, {
                text: data.qr_data.qr_data,
                width: 380,
                height: 380,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Set up download button for this specific employee
            document.getElementById('downloadPreviewCardBtn').onclick = function() {
                downloadEmployeeCard(employee, data.qr_data);
            };

            // Show preview modal
            document.getElementById('mealCardPreviewModal').classList.remove('hidden');

        } catch (error) {
            console.error('Error fetching QR data:', error);
            alert('Failed to load employee data. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Function to download all meal cards
    async function downloadAllMealCards() {
        if (employees.length === 0) {
            alert('No employees found to download.');
            return;
        }

        showLoading();

        try {
            for (let i = 0; i < employees.length; i++) {
                const employee = employees[i];

                // Fetch fresh QR data from backend
                const response = await fetch(`${baseUrl}/admin/employees/${employee.id}/qr-data`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    console.error(`Failed to fetch QR data for employee ${employee.id}`);
                    continue;
                }

                const qrData = await response.json();

                if (!qrData.success) {
                    console.error(`Failed to fetch QR data for employee ${employee.id}`);
                    continue;
                }

                await generateAndDownloadCard(qrData.qr_data, employee);

                // Add a small delay between downloads
                if (i < employees.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
            }

            alert(`Successfully downloaded ${employees.length} meal cards!`);

        } catch (error) {
            console.error('Error downloading meal cards:', error);
            alert('An error occurred while downloading the meal cards. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Helper function to generate and download a single card
    async function generateAndDownloadCard(qrData, employee) {
        return new Promise((resolve, reject) => {
            try {
                // Create a temporary meal card
                const tempCard = document.createElement('div');
                tempCard.className = 'meal-card';
                tempCard.style.width = '600px';
                tempCard.style.height = '1050px';
                tempCard.style.background = 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)';

                tempCard.innerHTML = `
                    <div style="padding: 3rem; display: flex; flex-direction: column; align-items: center; height: 100%;">
                        <div style="margin-bottom: 2rem;">
                            <img src="{{ asset('Assets/images/Reeds_Logo.png') }}"
                                 alt="Reeds Africa Consult Logo"
                                 style="height: 6rem; margin: 0 auto; object-fit: contain;">
                        </div>

                        <h1 style="font-size: 1.875rem; font-weight: bold; letter-spacing: 0.1em; color: #1f2937; margin-top: 2rem; margin-bottom: 3rem; text-align: center;">
                            OFFICIAL MEAL CARD
                        </h1>

                        <div style="width: 450px; height: 450px; border: 15px solid #78350f; background: white; padding: 1rem; display: flex; align-items: center; justify-content: center; margin-bottom: 2rem;">
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; text-align: center;">
                                <div id="tempQrCode-${employee.id}"></div>
                            </div>
                        </div>

                        <div style="background: #dc2626; color: white; padding: 3rem; width: 100%; border-radius: 0.5rem;">
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <p style="font-size: 1.25rem; font-weight: 300;">
                                    <span style="font-weight: 600;">Employee No:</span>
                                    <span style="margin-left: 0.5rem; font-style: italic;">${qrData.employee_code}</span>
                                </p>
                                <p style="font-size: 1.25rem; font-weight: 300;">
                                    <span style="font-weight: 600;">Name:</span>
                                    <span style="margin-left: 0.5rem; font-style: italic;">${qrData.formal_name}</span>
                                </p>
                                <p style="font-size: 1.25rem; font-weight: 300;">
                                    <span style="font-weight: 600;">Designation:</span>
                                    <span style="margin-left: 0.5rem; font-style: italic;">${qrData.designation}</span>
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: auto; padding-top: 1rem; border-top: 1px solid #d1d5db;">
                            <p style="font-size: 0.75rem; color: #6b7280;">
                                Powered By: <span style="font-weight: 600; color: #2563eb;">www.biztrak.ke</span>
                            </p>
                            <div style="width: 4rem; height: 2rem; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; border-radius: 0.25rem;">
                                BizTrak
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(tempCard);

                // Generate QR code
                const qrContainer = document.getElementById(`tempQrCode-${employee.id}`);
                new QRCode(qrContainer, {
                    text: qrData.qr_data,
                    width: 380,
                    height: 380,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });

                // Wait for QR code to render
                setTimeout(() => {
                    html2canvas(tempCard, {
                        scale: 2,
                        useCORS: true,
                        allowTaint: false,
                        backgroundColor: null
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.download = `MealCard_${qrData.formal_name.replace(/\s+/g, '_')}.png`;
                        link.href = canvas.toDataURL('image/png');
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        document.body.removeChild(tempCard);
                        resolve();
                    }).catch(reject);
                }, 500);

            } catch (error) {
                reject(error);
            }
        });
    }

    // Function to download a specific employee's card
    async function downloadEmployeeCard(employee, qrData) {
        showLoading();

        try {
            await generateAndDownloadCard(qrData, employee);
            closeMealCardPreview();
        } catch (error) {
            console.error('Error downloading meal card:', error);
            alert('An error occurred while downloading the meal card. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Function to close the meal card preview
    function closeMealCardPreview() {
        document.getElementById('mealCardPreviewModal').classList.add('hidden');
    }

    // Function to print all QR codes
    function printQRCodes() {
        showLoading();

        // Simulate print functionality
        setTimeout(() => {
            hideLoading();
            alert('Print functionality would generate a PDF with all QR codes. This feature can be implemented with a PDF generation library.');
        }, 1000);
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('mealCardPreviewModal');
        if (event.target === modal) {
            closeMealCardPreview();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMealCardPreview();
        }
    });
</script>

<style>
    .meal-card {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .qrcode-container img {
        border-radius: 8px;
    }

    /* Smooth transitions for modal */
    #mealCardPreviewModal {
        transition: opacity 0.3s ease-in-out;
    }

    #mealCardPreviewModal:not(.hidden) {
        display: block !important;
    }
</style>
@endsection
