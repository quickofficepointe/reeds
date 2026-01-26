<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Successful - Reeds Africa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check-circle text-green-600 text-3xl"></i>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-gray-900 mb-3">Documents Submitted Successfully!</h1>
            <p class="text-gray-600 mb-6">
                Thank you, <span class="font-semibold">{{ $employee->first_name }}</span>.
                Your documents have been received and will be reviewed by our HR team.
            </p>

            <!-- Employee Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-center space-x-3 mb-2">
                    <i class="fas fa-user-circle text-gray-400"></i>
                    <span class="font-medium">{{ $employee->formal_name }}</span>
                </div>
                <div class="text-sm text-gray-500">
                    Employee Code: {{ $employee->employee_code }}
                </div>
            </div>

            <!-- Next Steps -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">What happens next?</h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>HR will review your documents within 3-5 business days</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>You'll be contacted if additional information is needed</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-check text-green-500 mt-0.5"></i>
                        <span>Once verified, your documents will be marked as complete</span>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-100">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    For any questions, please contact HR department.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-500">
                &copy; {{ date('Y') }} Reeds Africa. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
