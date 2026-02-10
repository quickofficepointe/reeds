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
                <button onclick="generateTestInvoice()" class="bg-secondary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-600 transition duration-300 flex items-center space-x-2">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Generate Test Invoice</span>
                </button>
                <button onclick="refreshInvoices()" class="bg-gray-200 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-300 transition duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Invoice Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalInvoiceRevenue">Ksh 0.00</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-purple-500 text-xl"></i>
                </div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scans</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoicesTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Invoices will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="py-12 text-center hidden">
            <i class="fas fa-file-invoice text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No invoices found</p>
            <p class="text-sm text-gray-400 mt-1">Invoices are generated automatically every 2 weeks</p>
            <button onclick="generateTestInvoice()" class="mt-4 px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600">
                Generate Test Invoice
            </button>
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
                <div class="text-xs text-gray-500 space-y-1">
                    <p>• Invoice periods run from Monday to Saturday</p>
                    <p>• Each invoice covers a 2-week period</p>
                    <p>• Payment rate: Ksh 70 per meal</p>
                    <p>• Invoices are sent to: isaacnmuteru@gmail.com, info@driftplus.co.ke, info@vibeeplug.com, info@quickofficepointe.co.ke</p>
                    <p>• Payment is processed within 7-14 business days</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Test Invoice Modal -->
<div id="testInvoiceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-text-black">Generate Test Invoice</h3>
            <button onclick="closeTestInvoiceModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <p class="text-gray-600 mb-6">This will generate a test invoice for the current 2-week period for testing purposes.</p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
                <select id="invoicePeriod" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="current">Current Period</option>
                    <option value="previous">Previous Period</option>
                    <option value="custom">Custom Period</option>
                </select>
            </div>

            <div id="customPeriodFields" class="hidden space-y-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="customStartDate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="customEndDate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="closeTestInvoiceModal()" class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">
                Cancel
            </button>
            <button onclick="confirmTestInvoice()" class="px-4 py-2 bg-secondary-blue text-white rounded-md hover:bg-blue-600 font-medium">
                Generate
            </button>
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Invoice Number</p>
                        <p class="font-semibold" id="modalInvoiceNumber"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Invoice Date</p>
                        <p class="font-semibold" id="modalInvoiceDate"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Period</p>
                        <p class="font-semibold" id="modalPeriod"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Due Date</p>
                        <p class="font-semibold" id="modalDueDate"></p>
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
                                    <span class="font-semibold">Ksh 70.00</span>
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
                                    <p class="font-medium">Cooperative Bank of Kenya</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Account Name</p>
                                    <p class="font-medium">REEDS Africa Talent Gateway</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Account Number</p>
                                    <p class="font-medium">011 123 456 78900</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Swift Code</p>
                                    <p class="font-medium">KCOOKENA</p>
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

