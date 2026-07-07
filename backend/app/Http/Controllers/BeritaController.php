<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\KategoriBerita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BeritaController extends Controller
{
    /**
     * Menampilkan daftar berita dengan filter.
     */
    public function index(Request $request)
    {
        $query = Berita::with(['kategori', 'penulis', 'editor']);
        
        // Filter pencarian
        if ($request->has('cari')) {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('judul', 'like', "%{$cari}%")
                  ->orWhere('konten', 'like', "%{$cari}%")
                  ->orWhere('ringkasan', 'like', "%{$cari}%")
                  ->orWhereHas('kategori', function($kategoriQuery) use ($cari) {
                      $kategoriQuery->where('nama', 'like', "%{$cari}%");
                  })
                  ->orWhereHas('penulis', function($penulisQuery) use ($cari) {
                      $penulisQuery->where('name', 'like', "%{$cari}%");
                  });
            });
        }
        
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        if ($request->has('penulis_id')) {
            $query->where('penulis_id', $request->penulis_id);
        }
        
        if ($request->has('tanggal_mulai') && $request->has('tanggal_akhir')) {
            $query->whereBetween('tanggal_terbit', [$request->tanggal_mulai, $request->tanggal_akhir]);
        }
        
        // Default: hanya yang dipublikasikan
        if (!$request->has('show_all')) {
            $query->where('status', 'dipublikasikan')
                  ->where('tanggal_terbit', '<=', Carbon::now());
        }
        
        // Urutkan: berita utama dulu, lalu terbaru
        $query->orderByRaw("CASE 
            WHEN is_berita_utama = true THEN 1
            ELSE 2
        END")
        ->orderBy('tanggal_terbit', 'desc');
        
        // Paginasi
        $perPage = $request->get('per_page', 12);
        $berita = $query->paginate($perPage);
        
        return response()->json([
            'berita' => $berita,
            'statistik' => [
                'total' => $berita->total(),
                'dipublikasikan' => $berita->where('status', 'dipublikasikan')->count(),
                'berita_utama' => $berita->where('is_berita_utama', true)->count(),
                'paling_dilihat' => Berita::where('status', 'dipublikasikan')->orderBy('dilihat', 'desc')->take(3)->get(['id', 'judul', 'dilihat'])
            ],
            'filter' => [
                'kategori_options' => KategoriBerita::all(['id', 'nama']),
                'status_options' => ['draft', 'dipublikasikan', 'diarsipkan', 'menunggu_review'],
                'prioritas_options' => ['tinggi', 'sedang', 'rendah']
            ]
        ]);
    }

    /**
     * Menyimpan berita baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'ringkasan' => 'required|string|max:500',
            'konten' => 'required|string',
            'kategori_id' => 'required|exists:kategori_beritas,id',
            'penulis_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,dipublikasikan,diarsipkan,menunggu_review',
            'prioritas' => 'required|in:tinggi,sedang,rendah',
            'is_berita_utama' => 'boolean',
            'gambar_utama' => 'nullable|string|max:255',
            'gambar_thumbnail' => 'nullable|string|max:255',
            'tanggal_terbit' => 'nullable|date',
            'tags' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        // Generate slug dari judul
        $slug = str_slug($request->judul);
        $slugCount = Berita::where('slug', 'like', $slug . '%')->count();
        
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }
        
        $data = $request->all();
        $data['slug'] = $slug;
        
        // Set tanggal terbit default jika tidak diisi
        if (empty($data['tanggal_terbit']) && $data['status'] === 'dipublikasikan') {
            $data['tanggal_terbit'] = Carbon::now()->toDateString();
        }
        
        $berita = Berita::create($data);
        
        return response()->json([
            'pesan' => 'Berita berhasil dibuat.',
            'berita' => $berita->load(['kategori', 'penulis']),
            'informasi' => [
                'id' => $berita->id,
                'judul' => $berita->judul,
                'slug' => $berita->slug,
                'kategori' => $berita->kategori->nama,
                'status' => $berita->status,
                'prioritas' => $berita->prioritas,
                'is_berita_utama' => $berita->is_berita_utama,
                'tanggal_terbit' => $berita->tanggal_terbit
            ]
        ], 201);
    }

    /**
     * Menampilkan detail berita.
     */
    public function show($slug)
    {
        $berita = Berita::where('slug', $slug)
            ->orWhere('id', $slug)
            ->with(['kategori', 'penulis', 'editor', 'komentar.user'])
            ->firstOrFail();
        
        // Update jumlah dilihat
        $berita->increment('dilihat');
        
        // Berita terkait berdasarkan kategori
        $beritaTerkait = Berita::where('kategori_id', $berita->kategori_id)
            ->where('id', '!=', $berita->id)
            ->where('status', 'dipublikasikan')
            ->where('tanggal_terbit', '<=', Carbon::now())
            ->orderBy('tanggal_terbit', 'desc')
            ->take(4)
            ->get(['id', 'judul', 'slug', 'gambar_thumbnail', 'tanggal_terbit']);
        
        // Berita populer
        $beritaPopuler = Berita::where('status', 'dipublikasikan')
            ->where('tanggal_terbit', '>=', Carbon::now()->subDays(30))
            ->orderBy('dilihat', 'desc')
            ->take(5)
            ->get(['id', 'judul', 'slug', 'dilihat']);
        
        // Parse tags
        $tags = [];
        if (!empty($berita->tags)) {
            $tags = array_map('trim', explode(',', $berita->tags));
        }
        
        return response()->json([
            'berita' => $berita,
            'metadata' => [
                'dilihat' => $berita->dilihat,
                'dibagikan' => $berita->dibagikan,
                'komentar_count' => $berita->komentar->count(),
                'estimasi_baca' => $this->hitungEstimasiBaca($berita->konten),
                'tags' => $tags,
                'url_permanen' => url("/berita/{$berita->slug}")
            ],
            'penulis' => [
                'nama' => $berita->penulis->name,
                'bio' => $berita->penulis->bio ?? null,
                'avatar' => $berita->penulis->avatar ?? null,
                'total_berita' => Berita::where('penulis_id', $berita->penulis_id)->count()
            ],
            'berita_terkait' => $beritaTerkait,
            'berita_populer' => $beritaPopuler,
            'sosial_media' => [
                'share_url' => url("/berita/{$berita->slug}"),
                'share_title' => $berita->judul,
                'share_description' => $berita->ringkasan,
                'share_image' => $berita->gambar_utama
            ]
        ]);
    }

    /**
     * Mengupdate berita.
     */
    public function update(Request $request, $id)
    {
        $berita = Berita::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|string|max:255',
            'ringkasan' => 'sometimes|string|max:500',
            'konten' => 'sometimes|string',
            'kategori_id' => 'sometimes|exists:kategori_beritas,id',
            'status' => 'sometimes|in:draft,dipublikasikan,diarsipkan,menunggu_review',
            'prioritas' => 'sometimes|in:tinggi,sedang,rendah',
            'is_berita_utama' => 'boolean',
            'gambar_utama' => 'nullable|string|max:255',
            'gambar_thumbnail' => 'nullable|string|max:255',
            'tanggal_terbit' => 'nullable|date',
            'tags' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'editor_id' => 'nullable|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        // Update slug jika judul diubah
        if ($request->has('judul') && $request->judul !== $berita->judul) {
            $slug = str_slug($request->judul);
            $slugCount = Berita::where('slug', 'like', $slug . '%')->where('id', '!=', $berita->id)->count();
            
            if ($slugCount > 0) {
                $slug = $slug . '-' . ($slugCount + 1);
            }
            
            $request->merge(['slug' => $slug]);
        }
        
        $berita->update($request->all());
        
        return response()->json([
            'pesan' => 'Berita berhasil diperbarui.',
            'berita' => $berita->fresh()->load(['kategori', 'penulis', 'editor']),
            'perubahan' => [
                'judul_lama' => $berita->getOriginal('judul'),
                'status_lama' => $berita->getOriginal('status'),
                'editor' => $berita->editor->name ?? null,
                'tanggal_perbarui' => $berita->updated_at->format('d M Y H:i')
            ]
        ]);
    }

    /**
     * Menghapus berita.
     */
    public function destroy($id)
    {
        $berita = Berita::findOrFail($id);
        
        $berita->delete();
        
        return response()->json([
            'pesan' => 'Berita berhasil dihapus.',
            'berita_terhapus' => [
                'id' => $berita->id,
                'judul' => $berita->judul,
                'penulis' => $berita->penulis->name ?? null,
                'tanggal_terbit' => $berita->tanggal_terbit
            ]
        ]);
    }

    /**
     * Berita terbaru untuk homepage.
     */
    public function terbaru(Request $request)
    {
        $limit = $request->get('limit', 6);
        
        $query = Berita::with(['kategori', 'penulis'])
            ->where('status', 'dipublikasikan')
            ->where('tanggal_terbit', '<=', Carbon::now())
            ->orderBy('tanggal_terbit', 'desc');
        
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        if ($request->has('is_berita_utama')) {
            $query->where('is_berita_utama', $request->is_berita_utama);
        }
        
        $berita = $query->take($limit)->get();
        
        return response()->json([
            'berita_terbaru' => $berita->map(function($item) {
                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'slug' => $item->slug,
                    'ringkasan' => $item->ringkasan,
                    'gambar_thumbnail' => $item->gambar_thumbnail,
                    'kategori' => $item->kategori->nama,
                    'penulis' => $item->penulis->name,
                    'tanggal_terbit' => Carbon::parse($item->tanggal_terbit)->format('d M Y'),
                    'dilihat' => $item->dilihat,
                    'komentar_count' => $item->komentar_count
                ];
            }),
            'ringkasan' => [
                'total_dipublikasikan' => Berita::where('status', 'dipublikasikan')->count(),
                'berita_hari_ini' => Berita::where('status', 'dipublikasikan')
                    ->whereDate('tanggal_terbit', Carbon::today())
                    ->count(),
                'berita_bulan_ini' => Berita::where('status', 'dipublikasikan')
                    ->whereYear('tanggal_terbit', Carbon::now()->year)
                    ->whereMonth('tanggal_terbit', Carbon::now()->month)
                    ->count()
            ]
        ]);
    }

    /**
     * Statistik berita.
     */
    public function statistik(Request $request)
    {
        $query = Berita::query();
        
        if ($request->has('tahun')) {
            $query->whereYear('tanggal_terbit', $request->tahun);
        }
        
        $totalBerita = $query->count();
        $dipublikasikan = $query->clone()->where('status', 'dipublikasikan')->count();
        $draft = $query->clone()->where('status', 'draft')->count();
        $diarsipkan = $query->clone()->where('status', 'diarsipkan')->count();
        
        $byKategori = $query->clone()
            ->selectRaw('kategori_id, count(*) as jumlah')
            ->groupBy('kategori_id')
            ->with('kategori')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->kategori->nama ?? 'Tanpa Kategori' => $item->jumlah];
            });
        
        $byBulan = Berita::selectRaw('YEAR(tanggal_terbit) as tahun, MONTH(tanggal_terbit) as bulan, count(*) as jumlah')
            ->whereNotNull('tanggal_terbit')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->take(12)
            ->get();
        
        $byPenulis = $query->clone()
            ->selectRaw('penulis_id, count(*) as jumlah')
            ->groupBy('penulis_id')
            ->with('penulis')
            ->get()
            ->map(function($item) {
                return [
                    'penulis' => $item->penulis->name ?? 'Tidak diketahui',
                    'jumlah_berita' => $item->jumlah
                ];
            });
        
        $totalDilihat = $query->clone()->sum('dilihat');
        $totalDibagikan = $query->clone()->sum('dibagikan');
        $totalKomentar = $query->clone()->sum('komentar_count');
        
        $beritaTerpopuler = Berita::where('status', 'dipublikasikan')
            ->orderBy('dilihat', 'desc')
            ->take(5)
            ->get(['id', 'judul', 'dilihat', 'tanggal_terbit']);
        
        return response()->json([
            'total_berita' => $totalBerita,
            'status' => [
                'dipublikasikan' => $dipublikasikan,
                'draft' => $draft,
                'diarsipkan' => $diarsipkan,
                'persentase_dipublikasikan' => $totalBerita > 0 ? round(($dipublikasikan / $totalBerita) * 100, 2) : 0
            ],
            'per_kategori' => $byKategori,
            'per_bulan' => $byBulan->map(function($item) {
                return [
                    'periode' => date('M Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun)),
                    'jumlah' => $item->jumlah
                ];
            }),
            'per_penulis' => $byPenulis,
            'engagement' => [
                'total_dilihat' => $totalDilihat,
                'total_dibagikan' => $totalDibagikan,
                'total_komentar' => $totalKomentar,
                'rata_dilihat_per_berita' => $dipublikasikan > 0 ? round($totalDilihat / $dipublikasikan, 2) : 0
            ],
            'berita_terpopuler' => $beritaTerpopuler
        ]);
    }

    /**
     * Mencari berita untuk autocomplete.
     */
    public function cari(Request $request)
    {
        $query = $request->get('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        $results = Berita::where('judul', 'like', "%{$query}%")
            ->orWhere('ringkasan', 'like', "%{$query}%")
            ->where('status', 'dipublikasikan')
            ->where('tanggal_terbit', '<=', Carbon::now())
            ->with('kategori')
            ->limit(8)
            ->get(['id', 'judul', 'slug', 'ringkasan', 'gambar_thumbnail', 'kategori_id', 'tanggal_terbit']);
        
        return response()->json($results->map(function($berita) {
            return [
                'id' => $berita->id,
                'judul' => $berita->judul,
                'slug' => $berita->slug,
                'ringkasan' => substr($berita->ringkasan, 0, 100) . '...',
                'kategori' => $berita->kategori->nama ?? null,
                'tanggal' => Carbon::parse($berita->tanggal_terbit)->format('d M Y')
            ];
        }));
    }

    /**
     * Export berita ke PDF/Excel.
     */
    public function export(Request $request)
    {
        $jenis = $request->get('jenis', 'pdf');
        $data = $this->siapkanDataExport($request);
        $namaFile = "berita_" . date('Ymd_His');
        
        return response()->json([
            'pesan' => 'Fitur export berita akan diimplementasikan.',
            'nama_file' => $namaFile . '.' . $jenis,
            'preview_data' => $data,
            'status' => 'export_belum_diimplementasi'
        ]);
    }

    /**
     * Import berita dari Excel/CSV.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'kategori_id' => 'required|exists:kategori_beritas,id',
            'penulis_id' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        return response()->json([
            'pesan' => 'Fitur import berita akan diimplementasikan.',
            'status' => 'import_belum_diimplementasi'
        ]);
    }

    // Helper methods
    
    private function hitungEstimasiBaca($konten)
    {
        // Rata-rata kecepatan baca: 200-250 kata per menit
        $jumlahKata = str_word_count(strip_tags($konten));
        $menit = ceil($jumlahKata / 200);
        
        if ($menit < 1) {
            return 'Kurang dari 1 menit';
        } elseif ($menit == 1) {
            return '1 menit';
        } else {
            return "{$menit} menit";
        }
    }
    
    private function siapkanDataExport($request)
    {
        $query = Berita::with(['kategori', 'penulis']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        if ($request->has('tanggal_mulai') && $request->has('tanggal_akhir')) {
            $query->whereBetween('tanggal_terbit', [$request->tanggal_mulai, $request->tanggal_akhir]);
        }
        
        $berita = $query->get();
        
        return $berita->map(function($item) {
            return [
                'judul' => $item->judul,
                'kategori' => $item->kategori->nama ?? null,
                'penulis' => $item->penulis->name ?? null,
                'status' => $item->status,
                'prioritas' => $item->prioritas,
                'is_berita_utama' => $item->is_berita_utama,
                'tanggal_terbit' => $item->tanggal_terbit,
                'dilihat' => $item->dilihat,
                'dibagikan' => $item->dibagikan,
                'komentar_count' => $item->komentar_count,
                'ringkasan' => substr(strip_tags($item->ringkasan), 0, 200)
            ];
        });
    }
}
