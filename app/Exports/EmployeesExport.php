<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Employee::with(['department', 'subDepartment'])
            ->orderBy('department_id')
            ->orderBy('employee_code')
            ->get();
    }

    public function map($employee): array
    {
        return [
            $employee->employee_code,
            $employee->payroll_no,
            $employee->department_id,
            $employee->sub_department_id,
            $employee->employment_type,
            $employee->title,
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
            $employee->date_of_joining ? $employee->date_of_joining->format('Y-m-d') : '',
            $employee->on_probation ? 'Yes' : 'No',
            $employee->on_contract ? 'Yes' : 'No',
            $employee->icard_number,
            $employee->gender,
            $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '',
            $employee->marital_status,
            $employee->anniversary_date ? $employee->anniversary_date->format('Y-m-d') : '',
            $employee->religion,
            $employee->mother_tongue,
            $employee->nationality,
            $employee->ethnicity,
            $employee->tribe,
            $employee->designation,
            $employee->category,
            $employee->department->name ?? '',
            $employee->subDepartment->name ?? '',
            $employee->is_active ? 'Active' : 'Inactive',
            $employee->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'Employee Code*',
            'Payroll Number',
            'Department ID*',
            'Sub Department ID',
            'Employment Type',
            'Title',
            'First Name*',
            'Middle Name',
            'Last Name*',
            'Date of Joining',
            'On Probation',
            'On Contract',
            'ICard Number',
            'Gender',
            'Birth Date',
            'Marital Status',
            'Anniversary Date',
            'Religion',
            'Mother Tongue',
            'Nationality',
            'Ethnicity',
            'Tribe',
            'Designation',
            'Category',
            'Department Name',
            'Sub Department Name',
            'Status',
            'Created At'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],

            // Style required columns with background color
            'A1' => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE6E6']]],
            'C1' => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE6E6']]],
            'G1' => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE6E6']]],
            'I1' => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE6E6']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Employee Code
            'B' => 15, // Payroll Number
            'C' => 15, // Department ID
            'D' => 15, // Sub Department ID
            'E' => 15, // Employment Type
            'F' => 10, // Title
            'G' => 15, // First Name
            'H' => 15, // Middle Name
            'I' => 15, // Last Name
            'J' => 15, // Date of Joining
            'K' => 12, // On Probation
            'L' => 12, // On Contract
            'M' => 15, // ICard Number
            'N' => 10, // Gender
            'O' => 12, // Birth Date
            'P' => 15, // Marital Status
            'Q' => 15, // Anniversary Date
            'R' => 12, // Religion
            'S' => 15, // Mother Tongue
            'T' => 12, // Nationality
            'U' => 12, // Ethnicity
            'V' => 12, // Tribe
            'W' => 20, // Designation
            'X' => 15, // Category
            'Y' => 20, // Department Name
            'Z' => 20, // Sub Department Name
            'AA' => 12, // Status
            'AB' => 18, // Created At
        ];
    }
}
