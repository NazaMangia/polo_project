<?php

namespace Tests\Feature;

use App\Enums\RolMensaje;
use App\Models\Equipo;
use App\Models\Sesion;
use App\Services\Simulacion\EstadoSimulador;
use App\Services\Simulacion\SimulacionService;
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
        app(EstadoSimulador::class)->abrir(60);
    }

    public function test_con_el_simulador_cerrado_no_se_puede_ingresar_ni_preguntar_pero_si_ver_la_conversacion(): void
    {
        $inicio = $this->postJson('/api/sesiones', ['codigo' => 'OMEGA26']);
        $uuid = $inicio->json('sesion.uuid');
        $headers = ['X-Sesion-Token' => $inicio->json('token')];

        app(EstadoSimulador::class)->cerrar();

        $this->getJson('/api/estado')->assertJsonPath('simulador.abierto', false);
        $this->postJson('/api/sesiones', ['codigo' => 'ORANGE26'])->assertStatus(423);
        $this->postJson("/api/sesiones/{$uuid}/mensajes", ['contenido' => 'Hola'], $headers)->assertStatus(423);
        $this->getJson("/api/sesiones/{$uuid}", $headers)->assertOk();
    }

    public function test_el_simulador_se_cierra_solo_al_vencer_el_horario(): void
    {
        $this->travel(61)->minutes();

        $this->postJson('/api/sesiones', ['codigo' => 'OMEGA26'])->assertStatus(423);
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

        // Segundo ingreso con el mismo código (otro integrante): retoma la misma sesión activa (200)
        // y recibe el mismo token, así el primer integrante no queda afuera.
        $res2 = $this->postJson('/api/sesiones', ['codigo' => ' OMEGA26 ']);

        $res2->assertOk()
            ->assertJsonPath('reanudada', true)
            ->assertJsonPath('sesion.uuid', $uuid);

        $this->assertSame($token1, $res2->json('token'));
        $this->getJson("/api/sesiones/{$uuid}", ['X-Sesion-Token' => $token1])->assertOk();
        $this->assertSame(1, Sesion::count());
    }

    public function test_el_limite_de_mensajes_es_por_sesion_y_no_por_ip(): void
    {
        $omega = $this->postJson('/api/sesiones', ['codigo' => 'OMEGA26']);
        $orange = $this->postJson('/api/sesiones', ['codigo' => 'ORANGE26']);

        $enviar = fn ($inicio) => $this->postJson(
            "/api/sesiones/{$inicio->json('sesion.uuid')}/mensajes",
            ['contenido' => '¿Cómo dan los turnos hoy?'],
            ['X-Sesion-Token' => $inicio->json('token')],
        );

        // Dos equipos desde la misma IP: cada uno tiene sus propios 10 mensajes por minuto.
        for ($i = 0; $i < 10; $i++) {
            $enviar($omega)->assertCreated();
            $enviar($orange)->assertCreated();
        }

        $enviar($omega)->assertStatus(429);
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

        // El equipo no puede finalizar su propia entrevista.
        $this->postJson("/api/sesiones/{$uuid}/finalizar", [], $headers)->assertNotFound();

        // Solo el docente la finaliza.
        app(SimulacionService::class)->finalizarSesion(Sesion::where('uuid', $uuid)->firstOrFail());

        // Intentar enviar otro mensaje en sesión finalizada -> 409 Conflict
        $this->postJson("/api/sesiones/{$uuid}/mensajes", [
            'contenido' => 'Una pregunta más',
        ], $headers)->assertStatus(409);
    }
}
