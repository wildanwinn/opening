<?php

namespace Tests\Feature;

use App\Models\BimaOrder;
use App\Models\KproOrder;
use App\Models\Monitoring;
use App\Models\TselOrder;
use App\Models\User;
use App\Services\Upload\Skema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadBimaKproTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['nik' => '1', 'nama' => 'Admin', 'password' => 'x', 'role' => 'admin']);
    }

    private function csv(array $header, array $baris): string
    {
        $q = fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"';
        $out = implode(',', array_map($q, $header)) . "\n";
        foreach ($baris as $b) {
            $out .= implode(',', array_map(fn ($h) => $q($b[$h] ?? ''), $header)) . "\n";
        }

        return $out;
    }

    private function unggah(User $u, string $jenis, string $isi)
    {
        return $this->actingAs($u)->postJson(route('upload.preview', $jenis), [
            'file' => UploadedFile::fake()->createWithContent("$jenis.csv", $isi),
        ]);
    }

    public function test_upload_bima_tsel_terpisah_dan_semua_wo_muncul_di_monitoring(): void
    {
        $admin = $this->admin();
        $isi = $this->csv(Skema::HEADER_BIMA, [
            ['Workorder' => 'WO1', 'SC Order No/Track ID/CSRM No' => 'DGPS1-AOi99_103-794~WSA', 'Status' => 'COMPWORK', 'Workzone' => 'PKL', 'Customer Name' => 'A', 'Status Date' => '2026-09-28 14:20:01',
                'Order ID TSEL' => 'TSEL-1', 'Channel ID TSEL' => 'CH1'],
            ['Workorder' => 'WO2', 'SC Order No/Track ID/CSRM No' => '1-6868_1-VHRKT66_DELIVER', 'Status' => 'COMPLETE', 'Workzone' => 'KTM', 'Customer Name' => 'B'],
            ['Workorder' => 'WO1', 'Status' => 'STARTWORK'], // kunci kembar di file: dilewati
        ]);

        $r = $this->unggah($admin, "bima", $isi)->assertOk()->assertJsonFragment(['valid' => 2, 'dobel' => 1])->json();
        $this->actingAs($admin)->postJson($r['url_proses'])->assertOk()->assertJsonFragment(['selesai' => true, 'baru' => 2]);

        $this->assertSame(2, BimaOrder::count());
        $this->assertSame('AOi99', BimaOrder::find('WO1')->sc_order_no);       // hanya kode yang disimpan
        $this->assertSame('1-6868', BimaOrder::find('WO2')->sc_order_no);
        $this->assertSame(1, TselOrder::count());                    // hanya yang punya Order ID TSEL
        $this->assertSame('TSEL-1', TselOrder::first()->order_id_tsel);
        $this->assertSame(2, Monitoring::count());                   // semua WO BIMA tampil di Data Monitoring
        $this->assertSame('COMPWORK', Monitoring::where('wonum', 'WO1')->first()->status_tampil);
        $this->assertNotNull(Monitoring::where('wonum', 'WO1')->first()->tsel);
        $this->assertNull(Monitoring::where('wonum', 'WO2')->first()->tsel);

        // status di Data Monitoring mengikuti BIMA walaupun status order sendiri berbeda
        Monitoring::where('wonum', 'WO1')->update(['status' => 'STARTWORK']);
        $this->assertSame('COMPWORK', Monitoring::where('wonum', 'WO1')->first()->status_tampil);
        $this->actingAs($admin)->get(route('monitoring.index', ['status' => 'COMPWORK']))->assertOk()->assertSee('WO1');
    }

    public function test_upload_kpro_per_wonum_dan_validasi_header(): void
    {
        $admin = $this->admin();
        $isi = $this->csv(Skema::HEADER_KPRO, [['WONUM' => 'WO10', 'STO' => 'TEG', 'CUSTOMER_NAME' => 'X', 'DURASI_TTI' => '3,5']]);
        $r = $this->unggah($admin, 'kpro', $isi)->assertOk()->json();
        $this->actingAs($admin)->postJson($r['url_proses'])->assertJsonFragment(['selesai' => true]);
        $this->assertSame('WO10', KproOrder::first()->wonum);

        // header tambahan ditolak
        $this->unggah($admin, 'kpro', $this->csv(array_merge(Skema::HEADER_KPRO, ['KOLOM_BARU']), [['WONUM' => 'W']]))
            ->assertStatus(422)->assertJsonPath('message', 'File ditolak, bukan format KPRO (Fulfillment).');

        // kolom kunci hilang ditolak
        $tanpa = array_values(array_diff(Skema::HEADER_KPRO, ['WONUM']));
        $this->unggah($admin, 'kpro', $this->csv($tanpa, [['STO' => 'TEG']]))->assertStatus(422);
    }

    public function test_file_salah_jenis_ditolak_di_setiap_menu(): void
    {
        $admin = $this->admin();
        $bima = $this->csv(Skema::HEADER_BIMA, [['Workorder' => 'W1']]);
        $kpro = $this->csv(Skema::HEADER_KPRO, [['WONUM' => 'W1']]);

        $this->unggah($admin, 'kpro', $bima)->assertStatus(422);
        $this->unggah($admin, 'bima', $kpro)->assertStatus(422);
        $this->unggah($admin, 'master', $kpro)->assertStatus(422)->assertJsonFragment(['message' => 'File ditolak, bukan format Data Master.']);
        $this->assertSame(0, Monitoring::count() + BimaOrder::count() + KproOrder::count());
    }

    public function test_halaman_upload_dan_hanya_admin(): void
    {
        $this->actingAs($this->admin())->get(route('monitoring.upload', ['jenis' => 'bima']))->assertOk()->assertSee('BIMA');
        $hd = User::create(['nik' => '2', 'nama' => 'HD', 'password' => 'x', 'role' => 'hd']);
        $this->actingAs($hd)->get(route('monitoring.upload'))->assertForbidden();
    }

    public function test_tombol_send_memberi_tugas_ke_teknisi(): void
    {
        $hd = User::create(['nik' => '2', 'nama' => 'HD', 'password' => 'x', 'role' => 'hd']);
        $t  = User::create(['nik' => '3', 'nama' => 'Dedi', 'kode_teknisi' => 'T01', 'password' => 'x', 'role' => 'teknisi']);
        $m  = Monitoring::create(['wonum' => 'WO5', 'sto' => 'TEG', 'odp_name' => 'ODP-1']);

        $this->actingAs($hd)->get(route('monitoring.index'))->assertOk()->assertSee('Send');
        $this->actingAs($hd)->post(route('monitoring.assign', $m), ['teknisi_id' => $t->id])->assertSessionHas('sukses');

        $m->refresh();
        $this->assertSame('Dedi', $m->chief_name);
        $this->assertSame('T01', $m->chief_code);
        $this->actingAs($t)->get('/tugas')->assertOk()->assertSee('ODP-1');

        $this->actingAs($t)->post(route('monitoring.assign', $m), ['teknisi_id' => $t->id])->assertForbidden();
    }

    public function test_nama_role_baru(): void
    {
        foreach (['hd', 'tif', 'oc3'] as $i => $role) {
            $u = User::create(['nik' => 'r' . $i, 'nama' => $role, 'password' => 'x', 'role' => $role]);
            $this->assertSame($role, $u->fresh()->role);
        }
    }
}
