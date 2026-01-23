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
<!-- //FIXME: CHECK ON THE HEIGHT HERE -->
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
<!-- Loading Overlay -->
<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mr-3"></i>
                <span class="text-gray-700 font-medium">Preparing download...</span>
            </div>
            <div class="mt-2 text-sm text-gray-600 text-center">
                <div>Generating PDF files...</div>
                <div id="progressText" class="mt-1">Starting...</div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<!-- JSZip Utils for AJAX -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip-utils/0.1.0/jszip-utils.min.js"></script>
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

// Function to download all meal cards as a ZIP file
async function downloadAllMealCards() {
    if (employees.length === 0) {
        alert('No employees found to download.');
        return;
    }

    showLoading();

    try {
        const zip = new JSZip();
        let downloadCount = 0;
        let failedCount = 0;

        for (let i = 0; i < employees.length; i++) {
            const employee = employees[i];

            try {
                // Fetch fresh QR data from backend
                const response = await fetch(`${baseUrl}/admin/employees/${employee.id}/qr-data`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    console.error(`Failed to fetch QR data for employee ${employee.id}`);
                    failedCount++;
                    continue;
                }

                const data = await response.json();

                if (!data.success || !data.qr_data) {
                    console.error(`Failed to fetch QR data for employee ${employee.id}`, data);
                    failedCount++;
                    continue;
                }

                // Generate PDF as blob
                const pdfBlob = await generatePDFBlob(data.qr_data, employee);

                if (pdfBlob) {
                    // Sanitize filename - use employee data as fallback
                    let fileName;
                    if (data.qr_data.formal_name) {
                        fileName = `MealCard_${data.qr_data.formal_name.replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;
                    } else if (employee.formal_name) {
                        fileName = `MealCard_${employee.formal_name.replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;
                    } else {
                        fileName = `MealCard_Employee_${employee.id}.pdf`;
                    }

                    zip.file(fileName, pdfBlob);
                    downloadCount++;
                } else {
                    failedCount++;
                }

            } catch (error) {
                console.error(`Error processing employee ${employee.id}:`, error);
                failedCount++;
            }

            // Update loading message
            updateLoadingMessage(`Generating PDFs... (${i + 1}/${employees.length})`);

            // Add a small delay between requests to avoid overwhelming the server
            if (i < employees.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 300));
            }
        }

        if (downloadCount === 0) {
            hideLoading();
            alert('Failed to generate any PDFs. Please try again.');
            return;
        }

        // Generate and download ZIP file
        const zipBlob = await zip.generateAsync({ type: 'blob' });
        const zipUrl = URL.createObjectURL(zipBlob);

        const downloadLink = document.createElement('a');
        downloadLink.href = zipUrl;
        downloadLink.download = `Employee_Meal_Cards_${new Date().toISOString().split('T')[0]}.zip`;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        // Clean up URL object
        setTimeout(() => URL.revokeObjectURL(zipUrl), 100);

        hideLoading();

        let successMessage = `Successfully downloaded ${downloadCount} PDF meal cards as ZIP file!`;
        if (failedCount > 0) {
            successMessage += ` (${failedCount} failed)`;
        }
        alert(successMessage);

    } catch (error) {
        console.error('Error downloading meal cards:', error);
        hideLoading();
        alert('An error occurred while generating the ZIP file. Please try again.');
    }
}

