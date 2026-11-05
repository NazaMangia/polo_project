<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginDocenteRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthDocenteController extends Controller
{
    /**
     * POST /api/docente/login
     * Autentica al docente y devuelve un token personal de Sanctum
     * (además de iniciar sesión si la petición es stateful).
     */
    public function login(LoginDocenteRequest $request): JsonResponse
    {
        $email = mb_strtolower(trim((string) $request->validated('email')));
        $password = (string) $request->validated('password');

        $usuario = User::where('email', $email)->first();

        if (! $usuario || ! Hash::check($password, $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales ingresadas no son válidas.'],
            ]);
        }

        $token = $usuario->createToken('panel-docente')->plainTextToken;

        return response()->json([
            'token' => $token,
            'docente' => [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
                'email' => $usuario->email,
            ],
        ]);
    }

    /**
     * GET /api/docente/yo
     */
    public function yo(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'id' => $usuario->id,
            'nombre' => $usuario->name,
            'email' => $usuario->email,
        ]);
    }

    /**
     * POST /api/docente/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
