<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sto', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sto', 5)->unique();
            $table->string('nama_sto', 50);
            $table->string('wilayah', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sto');
    }
};