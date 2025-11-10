<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Feeding System</title>

    <link rel="icon" href="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-32x32.png" sizes="32x32">
    <link rel="icon" href="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-192x192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-180x180.png">
    <meta name="msapplication-TileImage" content="https://reedsafricaconsult.com/wp-content/uploads/2024/04/cropped-reeds_logo-270x270.png">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Colors -->
     <meta name="theme-color" content="#e82b2a">

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
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-secondary-blue focus:outline-none transition duration-150">
                                <div class="w-8 h-8 bg-secondary-blue rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="font-medium">{{ Auth::user()->name }}</span>
                            </button>
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

    @yield('scripts')
</body>
</html>
