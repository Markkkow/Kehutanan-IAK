@extends('layouts.app')

@section('judul', 'Kelola akun · Dashboard Kehutanan')

@section('isi')
@include('akun._kepala')

<main class="mx-auto max-w-[960px] px-4 py-6 md:px-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Daftar akun</h1>
            <p class="mt-1 text-sm text-ink-2">Admin bisa menambah, mengubah, dan menghapus akun. Peran <b>pengguna</b> hanya bisa melihat dashboard.</p>
        </div>
        <a href="{{ route('akun.create') }}" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:brightness-110">+ Tambah akun</a>
    </div>

    @if (session('pesan'))
        <div class="mt-4 rounded-xl border border-brand/30 bg-brand-lembut px-3.5 py-2.5 text-sm text-brand-teks" role="status">{{ session('pesan') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-peringatan/30 bg-peringatan-lembut px-3.5 py-2.5 text-sm text-peringatan" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="kartu mt-4 overflow-x-auto p-0">
        <table class="tabel-data">
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Dibuat</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            @foreach ($akun as $a)
                <tr>
                    <td class="font-medium text-ink">{{ $a->name }} @if ($a->is(auth()->user()))<span class="ml-1 text-[11px] text-ink-3">(Anda)</span>@endif</td>
                    <td>{{ $a->email }}</td>
                    <td><span class="{{ $a->isAdmin() ? 'pil-ok' : 'lencana' }}">{{ $a->peran }}</span></td>
                    <td class="whitespace-nowrap">{{ $a->created_at?->format('d-m-Y') }}</td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('akun.edit', $a) }}" class="rounded-lg border border-garis bg-surface px-2.5 py-1 text-xs font-medium text-ink-2 hover:text-ink">Edit</a>
                        @unless ($a->is(auth()->user()))
                            <form method="POST" action="{{ route('akun.destroy', $a) }}" class="inline" onsubmit="return confirm('Hapus akun {{ $a->email }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-lg border border-peringatan/30 bg-peringatan-lembut px-2.5 py-1 text-xs font-medium text-peringatan hover:brightness-95">Hapus</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
