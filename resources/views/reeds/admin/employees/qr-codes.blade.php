@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="mb-4 lg:mb-0">
                <h1 class="text-3xl font-bold text-gray-900">Employee QR Codes</h1>
                <p class="text-gray-600 mt-2">Manage and download employee meal card PDFs</p>
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
                    Download All PDFs
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
                            <i class="fas fa-file-pdf text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ready for PDF</dt>
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
                Click on any card to preview and download individual meal card PDFs
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
                                <i class="fas fa-file-pdf mr-2 text-xs"></i>
                                Download PDF
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

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mr-3"></i>
                <span class="text-gray-700 font-medium">Generating PDF...</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- QRCode.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- jsPDF CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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

    // Function to download individual meal card as PDF
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

            // Generate and download PDF
            await generateAndDownloadPDF(data.qr_data, employee);

        } catch (error) {
            console.error('Error downloading meal card:', error);
            alert('Failed to download PDF. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Function to download all meal cards as PDFs
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

                await generateAndDownloadPDF(qrData.qr_data, employee);

                // Add a small delay between downloads
                if (i < employees.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
            }

            alert(`Successfully downloaded ${employees.length} PDF meal cards!`);

        } catch (error) {
            console.error('Error downloading meal cards:', error);
            alert('An error occurred while downloading the PDFs. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Helper function to generate and download a single PDF
    async function generateAndDownloadPDF(qrData, employee) {
        return new Promise((resolve, reject) => {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                // Page dimensions
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();

                // Add background gradient
                doc.setFillColor(254, 243, 199); // amber-50
                doc.rect(0, 0, pageWidth, pageHeight, 'F');

                // Add Reeds Africa Logo (centered at top)
                const addReedsLogo = () => {
                    return new Promise((resolveLogo) => {
                        const img = new Image();
                        img.crossOrigin = 'Anonymous';
                        img.src = 'https://reeds.biztrak.ke/images/Logo-reeds-africa.png';

                        img.onload = function() {
                            const logoWidth = 50;
                            const logoHeight = (img.height * logoWidth) / img.width;
                            const logoX = (pageWidth - logoWidth) / 2;
                            const logoY = 20;

                            doc.addImage(img, 'PNG', logoX, logoY, logoWidth, logoHeight);
                            resolveLogo();
                        };

                        img.onerror = function() {
                            // If logo fails to load, just continue
                            console.warn('Reeds logo failed to load');
                            resolveLogo();
                        };
                    });
                };

                // Add title
                const addTitle = () => {
                    doc.setFontSize(24);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(31, 41, 55); // gray-900
                    doc.text('OFFICIAL MEAL CARD', pageWidth / 2, 100, { align: 'center' });
                };

                // Add QR Code
                const addQRCode = () => {
                    return new Promise((resolveQR) => {
                        // Create a temporary container for QR code
                        const tempDiv = document.createElement('div');
                        tempDiv.style.width = '150px';
                        tempDiv.style.height = '150px';
                        document.body.appendChild(tempDiv);

                        // Generate QR code
                        new QRCode(tempDiv, {
                            text: qrData.qr_data,
                            width: 150,
                            height: 150,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });

                        // Wait for QR code to render
                        setTimeout(() => {
                            const canvas = tempDiv.querySelector('canvas');
                            if (canvas) {
                                const qrDataURL = canvas.toDataURL('image/png');
                                const qrSize = 80;
                                const qrX = (pageWidth - qrSize) / 2;
                                const qrY = 120;

                                doc.addImage(qrDataURL, 'PNG', qrX, qrY, qrSize, qrSize);
                            }

                            document.body.removeChild(tempDiv);
                            resolveQR();
                        }, 100);
                    });
                };

                // Add employee information box
                const addEmployeeInfo = () => {
                    const boxWidth = pageWidth - 40;
                    const boxHeight = 50;
                    const boxX = 20;
                    const boxY = 220;

                    // Red background
                    doc.setFillColor(220, 38, 38); // red-600
                    doc.roundedRect(boxX, boxY, boxWidth, boxHeight, 3, 3, 'F');

                    // White text
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(14);
                    doc.setFont('helvetica', 'normal');

                    const lineHeight = 7;
                    let currentY = boxY + 15;

                    doc.text(`Employee No: ${qrData.employee_code}`, boxX + 10, currentY);
                    currentY += lineHeight;
                    doc.text(`Name: ${qrData.formal_name}`, boxX + 10, currentY);
                    currentY += lineHeight;
                    doc.text(`Designation: ${qrData.designation}`, boxX + 10, currentY);
                };

                // Add footer with BizTrak logo
                const addFooter = () => {
                    return new Promise((resolveFooter) => {
                        const img = new Image();
                        img.crossOrigin = 'Anonymous';
                        img.src = 'https://reeds.biztrak.ke/images/Biztrak-main-logo.png';

                        img.onload = function() {
                            const logoWidth = 30;
                            const logoHeight = (img.height * logoWidth) / img.width;
                            const logoX = pageWidth - logoWidth - 20;
                            const logoY = pageHeight - logoHeight - 20;

                            doc.addImage(img, 'PNG', logoX, logoY, logoWidth, logoHeight);

                            // Powered by text
                            doc.setTextColor(107, 114, 128); // gray-500
                            doc.setFontSize(10);
                            doc.setFont('helvetica', 'normal');
                            doc.text('Powered By: www.biztrak.ke', 20, logoY + (logoHeight / 2));

                            resolveFooter();
                        };

                        img.onerror = function() {
                            // If logo fails to load, just add text
                            doc.setTextColor(107, 114, 128);
                            doc.setFontSize(10);
                            doc.setFont('helvetica', 'normal');
                            doc.text('Powered By: www.biztrak.ke', 20, pageHeight - 15);
                            resolveFooter();
                        };
                    });
                };

                // Execute all steps in sequence
                addReedsLogo()
                    .then(() => {
                        addTitle();
                        return addQRCode();
                    })
                    .then(() => {
                        addEmployeeInfo();
                        return addFooter();
                    })
                    .then(() => {
                        // Save the PDF
                        const fileName = `MealCard_${qrData.formal_name.replace(/\s+/g, '_')}.pdf`;
                        doc.save(fileName);
                        resolve();
                    })
                    .catch(error => {
                        console.error('Error generating PDF:', error);
                        reject(error);
                    });

            } catch (error) {
                reject(error);
            }
        });
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
</script>

<style>
    .qrcode-container img {
        border-radius: 8px;
    }

    /* Smooth transitions */
    #loadingOverlay {
        transition: opacity 0.3s ease-in-out;
    }
</style>
@endsection
