<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Krs extends Model
{
    use HasFactory;

    protected $table = 'krs';

    protected $fillable = [
        'mahasiswa_id', 'semester', 'status', 'catatan_dosen', 'total_sks',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(MataKuliah::class, 'krs_mata_kuliahs', 'krs_id', 'mata_kuliah_id');
    }

    public function krsMataKuliahs()
    {
        return $this->hasMany(KrsMataKuliah::class);
    }

    // New relationships for additional tables and columns
    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'enrollment_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'enrollment_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Convenience relationships
    public function getGradeAttribute()
    {
        return $this->grades()->first();
    }

    public function getAttendanceRecordsAttribute()
    {
        return $this->attendances()->orderBy('attendance_date')->get();
    }

    // Accessors for new columns
    public function getFormattedStatusAttribute()
    {
        $statusMap = [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'completed' => 'Selesai'
        ];
        
        return $statusMap[$this->status] ?? $this->status;
    }

    public function getFormattedSemesterTypeAttribute()
    {
        $typeMap = [
            'odd' => 'Ganjil',
            'even' => 'Genap',
            'short' => 'Pendek'
        ];
        
        return $typeMap[$this->semester_type] ?? $this->semester_type;
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getCanBeGradedAttribute()
    {
        return $this->is_approved && !$this->is_completed;
    }

    // Additional methods
    public function approve($approverId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes' => $notes ?? $this->notes
        ]);
    }

    public function reject($approverId, $notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes' => $notes ?? $this->notes
        ]);
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    public function calculateTotalSks()
    {
        if ($this->mataKuliah) {
            return $this->mataKuliah->sks;
        }
        
        // For old structure with multiple courses
        return $this->mataKuliahs()->sum('sks');
    }

    public function getAttendancePercentageAttribute()
    {
        $attendances = $this->attendances;
        if ($attendances->isEmpty()) return 0;
        
        $present = $attendances->where('status', 'present')->count();
        return round(($present / $attendances->count()) * 100, 2);
    }

    public function getFinalGradeAttribute()
    {
        $grade = $this->grade;
        if (!$grade) return null;
        
        return [
            'letter_grade' => $grade->letter_grade,
            'grade_point' => $grade->grade_point,
            'total_score' => $grade->total_score,
            'status' => $grade->status
        ];
    }

    public function getEnrollmentSummaryAttribute()
    {
        return [
            'id' => $this->id,
            'student' => $this->mahasiswa->full_name ?? $this->mahasiswa->user->name,
            'nim' => $this->mahasiswa->nim,
            'course' => $this->mataKuliah->nama ?? 'Multiple Courses',
            'course_code' => $this->mataKuliah->kode ?? null,
            'sks' => $this->calculateTotalSks(),
            'academic_year' => $this->academic_year,
            'semester' => $this->formatted_semester_type,
            'status' => $this->formatted_status,
            'approved_by' => $this->approvedBy->name ?? null,
            'approved_at' => $this->approved_at?->format('d M Y H:i'),
            'attendance_percentage' => $this->attendance_percentage,
            'final_grade' => $this->final_grade,
            'notes' => $this->notes
        ];
    }

    // Validation methods
    public function validatePrerequisites($studentId)
    {
        if (!$this->mataKuliah || empty($this->mataKuliah->prerequisites)) {
            return ['valid' => true, 'message' => 'No prerequisites required'];
        }

        $prerequisites = $this->mataKuliah->prerequisites;
        $completedCourses = Krs::where('mahasiswa_id', $studentId)
            ->where('status', 'completed')
            ->whereHas('grades', function($query) {
                $query->whereIn('letter_grade', ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D']);
            })
            ->pluck('mata_kuliah_id');

        $missing = array_diff($prerequisites, $completedCourses->toArray());
        
        if (empty($missing)) {
            return ['valid' => true, 'message' => 'All prerequisites satisfied'];
        }

        $missingCourses = MataKuliah::whereIn('id', $missing)->pluck('nama')->toArray();
        return [
            'valid' => false,
            'message' => 'Missing prerequisites: ' . implode(', ', $missingCourses)
        ];
    }

    public function validateSksLimit($studentId, $semester, $academicYear)
    {
        $currentSks = Krs::where('mahasiswa_id', $studentId)
            ->where('academic_year', $academicYear)
            ->where('semester_type', $semester)
            ->whereIn('status', ['approved', 'pending'])
            ->with('mataKuliah')
            ->get()
            ->sum(function($krs) {
                return $krs->calculateTotalSks();
            });

        $newCourseSks = $this->calculateTotalSks();
        $totalSks = $currentSks + $newCourseSks;

        // Maximum SKS per semester is typically 24
        $maxSks = 24;

        if ($totalSks > $maxSks) {
            return [
                'valid' => false,
                'message' => "SKS limit exceeded. Current: {$currentSks}, New: {$newCourseSks}, Max: {$maxSks}"
            ];
        }

        return ['valid' => true, 'message' => 'SKS limit OK'];
    }

    // Update fillable for new columns
    protected $fillable = [
        'mahasiswa_id', 'mata_kuliah_id', 'status', 'academic_year', 
        'semester_type', 'approved_at', 'approved_by', 'notes',
        'catatan_dosen', 'total_sks'
    ];

    // Casts for date columns
    protected $casts = [
        'approved_at' => 'datetime'
    ];
}
