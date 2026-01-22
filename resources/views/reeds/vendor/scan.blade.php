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
        <!-- Debug Info (remove in production) -->
        <div id="debug-info" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm hidden">
            <strong>Debug Info:</strong>
            <div id="debug-content"></div>
        </div>

        <!-- Scanner Container -->
        <div id="scanner-container" class="mb-6">
            <div class="text-center mb-4">
                <div class="w-64 h-64 mx-auto border-4 border-secondary-blue rounded-lg relative overflow-hidden bg-gray-100" id="scanner-preview">
                    <!-- Loading State -->
                    <div id="scanner-loading" class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-2"></div>
                            <p class="text-gray-600">Loading scanner...</p>
                        </div>
                    </div>

                    <!-- Video Element -->
                    <video id="qr-video" class="w-full h-full object-cover hidden" playsinline></video>

                    <!-- Scanner Overlay -->
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
                <div class="flex space-x-2">
                    <button onclick="toggleDebug()" class="text-gray-500 hover:text-gray-700 text-sm">
                        <i class="fas fa-bug"></i> Debug
                    </button>
                    <button onclick="refreshScanHistory()" class="text-secondary-blue hover:text-blue-600">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            <div id="today-scans" class="space-y-3">
                <!-- Transactions will be loaded here -->
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-history text-2xl mb-2"></i>
                    <p>Loading scan history...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jsQR Library -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script>
    // Configuration
    const CONFIG = {
        SCAN_ENDPOINT: '{{ route("vendor.process-scan") }}',
        SCAN_HISTORY_ENDPOINT: '{{ route("vendor.scan-history") }}',
        CSRF_TOKEN: '{{ csrf_token() }}',
        DEBUG_MODE: {{ config('app.debug') ? 'true' : 'false' }}
    };

    let isCameraActive = false;
    let isProcessingScan = false;
    let stream = null;
    let animationFrame = null;
    const video = document.getElementById('qr-video');
    const canvas = document.createElement('canvas');
    const canvasContext = canvas.getContext('2d');

    // Enhanced logging with debug mode
    function logScanner(message, data = null) {
        const timestamp = new Date().toISOString().split('T')[1].split('.')[0];
        console.log(`[QR Scanner ${timestamp}] ${message}`, data || '');

        if (CONFIG.DEBUG_MODE) {
            const debugDiv = document.getElementById('debug-content');
            if (debugDiv) {
                debugDiv.innerHTML += `<div class="mb-1"><strong>${timestamp}:</strong> ${message} ${data ? JSON.stringify(data) : ''}</div>`;
                debugDiv.scrollTop = debugDiv.scrollHeight;
            }
        }
    }

    // Toggle debug info
    function toggleDebug() {
        const debugInfo = document.getElementById('debug-info');
        debugInfo.classList.toggle('hidden');
    }

    // Show loading state
    function showScannerLoading() {
        document.getElementById('scanner-loading').classList.remove('hidden');
        document.getElementById('qr-video').classList.add('hidden');
    }

    // Hide loading state
    function hideScannerLoading() {
        document.getElementById('scanner-loading').classList.add('hidden');
        document.getElementById('qr-video').classList.remove('hidden');
    }

    // Show error state
    function showScannerError(message) {
        document.getElementById('scanner-preview').innerHTML = `
            <div class="flex items-center justify-center h-full text-center">
                <div>
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-2"></i>
                    <p class="text-gray-500">${message}</p>
                    <p class="text-sm text-gray-400 mt-1">Please use manual input below</p>
                    <button onclick="retryScannerInitialization()" class="mt-3 bg-secondary-blue text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 transition duration-300">
                        Retry
                    </button>
                </div>
            </div>
        `;
    }

    // Retry scanner initialization
    function retryScannerInitialization() {
        document.getElementById('scanner-preview').innerHTML = `
            <div id="scanner-loading" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-2"></div>
                    <p class="text-gray-600">Loading scanner...</p>
                </div>
            </div>
            <video id="qr-video" class="w-full h-full object-cover hidden" playsinline></video>
            <div class="scanner-overlay absolute inset-0 border-2 border-white rounded"></div>
        `;
        initializeScanner();
    }

    // Check if jsQR is available
    function checkScannerAvailability() {
        if (typeof jsQR === 'undefined') {
            logScanner('ERROR: jsQR library not loaded');
            return false;
        }
        return true;
    }

    // Initialize Scanner
    async function initializeScanner() {
        logScanner('Starting scanner initialization...');
        showScannerLoading();

        if (!checkScannerAvailability()) {
            showScannerError('Scanner library failed to load');
            return;
        }

        logScanner('Scanner library loaded successfully');
        hideScannerLoading();

        // Test route accessibility first
        try {
            await testScanRoute();
            // Start camera automatically if route is accessible
            startCameraAutomatically();
        } catch (error) {
            logScanner('Route test failed, cannot start camera');
            showScannerError('Scan feature is not available');
        }
    }

    // Test if scan route is accessible
    async function testScanRoute() {
        logScanner('Testing scan route accessibility...');

        const formData = new FormData();
        formData.append('_token', CONFIG.CSRF_TOKEN);
        formData.append('test', 'true');

        try {
            const response = await fetch(CONFIG.SCAN_ENDPOINT, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData
            });

            logScanner('Route test response status:', response.status);

            if (response.status === 404) {
                throw new Error('Scan endpoint not found (404)');
            }

            const data = await response.json();
            logScanner('Route test response data:', data);

            return data;
        } catch (error) {
            logScanner('Route test failed:', error);
            throw error;
        }
    }

    // Start camera automatically
    async function startCameraAutomatically() {
        logScanner('Attempting to start camera automatically...');

        if (isCameraActive) {
            logScanner('Camera already active, skipping auto-start');
            return;
        }

        try {
            // Get camera stream with better error handling
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });

            video.srcObject = stream;
            video.setAttribute("playsinline", true);

            // Wait for video to be ready
            await new Promise((resolve, reject) => {
                video.onloadedmetadata = resolve;
                video.onerror = reject;
                setTimeout(() => reject(new Error('Video loading timeout')), 5000);
            });

            await video.play();

            logScanner('Camera started automatically - SUCCESS');
            isCameraActive = true;
            document.getElementById('scanner-preview').classList.remove('bg-gray-100');

            // Start QR scanning
            startQRScanning();

        } catch (err) {
            logScanner('ERROR starting camera automatically:', err);
            showCameraError(err);
        }
    }

    // Start QR scanning with jsQR - IMPROVED VERSION
    function startQRScanning() {
        if (!isCameraActive) return;

        let lastValidCode = '';
        let duplicateCount = 0;
        const MIN_CONSECUTIVE_READS = 2; // Require 2 consecutive same reads

        function scanQRCode() {
            if (!isCameraActive || isProcessingScan) {
                return;
            }

            try {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.height = video.videoHeight;
                    canvas.width = video.videoWidth;
                    canvasContext.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert",
                    });

                    if (code && code.data && code.data.trim().length > 0) {
                        const qrData = code.data.trim();

                        // ENHANCED DUPLICATE DETECTION - Prevent false positives
                        if (qrData === lastValidCode) {
                            duplicateCount++;
                            logScanner(`Same QR code detected ${duplicateCount} times:`, {
                                data: qrData.substring(0, 20) + '...',
                                length: qrData.length
                            });

                            if (duplicateCount >= MIN_CONSECUTIVE_READS) {
                                logScanner('Valid QR code confirmed, processing...');
                                handleScanResult(qrData);
                                duplicateCount = 0; // Reset after processing
                                lastValidCode = ''; // Reset to allow same code to be scanned again
                            }
                        } else {
                            // New code detected, start counting
                            lastValidCode = qrData;
                            duplicateCount = 1;
                            logScanner('New QR code detected, waiting for confirmation:', {
                                data: qrData.substring(0, 20) + '...',
                                length: qrData.length
                            });
                        }
                    } else {
                        // Reset counters when no valid QR code is detected
                        if (lastValidCode !== '') {
                            logScanner('No QR code detected, resetting counters');
                            lastValidCode = '';
                            duplicateCount = 0;
                        }
                    }
                }
            } catch (error) {
                logScanner('Error during QR scanning:', error);
            }

            if (isCameraActive && !isProcessingScan) {
                animationFrame = requestAnimationFrame(scanQRCode);
            }
        }

        // Start the scanning loop
        scanQRCode();
    }

    // Show camera error with detailed information
    function showCameraError(error) {
        logScanner('Showing camera error UI', error);

        let errorMessage = 'Camera access required for scanning';
        let errorDetails = 'Please allow camera permissions';

        if (error && error.name) {
            switch(error.name) {
                case 'NotAllowedError':
                    errorMessage = 'Camera permission denied';
                    errorDetails = 'Please allow camera access in your browser settings';
                    break;
                case 'NotFoundError':
                    errorMessage = 'No camera found';
                    errorDetails = 'Please check if your device has a camera';
                    break;
                case 'NotSupportedError':
                    errorMessage = 'Camera not supported';
                    errorDetails = 'Your browser may not support camera access';
                    break;
                case 'NotReadableError':
                    errorMessage = 'Camera in use';
                    errorDetails = 'Another application may be using the camera';
                    break;
                default:
                    errorMessage = `Camera error: ${error.message}`;
                    break;
            }
        }

        document.getElementById('scanner-preview').innerHTML = `
            <div class="flex items-center justify-center h-full text-center">
                <div>
                    <i class="fas fa-camera-slash text-4xl text-gray-400 mb-2"></i>
                    <p class="text-gray-500">${errorMessage}</p>
                    <p class="text-sm text-gray-400 mt-1">${errorDetails}</p>
                    <button onclick="retryCamera()" class="mt-3 bg-secondary-blue text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 transition duration-300">
                        Retry Camera
                    </button>
                </div>
            </div>
        `;
    }

    // Retry camera
    function retryCamera() {
        logScanner('Retrying camera initialization...');
        document.getElementById('scanner-preview').innerHTML = `
            <div id="scanner-loading" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-2"></div>
                    <p class="text-gray-600">Starting camera...</p>
                </div>
            </div>
            <video id="qr-video" class="w-full h-full object-cover hidden" playsinline></video>
            <div class="scanner-overlay absolute inset-0 border-2 border-white rounded"></div>
        `;

        // Reinitialize the scanner
        setTimeout(() => {
            startCameraAutomatically();
        }, 500);
    }

    // Manual camera start
    async function startCamera() {
        logScanner('Manual camera start requested');

        if (isCameraActive) {
            logScanner('Camera already active, ignoring manual start');
            Swal.fire({
                icon: 'info',
                title: 'Camera Already Active',
                text: 'Camera is already running',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }

        try {
            await startCameraAutomatically();

            Swal.fire({
                icon: 'success',
                title: 'Camera Started!',
                text: 'You can now scan QR codes',
                timer: 2000,
                showConfirmButton: false
            });
        } catch (err) {
            logScanner('ERROR in manual camera start:', err);
            showCameraError(err);
        }
    }

    // Stop camera
    function stopCamera() {
        logScanner('Stopping camera...');

        if (isCameraActive) {
            // Stop the scanning loop
            if (animationFrame) {
                cancelAnimationFrame(animationFrame);
                animationFrame = null;
            }

            // Stop the video stream
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }

            // Clear the video element
            video.srcObject = null;

            isCameraActive = false;
            document.getElementById('scanner-preview').classList.add('bg-gray-100');
            logScanner('Camera stopped successfully');

            Swal.fire({
                icon: 'info',
                title: 'Camera Stopped',
                text: 'Camera has been turned off',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            logScanner('Camera not active, ignoring stop request');
        }
    }

   // FIXED VERSION - Handle scan result with proper error response parsing
async function handleScanResult(qrCode) {
    logScanner('Handling scan result:', {
        raw: qrCode,
        length: qrCode?.length,
        trimmed: qrCode?.trim(),
        trimmedLength: qrCode?.trim()?.length
    });

    // ENHANCED VALIDATION
    if (!qrCode || qrCode.trim().length === 0) {
        logScanner('Empty QR code detected, ignoring scan');
        showScanResult('❌ No QR code detected', 'error');

        setTimeout(() => {
            hideScanResult();
        }, 2000);
        return;
    }

    const trimmedQrCode = qrCode.trim();

    if (trimmedQrCode.length < 1) {
        logScanner('QR code too short after trimming, ignoring scan');
        showScanResult('❌ Invalid QR code format', 'error');
        setTimeout(() => hideScanResult(), 2000);
        return;
    }

    if (isProcessingScan) {
        logScanner('Already processing a scan, ignoring new scan');
        return;
    }

    isProcessingScan = true;
    logScanner('Starting scan processing with QR code:', {
        length: trimmedQrCode.length,
        preview: trimmedQrCode.substring(0, 30) + '...'
    });

    // Show immediate feedback
    showScanResult('🔄 Processing QR code...', 'loading');

    // Stop scanning temporarily during processing
    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }

    try {
        logScanner('Sending scan to server...');

        const formData = new FormData();
        formData.append('qr_code', trimmedQrCode);
        formData.append('_token', CONFIG.CSRF_TOKEN);

        const response = await fetch(CONFIG.SCAN_ENDPOINT, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData
        });

        logScanner('Server response status:', response.status);

        let data;
        const responseText = await response.text();

        try {
            data = JSON.parse(responseText);
            logScanner('Server response parsed successfully:', data);
        } catch (e) {
            logScanner('Failed to parse JSON response:', responseText);
            throw new Error('Invalid server response format');
        }

        // FIXED: Handle both successful responses AND error responses properly
        if (data.success) {
            logScanner('Scan processed successfully');
            showScanResult('✅ Meal recorded successfully!', 'success');

            Swal.fire({
                icon: 'success',
                title: 'Meal Recorded!',
                html: `
                    <div class="text-left text-sm">
                        <p><strong>Employee:</strong> ${data.transaction.employee_name}</p>
                        <p><strong>Department:</strong> ${data.transaction.department}</p>
                        <p><strong>Amount:</strong> KSh ${data.transaction.amount}</p>
                        <p><strong>Time:</strong> ${data.transaction.time}</p>
                    </div>
                `,
                timer: 3000,
                showConfirmButton: false,
                position: 'top'
            });

            // Refresh today's scans
            loadTodayScans();

        } else {
            // FIXED: Handle server-side errors with specific messages
            let errorMessage = data.message || 'Scan failed';
            let errorType = data.error_type || 'error';

            logScanner('Server returned error:', {
                message: errorMessage,
                type: errorType,
                status: response.status
            });

            // Show the actual server error message instead of generic "server error"
            showScanResult(`❌ ${errorMessage}`, errorType);

            // Use appropriate SweetAlert based on error type
            let sweetAlertConfig = {
                icon: 'error',
                title: 'Scan Failed',
                text: errorMessage,
                confirmButtonText: 'OK',
                confirmButtonColor: '#2596be'
            };

            switch (errorType) {
                case 'feeding_hours':
                    sweetAlertConfig.icon = 'warning';
                    sweetAlertConfig.title = 'Outside Feeding Hours';
                    sweetAlertConfig.timer = 6000;
                    sweetAlertConfig.showConfirmButton = false;
                    break;
                case 'duplicate_meal':
                    sweetAlertConfig.icon = 'warning';
                    sweetAlertConfig.title = 'Already Fed Today';
                    sweetAlertConfig.timer = 5000;
                    sweetAlertConfig.showConfirmButton = false;
                    break;
                case 'employee_not_found':
                    sweetAlertConfig.icon = 'error';
                    sweetAlertConfig.title = 'Employee Not Found';
                    sweetAlertConfig.timer = 5000;
                    sweetAlertConfig.showConfirmButton = false;
                    break;
                case 'system_error':
                    sweetAlertConfig.icon = 'error';
                    sweetAlertConfig.title = 'System Error';
                    sweetAlertConfig.showConfirmButton = true;
                    break;
            }

            Swal.fire(sweetAlertConfig);
        }
    } catch (error) {
        logScanner('ERROR processing scan:', error);

        let errorMessage = 'An error occurred while processing the scan';

        if (error.message.includes('422')) {
            errorMessage = 'Invalid QR code format. Please scan a valid employee QR code.';
        } else if (error.message.includes('404')) {
            errorMessage = 'Scan endpoint not found. Please contact administrator.';
        } else if (error.message.includes('419')) {
            errorMessage = 'Session expired. Please refresh the page.';
        } else if (error.message.includes('Network')) {
            errorMessage = 'Network error. Please check your internet connection.';
        } else if (error.message.includes('Invalid server response')) {
            errorMessage = 'Server response error. Please try again.';
        }

        showScanResult('❌ ' + errorMessage, 'error');

        Swal.fire({
            icon: 'error',
            title: 'Scan Error',
            text: errorMessage,
            confirmButtonText: 'OK',
            confirmButtonColor: '#2596be'
        });
    } finally {
        logScanner('Scan processing completed');

        // Resume scanning after 3 seconds
        setTimeout(() => {
            hideScanResult();
            isProcessingScan = false;

            // Restart scanning if camera is active
            if (isCameraActive && !animationFrame) {
                startQRScanning();
            }
        }, 3000);
    }
}

    // Process manual QR input
    function processManualQR() {
        const manualInput = document.getElementById('manual-qr-input');
        const qrCode = manualInput.value.trim();

        logScanner('Manual QR input submitted:', qrCode);

        if (!qrCode) {
            logScanner('Manual QR input empty');
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
        logScanner(`Showing scan result: ${message}`, { type });
        const resultDiv = document.getElementById('scan-result');
        resultDiv.innerHTML = message;
        resultDiv.className = `text-center p-4 rounded-lg mb-4 font-semibold ${
            type === 'success' ? 'bg-green-100 border border-green-300 text-green-700' :
            type === 'warning' ? 'bg-yellow-100 border border-yellow-300 text-yellow-700' :
            type === 'error' ? 'bg-red-100 border border-red-300 text-red-700' :
            'bg-blue-100 border border-blue-300 text-blue-700'
        }`;
        resultDiv.classList.remove('hidden');
    }

    // Hide scan result
    function hideScanResult() {
        logScanner('Hiding scan result');
        document.getElementById('scan-result').classList.add('hidden');
    }

    // Load today's scans
    async function loadTodayScans() {
        logScanner('Loading today scans...');
        try {
            const response = await fetch(CONFIG.SCAN_HISTORY_ENDPOINT);
            logScanner('Scan history response:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            logScanner('Scan history data loaded:', data);

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
                            <p class="text-sm text-gray-500">${transaction.created_at}</p>
                        </div>
                    </div>
                `).join('');
                logScanner(`Displayed ${data.transactions.length} transactions`);
            } else {
                container.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-history text-2xl mb-2"></i>
                        <p>No scans today</p>
                    </div>
                `;
                logScanner('No transactions to display');
            }
        } catch (error) {
            logScanner('ERROR loading scan history:', error);
            const container = document.getElementById('today-scans');
            container.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Error loading scan history</p>
                    <p class="text-sm">${error.message}</p>
                </div>
            `;
        }
    }

    // Refresh scan history
    function refreshScanHistory() {
        logScanner('Refreshing scan history...');
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
        logScanner('DOM Content Loaded - Starting initialization');

        // Load today's scans immediately
        loadTodayScans();

        // Initialize scanner
        initializeScanner();

        // Camera control event listeners
        document.getElementById('start-camera').addEventListener('click', startCamera);
        document.getElementById('stop-camera').addEventListener('click', stopCamera);

        // Enter key for manual input
        document.getElementById('manual-qr-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processManualQR();
            }
        });

        logScanner('Event listeners attached');
    });

    // Clean up when leaving page
    window.addEventListener('beforeunload', function() {
        logScanner('Page unloading, cleaning up...');
        stopCamera();
    });
</script>

@endsection
