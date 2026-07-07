<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgramStudiController extends Controller
{
    /**
     * Menampilkan daftar program studi dengan statistik.
     */
    public function index(Request $request)
    {
        $query = ProgramStudi::with(['fakultas'])
            ->withCount(['mahasiswas', 'dosens', 'mataKuliahs']);
        
        // Filter pencarian
        if ($request->has('cari')) {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('name', 'like', "%{$cari}%")
                  ->orWhere('code', 'like', "%{$cari}%")
                  ->orWhere('jenjang', 'like', "%{$cari}%")
                  ->orWhereHas('fakultas', function($fakultasQuery) use ($cari) {
                      $fakultasQuery->where('name', 'like', "%{$cari}%");
                  });
            });
        }
        
        if ($request->has('fakultas_id')) {
            $query->where('faculty_id', $request->fakultas_id);
        }
        
        if ($request->has('jenjang')) {
            $query->where('jenjang', $request->jenjang);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Urutkan hasil
        $urutBy = $request->get('urut_by', 'name');
        $urutOrder = $request->get('urut_order', 'asc');
        $query->orderBy($urutBy, $urutOrder);
        
        // Paginasi
        $perPage = $request->get('per_page', 15);
        $programStudi = $query->paginate($perPage);
        
        return response()->json([
            'program_studi' => $programStudi,
            'ringkasan' => [
                'total_program' => $programStudi->total(),
                'total_mahasiswa' => Mahasiswa::count(),
                'total_dosen' => Dosen::count(),
                'fakultas_options' => Fakultas::all(['id', 'name']),
                'jenjang_options' => ['S1', 'S2', 'S3', 'D3', 'D4']
            ]
        ]);
    }

    /**
     * Menyimpan program studi baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:program_studis',
            'code' => 'required|string|max:20|unique:program_studis',
            'faculty_id' => 'required|exists:fakultas,id',
            'jenjang' => 'required|in:S1,S2,S3,D3,D4',
            'akreditasi' => 'nullable|in:A,B,C,Tidak Terakreditasi',
            'tahun_berdiri' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kepala_program' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        $programStudi = ProgramStudi::create($request->all());
        
        return response()->json([
            'pesan' => 'Program studi berhasil dibuat.',
            'program_studi' => $programStudi->load('fakultas'),
            'ringkasan' => [
                'id' => $programStudi->id,
                'nama' => $programStudi->name,
                'kode' => $programStudi->code,
                'jenjang' => $programStudi->jenjang,
                'fakultas' => $programStudi->fakultas->name,
                'mahasiswa' => 0,
                'dosen' => 0,
                'mata_kuliah' => 0
            ]
        ], 201);
    }

    /**
     * Menampilkan detail program studi.
     */
    public function show($id)
    {
        $programStudi = ProgramStudi::with(['fakultas'])
            ->withCount(['mahasiswas', 'dosens', 'mataKuliahs'])
            ->findOrFail($id);
        
        // Statistik mahasiswa
        $mahasiswaByAngkatan = Mahasiswa::where('study_program_id', $id)
            ->selectRaw('angkatan, count(*) as jumlah')
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'desc')
            ->get();
        
        $mahasiswaByStatus = Mahasiswa::where('study_program_id', $id)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->get()
            ->pluck('jumlah', 'status');
        
        // Dosen pengajar
        $dosenPengajar = Dosen::where('study_program_id', $id)
            ->with('user')
            ->get()
            ->map(function($dosen) {
                return [
                    'id' => $dosen->id,
                    'nama' => $dosen->full_name ?? $dosen->user->name,
                    'nip' => $dosen->nip,
                    'jabatan' => $dosen->position,
                    'pendidikan' => $dosen->education
                ];
            });
        
        // Mata kuliah
        $mataKuliahBySemester = \App\Models\MataKuliah::where('study_program_id', $id)
            ->selectRaw('semester, count(*) as jumlah')
            ->groupBy('semester')
            ->orderBy('semester')
            ->get()
            ->pluck('jumlah', 'semester');
        
        // Aktivitas terbaru
        $mahasiswaBaru = Mahasiswa::where('study_program_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($mahasiswa) {
                return [
                    'nama' => $mahasiswa->full_name ?? $mahasiswa->user->name,
                    'nim' => $mahasiswa->nim,
                    'angkatan' => $mahasiswa->angkatan,
                    'tanggal_daftar' => $mahasiswa->created_at->format('d M Y')
                ];
            });
        
        return response()->json([
            'program_studi' => $programStudi,
            'statistik' => [
                'mahasiswa' => [
                    'total' => $programStudi->mahasiswas_count,
                    'per_angkatan' => $mahasiswaByAngkatan,
                    'per_status' => $mahasiswaByStatus,
                    'rata_rata_ipk' => $this->hitungRataRataIPK($id)
                ],
                'dosen' => [
                    'total' => $programStudi->dosens_count,
                    'daftar' => $dosenPengajar,
                    'dengan_phd' => Dosen::where('study_program_id', $id)->where('education', 'like', '%Ph.D%')->count()
                ],
                'mata_kuliah' => [
                    'total' => $programStudi->mata_kuliahs_count,
                    'per_semester' => $mataKuliahBySemester,
                    'rata_rata_enrollment' => $this->hitungRataEnrollment($id)
                ]
            ],
            'informasi_kontak' => [
                'kepala_program' => $programStudi->kepala_program,
                'email' => $programStudi->email,
                'telepon' => $programStudi->phone,
                'website' => $programStudi->website,
                'akreditasi' => $programStudi->akreditasi,
                'tahun_berdiri' => $programStudi->tahun_berdiri
            ],
            'aktivitas_terbaru' => [
                'mahasiswa_baru' => $mahasiswaBaru,
                'dosen_baru' => Dosen::where('study_program_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get()
            ]
        ]);
    }

    /**
     * Mengupdate program studi.
     */
    public function update(Request $request, $id)
    {
        $programStudi = ProgramStudi::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:program_studis,name,' . $id,
            'code' => 'sometimes|string|max:20|unique:program_studis,code,' . $id,
            'faculty_id' => 'sometimes|exists:fakultas,id',
            'jenjang' => 'sometimes|in:S1,S2,S3,D3,D4',
            'akreditasi' => 'nullable|in:A,B,C,Tidak Terakreditasi',
            'tahun_berdiri' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kepala_program' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'status' => 'sometimes|in:active,inactive'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        $programStudi->update($request->all());
        
        return response()->json([
            'pesan' => 'Program studi berhasil diperbarui.',
            'program_studi' => $programStudi->fresh()->load('fakultas')
        ]);
    }

    /**
     * Menghapus program studi.
     */
    public function destroy($id)
    {
        $programStudi = ProgramStudi::findOrFail($id);
        
        // Cek apakah ada data terkait
        if ($programStudi->mahasiswas()->count() > 0) {
            return response()->json([
                'pesan' => 'Tidak dapat menghapus program studi yang memiliki mahasiswa. Silakan pindahkan atau hapus mahasiswa terlebih dahulu.'
            ], 400);
        }
        
        if ($programStudi->dosens()->count() > 0) {
            return response()->json([
                'pesan' => 'Tidak dapat menghapus program studi yang memiliki dosen. Silakan pindahkan atau hapus dosen terlebih dahulu.'
            ], 400);
        }
        
        if ($programStudi->mataKuliahs()->count() > 0) {
            return response()->json([
                'pesan' => 'Tidak dapat menghapus program studi yang memiliki mata kuliah. Silakan hapus atau pindahkan mata kuliah terlebih dahulu.'
            ], 400);
        }
        
        $programStudi->delete();
        
        return response()->json([
            'pesan' => 'Program studi berhasil dihapus.'
        ]);
    }

    /**
     * Mencari program studi untuk autocomplete.
     */
    public function cari(Request $request)
    {
        $query = $request->get('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        $results = ProgramStudi::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->with('fakultas')
            ->limit(10)
            ->get(['id', 'name', 'code', 'jenjang', 'faculty_id']);
        
        return response()->json($results->map(function($program) {
            return [
                'id' => $program->id,
                'nama' => $program->name,
                'kode' => $program->code,
                'jenjang' => $program->jenjang,
                'fakultas' => $program->fakultas->name ?? 'Tidak diketahui'
            ];
        }));
    }

    /**
     * Statistik program studi untuk dashboard.
     */
    public function statistik($id = null)
    {
        if ($id) {
            // Statistik program studi tertentu
            $programStudi = ProgramStudi::withCount(['mahasiswas', 'dosens', 'mataKuliahs'])->findOrFail($id);
            
            return response()->json([
                'program_studi' => $programStudi->name,
                'statistik' => [
                    'mahasiswa' => $programStudi->mahasiswas_count,
                    'dosen' => $programStudi->dosens_count,
                    'mata_kuliah' => $programStudi->mata_kuliahs_count,
                    'rata_ipk' => $this->hitungRataRataIPK($id),
                    'lulusan_terakhir' => $this->getLulusanTerakhir($id)
                ]
            ]);
        }
        
        // Ringkasan semua program studi
        $semuaProgram = ProgramStudi::withCount(['mahasiswas', 'dosens', 'mataKuliahs'])
            ->with('fakultas')
            ->get();
        
        $ringkasan = $semuaProgram->map(function($program) {
            return [
                'id' => $program->id,
                'nama' => $program->name,
                'kode' => $program->code,
                'fakultas' => $program->fakultas->name,
                'jenjang' => $program->jenjang,
                'mahasiswa' => $program->mahasiswas_count,
                'dosen' => $program->dosens_count,
                'mata_kuliah' => $program->mata_kuliahs_count,
                'rasio_mahasiswa_dosen' => $program->dosens_count > 0 ? round($program->mahasiswas_count / $program->dosens_count, 2) : 0
            ];
        });
        
        return response()->json([
            'ringkasan_program' => $ringkasan,
            'total_keseluruhan' => [
                'program' => $semuaProgram->count(),
                'mahasiswa' => $ringkasan->sum('mahasiswa'),
                'dosen' => $ringkasan->sum('dosen'),
                'mata_kuliah' => $ringkasan->sum('mata_kuliah')
            ],
            'program_terbesar' => [
                'mahasiswa' => $ringkasan->sortByDesc('mahasiswa')->take(3)->values(),
                'dosen' => $ringkasan->sortByDesc('dosen')->take(3)->values(),
                'mata_kuliah' => $ringkasan->sortByDesc('mata_kuliah')->take(3)->values()
            ]
        ]);
    }

    /**
     * Import program studi dari Excel/CSV.
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
        
        // TODO: Implementasi logika import
        return response()->json([
            'pesan' => 'Fitur import program studi akan diimplementasikan.',
            'status' => 'belum_diimplementasi'
        ]);
    }

    /**
     * Export data program studi.
     */
    public function export(Request $request)
    {
        $jenis = $request->get('jenis', 'excel');
        $programId = $request->get('program_id');
        
        if ($programId) {
            $program = ProgramStudi::findOrFail($programId);
            $data = $this->siapkanDataExport($program);
            $namaFile = "program_studi_{$program->code}_" . date('Ymd_His');
        } else {
            $data = $this->siapkanDataExportSemua();
            $namaFile = "semua_program_studi_" . date('Ymd_His');
        }
        
        return response()->json([
            'pesan' => 'Fitur export akan menghasilkan file ' . strtoupper($jenis) . '.',
            'nama_file' => $namaFile . '.' . $jenis,
            'preview_data' => $data,
            'status' => 'export_belum_diimplementasi'
        ]);
    }

    // Helper methods
    
    private function hitungRataRataIPK($programStudiId)
    {
        // Implementasi perhitungan rata-rata IPK
        $mahasiswa = Mahasiswa::where('study_program_id', $programStudiId)->get();
        
        if ($mahasiswa->isEmpty()) {
            return null;
        }
        
        $totalIPK = $mahasiswa->sum('ipk');
        return round($totalIPK / $mahasiswa->count(), 2);
    }
    
    private function hitungRataEnrollment($programStudiId)
    {
        $mataKuliah = \App\Models\MataKuliah::where('study_program_id', $programStudiId)->get();
        
        if ($mataKuliah->isEmpty()) {
            return 0;
        }
        
        return round($mataKuliah->avg('terisi'), 2);
    }
    
    private function getLulusanTerakhir($programStudiId)
    {
        // Implementasi mendapatkan data lulusan terakhir
        return null; // Placeholder
    }
    
    private function siapkanDataExport($program)
    {
        return [
            'informasi_program' => [
                'nama' => $program->name,
                'kode' => $program->code,
                'jenjang' => $program->jenjang,
                'akreditasi' => $program->akreditasi,
                'kepala_program' => $program->kepala_program,
                'tahun_berdiri' => $program->tahun_berdiri
            ],
            'statistik' => [
                'mahasiswa' => $program->mahasiswas()->count(),
                'dosen' => $program->dosens()->count(),
                'mata_kuliah' => $program->mataKuliahs()->count()
            ]
        ];
    }
    
    private function siapkanDataExportSemua()
    {
        $programs = ProgramStudi::withCount(['mahasiswas', 'dosens', 'mataKuliahs'])
            ->with('fakultas')
            ->get();
        
        return $programs->map(function($program) {
            return [
                'nama' => $program->name,
                'kode' => $program->code,
                'fakultas' => $program->fakultas->name,
                'jenjang' => $program->jenjang,
                'akreditasi' => $program->akreditasi,
                'mahasiswa' => $program->mahasiswas_count,
                'dosen' => $program->dosens_count,
                'mata_kuliah' => $program->mata_kuliahs_count
            ];
        });
    }
}
