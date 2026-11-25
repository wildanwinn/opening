<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Odp;
use App\Models\Sto;
use App\Models\User;
use App\Services\MonitoringRowMapper;
use App\Services\Upload\Skema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OdpDanUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['nik' => '1', 'nama' => 'Admin', 'password' => 'x', 'role' => 'admin']);
    }

    public function test_upload_ulang_hanya_update_parameter_tertentu(): void
    {
        $m = Monitoring::create([
            'wonum' => 'W1', 'customer_name' => 'ASLI', 'chief_name' => 'BUDI', 'engineermemo' => 'memo web',
            'status' => 'STARTWORK', 'statusdate' => '2026-10-01 10:00:00', 'errorcode_akhir' => null,
            'scorderno' => null,
        ]);

        (new MonitoringRowMapper)->simpan([
            'wonum' => 'W1', 'customer_name' => 'BARU', 'chief_name' => 'ANDI', 'engineermemo' => 'memo excel',
            'status' => 'COMPWORK', 'statusdate' => '2026-10-05 10:00:00', 'errorcode_akhir' => 'E1',
            'scorderno' => 'SC9', 'durasi_tti' => 3.5,
        ]);

        $m->refresh();
        $this->assertSame('ASLI', $m->customer_name);       // tidak ditimpa
        $this->assertSame('BUDI', $m->chief_name);           // tidak ditimpa
        $this->assertSame('memo web', $m->engineermemo);     // tidak ditimpa
        $this->assertSame('COMPWORK', $m->status);           // tanggal lebih baru
        $this->assertSame('E1', $m->errorcode_akhir);        // parameter boleh update
        $this->assertSame('SC9', $m->scorderno);             // kosong -> terisi

        (new MonitoringRowMapper)->simpan([
            'wonum' => 'W1', 'status' => 'WORKFAIL', 'statusdate' => '2026-10-02 10:00:00', 'scorderno' => 'SC-LAIN',
        ]);
        $m->refresh();
        $this->assertSame('COMPWORK', $m->status);           // tanggal lebih lama diabaikan
        $this->assertSame('SC9', $m->scorderno);            // sudah terisi, tidak ditimpa
    }

    /** CSV ODP dengan seluruh header resmi (baris: [sto, nama_odp, lat, long]). */
    private function csvOdp(array $baris, string $kolomNama = 'NAMA_ODP'): string
    {
        $head = array_map(fn ($h) => $h === 'NAMA_ODP' ? $kolomNama : $h, Skema::HEADER_ODP);
        $out = implode(',', $head) . "\n";
        foreach ($baris as [$sto, $nama, $lat, $lng]) {
            $r = array_fill(0, count($head), '');
            $r[array_search('STO', Skema::HEADER_ODP)] = $sto;
            $r[array_search('NAMA_ODP', Skema::HEADER_ODP)] = $nama;
            $r[array_search('LATITUDE', Skema::HEADER_ODP)] = $lat;
            $r[array_search('LONGITUDE', Skema::HEADER_ODP)] = $lng;
            $out .= implode(',', array_map(fn ($v) => str_contains($v, ',') ? '"' . $v . '"' : $v, $r)) . "\n";
        }

        return $out;
    }

    public function test_upload_odp_menambah_memperbarui_dan_memasangkan_order(): void
    {
        Sto::create(['kode_sto' => 'GBG', 'nama_sto' => 'Gubeng', 'wilayah' => 'SBY']);
        $order = Monitoring::create(['wonum' => 'W2', 'odp_name' => 'ODP-GBG-FA/001']);
        Odp::create(['nama_odp' => 'ODP-GBG-FA/002']);

        $csv = $this->csvOdp([
            ['GBG', 'ODP-GBG-FA/001', '-7.2575', '112.7521'],
            ['gbg', 'ODP-GBG-FA/002', '-7,30', 'abc'],
            ['XXX', 'ODP-NEW', '1', '2'],
        ]);

        $this->actingAs($this->admin())
            ->post(route('odp.import'), ['file' => [UploadedFile::fake()->createWithContent('odp.csv', $csv)]])
            ->assertSessionHas('sukses');

        $this->assertSame(3, Odp::count());
        $o1 = Odp::where('nama_odp', 'ODP-GBG-FA/001')->first();
        $this->assertSame('GBG', $o1->sto);
        $this->assertEqualsWithDelta(-7.2575, (float) $o1->latitude, 0.00001);
        $o2 = Odp::where('nama_odp', 'ODP-GBG-FA/002')->first();
        $this->assertEqualsWithDelta(-7.30, (float) $o2->latitude, 0.00001);
        $this->assertNull($o2->longitude);
        $this->assertSame('XXX', Odp::where('nama_odp', 'ODP-NEW')->first()->sto);
        $this->assertSame($o1->id, $order->fresh()->odp->id); // relasi lewat nama
    }

    public function test_file_odp_dengan_header_odp_name_diterima(): void
    {
        $csv = $this->csvOdp([['GBG', 'ODP-A', '-7', '112']], 'ODP_NAME');
        $this->actingAs($this->admin())
            ->post(route('odp.import'), ['file' => [UploadedFile::fake()->createWithContent('odp.csv', $csv)]])
            ->assertSessionHas('sukses');
        $this->assertSame(1, Odp::count());
        $this->assertSame('ODP-A', Odp::first()->nama_odp);
    }

    public function test_file_bima_dan_kpro_tidak_bisa_masuk_ke_odp(): void
    {
        $bima = implode(',', array_map(fn ($h) => '"' . $h . '"', Skema::HEADER_BIMA)) . "\nx\n";
        $kpro = implode(',', Skema::HEADER_KPRO) . "\nx\n";
        $admin = $this->admin();
        foreach ([$bima, $kpro] as $isi) {
            $this->actingAs($admin)
                ->post(route('odp.import'), ['file' => [UploadedFile::fake()->createWithContent('x.csv', $isi)]])
                ->assertSessionHas('error');
        }
        $this->assertSame(0, Odp::count());
    }

    public function test_template_odp_bisa_diunduh(): void
    {
        $this->actingAs($this->admin())->get(route('odp.template'))->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_halaman_odp_dan_modal_edit_tampil(): void
    {
        Odp::create(['nama_odp' => 'ODP-X', 'latitude' => -7.25, 'longitude' => 112.75]);
        $m   = Monitoring::create(['wonum' => 'W3', 'odp_name' => 'ODP-X']);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('odp.index'))
            ->assertOk()->assertSee('Upload Excel ODP')->assertSee('-7.25');

        $this->actingAs($admin)->get(route('monitoring.show', $m), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Lat ODP')->assertSee('-7.25')->assertSee('112.75')->assertSee('ODP VALID');
    }

    public function test_upload_file_html_bertipe_xls_dan_zip(): void
    {
        $html = '<table border="1"><thead><tr><th>REGIONAL</th><th>WITEL</th><th>DATEL</th><th>STO</th><th>STO_DESC</th><th>NOSS_ID</th><th>ODP_INDEX</th><th>NAMA_ODP</th><th>LATITUDE</th><th>LONGITUDE</th><th>CLUSNAME</th><th>CLUSTERSATATUS</th><th>AVAI</th><th>USED</th><th>RSV</th><th>RSK</th><th>IS_TOTAL</th><th>ODP_INFO</th><th>UPDATE_DATE</th><th>PERIODE</th><th>STATUS</th></tr></thead><tbody>'
            . '<tr><td>Regional 4</td><td>PEKALONGAN</td><td>BATANG</td><td>BTG</td><td>BATANG</td><td>7883968608</td><td>FN/D08/104.01</td><td>ODP-BTG-FN/104</td><td>-6.977208410069741</td><td>109.7971839693193</td><td> </td><td> </td><td>8</td><td>2</td><td>0</td><td>0</td><td>8</td><td></td><td>2026-09-15 12:11:23</td><td>20260930</td><td>BLACK</td></tr></tbody></table>';

        $zipPath = tempnam(sys_get_temp_dir(), 'z');
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        $zip->addFromString('ODP/Detil_BLACK_BTG.xls', $html);
        $zip->close();

        $order = Monitoring::create(['wonum' => 'W9', 'odp_name' => 'ODP-BTG-FN/104']);

        $this->actingAs($this->admin())->post(route('odp.import'), [
            'file' => [
                UploadedFile::fake()->createWithContent('Detil_BLACK_X.xls', $html),
                new UploadedFile($zipPath, 'odp.zip', null, null, true),
            ],
        ])->assertSessionHas('sukses');

        $o = Odp::where('nama_odp', 'ODP-BTG-FN/104')->first();
        $this->assertSame(1, Odp::count());
        $this->assertSame('BATANG', $o->datel);
        $this->assertSame('BLACK', $o->status);
        $this->assertSame(2, $o->used);
        $this->assertSame('2026-09-15 12:11:23', $o->update_date->format('Y-m-d H:i:s'));
        $this->assertSame($o->id, $order->fresh()->odp->id);
    }

    public function test_dashboard_tampil_dengan_maskot(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->assertSee('maskot-kucing', false);
    }

    public function test_potong_scorderno_dan_nama_odp(): void
    {
        $this->assertSame('AOi42608311154304937e6ea0', MonitoringRowMapper::kodeScorderno('DGPS260831235430003837213-AOi42608311154304937e6ea0_97061045-732809048~2026090100100342403318~42403318~141760726~3~WSA'));
        $this->assertSame('LP1092026', MonitoringRowMapper::kodeScorderno('LP1092026'));
        $this->assertSame('MOk42607031123195073b7fc0', MonitoringRowMapper::kodeScorderno('14VSWXBTLBQ51OMKGMB3XXH0F-MOk42607031123195073b7fc0_87368161-647894726~2026070311240139311757~39311757~131177630~3~WSA'));
        $this->assertSame('801Mg00000yDJGpIAO', MonitoringRowMapper::kodeScorderno('801Mg00000yDJGpIAO_001Mg00000xxd8JIAQ_802Mg00000VIBrCIAX_TSQ'));
        $this->assertSame('1-68683446374', MonitoringRowMapper::kodeScorderno('1-68683446374_1-VHRKT66_1-VJWCME0_DELIVER'));
        $this->assertNull(MonitoringRowMapper::kodeScorderno('  '));
        $this->assertSame('ODP-TEG-FAK/134', MonitoringRowMapper::namaOdp('ODP-TEG-FAK/134 FAK/D03/134.01'));
    }

    public function test_upload_order_tidak_membuat_odp_palsu(): void
    {
        (new MonitoringRowMapper)->simpan(['wonum' => 'W7', 'odp_name' => 'ODP-BARU-1']);
        $this->assertSame(0, Odp::count());
        $this->assertNull(Monitoring::where('wonum', 'W7')->first()->odp);

        Odp::create(['nama_odp' => 'ODP-BARU-1', 'latitude' => -7.1, 'longitude' => 110.1]);
        $this->assertNotNull(Monitoring::where('wonum', 'W7')->first()->odp);
    }
}
