<?php

namespace App\Http\Requests\Alumno;

use Illuminate\Foundation\Http\FormRequest;

class EnviarMensajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxCaracteres = (int) config('gemini.max_caracteres_mensaje', 1000);

        return [
            'contenido' => ['required', 'string', 'min:2', "max:{$maxCaracteres}"],
        ];
    }

    public function messages(): array
    {
        return [
            'contenido.required' => 'Escribí una pregunta o mensaje para el cliente.',
            'contenido.min' => 'El mensaje es demasiado corto.',
            'contenido.max' => 'El mensaje no puede superar los :max caracteres.',
        ];
    }
}
