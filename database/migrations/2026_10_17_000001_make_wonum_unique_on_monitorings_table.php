<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buang duplikat dulu (simpan baris dengan id terkecil), baru pasang unique.
        $duplikat = DB::table('monitorings')
            ->select('wonum')
            ->groupBy('wonum')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('wonum');

        foreach ($duplikat as $wonum) {
            $simpan = DB::table('monitorings')->where('wonum', $wonum)->min('id');
            DB::table('monitorings')->where('wonum', $wonum)->where('id', '!=', $simpan)->delete();
        }

        Schema::table('monitorings', function (Blueprint $table) {
            $table->unique('wonum');
        });
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropUnique(['wonum']);
        });
    }
};
