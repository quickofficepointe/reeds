@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h4 class="text-lg font-semibold text-gray-900">{{ $documentTitle }}</h4>
            <p class="text-sm text-gray-600">Employee: {{ $employee->formal_name }} ({{ $employee->employee_code }})</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('admin.employees.documents.download', ['employee' => $employee->id, 'documentType' => $documentType]) }}"
               class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition duration-150 flex items-center space-x-1">
                <i class="fas fa-download"></i>
                <span>Download</span>
            </a>
            <button onclick="closeDocumentModal()"
                    class="border border-gray-300 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-50 transition duration-150 flex items-center space-x-1">
                <i class="fas fa-times"></i>
                <span>Close</span>
            </button>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg overflow-hidden">
        @if($isImage)
            <div class="bg-gray-100 p-4">
                <img src="data:{{ $mimeType }};base64,{{ $base64 }}"
                     alt="{{ $documentTitle }}"
                     class="max-w-full h-auto mx-auto max-h-[70vh] object-contain">
            </div>
        @else
            <div class="bg-gray-100 p-8 text-center">
                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-pdf text-blue-600 text-4xl"></i>
                </div>
                <h5 class="text-lg font-medium text-gray-900 mb-2">{{ $documentTitle }}</h5>
                <p class="text-sm text-gray-600 mb-4">This document is in PDF format</p>
                <a href="{{ $fileUrl }}"
                   target="_blank"
                   class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-150 inline-flex items-center space-x-2">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Open in New Tab</span>
                </a>
            </div>
        @endif
    </div>

    <div class="text-xs text-gray-500 mt-2">
        <p>File type: {{ $mimeType }} | Filename: {{ $filename }}</p>
    </div>
</div>

<script>
    function closeDocumentModal() {
        const modal = document.getElementById('documentViewModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
@endsection
