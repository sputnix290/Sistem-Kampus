<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $table = 'grades';
    
    protected $fillable = [
        'enrollment_id',
        'student_id',
        'course_id',
        'assignment_score',
        'quiz_score',
        'mid_exam_score',
        'final_exam_score',
        'practicum_score',
        'attendance_score',
        'total_score',
        'letter_grade',
        'grade_point',
        'graded_by',
        'graded_at',
        'status',
        'notes'
    ];

    protected $casts = [
        'assignment_score' => 'decimal:2',
        'quiz_score' => 'decimal:2',
        'mid_exam_score' => 'decimal:2',
        'final_exam_score' => 'decimal:2',
        'practicum_score' => 'decimal:2',
        'attendance_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'grade_point' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Krs::class, 'enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Mahasiswa::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(MataKuliah::class, 'course_id');
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // Method untuk menghitung nilai akhir
    public function calculateTotalScore($weights = null)
    {
        // Default weights jika tidak disediakan
        if (!$weights) {
            $weights = [
                'assignment' => 0.20,
                'quiz' => 0.15,
                'mid_exam' => 0.25,
                'final_exam' => 0.30,
                'practicum' => 0.05,
                'attendance' => 0.05,
            ];
        }

        $total = ($this->assignment_score * $weights['assignment']) +
                 ($this->quiz_score * $weights['quiz']) +
                 ($this->mid_exam_score * $weights['mid_exam']) +
                 ($this->final_exam_score * $weights['final_exam']) +
                 ($this->practicum_score * $weights['practicum']) +
                 ($this->attendance_score * $weights['attendance']);

        return round($total, 2);
    }

    // Method untuk menentukan grade huruf
    public function determineLetterGrade($score = null)
    {
        if (!$score) {
            $score = $this->total_score;
        }

        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D+';
        if ($score >= 40) return 'D';
        return 'E';
    }

    // Method untuk menghitung grade point
    public function calculateGradePoint($letterGrade = null)
    {
        if (!$letterGrade) {
            $letterGrade = $this->letter_grade;
        }

        $gradePoints = [
            'A' => 4.00,
            'A-' => 3.75,
            'B+' => 3.50,
            'B' => 3.00,
            'B-' => 2.75,
            'C+' => 2.50,
            'C' => 2.00,
            'C-' => 1.75,
            'D+' => 1.50,
            'D' => 1.00,
            'E' => 0.00,
        ];

        return $gradePoints[$letterGrade] ?? 0.00;
    }
}
