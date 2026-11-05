<?php

namespace App\Http\Middleware;

use App\Models\Sesion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege los endpoints de una sesión verificando que la petición incluya
 * el header `X-Sesion-Token` correspondiente al `token_hash` de esa sesión.
 *
 * Así ningún equipo puede leer ni escribir en la sesión de otro cambiando el UUID de la URL.
 */
class VerificarTokenSesion
{
    public function handle(Request $request, Closure $next): Response
    {
        $sesion = $request->route('sesion');

        if (! $sesion instanceof Sesion) {
            abort(404, 'La sesión solicitada no existe.');
        }

        $token = $request->header('X-Sesion-Token') ?: $request->bearerToken();

        if (! $sesion->verificarToken($token)) {
            return response()->json([
                'message' => 'No tenés permiso para acceder a esta sesión. Volvé a ingresar con el código de tu equipo.',
            ], 403);
        }

        return $next($request);
    }
}
