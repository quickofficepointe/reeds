<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Unit; // Add Unit model
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeesImport implements ToCollection, WithHeadingRow
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $departmentMap = [];
    private $unitMap = []; // Add unit map
    private $errors = [];

    public function __construct()
    {
        $this->cacheDepartments();
        $this->cacheUnits(); // Cache units
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            Log::info('=== STARTING IMPORT ===');
            Log::info('Total rows: ' . $rows->count());

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    // Log first few rows to debug column names
                    if ($index < 3) {
                        Log::info("Row {$rowNumber} raw data:", $row->toArray());
                    }

                    // Get values with flexible column name matching
                    $employeeCode = $this->getRowValue($row, ['employee_code', 'employee code']);
                    $firstName = $this->getRowValue($row, ['first_name', 'first name']);
                    $lastName = $this->getRowValue($row, ['last_name', 'last name']);
                    $departmentName = $this->getRowValue($row, ['department']);
                    $unitName = $this->getRowValue($row, ['unit']); // Get unit name
                    $email = $this->getRowValue($row, ['email']);
                    $phone = $this->getRowValue($row, ['phone']);

                    Log::info("Processing Row {$rowNumber}: {$employeeCode} - {$firstName} {$lastName} - Dept: {$departmentName} - Unit: {$unitName}");

                    // Validate required fields (ADDED UNIT)
                    if (empty($employeeCode) || empty($firstName) || empty($lastName) || empty($departmentName) || empty($unitName)) {
                        $errorMsg = "Row {$rowNumber}: Missing required fields (Code: {$employeeCode}, First: {$firstName}, Last: {$lastName}, Dept: {$departmentName}, Unit: {$unitName})";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Check for duplicate employee_code
                    if (Employee::where('employee_code', $employeeCode)->exists()) {
                        $errorMsg = "Row {$rowNumber}: Employee code '{$employeeCode}' already exists";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Validate email if provided
                    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errorMsg = "Row {$rowNumber}: Invalid email format '{$email}'";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Check for duplicate email if provided
                    if ($email && Employee::where('email', $email)->exists()) {
                        $errorMsg = "Row {$rowNumber}: Email '{$email}' already exists";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Check for duplicate phone if provided
                    if ($phone && Employee::where('phone', $phone)->exists()) {
                        $errorMsg = "Row {$rowNumber}: Phone '{$phone}' already exists";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Convert department name to department ID
                    $departmentId = $this->getDepartmentIdFromName($departmentName, $rowNumber);
                    if (!$departmentId) {
                        $this->skippedCount++;
                        continue;
                    }

                    // Convert unit name to unit ID (NEW)
                    $unitId = $this->getUnitIdFromName($unitName, $rowNumber);
                    if (!$unitId) {
                        $this->skippedCount++;
                        continue;
                    }

                    // Create employee data (ADDED UNIT_ID, EMAIL, PHONE)
                    $employeeData = [
                        'employee_code' => $employeeCode,
                        'payroll_no' => $this->getRowValue($row, ['payroll_no', 'payroll no']),
                        'department_id' => $departmentId,
                        'unit_id' => $unitId, // Add unit_id
                        'sub_department_id' => null,
                        'employment_type' => 'Regular',
                        'first_name' => $firstName,
                        'middle_name' => $this->getRowValue($row, ['middle_name', 'middle name']),
                        'last_name' => $lastName,
                        'email' => $email, // Add email
                        'phone' => $phone, // Add phone
                        'icard_number' => $this->getRowValue($row, ['icard_number', 'icard number']),
                        'designation' => $this->getRowValue($row, ['designation']),
                        'gender' => $this->mapGender($this->getRowValue($row, ['gender'])),
                        'is_active' => true,
                    ];

                    // Check for duplicate payroll_no
                    if (!empty($employeeData['payroll_no']) && Employee::where('payroll_no', $employeeData['payroll_no'])->exists()) {
                        $errorMsg = "Row {$rowNumber}: Payroll number '{$employeeData['payroll_no']}' already exists";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Check for duplicate icard_number
                    if (!empty($employeeData['icard_number']) && Employee::where('icard_number', $employeeData['icard_number'])->exists()) {
                        $errorMsg = "Row {$rowNumber}: ICard number '{$employeeData['icard_number']}' already exists";
                        $this->errors[] = $errorMsg;
                        Log::warning($errorMsg);
                        $this->skippedCount++;
                        continue;
                    }

                    // Create employee
                    $employee = Employee::create($employeeData);

                    // Generate QR code
                    $employee->generateQrCode();

                    $this->importedCount++;
                    Log::info("SUCCESS: Imported {$employeeCode} with Unit: {$unitName}, Email: {$email}, Phone: {$phone}");

                } catch (\Exception $e) {
                    $errorMsg = "Row {$rowNumber}: " . $e->getMessage();
                    $this->errors[] = $errorMsg;
                    Log::error($errorMsg);
                    $this->skippedCount++;
                    continue;
                }
            }

            DB::commit();
            Log::info("=== IMPORT COMPLETED ===");
            Log::info("Imported: {$this->importedCount}, Skipped: {$this->skippedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("IMPORT FAILED: " . $e->getMessage());
            throw $e;
        }
    }

    private function getRowValue($row, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            // Try exact match
            if (isset($row[$key])) {
                $value = $row[$key];
                return is_string($value) ? trim($value) : $value;
            }

            // Try case-insensitive and space variations
            $lowerKey = strtolower(str_replace(' ', '_', $key));
            foreach ($row as $actualKey => $value) {
                $lowerActual = strtolower(str_replace(' ', '_', $actualKey));
                if ($lowerActual === $lowerKey) {
                    return is_string($value) ? trim($value) : $value;
                }
            }
        }
        return null;
    }

    private function cacheDepartments()
    {
        // Get all departments and create mapping with variations
        $departments = Department::all();

        foreach ($departments as $department) {
            $name = strtolower(trim($department->name));
            $this->departmentMap[$name] = $department->id;

            // Add common variations
            $variations = [
                $name,
                str_replace(['-', '&', '/'], [' ', 'and', ' '], $name),
                str_replace([' and ', ' '], [' & ', '-'], $name),
            ];

            foreach ($variations as $variation) {
                $this->departmentMap[$variation] = $department->id;
            }
        }

        // Specific manual mappings for your CSV variations
        $manualMappings = [
            // CSV Department Name => Database Department Name
            'pre - finishing' => 'pre-finishing',
            'pre finishing' => 'pre-finishing',
            'infrastructure & external development' => 'infrastructure & external development',
            'shell external' => 'shell external',
            'shell p&e' => 'shell',
            'qa/qc' => 'qa/qc',
            'hse' => 'hse',
        ];

        foreach ($manualMappings as $csvName => $dbName) {
            $normalizedDbName = strtolower(trim($dbName));
            if (isset($this->departmentMap[$normalizedDbName])) {
                $this->departmentMap[strtolower(trim($csvName))] = $this->departmentMap[$normalizedDbName];
            }
        }

        \Log::info('Department mappings:', $this->departmentMap);
    }

    // NEW METHOD: Cache units
    private function cacheUnits()
    {
        // Get all units and create mapping with variations
        $units = Unit::all();

        foreach ($units as $unit) {
            $name = strtolower(trim($unit->name));
            $this->unitMap[$name] = $unit->id;

            // Add common variations
            $variations = [
                $name,
                str_replace(['-', '&', '/'], [' ', 'and', ' '], $name),
                str_replace([' and ', ' '], [' & ', '-'], $name),
            ];

            foreach ($variations as $variation) {
                $this->unitMap[$variation] = $unit->id;
            }
        }

        // Manual mappings for unit variations
        $manualUnitMappings = [
            'blue unit' => 'blue',
            'blue_unit' => 'blue',
            'unit blue' => 'blue',
            'red unit' => 'red',
            'unit red' => 'red',
            'green unit' => 'green',
            'unit green' => 'green',
        ];

        foreach ($manualUnitMappings as $csvName => $dbName) {
            $normalizedDbName = strtolower(trim($dbName));
            if (isset($this->unitMap[$normalizedDbName])) {
                $this->unitMap[strtolower(trim($csvName))] = $this->unitMap[$normalizedDbName];
            }
        }

        Log::info('Unit mappings:', $this->unitMap);
    }

    private function getDepartmentIdFromName($departmentName, $rowNumber)
    {
        if (empty($departmentName)) {
            $errorMsg = "Row {$rowNumber}: Department name is empty";
            $this->errors[] = $errorMsg;
            Log::warning($errorMsg);
            return null;
        }

        $normalizedName = strtolower(trim($departmentName));

        Log::info("Looking for department: '{$departmentName}' -> '{$normalizedName}'");

        // Exact match
        if (isset($this->departmentMap[$normalizedName])) {
            $deptId = $this->departmentMap[$normalizedName];
            Log::info("Found department: '{$departmentName}' -> ID: {$deptId}");
            return $deptId;
        }

        // Try partial matching for close matches
        foreach ($this->departmentMap as $deptPattern => $deptId) {
            if (str_contains($normalizedName, $deptPattern) || str_contains($deptPattern, $normalizedName)) {
                Log::info("Found partial match: '{$departmentName}' -> '{$deptPattern}' (ID: {$deptId})");
                return $deptId;
            }
        }

        // Show available departments for debugging
        $availableDepts = array_unique(array_values($this->departmentMap));
        $availableDeptNames = Department::whereIn('id', $availableDepts)->pluck('name')->toArray();

        $errorMsg = "Row {$rowNumber}: Department '{$departmentName}' not found. Available: " . implode(', ', $availableDeptNames);
        $this->errors[] = $errorMsg;
    Log::warning($errorMsg);
        return null;
    }

    // NEW METHOD: Get unit ID from name
    private function getUnitIdFromName($unitName, $rowNumber)
    {
        if (empty($unitName)) {
            $errorMsg = "Row {$rowNumber}: Unit name is empty";
            $this->errors[] = $errorMsg;
            \Log::warning($errorMsg);
            return null;
        }

        $normalizedName = strtolower(trim($unitName));

        \Log::info("Looking for unit: '{$unitName}' -> '{$normalizedName}'");

        // Exact match
        if (isset($this->unitMap[$normalizedName])) {
            $unitId = $this->unitMap[$normalizedName];
            \Log::info("Found unit: '{$unitName}' -> ID: {$unitId}");
            return $unitId;
        }

        // Try partial matching for close matches
        foreach ($this->unitMap as $unitPattern => $unitId) {
            if (str_contains($normalizedName, $unitPattern) || str_contains($unitPattern, $normalizedName)) {
                \Log::info("Found partial match: '{$unitName}' -> '{$unitPattern}' (ID: {$unitId})");
                return $unitId;
            }
        }

        // Show available units for debugging
        $availableUnits = array_unique(array_values($this->unitMap));
        $availableUnitNames = Unit::whereIn('id', $availableUnits)->pluck('name')->toArray();

        $errorMsg = "Row {$rowNumber}: Unit '{$unitName}' not found. Available: " . implode(', ', $availableUnitNames);
        $this->errors[] = $errorMsg;
        \Log::warning($errorMsg);
        return null;
    }

    private function mapGender($gender)
    {
        if (empty($gender)) {
            return null;
        }

        $gender = strtolower(trim($gender));

        return match($gender) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            default => 'Other'
        };
    }

    // Optional: Format phone numbers (Kenya format)
    private function formatPhone($phone)
    {
        if (empty($phone)) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $phone);

        // If starts with 0, convert to 254
        if (strlen($phone) === 9 && $phone[0] === '0') {
            $phone = '254' . substr($phone, 1);
        }

        // If starts with 7, add 254
        if (strlen($phone) === 9 && $phone[0] === '7') {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
