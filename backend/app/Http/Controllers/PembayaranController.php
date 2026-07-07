<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PembayaranController extends Controller
{
    /**
     * Menampilkan daftar pembayaran dengan filter.
     */
    public function index(Request $request)
    {
        $query = Pembayaran::with(['mahasiswa.user', 'tahunAkademik']);
        
        // Filter pencarian
        if ($request->has('cari')) {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('no_pembayaran', 'like', "%{$cari}%")
                  ->orWhere('jenis_pembayaran', 'like', "%{$cari}%")
                  ->orWhere('status', 'like', "%{$cari}%")
                  ->orWhereHas('mahasiswa', function($mahasiswaQuery) use ($cari) {
                      $mahasiswaQuery->where('nim', 'like', "%{$cari}%")
                                    ->orWhere('full_name', 'like', "%{$cari}%")
                                    ->orWhereHas('user', function($userQuery) use ($cari) {
                                        $userQuery->where('name', 'like', "%{$cari}%");
                                    });
                  });
            });
        }
        
        if ($request->has('mahasiswa_id')) {
            $query->where('mahasiswa_id', $request->mahasiswa_id);
        }
        
        if ($request->has('tahun_akademik_id')) {
            $query->where('tahun_akademik_id', $request->tahun_akademik_id);
        }
        
        if ($request->has('jenis_pembayaran')) {
            $query->where('jenis_pembayaran', $request->jenis_pembayaran);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('tanggal_mulai') && $request->has('tanggal_akhir')) {
            $query->whereBetween('tanggal_pembayaran', [$request->tanggal_mulai, $request->tanggal_akhir]);
        }
        
        // Urutkan hasil
        $urutBy = $request->get('urut_by', 'created_at');
        $urutOrder = $request->get('urut_order', 'desc');
        $query->orderBy($urutBy, $urutOrder);
        
        // Paginasi
        $perPage = $request->get('per_page', 20);
        $pembayaran = $query->paginate($perPage);
        
        // Hitung statistik
        $statistik = [
            'total' => $pembayaran->total(),
            'lunas' => $pembayaran->where('status', 'lunas')->count(),
            'belum_lunas' => $pembayaran->where('status', 'belum_lunas')->count(),
            'terlambat' => $pembayaran->where('status', 'terlambat')->count(),
            'total_nominal' => $pembayaran->sum('jumlah_dibayar')
        ];
        
        return response()->json([
            'pembayaran' => $pembayaran,
            'statistik' => $statistik,
            'filter' => [
                'jenis_pembayaran_options' => ['spp', 'ukt', 'daftar_ulang', 'praktikum', 'skripsi', 'lainnya'],
                'status_options' => ['lunas', 'belum_lunas', 'terlambat', 'dibatalkan'],
                'tahun_akademik_options' => TahunAkademik::all(['id', 'tahun', 'semester'])
            ]
        ]);
    }

    /**
     * Menyimpan pembayaran baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'tahun_akademik_id' => 'required|exists:tahun_akademiks,id',
            'jenis_pembayaran' => 'required|in:spp,ukt,daftar_ulang,praktikum,skripsi,lainnya',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'jumlah_dibayar' => 'required|numeric|min:0',
            'tanggal_tagihan' => 'required|date',
            'tanggal_jatuh_tempo' => 'required|date',
            'tanggal_pembayaran' => 'nullable|date',
            'metode_pembayaran' => 'required|in:transfer,tunai,kartu_kredit,debit,online',
            'status' => 'required|in:lunas,belum_lunas,terlambat,dibatalkan',
            'keterangan' => 'nullable|string|max:500',
            'bukti_pembayaran' => 'nullable|string|max:255',
            'dibuat_oleh' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        // Generate nomor pembayaran otomatis
        $tahun = date('Y');
        $bulan = date('m');
        $urutan = Pembayaran::whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->count() + 1;
        
        $noPembayaran = 'PAY/' . $tahun . $bulan . '/' . str_pad($urutan, 4, '0', STR_PAD_LEFT);
        
        $data = $request->all();
        $data['no_pembayaran'] = $noPembayaran;
        
        $pembayaran = Pembayaran::create($data);
        
        // Update status mahasiswa jika pembayaran SPP/UKT
        if (in_array($pembayaran->jenis_pembayaran, ['spp', 'ukt']) && $pembayaran->status === 'lunas') {
            $this->updateStatusMahasiswa($pembayaran->mahasiswa_id, 'aktif');
        }
        
        return response()->json([
            'pesan' => 'Pembayaran berhasil dicatat.',
            'pembayaran' => $pembayaran->load(['mahasiswa.user', 'tahunAkademik']),
            'rincian' => [
                'no_pembayaran' => $pembayaran->no_pembayaran,
                'sisa_tagihan' => $pembayaran->jumlah_tagihan - $pembayaran->jumlah_dibayar,
                'status_pembayaran' => $pembayaran->status,
                'keterlambatan' => $this->hitungKeterlambatan($pembayaran)
            ]
        ], 201);
    }

    /**
     * Menampilkan detail pembayaran.
     */
    public function show($id)
    {
        $pembayaran = Pembayaran::with(['mahasiswa.user', 'tahunAkademik'])->findOrFail($id);
        
        $riwayatPembayaran = Pembayaran::where('mahasiswa_id', $pembayaran->mahasiswa_id)
            ->where('jenis_pembayaran', $pembayaran->jenis_pembayaran)
            ->orderBy('tanggal_pembayaran', 'desc')
            ->take(10)
            ->get();
        
        return response()->json([
            'pembayaran' => $pembayaran,
            'detail_mahasiswa' => [
                'nama' => $pembayaran->mahasiswa->full_name ?? $pembayaran->mahasiswa->user->name,
                'nim' => $pembayaran->mahasiswa->nim,
                'fakultas' => $pembayaran->mahasiswa->fakultas->name ?? null,
                'program_studi' => $pembayaran->mahasiswa->programStudi->name ?? null
            ],
            'rincian_keuangan' => [
                'jumlah_tagihan' => $pembayaran->jumlah_tagihan,
                'jumlah_dibayar' => $pembayaran->jumlah_dibayar,
                'sisa_tagihan' => $pembayaran->jumlah_tagihan - $pembayaran->jumlah_dibayar,
                'denda_keterlambatan' => $this->hitungDendaKeterlambatan($pembayaran),
                'status_pembayaran' => $pembayaran->status,
                'tanggal_jatuh_tempo' => $pembayaran->tanggal_jatuh_tempo,
                'hari_terlambat' => $this->hitungHariTerlambat($pembayaran)
            ],
            'riwayat_pembayaran' => $riwayatPembayaran,
            'informasi_tahun_akademik' => [
                'tahun' => $pembayaran->tahunAkademik->tahun,
                'semester' => $pembayaran->tahunAkademik->semester,
                'status' => $pembayaran->tahunAkademik->status
            ]
        ]);
    }

    /**
     * Mengupdate status pembayaran.
     */
    public function updateStatus(Request $request, $id)
    {
        $pembayaran = Pembayaran::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:lunas,belum_lunas,terlambat,dibatalkan',
            'jumlah_dibayar' => 'nullable|numeric|min:0',
            'tanggal_pembayaran' => 'nullable|date',
            'bukti_pembayaran' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:500',
            'diperbarui_oleh' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        $statusLama = $pembayaran->status;
        $data = $request->all();
        
        // Jika status menjadi lunas, set tanggal pembayaran ke sekarang jika tidak ada
        if ($request->status === 'lunas' && !$request->has('tanggal_pembayaran')) {
            $data['tanggal_pembayaran'] = Carbon::now()->toDateString();
        }
        
        // Jika jumlah dibayar sama dengan tagihan, otomatis lunas
        if ($request->has('jumlah_dibayar') && $request->jumlah_dibayar >= $pembayaran->jumlah_tagihan) {
            $data['status'] = 'lunas';
        }
        
        $pembayaran->update($data);
        
        // Update status mahasiswa jika perlu
        if (in_array($pembayaran->jenis_pembayaran, ['spp', 'ukt'])) {
            if ($pembayaran->status === 'lunas' && $statusLama !== 'lunas') {
                $this->updateStatusMahasiswa($pembayaran->mahasiswa_id, 'aktif');
            } elseif ($pembayaran->status === 'terlambat' && $pembayaran->hari_terlambat > 30) {
                $this->updateStatusMahasiswa($pembayaran->mahasiswa_id, 'terblokir');
            }
        }
        
        return response()->json([
            'pesan' => 'Status pembayaran berhasil diperbarui.',
            'pembayaran' => $pembayaran->fresh()->load(['mahasiswa.user', 'tahunAkademik']),
            'perubahan' => [
                'status_lama' => $statusLama,
                'status_baru' => $pembayaran->status,
                'tanggal_pembayaran' => $pembayaran->tanggal_pembayaran,
                'sisa_tagihan' => $pembayaran->jumlah_tagihan - $pembayaran->jumlah_dibayar
            ]
        ]);
    }

    /**
     * Menghapus pembayaran.
     */
    public function destroy($id)
    {
        $pembayaran = Pembayaran::findOrFail($id);
        
        // Cek jika pembayaran sudah lunas
        if ($pembayaran->status === 'lunas') {
            return response()->json([
                'pesan' => 'Tidak dapat menghapus pembayaran yang sudah lunas. Silakan batalkan terlebih dahulu.'
            ], 400);
        }
        
        $pembayaran->delete();
        
        return response()->json([
            'pesan' => 'Pembayaran berhasil dihapus.'
        ]);
    }

    /**
     * Pembayaran oleh mahasiswa.
     */
    public function byMahasiswa($mahasiswaId)
    {
        $mahasiswa = Mahasiswa::with(['user', 'fakultas', 'programStudi'])->findOrFail($mahasiswaId);
        
        $pembayaran = Pembayaran::where('mahasiswa_id', $mahasiswaId)
            ->with('tahunAkademik')
            ->orderBy('tanggal_jatuh_tempo', 'desc')
            ->get()
            ->groupBy('jenis_pembayaran');
        
        $statistik = [
            'total_pembayaran' => Pembayaran::where('mahasiswa_id', $mahasiswaId)->count(),
            'lunas' => Pembayaran::where('mahasiswa_id', $mahasiswaId)->where('status', 'lunas')->count(),
            'belum_lunas' => Pembayaran::where('mahasiswa_id', $mahasiswaId)->where('status', 'belum_lunas')->count(),
            'terlambat' => Pembayaran::where('mahasiswa_id', $mahasiswaId)->where('status', 'terlambat')->count(),
            'total_nominal' => Pembayaran::where('mahasiswa_id', $mahasiswaId)->sum('jumlah_dibayar'),
            'sisa_tagihan' => Pembayaran::where('mahasiswa_id', $mahasiswaId)
                ->where('status', '!=', 'lunas')
                ->sum('jumlah_tagihan')
        ];
        
        $tagihanBerjalan = Pembayaran::where('mahasiswa_id', $mahasiswaId)
            ->where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '>=', Carbon::now())
            ->get()
            ->map(function($tagihan) {
                return [
                    'jenis' => $tagihan->jenis_pembayaran,
                    'jumlah' => $tagihan->jumlah_tagihan,
                    'jatuh_tempo' => $tagihan->tanggal_jatuh_tempo,
                    'hari_menuju_jatuh_tempo' => Carbon::parse($tagihan->tanggal_jatuh_tempo)->diffInDays(Carbon::now())
                ];
            });
        
        return response()->json([
            'mahasiswa' => [
                'nama' => $mahasiswa->full_name ?? $mahasiswa->user->name,
                'nim' => $mahasiswa->nim,
                'fakultas' => $mahasiswa->fakultas->name ?? null,
                'program_studi' => $mahasiswa->programStudi->name ?? null,
                'status_akademik' => $mahasiswa->status
            ],
            'pembayaran_per_jenis' => $pembayaran->map(function($jenisPembayaran, $jenis) {
                return [
                    'jenis' => $jenis,
                    'total' => $jenisPembayaran->count(),
                    'lunas' => $jenisPembayaran->where('status', 'lunas')->count(),
                    'total_nominal' => $jenisPembayaran->sum('jumlah_dibayar'),
                    'rincian' => $jenisPembayaran->take(5)->map(function($p) {
                        return [
                            'no_pembayaran' => $p->no_pembayaran,
                            'tanggal' => $p->tanggal_pembayaran,
                            'jumlah' => $p->jumlah_dibayar,
                            'status' => $p->status
                        ];
                    })
                ];
            }),
            'statistik' => $statistik,
            'tagihan_berjalan' => $tagihanBerjalan,
            'rekomendasi_pembayaran' => $this->rekomendasiPembayaran($mahasiswaId)
        ]);
    }

    /**
     * Statistik pembayaran untuk dashboard.
     */
    public function statistik(Request $request)
    {
        $query = Pembayaran::query();
        
        if ($request->has('tahun_akademik_id')) {
            $query->where('tahun_akademik_id', $request->tahun_akademik_id);
        }
        
        if ($request->has('jenis_pembayaran')) {
            $query->where('jenis_pembayaran', $request->jenis_pembayaran);
        }
        
        $totalPembayaran = $query->count();
        $lunas = $query->clone()->where('status', 'lunas')->count();
        $belumLunas = $query->clone()->where('status', 'belum_lunas')->count();
        $terlambat = $query->clone()->where('status', 'terlambat')->count();
        $totalNominal = $query->clone()->sum('jumlah_dibayar');
        
        $byJenis = $query->clone()
            ->selectRaw('jenis_pembayaran, count(*) as jumlah, sum(jumlah_dibayar) as total_nominal')
            ->groupBy('jenis_pembayaran')
            ->get();
        
        $byBulan = Pembayaran::selectRaw('YEAR(tanggal_pembayaran) as tahun, MONTH(tanggal_pembayaran) as bulan, count(*) as jumlah, sum(jumlah_dibayar) as total_nominal')
            ->whereNotNull('tanggal_pembayaran')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->take(12)
            ->get();
        
        $tunggakan = Pembayaran::where('status', '!=', 'lunas')
            ->where('tanggal_jatuh_tempo', '<', Carbon::now())
            ->count();
        
        $totalTunggakan = Pembayaran::where('status', '!=', 'lunas')
            ->where('tanggal_jatuh_tempo', '<', Carbon::now())
            ->sum('jumlah_tagihan');
        
        return response()->json([
            'total_pembayaran' => $totalPembayaran,
            'status' => [
                'lunas' => $lunas,
                'belum_lunas' => $belumLunas,
                'terlambat' => $terlambat,
                'persentase_lunas' => $totalPembayaran > 0 ? round(($lunas / $totalPembayaran) * 100, 2) : 0
            ],
            'nominal' => [
                'total' => $totalNominal,
                'rata_rata' => $totalPembayaran > 0 ? round($totalNominal / $totalPembayaran, 2) : 0
            ],
            'per_jenis' => $byJenis->map(function($item) {
                return [
                    'jenis' => $item->jenis_pembayaran,
                    'jumlah' => $item->jumlah,
                    'total_nominal' => $item->total_nominal
                ];
            }),
            'per_bulan' => $byBulan->map(function($item) {
                return [
                    'periode' => date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun)),
                    'jumlah' => $item->jumlah,
                    'total_nominal' => $item->total_nominal
                ];
            }),
            'tunggakan' => [
                'jumlah' => $tunggakan,
                'total_nominal' => $totalTunggakan,
                'rata_tunggakan' => $tunggakan > 0 ? round($totalTunggakan / $tunggakan, 2) : 0
            ]
        ]);
    }

    /**
     * Generate invoice PDF untuk pembayaran.
     */
    public function generateInvoice($id)
    {
        $pembayaran = Pembayaran::with(['mahasiswa.user', 'tahunAkademik'])->findOrFail($id);
        
        // TODO: Implementasi PDF generation menggunakan DomPDF
        // $pdf = PDF::loadView('invoice.pembayaran', compact('pembayaran'));
        
        return response()->json([
            'pesan' => 'Fitur generate invoice akan diimplementasikan.',
            'data_invoice' => [
                'no_invoice' => $pembayaran->no_pembayaran,
                'mahasiswa' => $pembayaran->mahasiswa->full_name ?? $pembayaran->mahasiswa->user->name,
                'nim' => $pembayaran->mahasiswa->nim,
                'jenis_pembayaran' => $pembayaran->jenis_pembayaran,
                'jumlah_tagihan' => $pembayaran->jumlah_tagihan,
                'tanggal_jatuh_tempo' => $pembayaran->tanggal_jatuh_tempo,
                'status' => $pembayaran->status
            ],
            'status' => 'pdf_belum_diimplementasi'
        ]);
    }

    /**
     * Import pembayaran dari Excel/CSV.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'tahun_akademik_id' => 'required|exists:tahun_akademiks,id',
            'jenis_pembayaran' => 'required|in:spp,ukt,daftar_ulang,praktikum,skripsi,lainnya'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        return response()->json([
            'pesan' => 'Fitur import pembayaran akan diimplementasikan.',
            'status' => 'import_belum_diimplementasi'
        ]);
    }

    // Helper methods
    
    private function updateStatusMahasiswa($mahasiswaId, $status)
    {
        $mahasiswa = Mahasiswa::find($mahasiswaId);
        if ($mahasiswa) {
            $mahasiswa->update(['status' => $status]);
        }
    }
    
    private function hitungKeterlambatan($pembayaran)
    {
        if ($pembayaran->status !== 'terlambat' || !$pembayaran->tanggal_jatuh_tempo) {
            return 0;
        }
        
        $jatuhTempo = Carbon::parse($pembayaran->tanggal_jatuh_tempo);
        $tanggalPembayaran = $pembayaran->tanggal_pembayaran ? Carbon::parse($pembayaran->tanggal_pembayaran) : Carbon::now();
        
        return max(0, $jatuhTempo->diffInDays($tanggalPembayaran, false));
    }
    
    private function hitungDendaKeterlambatan($pembayaran)
    {
        $hariTerlambat = $this->hitungHariTerlambat($pembayaran);
        
        if ($hariTerlambat <= 0) {
            return 0;
        }
        
        // Contoh: denda 1% per hari keterlambatan
        $dendaPerHari = $pembayaran->jumlah_tagihan * 0.01;
        return round($dendaPerHari * $hariTerlambat, 2);
    }
    
    private function hitungHariTerlambat($pembayaran)
    {
        if (!$pembayaran->tanggal_jatuh_tempo) {
            return 0;
        }
        
        $jatuhTempo = Carbon::parse($pembayaran->tanggal_jatuh_tempo);
        $sekarang = Carbon::now();
        
        if ($pembayaran->tanggal_pembayaran) {
            $tanggalPembayaran = Carbon::parse($pembayaran->tanggal_pembayaran);
            return max(0, $jatuhTempo->diffInDays($tanggalPembayaran, false));
        }
        
        return max(0, $jatuhTempo->diffInDays($sekarang, false));
    }
    
    private function rekomendasiPembayaran($mahasiswaId)
    {
        $pembayaranBelumLunas = Pembayaran::where('mahasiswa_id', $mahasiswaId)
            ->where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '>=', Carbon::now())
            ->orderBy('tanggal_jatuh_tempo')
            ->take(3)
            ->get();
        
        return $pembayaranBelumLunas->map(function($p) {
            return [
                'jenis' => $p->jenis_pembayaran,
                'jumlah' => $p->jumlah_tagihan,
                'jatuh_tempo' => $p->tanggal_jatuh_tempo,
                'prioritas' => 'tinggi'
            ];
        });
    }
}
