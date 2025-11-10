<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <!-- Tailwind CSS CDN Link -->
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
</head>
<body class="bg-white min-h-screen p-4">

    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-text-black">QR Feeding System</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('profile.edit') }}"
                       class="text-secondary-blue hover:text-[#1e7a9e] transition duration-150 font-medium">
                        Edit Profile
                    </a>
                    @auth
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                               class="bg-primary-red text-white px-4 py-2 rounded-lg hover:bg-[#c22120] transition duration-300">
                                Dashboard
                            </a>
                        @elseif(Auth::user()->isVendor() && $profile->isVerified())
                            <a href="{{ route('vendor.dashboard') }}"
                               class="bg-primary-red text-white px-4 py-2 rounded-lg hover:bg-[#c22120] transition duration-300">
                                Dashboard
                            </a>
                        @endif
                    @endauth
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="text-text-black hover:text-gray-700 transition duration-150">
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden">

            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-secondary-blue to-primary-red p-6 text-white">
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        @if($profile->photo)
                            <img src="{{ Storage::url($profile->photo) }}" alt="Profile Photo"
                                 class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                        @else
                            <div class="w-24 h-24 rounded-full bg-white bg-opacity-20 border-4 border-white flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm font-medium">No Photo</span>
                            </div>
                        @endif
                        <div class="absolute -bottom-2 -right-2 bg-white text-primary-red rounded-full p-1 shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ $profile->user->name }}</h1>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                {{ $profile->user->getRoleName() }}
                            </span>
                            @if($profile->user->isVendor())
                                @if($profile->isVerified())
                                    <span class="bg-green-500 px-3 py-1 rounded-full text-sm font-medium flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Verified</span>
                                    </span>
                                @else
                                    <span class="bg-yellow-500 px-3 py-1 rounded-full text-sm font-medium flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Pending Verification</span>
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="p-6">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-text-black border-b border-gray-200 pb-2">
                            Personal Information
                        </h2>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">Full Name:</span>
                                <span class="text-text-black font-semibold">{{ $profile->user->name }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">Email Address:</span>
                                <span class="text-text-black font-semibold">{{ $profile->user->email }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">Phone Number:</span>
                                <span class="text-text-black font-semibold">{{ $profile->phone_number ?? 'Not provided' }}</span>
                            </div>

                            <div class="flex justify-between items-start py-2">
                                <span class="text-gray-600 font-medium">Bio:</span>
                                <span class="text-text-black font-semibold text-right max-w-xs">
                                    {{ $profile->bio ?? 'Not provided' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-text-black border-b border-gray-200 pb-2">
                            Account Status
                        </h2>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">Profile Status:</span>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                    Complete
                                </span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">Email Verified:</span>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                    Verified
                                </span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">User Role:</span>
                                <span class="bg-secondary-blue text-white px-3 py-1 rounded-full text-sm font-medium">
                                    {{ $profile->user->getRoleName() }}
                                </span>
                            </div>

                            @if($profile->user->isVendor() && $profile->isVerified())
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Verified On:</span>
                                    <span class="text-text-black font-semibold">
                                        {{ $profile->verified_at->format('M j, Y') }}
                                    </span>
                                </div>

                                @if($profile->verifier)
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">Verified By:</span>
                                    <span class="text-text-black font-semibold">
                                        {{ $profile->verifier->name }}
                                    </span>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('profile.edit') }}"
                       class="bg-secondary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#1e7a9e] transition duration-300 shadow-md text-center">
                        Edit Profile
                    </a>

                    @auth
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                               class="bg-primary-red text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md text-center">
                                Go to Dashboard
                            </a>
                        @elseif(Auth::user()->isVendor() && $profile->isVerified())
                            <a href="{{ route('vendor.dashboard') }}"
                               class="bg-primary-red text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md text-center">
                                Go to Dashboard
                            </a>
                        @endif
                    @endauth
                </div>

                <!-- Verification Notice for Vendors -->
                @if($profile->user->isVendor() && !$profile->isVerified())
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h3 class="text-sm font-semibold text-yellow-800">Pending Verification</h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Your vendor account is pending verification by an administrator.
                                    You will gain access to the vendor dashboard once your account is approved.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</body>
</html>
