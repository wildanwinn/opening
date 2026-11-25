<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru untuk upload terpisah + relasi antar tabel:
 *  - odps.odp_name diganti nama_odp (supaya tidak sama dengan header KPRO / Data Master)
 *  - bima_orders (kunci: workorder), tsel_orders (order TSEL dari BIMA), kpro_orders (kunci: wonum)
 *  - upload_batches.jenis (master | odp | kpro | bima)
 * Relasi lewat kunci bisnis: WONUM = Workorder, odp_name (order) = nama_odp (ODP).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('odps', 'odp_name') && ! Schema::hasColumn('odps', 'nama_odp')) {
            Schema::table('odps', fn (Blueprint $t) => $t->renameColumn('odp_name', 'nama_odp'));
        }

        Schema::table('upload_batches', function (Blueprint $t) {
            $t->string('jenis', 10)->default('master')->after('nama_file');
        });

        Schema::create('bima_orders', function (Blueprint $t) {
            $t->string('workorder', 20)->primary();
            $t->dateTime('date_created')->nullable();
            $t->dateTime('date_modified')->nullable();
            $t->string('sc_order_no', 150)->nullable();
            $t->string('oss_order_id', 60)->nullable();
            $t->string('service_no', 60)->nullable();
            $t->string('description', 150)->nullable();
            $t->string('crm_order_type', 30)->nullable();
            $t->string('owner_group', 100)->nullable();
            $t->string('status', 20)->nullable()->index();
            $t->string('product_name', 150)->nullable();
            $t->text('address')->nullable();
            $t->string('customer_name', 150)->nullable();
            $t->string('workzone', 10)->nullable()->index();
            $t->string('region_site_id', 20)->nullable();
            $t->dateTime('status_date')->nullable();
            $t->dateTime('sched_start')->nullable();
            $t->string('contact_number', 60)->nullable();
            $t->text('measurement')->nullable();
            $t->dateTime('measurement_date')->nullable();
            $t->string('measurement_result', 30)->nullable();
            $t->string('wo_class', 30)->nullable();
            $t->text('no_kontrak')->nullable();
            $t->string('product_type', 30)->nullable();
            $t->dateTime('booking_date')->nullable();
            $t->string('booking_id', 60)->nullable();
            $t->string('area_tif', 40)->nullable();
            $t->string('district_tif', 40)->nullable();
            $t->string('regional_tif', 40)->nullable();
            $t->foreignId('upload_batch_id')->nullable()->constrained('upload_batches')->nullOnDelete();
            $t->timestamps();
        });

        // Hanya WO yang punya Order ID TSEL yang masuk tabel ini. Tidak ada di sini = non-TSEL.
        Schema::create('tsel_orders', function (Blueprint $t) {
            $t->id();
            $t->string('workorder', 20)->unique();
            $t->string('order_id_tsel', 60)->index();
            $t->string('channel_id_tsel', 60)->nullable();
            $t->timestamps();
            $t->foreign('workorder')->references('workorder')->on('bima_orders')->cascadeOnDelete();
        });

        Schema::create('kpro_orders', function (Blueprint $t) {
            $t->string('wonum', 20)->primary();
            $t->string('crmordertype', 150)->nullable();
            $t->string('regional_baru', 150)->nullable();
            $t->string('district_baru', 150)->nullable();
            $t->string('datel_baru', 150)->nullable();
            $t->string('sto', 150)->nullable();
            $t->string('regional_lama', 150)->nullable();
            $t->string('district_lama', 150)->nullable();
            $t->string('datel_lama', 150)->nullable();
            $t->text('scorderno')->nullable();
            $t->string('package_name', 150)->nullable();
            $t->string('servicenum', 150)->nullable();
            $t->string('ncli', 150)->nullable();
            $t->string('flag_order', 150)->nullable();
            $t->string('customer_name', 150)->nullable();
            $t->text('customer_address')->nullable();
            $t->string('no_hp', 150)->nullable();
            $t->string('latitude_ins', 150)->nullable();
            $t->string('longitude_ins', 150)->nullable();
            $t->dateTime('datecreated')->nullable();
            $t->dateTime('tgl_manja')->nullable();
            $t->string('status_survey', 150)->nullable();
            $t->text('desc_task')->nullable();
            $t->string('status_task', 150)->nullable();
            $t->dateTime('actstart')->nullable();
            $t->dateTime('schedule_labor')->nullable();
            $t->string('amcrew', 150)->nullable();
            $t->string('laborcode', 150)->nullable();
            $t->string('laborname', 150)->nullable();
            $t->string('chief_code', 150)->nullable();
            $t->string('chief_name', 150)->nullable();
            $t->string('status', 150)->nullable();
            $t->dateTime('statusdate')->nullable();
            $t->string('errorcode', 150)->nullable();
            $t->string('suberrorcode', 150)->nullable();
            $t->text('engineermemo')->nullable();
            $t->string('ownergroup', 150)->nullable();
            $t->text('measurement')->nullable();
            $t->string('odp_name', 150)->nullable();
            $t->text('catatan_teknisi')->nullable();
            $t->string('tiket_id', 150)->nullable();
            $t->string('product', 150)->nullable();
            $t->string('jenis_product', 150)->nullable();
            $t->string('status_approval', 150)->nullable();
            $t->string('status_odp', 150)->nullable();
            $t->string('new_reservationid', 150)->nullable();
            $t->dateTime('tanggal_approval')->nullable();
            $t->string('flag_wsa', 150)->nullable();
            $t->dateTime('tgl_pi_awal')->nullable();
            $t->dateTime('tgl_pi_akhir')->nullable();
            $t->dateTime('tgl_fallout_akhir')->nullable();
            $t->string('territory_tif', 150)->nullable();
            $t->string('regional_tif', 150)->nullable();
            $t->string('district_tif', 150)->nullable();
            $t->text('keterangan_pi')->nullable();
            $t->string('errorcode_akhir', 150)->nullable();
            $t->string('suberrorcode_akhir', 150)->nullable();
            $t->text('engineermemo_akhir')->nullable();
            $t->string('flag_pda', 150)->nullable();
            $t->string('homepassid', 150)->nullable();
            $t->dateTime('tgl_fallout_awal')->nullable();
            $t->string('long_inst_ut', 150)->nullable();
            $t->string('lat_inst_ut', 150)->nullable();
            $t->string('long_retag_ut', 150)->nullable();
            $t->string('lat_retag_ut', 150)->nullable();
            $t->dateTime('start_date_pull_dropcore_akhir')->nullable();
            $t->dateTime('comp_date_testing_broadband')->nullable();
            $t->decimal('durasi_instalasi', 12, 2)->nullable();
            $t->decimal('durasi_tti', 12, 2)->nullable();
            $t->foreignId('upload_batch_id')->nullable()->constrained('upload_batches')->nullOnDelete();
            $t->timestamps();
            $t->index('sto');
            $t->index('odp_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpro_orders');
        Schema::dropIfExists('tsel_orders');
        Schema::dropIfExists('bima_orders');
        Schema::table('upload_batches', fn (Blueprint $t) => $t->dropColumn('jenis'));
        if (Schema::hasColumn('odps', 'nama_odp')) {
            Schema::table('odps', fn (Blueprint $t) => $t->renameColumn('nama_odp', 'odp_name'));
        }
    }
};
