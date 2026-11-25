<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $t) {
            $t->id();
            $t->string('nama')->unique();
            $t->string('satuan', 20)->default('pcs');
            $t->timestamps();
        });

        Schema::create('pekerjaan_materials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('monitoring_id')->constrained()->cascadeOnDelete();
            $t->foreignId('material_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('qty')->default(0);
            $t->timestamps();
            $t->unique(['monitoring_id', 'material_id']);
        });

        Schema::create('pekerjaan_fotos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('monitoring_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('jenis', 20); // material | sesudah
            $t->string('path');
            $t->timestamps();
            $t->index(['monitoring_id', 'jenis']);
        });

        Schema::table('monitorings', function (Blueprint $t) {
            $t->string('tahap', 10)->nullable()->after('status'); // pre | jalan | selesai
            $t->text('catatan_teknisi')->nullable();
        });

        $now = now();
        foreach ([['Kabel drop 1 core', 'meter'], ['Konektor SC-UPC', 'pcs'], ['Patchcord SC-SC', 'pcs'],
                  ['Klem kabel', 'pcs'], ['Roset / OTP', 'pcs']] as [$nama, $satuan]) {
            DB::table('materials')->insert(['nama' => $nama, 'satuan' => $satuan, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::table('monitorings', fn (Blueprint $t) => $t->dropColumn(['tahap', 'catatan_teknisi']));
        Schema::dropIfExists('pekerjaan_fotos');
        Schema::dropIfExists('pekerjaan_materials');
        Schema::dropIfExists('materials');
    }
};
