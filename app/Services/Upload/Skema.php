<?php

namespace App\Services\Upload;

use App\Services\OdpFileReader;

/**
 * Skema 4 jenis upload. Tiap jenis punya daftar header yang BOLEH, satu kolom kunci (primary key),
 * dan batas kecocokan minimal. Dipakai PemeriksaHeader untuk menolak file yang salah jenis
 * (mis. file BIMA yang diupload sebagai ODP) atau yang menambah header baru.
 */
final class Skema
{
    public const JENIS = ['master', 'odp', 'kpro', 'bima'];

    public const HEADER_MASTER = [
        'NO',
        'WONUM',
        'CRMORDERTYPE',
        'REGIONAL BARU',
        'DISTRICT BARU',
        'DATEL BARU',
        'STO',
        'REGIONAL LAMA',
        'DISTRICT LAMA',
        'DATEL LAMA',
        'SCORDERNO',
        'PACKAGE_NAME',
        'SERVICENUM',
        'NCLI',
        'FLAG_ORDER',
        'CUSTOMER_NAME',
        'CUSTOMER_ADDRESS',
        'NO_HP',
        'LATITUDE_INS',
        'LONGITUDE_INS',
        'DATECREATED',
        'TGL_MANJA',
        'STATUS_SURVEY',
        'DESC_TASK',
        'STATUS_TASK',
        'ACTSTART',
        'SCHEDULE_LABOR',
        'AMCREW',
        'LABORCODE',
        'LABORNAME',
        'CHIEF_CODE',
        'CHIEF_NAME',
        'STATUS',
        'STATUSDATE',
        'ERRORCODE_AWAL',
        'SUBERRORCODE_AWAL',
        'ENGINEERMEMO',
        'OWNERGROUP',
        'MEASUREMENT',
        'ODP_NAME',
        'CATATAN_TEKNISI',
        'TIKET_ID',
        'PRODUCT',
        'JENIS_PRODUCT',
        'FLAG_WSA',
        'TGL_PI_AWAL',
        'TGL_PI_AKHIR',
        'TGL_FALLOUT_AWAL',
        'TGL_FALLOUT_AKHIR',
        'TERRITORY_TIF',
        'DISTRICT_TIF',
        'KETERANGAN_PI',
        'FLAG_PDA',
        'WSA_RE',
        'ERRORCODE_AKHIR',
        'SUBERRORCODE_AKHIR',
        'FLAG_HOMEPASSID',
        'LONG_INST_UT',
        'LAT_INST_UT',
        'LONG_RETAG_UT',
        'LAT_RETAG_UT',
        'START_DATE_PULL_DROPCORE_AKHIR',
        'COMP_DATE_TESTING_BROADBAND',
        'DURASI_INSTALASI',
        'DURASI_TTI',
    ];
    public const HEADER_KPRO = [
        'NO',
        'WONUM',
        'CRMORDERTYPE',
        'REGIONAL BARU',
        'DISTRICT BARU',
        'DATEL BARU',
        'STO',
        'REGIONAL LAMA',
        'DISTRICT LAMA',
        'DATEL LAMA',
        'SCORDERNO',
        'PACKAGE_NAME',
        'SERVICENUM',
        'NCLI',
        'FLAG_ORDER',
        'CUSTOMER_NAME',
        'CUSTOMER_ADDRESS',
        'NO_HP',
        'LATITUDE_INS',
        'LONGITUDE_INS',
        'DATECREATED',
        'TGL_MANJA',
        'STATUS_SURVEY',
        'DESC_TASK',
        'STATUS_TASK',
        'ACTSTART',
        'SCHEDULE_LABOR',
        'AMCREW',
        'LABORCODE',
        'LABORNAME',
        'CHIEF_CODE',
        'CHIEF_NAME',
        'STATUS',
        'STATUSDATE',
        'ERRORCODE',
        'SUBERRORCODE',
        'ENGINEERMEMO',
        'OWNERGROUP',
        'MEASUREMENT',
        'ODP_NAME',
        'CATATAN_TEKNISI',
        'TIKET_ID',
        'PRODUCT',
        'JENIS_PRODUCT',
        'STATUS_APPROVAL',
        'STATUS_ODP',
        'NEW_RESERVATIONID',
        'TANGGAL_APPROVAL',
        'FLAG_WSA',
        'TGL_PI_AWAL',
        'TGL_PI_AKHIR',
        'TGL_FALLOUT_AKHIR',
        'TERRITORY_TIF',
        'REGIONAL_TIF',
        'DISTRICT_TIF',
        'KETERANGAN_PI',
        'ERRORCODE_AKHIR',
        'SUBERRORCODE_AKHIR',
        'ENGINEERMEMO_AKHIR',
        'FLAG_PDA',
        'HOMEPASSID',
        'TGL_FALLOUT_AWAL',
        'LONG_INST_UT',
        'LAT_INST_UT',
        'LONG_RETAG_UT',
        'LAT_RETAG_UT',
        'START_DATE_PULL_DROPCORE_AKHIR',
        'COMP_DATE_TESTING_BROADBAND',
        'DURASI_INSTALASI',
        'DURASI_TTI',
    ];
    public const HEADER_BIMA = [
        'Date Created',
        'Date Modified',
        'Workorder',
        'SC Order No/Track ID/CSRM No',
        'OSS Order ID',
        'Service No.',
        'Description',
        'CRM Order Type',
        'Owner Group',
        'Status',
        'Product Name',
        'Address',
        'Customer Name',
        'Workzone',
        'Region/Site ID',
        'Status Date',
        'Sched Start',
        'Contact Number',
        'Measurement',
        'Measurement Date',
        'Measurement Result',
        'WO Class',
        'No. Kontrak(KB/KL/P8)',
        'Product Type',
        'Booking Date',
        'Booking ID',
        'Area TIF',
        'District TIF',
        'Regional TIF',
        'Channel ID TSEL',
        'Order ID TSEL',
    ];
    public const HEADER_ODP = [
        'REGIONAL',
        'WITEL',
        'DATEL',
        'STO',
        'STO_DESC',
        'NOSS_ID',
        'ODP_INDEX',
        'NAMA_ODP',
        'LATITUDE',
        'LONGITUDE',
        'CLUSNAME',
        'CLUSTERSATATUS',
        'AVAI',
        'USED',
        'RSV',
        'RSK',
        'IS_TOTAL',
        'ODP_INFO',
        'UPDATE_DATE',
        'PERIODE',
        'STATUS',
    ];

