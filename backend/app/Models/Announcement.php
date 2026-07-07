<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';
    
    protected $fillable = [
        'title',
        'content',
        'priority',
        'target_audience',
        'target_ids',
        'start_date',
        'end_date',
        'is_published',
        'created_by',
        'views'
    ];

    protected $casts = [
        'target_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
        'views' => 'integer'
    ];

    protected $attributes = [
        'views' => 0,
        'is_published' => false
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeForAudience($query, $audience)
    {
        return $query->where(function($q) use ($audience) {
            $q->where('target_audience', 'all')
              ->orWhere('target_audience', $audience);
        });
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function isActive()
    {
        return $this->is_published && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function getExcerptAttribute($length = 150)
    {
        return str(strip_tags($this->content))->limit($length);
    }

    public function getPriorityColorAttribute()
    {
        return [
            'low' => 'bg-blue-100 text-blue-800',
            'medium' => 'bg-green-100 text-green-800',
            'high' => 'bg-yellow-100 text-yellow-800',
            'urgent' => 'bg-red-100 text-red-800',
        ][$this->priority] ?? 'bg-gray-100 text-gray-800';
    }
}
