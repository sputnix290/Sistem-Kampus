<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        return view('dashboard');
    }
    
    /**
     * Get dashboard statistics for admin.
     */
    public function stats()
    {
        $totalMahasiswa = Mahasiswa::count();
        $totalPembayaran = Pembayaran::count();
        $pembayaranLunas = Pembayaran::where('status', 'lunas')->count();
        $pembayaranBelumLunas = Pembayaran::where('status', 'belum_lunas')->count();
        $pembayaranTerlambat = Pembayaran::where('status', 'terlambat')->count();
        $totalNominal = Pembayaran::sum('jumlah_dibayar');
        
        return response()->json([
            'total_mahasiswa' => $totalMahasiswa,
            'total_pembayaran' => $totalPembayaran,
            'statistik' => [
                'lunas' => $pembayaranLunas,
                'belum_lunas' => $pembayaranBelumLunas,
                'terlambat' => $pembayaranTerlambat,
                'persentase_lunas' => $totalPembayaran > 0 ? round(($pembayaranLunas / $totalPembayaran) * 100, 2) : 0
            ],
            'total_nominal' => $totalNominal
        ]);
    }
    
    /**
     * Get student dashboard data.
     */
    public function studentDashboard($mahasiswaId)
    {
        $mahasiswa = Mahasiswa::with(['user', 'fakultas', 'programStudi'])->findOrFail($mahasiswaId);
        
        $pembayaran = Pembayaran::where('mahasiswa_id', $mahasiswaId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $statistik = [
            'total' => $pembayaran->count(),
            'lunas' => $pembayaran->where('status', 'lunas')->count(),
            'belum_lunas' => $pembayaran->where('status', 'belum_lunas')->count(),
            'terlambat' => $pembayaran->where('status', 'terlambat')->count(),
            'total_dibayar' => $pembayaran->sum('jumlah_dibayar'),
            'total_tagihan' => $pembayaran->sum('jumlah_tagihan')
        ];
        
        return response()->json([
            'mahasiswa' => $mahasiswa,
            'pembayaran' => $pembayaran,
            'statistik' => $statistik
        ]);
    }
}
