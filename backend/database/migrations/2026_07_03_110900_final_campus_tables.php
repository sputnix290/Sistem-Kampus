<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users table dengan kolom tambahan yang belum ada
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                if (!Schema::hasColumn('users', 'photo')) {
                    $table->string('photo')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('remember_token');
                }
            });
        }

        // 2. Update mahasiswas table dengan kolom tambahan
        if (Schema::hasTable('mahasiswas')) {
            Schema::table('mahasiswas', function (Blueprint $table) {
                if (!Schema::hasColumn('mahasiswas', 'full_name')) {
                    $table->string('full_name')->nullable()->after('nim');
                }
                if (!Schema::hasColumn('mahasiswas', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('full_name');
                }
                if (!Schema::hasColumn('mahasiswas', 'birth_place')) {
                    $table->string('birth_place')->nullable()->after('birth_date');
                }
                if (!Schema::hasColumn('mahasiswas', 'gender')) {
                    $table->enum('gender', ['male', 'female'])->nullable()->after('birth_place');
                }
                if (!Schema::hasColumn('mahasiswas', 'address')) {
                    $table->text('address')->nullable()->after('gender');
                }
                if (!Schema::hasColumn('mahasiswas', 'emergency_contact')) {
                    $table->string('emergency_contact')->nullable()->after('address');
                }
                if (!Schema::hasColumn('mahasiswas', 'emergency_phone')) {
                    $table->string('emergency_phone')->nullable()->after('emergency_contact');
                }
                if (!Schema::hasColumn('mahasiswas', 'blood_type')) {
                    $table->string('blood_type')->nullable()->after('emergency_phone');
                }
                if (!Schema::hasColumn('mahasiswas', 'religion')) {
                    $table->string('religion')->nullable()->after('blood_type');
                }
                if (!Schema::hasColumn('mahasiswas', 'ukt_amount')) {
                    $table->decimal('ukt_amount', 15, 2)->default(0)->after('religion');
                }
                if (!Schema::hasColumn('mahasiswas', 'ukt_status')) {
                    $table->enum('ukt_status', ['paid', 'unpaid', 'partial'])->default('unpaid')->after('ukt_amount');
                }
                if (!Schema::hasColumn('mahasiswas', 'father_name')) {
                    $table->string('father_name')->nullable()->after('ukt_status');
                }
                if (!Schema::hasColumn('mahasiswas', 'mother_name')) {
                    $table->string('mother_name')->nullable()->after('father_name');
                }
                if (!Schema::hasColumn('mahasiswas', 'parent_occupation')) {
                    $table->string('parent_occupation')->nullable()->after('mother_name');
                }
                if (!Schema::hasColumn('mahasiswas', 'parent_address')) {
                    $table->text('parent_address')->nullable()->after('parent_occupation');
                }
                if (!Schema::hasColumn('mahasiswas', 'parent_phone')) {
                    $table->string('parent_phone')->nullable()->after('parent_address');
                }
                if (!Schema::hasColumn('mahasiswas', 'faculty_id')) {
                    $table->foreignId('faculty_id')->nullable()->constrained('fakultas')->nullOnDelete()->after('program_studi');
                }
                if (!Schema::hasColumn('mahasiswas', 'study_program_id')) {
                    $table->foreignId('study_program_id')->nullable()->constrained('program_studis')->nullOnDelete()->after('faculty_id');
                }
            });
        }

        // 3. Update dosens table dengan kolom tambahan - FIXED: using 'nip' not 'nidn'
        if (Schema::hasTable('dosens')) {
            Schema::table('dosens', function (Blueprint $table) {
                if (!Schema::hasColumn('dosens', 'full_name')) {
                    $table->string('full_name')->nullable()->after('nip'); // FIXED: after('nip') not after('nidn')
                }
                if (!Schema::hasColumn('dosens', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('full_name');
                }
                if (!Schema::hasColumn('dosens', 'birth_place')) {
                    $table->string('birth_place')->nullable()->after('birth_date');
                }
                if (!Schema::hasColumn('dosens', 'gender')) {
                    $table->enum('gender', ['male', 'female'])->nullable()->after('birth_place');
                }
                if (!Schema::hasColumn('dosens', 'address')) {
                    $table->text('address')->nullable()->after('gender');
                }
                if (!Schema::hasColumn('dosens', 'phone')) {
                    $table->string('phone')->nullable()->after('address');
                }
                if (!Schema::hasColumn('dosens', 'email')) {
                    $table->string('email')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('dosens', 'education')) {
                    $table->enum('education', ['s1', 's2', 's3', 'professor'])->default('s1')->after('email');
                }
                if (!Schema::hasColumn('dosens', 'expertise')) {
                    $table->string('expertise')->nullable()->after('education');
                }
                if (!Schema::hasColumn('dosens', 'faculty_id')) {
                    $table->foreignId('faculty_id')->nullable()->constrained('fakultas')->nullOnDelete()->after('expertise');
                }
                if (!Schema::hasColumn('dosens', 'study_program_id')) {
                    $table->foreignId('study_program_id')->nullable()->constrained('program_studis')->nullOnDelete()->after('faculty_id');
                }
                if (!Schema::hasColumn('dosens', 'position')) {
                    $table->enum('position', ['lecturer', 'head_of_study_program', 'dean', 'vice_dean'])->default('lecturer')->after('study_program_id');
                }
                if (!Schema::hasColumn('dosens', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'retired'])->default('active')->after('position');
                }
            });
        }

        // 4. Update mata_kuliahs table dengan kolom tambahan - FIXED: using correct column names
        if (Schema::hasTable('mata_kuliahs')) {
            Schema::table('mata_kuliahs', function (Blueprint $table) {
                if (!Schema::hasColumn('mata_kuliahs', 'description')) {
                    $table->string('description')->nullable()->after('nama'); // FIXED: after('nama') not after('nama_matkul')
                }
                if (!Schema::hasColumn('mata_kuliahs', 'type')) {
                    $table->enum('type', ['mandatory', 'elective', 'practicum', 'thesis'])->default('mandatory')->after('description');
                }
                if (!Schema::hasColumn('mata_kuliahs', 'total_hours')) {
                    $table->integer('total_hours')->default(0)->after('type');
                }
                if (!Schema::hasColumn('mata_kuliahs', 'prerequisites')) {
                    $table->string('prerequisites')->nullable()->after('total_hours');
                }
                if (!Schema::hasColumn('mata_kuliahs', 'study_program_id')) {
                    $table->foreignId('study_program_id')->nullable()->constrained('program_studis')->nullOnDelete()->after('prerequisites');
                }
                // Note: semester column already exists in original migration
            });
        }

        // 5. Update jadwals table dengan kolom tambahan - FIXED: mata_kuliah_id already exists, only add missing columns
        if (Schema::hasTable('jadwals')) {
            Schema::table('jadwals', function (Blueprint $table) {
                // mata_kuliah_id already exists from original migration, don't add it again
                if (!Schema::hasColumn('jadwals', 'lecturer_id')) {
                    $table->foreignId('lecturer_id')->nullable()->constrained('dosens')->nullOnDelete()->after('mata_kuliah_id');
                }
                if (!Schema::hasColumn('jadwals', 'class_type')) {
                    $table->enum('class_type', ['regular', 'makeup', 'exam', 'extra'])->default('regular')->after('ruangan');
                }
                if (!Schema::hasColumn('jadwals', 'start_date')) {
                    $table->date('start_date')->nullable()->after('class_type');
                }
                if (!Schema::hasColumn('jadwals', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                // Note: hari, jam_mulai, jam_selesai, ruangan already exist in original migration
            });
        }

        // 6. Update krs table dengan kolom tambahan
        if (Schema::hasTable('krs')) {
            Schema::table('krs', function (Blueprint $table) {
                if (!Schema::hasColumn('krs', 'mata_kuliah_id')) {
                    $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete()->after('mahasiswa_id');
                }
                if (!Schema::hasColumn('krs', 'status')) {
                    $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->after('mata_kuliah_id');
                }
                if (!Schema::hasColumn('krs', 'academic_year')) {
                    $table->enum('academic_year', ['2023/2024', '2024/2025', '2025/2026', '2026/2027'])->default('2024/2025')->after('status');
                }
                if (!Schema::hasColumn('krs', 'semester_type')) {
                    $table->enum('semester_type', ['odd', 'even', 'short'])->default('odd')->after('academic_year');
                }
                if (!Schema::hasColumn('krs', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('semester_type');
                }
                if (!Schema::hasColumn('krs', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
                }
                if (!Schema::hasColumn('krs', 'notes')) {
                    $table->text('notes')->nullable()->after('approved_by');
                }
            });
        }

        // 7. Create tabel attendances jika belum ada
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->nullable()->constrained('krs')->nullOnDelete();
                $table->foreignId('student_id')->constrained('mahasiswas')->cascadeOnDelete();
                $table->foreignId('schedule_id')->nullable()->constrained('jadwals')->nullOnDelete();
                $table->date('attendance_date');
                $table->enum('status', ['present', 'absent', 'excused', 'late'])->default('absent');
                $table->time('check_in_time')->nullable();
                $table->time('check_out_time')->nullable();
                $table->string('attendance_method')->nullable()->comment('qr_code, biometric, manual');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 8. Create tabel grades jika belum ada
        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->nullable()->constrained('krs')->nullOnDelete();
                $table->foreignId('student_id')->constrained('mahasiswas')->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();
                $table->decimal('assignment_score', 5, 2)->default(0);
                $table->decimal('quiz_score', 5, 2)->default(0);
                $table->decimal('mid_exam_score', 5, 2)->default(0);
                $table->decimal('final_exam_score', 5, 2)->default(0);
                $table->decimal('practicum_score', 5, 2)->default(0);
                $table->decimal('attendance_score', 5, 2)->default(0);
                $table->decimal('total_score', 5, 2)->default(0);
                $table->string('letter_grade', 2)->nullable();
                $table->decimal('grade_point', 3, 2)->default(0);
                $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('graded_at')->nullable();
                $table->enum('status', ['draft', 'published', 'finalized'])->default('draft');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 9. Create tabel announcements jika belum ada
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->enum('target_audience', ['all', 'students', 'lecturers', 'parents', 'specific_faculty', 'specific_program'])->default('all');
                $table->json('target_ids')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_published')->default(false);
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->integer('views')->default(0);
                $table->timestamps();
            });
        }

        // 10. Create tabel news jika belum ada
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content');
                $table->string('featured_image')->nullable();
                $table->enum('category', ['academic', 'research', 'event', 'achievement', 'general'])->default('general');
                $table->json('tags')->nullable();
                $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->integer('views')->default(0);
                $table->timestamps();
            });
        }

        // 11. Create tabel faqs jika belum ada
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->text('answer');
                $table->enum('category', ['academic', 'admission', 'finance', 'facilities', 'general'])->default('general');
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 12. Create tabel contact_messages jika belum ada
        if (!Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('subject');
                $table->text('message');
                $table->enum('status', ['unread', 'read', 'replied', 'archived'])->default('unread');
                $table->text('admin_response')->nullable();
                $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
            });
        }

        // 13. Create tabel calendar_events jika belum ada
        if (!Schema::hasTable('calendar_events')) {
            Schema::create('calendar_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('event_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->enum('event_type', ['academic', 'holiday', 'exam', 'event', 'deadline'])->default('academic');
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_public')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Drop only the new tables we created
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('news');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('calendar_events');
        
        // Note: We don't drop columns from existing tables in down method
        // to avoid data loss during rollback
    }
};
