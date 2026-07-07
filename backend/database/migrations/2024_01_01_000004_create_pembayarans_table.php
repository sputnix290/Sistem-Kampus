<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->string('semester');
            $table->decimal('jumlah', 12, 2);
            $table->enum('status', ['pending', 'menunggu_verifikasi', 'lunas'])->default('pending');
            $table->enum('metode', ['virtual_account', 'qris', 'ewallet'])->nullable();
            $table->string('kode_transaksi')->nullable()->unique();
            $table->timestamp('tanggal_bayar')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
