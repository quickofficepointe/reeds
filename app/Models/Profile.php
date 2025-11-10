<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'photo',
        'phone_number',
        'bio',
        'is_complete',
        'is_verified',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified this profile
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if profile is complete
     */
    public function isComplete(): bool
    {
        return $this->is_complete && !empty($this->phone_number);
    }

    /**
     * Check if profile is verified (for vendors)
     */
    public function isVerified(): bool
    {
        return $this->is_verified && !is_null($this->verified_at);
    }

    /**
     * Mark profile as complete
     */
    public function markAsComplete(): void
    {
        $this->update([
            'is_complete' => true,
        ]);
    }

    /**
     * Mark profile as verified by admin
     */
    public function markAsVerified(int $adminId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $adminId,
        ]);
    }
}
