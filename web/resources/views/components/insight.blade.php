@props(['terlihat' => null, 'kenapa' => null, 'lanjut' => null, 'peringatan' => false, 'lipat' => true])

{{-- Pola penjelasan: Terlihat -> Kenapa -> Lanjut. Bila $lipat, Kenapa & Lanjut dilipat agar halaman tetap padat --}}
<div {{ $attributes->class(['insight', 'peringatan' => $peringatan]) }}>
    @if ($terlihat)<p><b>Terlihat</b><span>{{ $terlihat }}</span></p>@endif
    {{ $slot }}
    @if ($kenapa || $lanjut)
        @if ($lipat)
            <details class="lipat">
                <summary>Kenapa & selanjutnya</summary>
                <div class="mt-2.5 grid gap-2.5">
                    @if ($kenapa)<p><b>Kenapa</b><span>{{ $kenapa }}</span></p>@endif
                    @if ($lanjut)<p><b>Lanjut</b><span>{{ $lanjut }}</span></p>@endif
                </div>
            </details>
        @else
            @if ($kenapa)<p><b>Kenapa</b><span>{{ $kenapa }}</span></p>@endif
            @if ($lanjut)<p><b>Lanjut</b><span>{{ $lanjut }}</span></p>@endif
        @endif
    @endif
</div>
