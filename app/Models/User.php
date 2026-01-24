<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    const ROLE_ADMIN = 1;
    const ROLE_VENDOR = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'unit_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is vendor
     */
    public function isVendor(): bool
    {
        return $this->role === self::ROLE_VENDOR;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(int $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Get the profile associated with the user
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

       public function mealTransactions()
    {
        return $this->hasMany(MealTransaction::class, 'vendor_id');
    }

    /**
     * Get role name for display
     */
    public function getRoleName(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_VENDOR => 'Vendor',
            default => 'Unknown',
        };
    }
}
