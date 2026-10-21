<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Saat dibuka lewat tunnel (VS Code port forwarding / devtunnels), percayai header X-Forwarded-*
        // agar URL aset & form ikut https dan tidak diblokir browser sebagai mixed content.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Vercel: sistem file hanya-baca kecuali /tmp, jadi storage (log, cache, view terkompilasi) dipindah ke sana.
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL') || str_starts_with(__DIR__, '/var/task') || ! is_writable(dirname(__DIR__).'/storage')) {
    $storage = '/tmp/storage';
    foreach (['app', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $sub) {
        if (! is_dir("$storage/$sub")) {
            mkdir("$storage/$sub", 0777, true);
        }
    }
    $app->useStoragePath($storage);
}

return $app;
