<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reeds Africa Talent Gateway</title>

    <link rel="icon" href="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-32x32.png" sizes="32x32">
    <link rel="icon" href="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-192x192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-180x180.png">
    <meta name="msapplication-TileImage" content="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-270x270.png">
    <meta name="theme-color" content="#e82b2a">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-red': '#e92c2a',
                        'secondary-blue': '#2596be',
                        'card-bg': '#f7f7f7',
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Session Manager Styles -->
    <style>
        .toast-enter { opacity: 0; transform: translateY(-20px); }
        .toast-enter-active { opacity: 1; transform: translateY(0); transition: opacity 300ms, transform 300ms; }
        .toast-exit { opacity: 1; transform: translateY(0); }
        .toast-exit-active { opacity: 0; transform: translateY(-20px); transition: opacity 300ms, transform 300ms; }
    </style>
</head>
<body class="bg-white min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- Reeds Logo/Brand -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-secondary-blue rounded-lg flex items-center justify-center">
                            <i class="fas fa-utensils text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-gray-900">Reeds Africa</span>
                            <span class="text-sm text-gray-500 block -mt-1">Consult</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    @guest
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-secondary-blue transition duration-150 font-medium">
                                Login
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-secondary-blue text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-600 transition duration-150">
                                Register
                            </a>
                        @endif
                    @else
                        <div class="relative">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-secondary-blue focus:outline-none transition duration-150" id="userMenuBtn">
                                <div class="w-8 h-8 bg-secondary-blue rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="font-medium">{{ Auth::user()->name }}</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>

                            <!-- User Dropdown (only for logged-in users) -->
                            <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 hidden z-50">
                                @if(Auth::user()->role == 1)
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-tachometer-alt mr-3 text-gray-500 w-4"></i>
                                        Admin Dashboard
                                    </a>
                                @elseif(Auth::user()->role == 2)
                                    <a href="{{ route('vendor.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-store mr-3 text-gray-500 w-4"></i>
                                        Vendor Dashboard
                                    </a>
                                @endif

                                <a href="{{ route('profile.show') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-3 text-gray-500 w-4"></i>
                                    My Profile
                                </a>

                                <div class="border-t border-gray-100">
                                    <a href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                       class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt mr-3 w-4"></i>
                                        Logout
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                        @csrf
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-50 border-t border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 text-gray-600 mb-4 md:mb-0">
                    <span>Powered by</span>
                    <a href="https://biztrak.ke" target="_blank" class="font-semibold text-secondary-blue hover:text-blue-600 transition duration-150">
                        BizTrak Solutions
                    </a>
                </div>
                <div class="flex items-center space-x-6 text-sm text-gray-500">
                    <span>&copy; {{ date('Y') }} Reeds Africa Consult. All rights reserved.</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <!-- Session Manager -->
    <script src="{{ asset('js/session-manager.js') }}"></script>

    <script>
        // Public page JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown toggle
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userMenu = document.getElementById('userMenu');

            if (userMenuBtn && userMenu) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuBtn.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }

            // Initialize session manager for logged-in users
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                // Determine user type
                let userType = 'user';
                const userRole = {{ Auth::user()->role ?? 0 }};
                if (userRole == 1) userType = 'admin';
                if (userRole == 2) userType = 'vendor';

                // Initialize with appropriate settings
                window.publicSessionManager = new UniversalSessionManager({
                    userType: userType,
                    keepAliveUrl: `/${userType}/session/keep-alive`,
                    loginUrl: '{{ route("login") }}',
                    logoutUrl: '{{ route("logout") }}',
                    checkInterval: 60000, // Check every minute for public pages
                    warningTime: 300000 // 5 minutes warning
                });
            }
        });
    </script>

    @yield('scripts')
</body>
</html>
