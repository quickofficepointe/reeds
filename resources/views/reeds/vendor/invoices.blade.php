@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Invoices</h1>
                <p class="text-gray-600 mt-2">View and manage your payment invoices</p>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 md:mt-0 flex space-x-3">
                <button onclick="refreshInvoices()" class="bg-gray-200 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-300 transition duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Invoice Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Invoices</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="pendingInvoices">0</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Paid Invoices</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="paidInvoices">0</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Overdue Invoices</p>
                    <p class="text-2xl font-bold text-red-600 mt-2" id="overdueInvoices">0</p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalInvoiceRevenue">Ksh 0.00</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="all">All Invoices</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="searchInput" placeholder="Search invoice number..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
            </div>
            <div class="flex items-end">
                <button onclick="applyFilters()" class="bg-secondary-blue text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Invoice List -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-text-black">Invoice History</h3>
                <span class="text-sm text-gray-500" id="invoiceCount">0 invoices</span>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="py-12 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-4"></div>
            <p class="text-gray-600">Loading invoices...</p>
        </div>

        <!-- Table Content -->
        <div id="tableContent" class="hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period/Cycle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scans</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Invoices will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500" id="paginationInfo"></div>
                    <div class="flex space-x-2">
                        <button id="prevPage" onclick="changePage('prev')" class="px-3 py-1 border rounded-md hover:bg-gray-50 disabled:opacity-50">Previous</button>
                        <span id="currentPage" class="px-3 py-1">Page 1</span>
                        <button id="nextPage" onclick="changePage('next')" class="px-3 py-1 border rounded-md hover:bg-gray-50 disabled:opacity-50">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="py-12 text-center hidden">
            <i class="fas fa-file-invoice text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No invoices found</p>
            <p class="text-sm text-gray-400 mt-1">Invoices are generated automatically every 2 weeks</p>
        </div>
    </div>

    <!-- Next Invoice Info -->
    <div class="mt-6 bg-blue-50 rounded-xl border border-blue-200 p-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-secondary-blue text-xl mt-1 mr-3"></i>
            <div>
                <h4 class="font-semibold text-text-black mb-2">Invoice Generation Schedule</h4>
                <p class="text-sm text-gray-600 mb-3">
                    Invoices are automatically generated every 2 weeks on Saturday at 3:00 PM.
                    The next invoice will be generated on: <span id="nextInvoiceDate" class="font-semibold">Calculating...</span>
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-500">
                    <div>
                        <p class="font-semibold text-gray-700">Period Details:</p>
                        <p>• System started: Monday, February 2, 2026</p>
                        <p>• Invoice periods: Monday to Saturday</p>
                        <p>• Each invoice covers a 2-week period</p>
                        <p>• Current cycle: <span id="currentCycle">Calculating...</span></p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700">Payment Details:</p>
                        <p>• Payment rate: <strong class="text-secondary-blue">Ksh 65 per meal</strong></p>
                        <p>• Invoices sent to: isaacnmuteru@gmail.com, info@driftplus.co.ke, info@vibeeplug.com, info@quickofficepointe.co.ke</p>
                        <p>• Payment due: 30 days from invoice date</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Phone Number Info -->
    <div class="mt-4 bg-green-50 rounded-xl border border-green-200 p-4">
        <div class="flex items-center">
            <i class="fas fa-phone-alt text-green-600 text-lg mr-3"></i>
            <div>
                <p class="text-sm text-gray-700">
                    <span class="font-semibold">Vendor Contact:</span> Your phone number from your profile will appear on invoices for payment inquiries.
                    @if(auth()->user()->profile && auth()->user()->profile->phone_number)
                        <span class="ml-2 text-green-600">Current: {{ auth()->user()->profile->phone_number }}</span>
                    @else
                        <span class="ml-2 text-yellow-600">Please add your phone number in your profile for payment inquiries.</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div id="viewInvoiceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden overflow-y-auto">
    <div class="bg-white rounded-lg w-full max-w-4xl my-8 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 p-6 z-10">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-text-black" id="invoiceModalTitle">Invoice Details</h3>
                <button onclick="closeViewInvoiceModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <!-- Loading State -->
            <div id="invoiceModalLoading" class="py-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-4"></div>
                <p class="text-gray-600">Loading invoice details...</p>
            </div>

            <!-- Invoice Content -->
            <div id="invoiceModalContent" class="hidden">
                <!-- Status Banner -->
                <div id="invoiceStatusBanner" class="mb-6"></div>

                <!-- Invoice Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Invoice Number</p>
                        <p class="font-semibold" id="modalInvoiceNumber"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Invoice Date</p>
                        <p class="font-semibold" id="modalInvoiceDate"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Cycle</p>
                        <p class="font-semibold" id="modalCycle"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Period</p>
                        <p class="font-semibold" id="modalPeriod"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Due Date</p>
                        <p class="font-semibold" id="modalDueDate"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Rate per Meal</p>
                        <p class="font-semibold text-secondary-blue">Ksh 65.00</p>
                    </div>
                </div>

                <!-- Vendor Contact Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Vendor Contact for Payment Inquiries:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Phone</p>
                            <p class="font-medium" id="modalVendorPhone"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="font-medium" id="modalVendorEmail"></p>
                        </div>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="mb-8">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Invoice Items</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scans</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="modalInvoiceItems" class="bg-white divide-y divide-gray-200">
                                <!-- Items will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-text-black mb-4">Total Amount</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Scans:</span>
                                    <span class="font-semibold" id="modalTotalScans">0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Rate per meal:</span>
                                    <span class="font-semibold">Ksh 65.00</span>
                                </div>
                                <div class="border-t pt-3 mt-3">
                                    <div class="flex justify-between text-lg">
                                        <span class="font-bold text-gray-800">Total Amount:</span>
                                        <span class="font-bold text-secondary-blue" id="modalTotalAmount">Ksh 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <!-- Payment Information -->
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-text-black mb-4">Payment Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Bank Name</p>
                                    <p class="font-medium" id="modalBankName">Cooperative Bank of Kenya</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Account Name</p>
                                    <p class="font-medium" id="modalAccountName">REEDS Africa Talent Gateway</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Account Number</p>
                                    <p class="font-medium" id="modalAccountNumber">011 123 456 78900</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Swift Code</p>
                                    <p class="font-medium" id="modalSwiftCode">KCOOKENA</p>
                                </div>
                                <div class="pt-4 border-t">
                                    <p class="text-sm text-gray-600 mb-1">Payment Reference</p>
                                    <p class="font-medium text-secondary-blue" id="modalPaymentRef"></p>
                                    <p class="text-xs text-gray-500 mt-1">Please use this reference when making payment</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div id="modalNotesSection" class="mt-8 hidden">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-text-black mb-2">Notes</h3>
                        <p class="text-gray-700" id="modalNotes"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 bg-white border-t border-gray-200 p-6 z-10">
            <div class="flex justify-end space-x-3">
                <a href="#" id="downloadInvoiceBtn" class="px-4 py-2 bg-secondary-blue text-white rounded-md hover:bg-blue-600 font-medium flex items-center">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
                <button onclick="closeViewInvoiceModal()" class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentInvoiceId = null;
    let currentPage = 1;
    let lastPage = 1;
    let currentFilters = {
        status: 'all',
        search: ''
    };

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Invoices page loaded');
        loadInvoices();
        calculateNextInvoiceDate();
        getCurrentCycle();

        // Add search on enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    });

    // Get current cycle
    async function getCurrentCycle() {
        try {
            const response = await fetch('/vendor/invoices/periods');
            const data = await response.json();
            if (data.current) {
                document.getElementById('currentCycle').textContent =
                    `Cycle ${data.current.cycle_number} (${data.current.period_name})`;
            }
        } catch (error) {
            console.error('Error getting cycle:', error);
        }
    }

    // Apply filters
    function applyFilters() {
        currentFilters.status = document.getElementById('statusFilter').value;
        currentFilters.search = document.getElementById('searchInput').value;
        currentPage = 1;
        loadInvoices();
    }

    // Change page
    function changePage(direction) {
        if (direction === 'prev' && currentPage > 1) {
            currentPage--;
            loadInvoices();
        } else if (direction === 'next' && currentPage < lastPage) {
            currentPage++;
            loadInvoices();
        }
    }

    // Load invoices
    async function loadInvoices() {
        showLoading(true);
        hideTableContent();
        hideEmptyState();

        console.log('Loading invoices...', currentPage);

        try {
            const url = new URL('{{ route("vendor.invoices.data") }}', window.location.origin);
            url.searchParams.append('page', currentPage);
            url.searchParams.append('per_page', 10);

            if (currentFilters.status !== 'all') {
                url.searchParams.append('status', currentFilters.status);
            }

            if (currentFilters.search) {
                url.searchParams.append('search', currentFilters.search);
            }

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}`);
            }

            const data = await response.json();
            console.log('Invoices API Response:', data);

            if (data.success) {
                if (data.invoices && data.invoices.length > 0) {
                    updateInvoiceTable(data.invoices);
                    updateInvoiceStats(data.stats);
                    updatePagination(data.pagination);
                    showTableContent();
                } else {
                    showEmptyState();
                }

                document.getElementById('invoiceCount').textContent =
                    `${data.pagination?.total || 0} invoice${data.pagination?.total !== 1 ? 's' : ''}`;
            } else {
                throw new Error(data.message || 'Failed to load invoices');
            }
        } catch (error) {
            console.error('Error loading invoices:', error);
            showError('Error Loading Invoices', error.message || 'Failed to load invoices.');
            showEmptyState();
        } finally {
            showLoading(false);
        }
    }

    // Update pagination
    function updatePagination(pagination) {
        if (!pagination) return;

        currentPage = pagination.current_page;
        lastPage = pagination.last_page;

        document.getElementById('currentPage').textContent = `Page ${currentPage} of ${lastPage}`;
        document.getElementById('paginationInfo').textContent =
            `Showing ${(currentPage - 1) * pagination.per_page + 1} to ${Math.min(currentPage * pagination.per_page, pagination.total)} of ${pagination.total} entries`;

        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= lastPage;
    }

    // Update invoice table
    function updateInvoiceTable(invoices) {
        const tbody = document.getElementById('invoicesTableBody');

        tbody.innerHTML = invoices.map((invoice, index) => {
            const amount = parseFloat(invoice.total_amount || 0);
            const invoiceId = invoice.id;
            const dueDate = new Date(invoice.due_date);
            const today = new Date();
            const isOverdue = invoice.status === 'pending' && dueDate < today;

            return `
                <tr class="hover:bg-gray-50 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">${invoice.invoice_number}</div>
                        <div class="text-xs text-gray-500">${invoice.period}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">${invoice.period_start} to ${invoice.period_end}</div>
                        ${invoice.cycle ? `<div class="text-xs text-blue-600">Cycle ${invoice.cycle}</div>` : ''}
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold text-gray-900">
                            Ksh ${amount.toLocaleString('en-KE', { minimumFractionDigits: 2 })}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            ${invoice.total_scans || 0} scans
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-600">Ksh 65.00</span>
                    </td>
                    <td class="px-6 py-4">
                        ${getStatusBadge(isOverdue ? 'overdue' : invoice.status)}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm ${isOverdue ? 'text-red-600 font-semibold' : 'text-gray-500'}">
                            ${invoice.due_date}
                            ${isOverdue ? '<span class="ml-1 text-xs">(Overdue)</span>' : ''}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <button onclick="viewInvoice(${invoiceId})"
                                class="text-secondary-blue hover:text-blue-600 mr-3 inline-flex items-center"
                                title="View Invoice">
                            <i class="fas fa-eye mr-1"></i> View
                        </button>
                        <a href="/vendor/invoices/${invoiceId}/download"
                           class="text-green-600 hover:text-green-800 inline-flex items-center"
                           title="Download PDF">
                            <i class="fas fa-download mr-1"></i> PDF
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // View invoice details
    async function viewInvoice(invoiceId) {
        currentInvoiceId = invoiceId;
        showInvoiceModalLoading(true);
        document.getElementById('viewInvoiceModal').classList.remove('hidden');

        try {
            const response = await fetch(`/vendor/invoices/${invoiceId}/details`);

            if (!response.ok) {
                throw new Error(`Failed to fetch invoice details: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                displayInvoiceDetails(data.invoice);
            } else {
                throw new Error(data.message || 'Failed to load invoice details');
            }
        } catch (error) {
            console.error('Error loading invoice details:', error);
            showError('Error Loading Invoice', error.message || 'Failed to load invoice details.');
            closeViewInvoiceModal();
        } finally {
            showInvoiceModalLoading(false);
        }
    }

    // Display invoice details in modal
    function displayInvoiceDetails(invoice) {
        document.getElementById('invoiceModalTitle').textContent = `Invoice #${invoice.invoice_number}`;

        // Status banner
        const statusBanner = document.getElementById('invoiceStatusBanner');
        if (invoice.status === 'paid') {
            statusBanner.innerHTML = `
                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                    <div>
                        <p class="font-semibold">This invoice has been paid</p>
                    </div>
                </div>
            `;
        } else if (invoice.status === 'pending') {
            const dueDate = new Date(invoice.due_date);
            const today = new Date();
            if (dueDate < today) {
                statusBanner.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-3 text-xl"></i>
                        <div>
                            <p class="font-semibold">This invoice is OVERDUE</p>
                            <p class="text-sm">Payment was due on ${invoice.due_date}</p>
                        </div>
                    </div>
                `;
            } else {
                statusBanner.innerHTML = `
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg flex items-center">
                        <i class="fas fa-clock text-yellow-500 mr-3 text-xl"></i>
                        <div>
                            <p class="font-semibold">This invoice is pending payment</p>
                            <p class="text-sm">Due on ${invoice.due_date}</p>
                        </div>
                    </div>
                `;
            }
        } else {
            statusBanner.innerHTML = '';
        }

        // Basic info
        document.getElementById('modalInvoiceNumber').textContent = invoice.invoice_number;
        document.getElementById('modalInvoiceDate').textContent = invoice.invoice_date;
        document.getElementById('modalCycle').textContent = invoice.cycle ? `Cycle ${invoice.cycle}` : 'N/A';
        document.getElementById('modalPeriod').textContent = `${invoice.period_start} - ${invoice.period_end}`;
        document.getElementById('modalDueDate').textContent = invoice.due_date;
        document.getElementById('modalTotalScans').textContent = invoice.total_scans;
        document.getElementById('modalTotalAmount').textContent = `Ksh ${parseFloat(invoice.total_amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })}`;
        document.getElementById('modalPaymentRef').textContent = invoice.invoice_number;

        // Vendor contact
        document.getElementById('modalVendorPhone').textContent = invoice.vendor_phone || 'Not provided';
        document.getElementById('modalVendorEmail').textContent = invoice.vendor_email || 'N/A';

        // Bank details (if available from invoice)
        if (invoice.bank_details) {
            document.getElementById('modalBankName').textContent = invoice.bank_details.bank_name || 'Cooperative Bank of Kenya';
            document.getElementById('modalAccountName').textContent = invoice.bank_details.account_name || 'REEDS Africa Talent Gateway';
            document.getElementById('modalAccountNumber').textContent = invoice.bank_details.account_number || '011 123 456 78900';
            document.getElementById('modalSwiftCode').textContent = invoice.bank_details.swift_code || 'KCOOKENA';
        }

        // Download link
        document.getElementById('downloadInvoiceBtn').href = `/vendor/invoices/${currentInvoiceId}/download`;

        // Invoice items
        const itemsTable = document.getElementById('modalInvoiceItems');
        if (invoice.items && invoice.items.length > 0) {
            itemsTable.innerHTML = invoice.items.map(item => {
                const date = new Date(item.date);
                const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
                return `
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            ${item.date}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            ${dayName}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            ${item.description}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            ${item.scans}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            Ksh ${parseFloat(item.rate).toLocaleString('en-KE', { minimumFractionDigits: 2 })}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                            Ksh ${parseFloat(item.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })}
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            itemsTable.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-3 text-center text-gray-500">
                        No items found
                    </td>
                </tr>
            `;
        }

        // Notes
        const notesSection = document.getElementById('modalNotesSection');
        const notesContent = document.getElementById('modalNotes');
        if (invoice.notes) {
            notesContent.textContent = invoice.notes;
            notesSection.classList.remove('hidden');
        } else {
            notesSection.classList.add('hidden');
        }

        document.getElementById('invoiceModalContent').classList.remove('hidden');
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        const statusConfig = {
            'paid': { bg: 'bg-green-100', text: 'text-green-800', label: 'Paid' },
            'pending': { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending' },
            'overdue': { bg: 'bg-red-100', text: 'text-red-800', label: 'Overdue' },
            'draft': { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Draft' }
        };

        const config = statusConfig[status] || statusConfig.draft;

        return `<span class="px-2 py-1 text-xs font-semibold rounded-full ${config.bg} ${config.text}">
            ${config.label}
        </span>`;
    }

    // Update invoice stats
    function updateInvoiceStats(stats) {
        if (!stats) return;

        document.getElementById('pendingInvoices').textContent = stats.pending_invoices || 0;
        document.getElementById('paidInvoices').textContent = stats.paid_invoices || 0;
        document.getElementById('overdueInvoices').textContent = stats.overdue_invoices || 0;
        document.getElementById('totalInvoiceRevenue').textContent =
            'Ksh ' + (parseFloat(stats.total_revenue || 0)).toLocaleString('en-KE', { minimumFractionDigits: 2 });
    }

    // Calculate next invoice date
    function calculateNextInvoiceDate() {
        const startDate = new Date('2026-02-02'); // System start date
        const today = new Date();

        // Calculate days since start
        const daysSinceStart = Math.floor((today - startDate) / (1000 * 60 * 60 * 24));

        // Calculate current cycle (14-day cycles)
        const currentCycle = Math.floor(daysSinceStart / 14) + 1;

        // Calculate next invoice date (Saturday of current/next cycle)
        const daysToNextSaturday = (6 - today.getDay() + 7) % 7;
        let nextInvoiceDate = new Date(today);
        nextInvoiceDate.setDate(today.getDate() + daysToNextSaturday);

        // If we're past Thursday, move to next cycle
        if (today.getDay() > 4) { // After Thursday
            nextInvoiceDate.setDate(nextInvoiceDate.getDate() + 7);
        }

        nextInvoiceDate.setHours(15, 0, 0, 0);

        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        document.getElementById('nextInvoiceDate').textContent =
            nextInvoiceDate.toLocaleDateString('en-US', options);
    }

    // Modal functions
    function showInvoiceModalLoading(show) {
        const loading = document.getElementById('invoiceModalLoading');
        const content = document.getElementById('invoiceModalContent');

        if (show) {
            loading.classList.remove('hidden');
            content.classList.add('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }

    function closeViewInvoiceModal() {
        document.getElementById('viewInvoiceModal').classList.add('hidden');
        document.getElementById('invoiceModalContent').classList.add('hidden');
        currentInvoiceId = null;
    }

    function refreshInvoices() {
        loadInvoices();
        calculateNextInvoiceDate();
    }

    // UI Helper Functions
    function showLoading(show) {
        document.getElementById('loadingState').classList.toggle('hidden', !show);
    }

    function showTableContent() {
        document.getElementById('tableContent').classList.remove('hidden');
        document.getElementById('emptyState').classList.add('hidden');
    }

    function hideTableContent() {
        document.getElementById('tableContent').classList.add('hidden');
    }

    function showEmptyState() {
        document.getElementById('emptyState').classList.remove('hidden');
        document.getElementById('tableContent').classList.add('hidden');
    }

    function hideEmptyState() {
        document.getElementById('emptyState').classList.add('hidden');
    }

    function showError(title, message) {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#2596be'
        });
    }
</script>

<style>
    #loadingState {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    #emptyState {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    #viewInvoiceModal {
        z-index: 9999;
    }

    button:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .bg-yellow-100 { background-color: #fef9c3; }
    .text-yellow-800 { color: #854d0e; }
    .bg-green-100 { background-color: #dcfce7; }
    .text-green-800 { color: #166534; }
    .bg-red-100 { background-color: #fee2e2; }
    .text-red-800 { color: #991b1b; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .text-gray-800 { color: #374151; }
    .bg-blue-100 { background-color: #dbeafe; }
    .text-blue-800 { color: #1e40af; }
</style>
@endsection
