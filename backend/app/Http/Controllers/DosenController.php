<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DosenController extends Controller
{
    /**
     * Menampilkan daftar dosen dengan filter dan paginasi.
     */
    public function index(Request $request)
    {
        $query = Dosen::with(['user', 'fakultas', 'programStudi']);
        
        // Filter pencarian
        if ($request->has('cari')) {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('full_name', 'like', "%{$cari}%")
                  ->orWhere('nip', 'like', "%{$cari}%")
                  ->orWhere('position', 'like', "%{$cari}%")
                  ->orWhere('education', 'like', "%{$cari}%")
                  ->orWhereHas('user', function($userQuery) use ($cari) {
                      $userQuery->where('name', 'like', "%{$cari}%");
                  })
                  ->orWhereHas('fakultas', function($fakultasQuery) use ($cari) {
                      $fakultasQuery->where('name', 'like', "%{$cari}%");
                  });
            });
        }
        
        if ($request->has('fakultas_id')) {
            $query->where('faculty_id', $request->fakultas_id);
        }
        
        if ($request->has('program_studi_id')) {
            $query->where('study_program_id', $request->program_studi_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }
        
        // Urutkan hasil
        $urutBy = $request->get('urut_by', 'created_at');
        $urutOrder = $request->get('urut_order', 'desc');
        $query->orderBy($urutBy, $urutOrder);
        
        // Paginasi
        $perPage = $request->get('per_page', 15);
        $dosen = $query->paginate($perPage);
        
        // Tambahkan statistik
        $dosen->getCollection()->transform(function($dosenItem) {
            $dosenItem->total_mahasiswa = $dosenItem->mahasiswaWali()->count();
            $dosenItem->total_mata_kuliah = $dosenItem->mataKuliahs()->count();
            return $dosenItem;
        });
        
        return response()->json([
            'dosen' => $dosen,
            'filter' => [
                'fakultas_options' => Fakultas::all(['id', 'name']),
                'program_studi_options' => ProgramStudi::all(['id', 'name']),
                'position_options' => ['Guru Besar', 'Lektor Kepala', 'Lektor', 'Asisten Ahli', 'Tenaga Pengajar'],
                'status_options' => ['aktif', 'pensiun', 'cuti', 'meninggal']
            ]
        ]);
    }

    /**
     * Menyimpan dosen baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'nip' => 'required|string|max:20|unique:dosens',
            'full_name' => 'nullable|string|max:255',
            'position' => 'required|string|max:100',
            'education' => 'required|string|max:255',
            'faculty_id' => 'required|exists:fakultas,id',
            'study_program_id' => 'required|exists:program_studis,id',
            'bidang_keahlian' => 'required|string|max:255',
            'email_kontak' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:20',
            'gelar_depan' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:20',
            'foto' => 'nullable|string|max:255',
            'status' => 'required|in:aktif,pensiun,cuti,meninggal'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        // Cek NIP sudah terdaftar
        if (Dosen::where('nip', $request->nip)->exists()) {
            return response()->json([
                'pesan' => 'NIP sudah terdaftar.'
            ], 400);
        }
        
        // Buat user jika tidak ada user_id
        if (!$request->has('user_id')) {
            $user = User::create([
                'name' => $request->full_name ?? 'Dosen ' . $request->nip,
                'email' => $request->email_kontak ?? $request->nip . '@kampus.ac.id',
                'password' => bcrypt($request->nip), // Default password = NIP
                'role' => 'dosen'
            ]);
            $request->merge(['user_id' => $user->id]);
        }
        
        $dosen = Dosen::create($request->all());
        
        return response()->json([
            'pesan' => 'Dosen berhasil ditambahkan.',
            'dosen' => $dosen->load(['user', 'fakultas', 'programStudi']),
            'akses_login' => [
                'email' => $dosen->user->email,
                'password' => $request->nip, // Default password = NIP
                'peran' => 'dosen'
            ]
        ], 201);
    }

    /**
     * Menampilkan detail dosen.
     */
    public function show($id)
    {
        $dosen = Dosen::with(['user', 'fakultas', 'programStudi', 'mahasiswaWali.user', 'mataKuliahs'])
            ->findOrFail($id);
        
        // Statistik mengajar
        $statistikMengajar = [
            'total_mata_kuliah' => $dosen->mataKuliahs()->count(),
            'total_mahasiswa' => $dosen->mahasiswaWali()->count(),
            'mata_kuliah_aktif' => $dosen->mataKuliahs()->whereHas('krsMataKuliahs')->count(),
            'jadwal_mengajar' => $dosen->getScheduleSummary()
        ];
        
        // Riwayat pendidikan (dari education field)
        $riwayatPendidikan = $this->parseEducationHistory($dosen->education);
        
        return response()->json([
            'dosen' => $dosen,
            'statistik' => $statistikMengajar,
            'riwayat_pendidikan' => $riwayatPendidikan,
            'informasi_kontak' => [
                'email' => $dosen->email_kontak,
                'telepon' => $dosen->no_hp,
                'gelar_lengkap' => $dosen->getFullTitle()
            ]
        ]);
    }

    /**
     * Mengupdate data dosen.
     */
    public function update(Request $request, $id)
    {
        $dosen = Dosen::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:100',
            'education' => 'sometimes|string|max:255',
            'faculty_id' => 'sometimes|exists:fakultas,id',
            'study_program_id' => 'sometimes|exists:program_studis,id',
            'bidang_keahlian' => 'sometimes|string|max:255',
            'email_kontak' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:20',
            'gelar_depan' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:20',
            'foto' => 'nullable|string|max:255',
            'status' => 'sometimes|in:aktif,pensiun,cuti,meninggal'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'kesalahan' => $validator->errors()
            ], 422);
        }
        
        $dosen->update($request->all());
        
        // Update user name jika full_name diubah
        if ($request->has('full_name') && $dosen->user) {
            $dosen->user->update(['name' => $request->full_name]);
        }
        
        return response()->json([
            'pesan' => 'Data dosen berhasil diperbarui.',
            'dosen' => $dosen->fresh()->load(['user', 'fakultas', 'programStudi'])
        ]);
    }

    /**
     * Menghapus dosen.
     */
    public function destroy($id)
    {
        $dosen = Dosen::findOrFail($id);
        
        // Cek apakah dosen memiliki data terkait
        if ($dosen->mataKuliahs()->count() > 0) {
            return response()->json([
                'pesan' => 'Tidak dapat menghapus dosen yang mengampu mata kuliah. Silakan pindahkan atau hapus mata kuliah terlebih dahulu.'
            ], 400);
        }
        
        if ($dosen->mahasiswaWali()->count() > 0) {
            return response()->json([
                'pesan' => 'Tidak dapat menghapus dosen yang menjadi wali mahasiswa. Silakan pindahkan mahasiswa wali terlebih dahulu.'
            ], 400);
        }
        
        // Hapus user terkait (opsional)
        if ($dosen->user) {
            $dosen->user->delete();
        }
        
        $dosen->delete();
        
        return response()->json([
            'pesan' => 'Dosen berhasil dihapus.'
        ]);
    }

    /**
     * Mencari dosen untuk autocomplete.
     */
    public function cari(Request $request)
    {
        $query = $request->get('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        $results = Dosen::where('full_name', 'like', "%{$query}%")
            ->orWhere('nip', 'like', "%{$query}%")
            ->with(['fakultas', 'programStudi'])
            ->limit(10)
            ->get(['id', 'nip', 'full_name', 'position', 'faculty_id', 'study_program_id']);
        
        return response()->json($results->map(function($dosen) {
            return [
                'id' => $dosen->id,
                'nip' => $dosen->nip,
                'nama' => $dosen->full_name,
                'jabatan' => $dosen->position,
                'fakultas' => $dosen->fakultas->name ?? null,
                'program_studi' => $dosen->programStudi->name ?? null
            ];
        }));
    }

    /**
     * Statistik dosen untuk dashboard.
     */
    public function statistik(Request $request)
    {
        $query = Dosen::query();
        
        if ($request->has('fakultas_id')) {
            $query->where('faculty_id', $request->fakultas_id);
        }
        
        if ($request->has('program_studi_id')) {
            $query->where('study_program_id', $request->program_studi_id);
        }
        
        $totalDosen = $query->count();
        $dosenAktif = $query->clone()->where('status', 'aktif')->count();
        $dosenPensiun = $query->clone()->where('status', 'pensiun')->count();
        
        $byPosition = $query->clone()
            ->selectRaw('position, count(*) as jumlah')
            ->groupBy('position')
            ->get()
            ->pluck('jumlah', 'position');
        
        $byEducation = $query->clone()
            ->selectRaw("CASE 
                WHEN education LIKE '%Ph.D%' OR education LIKE '%Doktor%' THEN 'Doktor'
                WHEN education LIKE '%Magister%' OR education LIKE '%S2%' THEN 'Magister'
                WHEN education LIKE '%Sarjana%' OR education LIKE '%S1%' THEN 'Sarjana'
                ELSE 'Lainnya'
            END as tingkat, count(*) as jumlah")
            ->groupBy('tingkat')
            ->get()
            ->pluck('jumlah', 'tingkat');
        
        return response()->json([
            'total_dosen' => $totalDosen,
            'status' => [
                'aktif' => $dosenAktif,
                'pensiun' => $dosenPensiun,
                'cuti' => $query->clone()->where('status', 'cuti')->count(),
                'meninggal' => $query->clone()->where('status', 'meninggal')->count()
            ],
            'per_jabatan' => $byPosition,
            'per_pendidikan' => $byEducation,
            'rata_pengalaman' => round($query->clone()->avg('years_of_experience') ?? 0, 1)
        ]);
    }

    /**
     * Dosen yang bisa menjadi wali mahasiswa.
     */
    public function calonWaliMahasiswa(Request $request)
    {
        $dosen = Dosen::where('status', 'aktif')
            ->with(['fakultas', 'programStudi'])
            ->select(['id', 'nip', 'full_name', 'position', 'faculty_id', 'study_program_id'])
            ->orderBy('full_name')
            ->get();
        
        return response()->json([
            'calon_wali' => $dosen->map(function($dosen) {
                return [
                    'id' => $dosen->id,
                    'nip' => $dosen->nip,
                    'nama' => $dosen->full_name,
                    'jabatan' => $dosen->position,
                    'fakultas' => $dosen->fakultas->name ?? null,
                    'program_studi' => $dosen->programStudi->name ?? null,
                    'jumlah_mahasiswa_wali' => $dosen->mahasiswaWali()->count()
                ];
            })
        ]);
    }

    /**
     * Import dosen dari Excel/CSV.
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
            'pesan' => 'Fitur import dosen akan diimplementasikan.',
            'status' => 'belum_diimplementasi'
        ]);
    }

    /**
     * Export data dosen.
     */
    public function export(Request $request)
    {
        $jenis = $request->get('jenis', 'excel');
        $data = $this->siapkanDataExport($request);
        $namaFile = "data_dosen_" . date('Ymd_His');
        
        return response()->json([
            'pesan' => 'Fitur export akan menghasilkan file ' . strtoupper($jenis) . '.',
            'nama_file' => $namaFile . '.' . $jenis,
            'preview_data' => $data,
            'status' => 'export_belum_diimplementasi'
        ]);
    }

    // Helper methods
    
    private function parseEducationHistory($education)
    {
        // Parsing string pendidikan menjadi array riwayat
        $riwayat = [];
        
        if (strpos($education, 'Ph.D') !== false || strpos($education, 'Doktor') !== false) {
            $riwayat[] = ['tingkat' => 'Doktor', 'institusi' => 'Universitas ...'];
        }
        
        if (strpos($education, 'Magister') !== false || strpos($education, 'S2') !== false) {
            $riwayat[] = ['tingkat' => 'Magister', 'institusi' => 'Universitas ...'];
        }
        
        if (strpos($education, 'Sarjana') !== false || strpos($education, 'S1') !== false) {
            $riwayat[] = ['tingkat' => 'Sarjana', 'institusi' => 'Universitas ...'];
        }
        
        return $riwayat;
    }
    
    private function siapkanDataExport($request)
    {
        $query = Dosen::with(['fakultas', 'programStudi', 'user']);
        
        if ($request->has('fakultas_id')) {
            $query->where('faculty_id', $request->fakultas_id);
        }
        
        $dosen = $query->get();
        
        return $dosen->map(function($dosen) {
            return [
                'nip' => $dosen->nip,
                'nama_lengkap' => $dosen->full_name,
                'jabatan' => $dosen->position,
                'pendidikan' => $dosen->education,
                'fakultas' => $dosen->fakultas->name ?? null,
                'program_studi' => $dosen->programStudi->name ?? null,
                'bidang_keahlian' => $dosen->bidang_keahlian,
                'email' => $dosen->email_kontak,
                'telepon' => $dosen->no_hp,
                'status' => $dosen->status
            ];
        });
    }
}
