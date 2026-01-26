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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">With Unit Assignment</p>
                    @php
                        $vendorsWithUnit = \App\Models\User::where('role', 2)
                            ->whereNotNull('unit_id')
                            ->count();
                    @endphp
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $vendorsWithUnit }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-purple-500 text-xl"></i>
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
                                    @if($vendor->user->unit_id)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            <i class="fas fa-building mr-1"></i>
                                            {{ $vendor->user->unit->name ?? 'Unit' }}
                                        </span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                    <div class="space-y-1">
                                        <p><i class="fas fa-envelope mr-2 text-gray-400"></i> {{ $vendor->user->email }}</p>
                                        <p><i class="fas fa-phone mr-2 text-gray-400"></i> {{ $vendor->phone_number ?? 'Not provided' }}</p>
                                        <p><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i> {{ $vendor->location ?? 'Location not specified' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p><i class="fas fa-building mr-2 text-gray-400"></i> {{ $vendor->business_name ?? 'Business name not provided' }}</p>
                                        <p><i class="fas fa-user mr-2 text-gray-400"></i> {{ $vendor->contact_person ?? 'Contact person not specified' }}</p>
                                        <p><i class="fas fa-calendar mr-2 text-gray-400"></i> Registered {{ $vendor->user->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>

                                <!-- Current Unit Assignment (if any) -->
                                @if($vendor->user->unit_id)
                                <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                        <span class="text-sm text-blue-700">
                                            Currently assigned to:
                                            <strong>{{ $vendor->user->unit->name }}</strong>
                                            @if($vendor->user->unit->code)
                                                ({{ $vendor->user->unit->code }})
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                @endif

                                <!-- Additional Information -->
                                @if($vendor->bio)
                                <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-700">{{ $vendor->bio }}</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons with Unit Selection -->
                        <div class="flex flex-col sm:flex-row lg:flex-col space-y-2 sm:space-y-0 sm:space-x-2 lg:space-x-0 lg:space-y-2 mt-4 lg:mt-0 lg:ml-6 min-w-[250px]">
                            <!-- Unit Selection Dropdown -->
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Assign to Unit</label>
                                <select id="unitSelect_{{ $vendor->id }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">-- No Unit --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}"
                                            {{ $vendor->user->unit_id == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }} @if($unit->code)({{ $unit->code }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Select unit for vendor access</p>
                            </div>

                            <!-- Action Buttons -->
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
    // Verify vendor WITH UNIT ASSIGNMENT
    function verifyVendor(vendorId) {
        const unitSelect = document.getElementById(`unitSelect_${vendorId}`);
        const unitId = unitSelect ? unitSelect.value : null;
        const unitName = unitSelect && unitSelect.value ?
            unitSelect.options[unitSelect.selectedIndex].text : 'No Unit';

        Swal.fire({
            title: 'Approve Vendor?',
            html: unitId ?
                `<div class="text-left">
                    <p>This vendor will be approved and assigned to:</p>
                    <div class="mt-2 p-3 bg-green-50 rounded-lg border border-green-200">
                        <i class="fas fa-building text-green-500 mr-2"></i>
                        <strong>${unitName}</strong>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">The vendor will only be able to scan QR codes in this unit.</p>
                </div>` :
                `<div class="text-left">
                    <p>This vendor will be approved without unit assignment.</p>
                    <div class="mt-2 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                        <strong>No Unit Assigned</strong>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">The vendor can scan QR codes in any unit.</p>
                </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: unitId ? 'Approve with Unit' : 'Approve',
            cancelButtonText: 'Cancel',
            width: 500
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admin/verify/${vendorId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        unit_id: unitId || null
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Vendor Approved!',
                            html: `<div class="text-left">
                                <p>${data.success}</p>
                                ${unitId ?
                                    `<div class="mt-3 p-3 bg-green-50 rounded-lg">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        Assigned to: <strong>${unitName}</strong>
                                    </div>` :
                                    `<div class="mt-3 p-3 bg-yellow-50 rounded-lg">
                                        <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                                        No unit assigned. Can scan in any unit.
                                    </div>`
                                }
                            </div>`,
                            timer: 3000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Failed to approve vendor.'
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

        // Fetch vendor details via AJAX
        fetch(`/admin/vendor/${vendorId}/details`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.vendor) {
                    const vendor = data.vendor;

                    document.getElementById('vendorDetailsContent').innerHTML = `
                        <div class="space-y-6">
                            <!-- Profile Header -->
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    ${vendor.photo ?
                                        `<img src="${vendor.photo}" alt="Profile" class="w-20 h-20 rounded-full object-cover border-2 border-blue-200">` :
                                        `<div class="w-20 h-20 rounded-full bg-blue-100 border-2 border-blue-200 flex items-center justify-center">
                                            <i class="fas fa-store text-blue-500 text-2xl"></i>
                                        </div>`
                                    }
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-text-black">${vendor.name}</h4>
                                    <p class="text-gray-600">${vendor.email}</p>
                                    ${vendor.unit ?
                                        `<div class="mt-2 inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            <i class="fas fa-building mr-1"></i>
                                            ${vendor.unit.name} ${vendor.unit.code ? `(${vendor.unit.code})` : ''}
                                        </div>` :
                                        `<div class="mt-2 inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            No Unit Assigned
                                        </div>`
                                    }
                                </div>
                            </div>

                            <!-- Vendor Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">${vendor.business_name}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">${vendor.contact_person}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">${vendor.location}</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">${vendor.phone}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Registered On</label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">${vendor.registered_at}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <div class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                            <i class="fas fa-clock mr-1"></i>
                                            Pending Verification
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            ${vendor.description ? `
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Information</label>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-700">${vendor.description}</p>
                                </div>
                            </div>
                            ` : ''}

                            <!-- Current Unit Assignment -->
                            ${vendor.unit ? `
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h5 class="font-medium text-blue-800 mb-1">Current Unit Assignment</h5>
                                        <p class="text-sm text-blue-700">
                                            This vendor is currently assigned to <strong>${vendor.unit.name}</strong>
                                            ${vendor.unit.code ? `(${vendor.unit.code})` : ''}
                                        </p>
                                        <p class="text-xs text-blue-600 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            The vendor can only scan QR codes in this unit
                                        </p>
                                    </div>
                                    <div class="text-blue-500">
                                        <i class="fas fa-building text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    document.getElementById('vendorDetailsContent').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                            <p>Failed to load vendor details.</p>
                            <p class="text-sm text-gray-600 mt-1">${data.error || 'Please try again.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('vendorDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                        <p>Error loading vendor details.</p>
                        <p class="text-sm text-gray-600 mt-1">Please try again later.</p>
                    </div>
                `;
            });
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
