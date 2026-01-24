<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    /**
     * Display the document form.
     */
    public function create(Employee $employee)
    {
        $document = $employee->documents;

        return view('reeds.admin.employees.documents', compact('employee', 'document'));
    }

    /**
     * Store documents for employee.
     */
    public function store(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_relationship' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'next_of_kin_email' => 'nullable|email|max:255',
            'next_of_kin_address' => 'nullable|string',

            // File uploads
            'national_id_photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'passport_photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'passport_size_photo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'nssf_card_photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'sha_card_photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'kra_certificate_photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->except([
                'national_id_photo',
                'passport_photo',
                'passport_size_photo',
                'nssf_card_photo',
                'sha_card_photo',
                'kra_certificate_photo'
            ]);

            // Handle file uploads
            $fileFields = [
                'national_id_photo',
                'passport_photo',
                'passport_size_photo',
                'nssf_card_photo',
                'sha_card_photo',
                'kra_certificate_photo'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store("employee_documents/{$employee->employee_code}", 'public');
                    $data[$field] = $path;
                }
            }

            // Create or update documents
            if ($employee->documents) {
                $employee->documents->update($data);
                $message = 'Employee documents updated successfully!';
            } else {
                $employee->documents()->create($data);
                $message = 'Employee documents saved successfully!';
            }

            return redirect()->route('admin.employees.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save documents: ' . $e->getMessage());
        }
    }

    /**
     * Verify employee documents.
     */
    public function verify(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string',
            'is_verified' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $document = $employee->documents;

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found for this employee.'
                ], 404);
            }

            $document->update([
                'is_verified' => $request->is_verified,
                'verified_at' => $request->is_verified ? now() : null,
                'verified_by' => $request->is_verified ? auth()->id() : null,
                'verification_notes' => $request->verification_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->is_verified ? 'Employee documents verified successfully!' : 'Verification removed.',
                'document' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update verification status.'
            ], 500);
        }
    }

    /**
     * Download employee document.
     */
    public function download(Employee $employee, $documentType)
    {
        $allowedDocuments = [
            'national_id_photo',
            'passport_photo',
            'passport_size_photo',
            'nssf_card_photo',
            'sha_card_photo',
            'kra_certificate_photo'
        ];

        if (!in_array($documentType, $allowedDocuments)) {
            abort(404);
        }

        $document = $employee->documents;

        if (!$document || !$document->$documentType) {
            abort(404);
        }

        return Storage::disk('public')->download($document->$documentType);
    }

    /**
     * Show employee documents.
     */
    public function show(Employee $employee)
    {
        $document = $employee->documents;

        if (!$document) {
            return redirect()->route('admin.employees.documents.create', $employee)
                ->with('info', 'No documents found. Please upload documents first.');
        }

        return view('reeds.admin.employees.documents-show', compact('employee', 'document'));
    }
}
