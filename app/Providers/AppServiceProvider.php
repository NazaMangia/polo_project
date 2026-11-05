<?php

namespace App\Providers;

use App\Contracts\ProveedorIA;
use App\Services\Gemini\FakeProveedorIA;
use App\Services\Gemini\GeminiService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProveedorIA::class, function () {
            $driver = (string) config('gemini.driver', 'auto');
            $apiKey = trim((string) config('gemini.api_key', ''));

            if ($driver === 'fake' || ($driver === 'auto' && $apiKey === '')) {
                return new FakeProveedorIA;
            }

            return new GeminiService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('iniciar-sesion', function (Request $request) {
            return Limit::perMinute(40)->by($request->ip());
        });

        RateLimiter::for('login-docente', function (Request $request) {
            $clave = mb_strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(8)->by($clave);
        });

        RateLimiter::for('mensajes-ia', function (Request $request) {
            $sesionUuid = (string) optional($request->route('sesion'))->uuid ?: $request->ip();

            return Limit::perMinute(10)->by($sesionUuid)->response(function () {
                return response()->json([
                    'message' => 'Estás enviando mensajes demasiado rápido. Esperá unos segundos antes de hacer otra pregunta.',
                ], 429);
            });
        });
    }
}
