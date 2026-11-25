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
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->string('wonum', 30)->index();
            $table->string('crmordertype', 20)->nullable();
            $table->string('sto', 5)->nullable()->index();
            $table->string('datel', 30)->nullable();
            $table->string('package_name', 150)->nullable();
            $table->string('servicenum', 30)->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('latitude_ins', 30)->nullable();
            $table->string('longitude_ins', 30)->nullable();
            $table->dateTime('datecreated')->nullable();
            $table->dateTime('tgl_manja')->nullable();
            $table->string('status_survey', 20)->nullable();
            $table->string('amcrew', 30)->nullable();
            $table->string('chief_code', 20)->nullable();
            $table->string('chief_name', 100)->nullable();
            $table->string('status', 20)->nullable()->index();
            $table->dateTime('statusdate')->nullable();
            $table->string('errorcode_awal', 50)->nullable();
            $table->string('suberrorcode_awal', 50)->nullable();
            $table->text('engineermemo')->nullable();
            $table->string('odp_name', 100)->nullable();
            $table->string('product', 30)->nullable();
            $table->string('errorcode_akhir', 50)->nullable();
            $table->string('suberrorcode_akhir', 50)->nullable();
            $table->decimal('durasi_instalasi', 10, 2)->nullable();
            $table->decimal('durasi_tti', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};