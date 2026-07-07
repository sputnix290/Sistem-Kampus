<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'calendar_events';
    
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'event_type',
        'priority',
        'created_by',
        'is_public'
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_public' => 'boolean'
    ];

    protected $attributes = [
        'priority' => 'medium',
        'event_type' => 'academic',
        'is_public' => true
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('event_date', $date);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('event_date', $year)
                    ->whereMonth('event_date', $month);
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('event_date', '>=', now())
                    ->where('event_date', '<=', now()->addDays($days))
                    ->orderBy('event_date')
                    ->orderBy('start_time');
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeAcademic($query)
    {
        return $query->where('event_type', 'academic');
    }

    public function scopeHoliday($query)
    {
        return $query->where('event_type', 'holiday');
    }

    public function scopeExam($query)
    {
        return $query->where('event_type', 'exam');
    }

    public function scopeEvent($query)
    {
        return $query->where('event_type', 'event');
    }

    public function getFullEventDateTimeAttribute()
    {
        if ($this->start_time) {
            return $this->event_date->format('Y-m-d') . ' ' . $this->start_time->format('H:i');
        }
        return $this->event_date->format('Y-m-d');
    }

    public function getEventTypeLabelAttribute()
    {
        $labels = [
            'academic' => 'Akademik',
            'holiday' => 'Libur',
            'exam' => 'Ujian',
            'event' => 'Acara',
            'deadline' => 'Deadline',
        ];
        
        return $labels[$this->event_type] ?? 'Acara';
    }

    public function getEventTypeColorAttribute()
    {
        $colors = [
            'academic' => 'bg-blue-100 text-blue-800',
            'holiday' => 'bg-green-100 text-green-800',
            'exam' => 'bg-red-100 text-red-800',
            'event' => 'bg-purple-100 text-purple-800',
            'deadline' => 'bg-yellow-100 text-yellow-800',
        ];
        
        return $colors[$this->event_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'bg-green-100 text-green-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-red-100 text-red-800',
        ];
        
        return $colors[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    public function isUpcoming()
    {
        return $this->event_date >= now();
    }

    public function isToday()
    {
        return $this->event_date->isToday();
    }

    public function hasTime()
    {
        return !empty($this->start_time) || !empty($this->end_time);
    }

    public function getDurationAttribute()
    {
        if ($this->start_time && $this->end_time) {
            $start = $this->start_time;
            $end = $this->end_time;
            return $start->diff($end)->format('%h jam %i menit');
        }
        return 'Sepanjang hari';
    }

    public function getMonthAttribute()
    {
        return $this->event_date->format('F');
    }

    public function getDayAttribute()
    {
        return $this->event_date->format('d');
    }

    public function getFormattedDateAttribute()
    {
        return $this->event_date->translatedFormat('d F Y');
    }

    public static function getEventTypes()
    {
        return [
            'academic' => 'Akademik',
            'holiday' => 'Libur',
            'exam' => 'Ujian',
            'event' => 'Acara',
            'deadline' => 'Deadline',
        ];
    }
}
