<?php

namespace App\Http\Controllers;

use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KrsController extends Controller
{
    /**
     * Display enrollments with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = Krs::with(['mahasiswa.user', 'mataKuliah', 'approvedBy']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('mahasiswa', function($studentQuery) use ($search) {
                    $studentQuery->where('nim', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%")
                                ->orWhereHas('user', function($userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%");
                                });
                })
                ->orWhereHas('mataKuliah', function($courseQuery) use ($search) {
                    $courseQuery->where('kode', 'like', "%{$search}%")
                                ->orWhere('nama', 'like', "%{$search}%");
                });
            });
        }
        
        if ($request->has('mahasiswa_id')) {
            $query->where('mahasiswa_id', $request->mahasiswa_id);
        }
        
        if ($request->has('mata_kuliah_id')) {
            $query->where('mata_kuliah_id', $request->mata_kuliah_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        
        if ($request->has('semester_type')) {
            $query->where('semester_type', $request->semester_type);
        }
        
        // Sort results
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('per_page', 20);
        $enrollments = $query->paginate($perPage);
        
        return response()->json([
            'enrollments' => $enrollments,
            'filters' => [
                'academic_year_options' => ['2023/2024', '2024/2025', '2025/2026', '2026/2027'],
                'semester_type_options' => ['odd', 'even', 'short'],
                'status_options' => ['pending', 'approved', 'rejected', 'completed']
            ]
        ]);
    }

    /**
     * Store a new enrollment (KRS entry).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'academic_year' => 'required|in:2023/2024,2024/2025,2025/2026,2026/2027',
            'semester_type' => 'required|in:odd,even,short',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get student and course
        $student = Mahasiswa::findOrFail($request->mahasiswa_id);
        $course = MataKuliah::findOrFail($request->mata_kuliah_id);
        
        // Check if course is full
        if ($course->is_full) {
            return response()->json([
                'message' => 'Course is full. No available slots.'
            ], 400);
        }
        
        // Check if student is already enrolled in this course for the current semester
        $existingEnrollment = Krs::where('mahasiswa_id', $student->id)
            ->where('mata_kuliah_id', $course->id)
            ->where('academic_year', $request->academic_year)
            ->where('semester_type', $request->semester_type)
            ->first();
            
        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Student is already enrolled in this course for the selected semester.'
            ], 400);
        }
        
        // Create enrollment with validation
        $enrollment = new Krs([
            'mahasiswa_id' => $student->id,
            'mata_kuliah_id' => $course->id,
            'academic_year' => $request->academic_year,
            'semester_type' => $request->semester_type,
            'status' => 'pending',
            'notes' => $request->notes
        ]);
        
        // Validate prerequisites
        $prereqValidation = $enrollment->validatePrerequisites($student->id);
        if (!$prereqValidation['valid']) {
            return response()->json([
                'message' => $prereqValidation['message']
            ], 400);
        }
        
        // Validate SKS limit
        $sksValidation = $enrollment->validateSksLimit($student->id, $request->semester_type, $request->academic_year);
        if (!$sksValidation['valid']) {
            return response()->json([
                'message' => $sksValidation['message']
            ], 400);
        }
        
        // Save enrollment
        $enrollment->save();
        
        // Update course enrollment count
        $course->increment('terisi');
        
        return response()->json([
            'message' => 'Enrollment created successfully. Awaiting approval.',
            'enrollment' => $enrollment->load(['mahasiswa.user', 'mataKuliah']),
            'enrollment_summary' => $enrollment->enrollment_summary,
            'validations' => [
                'prerequisites' => $prereqValidation['message'],
                'sks_limit' => $sksValidation['message']
            ]
        ], 201);
    }

    /**
     * Display the specified enrollment.
     */
    public function show($id)
    {
        $enrollment = Krs::with(['mahasiswa.user', 'mataKuliah', 'approvedBy'])->findOrFail($id);
        
        return response()->json([
            'enrollment' => $enrollment,
            'enrollment_summary' => $enrollment->enrollment_summary,
            'attendance_records' => $enrollment->attendance_records,
            'grades' => $enrollment->grades,
            'validation_status' => [
                'prerequisites' => $enrollment->validatePrerequisites($enrollment->mahasiswa_id),
                'sks_limit' => $enrollment->validateSksLimit(
                    $enrollment->mahasiswa_id, 
                    $enrollment->semester_type, 
                    $enrollment->academic_year
                )
            ]
        ]);
    }

    /**
     * Update enrollment status (approve/reject).
     */
    public function updateStatus(Request $request, $id)
    {
        $enrollment = Krs::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
            'approved_by' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = auth()->user();
        
        // Check if user has permission to approve
        if (!$user->isAdmin() && !$user->isLecturer()) {
            return response()->json([
                'message' => 'Unauthorized. Only admins or lecturers can approve enrollments.'
            ], 403);
        }
        
        if ($request->status === 'approved') {
            $enrollment->approve($request->approved_by, $request->notes);
            $message = 'Enrollment approved successfully';
        } else {
            $enrollment->reject($request->approved_by, $request->notes);
            
            // Decrement course enrollment count if rejecting
            $enrollment->mataKuliah()->decrement('terisi');
            
            $message = 'Enrollment rejected';
        }
        
        return response()->json([
            'message' => $message,
            'enrollment' => $enrollment->load(['mahasiswa.user', 'mataKuliah', 'approvedBy']),
            'enrollment_summary' => $enrollment->enrollment_summary
        ]);
    }

    /**
     * Mark enrollment as completed (course finished).
     */
    public function complete($id)
    {
        $enrollment = Krs::findOrFail($id);
        
        if ($enrollment->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved enrollments can be marked as completed.'
            ], 400);
        }
        
        $enrollment->complete();
        
        return response()->json([
            'message' => 'Enrollment marked as completed',
            'enrollment' => $enrollment->load(['mahasiswa.user', 'mataKuliah']),
            'enrollment_summary' => $enrollment->enrollment_summary
        ]);
    }

    /**
     * Remove the specified enrollment.
     */
    public function destroy($id)
    {
        $enrollment = Krs::findOrFail($id);
        
        // Check if enrollment has grades or attendance records
        if ($enrollment->grades()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete enrollment with existing grades.'
            ], 400);
        }
        
        if ($enrollment->attendances()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete enrollment with existing attendance records.'
            ], 400);
        }
        
        // Decrement course enrollment count
        if ($enrollment->mataKuliah) {
            $enrollment->mataKuliah()->decrement('terisi');
        }
        
        $enrollment->delete();
        
        return response()->json([
            'message' => 'Enrollment deleted successfully'
        ]);
    }

    /**
     * Get enrollments by student.
     */
    public function byStudent($studentId)
    {
        $student = Mahasiswa::findOrFail($studentId);
        
        $enrollments = Krs::where('mahasiswa_id', $studentId)
            ->with(['mataKuliah', 'approvedBy'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester_type')
            ->get()
            ->groupBy('academic_year');
        
        $semesterSummary = [];
        foreach ($enrollments as $year => $yearEnrollments) {
            $semesterSummary[$year] = $yearEnrollments->groupBy('semester_type')->map(function($semesterEnrollments, $semester) {
                return [
                    'semester' => $semester,
                    'total_courses' => $semesterEnrollments->count(),
                    'total_sks' => $semesterEnrollments->sum(function($enrollment) {
                        return $enrollment->calculateTotalSks();
                    }),
                    'approved_courses' => $semesterEnrollments->where('status', 'approved')->count(),
                    'completed_courses' => $semesterEnrollments->where('status', 'completed')->count(),
                    'enrollments' => $semesterEnrollments->map(function($enrollment) {
                        return [
                            'id' => $enrollment->id,
                            'course' => $enrollment->mataKuliah->nama,
                            'course_code' => $enrollment->mataKuliah->kode,
                            'sks' => $enrollment->calculateTotalSks(),
                            'status' => $enrollment->formatted_status,
                            'approved_by' => $enrollment->approvedBy->name ?? null,
                            'approved_at' => $enrollment->approved_at?->format('d M Y'),
                            'attendance_percentage' => $enrollment->attendance_percentage,
                            'final_grade' => $enrollment->final_grade
                        ];
                    })
                ];
            });
        }
        
        return response()->json([
            'student' => [
                'id' => $student->id,
                'nim' => $student->nim,
                'name' => $student->full_name ?? $student->user->name,
                'angkatan' => $student->angkatan,
                'study_program' => $student->studyProgram->name ?? null
            ],
            'enrollments_by_year' => $semesterSummary,
            'total_enrollments' => Krs::where('mahasiswa_id', $studentId)->count(),
            'completed_courses' => Krs::where('mahasiswa_id', $studentId)->where('status', 'completed')->count(),
            'current_semester_enrollments' => $this->getCurrentSemesterEnrollments($studentId)
        ]);
    }

    /**
     * Get enrollments by course.
     */
    public function byCourse($courseId)
    {
        $course = MataKuliah::findOrFail($courseId);
        
        $enrollments = Krs::where('mata_kuliah_id', $courseId)
            ->with(['mahasiswa.user', 'approvedBy'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester_type')
            ->get()
            ->groupBy('academic_year');
        
        return response()->json([
            'course' => [
                'id' => $course->id,
                'kode' => $course->kode,
                'nama' => $course->nama,
                'sks' => $course->sks,
                'lecturer' => $course->dosen->full_name ?? $course->dosen->user->name
            ],
            'enrollments_by_year' => $enrollments->map(function($yearEnrollments, $year) {
                return [
                    'academic_year' => $year,
                    'total_enrollments' => $yearEnrollments->count(),
                    'by_semester' => $yearEnrollments->groupBy('semester_type')->map(function($semesterEnrollments, $semester) {
                        return [
                            'semester' => $semester,
                            'count' => $semesterEnrollments->count(),
                            'approved' => $semesterEnrollments->where('status', 'approved')->count(),
                            'completed' => $semesterEnrollments->where('status', 'completed')->count(),
                            'students' => $semesterEnrollments->map(function($enrollment) {
                                return [
                                    'id' => $enrollment->mahasiswa_id,
                                    'nim' => $enrollment->mahasiswa->nim,
                                    'name' => $enrollment->mahasiswa->full_name ?? $enrollment->mahasiswa->user->name,
                                    'status' => $enrollment->formatted_status,
                                    'attendance_percentage' => $enrollment->attendance_percentage,
                                    'final_grade' => $enrollment->final_grade
                                ];
                            })
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * Get pending enrollments for approval.
     */
    public function pending(Request $request)
    {
        $query = Krs::where('status', 'pending')
            ->with(['mahasiswa.user', 'mataKuliah']);
        
        if ($request->has('mata_kuliah_id')) {
            $query->where('mata_kuliah_id', $request->mata_kuliah_id);
        }
        
        $enrollments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'total_pending' => $enrollments->total(),
            'enrollments' => $enrollments
        ]);
    }

    /**
     * Get enrollment statistics.
     */
    public function statistics(Request $request)
    {
        $query = Krs::query();
        
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        
        if ($request->has('semester_type')) {
            $query->where('semester_type', $request->semester_type);
        }
        
        $total = $query->count();
        $pending = $query->clone()->where('status', 'pending')->count();
        $approved = $query->clone()->where('status', 'approved')->count();
        $rejected = $query->clone()->where('status', 'rejected')->count();
        $completed = $query->clone()->where('status', 'completed')->count();
        
        $byAcademicYear = Krs::selectRaw('academic_year, count(*) as count')
            ->groupBy('academic_year')
            ->orderBy('academic_year', 'desc')
            ->get();
        
        $bySemesterType = Krs::selectRaw('semester_type, count(*) as count')
            ->groupBy('semester_type')
            ->get();
        
        $byStatus = [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'completed' => $completed
        ];
        
        return response()->json([
            'total_enrollments' => $total,
            'by_status' => $byStatus,
            'by_academic_year' => $byAcademicYear,
            'by_semester_type' => $bySemesterType,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ]);
    }

    /**
     * Get current semester enrollments for a student.
     */
    private function getCurrentSemesterEnrollments($studentId)
    {
        $currentYear = date('Y') . '/' . (date('Y') + 1);
        $currentSemester = date('n') <= 6 ? 'odd' : 'even';
        
        return Krs::where('mahasiswa_id', $studentId)
            ->where('academic_year', $currentYear)
            ->where('semester_type', $currentSemester)
            ->with(['mataKuliah', 'approvedBy'])
            ->get()
            ->map(function($enrollment) {
                return [
                    'course' => $enrollment->mataKuliah->nama,
                    'course_code' => $enrollment->mataKuliah->kode,
                    'sks' => $enrollment->calculateTotalSks(),
                    'status' => $enrollment->formatted_status,
                    'lecturer' => $enrollment->mataKuliah->dosen->full_name ?? $enrollment->mataKuliah->dosen->user->name
                ];
            });
    }
}

