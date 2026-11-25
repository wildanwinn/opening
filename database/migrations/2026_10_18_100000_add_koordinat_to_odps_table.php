<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master ODP: kunci = odp_name, dikelompokkan per STO, ditambah koordinat.
        Schema::table('odps', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('sto');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
