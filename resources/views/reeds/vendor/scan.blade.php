@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-text-black">QR Code Scanner</h1>
            <p class="text-gray-600 mt-2">Scan employee QR codes to record meals</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('vendor.dashboard') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-600 transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Scanner Interface -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <!-- Scanner Container -->
        <div id="scanner-container" class="mb-6">
            <div class="text-center mb-4">
                <div class="w-64 h-64 mx-auto border-4 border-secondary-blue rounded-lg relative overflow-hidden bg-gray-100" id="scanner-preview">
                    <video id="qr-video" class="w-full h-full object-cover" playsinline></video>
                    <div class="scanner-overlay absolute inset-0 border-2 border-white rounded"></div>
                </div>
                <p class="text-gray-600 mt-2">Position QR code within the frame</p>

                <!-- Camera Controls -->
                <div class="mt-4 flex justify-center space-x-4">
                    <button id="start-camera" class="bg-secondary-blue text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 transition duration-300 flex items-center space-x-2">
                        <i class="fas fa-camera"></i>
                        <span>Start Camera</span>
                    </button>
                    <button id="stop-camera" class="bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300 flex items-center space-x-2">
                        <i class="fas fa-stop"></i>
                        <span>Stop Camera</span>
                    </button>
                </div>
            </div>

            <!-- Manual Input Fallback -->
            <div class="border-t pt-4 mt-4">
                <p class="text-center text-gray-600 mb-2">Or enter QR code manually:</p>
                <div class="flex space-x-2 max-w-md mx-auto">
                    <input type="text" id="manual-qr-input" placeholder="Enter QR code" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <button onclick="processManualQR()" class="bg-secondary-blue text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-600 transition duration-300">
                        Submit
                    </button>
                </div>
            </div>
        </div>

        <!-- Scan Result -->
        <div id="scan-result" class="hidden text-center p-4 rounded-lg mb-4"></div>

        <!-- Today's Transactions -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-text-black">Today's Scans</h3>
                <button onclick="refreshScanHistory()" class="text-secondary-blue hover:text-blue-600">
                    <i class="fas fa-refresh"></i> Refresh
                </button>
            </div>
            <div id="today-scans" class="space-y-3">
                <!-- Transactions will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- QR Scanner Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qr-scanner/1.4.2/qr-scanner.min.js"></script>
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let qrScanner = null;
    let isCameraActive = false;

    // Initialize QR Scanner
    function initializeScanner() {
        const video = document.getElementById('qr-video');

        qrScanner = new QrScanner(
            video,
            result => {
                handleScanResult(result);
            },
            {
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 1,
            }
        );

        // Start camera automatically
        startCamera();
    }

    // Start camera
    function startCamera() {
        if (isCameraActive) return;

        qrScanner.start().then(() => {
            isCameraActive = true;
            document.getElementById('scanner-preview').classList.remove('bg-gray-100');
            Swal.fire({
                icon: 'success',
                title: 'Camera Started!',
                text: 'You can now scan QR codes',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            console.error('Error starting scanner:', err);
            document.getElementById('scanner-preview').innerHTML = `
                <div class="flex items-center justify-center h-full text-center">
                    <div>
                        <i class="fas fa-camera-slash text-4xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">Camera access required for scanning</p>
                        <p class="text-sm text-gray-400">Please allow camera permissions and click "Start Camera"</p>
                    </div>
                </div>
            `;
            Swal.fire({
                icon: 'error',
                title: 'Camera Error',
                text: 'Please allow camera permissions to scan QR codes',
                confirmButtonText: 'OK'
            });
        });
    }

    // Stop camera
    function stopCamera() {
        if (qrScanner && isCameraActive) {
            qrScanner.stop();
            isCameraActive = false;
            document.getElementById('scanner-preview').classList.add('bg-gray-100');
            Swal.fire({
                icon: 'info',
                title: 'Camera Stopped',
                text: 'Camera has been turned off',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    // Handle scan result
    function handleScanResult(qrCode) {
        if (!qrCode) return;

        // Show loading state
        showScanResult('Processing QR code...', 'loading');

        // Send to server
        fetch('{{ route("vendor.process-scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ qr_code: qrCode })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: 'Meal Recorded!',
                    html: `
                        <div class="text-left">
                            <p><strong>Employee:</strong> ${data.transaction.employee_name}</p>
                            <p><strong>Code:</strong> ${data.transaction.employee_code}</p>
                            <p><strong>Department:</strong> ${data.transaction.department}</p>
                            <p><strong>Amount:</strong> KSh ${data.transaction.amount}</p>
                            <p><strong>Time:</strong> ${data.transaction.time}</p>
                            <p class="text-sm text-gray-500 mt-2">Transaction: ${data.transaction.code}</p>
                        </div>
                    `,
                    confirmButtonText: 'Continue Scanning',
                    confirmButtonColor: '#3085d6',
                }).then((result) => {
                    // Resume scanning
                    hideScanResult();
                    if (qrScanner && !isCameraActive) {
                        startCamera();
                    }
                });

                // Refresh today's scans
                loadTodayScans();

            } else {
                // Show error SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'Scan Failed',
                    text: data.message,
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#d33',
                }).then((result) => {
                    // Resume scanning
                    hideScanResult();
                    if (qrScanner && !isCameraActive) {
                        startCamera();
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Please check your connection and try again',
                confirmButtonText: 'OK'
            });
        });

        // Stop scanner temporarily
        if (qrScanner && isCameraActive) {
            qrScanner.stop();
            isCameraActive = false;
        }
    }

    // Process manual QR input
    function processManualQR() {
        const manualInput = document.getElementById('manual-qr-input');
        const qrCode = manualInput.value.trim();

        if (!qrCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Input Required',
                text: 'Please enter a QR code',
                confirmButtonText: 'OK'
            });
            return;
        }

        handleScanResult(qrCode);
        manualInput.value = '';
    }

    // Show scan result
    function showScanResult(message, type) {
        const resultDiv = document.getElementById('scan-result');
        resultDiv.innerHTML = message;
        resultDiv.className = `text-center p-4 rounded-lg mb-4 ${
            type === 'success' ? 'bg-green-100 border border-green-300' :
            type === 'error' ? 'bg-red-100 border border-red-300' :
            'bg-blue-100 border border-blue-300'
        }`;
        resultDiv.classList.remove('hidden');
    }

    // Hide scan result
    function hideScanResult() {
        document.getElementById('scan-result').classList.add('hidden');
    }

    // Load today's scans
    function loadTodayScans() {
        fetch('{{ route("vendor.scan-history") }}')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('today-scans');

                if (data.success && data.transactions.length > 0) {
                    container.innerHTML = data.transactions.map(transaction => `
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-utensils text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-text-black">${transaction.employee.formal_name}</p>
                                    <p class="text-sm text-gray-500">${transaction.employee.employee_code} • ${transaction.employee.department.name}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600">KSh ${transaction.amount}</p>
                                <p class="text-sm text-gray-500">${transaction.meal_time}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-history text-2xl mb-2"></i>
                            <p>No scans today</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading scan history:', error);
            });
    }

    // Refresh scan history
    function refreshScanHistory() {
        loadTodayScans();
        Swal.fire({
            icon: 'success',
            title: 'Refreshed!',
            text: 'Scan history updated',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeScanner();
        loadTodayScans();

        // Camera control event listeners
        document.getElementById('start-camera').addEventListener('click', startCamera);
        document.getElementById('stop-camera').addEventListener('click', stopCamera);

        // Enter key for manual input
        document.getElementById('manual-qr-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processManualQR();
            }
        });
    });

    // Clean up scanner when leaving page
    window.addEventListener('beforeunload', function() {
        if (qrScanner) {
            qrScanner.destroy();
        }
    });
</script>
@endsection

