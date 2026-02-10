@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Invoice #{{ $invoice->invoice_number }}</h1>
                <p class="text-gray-600 mt-2">Period: {{ $invoice->period_start->format('F j, Y') }} - {{ $invoice->period_end->format('F j, Y') }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="{{ route('vendor.invoices.download', $invoice->id) }}"
                   class="bg-secondary-blue text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-600 transition duration-300 flex items-center space-x-2">
                    <i class="fas fa-download mr-2"></i>
                    <span>Download PDF</span>
                </a>
                <a href="{{ route('vendor.invoices') }}"
                   class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition duration-300 flex items-center space-x-2">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Back to Invoices</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Invoice Status Banner -->
    <div class="mb-8">
        @if($invoice->status == 'paid')
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                <div>
                    <p class="font-semibold">This invoice has been paid</p>
                    <p class="text-sm">Payment received on {{ $invoice->updated_at->format('F j, Y') }}</p>
                </div>
            </div>
        @elseif($invoice->status == 'pending')
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-clock text-yellow-500 mr-3 text-xl"></i>
                <div>
                    <p class="font-semibold">This invoice is pending payment</p>
                    <p class="text-sm">Due on {{ $invoice->due_date->format('F j, Y') }}</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Invoice Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Invoice Summary -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
                <h3 class="text-lg font-semibold text-text-black mb-4">Invoice Summary</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Invoice Number</p>
                        <p class="font-semibold">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Invoice Date</p>
                        <p class="font-semibold">{{ $invoice->invoice_date->format('F j, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Period</p>
                        <p class="font-semibold">{{ $invoice->period_start->format('M j') }} - {{ $invoice->period_end->format('M j, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Due Date</p>
                        <p class="font-semibold">{{ $invoice->due_date->format('F j, Y') }}</p>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="mt-8">
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
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item->date->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $item->description }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item->scans }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        Ksh {{ number_format($item->rate, 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        Ksh {{ number_format($item->amount, 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Totals & Payment Info -->
        <div>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
                <h3 class="text-lg font-semibold text-text-black mb-4">Total Amount</h3>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Scans:</span>
                        <span class="font-semibold">{{ $invoice->total_scans }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Rate per meal:</span>
                        <span class="font-semibold">Ksh 70.00</span>
                    </div>
                    <div class="border-t pt-3 mt-3">
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-gray-800">Total Amount:</span>
                            <span class="font-bold text-secondary-blue">Ksh {{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

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
                        <p class="font-medium text-secondary-blue">{{ $invoice->invoice_number }}</p>
                        <p class="text-xs text-gray-500 mt-1">Please use this reference when making payment</p>
                    </div>
                </div>
            </div>

            <!-- Invoice Status -->
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Invoice Status</p>
                        @if($invoice->status == 'paid')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Paid
                            </span>
                        @elseif($invoice->status == 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                        @elseif($invoice->status == 'draft')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Draft
                            </span>
                        @endif
                    </div>

                    @if($invoice->is_test)
                    <div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                            TEST INVOICE
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    @if($invoice->notes)
    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-text-black mb-2">Notes</h3>
        <p class="text-gray-700">{{ $invoice->notes }}</p>
    </div>
    @endif

    <!-- Contact Information -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-text-black mb-4">Need Help?</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Email</p>
                <p class="font-medium">accounting@reedsafrica.com</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Phone</p>
                <p class="font-medium">+254 712 345 678</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Add any JavaScript functionality here if needed
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Invoice view page loaded');
    });
</script>
@endsection
