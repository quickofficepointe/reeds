<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired - Reeds Africa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center">
            <!-- Warning Icon -->
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
            </div>

            <!-- Message -->
            <h1 class="text-2xl font-bold text-gray-900 mb-3">Link Expired or Invalid</h1>
            <p class="text-gray-600 mb-6">
                {{ $message ?? 'This document upload link has expired or is no longer valid.' }}
            </p>

            <!-- Instructions -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">What to do next?</h3>
                <ul class="text-sm text-gray-600 space-y-2 text-left">
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-arrow-right text-blue-500 mt-0.5"></i>
                        <span>Contact your supervisor or HR department for a new link</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-arrow-right text-blue-500 mt-0.5"></i>
                        <span>Ensure you're using the most recent link sent to you</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-arrow-right text-blue-500 mt-0.5"></i>
                        <span>Links expire 30 days after being sent</span>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-phone-alt text-blue-500 mr-2"></i>
                    Contact HR: hr@reedsafrica.com
                </p>
            </div>
        </div>
    </div>
</body>
</html>
