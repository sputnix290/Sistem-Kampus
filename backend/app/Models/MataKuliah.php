<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliahs';

    protected $fillable = [
        'kode', 'nama', 'sks', 'kuota', 'terisi', 'semester', 'dosen_id', 'deskripsi',
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }

    public function krsMataKuliahs()
    {
        return $this->hasMany(KrsMataKuliah::class);
    }

    // New relationships for additional tables
    public function enrollments()
    {
        return $this->hasMany(Krs::class, 'mata_kuliah_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'course_id');
    }

    public function attendances()
    {
        return $this->hasManyThrough(Attendance::class, Krs::class, 'mata_kuliah_id', 'enrollment_id');
    }

    public function studyProgram()
    {
        return $this->belongsTo(ProgramStudi::class, 'study_program_id');
    }

    // Convenience relationships
    public function currentEnrollments()
    {
        $currentSemester = date('n') <= 6 ? 'odd' : 'even'; // Determine current semester
        $currentYear = date('Y') . '/' . (date('Y') + 1); // Current academic year
        
        return $this->enrollments()
            ->where('academic_year', $currentYear)
            ->where('semester_type', $currentSemester)
            ->where('status', 'approved');
    }

    public function students()
    {
        return $this->hasManyThrough(Mahasiswa::class, Krs::class, 'mata_kuliah_id', 'id', 'id', 'mahasiswa_id');
    }

    // Accessors for new columns
    public function getAvailableSlotsAttribute()
    {
        return max(0, $this->kuota - $this->terisi);
    }

    public function getIsFullAttribute()
    {
        return $this->terisi >= $this->kuota;
    }

    public function getFormattedTypeAttribute()
    {
        $typeMap = [
            'mandatory' => 'Wajib',
            'elective' => 'Pilihan',
            'practicum' => 'Praktikum',
            'thesis' => 'Skripsi/Tesis'
        ];
        
        return $typeMap[$this->type] ?? $this->type;
    }

    public function getCreditHoursAttribute()
    {
        // Assuming 1 SKS = 50 minutes per week
        return $this->sks * 50; // Total minutes per week
    }

    // Additional methods
    public function getAverageGradeAttribute()
    {
        $grades = $this->grades()->where('status', 'published')->get();
        if ($grades->isEmpty()) return 0;
        
        $total = $grades->sum('total_score');
        return round($total / $grades->count(), 2);
    }

    public function getAttendanceRateAttribute()
    {
        $attendances = $this->attendances()->get();
        if ($attendances->isEmpty()) return 0;
        
        $present = $attendances->where('status', 'present')->count();
        return round(($present / $attendances->count()) * 100, 2);
    }

    public function getCourseStatisticsAttribute()
    {
        return [
            'total_students' => $this->currentEnrollments()->count(),
            'available_slots' => $this->available_slots,
            'is_full' => $this->is_full,
            'average_grade' => $this->average_grade,
            'attendance_rate' => $this->attendance_rate,
            'total_hours' => $this->total_hours ?? 0,
            'type' => $this->formatted_type
        ];
    }

    // Update fillable for new columns
    protected $fillable = [
        'kode', 'nama', 'sks', 'kuota', 'terisi', 'semester', 'dosen_id', 'deskripsi',
        'description', 'type', 'total_hours', 'prerequisites', 'study_program_id'
    ];

    // Casts for JSON columns
    protected $casts = [
        'prerequisites' => 'array'
    ];
}
