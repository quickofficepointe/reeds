@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-text-black">Employees</h1>
            <p class="text-gray-600 mt-2">Manage employee information, QR codes, and document uploads</p>
        </div>
        <div class="flex flex-wrap gap-3 mt-4 md:mt-0">
            <!-- Document Invitation Dropdown -->
            <div class="relative group">
                <button id="documentInvitationBtn"
                        class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-300 shadow-md flex items-center space-x-2">
                    <i class="fas fa-file-upload"></i>
                    <span>Document Invitations</span>
                    <i class="fas fa-chevron-down text-xs ml-1"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="invitationDropdown"
                     class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50">
                    <div class="p-2">
                        <button onclick="sendBulkDocumentInvitations()"
                                class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 rounded-md flex items-center space-x-2">
                            <i class="fas fa-paper-plane text-blue-500"></i>
                            <div>
                                <div class="font-medium">Send to Selected</div>
                                <div class="text-xs text-gray-500">Send to checked employees</div>
                            </div>
                        </button>
                        <button onclick="sendToAllWithoutDocuments()"
                                class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 rounded-md flex items-center space-x-2 mt-1">
                            <i class="fas fa-users text-green-500"></i>
                            <div>
                                <div class="font-medium">Send to All Missing</div>
                                <div class="text-xs text-gray-500">Send to employees without documents</div>
                            </div>
                        </button>
                        <div class="border-t border-gray-200 mt-2 pt-2">
                            <button onclick="viewInvitationStatus()"
                                    class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 rounded-md flex items-center space-x-2">
                                <i class="fas fa-chart-bar text-purple-500"></i>
                                <div>
                                    <div class="font-medium">View Status</div>
                                    <div class="text-xs text-gray-500">Track invitation progress</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Buttons -->
            <a href="{{ route('admin.employees.import') }}" class="bg-secondary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#1e7a9e] transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-upload"></i>
                <span>Import</span>
            </a>
            <a href="{{ route('admin.employees.export') }}" class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-download"></i>
                <span>Export</span>
            </a>
            <button onclick="openCreateModal()" class="bg-primary-red text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add Employee</span>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->total() }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-primary-red text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->where('is_active', true)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">With QR Codes</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->where('qr_code', '!=', null)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-secondary-blue text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">With Documents</p>
                    @php
                        $withDocuments = $employees->filter(function($employee) {
                            return $employee->documents && $employee->documents->hasAllRequiredDocuments();
                        })->count();
                    @endphp
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $withDocuments }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-1">
                <input type="text"
                       id="searchInput"
                       placeholder="Search by name, code, phone, email, department, unit, next of kin..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150">
            </div>
            <div class="flex space-x-3">
                <select id="departmentFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                <select id="unitFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue">
                    <option value="">All Units</option>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select id="documentFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue">
                    <option value="">Document Status</option>
                    <option value="complete">Complete</option>
                    <option value="incomplete">Incomplete</option>
                    <option value="invited">Invitation Sent</option>
                    <option value="pending">No Documents</option>
                </select>
                <button onclick="resetFilters()" class="px-4 py-2 text-gray-600 hover:text-gray-900 transition duration-150">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
        </div>
        <!-- Search results info -->
        <div id="searchResultsInfo" class="mt-2 text-sm text-gray-500 hidden">
            Found <span id="searchResultsCount">0</span> results
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulkActionsBar" class="bg-blue-50 rounded-xl shadow-md border border-blue-200 p-4 mb-6 hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-blue-800">
                    <span id="selectedCount">0</span> employee(s) selected
                </span>
                <div class="flex space-x-2">
                    <button onclick="sendBulkDocumentInvitations()"
                            class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition duration-150 flex items-center space-x-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Document Invitation</span>
                    </button>
                    <button onclick="bulkDeactivate()"
                            class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600 transition duration-150 flex items-center space-x-2">
                        <i class="fas fa-pause"></i>
                        <span>Deactivate Selected</span>
                    </button>
                    <button onclick="bulkActivate()"
                            class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition duration-150 flex items-center space-x-2">
                        <i class="fas fa-play"></i>
                        <span>Activate Selected</span>
                    </button>
                    <button onclick="bulkDelete()"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition duration-150 flex items-center space-x-2">
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                    </button>
                    <button onclick="clearSelection()"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm">
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Department/Unit</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Designation</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Documents</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">QR Code</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="employeesTableBody" class="divide-y divide-gray-200">
                    @forelse($employees as $employee)
                    @php
                        $hasDocuments = $employee->documents && $employee->documents->hasAllRequiredDocuments();
                        $invitation = $employee->documentInvitation;
                        $documentStatus = 'pending';
                        $statusColor = 'red';
                        $statusIcon = 'times-circle';
                        $statusText = 'Missing';

                        if ($hasDocuments) {
                            $documentStatus = 'complete';
                            $statusColor = 'green';
                            $statusIcon = 'check-circle';
                            $statusText = 'Complete';
                        } elseif ($invitation) {
                            if ($invitation->status === 'completed') {
                                $documentStatus = 'complete';
                                $statusColor = 'green';
                                $statusIcon = 'check-circle';
                                $statusText = 'Complete';
                            } elseif ($invitation->status === 'opened') {
                                $documentStatus = 'invited';
                                $statusColor = 'yellow';
                                $statusIcon = 'eye';
                                $statusText = 'Link Opened';
                            } elseif ($invitation->status === 'sent') {
                                $documentStatus = 'invited';
                                $statusColor = 'blue';
                                $statusIcon = 'paper-plane';
                                $statusText = 'Invitation Sent';
                            }
                        }
                    @endphp
                    <tr class="hover:bg-gray-50 transition duration-150"
                        data-dept-id="{{ $employee->department_id }}"
                        data-unit-id="{{ $employee->unit_id ?? '' }}"
                        data-doc-status="{{ $documentStatus }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox"
                                   value="{{ $employee->id }}"
                                   class="employee-checkbox rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue"
                                   data-employee-id="{{ $employee->id }}"
                                   {{ !$employee->phone ? 'disabled' : '' }}>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-text-black">{{ $employee->formal_name }}</div>
                                <div class="text-sm text-gray-500">{{ $employee->employee_code }}</div>
                                @if($employee->payroll_no)
                                <div class="text-xs text-gray-400">Payroll: {{ $employee->payroll_no }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($employee->email)
                            <div class="text-sm text-gray-900 flex items-center space-x-1">
                                <i class="fas fa-envelope text-gray-400 text-xs"></i>
                                <span>{{ $employee->email }}</span>
                            </div>
                            @endif
                            @if($employee->phone)
                            <div class="text-sm text-gray-500 flex items-center space-x-1">
                                <i class="fas fa-phone text-gray-400 text-xs"></i>
                                <span>{{ $employee->phone }}</span>
                            </div>
                            @else
                            <div class="text-sm text-red-500 flex items-center space-x-1">
                                <i class="fas fa-exclamation-circle text-xs"></i>
                                <span>No phone number</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $employee->department->name }}</div>
                            @if($employee->subDepartment)
                            <div class="text-xs text-gray-500">{{ $employee->subDepartment->name }}</div>
                            @endif
                            @if($employee->unit)
                            <div class="text-xs text-blue-600 font-medium mt-1">
                                <i class="fas fa-building mr-1"></i>{{ $employee->unit->name }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $employee->designation ?? 'N/A' }}</div>
                            @if($employee->category)
                            <div class="text-xs text-gray-500">{{ $employee->category }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                <i class="fas fa-{{ $statusIcon }} mr-1"></i> {{ $statusText }}
                                @if($invitation && $invitation->reminder_count > 0 && $invitation->status === 'sent')
                                <span class="ml-1 text-xs">({{ $invitation->reminder_count }}R)</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($employee->qr_code)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i> Generated
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-times mr-1"></i> Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $employee->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="openEditModal({{ $employee->id }})" class="text-secondary-blue hover:text-[#1e7a9e] transition duration-150" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="viewEmployee({{ $employee->id }})" class="text-gray-600 hover:text-gray-900 transition duration-150" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(!$employee->qr_code)
                                <button onclick="generateQrCode({{ $employee->id }})" class="text-green-600 hover:text-green-800 transition duration-150" title="Generate QR Code">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                @endif

                                <!-- Document Invitation Button -->
                                @if(!$hasDocuments && $employee->phone)
                                <button onclick="sendDocumentInvitation({{ $employee->id }})"
                                        class="text-purple-600 hover:text-purple-800 transition duration-150"
                                        title="Send Document Invitation">
                                    <i class="fas fa-file-upload"></i>
                                </button>
                                @endif

                                <!-- Reminder Button -->
                                @if($invitation && $invitation->status === 'sent' && $invitation->reminder_count < 3 && $employee->phone)
                                <button onclick="sendDocumentReminder({{ $employee->id }})"
                                        class="text-orange-600 hover:text-orange-800 transition duration-150"
                                        title="Send Reminder ({{ $invitation->reminder_count }}/3)">
                                    <i class="fas fa-bell"></i>
                                </button>
                                @endif

                                <button onclick="toggleStatus({{ $employee->id }})" class="text-{{ $employee->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $employee->is_active ? 'yellow' : 'green' }}-800 transition duration-150" title="{{ $employee->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fas fa-{{ $employee->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button onclick="confirmDelete({{ $employee->id }}, '{{ $employee->formal_name }}')" class="text-primary-red hover:text-[#c22120] transition duration-150" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg">No employees found</p>
                            <p class="text-sm mt-1">Get started by adding your first employee</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
        <div class="px-6 py-4 border-t border-gray-200" id="paginationContainer">
            {{ $employees->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-xl font-bold text-text-black">Add Employee</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="employeeForm" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" id="employee_id" name="id">
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employee_code" class="block text-sm font-medium text-gray-700 mb-1">Employee Code *</label>
                        <input type="text" id="employee_code" name="employee_code" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., EMP000464">
                        <div id="employee_code_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="payroll_no" class="block text-sm font-medium text-gray-700 mb-1">Payroll Number</label>
                        <input type="text" id="payroll_no" name="payroll_no"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter payroll number">
                        <div id="payroll_no_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                        <select id="department_id" name="department_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <div id="department_id_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="sub_department_id" class="block text-sm font-medium text-gray-700 mb-1">Sub-Department</label>
                        <select id="sub_department_id" name="sub_department_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Sub-Department</option>
                            @foreach($subDepartments as $subDepartment)
                            <option value="{{ $subDepartment->id }}" data-department="{{ $subDepartment->department_id }}">{{ $subDepartment->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                        <select id="unit_id" name="unit_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                        <select id="employment_type" name="employment_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="Regular">Regular</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                            <option value="Intern">Intern</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <select id="title" name="title"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Title</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                        </select>
                    </div>

                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter first name">
                        <div id="first_name_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter last name">
                        <div id="last_name_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                </div>

                <div>
                    <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                           placeholder="Enter middle name (optional)">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="employee@company.com">
                        <div id="email_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <input type="text" id="phone" name="phone" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="254712345678">
                        <div id="phone_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select id="gender" name="gender"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="designation" class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                        <input type="text" id="designation" name="designation"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., Mason, Welder">
                    </div>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <input type="text" id="category" name="category"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                           placeholder="e.g., New ham, Omuford">
                </div>

                <div id="statusField" class="hidden">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                        <span class="text-sm font-medium text-gray-700">Active Employee</span>
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="bg-primary-red text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md">
                        Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="viewModalTitle" class="text-xl font-bold text-text-black">Employee Details</h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="viewModalContent" class="space-y-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Invitation Status Modal -->
<div id="invitationStatusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-text-black">Document Invitation Status</h3>
                <button onclick="closeInvitationStatusModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="invitationStatusContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-8">
                    <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600">Loading invitation status...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document View Modal -->
<div id="documentViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="documentModalTitle" class="text-xl font-bold text-text-black">View Document</h3>
                <button onclick="closeDocumentModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="documentModalContent" class="space-y-4">
                <div class="text-center py-8">
                    <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600">Loading document...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="confirmModalTitle" class="text-xl font-bold text-text-black">Confirm Action</h3>
                <button onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="confirmModalMessage" class="text-gray-600 mb-6">
                Are you sure you want to perform this action?
            </div>
            <div class="flex items-center justify-end space-x-3">
                <button onclick="closeConfirmModal()" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition duration-150">
                    Cancel
                </button>
                <button id="confirmModalBtn" class="bg-primary-red text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // =============================================
    // GLOBAL VARIABLES
    // =============================================
    let searchTimeout;
    let currentAction = null;
    let currentEmployeeId = null;

    // =============================================
    // SEARCH AND FILTER FUNCTIONS
    // =============================================

    // Search with debounce
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performSearch(), 500);
    });

    // Filter change events
    document.getElementById('departmentFilter').addEventListener('change', performSearch);
    document.getElementById('unitFilter').addEventListener('change', performSearch);
    document.getElementById('documentFilter').addEventListener('change', performSearch);

    async function performSearch(page = 1) {
    const searchTerm = document.getElementById('searchInput').value;
    const departmentId = document.getElementById('departmentFilter').value;
    const unitId = document.getElementById('unitFilter').value;
    const documentStatus = document.getElementById('documentFilter').value;

    console.log('Searching with params:', { searchTerm, departmentId, unitId, documentStatus, page });

    // Show loading state
    const tbody = document.getElementById('employeesTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-lg">Searching...</p>
            </td>
        </tr>
    `;

    try {
        const url = new URL('{{ route("admin.employees.search") }}', window.location.origin);
        url.searchParams.append('search', searchTerm);
        url.searchParams.append('department_id', departmentId);
        url.searchParams.append('unit_id', unitId);
        url.searchParams.append('document_status', documentStatus);
        url.searchParams.append('page', page);

        console.log('Request URL:', url.toString());

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.status === 403) {
            showNotification('error', 'Session expired. Please refresh the page.');
            setTimeout(() => window.location.reload(), 2000);
            return;
        }

        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
            // Update table with new HTML
            tbody.innerHTML = data.html;

            // Update pagination
            const paginationDiv = document.getElementById('paginationContainer');
            if (paginationDiv) {
                paginationDiv.innerHTML = data.pagination;
            }

            // Show results count
            const searchInfo = document.getElementById('searchResultsInfo');
            const searchCount = document.getElementById('searchResultsCount');
            if (searchTerm || departmentId || unitId || documentStatus) {
                searchCount.textContent = data.total_count;
                searchInfo.classList.remove('hidden');
            } else {
                searchInfo.classList.add('hidden');
            }

            // Re-attach event listeners
            attachEventListeners();

            // Scroll to top of table for better UX
            document.querySelector('.overflow-x-auto')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            console.error('Search failed:', data);
            showNotification('error', data.error || 'Search failed');
        }
    } catch (error) {
        console.error('Search error:', error);
        showNotification('error', 'Search failed. Please try again.');
    }
}
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('departmentFilter').value = '';
        document.getElementById('unitFilter').value = '';
        document.getElementById('documentFilter').value = '';
        performSearch();
    }

    // =============================================
    // CHECKBOX AND BULK ACTIONS
    // =============================================

function attachEventListeners() {
    console.log('Attaching event listeners...');

    // Select All checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.removeEventListener('change', selectAllHandler);
        selectAll.addEventListener('change', selectAllHandler);
    }

    // Individual checkboxes
    document.querySelectorAll('.employee-checkbox').forEach(cb => {
        cb.removeEventListener('change', updateSelectedCount);
        cb.addEventListener('change', updateSelectedCount);
    });

    // Pagination links - catch ALL possible pagination links
    const paginationLinks = document.querySelectorAll(
        '.pagination a, ' +
        '.pagination .page-link, ' +
        '.pagination .page-item a, ' +
        '.pagination li a, ' +
        '#paginationContainer a'
    );

    console.log('Found pagination links:', paginationLinks.length);

    paginationLinks.forEach(link => {
        // Remove any existing listeners
        link.removeEventListener('click', paginationHandler);
        // Add new listener
        link.addEventListener('click', paginationHandler);

        // Also set the href to javascript:void(0) to prevent default behavior
        // But store the original href in a data attribute
        if (!link.hasAttribute('data-original-href')) {
            link.setAttribute('data-original-href', link.href);
        }
    });
}

    function selectAllHandler(e) {
        const checkboxes = document.querySelectorAll('.employee-checkbox:not(:disabled)');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateSelectedCount();
    }

    function paginationHandler(e) {
    e.preventDefault();
    e.stopPropagation(); // Add this to prevent event bubbling

    // Get the page number from the href
    const url = new URL(this.href);
    const page = url.searchParams.get('page') || 1;

    console.log('Pagination clicked - going to page:', page);

    // Get current filter values
    const searchTerm = document.getElementById('searchInput').value;
    const departmentId = document.getElementById('departmentFilter').value;
    const unitId = document.getElementById('unitFilter').value;
    const documentStatus = document.getElementById('documentFilter').value;

    // Perform search with page parameter
    performSearch(page);
}

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.employee-checkbox:checked:not(:disabled)').length;
        document.getElementById('selectedCount').textContent = selected;

        if (selected > 0) {
            document.getElementById('bulkActionsBar').classList.remove('hidden');
        } else {
            document.getElementById('bulkActionsBar').classList.add('hidden');
        }
    }

    function clearSelection() {
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelectedCount();
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.employee-checkbox:checked:not(:disabled)'))
            .map(cb => cb.value);
    }

    // =============================================
    // BULK ACTIONS
    // =============================================

    async function bulkDeactivate() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            showNotification('error', 'Please select at least one employee.');
            return;
        }

        if (await confirmAction(`Deactivate ${ids.length} employee(s)?`)) {
            try {
                const response = await fetch('{{ route("admin.employees.bulk-status-update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids, status: false })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.success);
                    clearSelection();
                    setTimeout(() => performSearch(), 1000);
                } else {
                    showNotification('error', data.error || 'Failed to deactivate employees.');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            }
        }
    }

    async function bulkActivate() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            showNotification('error', 'Please select at least one employee.');
            return;
        }

        if (await confirmAction(`Activate ${ids.length} employee(s)?`)) {
            try {
                const response = await fetch('{{ route("admin.employees.bulk-status-update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids, status: true })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.success);
                    clearSelection();
                    setTimeout(() => performSearch(), 1000);
                } else {
                    showNotification('error', data.error || 'Failed to activate employees.');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            }
        }
    }

    async function bulkDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            showNotification('error', 'Please select at least one employee.');
            return;
        }

        if (await confirmAction(`Delete ${ids.length} employee(s)? This action cannot be undone.`)) {
            try {
                const response = await fetch('{{ route("admin.employees.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.success);
                    clearSelection();
                    setTimeout(() => performSearch(), 1000);
                } else {
                    showNotification('error', data.error || 'Failed to delete employees.');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            }
        }
    }

    // =============================================
    // INDIVIDUAL EMPLOYEE ACTIONS
    // =============================================

    function toggleStatus(employeeId) {
        confirmAction('Are you sure you want to change the status of this employee?').then(confirmed => {
            if (confirmed) {
                fetch(`/admin/employees/${employeeId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.success);
                        setTimeout(() => performSearch(), 1000);
                    } else {
                        showNotification('error', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
            }
        });
    }

    function confirmDelete(employeeId, employeeName) {
        confirmAction(`Are you sure you want to delete "${employeeName}"? This action cannot be undone.`).then(confirmed => {
            if (confirmed) {
                fetch(`/admin/employees/${employeeId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.success);
                        setTimeout(() => performSearch(), 1000);
                    } else {
                        showNotification('error', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
            }
        });
    }

    function generateQrCode(employeeId) {
        confirmAction('Are you sure you want to generate QR code for this employee?').then(confirmed => {
            if (confirmed) {
                fetch(`/admin/employees/${employeeId}/generate-qr`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.success);
                        setTimeout(() => performSearch(), 1000);
                    } else {
                        showNotification('error', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
            }
        });
    }

    // =============================================
    // DOCUMENT INVITATION FUNCTIONS
    // =============================================

    function sendDocumentInvitation(employeeId) {
        confirmAction('Send document invitation to this employee?').then(confirmed => {
            if (confirmed) {
                fetch(`/admin/employees/${employeeId}/documents/send-invitation`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.success);
                        setTimeout(() => performSearch(), 1500);
                    } else {
                        showNotification('error', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
            }
        });
    }

    function sendDocumentReminder(employeeId) {
        confirmAction('Send reminder for document upload?').then(confirmed => {
            if (confirmed) {
                fetch(`/admin/employees/${employeeId}/documents/send-reminder`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.success);
                        setTimeout(() => performSearch(), 1500);
                    } else {
                        showNotification('error', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
            }
        });
    }

    function sendBulkDocumentInvitations() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            showNotification('error', 'Please select at least one employee with a phone number.');
            return;
        }

        confirmAction(`Send document invitations to ${ids.length} selected employees?`).then(confirmed => {
            if (confirmed) {
                fetch('{{ route("admin.employees.bulk-send-document-invitations") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.success);
                        clearSelection();
                        setTimeout(() => performSearch(), 2000);
                    } else {
                        showNotification('error', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
            }
        });
    }

    async function sendToAllWithoutDocuments() {
        try {
            const response = await fetch('{{ route("admin.employees.stats") }}');
            const data = await response.json();

            if (data.success && data.stats) {
                const totalEmployees = data.stats.total_employees;

                confirmAction(`This will send document invitations to all ${totalEmployees} employees without complete documents. Are you sure?`).then(confirmed => {
                    if (confirmed) {
                        const allEmployees = Array.from(document.querySelectorAll('.employee-checkbox:not(:disabled)'))
                            .map(cb => cb.value);

                        if (allEmployees.length === 0) {
                            showNotification('error', 'No employees found with phone numbers.');
                            return;
                        }

                        fetch('{{ route("admin.employees.bulk-send-document-invitations") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ ids: allEmployees })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('success', data.success);
                                clearSelection();
                                setTimeout(() => performSearch(), 2000);
                            } else {
                                showNotification('error', data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('error', 'An error occurred. Please try again.');
                        });
                    }
                });
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Failed to get employee statistics.');
        }
    }

    async function viewInvitationStatus() {
        document.getElementById('invitationStatusModal').classList.remove('hidden');

        try {
            const response = await fetch('{{ route("admin.employees.stats") }}');
            const data = await response.json();

            if (data.success) {
                const stats = data.stats;
                const totalEmployees = stats.total_employees;
                const employeesWithDocuments = stats.employees_with_documents || 0;
                const pendingInvitations = stats.pending_invitations || 0;
                const completedInvitations = stats.completed_invitations || 0;

                const content = `
                    <div class="space-y-6">
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="text-sm font-medium text-blue-600">Total Employees</div>
                                <div class="text-2xl font-bold text-gray-900 mt-1">${totalEmployees}</div>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="text-sm font-medium text-green-600">Documents Complete</div>
                                <div class="text-2xl font-bold text-gray-900 mt-1">${employeesWithDocuments}</div>
                                <div class="text-xs text-gray-500 mt-1">${Math.round((employeesWithDocuments/totalEmployees)*100)}% of total</div>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="text-sm font-medium text-yellow-600">Invitations Sent</div>
                                <div class="text-2xl font-bold text-gray-900 mt-1">${pendingInvitations}</div>
                            </div>
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <div class="text-sm font-medium text-purple-600">Completed Uploads</div>
                                <div class="text-2xl font-bold text-gray-900 mt-1">${completedInvitations}</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Document Completion Progress</span>
                                <span class="text-sm font-medium text-gray-900">${employeesWithDocuments}/${totalEmployees}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-green-600 h-2.5 rounded-full" style="width: ${Math.round((employeesWithDocuments/totalEmployees)*100)}%"></div>
                            </div>
                        </div>

                        <!-- Status Legend -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Status Legend</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Complete
                                    </span>
                                    <span class="text-xs text-gray-600">Documents uploaded</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-paper-plane mr-1"></i> Sent
                                    </span>
                                    <span class="text-xs text-gray-600">Invitation sent</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-eye mr-1"></i> Opened
                                    </span>
                                    <span class="text-xs text-gray-600">Link opened</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i> Missing
                                    </span>
                                    <span class="text-xs text-gray-600">No documents</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3 pt-4 border-t border-gray-200">
                            <button onclick="sendToAllWithoutDocuments()"
                                    class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition duration-150">
                                Send to All Missing
                            </button>
                            <button onclick="closeInvitationStatusModal()"
                                    class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition duration-150">
                                Close
                            </button>
                        </div>
                    </div>
                `;

                document.getElementById('invitationStatusContent').innerHTML = content;
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('invitationStatusContent').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
                    <p>Failed to load invitation status</p>
                </div>
            `;
        }
    }

    function closeInvitationStatusModal() {
        document.getElementById('invitationStatusModal').classList.add('hidden');
    }

    // =============================================
    // MODAL FUNCTIONS
    // =============================================

    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Add Employee';
        document.getElementById('employeeForm').reset();
        document.getElementById('employee_id').value = '';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('statusField').classList.add('hidden');
        document.getElementById('employeeModal').classList.remove('hidden');
        clearErrors();
        filterSubDepartments();
    }

    async function openEditModal(employeeId) {
        try {
            const response = await fetch(`/admin/employees/${employeeId}/edit`);
            const data = await response.json();

            if (data.employee) {
                const employee = data.employee;

                document.getElementById('modalTitle').textContent = 'Edit Employee';
                document.getElementById('employee_id').value = employee.id;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('employee_code').value = employee.employee_code;
                document.getElementById('payroll_no').value = employee.payroll_no || '';
                document.getElementById('department_id').value = employee.department_id;
                document.getElementById('sub_department_id').value = employee.sub_department_id || '';
                document.getElementById('unit_id').value = employee.unit_id || '';
                document.getElementById('title').value = employee.title || '';
                document.getElementById('first_name').value = employee.first_name;
                document.getElementById('middle_name').value = employee.middle_name || '';
                document.getElementById('last_name').value = employee.last_name;
                document.getElementById('email').value = employee.email || '';
                document.getElementById('phone').value = employee.phone || '';
                document.getElementById('employment_type').value = employee.employment_type;
                document.getElementById('gender').value = employee.gender || '';
                document.getElementById('designation').value = employee.designation || '';
                document.getElementById('category').value = employee.category || '';
                document.getElementById('is_active').checked = employee.is_active;
                document.getElementById('statusField').classList.remove('hidden');
                document.getElementById('employeeModal').classList.remove('hidden');
                clearErrors();
                filterSubDepartments();
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Failed to load employee data');
        }
    }

    async function viewEmployee(employeeId) {
        try {
            const response = await fetch(`/admin/employees/${employeeId}`);
            const data = await response.json();

            if (data.success && data.employee) {
                const employee = data.employee;

                // Format the content for the view modal
                let content = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Employee Code:</span>
                                    <span class="text-sm text-gray-900">${employee.employee_code}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Full Name:</span>
                                    <span class="text-sm text-gray-900">${employee.formal_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Gender:</span>
                                    <span class="text-sm text-gray-900">${employee.gender || 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Contact Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Email:</span>
                                    <span class="text-sm text-gray-900">${employee.email || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Phone:</span>
                                    <span class="text-sm text-gray-900">${employee.phone || 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Department & Unit</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Department:</span>
                                    <span class="text-sm text-gray-900">${employee.department?.name || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Sub-Department:</span>
                                    <span class="text-sm text-gray-900">${employee.sub_department?.name || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Unit:</span>
                                    <span class="text-sm text-gray-900">${employee.unit?.name || 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Employment Details</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Designation:</span>
                                    <span class="text-sm text-gray-900">${employee.designation || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Category:</span>
                                    <span class="text-sm text-gray-900">${employee.category || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Employment Type:</span>
                                    <span class="text-sm text-gray-900">${employee.employment_type || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Status:</span>
                                    <span class="text-sm ${employee.is_active ? 'text-green-600' : 'text-red-600'} font-medium">
                                        ${employee.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('viewModalTitle').textContent = `Employee: ${employee.employee_code}`;
                document.getElementById('viewModalContent').innerHTML = content;
                document.getElementById('viewModal').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Failed to load employee details');
        }
    }

    function closeModal() {
        document.getElementById('employeeModal').classList.add('hidden');
        clearErrors();
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }

    function closeDocumentModal() {
        document.getElementById('documentViewModal').classList.add('hidden');
    }

    function clearErrors() {
        document.querySelectorAll('[id$="_error"]').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
        });
    }

    function filterSubDepartments() {
        const departmentId = document.getElementById('department_id').value;
        const subDepartmentSelect = document.getElementById('sub_department_id');
        const options = subDepartmentSelect.getElementsByTagName('option');

        for (let i = 1; i < options.length; i++) {
            const option = options[i];
            const optionDepartment = option.getAttribute('data-department');
            option.style.display = (!departmentId || optionDepartment === departmentId) ? '' : 'none';
            if (option.style.display === 'none' && option.selected) {
                option.selected = false;
            }
        }
    }

    document.getElementById('department_id').addEventListener('change', filterSubDepartments);

    // =============================================
    // FORM SUBMISSION
    // =============================================

    document.getElementById('employeeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Saving...';

        const form = this;
        const formData = new FormData(form);
        const employeeId = document.getElementById('employee_id').value;

        const action = employeeId ? `/admin/employees/${employeeId}` : '/admin/employees';

        try {
            const response = await fetch(action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (response.status === 422) {
                if (data.errors) {
                    displayValidationErrors(data.errors);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
                return;
            }

            if (response.ok) {
                if (data.success) {
                    closeModal();
                    showNotification('success', data.success);
                    setTimeout(() => performSearch(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            } else {
                showNotification('error', data.error || 'An error occurred. Please try again.');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showNotification('error', 'Network error. Please check your connection and try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    function displayValidationErrors(errors) {
        clearErrors();
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(`${field}_error`);
            if (errorElement) {
                errorElement.textContent = errors[field][0];
                errorElement.classList.remove('hidden');
                const inputElement = document.getElementById(field);
                if (inputElement) inputElement.classList.add('border-red-500');
            }
        });
    }

    // =============================================
    // DOCUMENT FUNCTIONS
    // =============================================

    function downloadDocument(employeeId, documentType) {
        const url = `/admin/employees/${employeeId}/documents/${documentType}/download`;
        window.open(url, '_blank');
    }

    function viewDocument(employeeId, documentType) {
        const url = `/admin/employees/${employeeId}/documents/${documentType}/view`;

        const modal = document.getElementById('documentViewModal');
        const documentTitles = {
            'national_id_photo': 'National ID',
            'passport_photo': 'Passport Photo',
            'passport_size_photo': 'Passport Size Photo',
            'nssf_card_photo': 'NSSF Card',
            'sha_card_photo': 'SHA Card',
            'kra_certificate_photo': 'KRA Certificate'
        };

        document.getElementById('documentModalTitle').textContent = `View ${documentTitles[documentType] || 'Document'}`;
        modal.classList.remove('hidden');

        fetch(url)
            .then(response => response.text())
            .then(html => {
                document.getElementById('documentModalContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('documentModalContent').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
                        <p>Failed to load document</p>
                    </div>
                `;
            });
    }

    // =============================================
    // CONFIRMATION MODAL
    // =============================================

    function confirmAction(message) {
        return new Promise((resolve) => {
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModal').classList.remove('hidden');

            const confirmBtn = document.getElementById('confirmModalBtn');

            // Remove any existing listeners
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            newConfirmBtn.onclick = function() {
                closeConfirmModal();
                resolve(true);
            };

            // Handle cancel - remove any existing listeners first
            const closeModal = document.getElementById('confirmModal');
            const cancelHandler = function(e) {
                if (e.target.id === 'confirmModal' || e.target.closest('button')?.textContent === 'Cancel') {
                    closeConfirmModal();
                    resolve(false);
                }
            };

            closeModal.removeEventListener('click', cancelHandler);
            closeModal.addEventListener('click', cancelHandler, { once: true });
        });
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }

    // =============================================
    // NOTIFICATION FUNCTION
    // =============================================

    function showNotification(type, message) {
        // Remove existing notifications
        document.querySelectorAll('.notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-[1000] ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
    }

    // =============================================
    // INITIALIZATION
    // =============================================

    document.addEventListener('DOMContentLoaded', function() {
        attachEventListeners();

        // Close modals when clicking outside
        document.getElementById('employeeModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'employeeModal') closeModal();
        });

        document.getElementById('viewModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'viewModal') closeViewModal();
        });

        document.getElementById('invitationStatusModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'invitationStatusModal') closeInvitationStatusModal();
        });

        document.getElementById('documentViewModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'documentViewModal') closeDocumentModal();
        });

        // Document Invitation dropdown
        document.getElementById('documentInvitationBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('invitationDropdown');
            dropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('invitationDropdown');
            const button = document.getElementById('documentInvitationBtn');

            if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });
</script>

@endsection
