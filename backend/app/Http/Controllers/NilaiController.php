<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Krs;
use App\Models\MataKuliah;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NilaiController extends Controller
{
    /**
     * Display grades with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = Grade::with(['enrollment.mahasiswa.user', 'enrollment.mataKuliah', 'gradedBy']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('enrollment.mahasiswa', function($studentQuery) use ($search) {
                    $studentQuery->where('nim', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%")
                                ->orWhereHas('user', function($userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%");
                                });
                })
                ->orWhereHas('enrollment.mataKuliah', function($courseQuery) use ($search) {
                    $courseQuery->where('kode', 'like', "%{$search}%")
                                ->orWhere('nama', 'like', "%{$search}%");
                })
                ->orWhere('letter_grade', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('mahasiswa_id')) {
            $query->whereHas('enrollment', function($enrollmentQuery) use ($request) {
                $enrollmentQuery->where('mahasiswa_id', $request->mahasiswa_id);
            });
        }
        
        if ($request->has('mata_kuliah_id')) {
            $query->whereHas('enrollment', function($enrollmentQuery) use ($request) {
                $enrollmentQuery->where('mata_kuliah_id', $request->mata_kuliah_id);
            });
        }
        
        if ($request->has('lecturer_id')) {
            $query->where('graded_by', $request->lecturer_id);
        }
        
        if ($request->has('academic_year')) {
            $query->whereHas('enrollment', function($enrollmentQuery) use ($request) {
                $enrollmentQuery->where('academic_year', $request->academic_year);
            });
        }
        
        if ($request->has('semester_type')) {
            $query->whereHas('enrollment', function($enrollmentQuery) use ($request) {
                $enrollmentQuery->where('semester_type', $request->semester_type);
            });
        }
        
        if ($request->has('letter_grade')) {
            $query->where('letter_grade', $request->letter_grade);
        }
        
        // Sort results
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('per_page', 20);
        $grades = $query->paginate($perPage);
        
        return response()->json([
            'grades' => $grades,
            'filters' => [
                'letter_grade_options' => ['A', 'B+', 'B', 'C+', 'C', 'D', 'E'],
                'academic_year_options' => ['2023/2024', '2024/2025', '2025/2026', '2026/2027'],
                'semester_type_options' => ['odd', 'even', 'short']
            ]
        ]);
    }

    /**
     * Store a new grade.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enrollment_id' => 'required|exists:krs,id',
            'assignment_score' => 'nullable|numeric|min:0|max:100',
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'quiz_score' => 'nullable|numeric|min:0|max:100',
            'project_score' => 'nullable|numeric|min:0|max:100',
            'letter_grade' => 'required|in:A,B+,B,C+,C,D,E',
            'graded_by' => 'required|exists:dosens,id',
            'notes' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get enrollment
        $enrollment = Krs::findOrFail($request->enrollment_id);
        
        // Check if enrollment is completed
        if ($enrollment->status !== 'completed') {
            return response()->json([
                'message' => 'Cannot add grades to an enrollment that is not completed.'
            ], 400);
        }
        
        // Check if lecturer is assigned to this course
        $lecturer = Dosen::findOrFail($request->graded_by);
        $course = $enrollment->mataKuliah;
        
        if ($course->dosen_id !== $lecturer->id) {
            return response()->json([
                'message' => 'Lecturer is not assigned to this course.'
            ], 403);
        }
        
        // Calculate weighted average if scores provided
        $finalGrade = null;
        if ($request->assignment_score || $request->midterm_score || $request->final_score) {
            // Default weights: assignment 30%, midterm 30%, final 40%
            $assignmentWeight = 0.3;
            $midtermWeight = 0.3;
            $finalWeight = 0.4;
            
            $assignmentScore = $request->assignment_score ?? 0;
            $midtermScore = $request->midterm_score ?? 0;
            $finalScore = $request->final_score ?? 0;
            
            $finalGrade = ($assignmentScore * $assignmentWeight) + 
                         ($midtermScore * $midtermWeight) + 
                         ($finalScore * $finalWeight);
            
            // Add quiz and project scores if provided
            if ($request->quiz_score) {
                $finalGrade += ($request->quiz_score * 0.1); // 10% weight
            }
            
            if ($request->project_score) {
                $finalGrade += ($request->project_score * 0.2); // 20% weight
            }
        }
        
        // Create grade
        $grade = new Grade([
            'enrollment_id' => $enrollment->id,
            'assignment_score' => $request->assignment_score,
            'midterm_score' => $request->midterm_score,
            'final_score' => $request->final_score,
            'quiz_score' => $request->quiz_score,
            'project_score' => $request->project_score,
            'letter_grade' => $request->letter_grade,
            'final_grade' => $finalGrade,
            'graded_by' => $lecturer->id,
            'notes' => $request->notes,
            'is_published' => $request->get('is_published', false)
        ]);
        
        $grade->save();
        
        // Update enrollment final grade
        $enrollment->updateFinalGrade($grade->letter_grade);
        
        return response()->json([
            'message' => 'Grade added successfully.',
            'grade' => $grade->load(['enrollment.mahasiswa.user', 'enrollment.mataKuliah', 'gradedBy']),
            'grade_summary' => $grade->grade_summary,
            'calculated_grade' => $finalGrade ? number_format($finalGrade, 2) . '/100' : 'N/A',
            'letter_grade_explanation' => $grade->letter_grade_explanation
        ], 201);
    }

    /**
     * Display the specified grade.
     */
    public function show($id)
    {
        $grade = Grade::with(['enrollment.mahasiswa.user', 'enrollment.mataKuliah', 'gradedBy'])->findOrFail($id);
        
        return response()->json([
            'grade' => $grade,
            'grade_summary' => $grade->grade_summary,
            'component_scores' => [
                'assignment' => $grade->assignment_score,
                'midterm' => $grade->midterm_score,
                'final' => $grade->final_score,
                'quiz' => $grade->quiz_score,
                'project' => $grade->project_score
            ],
            'enrollment_details' => [
                'student' => $grade->enrollment->mahasiswa->full_name ?? $grade->enrollment->mahasiswa->user->name,
                'nim' => $grade->enrollment->mahasiswa->nim,
                'course' => $grade->enrollment->mataKuliah->nama,
                'course_code' => $grade->enrollment->mataKuliah->kode,
                'academic_year' => $grade->enrollment->academic_year,
                'semester' => $grade->enrollment->semester_type
            ]
        ]);
    }

    /**
     * Update the specified grade.
     */
    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'assignment_score' => 'nullable|numeric|min:0|max:100',
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'quiz_score' => 'nullable|numeric|min:0|max:100',
            'project_score' => 'nullable|numeric|min:0|max:100',
            'letter_grade' => 'sometimes|in:A,B+,B,C+,C,D,E',
            'notes' => 'nullable|string|max:500',
            'is_published' => 'sometimes|boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if grade can be modified (not published or within 7 days)
        if ($grade->is_published && !$grade->canModify()) {
            return response()->json([
                'message' => 'Grade has been published and cannot be modified.'
            ], 400);
        }
        
        // Calculate final grade if scores are updated
        if ($request->has('assignment_score') || $request->has('midterm_score') || $request->has('final_score') || 
            $request->has('quiz_score') || $request->has('project_score')) {
            
            $assignmentScore = $request->has('assignment_score') ? $request->assignment_score : $grade->assignment_score;
            $midtermScore = $request->has('midterm_score') ? $request->midterm_score : $grade->midterm_score;
            $finalScore = $request->has('final_score') ? $request->final_score : $grade->final_score;
            $quizScore = $request->has('quiz_score') ? $request->quiz_score : $grade->quiz_score;
            $projectScore = $request->has('project_score') ? $request->project_score : $grade->project_score;
            
            // Default weights: assignment 30%, midterm 30%, final 40%
            $assignmentWeight = 0.3;
            $midtermWeight = 0.3;
            $finalWeight = 0.4;
            
            $finalGrade = ($assignmentScore * $assignmentWeight) + 
                         ($midtermScore * $midtermWeight) + 
                         ($finalScore * $finalWeight);
            
            // Add quiz and project scores
            if ($quizScore) {
                $finalGrade += ($quizScore * 0.1); // 10% weight
            }
            
            if ($projectScore) {
                $finalGrade += ($projectScore * 0.2); // 20% weight
            }
            
            $request->merge(['final_grade' => $finalGrade]);
            
            // Determine letter grade if not specified
            if (!$request->has('letter_grade')) {
                if ($finalGrade >= 85) $letterGrade = 'A';
                elseif ($finalGrade >= 75) $letterGrade = 'B+';
                elseif ($finalGrade >= 70) $letterGrade = 'B';
                elseif ($finalGrade >= 65) $letterGrade = 'C+';
                elseif ($finalGrade >= 60) $letterGrade = 'C';
                elseif ($finalGrade >= 55) $letterGrade = 'D';
                else $letterGrade = 'E';
                
                $request->merge(['letter_grade' => $letterGrade]);
            }
        }
        
        $grade->update($request->all());
        
        // Update enrollment final grade if letter grade changed
        if ($request->has('letter_grade')) {
            $grade->enrollment->updateFinalGrade($grade->letter_grade);
        }
        
        return response()->json([
            'message' => 'Grade updated successfully.',
            'grade' => $grade->load(['enrollment.mahasiswa.user', 'enrollment.mataKuliah', 'gradedBy']),
            'grade_summary' => $grade->grade_summary
        ]);
    }

    /**
     * Publish grade (make visible to student).
     */
    public function publish($id)
    {
        $grade = Grade::findOrFail($id);
        
        if ($grade->is_published) {
            return response()->json([
                'message' => 'Grade is already published.'
            ], 400);
        }
        
        $grade->publish();
        
        return response()->json([
            'message' => 'Grade published successfully.',
            'grade' => $grade->load(['enrollment.mahasiswa.user', 'enrollment.mataKuliah', 'gradedBy']),
            'grade_summary' => $grade->grade_summary
        ]);
    }

    /**
     * Remove the specified grade.
     */
    public function destroy($id)
    {
        $grade = Grade::findOrFail($id);
        
        // Check if grade can be deleted
        if ($grade->is_published && !$grade->canModify()) {
            return response()->json([
                'message' => 'Grade has been published and cannot be deleted.'
            ], 400);
        }
        
        // Reset enrollment final grade
        $grade->enrollment->updateFinalGrade(null);
        
        $grade->delete();
        
        return response()->json([
            'message' => 'Grade deleted successfully.'
        ]);
    }

    /**
     * Get grades by student.
     */
    public function byStudent($studentId)
    {
        $grades = Grade::whereHas('enrollment', function($enrollmentQuery) use ($studentId) {
                $enrollmentQuery->where('mahasiswa_id', $studentId);
            })
            ->with(['enrollment.mataKuliah', 'gradedBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('enrollment.academic_year');
        
        $summary = [];
        $totalGPA = 0;
        $totalCredits = 0;
        
        foreach ($grades as $year => $yearGrades) {
            $yearGPA = 0;
            $yearCredits = 0;
            $yearSummary = [];
            
            foreach ($yearGrades as $grade) {
                $courseSks = $grade->enrollment->mataKuliah->sks ?? 0;
                $gradePoint = $grade->grade_point ?? 0;
                
                $yearGPA += ($gradePoint * $courseSks);
                $yearCredits += $courseSks;
                
                $yearSummary[] = [
                    'course' => $grade->enrollment->mataKuliah->nama,
                    'course_code' => $grade->enrollment->mataKuliah->kode,
                    'sks' => $courseSks,
                    'letter_grade' => $grade->letter_grade,
                    'final_grade' => $grade->final_grade,
                    'grade_point' => $grade->grade_point,
                    'published' => $grade->is_published,
                    'graded_by' => $grade->gradedBy->full_name ?? $grade->gradedBy->user->name
                ];
            }
            
            $yearGPA = $yearCredits > 0 ? $yearGPA / $yearCredits : 0;
            
            $summary[$year] = [
                'academic_year' => $year,
                'total_courses' => count($yearGrades),
                'total_sks' => $yearCredits,
                'gpa' => round($yearGPA, 2),
                'grades' => $yearSummary
            ];
            
            $totalGPA += $yearGPA;
            $totalCredits += $yearCredits;
        }
        
        $overallGPA = $totalCredits > 0 ? $totalGPA / $totalCredits : 0;
        
        return response()->json([
            'grades_by_year' => $summary,
            'overall_statistics' => [
                'total_courses' => Grade::whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('mahasiswa_id', $studentId);
                })->count(),
                'total_sks' => $totalCredits,
                'overall_gpa' => round($overallGPA, 2),
                'published_grades' => Grade::whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('mahasiswa_id', $studentId);
                })->where('is_published', true)->count(),
                'letter_grade_distribution' => $this->getGradeDistribution($studentId)
            ]
        ]);
    }

    /**
     * Get grades by course.
     */
    public function byCourse($courseId)
    {
        $grades = Grade::whereHas('enrollment', function($enrollmentQuery) use ($courseId) {
                $enrollmentQuery->where('mata_kuliah_id', $courseId);
            })
            ->with(['enrollment.mahasiswa.user', 'gradedBy'])
            ->orderBy('final_grade', 'desc')
            ->get();
        
        $statistics = [
            'total_students' => $grades->count(),
            'average_grade' => $grades->avg('final_grade') ? round($grades->avg('final_grade'), 2) : null,
            'highest_grade' => $grades->max('final_grade'),
            'lowest_grade' => $grades->min('final_grade'),
            'letter_grade_distribution' => $grades->groupBy('letter_grade')->map->count(),
            'grade_summary' => $this->calculateGradeSummary($grades)
        ];
        
        return response()->json([
            'grades' => $grades->map(function($grade) {
                return [
                    'student' => $grade->enrollment->mahasiswa->full_name ?? $grade->enrollment->mahasiswa->user->name,
                    'nim' => $grade->enrollment->mahasiswa->nim,
                    'final_grade' => $grade->final_grade,
                    'letter_grade' => $grade->letter_grade,
                    'published' => $grade->is_published,
                    'graded_by' => $grade->gradedBy->full_name ?? $grade->gradedBy->user->name,
                    'graded_at' => $grade->created_at->format('d M Y')
                ];
            }),
            'statistics' => $statistics
        ]);
    }

    /**
     * Import grades from CSV/Excel.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx',
            'academic_year' => 'required',
            'semester_type' => 'required',
            'graded_by' => 'required|exists:dosens,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // TODO: Implement CSV/Excel parsing logic
        // This would parse the file and create grade entries
        
        return response()->json([
            'message' => 'Grade import feature would be implemented here.',
            'status' => 'not_implemented'
        ]);
    }

    /**
     * Get grade statistics and analytics.
     */
    public function statistics(Request $request)
    {
        $query = Grade::query();
        
        if ($request->has('academic_year')) {
            $query->whereHas('enrollment', function($enrollmentQuery) use ($request) {
                $enrollmentQuery->where('academic_year', $request->academic_year);
            });
        }
        
        if ($request->has('semester_type')) {
            $query->whereHas('enrollment', function($enrollmentQuery) use ($request) {
                $enrollmentQuery->where('semester_type', $request->semester_type);
            });
        }
        
        $totalGrades = $query->count();
        $publishedGrades = $query->clone()->where('is_published', true)->count();
        $averageGrade = $query->clone()->avg('final_grade');
        
        $letterGradeDistribution = $query->clone()
            ->selectRaw('letter_grade, count(*) as count')
            ->groupBy('letter_grade')
            ->orderBy('letter_grade')
            ->get()
            ->pluck('count', 'letter_grade');
        
        $byAcademicYear = Grade::selectRaw('substring_index(enrollment.academic_year, "/", 1) as year, count(*) as count')
            ->join('krs as enrollment', 'grades.enrollment_id', '=', 'enrollment.id')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();
        
        return response()->json([
            'total_grades' => $totalGrades,
            'published_grades' => $publishedGrades,
            'average_grade' => $averageGrade ? round($averageGrade, 2) : null,
            'letter_grade_distribution' => $letterGradeDistribution,
            'by_academic_year' => $byAcademicYear,
            'publication_rate' => $totalGrades > 0 ? round(($publishedGrades / $totalGrades) * 100, 2) : 0
        ]);
    }

    /**
     * Get grade distribution for a student.
     */
    private function getGradeDistribution($studentId)
    {
        return Grade::whereHas('enrollment', function($enrollmentQuery) use ($studentId) {
                $enrollmentQuery->where('mahasiswa_id', $studentId);
            })
            ->selectRaw('letter_grade, count(*) as count')
            ->groupBy('letter_grade')
            ->get()
            ->pluck('count', 'letter_grade');
    }

    /**
     * Calculate grade summary statistics.
     */
    private function calculateGradeSummary($grades)
    {
        $summary = [];
        $gradePoints = [
            'A' => 4.0,
            'B+' => 3.5,
            'B' => 3.0,
            'C+' => 2.5,
            'C' => 2.0,
            'D' => 1.0,
            'E' => 0.0
        ];
        
        foreach ($gradePoints as $letter => $point) {
            $count = $grades->where('letter_grade', $letter)->count();
            $summary[$letter] = [
                'count' => $count,
                'percentage' => $grades->count() > 0 ? round(($count / $grades->count()) * 100, 2) : 0,
                'grade_point' => $point
            ];
        }
        
        return $summary;
    }
}

