<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile</title>

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
<body class="bg-white min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-100">

        <h2 class="text-3xl font-bold text-text-black mb-6 text-center">
            Complete Your Profile
        </h2>

        @if(session('warning'))
            <div class="mb-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg text-sm">
                {{ session('warning') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Profile Photo Upload -->
            <div class="mb-6 text-center">
                <div class="relative inline-block">
                    @if($profile->photo)
                        <img src="{{ Storage::url($profile->photo) }}" alt="Profile Photo"
                             class="w-32 h-32 rounded-full object-cover border-4 border-secondary-blue shadow-md">
                    @else
                        <div class="w-32 h-32 rounded-full bg-gray-200 border-4 border-secondary-blue flex items-center justify-center shadow-md">
                            <span class="text-gray-500 text-sm">No Photo</span>
                        </div>
                    @endif

                    <!-- Camera Icon Overlay -->
                    <label for="photo" class="absolute bottom-0 right-0 bg-primary-red text-white p-2 rounded-full cursor-pointer shadow-lg hover:bg-[#c22120] transition duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </label>
                </div>
                <input type="file" id="photo" name="photo" accept="image/*" class="hidden">
                <p class="text-xs text-gray-500 mt-2">Click camera icon to upload photo</p>
            </div>

            <!-- Phone Number Field -->
            <div class="mb-4">
                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                <input type="text" id="phone_number" name="phone_number"
                       value="{{ old('phone_number', $profile->phone_number) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                       placeholder="Enter your phone number">
            </div>

            <!-- Bio Field -->
            <div class="mb-6">
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                <textarea id="bio" name="bio" rows="4"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black resize-none"
                          placeholder="Tell us about yourself...">{{ old('bio', $profile->bio) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Maximum 500 characters</p>
            </div>

            <!-- Current User Info (Read-only) -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Account Information</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="text-text-black font-medium">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="text-text-black font-medium">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Role:</span>
                        <span class="text-text-black font-medium">{{ auth()->user()->getRoleName() }}</span>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-primary-red text-white py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md text-lg">
                Save Profile
            </button>

            <!-- Back to Profile Link -->
            <div class="mt-4 text-center">
                <a href="{{ route('profile.show') }}"
                   class="text-secondary-blue hover:text-[#1e7a9e] transition duration-150 font-medium text-sm">
                    ← Back to Profile
                </a>
            </div>
        </form>
    </div>

    <!-- JavaScript for Image Preview -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const photoInput = document.getElementById('photo');
            const profileImage = document.querySelector('img[alt="Profile Photo"]');
            const noPhotoDiv = document.querySelector('.bg-gray-200');

            photoInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();

                    reader.onload = (e) => {
                        // Remove no photo div if it exists
                        if (noPhotoDiv) {
                            noPhotoDiv.style.display = 'none';
                        }

                        // Create or update image
                        if (profileImage) {
                            profileImage.src = e.target.result;
                        } else {
                            const newImg = document.createElement('img');
                            newImg.src = e.target.result;
                            newImg.alt = 'Profile Photo';
                            newImg.className = 'w-32 h-32 rounded-full object-cover border-4 border-secondary-blue shadow-md';
                            document.querySelector('.relative').insertBefore(newImg, document.querySelector('label[for="photo"]'));
                        }
                    };

                    reader.readAsDataURL(file);
                }
            });
        });
    </script>

</body>
</html>