<!-- Debug Info (remove in production) -->
<div class="mt-4 p-4 bg-gray-100 rounded-lg hidden" id="debugInfo">
    <h4 class="font-semibold text-gray-700 mb-2">Debug Info</h4>
    <pre id="debugContent" class="text-xs bg-white p-2 rounded overflow-auto max-h-40"></pre>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentInvoiceId = null;

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Invoices page loaded');
        loadInvoices();
        calculateNextInvoiceDate();

        // Add event listener for period change
        document.getElementById('invoicePeriod').addEventListener('change', function() {
            const customFields = document.getElementById('customPeriodFields');
            if (this.value === 'custom') {
                customFields.classList.remove('hidden');

                // Set default dates
                const today = new Date();
                const oneMonthAgo = new Date(today);
                oneMonthAgo.setMonth(today.getMonth() - 1);

                document.getElementById('customStartDate').value = oneMonthAgo.toISOString().split('T')[0];
                document.getElementById('customEndDate').value = today.toISOString().split('T')[0];
            } else {
                customFields.classList.add('hidden');
            }
        });
    });

    // Load invoices
    async function loadInvoices() {
        showLoading(true);
        hideTableContent();
        hideEmptyState();

        console.log('Loading invoices...');

        try {
            const response = await fetch('{{ route("vendor.invoices.data") }}');

            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Invoices API Response:', data);

            if (data.success) {
                // Check if we have invoices array
                if (data.invoices && Array.isArray(data.invoices)) {
                    console.log(`Found ${data.invoices.length} invoices`);
                    updateInvoiceTable(data.invoices);
                    updateInvoiceStats(data.stats);

                    if (data.invoices.length > 0) {
                        showTableContent();
                        document.getElementById('invoiceCount').textContent =
                            `${data.invoices.length} invoice${data.invoices.length !== 1 ? 's' : ''}`;
                    } else {
                        showEmptyState();
                    }
                } else {
                    console.warn('No invoices array in response:', data);
                    showEmptyState();
                }
            } else {
                throw new Error(data.message || 'Failed to load invoices');
            }
        } catch (error) {
            console.error('Error loading invoices:', error);
            showError('Error Loading Invoices', error.message || 'Failed to load invoices. Please check your connection.');
            showEmptyState();
        } finally {
            showLoading(false);
        }
    }

    // Update invoice table
    function updateInvoiceTable(invoices) {
        const tbody = document.getElementById('invoicesTableBody');

        if (!invoices || invoices.length === 0) {
            tbody.innerHTML = '';
            return;
        }

        tbody.innerHTML = invoices.map((invoice, index) => {
            const amount = parseFloat(invoice.total_amount || 0);
            const isTest = invoice.is_test || false;
            const testBadge = isTest ? '<span class="ml-1 px-1 py-0.5 text-xs bg-gray-200 text-gray-600 rounded">TEST</span>' : '';
            const invoiceId = invoice.id;

            return `
                <tr class="hover:bg-gray-50 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${invoice.invoice_number}${testBadge}</div>
                        <div class="text-xs text-gray-500">${invoice.period}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${invoice.period_start} to ${invoice.period_end}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-semibold text-gray-900">
                            Ksh ${amount.toLocaleString('en-KE', { minimumFractionDigits: 2 })}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            ${invoice.total_scans || 0} scans
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${getStatusBadge(invoice.status)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${invoice.invoice_date || invoice.created_at}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
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
        // Update modal title
        document.getElementById('invoiceModalTitle').textContent = `Invoice #${invoice.invoice_number}`;

        // Update status banner
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
            statusBanner.innerHTML = `
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-clock text-yellow-500 mr-3 text-xl"></i>
                    <div>
                        <p class="font-semibold">This invoice is pending payment</p>
                        <p class="text-sm">Due on ${invoice.due_date}</p>
                    </div>
                </div>
            `;
        } else {
            statusBanner.innerHTML = '';
        }

        // Update basic info
        document.getElementById('modalInvoiceNumber').textContent = invoice.invoice_number;
        document.getElementById('modalInvoiceDate').textContent = invoice.invoice_date;
        document.getElementById('modalPeriod').textContent = `${invoice.period_start} - ${invoice.period_end}`;
        document.getElementById('modalDueDate').textContent = invoice.due_date;
        document.getElementById('modalTotalScans').textContent = invoice.total_scans;
        document.getElementById('modalTotalAmount').textContent = `Ksh ${parseFloat(invoice.total_amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })}`;
        document.getElementById('modalPaymentRef').textContent = invoice.invoice_number;

        // Update download link
        document.getElementById('downloadInvoiceBtn').href = `/vendor/invoices/${currentInvoiceId}/download`;

        // Update invoice items
        const itemsTable = document.getElementById('modalInvoiceItems');
        if (invoice.items && invoice.items.length > 0) {
            itemsTable.innerHTML = invoice.items.map(item => `
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        ${item.date}
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
            `).join('');
        } else {
            itemsTable.innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-3 text-center text-gray-500">
                        No items found
                    </td>
                </tr>
            `;
        }

        // Update notes
        const notesSection = document.getElementById('modalNotesSection');
        const notesContent = document.getElementById('modalNotes');
        if (invoice.notes) {
            notesContent.textContent = invoice.notes;
            notesSection.classList.remove('hidden');
        } else {
            notesSection.classList.add('hidden');
        }

        // Show content
        document.getElementById('invoiceModalContent').classList.remove('hidden');
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        const statusMap = {
            'pending': { color: 'yellow', text: 'Pending' },
            'paid': { color: 'green', text: 'Paid' },
            'overdue': { color: 'red', text: 'Overdue' },
            'draft': { color: 'gray', text: 'Draft' }
        };

        const statusInfo = statusMap[status] || { color: 'gray', text: status };

        return `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-${statusInfo.color}-100 text-${statusInfo.color}-800">
            ${statusInfo.text}
        </span>`;
    }

    // Update invoice stats
    function updateInvoiceStats(stats) {
        if (!stats) {
            console.warn('No stats provided');
            return;
        }

        document.getElementById('pendingInvoices').textContent = stats.pending_invoices || 0;
        document.getElementById('paidInvoices').textContent = stats.paid_invoices || 0;
        document.getElementById('totalInvoiceRevenue').textContent =
            'Ksh ' + (parseFloat(stats.total_revenue || 0)).toLocaleString('en-KE', { minimumFractionDigits: 2 });
    }

    // Calculate next invoice date
    function calculateNextInvoiceDate() {
        const today = new Date();
        const dayOfWeek = today.getDay(); // 0 = Sunday, 6 = Saturday
        const daysUntilSaturday = (6 - dayOfWeek + 7) % 7 || 7;

        let nextSaturday = new Date(today);
        nextSaturday.setDate(today.getDate() + daysUntilSaturday);

        // Check if we need to skip to next 2-week period
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const daysSinceMonthStart = Math.floor((today - startOfMonth) / (1000 * 60 * 60 * 24));
        const weekOfMonth = Math.floor(daysSinceMonthStart / 7) + 1;

        if (weekOfMonth % 2 === 0) {
            // Already on 2-week schedule, add 2 weeks
            nextSaturday.setDate(nextSaturday.getDate() + 7);
        }

        // Set time to 3:00 PM
        nextSaturday.setHours(15, 0, 0, 0);

        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        document.getElementById('nextInvoiceDate').textContent =
            nextSaturday.toLocaleDateString('en-US', options);
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

    // Generate test invoice
    function generateTestInvoice() {
        document.getElementById('testInvoiceModal').classList.remove('hidden');
    }

    function closeTestInvoiceModal() {
        document.getElementById('testInvoiceModal').classList.add('hidden');
    }

    async function confirmTestInvoice() {
        const period = document.getElementById('invoicePeriod').value;
        let startDate = null;
        let endDate = null;

        if (period === 'custom') {
            startDate = document.getElementById('customStartDate').value;
            endDate = document.getElementById('customEndDate').value;

            if (!startDate || !endDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select both start and end dates'
                });
                return;
            }
        }

        try {
            const response = await fetch('{{ route("vendor.invoices.generate-test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Test invoice generated successfully',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#2596be'
                });

                closeTestInvoiceModal();
                loadInvoices(); // Refresh the list
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to generate test invoice',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#2596be'
                });
            }
        } catch (error) {
            console.error('Error generating test invoice:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to generate test invoice. Please try again.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#2596be'
            });
        }
    }

    // Refresh invoices
    function refreshInvoices() {
        loadInvoices();
        calculateNextInvoiceDate();
    }

    // UI Helper Functions
    function showLoading(show) {
        const loadingState = document.getElementById('loadingState');
        if (show) {
            loadingState.classList.remove('hidden');
        } else {
            loadingState.classList.add('hidden');
        }
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
    /* Additional styles for better UI */
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

    /* Modal styles */
    #viewInvoiceModal {
        z-index: 9999;
    }

    /* Status badge colors */
    .bg-yellow-100 { background-color: #fef9c3; }
    .text-yellow-800 { color: #854d0e; }

    .bg-green-100 { background-color: #dcfce7; }
    .text-green-800 { color: #166534; }

    .bg-red-100 { background-color: #fee2e2; }
    .text-red-800 { color: #991b1b; }

    .bg-gray-100 { background-color: #f3f4f6; }
    .text-gray-800 { color: #374151; }
</style>
@endsection
