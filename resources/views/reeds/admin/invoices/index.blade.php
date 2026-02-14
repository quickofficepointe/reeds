@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text-black">Invoice Management</h1>
        <p class="text-gray-600 mt-2">View and manage all vendor invoices</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Invoices</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['total_invoices'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-invoice text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-2">{{ $stats['pending_invoices'] }}</p>
                    <p class="text-xs text-gray-500">Ksh {{ number_format($stats['pending_amount'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Overdue</p>
                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $stats['overdue_invoices'] }}</p>
                    <p class="text-xs text-gray-500">Ksh {{ number_format($stats['overdue_amount'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">{{ $stats['paid_invoices'] }}</p>
                    <p class="text-xs text-gray-500">Ksh {{ number_format($stats['total_revenue'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Recipients Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-envelope text-blue-600 mt-1 mr-3"></i>
            <div>
                <p class="font-semibold text-blue-800">Invoice Email Recipients:</p>
                <p class="text-sm text-blue-600">
                    emmanuel.bore@unityhomes.co.ke, unityhomesreeds@gmail.com, julius.kibe@unityhomes.co.ke,
                    hr@reedsconsulting.com, gm@reedsconsluting.com
                </p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="statusFilter" class="w-full px-3 py-2 border rounded-md">
                    <option value="all">All Invoices</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" id="dateFrom" class="w-full px-3 py-2 border rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" id="dateTo" class="w-full px-3 py-2 border rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="searchInput" placeholder="Invoice # or Vendor..."
                       class="w-full px-3 py-2 border rounded-md">
            </div>
        </div>
        <div class="flex justify-end mt-4 space-x-2">
            <button onclick="applyFilters()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
            <button onclick="sendBulkEmails()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-envelope mr-2"></i>Send Bulk Emails
            </button>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="rounded">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scans</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="invoicesTableBody" class="bg-white divide-y divide-gray-200">
                    <!-- Loaded via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t">
            <div class="flex items-center justify-between">
                <div id="paginationInfo" class="text-sm text-gray-500"></div>
                <div class="flex space-x-2">
                    <button id="prevPage" onclick="changePage('prev')"
                            class="px-3 py-1 border rounded-md hover:bg-gray-50">Previous</button>
                    <span id="currentPage" class="px-3 py-1">Page 1</span>
                    <button id="nextPage" onclick="changePage('next')"
                            class="px-3 py-1 border rounded-md hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add CSRF Token Meta -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
let currentPage = 1;
let lastPage = 1;
let selectedInvoices = [];

document.addEventListener('DOMContentLoaded', function() {
    loadInvoices();

    document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        if (e.target.checked) {
            selectedInvoices = Array.from(checkboxes).map(cb => cb.value);
        } else {
            selectedInvoices = [];
        }
    });
});

function loadInvoices() {
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const search = document.getElementById('searchInput').value;

    let url = `/admin/invoices/data?page=${currentPage}`;
    if (status !== 'all') url += `&status=${status}`;
    if (dateFrom) url += `&date_from=${dateFrom}`;
    if (dateTo) url += `&date_to=${dateTo}`;
    if (search) url += `&search=${search}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateTable(data.invoices);
                updatePagination(data.pagination);
            }
        })
        .catch(error => console.error('Error loading invoices:', error));
}

function updateTable(invoices) {
    const tbody = document.getElementById('invoicesTableBody');
    tbody.innerHTML = invoices.map(inv => `
        <tr>
            <td class="px-6 py-4">
                <input type="checkbox" class="invoice-checkbox rounded" value="${inv.id}"
                       onchange="toggleInvoice(${inv.id})">
            </td>
            <td class="px-6 py-4">
                <div class="font-medium">${inv.invoice_number}</div>
                <div class="text-xs text-gray-500">${inv.period}</div>
            </td>
            <td class="px-6 py-4">
                <div class="font-medium">${inv.vendor_name}</div>
                <div class="text-xs text-gray-500">${inv.vendor_phone || 'No phone'}</div>
            </td>
            <td class="px-6 py-4 text-sm">
                ${inv.period_start} to ${inv.period_end}
                ${inv.cycle ? `<br><span class="text-xs text-blue-600">Cycle ${inv.cycle}</span>` : ''}
            </td>
            <td class="px-6 py-4 font-semibold">${inv.formatted_total}</td>
            <td class="px-6 py-4">${inv.total_scans}</td>
            <td class="px-6 py-4">${inv.status_badge}</td>
            <td class="px-6 py-4 ${inv.is_overdue ? 'text-red-600 font-semibold' : ''}">
                ${inv.due_date}
                ${inv.is_overdue ? '<br><span class="text-xs">Overdue</span>' : ''}
            </td>
            <td class="px-6 py-4">
                <a href="/admin/invoices/${inv.id}" class="text-blue-600 hover:text-blue-900 mr-2">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="/admin/invoices/${inv.id}/download" class="text-green-600 hover:text-green-900 mr-2">
                    <i class="fas fa-download"></i>
                </a>
                <button onclick="sendReminder(${inv.id})" class="text-yellow-600 hover:text-yellow-900"
                        title="Send Email Reminder">
                    <i class="fas fa-envelope"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function toggleInvoice(id) {
    const index = selectedInvoices.indexOf(id.toString());
    if (index === -1) {
        selectedInvoices.push(id.toString());
    } else {
        selectedInvoices.splice(index, 1);
    }
}

function sendBulkEmails() {
    if (selectedInvoices.length === 0) {
        alert('Please select at least one invoice');
        return;
    }

    if (!confirm(`Send email reminders for ${selectedInvoices.length} invoice(s)?`)) return;

    fetch('/admin/invoices/send-bulk-emails', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ invoice_ids: selectedInvoices })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
        } else {
            alert('Failed to send emails');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending emails');
    });
}

function sendReminder(id) {
    if (!confirm('Send invoice reminder email?')) return;

    fetch(`/admin/invoices/${id}/send-reminder`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Reminder email sent');
        } else {
            alert('Failed to send reminder');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending reminder');
    });
}

function applyFilters() {
    currentPage = 1;
    loadInvoices();
}

function changePage(direction) {
    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
        loadInvoices();
    } else if (direction === 'next' && currentPage < lastPage) {
        currentPage++;
        loadInvoices();
    }
}

function updatePagination(pagination) {
    currentPage = pagination.current_page;
    lastPage = pagination.last_page;

    document.getElementById('currentPage').textContent = `Page ${currentPage} of ${lastPage}`;
    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= lastPage;
}
</script>
@endsection
