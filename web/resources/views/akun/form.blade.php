@extends('layouts.app')

@section('judul', ($user->exists ? 'Edit akun' : 'Tambah akun').' · Dashboard Kehutanan')

@section('isi')
@include('akun._kepala')

@php $kelasInput = 'mt-1.5 w-full rounded-xl border border-garis bg-surface-2 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-brand focus:ring-4 focus:ring-brand/15'; @endphp

<main class="mx-auto max-w-[560px] px-4 py-6 md:px-6">
    <h1 class="text-xl font-semibold">{{ $user->exists ? 'Edit akun' : 'Tambah akun' }}</h1>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-peringatan/30 bg-peringatan-lembut px-3.5 py-2.5 text-sm text-peringatan" role="alert">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ $user->exists ? route('akun.update', $user) : route('akun.store') }}" class="kartu mt-4 p-5">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <label for="name" class="block text-xs font-medium text-ink-2">Nama</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" class="{{ $kelasInput }}">

        <label for="email" class="mt-4 block text-xs font-medium text-ink-2">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="off" class="{{ $kelasInput }}">

        <label for="peran" class="mt-4 block text-xs font-medium text-ink-2">Peran</label>
        <select id="peran" name="peran" class="pilih mt-1.5 w-full py-2.5">
            @foreach (['pengguna' => 'Pengguna (hanya melihat dashboard)', 'admin' => 'Admin (bisa mengelola akun)'] as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('peran', $user->peran) === $nilai)>{{ $label }}</option>
            @endforeach
        </select>

        <label for="password" class="mt-4 block text-xs font-medium text-ink-2">Password @if ($user->exists)<span class="font-normal text-ink-3">(kosongkan bila tidak diubah)</span>@endif</label>
        <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" @unless ($user->exists) required @endunless class="{{ $kelasInput }}">

        <label for="password_confirmation" class="mt-4 block text-xs font-medium text-ink-2">Ulangi password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" class="{{ $kelasInput }}">

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:brightness-110">Simpan</button>
            <a href="{{ route('akun.index') }}" class="rounded-xl border border-garis bg-surface px-4 py-2.5 text-sm font-medium text-ink-2 hover:text-ink">Batal</a>
        </div>
    </form>
</main>
@endsection
