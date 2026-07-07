<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nim', 'jurusan', 'angkatan', 'foto', 'ipk', 'dosen_wali_id', 'semester_aktif',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dosenWali()
    {
        return $this->belongsTo(Dosen::class, 'dosen_wali_id');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function krs()
    {
        return $this->hasMany(Krs::class);
    }

    public function pembayaranAktif()
    {
        return $this->hasOne(Pembayaran::class)->latestOfMany();
    }

    // New relationships for additional tables
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id');
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
        // Get announcements targeted to students
        return Announcement::where('target_audience', 'students')
            ->orWhere('target_audience', 'all')
            ->orWhere(function($query) {
                $query->where('target_audience', 'specific_program')
                      ->whereJsonContains('target_ids', $this->study_program_id);
            })
            ->orWhere(function($query) {
                $query->where('target_audience', 'specific_faculty')
                      ->whereJsonContains('target_ids', $this->faculty_id);
            });
    }

    // Additional methods
    public function getFullProfileAttribute()
    {
        return [
            'nim' => $this->nim,
            'full_name' => $this->full_name ?? $this->user->name,
            'faculty' => $this->faculty->name ?? null,
            'study_program' => $this->studyProgram->name ?? null,
            'angkatan' => $this->angkatan,
            'semester_aktif' => $this->semester_aktif,
            'status' => $this->status,
            'ipk' => $this->ipk,
            'ukt_status' => $this->ukt_status ?? 'unpaid',
            'ukt_amount' => $this->ukt_amount ?? 0
        ];
    }

    public function getAttendancePercentageAttribute()
    {
        $totalSessions = $this->attendances()->count();
        if ($totalSessions === 0) return 0;
        
        $presentSessions = $this->attendances()->where('status', 'present')->count();
        return round(($presentSessions / $totalSessions) * 100, 2);
    }

    public function getCurrentSemesterCoursesAttribute()
    {
        return $this->krs()->whereHas('mataKuliah', function($query) {
            $query->where('semester', $this->semester_aktif);
        })->with('mataKuliah')->get();
    }
}
