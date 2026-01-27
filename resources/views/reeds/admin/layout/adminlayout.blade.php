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
                        'sidebar-red': '#e92c2a',
                        'sidebar-red-dark': '#c22120',
                    }
                }
            }
        }
    </script>

    <style>
        .sidebar {
            background: linear-gradient(180deg, #e92c2a 0%, #c22120 100%);
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
            color: #e92c2a !important;
            font-weight: 600;
        }

        .nav-link.active i {
            color: #e92c2a !important;
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
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 bg-primary-red shadow-md h-16">
        <div class="flex items-center justify-between h-full px-4">
            <div class="flex items-center">
                <button class="mr-4 text-white md:hidden" id="mobileSidebarToggle">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <a class="flex items-center" href="{{ route('admin.dashboard') }}">
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
                            <i class="fas fa-user-shield text-white"></i>
                        @endif
                    </div>
                    <span class="hidden md:inline font-medium">{{ Auth::user()->name }}</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="userDropdownMenu" class="absolute right-0 mt-2 w-48 user-dropdown rounded-lg py-2 hidden z-40">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Administrator</p>
                    </div>

                    <a href="{{ route('profile.show') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-3 text-gray-500 w-4"></i>
                        My Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700">
                            <i class="fas fa-sign-out-alt mr-3 w-4"></i>
                            Logout
                        </button>
                    </form>
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
                            <i class="fas fa-user-shield text-white text-lg"></i>
                        </div>
                    @endif
                    <span class="absolute bottom-1 right-1 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-sidebar-red"></span>
                </div>
                <div>
                    <h6 class="font-medium text-white">{{ Auth::user()->name }}</h6>
                    <p class="text-xs opacity-80 text-white">Administrator</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <ul class="space-y-1">
                <!-- Dashboard -->
                <li class="mt-2">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Main</p>
                </li>
                <li>
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                        Dashboard
                    </a>
                </li>

                <!-- Organization Management -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Organization</p>
                </li>
                <li>
                    <a href="{{ route('admin.units.index') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}">
                        <i class="fas fa-building mr-3 w-5 text-center"></i>
                        Units
                        <span class="ml-auto bg-white bg-opacity-20 text-white text-xs rounded-full px-2 py-1">
                            {{ \App\Models\Unit::count() }}
                        </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.departments.index') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                        <i class="fas fa-sitemap mr-3 w-5 text-center"></i>
                        Departments
                        <span class="ml-auto bg-secondary-blue text-white text-xs rounded-full px-2 py-1">
                            {{ \App\Models\Department::count() }}
                        </span>
                    </a>
                </li>

                <!-- Employee Management -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Employee Management</p>
                </li>
                <li>
                    <a href="{{ route('admin.onboarding.index') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.onboarding.*') ? 'active' : '' }}">
                        <i class="fas fa-user-plus mr-3 w-5 text-center"></i>
                        Onboarding
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.employees.index') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                        <i class="fas fa-users mr-3 w-5 text-center"></i>
                        Employees
                        <span class="ml-auto bg-green-500 text-white text-xs rounded-full px-2 py-1">
                            {{ \App\Models\Employee::active()->count() }}
                        </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.employees.import') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.employees.import') ? 'active' : '' }}">
                        <i class="fas fa-file-upload mr-3 w-5 text-center"></i>
                        Upload Employees
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.employees.qr-codes') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.employees.qr-codes') ? 'active' : '' }}">
                        <i class="fas fa-qrcode mr-3 w-5 text-center"></i>
                        QR Codes
                    </a>
                </li>

                <!-- Vendor Management -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Vendor Management</p>
                </li>
                <li>
                    <a href="{{ route('admin.verifications') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.verifications') ? 'active' : '' }}">
                        <i class="fas fa-user-check mr-3 w-5 text-center"></i>
                        Pending Verifications
                        <span class="ml-auto bg-yellow-500 text-white text-xs rounded-full px-2 py-1">
                            {{ \App\Models\Profile::whereHas('user', function($q) { $q->where('role', 2); })->where('is_verified', false)->count() }}
                        </span>
                    </a>
                </li>

                <!-- Analytics & Reports -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">Analytics</p>
                </li>
                <li>
                    <a href="{{ route('admin.analytics') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
                        Analytics Dashboard
                    </a>
                </li>

                <!-- System Management -->
                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider section-header">System</p>
                </li>
                <li>
                    <a href="{{ route('profile.show') }}"
                       class="flex items-center px-3 py-3 text-sm rounded-lg nav-link {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                        <i class="fas fa-cog mr-3 w-5 text-center"></i>
                        System Settings
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

    <script>
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

        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.add('hidden');
            }
        });

        // Set active navigation link
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.href === window.location.href || link.classList.contains('active')) {
                link.classList.add('active');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('mobileSidebarToggle');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth < 768 &&
                !sidebar.contains(event.target) &&
                !toggleBtn.contains(event.target) &&
                event.target.closest('#mainContent')) {

                sidebar.classList.add('hidden');
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-0');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth >= 768) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('ml-0');
                mainContent.classList.add('ml-64');
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-0');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state based on screen size
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.add('hidden');
                document.getElementById('mainContent').classList.remove('ml-64');
                document.getElementById('mainContent').classList.add('ml-0');
            }
        });
    </script>
 <!-- Admin Layout: Add this before closing </body> -->

    @yield('scripts')

</body>
</html>
