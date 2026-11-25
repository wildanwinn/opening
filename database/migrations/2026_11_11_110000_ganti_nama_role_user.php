<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Ganti nama role: staff -> hd, tiv -> tif, fall_star -> oc3. */
return new class extends Migration
{
    private const PETA = ['staff' => 'hd', 'tiv' => 'tif', 'fall_star' => 'oc3'];

    public function up(): void
    {
        $this->ganti(self::PETA,
            "ENUM('admin','staff','teknisi','edp','tsel','tiv','service_area','fall_star','hd','tif','oc3')",
            "ENUM('admin','hd','teknisi','edp','tsel','tif','service_area','oc3')", 'hd');
    }

    public function down(): void
    {
        $this->ganti(array_flip(self::PETA),
            "ENUM('admin','staff','teknisi','edp','tsel','tiv','service_area','fall_star','hd','tif','oc3')",
            "ENUM('admin','staff','teknisi','edp','tsel','tiv','service_area','fall_star')", 'staff');
    }

    private function ganti(array $peta, string $enumLonggar, string $enumAkhir, string $default): void
    {
        $mysql = in_array(DB::getDriverName(), ['mysql', 'mariadb']);

        if ($mysql) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role {$enumLonggar} NOT NULL DEFAULT '{$default}'");
        } else {
            Schema::table('users', fn (Blueprint $t) => $t->string('role', 20)->default($default)->change());
        }

        foreach ($peta as $lama => $baru) {
            DB::table('users')->where('role', $lama)->update(['role' => $baru]);
        }

        if ($mysql) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role {$enumAkhir} NOT NULL DEFAULT '{$default}'");
        }
    }
};
