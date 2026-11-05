<?php

namespace Tests\Feature;

use App\Enums\RolMensaje;
use App\Exceptions\ProveedorIAException;
use App\Models\Escenario;
use App\Services\Gemini\GeminiService;
use App\Services\Simulacion\PromptBuilder;
use Database\Seeders\EscenarioClinicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PromptYGeminiTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_prompt_builder_incluye_las_cuatro_capas_y_etiquetas_de_revelacion(): void
    {
        $this->seed(EscenarioClinicaSeeder::class);
        $escenario = Escenario::firstOrFail();

        $prompt = (new PromptBuilder)->construirInstruccionSistema($escenario);

        $this->assertStringContainsString('REGLAS ESTRICTAS E INQUEBRANTABLES', $prompt);
        $this->assertStringContainsString('Laura Méndez', $prompt);
        $this->assertStringContainsString('[ESPONTÁNEO] Evitar que se den dos turnos', $prompt);
        $this->assertStringContainsString('[SI PREGUNTAN] La clínica tiene 5 médicos', $prompt);
        $this->assertStringContainsString('[PREGUNTA PRECISA] Los turnos de primera vez duran el doble', $prompt);
        $this->assertStringContainsString('Información reservada / secretos del cliente', $prompt);
        $this->assertStringContainsString('Nivel de dificultad (Intermedia)', $prompt);
    }

    public function test_el_historial_excluye_mensajes_de_sistema_y_gemini_antepone_turno_user(): void
    {
        $builder = new PromptBuilder;

        $historial = $builder->construirHistorial([
            ['rol' => RolMensaje::Cliente->value, 'contenido' => 'Hola, soy Laura.'],
            ['rol' => RolMensaje::Sistema->value, 'contenido' => 'Aviso interno'],
            ['rol' => RolMensaje::Alumno->value, 'contenido' => '¿Cuántos médicos hay?'],
        ]);

        $this->assertCount(2, $historial);

        $contents = (new GeminiService)->formatearContenidos($historial);

        // Como el primer mensaje de la sesión es del cliente ('model'), GeminiService
        // antepone un turno 'user' sintético para cumplir el contrato de la API de Gemini.
        $this->assertCount(3, $contents);
        $this->assertSame('user', $contents[0]['role']);
        $this->assertSame('model', $contents[1]['role']);
        $this->assertSame('user', $contents[2]['role']);
    }

    public function test_gemini_service_envia_payload_correcto_y_parsea_respuesta(): void
    {
        config([
            'gemini.api_key' => 'test-api-key-secreta',
            'gemini.model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'role' => 'model',
                            'parts' => [
                                ['text' => 'Trabajan cinco médicos en la clínica.'],
                            ],
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 200,
                    'candidatesTokenCount' => 12,
                    'totalTokenCount' => 212,
                ],
            ], 200),
        ]);

        $respuesta = (new GeminiService)->responder('Instrucción del sistema', [
            ['rol' => 'alumno', 'contenido' => '¿Cuántos médicos hay?'],
        ]);

        $this->assertSame('Trabajan cinco médicos en la clínica.', $respuesta->texto);
        $this->assertSame(212, $respuesta->tokensTotal);
        $this->assertSame('STOP', $respuesta->finishReason);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'test-api-key-secreta')
                && data_get($request->data(), 'system_instruction.parts.0.text') === 'Instrucción del sistema'
                && data_get($request->data(), 'contents.0.parts.0.text') === '¿Cuántos médicos hay?'
                && data_get($request->data(), 'generationConfig.thinkingConfig.thinkingBudget') === 0;
        });
    }

    public function test_gemini_service_lanza_excepcion_amigable_ante_error_429(): void
    {
        config(['gemini.api_key' => 'test-api-key-secreta']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Resource has been exhausted (e.g. check quota).'],
            ], 429),
        ]);

        $this->expectException(ProveedorIAException::class);
        $this->expectExceptionMessage('límite de cuota');

        (new GeminiService)->responder('Sistema', [
            ['rol' => 'alumno', 'contenido' => 'Hola'],
        ]);
    }

    public function test_el_comando_artisan_simulador_probar_funciona_con_el_proveedor_fake(): void
    {
        $this->seed(EscenarioClinicaSeeder::class);
        config(['gemini.driver' => 'fake']);

        $this->artisan('simulador:probar', [
            '--pregunta' => '¿Cuántos médicos trabajan en la clínica?',
        ])
            ->expectsOutputToContain('cinco médicos')
            ->assertSuccessful();
    }
}
