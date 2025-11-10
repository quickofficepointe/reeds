@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Pending Vendor Verifications</h1>
                <p class="text-gray-600 mt-2">Review and verify vendor registration requests</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                    {{ $pendingVendors->count() }} Pending Verification{{ $pendingVendors->count() !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Review</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $pendingVendors->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Verified This Month</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $verifiedThisMonth }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Vendors</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $totalVendors }}</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-secondary-blue text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Vendors List -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-text-black">Vendors Awaiting Approval</h2>
        </div>

        @if($pendingVendors->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($pendingVendors as $vendor)
                <div class="p-6 hover:bg-gray-50 transition duration-150">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <!-- Vendor Info -->
                        <div class="flex items-start space-x-4 flex-1">
                            <!-- Profile Photo -->
                            <div class="flex-shrink-0">
                                @if($vendor->photo)
                                    <img src="{{ Storage::url($vendor->photo) }}" alt="Profile" class="w-16 h-16 rounded-full object-cover border-2 border-yellow-200">
                                @else
                                    <div class="w-16 h-16 rounded-full bg-yellow-100 border-2 border-yellow-200 flex items-center justify-center">
                                        <i class="fas fa-store text-yellow-500 text-xl"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Vendor Details -->
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="text-lg font-semibold text-text-black">{{ $vendor->user->name }}</h3>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                    <div class="space-y-1">
                                        <p><i class="fas fa-envelope mr-2 text-gray-400"></i> {{ $vendor->user->email }}</p>
                                        <p><i class="fas fa-phone mr-2 text-gray-400"></i> {{ $vendor->phone ?? 'Not provided' }}</p>
                                        <p><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i> {{ $vendor->location ?? 'Location not specified' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p><i class="fas fa-building mr-2 text-gray-400"></i> {{ $vendor->business_name ?? 'Business name not provided' }}</p>
                                        <p><i class="fas fa-user mr-2 text-gray-400"></i> {{ $vendor->contact_person ?? 'Contact person not specified' }}</p>
                                        <p><i class="fas fa-calendar mr-2 text-gray-400"></i> Registered {{ $vendor->user->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>

                                <!-- Additional Information -->
                                @if($vendor->description)
                                <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-700">{{ $vendor->description }}</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row lg:flex-col space-y-2 sm:space-y-0 sm:space-x-2 lg:space-x-0 lg:space-y-2 mt-4 lg:mt-0 lg:ml-6">
                            <button onclick="verifyVendor({{ $vendor->id }})"
                                    class="flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-150 font-medium">
                                <i class="fas fa-check mr-2"></i> Approve
                            </button>
                            <button onclick="showRejectModal({{ $vendor->id }})"
                                    class="flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-150 font-medium">
                                <i class="fas fa-times mr-2"></i> Reject
                            </button>
                            <button onclick="viewVendorDetails({{ $vendor->id }})"
                                    class="flex items-center justify-center px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150 font-medium">
                                <i class="fas fa-eye mr-2"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-text-black mb-2">All Caught Up!</h3>
                <p class="text-gray-600 mb-6">There are no pending vendor verifications at the moment.</p>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if($pendingVendors->hasPages())
    <div class="mt-6">
        {{ $pendingVendors->links() }}
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-text-black">Reject Vendor</h3>
            <button onclick="closeRejectModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="rejectForm">
            @csrf
            <input type="hidden" id="rejectVendorId" name="vendor_id">

            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Rejection</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Please provide a reason for rejecting this vendor..."
                          required></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-150">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-150 font-medium">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Vendor Details Modal -->
<div id="vendorDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-text-black">Vendor Details</h3>
            <button onclick="closeVendorDetailsModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div id="vendorDetailsContent">
            <!-- Vendor details will be loaded here -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Verify vendor
    function verifyVendor(vendorId) {
        Swal.fire({
            title: 'Approve Vendor?',
            text: 'This vendor will be approved and can start scanning QR codes.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Approve!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admin/verify/${vendorId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Vendor Approved!',
                            text: 'Vendor has been successfully verified.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to approve vendor.'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while approving the vendor.'
                    });
                });
            }
        });
    }

    // Show reject modal
    function showRejectModal(vendorId) {
        document.getElementById('rejectVendorId').value = vendorId;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    // Close reject modal
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejection_reason').value = '';
    }

    // Reject vendor
    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const vendorId = document.getElementById('rejectVendorId').value;
        const reason = document.getElementById('rejection_reason').value;

        fetch(`/admin/reject/${vendorId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Vendor Rejected',
                    text: 'Vendor has been rejected successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to reject vendor.'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while rejecting the vendor.'
            });
        });
    });

    // View vendor details
    function viewVendorDetails(vendorId) {
        // Show loading state
        document.getElementById('vendorDetailsContent').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-secondary-blue mb-2"></i>
                <p>Loading vendor details...</p>
            </div>
        `;

        document.getElementById('vendorDetailsModal').classList.remove('hidden');

        // In a real implementation, you would fetch vendor details via AJAX
        // For now, we'll show a static message
        setTimeout(() => {
            document.getElementById('vendorDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business Name</label>
                            <p class="mt-1 text-sm text-gray-900">Vendor Business</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                            <p class="mt-1 text-sm text-gray-900">John Doe</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <p class="mt-1 text-sm text-gray-900">+254 712 345 678</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <p class="mt-1 text-sm text-gray-900">Nairobi, Kenya</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                        <p class="mt-1 text-sm text-gray-900">2 days ago</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Additional Information</label>
                        <p class="mt-1 text-sm text-gray-900">This vendor specializes in providing meals for corporate clients.</p>
                    </div>
                </div>
            `;
        }, 1000);
    }

    // Close vendor details modal
    function closeVendorDetailsModal() {
        document.getElementById('vendorDetailsModal').classList.add('hidden');
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
        const rejectModal = document.getElementById('rejectModal');
        const detailsModal = document.getElementById('vendorDetailsModal');

        if (event.target === rejectModal) {
            closeRejectModal();
        }
        if (event.target === detailsModal) {
            closeVendorDetailsModal();
        }
    });
</script>
@endsection
