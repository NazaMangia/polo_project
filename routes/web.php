<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web
|--------------------------------------------------------------------------
| La aplicación es una SPA de Vue: cualquier URL que no sea /api/* devuelve
| la misma vista y Vue Router se encarga de mostrar la pantalla correcta.
*/

Route::view('/{any?}', 'app')->where('any', '^(?!api|up|sanctum).*$');
