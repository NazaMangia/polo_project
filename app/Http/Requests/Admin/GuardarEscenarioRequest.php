<?php

namespace App\Http\Requests\Admin;

use App\Enums\Dificultad;
use App\Enums\PoliticaRevelacion;
use App\Models\Escenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEscenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza las listas de conocimiento: descarta ítems vacíos y asigna un ID automático
     * (`nec-1`, `act-2`, etc.) si el formulario no lo envió.
     */
    protected function prepareForValidation(): void
    {
        $prefijos = [
            'informacion_cliente' => 'inf',
            'necesidades' => 'nec',
            'actores' => 'act',
            'procesos' => 'pro',
            'datos' => 'dat',
            'reglas_negocio' => 'reg',
            'restricciones' => 'res',
        ];

        $normalizados = [];

        foreach ($prefijos as $campo => $prefijo) {
            if (! $this->has($campo) || ! is_array($this->input($campo))) {
                continue;
            }

            $itemsLimpios = [];
            foreach ($this->input($campo) as $indice => $item) {
                $texto = trim((string) ($item['texto'] ?? ''));
                if ($texto === '') {
                    continue;
                }

                $itemsLimpios[] = [
                    'id' => ! empty($item['id']) ? (string) $item['id'] : $prefijo.'-'.($indice + 1),
                    'texto' => $texto,
                    'revelacion' => $item['revelacion'] ?? PoliticaRevelacion::SiPregunta->value,
                ];
            }

            $normalizados[$campo] = $itemsLimpios;
        }

        if (! empty($normalizados)) {
            $this->merge($normalizados);
        }
    }

    public function rules(): array
    {
        $reglas = [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'dificultad' => ['required', Rule::enum(Dificultad::class)],
            'activo' => ['sometimes', 'boolean'],
            'problema_principal' => ['required', 'string', 'max:3000'],
            'contexto' => ['nullable', 'string', 'max:5000'],
            'rol_cliente' => ['required', 'string', 'max:1000'],
            'personalidad' => ['nullable', 'string', 'max:2000'],
            'mensaje_inicial' => ['required', 'string', 'max:1500'],
            'informacion_reservada' => ['nullable', 'string', 'max:4000'],
            'comportamiento_esperado' => ['nullable', 'string', 'max:4000'],
            'instrucciones_ia' => ['nullable', 'string', 'max:4000'],
        ];

        foreach (array_keys(Escenario::LISTAS_CONOCIMIENTO) as $campo) {
            $reglas[$campo] = ['nullable', 'array', 'max:40'];
            $reglas["{$campo}.*.id"] = ['required', 'string', 'max:40'];
            $reglas["{$campo}.*.texto"] = ['required', 'string', 'max:1000'];
            $reglas["{$campo}.*.revelacion"] = ['required', Rule::enum(PoliticaRevelacion::class)];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del escenario es obligatorio.',
            'problema_principal.required' => 'Indicá cuál es el problema principal del escenario.',
            'rol_cliente.required' => 'Indicá el rol del cliente que interpretará la IA.',
            'mensaje_inicial.required' => 'Escribí el saludo inicial con el que comienza la entrevista.',
        ];
    }
}
