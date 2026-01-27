<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewEmployeeOnboarding extends Model
{
    use HasFactory;

    protected $table = 'new_employee_onboarding';

    protected $fillable = [
        // Token
        'token',

        // Basic Bio Data
        'first_name', 'middle_name', 'last_name',
        'personal_phone', 'personal_email', 'date_of_birth', 'gender',

        // Employment Details
        'designation', 'date_of_joining', 'employment_type',
        'department_id', 'sub_department_id',

        // Identification Numbers
        'national_id_number', 'passport_number', 'nssf_number',
        'sha_number', 'kra_pin',

        // Statutory Documents
        'national_id_photo', 'passport_photo', 'nssf_card_photo',
        'sha_card_photo', 'kra_certificate_photo', 'passport_size_photo',

        // Next of Kin
        'next_of_kin_name', 'next_of_kin_relationship', 'next_of_kin_phone',
        'next_of_kin_email', 'next_of_kin_address',
 'education_level',
    'field_of_study',
    'institution',
    'year_completed',
    'cv_upload',
        // Processing Fields
        'status', 'assigned_employee_code', 'processed_by', 'processed_at',
        'location', 'unit_id', 'hr_notes', 'rejection_reason'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeByToken($query, $token)
    {
        return $query->where('token', $token);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeMombasa($query)
    {
        return $query->where('location', 'Mombasa');
    }

    // Helper Methods
    public function getFullNameAttribute()
    {
        $name = $this->first_name;
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        $name .= ' ' . $this->last_name;
        return $name;
    }

    public function getFormalNameAttribute()
    {
        return "{$this->last_name}, {$this->first_name}" .
               ($this->middle_name ? " {$this->middle_name}" : "");
    }

    public function generateToken()
    {
        if (!$this->token) {
            $this->token = Str::random(40);
            $this->save();
        }
        return $this->token;
    }

    public function markAsSubmitted()
    {
        $this->status = 'submitted';
        $this->save();
    }

    public function isEditable()
    {
        return $this->status === 'draft';
    }

    public function isSubmitted()
    {
        return $this->status === 'submitted';
    }

    public function isVerified()
    {
        return $this->status === 'verified';
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
