<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MataKuliahController extends Controller
{
    /**
     * Display a listing of courses with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = MataKuliah::with(['dosen', 'studyProgram']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('dosen', function($dosenQuery) use ($search) {
                      $dosenQuery->where('full_name', 'like', "%{$search}%")
                                ->orWhereHas('user', function($userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%");
                                });
                  });
            });
        }
        
        if ($request->has('dosen_id')) {
            $query->where('dosen_id', $request->dosen_id);
        }
        
        if ($request->has('study_program_id')) {
            $query->where('study_program_id', $request->study_program_id);
        }
        
        if ($request->has('semester')) {
            $query->where('semester', $request->semester);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('sks')) {
            $query->where('sks', $request->sks);
        }
        
        // Filter by availability
        if ($request->has('available_only') && $request->available_only == 'true') {
            $query->where('terisi', '<', \DB::raw('kuota'));
        }
        
        // Sort results
        $sortBy = $request->get('sort_by', 'kode');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('per_page', 20);
        $courses = $query->paginate($perPage);
        
        // Get filter options for frontend
        $lecturers = Dosen::with('user')->get();
        $studyPrograms = ProgramStudi::all();
        
        return response()->json([
            'courses' => $courses,
            'filters' => [
                'lecturers' => $lecturers,
                'study_programs' => $studyPrograms,
                'semester_options' => [1, 2, 3, 4, 5, 6, 7, 8],
                'type_options' => ['mandatory', 'elective', 'practicum', 'thesis'],
                'sks_options' => [1, 2, 3, 4, 6]
            ]
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:mata_kuliahs,kode',
            'nama' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:6',
            'kuota' => 'required|integer|min:1|max:100',
            'semester' => 'required|integer|min:1|max:8',
            'dosen_id' => 'required|exists:dosens,id',
            'study_program_id' => 'required|exists:program_studis,id',
            'type' => 'required|in:mandatory,elective,practicum,thesis',
            'total_hours' => 'nullable|integer|min:0',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:mata_kuliahs,id',
            'deskripsi' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Create course record
        $course = MataKuliah::create([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'sks' => $request->sks,
            'kuota' => $request->kuota,
            'terisi' => 0,
            'semester' => $request->semester,
            'dosen_id' => $request->dosen_id,
            'study_program_id' => $request->study_program_id,
            'type' => $request->type,
            'total_hours' => $request->total_hours,
            'prerequisites' => $request->prerequisites,
            'deskripsi' => $request->deskripsi,
            'description' => $request->description,
        ]);
        
        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course->load(['dosen', 'studyProgram']),
            'course_statistics' => $course->course_statistics
        ], 201);
    }

    /**
     * Display the specified course with full details.
     */
    public function show($id)
    {
        $course = MataKuliah::with(['dosen.user', 'studyProgram'])->findOrFail($id);
        
        return response()->json([
            'course' => $course,
            'course_statistics' => $course->course_statistics,
            'enrollments' => $course->currentEnrollments()->with('mahasiswa.user')->get(),
            'grades_summary' => [
                'average_grade' => $course->average_grade,
                'grade_distribution' => $this->getGradeDistribution($course),
                'total_students_graded' => $course->grades()->where('status', 'published')->count()
            ],
            'attendance_summary' => [
                'attendance_rate' => $course->attendance_rate,
                'total_sessions' => $course->attendances()->count(),
                'present_sessions' => $course->attendances()->where('status', 'present')->count()
            ],
            'schedule' => $course->jadwals,
            'prerequisites_details' => $course->prerequisites ? 
                MataKuliah::whereIn('id', $course->prerequisites)->get(['id', 'kode', 'nama', 'sks']) : []
        ]);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, $id)
    {
        $course = MataKuliah::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'kode' => 'sometimes|string|unique:mata_kuliahs,kode,' . $id,
            'nama' => 'sometimes|string|max:255',
            'sks' => 'sometimes|integer|min:1|max:6',
            'kuota' => 'sometimes|integer|min:' . $course->terisi . '|max:100',
            'semester' => 'sometimes|integer|min:1|max:8',
            'dosen_id' => 'sometimes|exists:dosens,id',
            'study_program_id' => 'sometimes|exists:program_studis,id',
            'type' => 'sometimes|in:mandatory,elective,practicum,thesis',
            'total_hours' => 'nullable|integer|min:0',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:mata_kuliahs,id',
            'deskripsi' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Validate that new quota is not less than current enrollment
        if ($request->has('kuota') && $request->kuota < $course->terisi) {
            return response()->json([
                'errors' => ['kuota' => ['New quota cannot be less than current enrollment (' . $course->terisi . ')']]
            ], 422);
        }
        
        // Update course record
        $course->update([
            'kode' => $request->kode ?? $course->kode,
            'nama' => $request->nama ?? $course->nama,
            'sks' => $request->sks ?? $course->sks,
            'kuota' => $request->kuota ?? $course->kuota,
            'semester' => $request->semester ?? $course->semester,
            'dosen_id' => $request->dosen_id ?? $course->dosen_id,
            'study_program_id' => $request->study_program_id ?? $course->study_program_id,
            'type' => $request->type ?? $course->type,
            'total_hours' => $request->total_hours ?? $course->total_hours,
            'prerequisites' => $request->prerequisites ?? $course->prerequisites,
            'deskripsi' => $request->deskripsi ?? $course->deskripsi,
            'description' => $request->description ?? $course->description,
        ]);
        
        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->load(['dosen', 'studyProgram']),
            'course_statistics' => $course->course_statistics
        ]);
    }

    /**
     * Remove the specified course.
     */
    public function destroy($id)
    {
        $course = MataKuliah::findOrFail($id);
        
        // Check if course has enrollments
        if ($course->terisi > 0) {
            return response()->json([
                'message' => 'Cannot delete course with active enrollments. Please remove students first.'
            ], 400);
        }
        
        // Check if course is a prerequisite for other courses
        $isPrerequisite = MataKuliah::whereJsonContains('prerequisites', $id)->exists();
        if ($isPrerequisite) {
            return response()->json([
                'message' => 'Cannot delete course that is a prerequisite for other courses.'
            ], 400);
        }
        
        // Delete associated schedules
        $course->jadwals()->delete();
        
        // Delete course record
        $course->delete();
        
        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }

    /**
     * Get course enrollment statistics.
     */
    public function enrollmentStatistics($id)
    {
        $course = MataKuliah::findOrFail($id);
        
        $enrollments = $course->currentEnrollments()->with('mahasiswa.user')->get();
        $enrollmentByGender = $enrollments->groupBy('mahasiswa.gender')->map->count();
        $enrollmentByAngkatan = $enrollments->groupBy('mahasiswa.angkatan')->map->count();
        
        return response()->json([
            'course' => $course->nama,
            'course_code' => $course->kode,
            'enrollment_stats' => [
                'total_enrolled' => $course->terisi,
                'available_slots' => $course->available_slots,
                'quota' => $course->kuota,
                'enrollment_rate' => round(($course->terisi / $course->kuota) * 100, 2),
                'by_gender' => $enrollmentByGender,
                'by_angkatan' => $enrollmentByAngkatan,
                'enrollment_list' => $enrollments->map(function($enrollment) {
                    return [
                        'student_id' => $enrollment->mahasiswa_id,
                        'nim' => $enrollment->mahasiswa->nim,
                        'name' => $enrollment->mahasiswa->full_name ?? $enrollment->mahasiswa->user->name,
                        'angkatan' => $enrollment->mahasiswa->angkatan,
                        'status' => $enrollment->status
                    ];
                })
            ]
        ]);
    }

    /**
     * Get course grade statistics.
     */
    public function gradeStatistics($id)
    {
        $course = MataKuliah::findOrFail($id);
        $grades = $course->grades()->where('status', 'published')->get();
        
        $gradeDistribution = $this->getGradeDistribution($course);
        $scoreRanges = [
            '85-100' => $grades->whereBetween('total_score', [85, 100])->count(),
            '70-84' => $grades->whereBetween('total_score', [70, 84])->count(),
            '55-69' => $grades->whereBetween('total_score', [55, 69])->count(),
            '40-54' => $grades->whereBetween('total_score', [40, 54])->count(),
            '0-39' => $grades->whereBetween('total_score', [0, 39])->count(),
        ];
        
        return response()->json([
            'course' => $course->nama,
            'course_code' => $course->kode,
            'grade_stats' => [
                'total_students_graded' => $grades->count(),
                'average_grade' => $course->average_grade,
                'highest_score' => $grades->max('total_score'),
                'lowest_score' => $grades->min('total_score'),
                'grade_distribution' => $gradeDistribution,
                'score_ranges' => $scoreRanges,
                'component_averages' => [
                    'assignment' => round($grades->avg('assignment_score'), 2),
                    'quiz' => round($grades->avg('quiz_score'), 2),
                    'mid_exam' => round($grades->avg('mid_exam_score'), 2),
                    'final_exam' => round($grades->avg('final_exam_score'), 2),
                    'practicum' => round($grades->avg('practicum_score'), 2),
                    'attendance' => round($grades->avg('attendance_score'), 2),
                ]
            ]
        ]);
    }

    /**
     * Get courses by study program.
     */
    public function byStudyProgram($studyProgramId)
    {
        $courses = MataKuliah::where('study_program_id', $studyProgramId)
            ->with(['dosen.user', 'studyProgram'])
            ->orderBy('semester')
            ->orderBy('kode')
            ->get()
            ->groupBy('semester');
        
        return response()->json([
            'study_program' => ProgramStudi::find($studyProgramId)->name ?? 'Unknown',
            'courses_by_semester' => $courses->map(function($semesterCourses, $semester) {
                return [
                    'semester' => $semester,
                    'total_courses' => $semesterCourses->count(),
                    'total_sks' => $semesterCourses->sum('sks'),
                    'courses' => $semesterCourses->map(function($course) {
                        return [
                            'id' => $course->id,
                            'kode' => $course->kode,
                            'nama' => $course->nama,
                            'sks' => $course->sks,
                            'type' => $course->formatted_type,
                            'lecturer' => $course->dosen->full_name ?? $course->dosen->user->name,
                            'available_slots' => $course->available_slots,
                            'is_full' => $course->is_full
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * Get courses available for enrollment (not full).
     */
    public function availableForEnrollment(Request $request)
    {
        $query = MataKuliah::where('terisi', '<', \DB::raw('kuota'))
            ->with(['dosen.user', 'studyProgram']);
        
        if ($request->has('study_program_id')) {
            $query->where('study_program_id', $request->study_program_id);
        }
        
        if ($request->has('semester')) {
            $query->where('semester', $request->semester);
        }
        
        $courses = $query->orderBy('kode')->get();
        
        return response()->json([
            'total_available' => $courses->count(),
            'courses' => $courses->map(function($course) {
                return [
                    'id' => $course->id,
                    'kode' => $course->kode,
                    'nama' => $course->nama,
                    'sks' => $course->sks,
                    'type' => $course->formatted_type,
                    'lecturer' => $course->dosen->full_name ?? $course->dosen->user->name,
                    'available_slots' => $course->available_slots,
                    'quota' => $course->kuota,
                    'enrolled' => $course->terisi,
                    'semester' => $course->semester,
                    'prerequisites' => $course->prerequisites_details ?? []
                ];
            })
        ]);
    }

    /**
     * Get course statistics summary.
     */
    public function statistics()
    {
        $total = MataKuliah::count();
        $available = MataKuliah::where('terisi', '<', \DB::raw('kuota'))->count();
        $full = MataKuliah::where('terisi', '>=', \DB::raw('kuota'))->count();
        
        $byType = MataKuliah::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();
        
        $bySemester = MataKuliah::selectRaw('semester, count(*) as count')
            ->groupBy('semester')
            ->orderBy('semester')
            ->get();
        
        $bySks = MataKuliah::selectRaw('sks, count(*) as count')
            ->groupBy('sks')
            ->orderBy('sks')
            ->get();
        
        $totalEnrollment = MataKuliah::sum('terisi');
        $totalQuota = MataKuliah::sum('kuota');
        $enrollmentRate = $totalQuota > 0 ? round(($totalEnrollment / $totalQuota) * 100, 2) : 0;
        
        return response()->json([
            'total_courses' => $total,
            'available_courses' => $available,
            'full_courses' => $full,
            'by_type' => $byType,
            'by_semester' => $bySemester,
            'by_sks' => $bySks,
            'enrollment_summary' => [
                'total_enrolled' => $totalEnrollment,
                'total_quota' => $totalQuota,
                'enrollment_rate' => $enrollmentRate . '%'
            ]
        ]);
    }

    /**
     * Helper method to get grade distribution.
     */
    private function getGradeDistribution($course)
    {
        $grades = $course->grades()->where('status', 'published')->get();
        
        $distribution = [
            'A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0, 'B-' => 0,
            'C+' => 0, 'C' => 0, 'C-' => 0, 'D+' => 0, 'D' => 0, 'E' => 0
        ];
        
        foreach ($grades as $grade) {
            if (isset($distribution[$grade->letter_grade])) {
                $distribution[$grade->letter_grade]++;
            }
        }
        
        return $distribution;
    }
}

