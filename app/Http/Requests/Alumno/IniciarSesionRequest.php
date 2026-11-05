<?php

namespace App\Http\Requests\Alumno;

use Illuminate\Foundation\Http\FormRequest;

class IniciarSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'min:3', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresá el código de tu equipo.',
            'codigo.min' => 'El código de equipo es demasiado corto.',
            'codigo.max' => 'El código de equipo no puede superar los 30 caracteres.',
        ];
    }
}
