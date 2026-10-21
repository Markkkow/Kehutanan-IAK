<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD akun pengguna dashboard (khusus admin). */
class AkunController extends Controller
{
    public function index()
    {
        return view('akun.index', ['akun' => User::orderByDesc('peran')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('akun.form', ['user' => new User(['peran' => 'pengguna'])]);
    }

    public function store(Request $request)
    {
        User::create($this->validasi($request));

        return redirect()->route('akun.index')->with('pesan', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('akun.form', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validasi($request, $user);
        if ($user->isAdmin() && $data['peran'] !== 'admin' && $this->adminTerakhir($user)) {
            return back()->withErrors(['peran' => 'Harus ada minimal satu admin.'])->withInput();
        }
        if (empty($data['password'])) {
            unset($data['password']); // kosong = password tidak diubah
        }
        $user->update($data);

        return redirect()->route('akun.index')->with('pesan', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['akun' => 'Tidak bisa menghapus akun yang sedang dipakai.']);
        }
        if ($user->isAdmin() && $this->adminTerakhir($user)) {
            return back()->withErrors(['akun' => 'Harus ada minimal satu admin.']);
        }
        $user->delete();

        return redirect()->route('akun.index')->with('pesan', "Akun {$user->email} dihapus.");
    }

    private function validasi(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'peran' => ['required', Rule::in(['admin', 'pengguna'])],
            // wajib saat membuat akun; saat edit boleh kosong (tidak diubah)
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'confirmed' => 'Ulangi password tidak sama.',
            'in' => ':attribute tidak dikenal.',
        ], ['name' => 'Nama', 'email' => 'Email', 'peran' => 'Peran', 'password' => 'Password']);
    }

    private function adminTerakhir(User $user): bool
    {
        return User::where('peran', 'admin')->whereKeyNot($user->getKey())->doesntExist();
    }
}
