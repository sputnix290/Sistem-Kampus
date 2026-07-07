<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'mahasiswa_id', 'semester', 'jumlah', 'status', 'metode',
        'kode_transaksi', 'tanggal_bayar', 'keterangan', 'jenis_pembayaran',
        'jumlah_tagihan', 'dibuat_oleh', 'tahun_akademik_id',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }
    
    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class);
    }
    
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'lunas':
                return 'Lunas';
            case 'belum_lunas':
                return 'Belum Lunas';
            case 'terlambat':
                return 'Terlambat';
            default:
                return ucfirst($this->status);
        }
    }
    
    public function getSisaBayarAttribute()
    {
        return $this->jumlah_tagihan - $this->jumlah;
    }
    

}
