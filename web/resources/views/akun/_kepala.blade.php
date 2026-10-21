{{-- Kepala halaman akun: kembali ke dashboard + identitas pengguna --}}
<header class="border-b border-garis bg-page/85 backdrop-blur-xl">
    <div class="mx-auto flex max-w-[960px] items-center gap-3 px-4 py-3 md:px-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-garis bg-surface px-3 py-2 text-xs font-medium text-ink-2 hover:text-ink">← Dashboard</a>
        <span class="text-sm font-semibold">Kelola akun</span>
        <div class="ml-auto text-right text-xs leading-tight">
            <div class="font-medium text-ink">{{ auth()->user()->name }}</div>
            <div class="text-ink-3">{{ auth()->user()->email }}</div>
        </div>
    </div>
</header>
