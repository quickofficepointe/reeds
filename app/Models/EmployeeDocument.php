<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_email',
        'next_of_kin_address',
        'national_id_photo',
        'passport_photo',
        'passport_size_photo',
        'nssf_card_photo',
        'sha_card_photo',
        'kra_certificate_photo',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_notes'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the document.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who verified the document.
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get all document fields that are files.
     */
    public function getDocumentFields()
    {
        return [
            'national_id_photo' => 'National ID Photo',
            'passport_photo' => 'Passport Photo',
            'passport_size_photo' => 'Passport Size Photo',
            'nssf_card_photo' => 'NSSF Card Photo',
            'sha_card_photo' => 'SHA Card Photo',
            'kra_certificate_photo' => 'KRA Certificate Photo',
        ];
    }

    /**
     * Check if document has all required files.
     */
    public function hasAllRequiredDocuments()
    {
        $required = ['national_id_photo', 'passport_size_photo', 'nssf_card_photo', 'sha_card_photo', 'kra_certificate_photo'];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get uploaded documents count.
     */
    public function getUploadedCountAttribute()
    {
        $count = 0;
        $documentFields = $this->getDocumentFields();

        foreach (array_keys($documentFields) as $field) {
            if (!empty($this->$field)) {
                $count++;
            }
        }

        return $count;
    }
}
