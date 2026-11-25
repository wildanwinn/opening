<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TugasTeknisiTest extends TestCase
{
    use RefreshDatabase;

    public function test_alur_mulai_pre_jalan_selesai(): void
    {
        Storage::fake('local');
        $t = User::create(['nik' => '2', 'nama' => 'Dedi Irawan', 'kode_teknisi' => 'T01', 'password' => 'x', 'role' => 'teknisi']);
        $m = Monitoring::create(['wonum' => 'WO1', 'sto' => 'TEG', 'odp_name' => 'ODP-01', 'chief_code' => 'T01', 'chief_name' => 'Dedi Irawan']);
        $mat = Material::first();

        $this->actingAs($t)->get('/dashboard')->assertRedirect('/tugas');
        $this->actingAs($t)->get('/tugas')->assertOk()->assertSee('ODP-01')->assertSee('Mulai');

        $this->actingAs($t)->post("/tugas/{$m->id}/mulai")->assertRedirect("/tugas/{$m->id}/pre");
        $this->assertSame('STARTWORK', $m->fresh()->status);

        // foto kurang dari 2 -> ditolak
        $this->actingAs($t)->post("/tugas/{$m->id}/pre", ['qty' => [$mat->id => 5], 'foto' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertSessionHas('error');
        $this->assertSame('pre', $m->fresh()->tahap);

        $this->actingAs($t)->post("/tugas/{$m->id}/pre", ['foto' => [UploadedFile::fake()->image('b.jpg')]])
            ->assertRedirect("/tugas/{$m->id}/lokasi");
        $this->assertSame('CONTWORK', $m->fresh()->status);
        $this->assertSame(5, $m->pekerjaanMaterials()->first()->qty);

        $this->actingAs($t)->get("/tugas/{$m->id}/lokasi")->assertOk();
        $this->actingAs($t)->get("/tugas/{$m->id}/selesai")->assertOk();

        $this->actingAs($t)->post("/tugas/{$m->id}/selesai", ['catatan' => 'ok'])->assertSessionHas('error');
        $this->actingAs($t)->post("/tugas/{$m->id}/selesai", ['catatan' => 'ok', 'foto' => [UploadedFile::fake()->image('c.jpg')]])
            ->assertRedirect('/tugas');
        $m->refresh();
        $this->assertSame('COMPWORK', $m->status);
        $this->assertSame('selesai', $m->tahap);

        $foto = $m->fotos()->first();
        $this->actingAs($t)->get("/foto/{$foto->id}")->assertOk();
    }

    public function test_teknisi_tidak_bisa_buka_tugas_orang_lain(): void
    {
        $t = User::create(['nik' => '2', 'nama' => 'Dedi', 'kode_teknisi' => 'T01', 'password' => 'x', 'role' => 'teknisi']);
        $lain = Monitoring::create(['wonum' => 'WO9', 'sto' => 'TEG', 'chief_code' => 'T99', 'chief_name' => 'Lain']);

        $this->actingAs($t)->post("/tugas/{$lain->id}/mulai")->assertNotFound();
    }

    public function test_perintah_tugas_demo_memberi_order(): void
    {
        $t = User::create(['nik' => '24040075', 'nama' => 'wildan', 'password' => 'x', 'role' => 'teknisi']);
        Monitoring::create(['wonum' => 'WO5', 'sto' => 'TEG', 'odp_name' => 'ODP-05']);

        $this->artisan('tugas:demo', ['teknisi' => '24040075'])->assertSuccessful();
        $this->actingAs($t)->get('/tugas?q=ODP-05')->assertOk()->assertSee('ODP-05');
    }
}
