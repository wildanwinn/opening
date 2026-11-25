<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Header master ODP mengikuti file ODP dari sumber (Detil_<STATUS>_Regional 4_<WITEL>_<DATEL>_<STO>.xls). */
    public function up(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->string('regional', 50)->nullable()->after('id');
            $table->string('witel', 50)->nullable()->after('regional');
            $table->string('datel', 50)->nullable()->after('witel');
            $table->string('sto_desc', 100)->nullable()->after('sto');
            $table->string('noss_id', 30)->nullable()->after('sto_desc');
            $table->string('odp_index', 60)->nullable()->after('noss_id');
            $table->string('clusname', 100)->nullable();
            $table->string('clusterstatus', 50)->nullable();
            $table->unsignedInteger('avai')->nullable();
            $table->unsignedInteger('used')->nullable();
            $table->unsignedInteger('rsv')->nullable();
            $table->unsignedInteger('rsk')->nullable();
            $table->unsignedInteger('is_total')->nullable();
            $table->text('odp_info')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->string('periode', 10)->nullable();
            $table->string('status', 20)->nullable()->index(); // warna kapasitas: GREEN/YELLOW/RED/BLACK/BLACKSYSTEM
        });

        // koordinat di file sumber punya ~15 digit di belakang koma
        Schema::table('odps', function (Blueprint $table) {
            $table->decimal('latitude', 18, 14)->nullable()->change();
            $table->decimal('longitude', 18, 14)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn([
                'regional', 'witel', 'datel', 'sto_desc', 'noss_id', 'odp_index', 'clusname', 'clusterstatus',
                'avai', 'used', 'rsv', 'rsk', 'is_total', 'odp_info', 'update_date', 'periode', 'status',
            ]);
        });
    }
};
