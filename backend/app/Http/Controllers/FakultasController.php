<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FakultasController extends Controller
{
    /**
     * Display all faculties with statistics.
     */
    public function index(Request $request)
    {
        $query = Fakultas::withCount(['mahasiswas', 'dosens', 'programStudis']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('dean', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('has_programs')) {
            $query->has('programStudis');
        }
        
        // Sort results
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('per_page', 15);
        $faculties = $query->paginate($perPage);
        
        // Calculate additional statistics
        $faculties->getCollection()->transform(function($faculty) {
            $faculty->total_courses = MataKuliah::whereHas('programStudi', function($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id);
            })->count();
            
            return $faculty;
        });
        
        return response()->json([
            'faculties' => $faculties,
            'summary' => [
                'total_faculties' => $faculties->total(),
                'total_students' => Mahasiswa::count(),
                'total_lecturers' => Dosen::count(),
                'total_programs' => ProgramStudi::count()
            ]
        ]);
    }

    /**
     * Store a newly created faculty.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:fakultas',
            'code' => 'required|string|max:10|unique:fakultas',
            'dean' => 'required|string|max:255',
            'vice_dean' => 'nullable|string|max:255',
            'establishment_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'logo' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        $faculty = Fakultas::create($request->all());
        
        return response()->json([
            'message' => 'Faculty created successfully.',
            'faculty' => $faculty,
            'faculty_summary' => [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'code' => $faculty->code,
                'dean' => $faculty->dean,
                'programs_count' => 0,
                'students_count' => 0,
                'lecturers_count' => 0
            ]
        ], 201);
    }

    /**
     * Display the specified faculty with detailed statistics.
     */
    public function show($id)
    {
        $faculty = Fakultas::withCount(['mahasiswas', 'dosens', 'programStudis'])->findOrFail($id);
        
        // Get study programs with counts
        $programs = ProgramStudi::where('faculty_id', $id)
            ->withCount(['mahasiswas', 'dosens', 'mataKuliahs'])
            ->get();
        
        // Get students by year
        $studentsByYear = Mahasiswa::where('faculty_id', $id)
            ->selectRaw('angkatan, count(*) as count')
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'desc')
            ->get();
        
        // Get lecturers by education level
        $lecturersByEducation = Dosen::where('faculty_id', $id)
            ->selectRaw('education, count(*) as count')
            ->groupBy('education')
            ->get()
            ->pluck('count', 'education');
        
        // Get course statistics
        $courses = MataKuliah::whereHas('programStudi', function($query) use ($id) {
                $query->where('faculty_id', $id);
            })
            ->selectRaw('semester, count(*) as count')
            ->groupBy('semester')
            ->orderBy('semester')
            ->get()
            ->pluck('count', 'semester');
        
        // Recent activities
        $recentStudents = Mahasiswa::where('faculty_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->full_name ?? $student->user->name,
                    'nim' => $student->nim,
                    'angkatan' => $student->angkatan,
                    'semester' => $student->semester_aktif
                ];
            });
        
        $recentLecturers = Dosen::where('faculty_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($lecturer) {
                return [
                    'id' => $lecturer->id,
                    'name' => $lecturer->full_name ?? $lecturer->user->name,
                    'nip' => $lecturer->nip,
                    'position' => $lecturer->position
                ];
            });
        
        return response()->json([
            'faculty' => $faculty,
            'statistics' => [
                'students' => [
                    'total' => $faculty->mahasiswas_count,
                    'by_year' => $studentsByYear,
                    'average_gpa' => $this->calculateFacultyAverageGPA($id),
                    'graduation_rate' => $this->calculateFacultyGraduationRate($id)
                ],
                'lecturers' => [
                    'total' => $faculty->dosens_count,
                    'by_education' => $lecturersByEducation,
                    'with_phd' => Dosen::where('faculty_id', $id)->where('education', 'like', '%Ph.D%')->count(),
                    'average_experience' => round(Dosen::where('faculty_id', $id)->avg('years_of_experience') ?? 0, 1)
                ],
                'programs' => [
                    'total' => $faculty->program_studis_count,
                    'list' => $programs->map(function($program) {
                        return [
                            'id' => $program->id,
                            'name' => $program->name,
                            'code' => $program->code,
                            'students' => $program->mahasiswas_count,
                            'lecturers' => $program->dosens_count,
                            'courses' => $program->mata_kuliahs_count
                        ];
                    })
                ],
                'courses' => [
                    'total' => array_sum($courses->toArray()),
                    'by_semester' => $courses,
                    'average_enrollment' => $this->calculateFacultyAverageEnrollment($id)
                ]
            ],
            'recent_activities' => [
                'students' => $recentStudents,
                'lecturers' => $recentLecturers,
                'programs_added' => ProgramStudi::where('faculty_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get()
            ],
            'contact_info' => [
                'dean' => $faculty->dean,
                'vice_dean' => $faculty->vice_dean,
                'email' => $faculty->email,
                'phone' => $faculty->phone,
                'website' => $faculty->website,
                'address' => $faculty->address
            ]
        ]);
    }

    /**
     * Update the specified faculty.
     */
    public function update(Request $request, $id)
    {
        $faculty = Fakultas::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:fakultas,name,' . $id,
            'code' => 'sometimes|string|max:10|unique:fakultas,code,' . $id,
            'dean' => 'sometimes|string|max:255',
            'vice_dean' => 'nullable|string|max:255',
            'establishment_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'logo' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Update faculty
        $faculty->update($request->all());
        
        return response()->json([
            'message' => 'Faculty updated successfully.',
            'faculty' => $faculty->fresh()
        ]);
    }

    /**
     * Remove the specified faculty.
     */
    public function destroy($id)
    {
        $faculty = Fakultas::findOrFail($id);
        
        // Check if faculty has related data
        if ($faculty->mahasiswas()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete faculty that has students. Please reassign or delete students first.'
            ], 400);
        }
        
        if ($faculty->dosens()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete faculty that has lecturers. Please reassign or delete lecturers first.'
            ], 400);
        }
        
        if ($faculty->programStudis()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete faculty that has study programs. Please delete or reassign study programs first.'
            ], 400);
        }
        
        $faculty->delete();
        
        return response()->json([
            'message' => 'Faculty deleted successfully.'
        ]);
    }

    /**
     * Search faculties with autocomplete.
     */
    public function search(Request $request)
    {
        $query = $request->get('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        $faculties = Fakultas::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'code', 'dean']);
        
        return response()->json($faculties);
    }

    /**
     * Get faculty statistics for dashboard widgets.
     */
    public function statistics($id = null)
    {
        if ($id) {
            // Single faculty statistics
            $faculty = Fakultas::findOrFail($id);
            
            return response()->json([
                'faculty' => $faculty->name,
                'stats' => [
                    'students' => $faculty->mahasiswas()->count(),
                    'lecturers' => $faculty->dosens()->count(),
                    'programs' => $faculty->programStudis()->count(),
                    'courses' => MataKuliah::whereHas('programStudi', function($query) use ($id) {
                        $query->where('faculty_id', $id);
                    })->count(),
                    'average_gpa' => $this->calculateFacultyAverageGPA($id),
                    'attendance_rate' => $this->calculateFacultyAttendanceRate($id)
                ]
            ]);
        }
        
        // All faculties summary
        $faculties = Fakultas::withCount(['mahasiswas', 'dosens', 'programStudis'])->get();
        
        $summary = $faculties->map(function($faculty) {
            $courses = MataKuliah::whereHas('programStudi', function($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id);
            })->count();
            
            return [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'code' => $faculty->code,
                'students' => $faculty->mahasiswas_count,
                'lecturers' => $faculty->dosens_count,
                'programs' => $faculty->program_studis_count,
                'courses' => $courses,
                'student_ratio' => $faculty->dosens_count > 0 ? round($faculty->mahasiswas_count / $faculty->dosens_count, 2) : 0
            ];
        });
        
        return response()->json([
            'faculties_summary' => $summary,
            'overall_totals' => [
                'faculties' => $faculties->count(),
                'students' => $summary->sum('students'),
                'lecturers' => $summary->sum('lecturers'),
                'programs' => $summary->sum('programs'),
                'courses' => $summary->sum('courses')
            ],
            'top_faculties' => [
                'by_students' => $summary->sortByDesc('students')->take(3)->values(),
                'by_lecturers' => $summary->sortByDesc('lecturers')->take(3)->values(),
                'by_programs' => $summary->sortByDesc('programs')->take(3)->values()
            ]
        ]);
    }

    /**
     * Export faculty data to Excel/PDF.
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'excel');
        $facultyId = $request->get('faculty_id');
        
        if ($facultyId) {
            $faculty = Fakultas::findOrFail($facultyId);
            $data = $this->prepareFacultyExportData($faculty);
            $filename = "faculty_{$faculty->code}_" . date('Ymd_His');
        } else {
            $data = $this->prepareAllFacultiesExportData();
            $filename = "all_faculties_" . date('Ymd_His');
        }
        
        // TODO: Implement actual Excel/PDF export
        // For now, return JSON data
        
        return response()->json([
            'message' => 'Export functionality would generate a ' . strtoupper($type) . ' file.',
            'filename' => $filename . '.' . $type,
            'data_preview' => $data,
            'status' => 'export_not_implemented'
        ]);
    }

    /**
     * Import faculties from Excel/CSV.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'import_mode' => 'sometimes|in:create,update,replace'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // TODO: Implement actual import logic
        // This would parse the file and create/update faculty records
        
        return response()->json([
            'message' => 'Import functionality would process the uploaded file.',
            'status' => 'import_not_implemented'
        ]);
    }

    /**
     * Prepare faculty data for export.
     */
    private function prepareFacultyExportData($faculty)
    {
        return [
            'faculty_info' => [
                'name' => $faculty->name,
                'code' => $faculty->code,
                'dean' => $faculty->dean,
                'establishment_year' => $faculty->establishment_year,
                'contact' => [
                    'email' => $faculty->email,
                    'phone' => $faculty->phone,
                    'website' => $faculty->website,
                    'address' => $faculty->address
                ]
            ],
            'statistics' => [
                'students' => $faculty->mahasiswas()->count(),
                'lecturers' => $faculty->dosens()->count(),
                'programs' => $faculty->programStudis()->count()
            ],
            'programs' => $faculty->programStudis->map(function($program) {
                return [
                    'name' => $program->name,
                    'code' => $program->code,
                    'students' => $program->mahasiswas()->count(),
                    'lecturers' => $program->dosens()->count()
                ];
            })
        ];
    }

    /**
     * Prepare all faculties data for export.
     */
    private function prepareAllFacultiesExportData()
    {
        $faculties = Fakultas::withCount(['mahasiswas', 'dosens', 'programStudis'])->get();
        
        return $faculties->map(function($faculty) {
            return [
                'name' => $faculty->name,
                'code' => $faculty->code,
                'dean' => $faculty->dean,
                'students' => $faculty->mahasiswas_count,
                'lecturers' => $faculty->dosens_count,
                'programs' => $faculty->program_studis_count,
                'establishment_year' => $faculty->establishment_year
            ];
        });
    }

    /**
     * Calculate faculty average GPA.
     */
    private function calculateFacultyAverageGPA($facultyId)
    {
        // Implementation would calculate average GPA for all students in faculty
        return 3.25; // Placeholder
    }

    /**
     * Calculate faculty graduation rate.
     */
    private function calculateFacultyGraduationRate($facultyId)
    {
        // Implementation would calculate graduation rate
        return 92.3; // Placeholder
    }

    /**
     * Calculate faculty average enrollment per course.
     */
    private function calculateFacultyAverageEnrollment($facultyId)
    {
        $courses = MataKuliah::whereHas('programStudi', function($query) use ($facultyId) {
            $query->where('faculty_id', $facultyId);
        })->get();
        
        return $courses->count() > 0 ? round($courses->avg('terisi'), 2) : 0;
    }

    /**
     * Calculate faculty attendance rate.
     */
    private function calculateFacultyAttendanceRate($facultyId)
    {
        // Implementation would calculate attendance rate
        return 85.5; // Placeholder
    }
}

