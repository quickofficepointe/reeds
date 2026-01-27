<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reeds Africa Talent Gateway</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configure custom colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-red': '#e92c2a',
                        'secondary-blue': '#2596be',
                        'text-black': '#000000',
                        'sidebar-blue': '#2596be',
                        'sidebar-blue-dark': '#1e7a9e',
                    }
                }
            }
        }
    </script>

    <style>
        .sidebar {
            background: linear-gradient(180deg, #2596be 0%, #1e7a9e 100%);
        }

        .nav-link {
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .nav-link.active {
            background-color: #FFFFFF !important;
            color: #2596be !important;
            font-weight: 600;
        }

        .nav-link.active i {
            color: #2596be !important;
        }

        .section-header {
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .user-dropdown {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(229, 231, 235, 0.8);
        }

        /* Toast animations */
        .toast-enter {
            opacity: 0;
            transform: translateY(-20px);
        }

        .toast-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms, transform 300ms;
        }

        .toast-exit {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-exit-active {
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 300ms, transform 300ms;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 bg-secondary-blue shadow-md h-16">
        <div class="flex items-center justify-between h-full px-4">
            <div class="flex items-center">
                <button class="mr-4 text-white md:hidden" id="mobileSidebarToggle">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <a class="flex items-center" href="{{ route('vendor.dashboard') }}">
                    <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-3">
                        <i class="fas fa-qrcode text-white text-lg"></i>
                    </div>
                    <span class="font-bold text-white text-xl">QR Feeding System</span>
                </a>
            </div>

            <!-- User Dropdown -->
            <div class="relative">
                <button class="flex items-center space-x-2 text-white hover:bg-white hover:bg-opacity-10 p-2 rounded-lg transition-all duration-200" id="userDropdownBtn">
                    <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                        @if(Auth::user()->profile && Auth::user()->profile->photo)
                            <img src="{{ Storage::url(Auth::user()->profile->photo) }}" alt="Profile" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <i class="fas fa-store text-white"></i>
                        @endif
                    </div>
                    <span class="hidden md:inline font-medium">{{ Auth::user()->name }}</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="userDropdownMenu" class="absolute right-0 mt-2 w-48 user-dropdown rounded-lg py-2 hidden z-40">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            @if(Auth::user()->profile && Auth::user()->profile->isVerified())
                                Verified Vendor
                            @else
                                Pending Verification
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('profile.show') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-3 text-gray-500 w-4"></i>
                        My Profile
                    </a>

                    <div class="border-t border-gray-100">
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700">
                            <i class="fas fa-sign-out-alt mr-3 w-4"></i>
                            Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="fixed top-16 left-0 bottom-0 w-64 text-white z-20 overflow-y-auto sidebar hidden md:block" id="sidebar">
        <div class="p-4">
            <!-- User Profile Card -->
            <div class="flex items-center mb-6 p-3 bg-white bg-opacity-10 rounded-lg">
                <div class="relative mr-3">
                    @if(Auth::user()->profile && Auth::user()->profile->photo)
                        <img src="{{ Storage::url(Auth::user()->profile->photo) }}" alt="Profile" class="w-12 h-12 rounded-full object-cover border-2 border-white border-opacity-30">
                    @else
                        <div class="w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center border-2 border-white border-opacity-30">
                            <i class="fas fa-store text-white text-lg"></i>
                        </div>
                    @endif
                    <span class="absolute bottom-1 right-1 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-sidebar-blue"></span>
                </div>
                <div>
                    <h6 class="font-medium text-white">{{ Auth::user()->name }}</h6>
                    <p class="text-xs opacity-80 text-white">
                        @if(Auth::user()->profile && Auth::user()->profile->isVerified())
                            Verified Vendor
                        @else
                            <span class="text-yellow-300">Pending Verification</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Navigation Links -->
            <ul class="space-y-1">
                <!-- Main -->
                <li class="mt-2">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Main</p>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                        Dashboard
                    </a>
                </li>

                <!-- QR Operations -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">QR Operations</p>
                </li>
                <li>
                    <a href="{{ route('vendor.scan') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('vendor.scan') ? 'active' : '' }}">
                        <i class="fas fa-camera mr-3 w-5 text-center"></i>
                        Scan QR Code
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.scan-history') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('vendor.scan-history') ? 'active' : '' }}">
                        <i class="fas fa-history mr-3 w-5 text-center"></i>
                        Scan History
                    </a>
                </li>

                <!-- Reports -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Reports</p>
                </li>
                <li>
                    <a href="#"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link">
                        <i class="fas fa-file-alt mr-3 w-5 text-center"></i>
                        Daily Reports
                    </a>
                </li>
                <li>
                    <a href="#"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link">
                        <i class="fas fa-chart-line mr-3 w-5 text-center"></i>
                        Performance Analytics
                    </a>
                </li>

                <!-- Account -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Account</p>
                </li>
                <li>
                    <a href="{{ route('profile.show') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                        <i class="fas fa-user-cog mr-3 w-5 text-center"></i>
                        Profile Settings
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content ml-0 md:ml-64 mt-16 p-4 md:p-6 min-h-screen" id="mainContent">
        @yield('content')
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <!-- Session Management Script -->
    <script>
        // Session Management System for Vendors
        class VendorSessionManager {
            constructor() {
                this.checkInterval = 30000; // Check every 30 seconds
                this.keepAliveInterval = 1200000; // Send keep-alive every 20 minutes
                this.warningTime = 300000; // Show warning 5 minutes before expiry
                this.sessionLifetime = {{ config('session.lifetime') * 60 * 1000 }}; // Convert to milliseconds
                this.lastActivity = Date.now();
                this.isActive = true;
                this.warningShown = false;
               
                this.loginUrl = '{{ route("login") }}';
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                this.init();
            }

            init() {
                console.log('Vendor Session Manager Initialized');
                // Track user activity
                this.trackActivity();

                // Start session checker
                this.startSessionChecker();

                // Start keep-alive
                this.startKeepAlive();

                // Listen for AJAX errors
                this.interceptAjaxErrors();
            }

            trackActivity() {
                // Update last activity on user interaction (especially important for scanning)
                ['click', 'keypress', 'mousemove', 'scroll', 'touchstart', 'touchend'].forEach(event => {
                    document.addEventListener(event, () => {
                        this.lastActivity = Date.now();
                        this.isActive = true;
                        if (this.warningShown) {
                            this.hideSessionWarning();
                        }
                    }, { passive: true });
                });

                // Track scanning activity specifically
                if (window.isProcessingScan !== undefined) {
                    // Monitor scan processing
                    const originalScanHandler = window.handleScanResult;
                    if (originalScanHandler) {
                        window.handleScanResult = async function(...args) {
                            this.lastActivity = Date.now();
                            return await originalScanHandler.apply(this, args);
                        }.bind(this);
                    }
                }

                // Check visibility change (tab switching)
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.lastActivity = Date.now();
                        this.checkSession();
                    }
                });
            }

            startSessionChecker() {
                setInterval(() => {
                    this.checkSession();
                }, this.checkInterval);
            }

            startKeepAlive() {
                setInterval(() => {
                    if (this.isActive) {
                        this.sendKeepAlive();
                    }
                }, this.keepAliveInterval);
            }

            async checkSession() {
                const now = Date.now();
                const timeSinceActivity = now - this.lastActivity;
                const timeLeft = this.sessionLifetime - timeSinceActivity;

                // Show warning if session is about to expire
                if (timeLeft > 0 && timeLeft <= this.warningTime && !this.warningShown) {
                    this.showSessionWarning(timeLeft);
                }

                // Check if session might have expired
                if (timeSinceActivity > this.sessionLifetime) {
                    await this.handleSessionExpired();
                }
            }

            showSessionWarning(timeLeft) {
                this.warningShown = true;

                const minutes = Math.ceil(timeLeft / 60000);
                const seconds = Math.ceil((timeLeft % 60000) / 1000);

                // Create warning modal optimized for scanning environment
                const warningModal = document.createElement('div');
                warningModal.id = 'vendorSessionWarning';
                warningModal.innerHTML = `
                    <div class="fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl">
                            <div class="flex flex-col items-center text-center mb-4">
                                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Session About to Expire</h3>
                                <p class="text-gray-600 mb-4">Your session will expire in <span class="font-bold text-primary-red">${minutes}m ${seconds}s</span></p>
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-6">
                                    <div class="bg-yellow-500 h-2 rounded-full" style="width: ${(timeLeft / this.warningTime) * 100}%"></div>
                                </div>
                            </div>
                            <div class="flex flex-col space-y-3">
                                <button id="vendorExtendSession"
                                        class="w-full px-4 py-3 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-200 font-semibold text-lg flex items-center justify-center">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    Continue Scanning
                                </button>
                                <button id="vendorLogout"
                                        class="w-full px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(warningModal);

                // Add event listeners
                document.getElementById('vendorExtendSession').addEventListener('click', () => {
                    this.extendSession();
                    this.hideSessionWarning();
                });

                document.getElementById('vendorLogout').addEventListener('click', () => {
                    this.logout();
                });
            }

            hideSessionWarning() {
                const warningModal = document.getElementById('vendorSessionWarning');
                if (warningModal) {
                    warningModal.remove();
                }
                this.warningShown = false;
            }

            async extendSession() {
                try {
                    const response = await this.sendKeepAlive();
                    if (response.success) {
                        this.lastActivity = Date.now();
                        this.showToast('Session extended - Keep scanning!', 'success');
                    }
                } catch (error) {
                    console.error('Failed to extend session:', error);
                    this.showToast('Failed to extend session', 'error');
                }
            }

            async sendKeepAlive() {
                try {
                    const response = await fetch(this.keepAliveUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        throw new Error('Keep-alive failed');
                    }

                    return await response.json();
                } catch (error) {
                    console.error('Keep-alive error:', error);
                    return { success: false };
                }
            }

            async handleSessionExpired() {
                // Check with server first
                try {
                    const response = await fetch(this.keepAliveUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    if (response.status === 401 || response.status === 419) {
                        await this.showSessionExpiredModal();
                    } else if (response.ok) {
                        // Session is still valid, update last activity
                        this.lastActivity = Date.now();
                        this.hideSessionWarning();
                        return;
                    }
                } catch (error) {
                    await this.showSessionExpiredModal();
                }
            }

            async showSessionExpiredModal() {
                this.hideSessionWarning();

                // Create vendor-friendly expired session modal
                const expiredModal = document.createElement('div');
                expiredModal.id = 'vendorSessionExpired';
                expiredModal.innerHTML = `
                    <div class="fixed inset-0 bg-black bg-opacity-80 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-xl p-6 max-w-md w-full text-center shadow-2xl">
                            <div class="flex flex-col items-center mb-6">
                                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Session Expired</h3>
                                <p class="text-gray-600 mb-1">Your scanning session has expired due to inactivity</p>
                                <p class="text-sm text-gray-500">Please log in again to continue scanning</p>
                            </div>
                            <div class="space-y-3">
                                <button id="vendorLoginAgain"
                                        class="w-full px-6 py-3 bg-primary-red text-white rounded-lg hover:bg-red-600 transition duration-200 font-semibold text-lg">
                                    Log In Again
                                </button>
                                <div class="text-sm text-gray-500">
                                    Redirecting in <span id="vendorRedirectCountdown">5</span> seconds...
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(expiredModal);

                // Start countdown
                let countdown = 5;
                const countdownElement = document.getElementById('vendorRedirectCountdown');
                const countdownInterval = setInterval(() => {
                    countdown--;
                    countdownElement.textContent = countdown;

                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = this.loginUrl;
                    }
                }, 1000);

                // Redirect to login when button clicked
                document.getElementById('vendorLoginAgain').addEventListener('click', () => {
                    clearInterval(countdownInterval);
                    window.location.href = this.loginUrl;
                });
            }

            interceptAjaxErrors() {
                // Intercept fetch requests
                const originalFetch = window.fetch;

                window.fetch = async function(...args) {
                    try {
                        const response = await originalFetch(...args);

                        // Check for session expired responses
                        if (response.status === 401 || response.status === 419) {
                            const contentType = response.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                const data = await response.json();
                                if (data.session_expired) {
                                    const sessionManager = new VendorSessionManager();
                                    await sessionManager.showSessionExpiredModal();
                                    return Promise.reject(new Error('Session expired'));
                                }
                            }
                        }

                        return response;
                    } catch (error) {
                        // Handle network errors
                        if (error.message === 'Failed to fetch') {
                            // Network error - show appropriate message
                            this.showNetworkErrorModal();
                        }
                        throw error;
                    }
                }.bind(this);
            }

            showNetworkErrorModal() {
                const networkModal = document.createElement('div');
                networkModal.id = 'vendorNetworkError';
                networkModal.innerHTML = `
                    <div class="fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-xl p-6 max-w-md w-full text-center">
                            <div class="flex flex-col items-center mb-6">
                                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-wifi-slash text-yellow-600 text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Connection Issue</h3>
                                <p class="text-gray-600">Unable to connect to server</p>
                                <p class="text-sm text-gray-500 mt-1">Please check your internet connection</p>
                            </div>
                            <div class="space-y-3">
                                <button id="vendorRetryConnection"
                                        class="w-full px-4 py-3 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-200">
                                    Retry Connection
                                </button>
                                <button onclick="location.reload()"
                                        class="w-full px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
                                    Reload Page
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(networkModal);

                document.getElementById('vendorRetryConnection').addEventListener('click', () => {
                    networkModal.remove();
                    // Check if we can reach the server
                    this.sendKeepAlive().then(() => {
                        this.showToast('Connection restored!', 'success');
                    }).catch(() => {
                        this.showNetworkErrorModal();
                    });
                });
            }

            showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
                    type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
                    type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
                    'bg-blue-100 text-blue-800 border border-blue-200'
                }`;
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                        <span>${message}</span>
                    </div>
                `;

                document.body.appendChild(toast);

                // Auto-remove after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            logout() {
                document.getElementById('logout-form').submit();
            }
        }

        // UI Controls
        document.addEventListener('DOMContentLoaded', function() {
            // Only initialize if user is logged in
            if (document.querySelector('meta[name="csrf-token"]')) {
                window.vendorSessionManager = new VendorSessionManager();
            }

            // Toggle sidebar on mobile
            document.getElementById('mobileSidebarToggle').addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('hidden');

                // Toggle main content margin
                const mainContent = document.getElementById('mainContent');
                if (sidebar.classList.contains('hidden')) {
                    mainContent.classList.remove('ml-64');
                    mainContent.classList.add('ml-0');
                } else {
                    mainContent.classList.remove('ml-0');
                    mainContent.classList.add('ml-64');
                }
            });

            // User dropdown toggle
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (userDropdownBtn) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdownMenu.classList.toggle('hidden');
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (userDropdownBtn && userDropdownMenu &&
                    !userDropdownBtn.contains(event.target) &&
                    !userDropdownMenu.contains(event.target)) {
                    userDropdownMenu.classList.add('hidden');
                }

                // Close sidebar when clicking outside on mobile
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('mobileSidebarToggle');
                const mainContent = document.getElementById('mainContent');

                if (window.innerWidth < 768 &&
                    !sidebar.contains(event.target) &&
                    !toggleBtn.contains(event.target) &&
                    event.target.closest('#mainContent')) {

                    sidebar.classList.add('hidden');
                    if (mainContent) {
                        mainContent.classList.remove('ml-64');
                        mainContent.classList.add('ml-0');
                    }
                }
            });

            // Set active navigation link
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.href === window.location.href || link.classList.contains('active')) {
                    link.classList.add('active');
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');

                if (window.innerWidth >= 768) {
                    if (sidebar) sidebar.classList.remove('hidden');
                    if (mainContent) {
                        mainContent.classList.remove('ml-0');
                        mainContent.classList.add('ml-64');
                    }
                } else {
                    if (sidebar) sidebar.classList.add('hidden');
                    if (mainContent) {
                        mainContent.classList.remove('ml-64');
                        mainContent.classList.add('ml-0');
                    }
                }
            });

            // Initialize on page load
            if (window.innerWidth < 768) {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');
                if (sidebar) sidebar.classList.add('hidden');
                if (mainContent) {
                    mainContent.classList.remove('ml-64');
                    mainContent.classList.add('ml-0');
                }
            }
        });

    </script>
<script src="{{ asset('js/session-manager.js') }}"></script>
    @yield('scripts')
</body>
</html>
