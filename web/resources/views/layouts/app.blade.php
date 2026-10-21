<!DOCTYPE html>
<html lang="id" data-tema="terang">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Dashboard Kehutanan')</title>
    <script>
        // Terapkan tema tersimpan sebelum halaman digambar (mencegah kedip)
        try { const t = localStorage.getItem('tema'); if (t) document.documentElement.dataset.tema = t; } catch (e) {}
    </script>
    @vite(['resources/css/app.css'])
    @stack('kepala')
</head>
<body class="min-h-dvh bg-page font-sans text-ink antialiased">
    @yield('isi')
</body>
</html>
