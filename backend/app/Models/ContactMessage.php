<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $table = 'contact_messages';
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'admin_response',
        'responded_by',
        'responded_at'
    ];

    protected $casts = [
        'responded_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'unread'
    ];

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeByDate($query, $startDate, $endDate = null)
    {
        $query->whereBetween('created_at', [$startDate, $endDate ?? now()]);
        return $query;
    }

    public function markAsRead()
    {
        $this->update(['status' => 'read']);
    }

    public function markAsReplied($response, $respondedBy = null)
    {
        $this->update([
            'status' => 'replied',
            'admin_response' => $response,
            'responded_by' => $respondedBy,
            'responded_at' => now()
        ]);
    }

    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'unread' => 'bg-red-100 text-red-800',
            'read' => 'bg-yellow-100 text-yellow-800',
            'replied' => 'bg-green-100 text-green-800',
            'archived' => 'bg-gray-100 text-gray-800',
        ];
        
        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'unread' => 'Belum Dibaca',
            'read' => 'Sudah Dibaca',
            'replied' => 'Sudah Dibalas',
            'archived' => 'Diarsipkan',
        ];
        
        return $labels[$this->status] ?? 'Unknown';
    }

    public function isUnread()
    {
        return $this->status === 'unread';
    }

    public function isReplied()
    {
        return $this->status === 'replied';
    }

    public function getFormattedPhoneAttribute()
    {
        if (!$this->phone) {
            return '-';
        }
        
        // Format phone number if needed
        return $this->phone;
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }
}
