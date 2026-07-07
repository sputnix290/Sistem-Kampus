<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'phone', 'photo', 'last_login_at', 'status'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime'
    ];

    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class);
    }

    public function dosen()
    {
        return $this->hasOne(Dosen::class);
    }

    // New relationships for additional columns and tables
    public function profile()
    {
        return match($this->role) {
            'mahasiswa' => $this->mahasiswa,
            'dosen' => $this->dosen,
            'admin' => null, // Admin doesn't have separate profile
            'parent' => null, // Parent profile can be added later
            default => null
        };
    }

    public function announcementsCreated()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function newsArticles()
    {
        return $this->hasMany(News::class, 'author_id');
    }

    public function gradesGiven()
    {
        return $this->hasMany(Grade::class, 'graded_by');
    }

    public function attendancesRecorded()
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    public function contactMessagesResponded()
    {
        return $this->hasMany(ContactMessage::class, 'responded_by');
    }

    // Accessors for new columns
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        
        // Return default based on role
        return match($this->role) {
            'mahasiswa' => asset('defaults/student-avatar.png'),
            'dosen' => asset('defaults/lecturer-avatar.png'),
            'admin' => asset('defaults/admin-avatar.png'),
            default => asset('defaults/user-avatar.png')
        };
    }

    public function getFormattedRoleAttribute()
    {
        $roleMap = [
            'mahasiswa' => 'Mahasiswa',
            'dosen' => 'Dosen',
            'admin' => 'Administrator',
            'parent' => 'Orang Tua'
        ];
        
        return $roleMap[$this->role] ?? $this->role;
    }

    public function getLastLoginFormattedAttribute()
    {
        if (!$this->last_login_at) return 'Belum pernah login';
        
        return $this->last_login_at->diffForHumans();
    }

    // Additional methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isLecturer()
    {
        return $this->role === 'dosen';
    }

    public function isStudent()
    {
        return $this->role === 'mahasiswa';
    }

    public function isParent()
    {
        return $this->role === 'parent';
    }

    public function getDashboardRouteAttribute()
    {
        return match($this->role) {
            'admin' => '/admin/dashboard',
            'dosen' => '/lecturer/dashboard',
            'mahasiswa' => '/student/dashboard',
            'parent' => '/parent/dashboard',
            default => '/dashboard'
        };
    }

    public function getProfileSummaryAttribute()
    {
        $summary = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->formatted_role,
            'phone' => $this->phone,
            'photo_url' => $this->profile_photo_url,
            'last_login' => $this->last_login_formatted,
            'status' => $this->status ?? 'active',
            'dashboard_route' => $this->dashboard_route
        ];

        // Add role-specific profile data
        if ($this->profile()) {
            $summary['role_profile'] = $this->profile()->profile_summary ?? null;
        }

        return $summary;
    }

}
