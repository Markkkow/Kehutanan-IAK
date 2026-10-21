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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('kepala')
</head>
<body class="min-h-dvh bg-page font-sans text-ink antialiased">
    @yield('isi')
</body>
</html>
