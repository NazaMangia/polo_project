<?php

namespace Tests\Feature;

use App\Enums\EstadoSesion;
use App\Enums\RolMensaje;
use App\Models\Equipo;
use App\Models\Sesion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gemini.driver' => 'fake']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_equipo_valido_inicia_sesion_recibe_saludo_inicial_y_la_retoma_si_reingresa(): void
    {
        // Primer ingreso: crea sesión (201) y guarda el saludo del cliente.
        $res1 = $this->postJson('/api/sesiones', ['codigo' => 'omega26']);

        $res1->assertCreated()
            ->assertJsonPath('reanudada', false)
            ->assertJsonPath('sesion.equipo.codigo', 'OMEGA26')
            ->assertJsonPath('sesion.escenario.nombre', 'Sistema de turnos para una clínica')
            ->assertJsonCount(1, 'sesion.mensajes');

        $uuid = $res1->json('sesion.uuid');
        $token1 = $res1->json('token');

        // La respuesta al alumno NO debe filtrar las necesidades ocultas ni reglas de negocio.
        $this->assertNull($res1->json('sesion.escenario.necesidades'));
        $this->assertNull($res1->json('sesion.escenario.reglas_negocio'));

        // Segundo ingreso con el mismo código: retoma la misma sesión activa (200) y emite nuevo token.
        $res2 = $this->postJson('/api/sesiones', ['codigo' => ' OMEGA26 ']);

        $res2->assertOk()
            ->assertJsonPath('reanudada', true)
            ->assertJsonPath('sesion.uuid', $uuid);

        $this->assertNotSame($token1, $res2->json('token'));
        $this->assertSame(1, Sesion::count());
    }

    public function test_rechaza_codigo_inexistente_equipo_inactivo_o_sin_escenario(): void
    {
        $this->postJson('/api/sesiones', ['codigo' => 'NOEXISTE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);

        $this->postJson('/api/sesiones', ['codigo' => 'INACT26'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);

        $this->postJson('/api/sesiones', ['codigo' => 'SINESC26'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_protege_la_sesion_con_token_secreto(): void
    {
        $inicio = $this->postJson('/api/sesiones', ['codigo' => 'OMEGA26']);
        $uuid = $inicio->json('sesion.uuid');
        $tokenValido = $inicio->json('token');

        // Sin header X-Sesion-Token -> 403
        $this->getJson("/api/sesiones/{$uuid}")->assertForbidden();

        // Con token de otro equipo -> 403
        $this->getJson("/api/sesiones/{$uuid}", ['X-Sesion-Token' => 'token-falso'])->assertForbidden();

        // Con token válido -> 200
        $this->getJson("/api/sesiones/{$uuid}", ['X-Sesion-Token' => $tokenValido])
            ->assertOk()
            ->assertJsonPath('data.uuid', $uuid);
    }

    public function test_envia_mensaje_y_recibe_respuesta_del_cliente_y_bloquea_tras_finalizar(): void
    {
        $inicio = $this->postJson('/api/sesiones', ['codigo' => 'OMEGA26']);
        $uuid = $inicio->json('sesion.uuid');
        $headers = ['X-Sesion-Token' => $inicio->json('token')];

        $envio = $this->postJson("/api/sesiones/{$uuid}/mensajes", [
            'contenido' => '¿Cuántos médicos trabajan actualmente en la clínica?',
        ], $headers);

        $envio->assertCreated()
            ->assertJsonPath('mensaje_alumno.rol', RolMensaje::Alumno->value)
            ->assertJsonPath('mensaje_cliente.rol', RolMensaje::Cliente->value);

        $this->assertStringContainsString('cinco médicos', $envio->json('mensaje_cliente.contenido'));

        // Finalizar la entrevista
        $fin = $this->postJson("/api/sesiones/{$uuid}/finalizar", [], $headers);
        $fin->assertOk()
            ->assertJsonPath('data.estado', EstadoSesion::Finalizada->value);

        // Intentar enviar otro mensaje en sesión finalizada -> 409 Conflict
        $this->postJson("/api/sesiones/{$uuid}/mensajes", [
            'contenido' => 'Una pregunta más',
        ], $headers)->assertStatus(409);
    }
}
