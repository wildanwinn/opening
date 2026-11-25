<?php

namespace App\Http\Controllers;

use App\Models\Sto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoController extends Controller
{
    public function index(Request $request)
    {
        $query = Sto::withCount('monitorings');

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('kode_sto', 'like', "%{$cari}%")
                  ->orWhere('nama_sto', 'like', "%{$cari}%")
                  ->orWhere('wilayah', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('wilayah')) {
            $query->where('wilayah', $request->wilayah);
        }

        return view('sto.index', [
            'daftarSto' => $query->orderBy('wilayah')->orderBy('kode_sto')->paginate(25)->withQueryString(),
            'daftarWilayah' => Sto::select('wilayah')->distinct()->orderBy('wilayah')->pluck('wilayah'),
        ]);
    }

    public function create()
    {
        return view('sto.form', ['sto' => new Sto]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_sto' => ['required', 'string', 'max:5', 'unique:sto,kode_sto'],
            'nama_sto' => ['required', 'string', 'max:50'],
            'wilayah'  => ['required', 'string', 'max:30'],
        ]);

        Sto::create($data);
        return redirect()->route('sto.index')->with('sukses', 'STO berhasil ditambahkan.');
    }

    public function edit(Sto $sto)
    {
        return view('sto.form', ['sto' => $sto]);
    }

    public function update(Request $request, Sto $sto)
    {
        $data = $request->validate([
            'kode_sto' => ['required', 'string', 'max:5', Rule::unique('sto', 'kode_sto')->ignore($sto->id)],
            'nama_sto' => ['required', 'string', 'max:50'],
            'wilayah'  => ['required', 'string', 'max:30'],
        ]);

        $sto->update($data);
        return redirect()->route('sto.index')->with('sukses', 'STO berhasil diperbarui.');
    }

    public function destroy(Sto $sto)
    {
        if ($sto->monitorings()->count() > 0) {
            return back()->with('error', "STO {$sto->kode_sto} gak bisa dihapus karena masih punya {$sto->monitorings()->count()} order.");
        }
        $sto->delete();
        return back()->with('sukses', 'STO berhasil dihapus.');
    }
}