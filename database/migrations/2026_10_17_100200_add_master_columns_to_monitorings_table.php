<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->string('scorderno', 50)->nullable()->index()->after('wonum');
            $table->string('homepass_id', 50)->nullable()->after('odp_name');
            $table->foreignId('odp_id')->nullable()->after('homepass_id')->constrained('odps')->nullOnDelete();
            $table->foreignId('upload_batch_id')->nullable()->after('odp_id')->constrained('upload_batches')->nullOnDelete();
        });

        // Isi tabel ODP dari nama ODP yang sudah ada di data lama, lalu hubungkan.
        $daftar = DB::table('monitorings')
            ->whereNotNull('odp_name')
            ->where('odp_name', '!=', '')
            ->select('odp_name', DB::raw('MIN(sto) as sto'))
            ->groupBy('odp_name')
            ->get();

        $sudah = [];
        foreach ($daftar->chunk(500) as $potong) {
            $baris = [];
            foreach ($potong as $o) {
                $nama  = trim($o->odp_name);
                $kunci = mb_strtolower($nama);
                if ($nama === '' || isset($sudah[$kunci])) {
                    continue;
                }
                $sudah[$kunci] = true;
                $baris[] = ['odp_name' => $nama, 'sto' => $o->sto, 'created_at' => now(), 'updated_at' => now()];
            }
            if ($baris) {
                DB::table('odps')->insert($baris);
            }
        }

        DB::statement('UPDATE monitorings SET odp_id = (SELECT id FROM odps WHERE odps.odp_name = monitorings.odp_name)');
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upload_batch_id');
            $table->dropConstrainedForeignId('odp_id');
            $table->dropColumn(['scorderno', 'homepass_id']);
        });
    }
};
