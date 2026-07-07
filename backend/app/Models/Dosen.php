<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nip', 'bidang_keahlian', 'foto', 'email_kontak', 'no_hp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class);
    }

    public function mahasiswaWali()
    {
        return $this->hasMany(Mahasiswa::class, 'dosen_wali_id');
    }

    // New relationships for additional tables and columns
    public function schedules()
    {
        return $this->hasMany(Jadwal::class, 'lecturer_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'graded_by', 'user_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Fakultas::class, 'faculty_id');
    }

    public function studyProgram()
    {
        return $this->belongsTo(ProgramStudi::class, 'study_program_id');
    }

    public function announcements()
    {
        // Get announcements targeted to lecturers
        return Announcement::where('target_audience', 'lecturers')
            ->orWhere('target_audience', 'all')
            ->orWhere(function($query) {
                $query->where('target_audience', 'specific_faculty')
                      ->whereJsonContains('target_ids', $this->faculty_id);
            });
    }

    // Accessors for new columns
    public function getFullNameAttribute()
    {
        if ($this->attributes['full_name'] ?? null) {
            return $this->attributes['full_name'];
        }
        
        // Construct from user name and gelar
        $gelarDepan = $this->gelar_depan ? $this->gelar_depan . ' ' : '';
        $gelarBelakang = $this->gelar_belakang ? ', ' . $this->gelar_belakang : '';
        return $gelarDepan . $this->user->name . $gelarBelakang;
    }

    public function getFormattedEducationAttribute()
    {
        $educationMap = [
            's1' => 'S1',
            's2' => 'S2',
            's3' => 'S3',
            'professor' => 'Profesor'
        ];
        
        return $educationMap[$this->education] ?? $this->education;
    }

    public function getFormattedPositionAttribute()
    {
        $positionMap = [
            'lecturer' => 'Dosen',
            'head_of_study_program' => 'Ketua Program Studi',
            'dean' => 'Dekan',
            'vice_dean' => 'Wakil Dekan'
        ];
        
        return $positionMap[$this->position] ?? $this->position;
    }

    // Additional methods
    public function getCurrentScheduleAttribute()
    {
        return $this->schedules()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('mataKuliah')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
    }

    public function getTeachingCoursesAttribute()
    {
        return $this->mataKuliahs()->withCount(['enrollments', 'grades'])->get();
    }

    public function getStudentsToGradeAttribute()
    {
        // Get students in lecturer's courses who need grading
        return Mahasiswa::whereHas('krs', function($query) {
            $query->whereHas('mataKuliah', function($q) {
                $q->where('dosen_id', $this->id);
            })->whereDoesntHave('grades');
        })->with(['krs.mataKuliah'])->get();
    }

    public function getProfileSummaryAttribute()
    {
        return [
            'nip' => $this->nip,
            'full_name' => $this->full_name,
            'position' => $this->formatted_position,
            'education' => $this->formatted_education,
            'expertise' => $this->expertise,
            'faculty' => $this->faculty->name ?? null,
            'study_program' => $this->studyProgram->name ?? null,
            'email' => $this->email ?? $this->user->email,
            'phone' => $this->phone ?? $this->no_hp,
            'total_courses' => $this->mataKuliahs()->count(),
            'total_students' => $this->mahasiswaWali()->count()
        ];
    }

    // Update fillable for new columns
    protected $fillable = [
        'user_id', 'nip', 'bidang_keahlian', 'foto', 'email_kontak', 'no_hp',
        'gelar_depan', 'gelar_belakang', 'full_name', 'birth_date', 'birth_place',
        'gender', 'address', 'phone', 'email', 'education', 'expertise',
        'faculty_id', 'study_program_id', 'position', 'status'
    ];
}
