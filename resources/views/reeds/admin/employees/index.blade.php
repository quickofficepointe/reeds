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
                <input type="text" id="searchInput" placeholder="Search by name, code, phone, email..."
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
                <tbody class="divide-y divide-gray-200">
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
        <div class="px-6 py-4 border-t border-gray-200">
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

            <form id="employeeForm" class="space-y-4">
                @csrf
                <input type="hidden" id="employee_id" name="id">

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
                        <label for="icard_number" class="block text-sm font-medium text-gray-700 mb-1">ICard Number</label>
                        <input type="text" id="icard_number" name="icard_number"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter ICard number">
                        <div id="icard_number_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="designation" class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                        <input type="text" id="designation" name="designation"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., Mason, Welder">
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" id="category" name="category"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., New ham, Omuford">
                    </div>
                </div>

                <div id="statusField" class="hidden">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="is_active" name="is_active" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
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
@endsection

@section('scripts')
<script>
    // Document Invitation Functions
    function openDocumentInvitationModal() {
        const dropdown = document.getElementById('invitationDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('invitationDropdown');
        const button = document.getElementById('documentInvitationBtn');

        if (!button.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Update selected count
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.employee-checkbox:checked:not(:disabled)').length;
        document.getElementById('selectedCount').textContent = selected;

        if (selected > 0) {
            document.getElementById('bulkActionsBar').classList.remove('hidden');
        } else {
            document.getElementById('bulkActionsBar').classList.add('hidden');
        }
    }

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.employee-checkbox:not(:disabled)');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateSelectedCount();
    });

    // Individual checkbox change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employee-checkbox')) {
            updateSelectedCount();
        }
    });

    function clearSelection() {
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelectedCount();
    }

    function sendBulkDocumentInvitations() {
        const selectedEmployees = Array.from(document.querySelectorAll('.employee-checkbox:checked:not(:disabled)'))
            .map(cb => cb.value);

        if (selectedEmployees.length === 0) {
            showNotification('error', 'Please select at least one employee with a phone number.');
            return;
        }

        if (confirm(`Send document invitations to ${selectedEmployees.length} selected employees?`)) {
            fetch('/admin/employees/bulk-send-document-invitations', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: selectedEmployees })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    clearSelection();
                    setTimeout(() => window.location.reload(), 2000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    async function sendToAllWithoutDocuments() {
        try {
            const response = await fetch('/admin/employees/stats');
            const data = await response.json();

            if (data.success && data.stats) {
                const totalEmployees = data.stats.total_employees;
                const activeEmployees = data.stats.active_employees;

                if (confirm(`This will send document invitations to all ${totalEmployees} employees without complete documents. Are you sure?`)) {
                    // Get all employee IDs without documents
                    const allEmployees = Array.from(document.querySelectorAll('.employee-checkbox:not(:disabled)'))
                        .map(cb => cb.value);

                    if (allEmployees.length === 0) {
                        showNotification('error', 'No employees found with phone numbers.');
                        return;
                    }

                    fetch('/admin/employees/bulk-send-document-invitations', {
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
                            setTimeout(() => window.location.reload(), 2000);
                        } else if (data.error) {
                            showNotification('error', data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred. Please try again.');
                    });
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Failed to get employee statistics.');
        }
    }

    function sendDocumentInvitation(employeeId) {
        if (confirm('Send document invitation to this employee?')) {
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
                    setTimeout(() => window.location.reload(), 1500);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function sendDocumentReminder(employeeId) {
        if (confirm('Send reminder for document upload?')) {
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
                    setTimeout(() => window.location.reload(), 1500);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    async function viewInvitationStatus() {
        document.getElementById('invitationStatusModal').classList.remove('hidden');

        try {
            const response = await fetch('/admin/employees/stats');
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

    // Filter table by document status
    document.getElementById('documentFilter').addEventListener('change', function(e) {
        filterTable();
    });

    function filterTable() {
        const deptId = document.getElementById('departmentFilter').value;
        const unitId = document.getElementById('unitFilter').value;
        const docStatus = document.getElementById('documentFilter').value;
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const rowDeptId = row.getAttribute('data-dept-id');
            const rowUnitId = row.getAttribute('data-unit-id');
            const rowDocStatus = row.getAttribute('data-doc-status');
            let show = true;

            if (deptId && rowDeptId !== deptId) {
                show = false;
            }
            if (unitId && rowUnitId !== unitId) {
                show = false;
            }
            if (docStatus && rowDocStatus !== docStatus) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    // Rest of your existing functions (keep them as they are)
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Add Employee';
        document.getElementById('employeeForm').reset();
        document.getElementById('employee_id').value = '';
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
                document.getElementById('icard_number').value = employee.icard_number || '';
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
                const dateOfJoining = employee.date_of_joining ? new Date(employee.date_of_joining).toLocaleDateString() : 'N/A';

                // Get document status
                let docStatus = 'No documents uploaded';
                let docStatusClass = 'bg-red-100 text-red-800';

                if (employee.documents && employee.documents.hasAllRequiredDocuments()) {
                    docStatus = 'All documents uploaded and verified';
                    docStatusClass = 'bg-green-100 text-green-800';
                } else if (employee.document_invitation) {
                    const inv = employee.document_invitation;
                    if (inv.status === 'sent') {
                        docStatus = `Invitation sent on ${new Date(inv.sent_at).toLocaleDateString()}`;
                        docStatusClass = 'bg-blue-100 text-blue-800';
                    } else if (inv.status === 'opened') {
                        docStatus = `Link opened on ${new Date(inv.opened_at).toLocaleDateString()}`;
                        docStatusClass = 'bg-yellow-100 text-yellow-800';
                    } else if (inv.status === 'completed') {
                        docStatus = `Documents uploaded on ${new Date(inv.completed_at).toLocaleDateString()}`;
                        docStatusClass = 'bg-green-100 text-green-800';
                    }
                }

                const content = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
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
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Date of Joining:</span>
                                    <span class="text-sm text-gray-900">${dateOfJoining}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
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
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">ICard Number:</span>
                                    <span class="text-sm text-gray-900">${employee.icard_number || 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Document Status -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Document Status</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Status:</span>
                                    <span class="text-sm ${docStatusClass} px-2 py-1 rounded-full">${docStatus}</span>
                                </div>
                                ${employee.document_invitation ? `
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Invitation Sent:</span>
                                    <span class="text-sm text-gray-900">${new Date(employee.document_invitation.sent_at).toLocaleDateString()}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Reminders Sent:</span>
                                    <span class="text-sm text-gray-900">${employee.document_invitation.reminder_count}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Employment Details -->
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
                                    <span class="text-sm text-gray-900">${employee.employment_type}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Organizational Structure -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Organizational Structure</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Department:</span>
                                    <span class="text-sm text-gray-900">${employee.department ? employee.department.name : 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Sub-Department:</span>
                                    <span class="text-sm text-gray-900">${employee.sub_department ? employee.sub_department.name : 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Unit:</span>
                                    <span class="text-sm text-gray-900">${employee.unit ? employee.unit.name : 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Status</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">Active:</span>
                                    <span class="text-sm ${employee.is_active ? 'text-green-600' : 'text-red-600'} font-medium">
                                        ${employee.is_active ? 'Yes' : 'No'}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-600">QR Code:</span>
                                    <span class="text-sm ${employee.qr_code ? 'text-green-600' : 'text-yellow-600'} font-medium">
                                        ${employee.qr_code ? 'Generated' : 'Pending'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('viewModalTitle').textContent = `Employee: ${employee.employee_code}`;
                document.getElementById('viewModalContent').innerHTML = content;
                document.getElementById('viewModal').classList.remove('hidden');
            } else {
                showNotification('error', 'Failed to load employee details');
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

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });

    // Filter by department
    document.getElementById('departmentFilter').addEventListener('change', filterTable);

    // Filter by unit
    document.getElementById('unitFilter').addEventListener('change', filterTable);

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('departmentFilter').value = '';
        document.getElementById('unitFilter').value = '';
        document.getElementById('documentFilter').value = '';
        document.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
    }

    // Handle form submission
    document.getElementById('employeeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const employeeId = document.getElementById('employee_id').value;
        const url = employeeId ? `/admin/employees/${employeeId}` : '/admin/employees';
        const method = employeeId ? 'PUT' : 'POST';

        const data = {};
        formData.forEach((value, key) => {
            if (key === 'is_active') {
                data[key] = value === 'on';
            } else if (value !== '') {
                data[key] = value;
            }
        });

        if (!data.sub_department_id) data.sub_department_id = null;
        if (!data.unit_id) data.unit_id = null;
        if (!data.email) data.email = null;

        fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 || status === 201) {
                if (body.success) {
                    closeModal();
                    showNotification('success', body.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (body.error) {
                    showNotification('error', body.error);
                }
            } else if (status === 422) {
                if (body.errors) {
                    displayValidationErrors(body.errors);
                } else if (body.error) {
                    showNotification('error', body.error);
                }
            } else {
                showNotification('error', body.error || 'An error occurred. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'An error occurred. Please try again.');
        });
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

    function generateQrCode(employeeId) {
        if (confirm('Are you sure you want to generate QR code for this employee?')) {
            fetch(`/admin/employees/${employeeId}/generate-qr`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function toggleStatus(employeeId) {
        if (confirm('Are you sure you want to change the status of this employee?')) {
            fetch(`/admin/employees/${employeeId}/toggle-status`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function confirmDelete(employeeId, employeeName) {
        if (confirm(`Are you sure you want to delete "${employeeName}"? This action cannot be undone.`)) {
            fetch(`/admin/employees/${employeeId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Close modal when clicking outside
    document.getElementById('employeeModal').addEventListener('click', function(e) {
        if (e.target.id === 'employeeModal') closeModal();
    });

    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target.id === 'viewModal') closeViewModal();
    });

    document.getElementById('invitationStatusModal').addEventListener('click', function(e) {
        if (e.target.id === 'invitationStatusModal') closeInvitationStatusModal();
    });
</script>
@endsection
