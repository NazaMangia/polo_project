<?php

namespace App\Http\Requests\Admin;

use App\Models\Equipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('codigo')) {
            $this->merge([
                'codigo' => Equipo::normalizarCodigo((string) $this->input('codigo')),
            ]);
        }
    }

    public function rules(): array
    {
        $equipo = $this->route('equipo');
        $ignorarId = $equipo instanceof Equipo ? $equipo->id : null;

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => [
                'nullable',
                'string',
                'min:3',
                'max:20',
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('equipos', 'codigo')->ignore($ignorarId),
            ],
            'activo' => ['sometimes', 'boolean'],
            'escenario_id' => ['nullable', 'integer', 'exists:escenarios,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del equipo es obligatorio.',
            'codigo.unique' => 'Ese código ya está asignado a otro equipo.',
            'codigo.regex' => 'El código solo puede contener letras, números y guiones.',
            'escenario_id.exists' => 'El escenario seleccionado no existe.',
        ];
    }
}
