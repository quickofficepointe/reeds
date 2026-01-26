<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'token',
        'status', // pending, sent, opened, completed, expired
        'sms_sent',
        'sms_message_id',
        'sms_status',
        'sms_error',
        'email_sent',
        'email_status',
        'email_error',
        'sent_at',
        'opened_at',
        'completed_at',
        'expires_at',
        'reminder_count',
        'last_reminder_sent_at',
        'sent_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'sms_sent' => 'boolean',
        'email_sent' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'pending',
        'reminder_count' => 0,
        'sms_sent' => false,
        'email_sent' => false,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = static::generateToken();
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(30);
            }
        });
    }

    /**
     * Generate a new invitation token
     */
    public static function generateToken()
    {
        return Str::random(32);
    }

    /**
     * Generate the document upload URL
     */
    public function getUploadUrl()
    {
        return url("/d/{$this->token}");
    }

    /**
     * Generate SMS message with link
     */
    public function generateSmsMessage()
    {
        $employee = $this->employee;
        $shortUrl = url("/d/{$this->token}");

        return "Hello {$employee->first_name}, please upload your documents using this link: {$shortUrl} - Reeds Africa";
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if invitation is active (not expired and not completed)
     */
    public function isActive()
    {
        return !$this->isExpired() && $this->status !== 'completed';
    }

    /**
     * Check if SMS was successfully sent
     */
    public function smsWasSuccessful()
    {
        return $this->sms_sent && $this->sms_status === 'sent';
    }

    /**
     * Mark as sent
     */
    public function markAsSent($smsMessageId = null)
    {
        $this->update([
            'status' => 'sent',
            'sms_sent' => true,
            'sms_status' => 'sent',
            'sms_message_id' => $smsMessageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as opened
     */
    public function markAsOpened()
    {
        if ($this->status !== 'opened' && $this->status !== 'completed') {
            $this->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
        }
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($error = null)
    {
        $this->update([
            'sms_sent' => false,
            'sms_status' => 'failed',
            'sms_error' => $error,
        ]);
    }

    /**
     * Increment reminder count
     */
    public function incrementReminder()
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_sent_at' => now()]);
    }

    /**
     * Check if max reminders reached
     */
    public function maxRemindersReached()
    {
        return $this->reminder_count >= 3;
    }

    /**
     * Scope for active invitations
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'sent', 'opened'])
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for completed invitations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent invitations
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for expired invitations
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'sent' => 'info',
            'opened' => 'primary',
            'completed' => 'success',
            'expired' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    /**
     * Get days remaining until expiration
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->expires_at) {
            return 30;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
