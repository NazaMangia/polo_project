<?php

namespace App\Providers;

use App\Contracts\ProveedorIA;
use App\Models\Sesion;
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
            // El throttle corre antes del route model binding: el parámetro todavía es el uuid (string).
            // Se limita por sesión y no por IP, porque todo un aula suele salir a internet con la misma IP.
            $sesion = $request->route('sesion');
            $clave = $sesion instanceof Sesion ? $sesion->uuid : (string) $sesion;

            return Limit::perMinute(10)->by($clave !== '' ? 'sesion:'.$clave : $request->ip())->response(function () {
                return response()->json([
                    'message' => 'Estás enviando mensajes demasiado rápido. Esperá unos segundos antes de hacer otra pregunta.',
                ], 429);
            });
        });
    }
}
