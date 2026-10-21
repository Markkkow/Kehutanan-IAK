@extends('layouts.app')

@section('judul', 'Masuk · Dashboard Kehutanan')

@section('isi')
<div class="grid min-h-dvh lg:grid-cols-[1.15fr_1fr]">
    <section class="relative hidden overflow-hidden bg-gradient-to-br from-[#234b30] via-[#457553] to-[#6f9a78] text-[#f6f1e4] lg:flex lg:flex-col lg:gap-8 lg:p-12">
        <div class="absolute inset-x-0 bottom-0 h-1/2"><x-lanskap-hutan /></div>
        <div class="relative flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-xl bg-[#1e3a28] text-[#f6f1e4]"><x-ikon nama="pohon" class="size-[18px]" /></span>
            <span class="text-sm font-semibold">Dashboard Kehutanan</span>
        </div>
        <div class="relative">
            <span class="inline-flex rounded-full bg-black/15 px-3 py-1 text-[11.5px] font-medium">Integrasi 5 sumber data · 2018–2026</span>
            <h1 class="mt-5 max-w-lg text-4xl leading-[1.1] font-semibold text-balance text-white">
                Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?
            </h1>
            <p class="mt-4 max-w-md text-[15px] leading-relaxed text-pretty text-[#eef3e6]/90">
                Data BPS dan KLHK dalam empat format file disatukan lewat pipeline ETL ke satu database, lalu dianalisis di sini.
            </p>
        </div>
        <div class="relative grid max-w-lg grid-cols-5 gap-2.5 text-center">
            @foreach ([
                ['D1', 'Kelas lahan', 'XLS', 'pohon', '#8ee2b3', 'rgba(82,199,136,0.35)'],
                ['D2', 'Tutupan', 'CSV', 'daun', '#a4eb9f', 'rgba(94,196,126,0.35)'],
                ['D3', 'Karhutla', 'XLSX', 'api', '#ffb09c', 'rgba(232,112,82,0.45)'],
                ['D4', 'Kayu', 'JSON', 'kayu', '#f0be8d', 'rgba(212,146,82,0.45)'],
                ['D5', 'Deforestasi', 'CSV', 'kapak', '#f8cf79', 'rgba(230,173,58,0.45)'],
            ] as [$k, $n, $f, $i, $warnaTeks, $warnaGaris])
                <div class="rounded-xl border bg-[#12261a]/65 px-2 py-3 backdrop-blur-md" style="border-color: {{ $warnaGaris }}">
                    <span class="mx-auto grid size-7 place-items-center rounded-lg bg-white/10" style="color: {{ $warnaTeks }}">
                        <x-ikon :nama="$i" class="size-4" />
                    </span>
                    <div class="mt-1.5 text-[12px] font-semibold text-white">{{ $n }}</div>
                    <div class="mt-0.5 text-[10.5px] font-medium" style="color: {{ $warnaTeks }}">{{ $k }} · {{ $f }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid place-items-center px-6 py-12">
        <form class="w-full max-w-sm" method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid size-9 place-items-center rounded-xl bg-brand text-[#f6f1e4]"><x-ikon nama="pohon" class="size-[18px]" /></span>
                <span class="text-sm font-semibold">Dashboard Kehutanan</span>
            </div>
            <h2 class="text-2xl font-semibold">Masuk</h2>
            <p class="mt-1.5 text-sm text-ink-2">Gunakan akun yang terdaftar untuk membuka dashboard.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-peringatan/30 bg-peringatan-lembut px-3.5 py-2.5 text-sm text-peringatan" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <label for="email" class="mt-6 block text-xs font-medium text-ink-2">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1.5 w-full rounded-xl border border-garis bg-surface-2 px-3.5 py-2.5 text-sm text-ink outline-none placeholder:text-ink-3 focus:border-brand focus:ring-4 focus:ring-brand/15">

            <label for="password" class="mt-4 block text-xs font-medium text-ink-2">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1.5 w-full rounded-xl border border-garis bg-surface-2 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-brand focus:ring-4 focus:ring-brand/15">

            <label class="mt-4 flex items-center gap-2 text-sm text-ink-2">
                <input type="checkbox" name="ingat" value="1" class="size-4 rounded accent-[var(--brand)]"> Ingat saya
            </label>

            <button id="tombolMasuk" type="submit" class="mt-6 w-full rounded-xl bg-brand py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                Masuk
            </button>

            @if ($demo)
                {{-- Akun demo: klik untuk mengisi email & password otomatis --}}
                <div class="mt-7 border-t border-garis pt-5">
                    <div class="text-xs font-medium text-ink-2">Akun demo · klik untuk mengisi otomatis</div>
                    <div class="mt-2.5 grid gap-2">
                        @foreach ($demo as $a)
                            <button type="button" data-email="{{ $a['email'] }}" data-password="{{ $a['password'] }}"
                                    class="isi-demo flex w-full min-w-0 items-center gap-3 rounded-xl border border-garis bg-surface-2 px-3 py-2.5 text-left transition hover:border-brand/40 hover:bg-sorot">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-lembut text-brand-teks"><x-ikon nama="akun" class="size-4" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[13px] font-medium text-ink">Masuk sebagai {{ $a['name'] }}</span>
                                    <span class="block truncate text-[11.5px] text-ink-3">{{ $a['email'] }} · {{ $a['ket'] }}</span>
                                </span>
                                <span class="{{ $a['peran'] === 'admin' ? 'pil-ok' : 'lencana' }}">{{ $a['peran'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <script>
                    document.querySelectorAll('.isi-demo').forEach((b) => b.addEventListener('click', () => {
                        document.getElementById('email').value = b.dataset.email;
                        document.getElementById('password').value = b.dataset.password;
                        document.getElementById('tombolMasuk').focus();
                    }));
                </script>
            @endif
        </form>
    </section>
</div>
@endsection
