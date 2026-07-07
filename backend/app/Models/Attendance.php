<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';
    
    protected $fillable = [
        'enrollment_id',
        'student_id',
        'schedule_id',
        'attendance_date',
        'status',
        'check_in_time',
        'check_out_time',
        'latitude',
        'longitude',
        'attendance_method',
        'recorded_by',
        'notes'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Krs::class, 'enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Mahasiswa::class, 'student_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Jadwal::class, 'schedule_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getAttendancePercentageAttribute()
    {
        // This would calculate attendance percentage based on total sessions
        return 0;
    }
}