    /**
     * Kolom ciri khas: hanya ada di salah satu dari Data Master / KPRO (keduanya berbagi 61 header).
     * File harus memuat minimal sekian kolom ini, supaya file yang cuma berisi header bersama
     * tidak bisa masuk ke dua menu sekaligus.
     */
    public const CIRI_KHAS = [
        'master' => ['kolom' => ['errorcode_awal', 'suberrorcode_awal', 'flag_homepassid', 'wsa_re'], 'minimal' => 2],
        'kpro'   => ['kolom' => ['errorcode', 'suberrorcode', 'status_approval', 'status_odp', 'new_reservationid',
                                 'tanggal_approval', 'homepassid', 'engineermemo_akhir', 'regional_tif'], 'minimal' => 5],
    ];

    /** Pemetaan header BIMA => kolom tabel bima_orders. Order ID/Channel ID TSEL masuk tabel tsel_orders. */
    public const KOLOM_BIMA = [
        'Date Created' => 'date_created', 'Date Modified' => 'date_modified', 'Workorder' => 'workorder',
        'SC Order No/Track ID/CSRM No' => 'sc_order_no', 'OSS Order ID' => 'oss_order_id', 'Service No.' => 'service_no',
        'Description' => 'description', 'CRM Order Type' => 'crm_order_type', 'Owner Group' => 'owner_group',
        'Status' => 'status', 'Product Name' => 'product_name', 'Address' => 'address', 'Customer Name' => 'customer_name',
        'Workzone' => 'workzone', 'Region/Site ID' => 'region_site_id', 'Status Date' => 'status_date',
        'Sched Start' => 'sched_start', 'Contact Number' => 'contact_number', 'Measurement' => 'measurement',
        'Measurement Date' => 'measurement_date', 'Measurement Result' => 'measurement_result', 'WO Class' => 'wo_class',
        'No. Kontrak(KB/KL/P8)' => 'no_kontrak', 'Product Type' => 'product_type', 'Booking Date' => 'booking_date',
        'Booking ID' => 'booking_id', 'Area TIF' => 'area_tif', 'District TIF' => 'district_tif', 'Regional TIF' => 'regional_tif',
    ];

    public const TANGGAL_BIMA = ['date_created', 'date_modified', 'status_date', 'sched_start', 'measurement_date', 'booking_date'];

    /** Kolom KPRO bertipe teks panjang / tanggal / angka (sisanya string biasa). */
    public const KPRO_TEKS = ['scorderno', 'customer_address', 'engineermemo', 'engineermemo_akhir', 'measurement', 'catatan_teknisi', 'desc_task', 'keterangan_pi'];
    public const KPRO_TANGGAL = ['datecreated', 'tgl_manja', 'actstart', 'schedule_labor', 'statusdate', 'tanggal_approval', 'tgl_pi_awal', 'tgl_pi_akhir', 'tgl_fallout_akhir', 'tgl_fallout_awal', 'start_date_pull_dropcore_akhir', 'comp_date_testing_broadband'];
    public const KPRO_ANGKA = ['durasi_instalasi', 'durasi_tti'];

    /** @return array{label:string, kunci:string, header:array, minimal:float, tabel:string, ringkas:string} */
    public static function definisi(string $jenis): array
    {
        return match ($jenis) {
            'master' => ['label' => 'Data Master', 'kunci' => 'wonum', 'header' => self::HEADER_MASTER, 'minimal' => 0.7, 'tabel' => 'monitorings',
                'ringkas' => 'File DATA MASTER (65 kolom, kunci WONUM)'],
            'odp'    => ['label' => 'Data ODP', 'kunci' => 'nama_odp', 'header' => self::HEADER_ODP, 'minimal' => 0.8, 'tabel' => 'odps',
                'ringkas' => 'File ODP (21 kolom, kunci NAMA_ODP)'],
            'kpro'   => ['label' => 'KPRO (Fulfillment)', 'kunci' => 'wonum', 'header' => self::HEADER_KPRO, 'minimal' => 0.7, 'tabel' => 'kpro_orders',
                'ringkas' => 'File KPRO / report fulfillment end state (70 kolom, kunci WONUM)'],
            'bima'   => ['label' => 'BIMA (Export)', 'kunci' => 'workorder', 'header' => self::HEADER_BIMA, 'minimal' => 0.8, 'tabel' => 'bima_orders',
                'ringkas' => 'File BIMA export (31 kolom, kunci Workorder)'],
        };
    }

    public static function slugs(string $jenis): array
    {
        return array_map([OdpFileReader::class, 'slug'], self::definisi($jenis)['header']);
    }
}
