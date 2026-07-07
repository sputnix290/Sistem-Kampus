<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul', 'isi', 'target_role', 'tipe', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
