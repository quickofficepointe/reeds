<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Feeding System - Vendor</title>

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
                        'bg-white': '#ffffff',
                        'text-black': '#000000',
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
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background-color: #FFFFFF !important;
            color: #2596be !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 bg-secondary-blue shadow-sm h-16">
        <div class="flex items-center justify-between h-full px-4">
            <div class="flex items-center">
                <button class="mr-4 text-white md:hidden" id="mobileSidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="flex items-center" href="{{ route('vendor.dashboard') }}">
                    <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-2">
                        <i class="fas fa-qrcode text-white"></i>
                    </div>
                    <span class="font-bold text-white text-xl">QR Feeding System</span>
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="flex items-center text-white focus:outline-none" id="userDropdown">
                        <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                            <i class="fas fa-store text-white"></i>
                        </div>
                        <span class="ml-2 hidden md:inline font-medium">{{ Auth::user()->name }}</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="fixed top-16 left-0 bottom-0 w-64 text-white z-20 overflow-y-auto sidebar hidden md:block" id="sidebar">
        <div class="p-4">
            <!-- User Profile -->
            <div class="flex items-center mb-6 p-3 bg-white bg-opacity-10 rounded-lg">
                <div class="relative mr-3">
                    @if(Auth::user()->profile && Auth::user()->profile->photo)
                        <img src="{{ Storage::url(Auth::user()->profile->photo) }}" alt="Profile" class="w-10 h-10 rounded-full object-cover">
                    @else
                        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                            <i class="fas fa-store text-white"></i>
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-secondary-blue"></span>
                </div>
                <div>
                    <h6 class="font-medium">{{ Auth::user()->name }}</h6>
                    <p class="text-xs opacity-75">
                        @if(Auth::user()->profile && Auth::user()->profile->isVerified())
                            Verified Vendor
                        @else
                            Pending Verification
                        @endif
                    </p>
                </div>
            </div>

            <!-- Navigation Links -->
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="flex items-center px-3 py-3 text-sm rounded-md nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                    </a>
                </li>

                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white text-opacity-70">
                        QR Operations</p>
                </li>
                <li>
                    <a href="{{ route('vendor.scan') }}" class="flex items-center px-3 py-3 text-sm rounded-md nav-link {{ request()->routeIs('vendor.scan') ? 'active' : '' }}">
                        <i class="fas fa-camera mr-3"></i> Scan QR Code
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center px-3 py-3 text-sm rounded-md nav-link">
                        <i class="fas fa-history mr-3"></i> Scan History
                        <span class="ml-auto bg-primary-red text-white text-xs rounded-full px-2 py-1">
                            0
                        </span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center px-3 py-3 text-sm rounded-md nav-link">
                        <i class="fas fa-chart-line mr-3"></i> Today's Stats
                    </a>
                </li>

                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white text-opacity-70">
                        Reports</p>
                </li>
                <li>
                    <a href="#" class="flex items-center px-3 py-3 text-sm rounded-md nav-link">
                        <i class="fas fa-file-alt mr-3"></i> Daily Reports
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center px-3 py-3 text-sm rounded-md nav-link">
                        <i class="fas fa-calendar-alt mr-3"></i> Monthly Summary
                    </a>
                </li>

                <li class="mt-4">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white text-opacity-70">
                        Account</p>
                </li>
                <li>
                    <a href="{{ route('profile.show') }}" class="flex items-center px-3 py-3 text-sm rounded-md nav-link">
                        <i class="fas fa-user mr-3"></i> My Profile
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-3 py-3 text-sm rounded-md nav-link text-left">
                            <i class="fas fa-sign-out-alt mr-3"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content ml-0 md:ml-64 mt-16 p-4 min-h-screen" id="mainContent">
        @yield('content')
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('mobileSidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('hidden');
        });

        // Set active navigation link
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('mobileSidebarToggle');

            if (window.innerWidth < 768 && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                sidebar.classList.add('hidden');
            }
        });
    </script>

    @yield('scripts')
</body>
</html>
