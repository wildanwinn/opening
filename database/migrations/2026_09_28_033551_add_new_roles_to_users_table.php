<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','staff','teknisi','edp','tsel','tiv','service_area','fall_star') NOT NULL DEFAULT 'staff'");
            return;
        }

        // SQLite / lainnya: enum jadi CHECK constraint, jadi ubah ke string biasa.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->change();
        });
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','staff','teknisi') NOT NULL DEFAULT 'staff'");
        }
    }
};
