<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kolom header kuning di Excel DATA MASTER yang belum ada di database. */
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->string('long_inst_ut', 30)->nullable()->after('longitude_ins');
            $table->string('lat_inst_ut', 30)->nullable()->after('long_inst_ut');
        });
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn(['long_inst_ut', 'lat_inst_ut']);
        });
    }
};
