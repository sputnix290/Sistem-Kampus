<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use Barryvdh\DomPDF\Facade\Pdf;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of students with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = Mahasiswa::with(['user', 'faculty', 'studyProgram']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }
        
        if ($request->has('study_program_id')) {
            $query->where('study_program_id', $request->study_program_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('angkatan')) {
            $query->where('angkatan', $request->angkatan);
        }
        
        // Sort results
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('per_page', 20);
        $students = $query->paginate($perPage);
        
        // Get filter options for frontend
        $faculties = Faculty::all();
        $studyPrograms = StudyProgram::all();
        $angkatanOptions = Mahasiswa::distinct('angkatan')->pluck('angkatan');
        
        return response()->json([
            'mahasiswa' => $students,
            'filter' => [
                'fakultas_options' => \App\Models\Fakultas::all(['id', 'name']),
                'program_studi_options' => \App\Models\ProgramStudi::all(['id', 'name']),
                'status_options' => ['aktif', 'tidak aktif', 'lulus', 'keluar']
            ]
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => 'required|string|unique:mahasiswas,nim',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'faculty_id' => 'required|exists:faculties,id',
            'study_program_id' => 'required|exists:study_programs,id',
            'angkatan' => 'required|integer',
            'semester_aktif' => 'integer|min:1|max:8',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Create user account
        $user = User::create([
            'name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'phone' => $request->phone,
        ]);
        
        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('student-photos', 'public');
        }
        
        // Create student record
        $student = Mahasiswa::create([
            'user_id' => $user->id,
            'nim' => $request->nim,
            'full_name' => $request->full_name,
            'faculty_id' => $request->faculty_id,
            'study_program_id' => $request->study_program_id,
            'angkatan' => $request->angkatan,
            'semester_aktif' => $request->semester_aktif ?? 1,
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'gender' => $request->gender,
            'address' => $request->address,
            'photo' => $photoPath,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone,
            'blood_type' => $request->blood_type,
            'religion' => $request->religion,
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'parent_occupation' => $request->parent_occupation,
            'parent_address' => $request->parent_address,
            'parent_phone' => $request->parent_phone,
            'status' => 'aktif',
        ]);
        
        // Create student KTM ID
        $student->update([
            'ktm_id' => 'KTM-' . str_pad($student->id, 6, '0', STR_PAD_LEFT) . '-' . substr($request->nim, -4)
        ]);
        
        return response()->json([
            'pesan' => 'Mahasiswa berhasil ditambahkan.',
            'mahasiswa' => $student->load(['user', 'faculty', 'studyProgram']),
            'kartu_mahasiswa' => [
                'ktm_virtual_url' => $student->virtual_ktm_url,
                'unduh_url' => $student->download_ktm_url
            ]
        ], 201);
    }

    /**
     * Display the specified student.
     */
    public function show($id)
    {
        $student = Mahasiswa::with(['user', 'faculty', 'studyProgram'])->findOrFail($id);
        
        return response()->json([
            'student' => $student,
            'enrollments' => $student->enrollments()->with('course')->get(),
            'grades' => $student->grades()->with('course')->get(),
            'attendances' => $student->attendances()->latest()->limit(10)->get(),
            'payments' => $student->payments()->latest()->limit(10)->get()
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, $id)
    {
        $student = Mahasiswa::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nim' => 'sometimes|string|unique:mahasiswas,nim,' . $id,
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $student->user_id,
            'faculty_id' => 'sometimes|exists:faculties,id',
            'study_program_id' => 'sometimes|exists:study_programs,id',
            'angkatan' => 'sometimes|integer',
            'semester_aktif' => 'sometimes|integer|min:1|max:8',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'status' => 'sometimes|in:aktif,cuti,lulus,keluar',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Update user account if email changed
        if ($request->has('email')) {
            $student->user->update([
                'email' => $request->email,
                'name' => $request->full_name ?? $student->user->name,
                'phone' => $request->phone ?? $student->user->phone
            ]);
        }
        
        // Handle photo upload
        $photoPath = $student->photo;
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }
            $photoPath = $request->file('photo')->store('student-photos', 'public');
        }
        
        // Update student record
        $student->update([
            'nim' => $request->nim ?? $student->nim,
            'full_name' => $request->full_name ?? $student->full_name,
            'faculty_id' => $request->faculty_id ?? $student->faculty_id,
            'study_program_id' => $request->study_program_id ?? $student->study_program_id,
            'angkatan' => $request->angkatan ?? $student->angkatan,
            'semester_aktif' => $request->semester_aktif ?? $student->semester_aktif,
            'birth_date' => $request->birth_date ?? $student->birth_date,
            'birth_place' => $request->birth_place ?? $student->birth_place,
            'gender' => $request->gender ?? $student->gender,
            'address' => $request->address ?? $student->address,
            'photo' => $photoPath,
            'emergency_contact' => $request->emergency_contact ?? $student->emergency_contact,
            'emergency_phone' => $request->emergency_phone ?? $student->emergency_phone,
            'blood_type' => $request->blood_type ?? $student->blood_type,
            'religion' => $request->religion ?? $student->religion,
            'father_name' => $request->father_name ?? $student->father_name,
            'mother_name' => $request->mother_name ?? $student->mother_name,
            'parent_occupation' => $request->parent_occupation ?? $student->parent_occupation,
            'parent_address' => $request->parent_address ?? $student->parent_address,
            'parent_phone' => $request->parent_phone ?? $student->parent_phone,
            'status' => $request->status ?? $student->status,
            'ukt_amount' => $request->ukt_amount ?? $student->ukt_amount,
            'ukt_status' => $request->ukt_status ?? $student->ukt_status,
        ]);
        
        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $student->load(['user', 'faculty', 'studyProgram'])
        ]);
    }

    /**
     * Remove the specified student.
     */
    public function destroy($id)
    {
        $student = Mahasiswa::findOrFail($id);
        
        // Delete photo if exists
        if ($student->photo && Storage::disk('public')->exists($student->photo)) {
            Storage::disk('public')->delete($student->photo);
        }
        
        // Delete associated user
        $student->user->delete();
        
        // Delete student record
        $student->delete();
        
        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Import students from Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);
        
        try {
            Excel::import(new StudentsImport, $request->file('file'));
            
            return response()->json([
                'message' => 'Students imported successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error importing students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students to Excel.
     */
    public function exportExcel(Request $request)
    {
        $type = $request->get('type', 'all');
        $filename = 'students_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(new StudentsExport($type), $filename);
    }

    /**
     * Export student to PDF (KTM).
     */
    public function exportKTM($id)
    {
        $student = Mahasiswa::with(['user', 'faculty', 'studyProgram'])->findOrFail($id);
        
        $pdf = Pdf::loadView('exports.student_ktm', [
            'student' => $student
        ]);
        
        return $pdf->download('ktm_' . $student->nim . '.pdf');
    }

    /**
     * Get student statistics.
     */
    public function statistics()
    {
        $total = Mahasiswa::count();
        $active = Mahasiswa::where('status', 'aktif')->count();
        $graduated = Mahasiswa::where('status', 'lulus')->count();
        $byFaculty = Mahasiswa::selectRaw('faculties.name as faculty, count(*) as count')
            ->join('faculties', 'mahasiswas.faculty_id', '=', 'faculties.id')
            ->groupBy('faculties.id', 'faculties.name')
            ->get();
        $byAngkatan = Mahasiswa::selectRaw('angkatan, count(*) as count')
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'desc')
            ->get();
        
        return response()->json([
            'total_students' => $total,
            'active_students' => $active,
            'graduated_students' => $graduated,
            'by_faculty' => $byFaculty,
            'by_angkatan' => $byAngkatan
        ]);
    }
}
