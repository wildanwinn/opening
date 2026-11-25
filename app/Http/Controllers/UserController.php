<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const DAFTAR_ROLE = ['admin', 'hd', 'teknisi', 'edp', 'tsel', 'tif', 'service_area', 'oc3'];

    public function index(Request $request)
    {
        $query = User::with('sto');

        if ($request->filled('cari')) {
            $cari = trim($request->cari);
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nik', 'like', "%{$cari}%")
                  ->orWhere('kode_teknisi', 'like', "%{$cari}%");
            });
        }

        $users = $query->orderBy('nama')->paginate(20)->withQueryString();

        return view('user.index', ['users' => $users]);
    }

    public function create()
    {
        return view('user.form', ['user' => new User, 'daftarSto' => Sto::orderBy('kode_sto')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nik'          => ['required', 'string', 'max:20', 'unique:users,nik'],
            'kode_teknisi' => ['nullable', 'string', 'max:30', 'unique:users,kode_teknisi'],
            'nama'         => ['required', 'string', 'max:100'],
            'password'     => ['required', 'string', 'min:6'],
            'role'         => ['required', Rule::in(self::DAFTAR_ROLE)],
            'sto_id'       => ['nullable', 'exists:sto,id'],
        ]);

        User::create($data);

        return redirect()->route('user.index')->with('sukses', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('user.form', ['user' => $user, 'daftarSto' => Sto::orderBy('kode_sto')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nik'          => ['required', 'string', 'max:20', Rule::unique('users', 'nik')->ignore($user->id)],
            'kode_teknisi' => ['nullable', 'string', 'max:30', Rule::unique('users', 'kode_teknisi')->ignore($user->id)],
            'nama'         => ['required', 'string', 'max:100'],
            'password'     => ['nullable', 'string', 'min:6'],
            'role'         => ['required', Rule::in(self::DAFTAR_ROLE)],
            'sto_id'       => ['nullable', 'exists:sto,id'],
        ]);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('user.index')->with('sukses', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        $user->delete();
        return back()->with('sukses', 'User berhasil dihapus.');
    }
}