<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable();          // file sementara (JSONL), dihapus setelah selesai
            $table->unsignedInteger('total_baris')->default(0);
            $table->unsignedInteger('baris_diproses')->default(0);
            $table->unsignedInteger('baris_baru')->default(0);
            $table->unsignedInteger('baris_update')->default(0);
            $table->unsignedInteger('baris_gagal')->default(0);
            $table->string('status', 20)->default('pratinjau'); // pratinjau | proses | selesai
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};
