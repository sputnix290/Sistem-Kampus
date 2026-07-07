<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Krs;
use App\Models\MataKuliah;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PresensiController extends Controller
{
    /**
     * Display attendance records with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['enrollment.mahasiswa.user', 'enrollment.mataKuliah']);
        
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
                ->orWhere('status', 'like', "%{$search}%");
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
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('attendance_date')) {
            $query->whereDate('attendance_date', $request->attendance_date);
        }
        
        if ($request->has('date_range')) {
            $dates = explode(',', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('attendance_date', [$dates[0], $dates[1]]);
            }
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
        
        // Sort results
        $sortBy = $request->get('sort_by', 'attendance_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('per_page', 30);
        $attendanceRecords = $query->paginate($perPage);
        
        return response()->json([
            'attendance_records' => $attendanceRecords,
            'filters' => [
                'status_options' => ['present', 'absent', 'excused', 'late'],
                'academic_year_options' => ['2023/2024', '2024/2025', '2025/2026', '2026/2027'],
                'semester_type_options' => ['odd', 'even', 'short']
            ]
        ]);
    }

    /**
     * Store a new attendance record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enrollment_id' => 'required|exists:krs,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,absent,excused,late',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
            'recorded_by' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get enrollment
        $enrollment = Krs::findOrFail($request->enrollment_id);
        
        // Check if attendance for this date already exists
        $existingAttendance = Attendance::where('enrollment_id', $enrollment->id)
            ->whereDate('attendance_date', $request->attendance_date)
            ->first();
            
        if ($existingAttendance) {
            return response()->json([
                'message' => 'Attendance for this date already exists.'
            ], 400);
        }
        
        // Check if enrollment is active (approved or completed)
        if (!in_array($enrollment->status, ['approved', 'completed'])) {
            return response()->json([
                'message' => 'Cannot record attendance for an enrollment that is not approved.'
            ], 400);
        }
        
        // Calculate duration if time_in and time_out provided
        $duration = null;
        if ($request->time_in && $request->time_out) {
            $start = \Carbon\Carbon::parse($request->time_in);
            $end = \Carbon\Carbon::parse($request->time_out);
            $duration = $start->diffInMinutes($end);
            
            // Check if duration is negative (time_out before time_in)
            if ($duration < 0) {
                return response()->json([
                    'message' => 'Time out must be after time in.'
                ], 400);
            }
        }
        
        // Determine if late
        $isLate = false;
        if ($request->status === 'late') {
            $isLate = true;
        } elseif ($request->time_in) {
            // Check if time_in is after expected start time (e.g., 08:00)
            $expectedStart = \Carbon\Carbon::parse('08:00');
            $actualStart = \Carbon\Carbon::parse($request->time_in);
            
            if ($actualStart->gt($expectedStart)) {
                $isLate = true;
            }
        }
        
        // Create attendance record
        $attendance = new Attendance([
            'enrollment_id' => $enrollment->id,
            'attendance_date' => $request->attendance_date,
            'status' => $request->status,
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'duration' => $duration,
            'is_late' => $isLate,
            'notes' => $request->notes,
            'recorded_by' => $request->recorded_by
        ]);
        
        $attendance->save();
        
        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'attendance' => $attendance->load(['enrollment.mahasiswa.user', 'enrollment.mataKuliah']),
            'attendance_summary' => $attendance->attendance_summary,
            'duration_summary' => $duration ? $this->formatDuration($duration) : 'N/A'
        ], 201);
    }

    /**
     * Batch create attendance records for multiple students.
     */
    public function batchStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'attendance_date' => 'required|date',
            'attendance_records' => 'required|array',
            'attendance_records.*.mahasiswa_id' => 'required|exists:mahasiswas,id',
            'attendance_records.*.status' => 'required|in:present,absent,excused,late',
            'attendance_records.*.time_in' => 'nullable|date_format:H:i',
            'attendance_records.*.time_out' => 'nullable|date_format:H:i',
            'attendance_records.*.notes' => 'nullable|string|max:500',
            'recorded_by' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        $course = MataKuliah::findOrFail($request->mata_kuliah_id);
        $recordedBy = $request->recorded_by;
        $attendanceDate = $request->attendance_date;
        
        $createdRecords = [];
        $errors = [];
        
        foreach ($request->attendance_records as $index => $record) {
            try {
                // Find enrollment for this student and course
                $enrollment = Krs::where('mahasiswa_id', $record['mahasiswa_id'])
                    ->where('mata_kuliah_id', $request->mata_kuliah_id)
                    ->whereIn('status', ['approved', 'completed'])
                    ->first();
                
                if (!$enrollment) {
                    $errors[] = "Student {$record['mahasiswa_id']} is not enrolled in this course.";
                    continue;
                }
                
                // Check if attendance for this date already exists
                $existingAttendance = Attendance::where('enrollment_id', $enrollment->id)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->first();
                    
                if ($existingAttendance) {
                    $errors[] = "Student {$record['mahasiswa_id']} already has attendance for this date.";
                    continue;
                }
                
                // Calculate duration if time_in and time_out provided
                $duration = null;
                if (!empty($record['time_in']) && !empty($record['time_out'])) {
                    $start = \Carbon\Carbon::parse($record['time_in']);
                    $end = \Carbon\Carbon::parse($record['time_out']);
                    $duration = $start->diffInMinutes($end);
                    
                    if ($duration < 0) {
                        $errors[] = "Student {$record['mahasiswa_id']}: Time out must be after time in.";
                        continue;
                    }
                }
                
                // Determine if late
                $isLate = false;
                if ($record['status'] === 'late') {
                    $isLate = true;
                } elseif (!empty($record['time_in'])) {
                    $expectedStart = \Carbon\Carbon::parse('08:00');
                    $actualStart = \Carbon\Carbon::parse($record['time_in']);
                    
                    if ($actualStart->gt($expectedStart)) {
                        $isLate = true;
                    }
                }
                
                // Create attendance record
                $attendance = new Attendance([
                    'enrollment_id' => $enrollment->id,
                    'attendance_date' => $attendanceDate,
                    'status' => $record['status'],
                    'time_in' => $record['time_in'] ?? null,
                    'time_out' => $record['time_out'] ?? null,
                    'duration' => $duration,
                    'is_late' => $isLate,
                    'notes' => $record['notes'] ?? null,
                    'recorded_by' => $recordedBy
                ]);
                
                $attendance->save();
                $attendance->load(['enrollment.mahasiswa.user', 'enrollment.mataKuliah']);
                
                $createdRecords[] = [
                    'index' => $index,
                    'attendance' => $attendance,
                    'attendance_summary' => $attendance->attendance_summary
                ];
                
            } catch (\Exception $e) {
                $errors[] = "Student {$record['mahasiswa_id']}: {$e->getMessage()}";
            }
        }
        
        $status = count($errors) > 0 ? 'partial' : 'success';
        
        // Calculate batch statistics
        $totalRecords = count($request->attendance_records);
        $successful = count($createdRecords);
        
        return response()->json([
            'status' => $status,
            'message' => "Batch attendance recorded. Successful: {$successful}/{$totalRecords}",
            'created_records' => $createdRecords,
            'errors' => $errors,
            'summary' => [
                'total_records' => $totalRecords,
                'successful' => $successful,
                'failed' => count($errors),
                'attendance_date' => $attendanceDate,
                'course' => $course->nama,
                'lecturer' => $course->dosen->full_name ?? $course->dosen->user->name
            ]
        ], $status === 'success' ? 201 : 207);
    }

    /**
     * Display the specified attendance record.
     */
    public function show($id)
    {
        $attendance = Attendance::with(['enrollment.mahasiswa.user', 'enrollment.mataKuliah'])->findOrFail($id);
        
        return response()->json([
            'attendance' => $attendance,
            'attendance_summary' => $attendance->attendance_summary,
            'student_details' => [
                'name' => $attendance->enrollment->mahasiswa->full_name ?? $attendance->enrollment->mahasiswa->user->name,
                'nim' => $attendance->enrollment->mahasiswa->nim,
                'program' => $attendance->enrollment->mahasiswa->studyProgram->name ?? null
            ],
            'course_details' => [
                'name' => $attendance->enrollment->mataKuliah->nama,
                'code' => $attendance->enrollment->mataKuliah->kode,
                'lecturer' => $attendance->enrollment->mataKuliah->dosen->full_name ?? $attendance->enrollment->mataKuliah->dosen->user->name
            ]
        ]);
    }

    /**
     * Update the specified attendance record.
     */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:present,absent,excused,late',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if attendance can be modified (within 24 hours)
        $attendanceDate = \Carbon\Carbon::parse($attendance->attendance_date);
        $now = now();
        
        if ($attendanceDate->diffInDays($now) > 1) {
            return response()->json([
                'message' => 'Attendance records older than 24 hours cannot be modified.'
            ], 400);
        }
        
        // Calculate new duration if times are updated
        $duration = $attendance->duration;
        if ($request->has('time_in') || $request->has('time_out')) {
            $timeIn = $request->has('time_in') ? $request->time_in : $attendance->time_in;
            $timeOut = $request->has('time_out') ? $request->time_out : $attendance->time_out;
            
            if ($timeIn && $timeOut) {
                $start = \Carbon\Carbon::parse($timeIn);
                $end = \Carbon\Carbon::parse($timeOut);
                $duration = $start->diffInMinutes($end);
                
                if ($duration < 0) {
                    return response()->json([
                        'message' => 'Time out must be after time in.'
                    ], 400);
                }
            } else {
                $duration = null;
            }
            
            $request->merge(['duration' => $duration]);
        }
        
        // Update is_late based on status or time_in
        $isLate = $attendance->is_late;
        if ($request->has('status')) {
            $isLate = ($request->status === 'late');
        } elseif ($request->has('time_in')) {
            if ($request->time_in) {
                $expectedStart = \Carbon\Carbon::parse('08:00');
                $actualStart = \Carbon\Carbon::parse($request->time_in);
                $isLate = $actualStart->gt($expectedStart);
            }
        }
        
        $request->merge(['is_late' => $isLate]);
        
        $attendance->update($request->all());
        
        return response()->json([
            'message' => 'Attendance record updated successfully.',
            'attendance' => $attendance->load(['enrollment.mahasiswa.user', 'enrollment.mataKuliah']),
            'attendance_summary' => $attendance->attendance_summary
        ]);
    }

    /**
     * Remove the specified attendance record.
     */
    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        
        // Check if attendance can be deleted (within 24 hours)
        $attendanceDate = \Carbon\Carbon::parse($attendance->attendance_date);
        $now = now();
        
        if ($attendanceDate->diffInDays($now) > 1) {
            return response()->json([
                'message' => 'Attendance records older than 24 hours cannot be deleted.'
            ], 400);
        }
        
        $attendance->delete();
        
        return response()->json([
            'message' => 'Attendance record deleted successfully.'
        ]);
    }

    /**
     * Get attendance by student.
     */
    public function byStudent($studentId)
    {
        $attendanceRecords = Attendance::whereHas('enrollment', function($enrollmentQuery) use ($studentId) {
                $enrollmentQuery->where('mahasiswa_id', $studentId);
            })
            ->with(['enrollment.mataKuliah'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('enrollment.mata_kuliah_id');
        
        $summary = [];
        $totalAttendance = 0;
        $presentCount = 0;
        
        foreach ($attendanceRecords as $courseId => $courseAttendance) {
            $course = $courseAttendance->first()->enrollment->mataKuliah;
            $courseTotal = $courseAttendance->count();
            $coursePresent = $courseAttendance->where('status', 'present')->count();
            $courseLate = $courseAttendance->where('status', 'late')->count();
            
            $summary[] = [
                'course' => $course->nama,
                'course_code' => $course->kode,
                'total_classes' => $courseTotal,
                'present' => $coursePresent,
                'absent' => $courseAttendance->where('status', 'absent')->count(),
                'excused' => $courseAttendance->where('status', 'excused')->count(),
                'late' => $courseLate,
                'attendance_rate' => $courseTotal > 0 ? round(($coursePresent / $courseTotal) * 100, 2) : 0,
                'recent_records' => $courseAttendance->take(5)->map(function($record) {
                    return [
                        'date' => $record->attendance_date,
                        'status' => $record->formatted_status,
                        'time_in' => $record->time_in,
                        'time_out' => $record->time_out,
                        'duration' => $record->duration_summary
                    ];
                })
            ];
            
            $totalAttendance += $courseTotal;
            $presentCount += $coursePresent;
        }
        
        $overallAttendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 2) : 0;
        
        return response()->json([
            'attendance_by_course' => $summary,
            'overall_statistics' => [
                'total_classes' => $totalAttendance,
                'total_present' => $presentCount,
                'overall_attendance_rate' => $overallAttendanceRate,
                'total_absent' => Attendance::whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('mahasiswa_id', $studentId);
                })->where('status', 'absent')->count(),
                'total_late' => Attendance::whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('mahasiswa_id', $studentId);
                })->where('status', 'late')->count(),
                'monthly_summary' => $this->getMonthlyAttendance($studentId)
            ]
        ]);
    }

    /**
     * Get attendance by course.
     */
    public function byCourse($courseId)
    {
        $attendanceRecords = Attendance::whereHas('enrollment', function($enrollmentQuery) use ($courseId) {
                $enrollmentQuery->where('mata_kuliah_id', $courseId);
            })
            ->with(['enrollment.mahasiswa.user'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('attendance_date');
        
        $course = MataKuliah::findOrFail($courseId);
        $enrolledStudents = Krs::where('mata_kuliah_id', $courseId)
            ->whereIn('status', ['approved', 'completed'])
            ->with('mahasiswa.user')
            ->get();
        
        $studentAttendanceStats = [];
        foreach ($enrolledStudents as $enrollment) {
            $studentAttendance = $attendanceRecords->flatten()
                ->where('enrollment_id', $enrollment->id)
                ->values();
            
            $total = $studentAttendance->count();
            $present = $studentAttendance->whereIn('status', ['present', 'late'])->count();
            
            $studentAttendanceStats[] = [
                'student' => $enrollment->mahasiswa->full_name ?? $enrollment->mahasiswa->user->name,
                'nim' => $enrollment->mahasiswa->nim,
                'total_classes' => $total,
                'present' => $present,
                'absent' => $studentAttendance->where('status', 'absent')->count(),
                'excused' => $studentAttendance->where('status', 'excused')->count(),
                'late' => $studentAttendance->where('status', 'late')->count(),
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                'recent_status' => $studentAttendance->last()->status ?? 'no_data'
            ];
        }
        
        return response()->json([
            'course' => [
                'name' => $course->nama,
                'code' => $course->kode,
                'lecturer' => $course->dosen->full_name ?? $course->dosen->user->name
            ],
            'attendance_by_date' => $attendanceRecords->map(function($dateRecords, $date) {
                return [
                    'date' => $date,
                    'total_students' => $dateRecords->count(),
                    'present' => $dateRecords->whereIn('status', ['present', 'late'])->count(),
                    'absent' => $dateRecords->where('status', 'absent')->count(),
                    'excused' => $dateRecords->where('status', 'excused')->count(),
                    'late' => $dateRecords->where('status', 'late')->count(),
                    'attendance_rate' => $dateRecords->count() > 0 ? round(($dateRecords->whereIn('status', ['present', 'late'])->count() / $dateRecords->count()) * 100, 2) : 0,
                    'students' => $dateRecords->map(function($record) {
                        return [
                            'student' => $record->enrollment->mahasiswa->full_name ?? $record->enrollment->mahasiswa->user->name,
                            'nim' => $record->enrollment->mahasiswa->nim,
                            'status' => $record->formatted_status,
                            'time_in' => $record->time_in,
                            'time_out' => $record->time_out
                        ];
                    })
                ];
            }),
            'student_statistics' => $studentAttendanceStats
        ]);
    }

    /**
     * Get attendance statistics and analytics.
     */
    public function statistics(Request $request)
    {
        $query = Attendance::query();
        
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
        
        $totalRecords = $query->count();
        $presentRecords = $query->clone()->where('status', 'present')->count();
        $absentRecords = $query->clone()->where('status', 'absent')->count();
        $excusedRecords = $query->clone()->where('status', 'excused')->count();
        $lateRecords = $query->clone()->where('status', 'late')->count();
        
        $byStatus = [
            'present' => $presentRecords,
            'absent' => $absentRecords,
            'excused' => $excusedRecords,
            'late' => $lateRecords
        ];
        
        $byMonth = Attendance::selectRaw('MONTH(attendance_date) as month, YEAR(attendance_date) as year, count(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();
        
        $attendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 2) : 0;
        
        return response()->json([
            'total_records' => $totalRecords,
            'by_status' => $byStatus,
            'by_month' => $byMonth->map(function($record) {
                return [
                    'month_year' => date('F Y', mktime(0, 0, 0, $record->month, 1, $record->year)),
                    'count' => $record->count
                ];
            }),
            'attendance_rate' => $attendanceRate,
            'late_rate' => $totalRecords > 0 ? round(($lateRecords / $totalRecords) * 100, 2) : 0
        ]);
    }

    /**
     * Get monthly attendance summary for a student.
     */
    private function getMonthlyAttendance($studentId)
    {
        $monthlyData = Attendance::whereHas('enrollment', function($enrollmentQuery) use ($studentId) {
                $enrollmentQuery->where('mahasiswa_id', $studentId);
            })
            ->selectRaw('MONTH(attendance_date) as month, YEAR(attendance_date) as year, 
                        count(*) as total, 
                        sum(case when status in ("present", "late") then 1 else 0 end) as present')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();
        
        return $monthlyData->map(function($record) {
            return [
                'month_year' => date('M Y', mktime(0, 0, 0, $record->month, 1, $record->year)),
                'total_classes' => $record->total,
                'present' => $record->present,
                'attendance_rate' => $record->total > 0 ? round(($record->present / $record->total) * 100, 2) : 0
            ];
        });
    }

    /**
     * Format duration in minutes to readable format.
     */
    private function formatDuration($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes === 0) {
            return $hours . ' hours';
        }
        
        return $hours . ' hours ' . $remainingMinutes . ' minutes';
    }
}

