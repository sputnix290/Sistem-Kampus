<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KrsMataKuliah extends Model
{
    use HasFactory;

    protected $table = 'krs_mata_kuliahs';

    protected $fillable = ['krs_id', 'mata_kuliah_id'];

    public function krs()
    {
        return $this->belongsTo(Krs::class);
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }
}
