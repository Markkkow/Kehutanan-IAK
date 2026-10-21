@extends('layouts.app')

@section('judul', 'Dashboard Kehutanan Indonesia')

@push('kepala')
    <script>window.DATA = {{ Js::from($grafik) }};</script>
    @vite(['resources/js/dashboard.js'])
@endpush

@section('isi')
@php
    $tabs = [
        'ringkasan' => ['Ringkasan', 'ringkasan'],
        'per-data' => ['Per data', 'grafik'],
        'sebaran' => ['Peta sebaran', 'peta'],
        'gabungan' => ['Analisis gabungan', 'lapisan'],
        'integrasi' => ['Integrasi data', 'basisdata'],
    ];
@endphp

<header class="sticky top-0 z-30 border-b border-garis bg-page/85 backdrop-blur-xl">
    <div class="mx-auto flex max-w-[1360px] items-center gap-4 px-4 py-3 md:px-6">
        <a href="#ringkasan" class="flex shrink-0 items-center gap-2.5" >
            <span class="grid size-9 place-items-center rounded-xl bg-brand text-[#f6f1e4] shadow-sm"><x-ikon nama="pohon" class="size-[18px]" /></span>
            <span class="hidden text-sm leading-tight font-semibold sm:block">Dashboard Kehutanan<span class="block text-[11px] font-normal text-ink-3">Data-level EAI · Kelompok 3</span></span>
        </a>

        <nav class="mx-auto hidden items-center gap-1 rounded-xl border border-garis bg-surface p-1 lg:flex" aria-label="Bagian dashboard">
            @foreach ($tabs as $id => [$label, $ikon])
                <a href="#{{ $id }}" class="tab inline-flex items-center gap-1.5" data-tab="{{ $id }}" aria-current="false"><x-ikon :nama="$ikon" class="size-4" />{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:ml-0">
            <button id="gantiTema" type="button" class="grid size-9 place-items-center rounded-lg border border-garis bg-surface text-ink-2 hover:text-ink" aria-label="Ganti mode siang/malam" title="Ganti mode siang/malam">
                <x-ikon nama="bulan" class="size-4 dark:hidden" /><x-ikon nama="matahari" class="hidden size-4 dark:block" />
            </button>
            @can('kelola-akun')
                <a href="{{ route('akun.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-garis bg-surface px-3 py-2 text-xs font-medium text-ink-2 hover:text-ink" title="Kelola akun">
                    <x-ikon nama="akun" class="size-4" /><span class="hidden sm:inline">Akun</span>
                </a>
            @endcan
            <div class="hidden text-right text-xs leading-tight md:block">
                <div class="font-medium text-ink">{{ auth()->user()->name }}</div>
                <div class="text-ink-3">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-garis bg-surface px-3 py-2 text-xs font-medium text-ink-2 hover:text-ink">Keluar</button>
            </form>
        </div>
    </div>

    {{-- Tab versi layar kecil: bisa digeser --}}
    <nav class="flex gap-1 overflow-x-auto px-4 pb-2.5 lg:hidden" aria-label="Bagian dashboard">
        @foreach ($tabs as $id => [$label, $ikon])
            <a href="#{{ $id }}" class="tab inline-flex items-center gap-1.5" data-tab="{{ $id }}" aria-current="false"><x-ikon :nama="$ikon" class="size-4" />{{ $label }}</a>
        @endforeach
    </nav>
</header>

<main class="mx-auto max-w-[1360px] px-4 pt-5 pb-16 md:px-6">
    <section id="ringkasan" data-panel="ringkasan" class="scroll-mt-28 [&:not(:first-child)]:mt-9">@include('dashboard.partials.ringkasan')</section>
    <section id="per-data" data-panel="per-data" class="scroll-mt-28 [&:not(:first-child)]:mt-9">@include('dashboard.partials.per-data')</section>
    <section id="sebaran" data-panel="sebaran" class="scroll-mt-28 [&:not(:first-child)]:mt-9">@include('dashboard.partials.sebaran')</section>
    <section id="gabungan" data-panel="gabungan" class="scroll-mt-28 [&:not(:first-child)]:mt-9">@include('dashboard.partials.gabungan')</section>
    <section id="integrasi" data-panel="integrasi" class="scroll-mt-28 [&:not(:first-child)]:mt-9">@include('dashboard.partials.integrasi')</section>

    <footer class="mt-16 flex flex-wrap items-center justify-between gap-2 border-t border-garis pt-5 text-xs text-ink-3">
        <span>Sumber: BPS (bps.go.id) · SIPONGI KEMENHUT · KLHK</span>
        @php $sumberData = config('database.connections.data'); @endphp
        <span>Dibaca dari replika <code class="text-ink-2">{{ $sumberData['database'] }}</code> (port {{ $sumberData['port'] }}) hasil pipeline ETL</span>
    </footer>
</main>
@endsection
