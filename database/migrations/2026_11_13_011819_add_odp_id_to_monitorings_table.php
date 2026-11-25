<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('monitorings', 'odp_id')) {
            Schema::table('monitorings', function (Blueprint $table) {
                $table->foreignId('odp_id')->nullable()->after('sto')->constrained('odps')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('monitorings', 'odp_id')) {
            Schema::table('monitorings', function (Blueprint $table) {
                $table->dropForeign(['odp_id']);
                $table->dropColumn('odp_id');
            });
        }
    }
};