// Helper function to generate PDF as blob instead of downloading
async function generatePDFBlob(qrData, employee) {
    return new Promise((resolve, reject) => {
        try {
            const { jsPDF } = window.jspdf;

            // Set Custom Card Dimensions (600pt x 1050pt)
            const CUSTOM_WIDTH = 600;
            const CUSTOM_HEIGHT = 1050;

            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'pt',
                format: [CUSTOM_WIDTH, CUSTOM_HEIGHT]
            });

            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();

            // SCALED CONTENT SIZES & POSITIONS
            const PADDING = 40;

            // Logo
            const logoWidth = 120;
            const logoHeight = 120;
            const logoX = (pageWidth / 2) - (logoWidth / 2);
            const logoY = 50;

            // Title
            const titleY = logoY + logoHeight + 50;

            // QR Code
            const qrSize = 350;
            const qrX = (pageWidth / 2) - (qrSize / 2);
            const qrY = titleY + 60;

            // Employee Info Box
            const boxWidth = pageWidth;
            const boxHeight = 280;
            const boxX = 0;
            const boxY = qrY + qrSize + 30;

            // Footer
            const footerY = pageHeight - 50;

            // Add background fill
            doc.setFillColor(254, 243, 199);
            doc.rect(0, 0, pageWidth, pageHeight, 'F');

            // Add Reeds Logo
            const addReedsLogo = () => {
                return new Promise((resolveLogo) => {
                    doc.addImage(REEDS_LOGO_BASE64, 'PNG', logoX, logoY, logoWidth, logoHeight);
                    resolveLogo();
                });
            };

            // Add title
            const addTitle = () => {
                doc.setTextColor(234, 88, 12);
                doc.setFontSize(38);
                doc.setFont('helvetica', 'bold');

                const text = "OFFICIAL MEAL CARD";
                doc.text(text, pageWidth / 2, titleY, { align: 'center' });
            };

            // Add QR Code with Brown Border
            const addQRCode = () => {
                return new Promise((resolveQR) => {
                    const tempDiv = document.createElement('div');
                    const tempQrSize = 350;
                    tempDiv.style.width = `${tempQrSize}px`;
                    tempDiv.style.height = `${tempQrSize}px`;
                    document.body.appendChild(tempDiv);

                    new QRCode(tempDiv, {
                        text: qrData.qr_data,
                        width: tempQrSize,
                        height: tempQrSize,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    setTimeout(() => {
                        const canvas = tempDiv.querySelector('canvas');
                        if (canvas) {
                            const qrDataURL = canvas.toDataURL('image/png');

                            // Add brown border
                            const borderThickness = 2;
                            doc.setDrawColor(92, 49, 10);
                            doc.setLineWidth(borderThickness);
                            doc.rect(
                                qrX - borderThickness,
                                qrY - borderThickness,
                                qrSize + borderThickness * 2,
                                qrSize + borderThickness * 2,
                                'D'
                            );

                            doc.addImage(qrDataURL, 'PNG', qrX, qrY, qrSize, qrSize);
                        }

                        document.body.removeChild(tempDiv);
                        resolveQR();
                    }, 100);
                });
            };

            // Add employee information box
            const addEmployeeInfo = () => {
                doc.setFillColor(220, 38, 38);
                doc.roundedRect(boxX, boxY, boxWidth, boxHeight, 0, 0, 'F');

                doc.setTextColor(255, 255, 255);
                doc.setFontSize(30);
                doc.setFont('helvetica', 'normal');

                const lineHeight = 50;
                const textX = boxX + PADDING;
                let currentY = boxY + 50;

                // Use employee data with fallbacks
                const employeeCode = qrData.employee_code || employee.employee_code || 'N/A';
                const formalName = qrData.formal_name || employee.formal_name || 'N/A';
                const designation = qrData.designation || employee.designation || 'N/A';
                const department = qrData.department || (employee.department ? employee.department.name : 'N/A');

                doc.text(`Employee No: ${employeeCode}`, textX, currentY);
                currentY += lineHeight;
                doc.text(`Name: ${formalName}`, textX, currentY);
                currentY += lineHeight;
                doc.text(`Designation: ${designation}`, textX, currentY);
                currentY += lineHeight;
                doc.text(`Department: ${department}`, textX, currentY);
            };

            // Add footer with BizTrak logo
            const addFooter = () => {
                return new Promise((resolveFooter) => {
                    const img = new Image();
                    img.src = BIZTRAK_LOGO_BASE64;

                    img.onload = function() {
                        const logoWidth = 80;
                        const logoHeight = (img.height * logoWidth) / img.width;
                        const logoX = pageWidth - logoWidth - PADDING;
                        const logoY = pageHeight - logoHeight - 40;

                        doc.addImage(BIZTRAK_LOGO_BASE64, 'PNG', logoX, logoY, logoWidth, logoHeight);

                        doc.setTextColor(107, 114, 128);
                        doc.setFontSize(18);
                        doc.setFont('helvetica', 'normal');
                        doc.text('Powered By: www.biztrak.ke', PADDING, logoY + (logoHeight / 2));

                        resolveFooter();
                    };

                    img.onerror = function() {
                        doc.setTextColor(107, 114, 128);
                        doc.setFontSize(18);
                        doc.setFont('helvetica', 'normal');
                        doc.text('Powered By: www.biztrak.ke', PADDING, footerY);
                        resolveFooter();
                    };
                });
            };

            // Execute all steps
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
                    // Get PDF as blob instead of saving
                    const pdfBlob = doc.output('blob');
                    resolve(pdfBlob);
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

// Helper function to update loading message
// Helper function to update loading message
function updateLoadingMessage(message) {
    const loadingText = document.querySelector('#loadingOverlay span');
    const progressText = document.querySelector('#progressText');

    if (loadingText) {
        loadingText.textContent = 'Preparing download...';
    }
    if (progressText) {
        progressText.textContent = message;
    }
}


// Helper function to generate PDF as blob instead of downloading
async function generatePDFBlob(qrData, employee) {
    return new Promise((resolve, reject) => {
        try {
            const { jsPDF } = window.jspdf;

            // Set Custom Card Dimensions (600pt x 1050pt)
            const CUSTOM_WIDTH = 600;
            const CUSTOM_HEIGHT = 1050;

            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'pt',
                format: [CUSTOM_WIDTH, CUSTOM_HEIGHT]
            });

            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();

            // SCALED CONTENT SIZES & POSITIONS
            const PADDING = 40;

            // Logo
            const logoWidth = 120;
            const logoHeight = 120;
            const logoX = (pageWidth / 2) - (logoWidth / 2);
            const logoY = 50;

            // Title
            const titleY = logoY + logoHeight + 50;

            // QR Code
            const qrSize = 350;
            const qrX = (pageWidth / 2) - (qrSize / 2);
            const qrY = titleY + 60;

            // Employee Info Box
            const boxWidth = pageWidth;
            const boxHeight = 280;
            const boxX = 0;
            const boxY = qrY + qrSize + 30;

            // Footer
            const footerY = pageHeight - 50;

            // Add background fill
            doc.setFillColor(254, 243, 199);
            doc.rect(0, 0, pageWidth, pageHeight, 'F');

            // Add Reeds Logo
            const addReedsLogo = () => {
                return new Promise((resolveLogo) => {
                    doc.addImage(REEDS_LOGO_BASE64, 'PNG', logoX, logoY, logoWidth, logoHeight);
                    resolveLogo();
                });
            };

            // Add title
            const addTitle = () => {
                doc.setTextColor(234, 88, 12);
                doc.setFontSize(38);
                doc.setFont('helvetica', 'bold');

                const text = "OFFICIAL MEAL CARD";
                doc.text(text, pageWidth / 2, titleY, { align: 'center' });
            };

            // Add QR Code with Brown Border
            const addQRCode = () => {
                return new Promise((resolveQR) => {
                    const tempDiv = document.createElement('div');
                    const tempQrSize = 350;
                    tempDiv.style.width = `${tempQrSize}px`;
                    tempDiv.style.height = `${tempQrSize}px`;
                    document.body.appendChild(tempDiv);

                    new QRCode(tempDiv, {
                        text: qrData.qr_data,
                        width: tempQrSize,
                        height: tempQrSize,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    setTimeout(() => {
                        const canvas = tempDiv.querySelector('canvas');
                        if (canvas) {
                            const qrDataURL = canvas.toDataURL('image/png');

                            // Add brown border
                            const borderThickness = 2;
                            doc.setDrawColor(92, 49, 10);
                            doc.setLineWidth(borderThickness);
                            doc.rect(
                                qrX - borderThickness,
                                qrY - borderThickness,
                                qrSize + borderThickness * 2,
                                qrSize + borderThickness * 2,
                                'D'
                            );

                            doc.addImage(qrDataURL, 'PNG', qrX, qrY, qrSize, qrSize);
                        }

                        document.body.removeChild(tempDiv);
                        resolveQR();
                    }, 100);
                });
            };

            // Add employee information box
            const addEmployeeInfo = () => {
                doc.setFillColor(220, 38, 38);
                doc.roundedRect(boxX, boxY, boxWidth, boxHeight, 0, 0, 'F');

                doc.setTextColor(255, 255, 255);
                doc.setFontSize(30);
                doc.setFont('helvetica', 'normal');

                const lineHeight = 50;
                const textX = boxX + PADDING;
                let currentY = boxY + 50;

                doc.text(`Employee No: ${qrData.employee_code}`, textX, currentY);
                currentY += lineHeight;
                doc.text(`Name: ${qrData.formal_name}`, textX, currentY);
                currentY += lineHeight;
                doc.text(`Designation: ${qrData.designation}`, textX, currentY);
                currentY += lineHeight;
                doc.text(`Department: ${qrData.department}`, textX, currentY);
            };

            // Add footer with BizTrak logo
            const addFooter = () => {
                return new Promise((resolveFooter) => {
                    const img = new Image();
                    img.src = BIZTRAK_LOGO_BASE64;

                    img.onload = function() {
                        const logoWidth = 80;
                        const logoHeight = (img.height * logoWidth) / img.width;
                        const logoX = pageWidth - logoWidth - PADDING;
                        const logoY = pageHeight - logoHeight - 40;

                        doc.addImage(BIZTRAK_LOGO_BASE64, 'PNG', logoX, logoY, logoWidth, logoHeight);

                        doc.setTextColor(107, 114, 128);
                        doc.setFontSize(18);
                        doc.setFont('helvetica', 'normal');
                        doc.text('Powered By: www.biztrak.ke', PADDING, logoY + (logoHeight / 2));

                        resolveFooter();
                    };

                    img.onerror = function() {
                        doc.setTextColor(107, 114, 128);
                        doc.setFontSize(18);
                        doc.setFont('helvetica', 'normal');
                        doc.text('Powered By: www.biztrak.ke', PADDING, footerY);
                        resolveFooter();
                    };
                });
            };

            // Execute all steps
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
                    // Get PDF as blob instead of saving
                    const pdfBlob = doc.output('blob');
                    resolve(pdfBlob);
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

// Helper function to update loading message
function updateLoadingMessage(message) {
    const loadingText = document.querySelector('#loadingOverlay span');
    if (loadingText) {
        loadingText.textContent = message;
    }
}
    // Helper function to generate and download a single PDF

    async function generateAndDownloadPDF(qrData, employee) {
        return new Promise((resolve, reject) => {
            try {
                const { jsPDF } = window.jspdf;

                // ---  Set Custom Card Dimensions (600pt x 1050pt) ---
                const CUSTOM_WIDTH = 600;
                const CUSTOM_HEIGHT = 1050;

                const doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'pt',
                    format: [CUSTOM_WIDTH, CUSTOM_HEIGHT] // [width, height] in points
                });

                const pageWidth = doc.internal.pageSize.getWidth();  // 600 pt
                const pageHeight = doc.internal.pageSize.getHeight(); // 1050 pt

                // --- SCALED CONTENT SIZES & POSITIONS (Adjusted for 600x1050) ---
                const PADDING = 40;

                // Logo
                const logoWidth = 120;
                const logoHeight = 120;
                const logoX = (pageWidth / 2) - (logoWidth / 2); // Center logo
                const logoY = 50; // Moved up slightly

                // Title (MOVED UP)
                const titleY = logoY + logoHeight + 50; // Closer to the logo

                // QR Code
                const qrSize = 350;
                const qrX = (pageWidth / 2) - (qrSize / 2); // Center QR
                const qrY = titleY + 60; // Start below the title (moved up)

                // Employee Info Box (FULL WIDTH)
                const boxWidth = pageWidth; // Full width
                const boxHeight = 280;
                const boxX = 0; // Starts at 0
                const boxY = qrY + qrSize + 30; // Start below the QR code

                // Footer (MOVED DOWN TO CREATE SPACE)
                const footerY = pageHeight - 50;
                // -----------------------------------------------------------------

                // Add background fill for the entire card
                doc.setFillColor(254, 243, 199); // amber-50
                doc.rect(0, 0, pageWidth, pageHeight, 'F');

                // Add Reeds Africa Logo
                const addReedsLogo = () => {
                    return new Promise((resolveLogo) => {
                        // The logoWidth, logoHeight, logoX, and logoY variables
                        // from the outer scope are used here.

                        // 🛑 Direct addImage call using the Base64 constant
                        doc.addImage(REEDS_LOGO_BASE64, 'PNG', logoX, logoY, logoWidth, logoHeight);

                        resolveLogo();
                    });
                };

                // Add title
                const addTitle = () => {
                    doc.setTextColor(234, 88, 12); // orange-600
                    doc.setFontSize(38);
                    doc.setFont('helvetica', 'bold');

                    const text = "OFFICIAL MEAL CARD";
                    doc.text(text, pageWidth / 2, titleY, { align: 'center' });
                };

                // Add QR Code (with Brown Border)
                const addQRCode = () => {
                    return new Promise((resolveQR) => {
                        // Create a temporary container for QR code
                        const tempDiv = document.createElement('div');
                        const tempQrSize = 350;
                        tempDiv.style.width = `${tempQrSize}px`;
                        tempDiv.style.height = `${tempQrSize}px`;
                        document.body.appendChild(tempDiv);

                        // Generate QR code
                        new QRCode(tempDiv, {
                            text: qrData.qr_data,
                            width: tempQrSize,
                            height: tempQrSize,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });

                        // Wait for QR code to render
                        setTimeout(() => {
                            const canvas = tempDiv.querySelector('canvas');
                            if (canvas) {
                                const qrDataURL = canvas.toDataURL('image/png');

                                // 🛑 ADD BROWN BORDER
                                const borderThickness = 2; // 2 pt border
                                doc.setDrawColor(92, 49, 10); // Dark Brown (or a suitable brown RGB)
                                doc.setLineWidth(borderThickness);
                                doc.rect(
                                    qrX - borderThickness,
                                    qrY - borderThickness,
                                    qrSize + borderThickness * 2,
                                    qrSize + borderThickness * 2,
                                    'D' // Draw mode
                                );

                                // Add QR image inside the border
                                doc.addImage(qrDataURL, 'PNG', qrX, qrY, qrSize, qrSize);
                            }

                            document.body.removeChild(tempDiv);
                            resolveQR();
                        }, 100);
                    });
                };

                // Add employee information box (FULL WIDTH)
                const addEmployeeInfo = () => {
                    // Red box background
                    doc.setFillColor(220, 38, 38); // red-600
                    //  BOX SPANS FULL WIDTH (boxX=0, boxWidth=pageWidth)
                    doc.roundedRect(boxX, boxY, boxWidth, boxHeight, 0, 0, 'F'); // Removed corner radius for full width

                    // White text
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(30);
                    doc.setFont('helvetica', 'normal');

                    const lineHeight = 50;
                    const textX = boxX + PADDING; // Text aligned 40pt from the left edge (padding)
                    let currentY = boxY + 50;

                    doc.text(`Employee No: ${qrData.employee_code}`, textX, currentY);
                    currentY += lineHeight;
                    doc.text(`Name: ${qrData.formal_name}`, textX, currentY);
                    currentY += lineHeight;
                    doc.text(`Designation: ${qrData.designation}`, textX, currentY);
                    currentY += lineHeight;
                    doc.text(`Department: ${qrData.department}`, textX, currentY);
                };

                // Add footer with BizTrak logo
                const addFooter = () => {
                    return new Promise((resolveFooter) => {
                        //  We can still use a temporary Image object to calculate dimensions,
                        // but we use the Base64 string for the PDF itself.
                        const img = new Image();
                        img.src = BIZTRAK_LOGO_BASE64;

                        // Calculation must still happen inside onload,
                        // as the browser needs to read the Base64 image to get its dimensions.
                        img.onload = function() {
                            const logoWidth = 80;
                            const logoHeight = (img.height * logoWidth) / img.width;
                            const logoX = pageWidth - logoWidth - PADDING;
                            const logoY = pageHeight - logoHeight - 40;
                            const bottomGap = 2; // The desired gap from the bottom edge

                            //  Direct addImage call using the Base64 constant
                            doc.addImage(BIZTRAK_LOGO_BASE64, 'PNG', logoX, logoY, logoWidth, logoHeight);

                            // Powered by text (left aligned)
                            doc.setTextColor(107, 114, 128); // gray-500
                            doc.setFontSize(18);
                            doc.setFont('helvetica', 'normal');
                            doc.text('Powered By: www.biztrak.ke', PADDING, logoY + (logoHeight / 2));

                            resolveFooter();
                        };

                        // If the image fails to load (very unlikely with Base64), fall back to text
                        img.onerror = function() {
                            doc.setTextColor(107, 114, 128);
                            doc.setFontSize(18);
                            doc.setFont('helvetica', 'normal');
                            doc.text('Powered By: www.biztrak.ke', PADDING, footerY);
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

<!-- base 64 images -->
 <script>
const REEDS_LOGO_BASE64 = "data:image/png;base64,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"
const BIZTRAK_LOGO_BASE64 = "data:image/png;base64,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";
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
