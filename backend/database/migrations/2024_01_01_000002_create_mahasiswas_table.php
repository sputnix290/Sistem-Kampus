<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->string('jurusan');
            $table->string('program_studi')->nullable();
            $table->integer('angkatan');
            $table->integer('semester_aktif')->default(1);
            $table->string('foto')->nullable();
            $table->decimal('ipk', 3, 2)->default(0.00);
            $table->foreignId('dosen_wali_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->enum('status', ['aktif', 'cuti', 'lulus', 'keluar'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswas');
    }
};
