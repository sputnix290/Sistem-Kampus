<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PengumumanController extends Controller
{
    /**
     * Menampilkan daftar pengumuman dengan filter.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Pengumuman::with(['dibuatOleh', 'diperbaruiOleh']);
        
        // Filter berdasarkan role user
        if ($user->isMahasiswa()) {
            $query->where(function($q) {
                $q->where('target_role', 'mahasiswa')
                  ->orWhere('target_role', 'all');
            });
        } elseif ($user->isDosen()) {
            $query->where(function($q) {
                $q->where('target_role', 'dosen')
                  ->orWhere('target_role', 'all');
            });
        }
        // Admin bisa lihat semua
        
        // Filter pencarian
        if ($request->has('cari')) {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('judul', 'like', "%{$cari}%")
                  ->orWhere('konten', 'like', "%{$cari}%")
                  ->orWhere('kategori', 'like', "%{$cari}%")
                  ->orWhereHas('dibuatOleh', function($userQuery) use ($cari) {
                      $userQuery->where('name', 'like', "%{$cari}%");
                  });
            });
        }
        
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        
        if ($request->has('target_role')) {
            $query->where('target_role', $request->target_role);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        if ($request->has('tanggal_mulai') && $request->has('tanggal_akhir')) {
            $query->whereBetween('tanggal_terbit', [$request->tanggal_mulai, $request->tanggal_akhir]);
        }
        
        // Default: hanya yang aktif dan belum kadaluarsa
        if (!$request->has('show_expired')) {
            $query->where(function($q) {
                $q->where('status', 'aktif')
                  ->where(function($subQuery) {
                      $subQuery->whereNull('tanggal_kadaluarsa')
                              ->orWhere('tanggal_kadaluarsa', '>=', Carbon::now());
                  });
            });
        }
        
        // Urutkan: prioritas tinggi dulu, lalu terbaru
        $query->orderByRaw("CASE 
            WHEN prioritas = 'tinggi' THEN 1
            WHEN prioritas = 'sedang' THEN 2
            WHEN prioritas = 'rendah' THEN 3
            ELSE 4
        END")
        ->orderBy('created_at', 'desc');
        
        // Paginasi
        $perPage = $request->get('per_page', 15);
        $pengumuman = $query->paginate($perPage);
        
        return response()->json([
            'pengumuman' => $pengumuman,
            'statistik' => [
                'total' => $pengumuman->total(),
                'aktif' => $pengumuman->where('status', 'aktif')->count(),
                'prioritas_tinggi' => $pengumuman->where('prioritas', 'tinggi')->count(),
                'kadaluarsa' => Pengumuman::where('status', 'aktif')
                    ->whereNotNull('tanggal_kadaluarsa')
                    ->where('tanggal_kadaluarsa', '<', Carbon::now())
                    ->count()
            ],
            'filter' => [
                'kategori_options' => ['akademik', 'administrasi', 'beasiswa', 'kegiatan', 'lowongan', 'lainnya'],
                'target_role_options' => ['all', 'admin', 'dosen', 'mahasiswa', 'orang_tua'],
                'prioritas_options' => ['tinggi', 'sedang', 'rendah'],
                'status_options' => ['aktif', 'draft', 'diarsipkan']
            ]
        ]);
    }

    /**
     * Menyimpan pengumuman baru.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'kategori' => 'required|in:akademik,administrasi,beasiswa,kegiatan,lowongan,lainnya',
            'target_role' => 'required|in:all,admin,dosen,mahasiswa,orang_tua',
            'prioritas' => 'required|in:tinggi,sedang,rendah',
            'status' => 'required|in:aktif,draft,diarsipkan',
            'tanggal_terbit' => 'required|date',
            'tanggal_kadaluarsa' => 'nullable|date|after_or_equal:tanggal_terbit',
            'lampiran' => 'nullable|string|max:255',
            'dibuat_oleh' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        // Hanya admin yang bisa membuat pengumuman untuk semua role
        if (!$user->isAdmin() && $request->target_role !== $user->role) {
            return response()->json([
                'pesan' => 'Anda hanya dapat membuat pengumuman untuk role Anda sendiri.'
            ], 403);
        }
        
        $pengumuman = Pengumuman::create($request->all());
        
        return response()->json([
            'pesan' => 'Pengumuman berhasil dibuat.',
            'pengumuman' => $pengumuman->load(['dibuatOleh']),
            'informasi' => [
                'id' => $pengumuman->id,
                'judul' => $pengumuman->judul,
                'kategori' => $pengumuman->kategori,
                'target_role' => $pengumuman->target_role,
                'prioritas' => $pengumuman->prioritas,
                'status' => $pengumuman->status,
                'tanggal_terbit' => $pengumuman->tanggal_terbit,
                'dibuat_oleh' => $pengumuman->dibuatOleh->name
            ]
        ], 201);
    }

    /**
     * Menampilkan detail pengumuman.
     */
    public function show($id)
    {
        $pengumuman = Pengumuman::with(['dibuatOleh', 'diperbaruiOleh'])->findOrFail($id);
        $user = auth()->user();
        
        // Cek apakah user memiliki akses ke pengumuman ini
        if ($pengumuman->target_role !== 'all' && $pengumuman->target_role !== $user->role && !$user->isAdmin()) {
            return response()->json([
                'pesan' => 'Anda tidak memiliki akses ke pengumuman ini.'
            ], 403);
        }
        
        // Update jumlah dilihat
        $pengumuman->increment('dilihat');
        
        // Pengumuman terkait berdasarkan kategori
        $pengumumanTerkait = Pengumuman::where('kategori', $pengumuman->kategori)
            ->where('id', '!=', $pengumuman->id)
            ->where('status', 'aktif')
            ->where(function($q) use ($user) {
                $q->where('target_role', 'all')
                  ->orWhere('target_role', $user->role);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'judul', 'kategori', 'created_at']);
        
        // Status kadaluarsa
        $statusKadaluarsa = null;
        if ($pengumuman->tanggal_kadaluarsa) {
            $kadaluarsa = Carbon::parse($pengumuman->tanggal_kadaluarsa);
            $sekarang = Carbon::now();
            
            if ($kadaluarsa->isPast()) {
                $statusKadaluarsa = 'kadaluarsa';
            } else {
                $hariTersisa = $kadaluarsa->diffInDays($sekarang);
                $statusKadaluarsa = "berlaku hingga {$hariTersisa} hari lagi";
            }
        }
        
        return response()->json([
            'pengumuman' => $pengumuman,
            'metadata' => [
                'dilihat' => $pengumuman->dilihat,
                'status_kadaluarsa' => $statusKadaluarsa,
                'durasi_publikasi' => $this->hitungDurasiPublikasi($pengumuman),
                'target_pembaca' => $this->getTargetPembaca($pengumuman->target_role)
            ],
            'penulis' => [
                'nama' => $pengumuman->dibuatOleh->name,
                'role' => $pengumuman->dibuatOleh->role,
                'terakhir_diperbarui' => $pengumuman->diperbaruiOleh->name ?? null,
                'tanggal_perbarui' => $pengumuman->updated_at
            ],
            'pengumuman_terkait' => $pengumumanTerkait,
            'akses' => [
                'dapat_edit' => $user->isAdmin() || $user->id === $pengumuman->dibuat_oleh,
                'dapat_hapus' => $user->isAdmin(),
                'status' => $pengumuman->status
            ]
        ]);
    }

    /**
     * Mengupdate pengumuman.
     */
    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $user = auth()->user();
        
        // Cek apakah user memiliki izin mengedit
        if (!$user->isAdmin() && $user->id !== $pengumuman->dibuat_oleh) {
            return response()->json([
                'pesan' => 'Anda tidak memiliki izin untuk mengedit pengumuman ini.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|string|max:255',
            'konten' => 'sometimes|string',
            'kategori' => 'sometimes|in:akademik,administrasi,beasiswa,kegiatan,lowongan,lainnya',
            'target_role' => 'sometimes|in:all,admin,dosen,mahasiswa,orang_tua',
            'prioritas' => 'sometimes|in:tinggi,sedang,rendah',
            'status' => 'sometimes|in:aktif,draft,diarsipkan',
            'tanggal_terbit' => 'sometimes|date',
            'tanggal_kadaluarsa' => 'nullable|date|after_or_equal:tanggal_terbit',
            'lampiran' => 'nullable|string|max:255',
            'diperbarui_oleh' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        // Hanya admin yang bisa mengubah target_role
        if ($request->has('target_role') && $request->target_role !== $pengumuman->target_role && !$user->isAdmin()) {
            return response()->json([
                'pesan' => 'Hanya admin yang dapat mengubah target role pengumuman.'
            ], 403);
        }
        
        $pengumuman->update($request->all());
        
        return response()->json([
            'pesan' => 'Pengumuman berhasil diperbarui.',
            'pengumuman' => $pengumuman->fresh()->load(['dibuatOleh', 'diperbaruiOleh']),
            'perubahan' => [
                'judul_lama' => $pengumuman->getOriginal('judul'),
                'status_lama' => $pengumuman->getOriginal('status'),
                'diperbarui_oleh' => $pengumuman->diperbaruiOleh->name ?? null,
                'tanggal_perbarui' => $pengumuman->updated_at->format('d M Y H:i')
            ]
        ]);
    }

    /**
     * Menghapus pengumuman.
     */
    public function destroy($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $user = auth()->user();
        
        // Hanya admin atau pembuat yang bisa menghapus
        if (!$user->isAdmin() && $user->id !== $pengumuman->dibuat_oleh) {
            return response()->json([
                'pesan' => 'Anda tidak memiliki izin untuk menghapus pengumuman ini.'
            ], 403);
        }
        
        $pengumuman->delete();
        
        return response()->json([
            'pesan' => 'Pengumuman berhasil dihapus.',
            'pengumuman_terhapus' => [
                'id' => $pengumuman->id,
                'judul' => $pengumuman->judul,
                'dibuat_oleh' => $pengumuman->dibuatOleh->name ?? null,
                'tanggal_terbit' => $pengumuman->tanggal_terbit
            ]
        ]);
    }

    /**
     * Pengumuman terbaru untuk dashboard.
     */
    public function terbaru(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);
        
        $query = Pengumuman::where('status', 'aktif')
            ->where(function($q) use ($user) {
                $q->where('target_role', 'all')
                  ->orWhere('target_role', $user->role);
            })
            ->where(function($q) {
                $q->whereNull('tanggal_kadaluarsa')
                  ->orWhere('tanggal_kadaluarsa', '>=', Carbon::now());
            })
            ->with('dibuatOleh')
            ->orderBy('created_at', 'desc');
        
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        
        if ($request->has('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        $pengumuman = $query->take($limit)->get();
        
        return response()->json([
            'pengumuman_terbaru' => $pengumuman->map(function($item) {
                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'konten_singkat' => substr(strip_tags($item->konten), 0, 100) . '...',
                    'kategori' => $item->kategori,
                    'prioritas' => $item->prioritas,
                    'dibuat_oleh' => $item->dibuatOleh->name,
                    'tanggal_terbit' => Carbon::parse($item->tanggal_terbit)->format('d M Y'),
                    'dilihat' => $item->dilihat,
                    'lampiran' => !empty($item->lampiran)
                ];
            }),
            'ringkasan' => [
                'total_aktif' => Pengumuman::where('status', 'aktif')->count(),
                'prioritas_tinggi' => Pengumuman::where('status', 'aktif')->where('prioritas', 'tinggi')->count(),
                'target_role' => Pengumuman::selectRaw('target_role, count(*) as jumlah')
                    ->where('status', 'aktif')
                    ->groupBy('target_role')
                    ->get()
                    ->pluck('jumlah', 'target_role')
            ]
        ]);
    }

    /**
     * Statistik pengumuman.
     */
    public function statistik(Request $request)
    {
        $query = Pengumuman::query();
        
        if ($request->has('tahun')) {
            $query->whereYear('created_at', $request->tahun);
        }
        
        $totalPengumuman = $query->count();
        $aktif = $query->clone()->where('status', 'aktif')->count();
        $draft = $query->clone()->where('status', 'draft')->count();
        $diarsipkan = $query->clone()->where('status', 'diarsipkan')->count();
        
        $byKategori = $query->clone()
            ->selectRaw('kategori, count(*) as jumlah')
            ->groupBy('kategori')
            ->get()
            ->pluck('jumlah', 'kategori');
        
        $byBulan = Pengumuman::selectRaw('YEAR(created_at) as tahun, MONTH(created_at) as bulan, count(*) as jumlah')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->take(12)
            ->get();
        
        $byTargetRole = $query->clone()
            ->selectRaw('target_role, count(*) as jumlah')
            ->groupBy('target_role')
            ->get()
            ->pluck('jumlah', 'target_role');
        
        $totalDilihat = $query->clone()->sum('dilihat');
        $rataDilihat = $totalPengumuman > 0 ? round($totalDilihat / $totalPengumuman, 2) : 0;
        
        return response()->json([
            'total_pengumuman' => $totalPengumuman,
            'status' => [
                'aktif' => $aktif,
                'draft' => $draft,
                'diarsipkan' => $diarsipkan,
                'persentase_aktif' => $totalPengumuman > 0 ? round(($aktif / $totalPengumuman) * 100, 2) : 0
            ],
            'per_kategori' => $byKategori,
            'per_bulan' => $byBulan->map(function($item) {
                return [
                    'periode' => date('M Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun)),
                    'jumlah' => $item->jumlah
                ];
            }),
            'per_target_role' => $byTargetRole,
            'engagement' => [
                'total_dilihat' => $totalDilihat,
                'rata_dilihat' => $rataDilihat,
                'pengumuman_terpopuler' => Pengumuman::orderBy('dilihat', 'desc')->take(3)->get(['id', 'judul', 'dilihat'])
            ]
        ]);
    }

    /**
     * Export pengumuman ke PDF/Excel.
     */
    public function export(Request $request)
    {
        $jenis = $request->get('jenis', 'pdf');
        $data = $this->siapkanDataExport($request);
        $namaFile = "pengumuman_" . date('Ymd_His');
        
        return response()->json([
            'pesan' => 'Fitur export pengumuman akan diimplementasikan.',
            'nama_file' => $namaFile . '.' . $jenis,
            'preview_data' => $data,
            'status' => 'export_belum_diimplementasi'
        ]);
    }

    /**
     * Import pengumuman dari Excel/CSV.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'mode_import' => 'sometimes|in:buat,perbarui,ganti'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        return response()->json([
            'pesan' => 'Fitur import pengumuman akan diimplementasikan.',
            'status' => 'import_belum_diimplementasi'
        ]);
    }

    // Helper methods
    
    private function hitungDurasiPublikasi($pengumuman)
    {
        $terbit = Carbon::parse($pengumuman->tanggal_terbit);
        $sekarang = Carbon::now();
        
        if ($pengumuman->tanggal_kadaluarsa) {
            $kadaluarsa = Carbon::parse($pengumuman->tanggal_kadaluarsa);
            
            if ($kadaluarsa->isPast()) {
                return 'telah berakhir';
            }
            
            $hariTersisa = $kadaluarsa->diffInDays($sekarang);
            return "{$hariTersisa} hari tersisa";
        }
        
        $hariTerbit = $terbit->diffInDays($sekarang);
        return "{$hariTerbit} hari sejak terbit";
    }
    
    private function getTargetPembaca($targetRole)
    {
        switch ($targetRole) {
            case 'all': return 'Semua pengguna';
            case 'admin': return 'Administrator';
            case 'dosen': return 'Dosen';
            case 'mahasiswa': return 'Mahasiswa';
            case 'orang_tua': return 'Orang Tua';
            default: return $targetRole;
        }
    }
    
    private function siapkanDataExport($request)
    {
        $query = Pengumuman::with(['dibuatOleh', 'diperbaruiOleh']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        
        if ($request->has('tanggal_mulai') && $request->has('tanggal_akhir')) {
            $query->whereBetween('tanggal_terbit', [$request->tanggal_mulai, $request->tanggal_akhir]);
        }
        
        $pengumuman = $query->get();
        
        return $pengumuman->map(function($item) {
            return [
                'judul' => $item->judul,
                'kategori' => $item->kategori,
                'target_role' => $item->target_role,
                'prioritas' => $item->prioritas,
                'status' => $item->status,
                'tanggal_terbit' => $item->tanggal_terbit,
                'tanggal_kadaluarsa' => $item->tanggal_kadaluarsa,
                'dibuat_oleh' => $item->dibuatOleh->name ?? null,
                'dilihat' => $item->dilihat,
                'konten_pendek' => substr(strip_tags($item->konten), 0, 200)
            ];
        });
    }
}
