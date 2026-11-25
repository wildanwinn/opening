<?php
// Nama file saat dibuat: php artisan make:migration add_kode_teknisi_to_users_table
// Isi method up() dan down() dengan ini:

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kode_teknisi', 30)->nullable()->unique()->after('nik');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kode_teknisi');
        });
    }
};