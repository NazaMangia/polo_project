<?php

namespace Tests\Feature;

use App\Enums\EstadoSesion;
use App\Models\Equipo;
use App\Models\Escenario;
use App\Models\Sesion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiDocenteTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->docente = User::first() ?: User::factory()->create([
            'email' => 'docente@escuela.edu.ar',
            'password' => Hash::make('docente1234'),
        ]);
    }

    public function test_las_rutas_admin_requieren_autenticacion(): void
    {
        $this->getJson('/api/admin/equipos')->assertUnauthorized();
        $this->getJson('/api/admin/escenarios')->assertUnauthorized();
        $this->getJson('/api/admin/sesiones')->assertUnauthorized();
        $this->postJson('/api/admin/simulador/abrir')->assertUnauthorized();
    }

    public function test_el_docente_abre_y_cierra_el_simulador(): void
    {
        $this->actingAs($this->docente, 'sanctum');

        $this->getJson('/api/admin/simulador')->assertOk()->assertJsonPath('abierto', false);

        $abrir = $this->postJson('/api/admin/simulador/abrir', ['horas' => 2]);
        $abrir->assertOk()->assertJsonPath('abierto', true);
        $this->assertNotNull($abrir->json('abierto_hasta'));

        $this->postJson('/api/admin/simulador/abrir', ['horas' => 50])->assertUnprocessable();

        $this->postJson('/api/admin/simulador/cerrar')
            ->assertOk()
            ->assertJsonPath('abierto', false)
            ->assertJsonPath('abierto_hasta', null);
    }

    public function test_login_docente_devuelve_token_y_permite_acceder_al_panel(): void
    {
        $this->postJson('/api/docente/login', [
            'email' => 'docente@escuela.edu.ar',
            'password' => 'incorrecta',
        ])->assertUnprocessable();

        $login = $this->postJson('/api/docente/login', [
            'email' => 'docente@escuela.edu.ar',
            'password' => 'docente1234',
        ]);

        $login->assertOk()
            ->assertJsonPath('docente.email', 'docente@escuela.edu.ar')
            ->assertJsonStructure(['token', 'docente']);

        $token = $login->json('token');

        $this->getJson('/api/docente/yo', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('email', 'docente@escuela.edu.ar');
    }

    public function test_abm_de_equipos_y_regeneracion_de_codigo(): void
    {
        $escenario = Escenario::firstOrFail();

        // Crear equipo sin enviar código -> lo genera automáticamente
        $crear = $this->actingAs($this->docente)->postJson('/api/admin/equipos', [
            'nombre' => 'Equipo Alfa',
            'escenario_id' => $escenario->id,
        ]);

        $crear->assertCreated()
            ->assertJsonPath('data.nombre', 'Equipo Alfa')
            ->assertJsonPath('data.escenario_id', $escenario->id);

        $equipoId = $crear->json('data.id');
        $codigoInicial = $crear->json('data.codigo');
        $this->assertStringStartsWith('ALFA-', $codigoInicial);

        // Editar estado y código manual
        $editar = $this->actingAs($this->docente)->putJson("/api/admin/equipos/{$equipoId}", [
            'nombre' => 'Equipo Alfa Editado',
            'codigo' => 'alfa99',
            'activo' => false,
            'escenario_id' => $escenario->id,
        ]);

        $editar->assertOk()
            ->assertJsonPath('data.codigo', 'ALFA99')
            ->assertJsonPath('data.activo', false);

        // Regenerar código
        $regenerar = $this->actingAs($this->docente)->postJson("/api/admin/equipos/{$equipoId}/regenerar-codigo");
        $regenerar->assertOk();
        $this->assertNotSame('ALFA99', $regenerar->json('data.codigo'));

        // Eliminar (soft delete)
        $this->actingAs($this->docente)->deleteJson("/api/admin/equipos/{$equipoId}")->assertOk();
        $this->assertSoftDeleted('equipos', ['id' => $equipoId]);
    }

    public function test_abm_de_escenarios_duplicacion_y_vista_previa_de_prompt(): void
    {
        $crear = $this->actingAs($this->docente)->postJson('/api/admin/escenarios', [
            'nombre' => 'Biblioteca Escolar',
            'descripcion' => 'Préstamo de libros en una escuela técnica.',
            'dificultad' => 'basica',
            'activo' => true,
            'problema_principal' => 'No saben qué alumno tiene cada libro.',
            'rol_cliente' => 'Bibliotecario',
            'mensaje_inicial' => 'Hola, soy el bibliotecario de la escuela.',
            'necesidades' => [
                ['texto' => 'Registrar préstamos y devoluciones', 'revelacion' => 'espontaneo'],
            ],
            'reglas_negocio' => [
                ['texto' => 'Máximo 2 libros por alumno', 'revelacion' => 'si_pregunta'],
            ],
        ]);

        $crear->assertCreated()
            ->assertJsonPath('data.nombre', 'Biblioteca Escolar')
            ->assertJsonPath('data.necesidades.0.id', 'nec-1');

        $escenarioId = $crear->json('data.id');

        // Vista previa del prompt
        $prompt = $this->actingAs($this->docente)->getJson("/api/admin/escenarios/{$escenarioId}/prompt");
        $prompt->assertOk();
        $this->assertStringContainsString('Bibliotecario', $prompt->json('prompt'));
        $this->assertStringContainsString('[ESPONTÁNEO] Registrar préstamos y devoluciones', $prompt->json('prompt'));

        // Duplicar escenario
        $duplicar = $this->actingAs($this->docente)->postJson("/api/admin/escenarios/{$escenarioId}/duplicar");
        $duplicar->assertCreated()
            ->assertJsonPath('data.nombre', 'Biblioteca Escolar (Copia)')
            ->assertJsonPath('data.activo', false);
    }

    public function test_listado_detalle_y_cierre_de_sesiones_por_el_docente(): void
    {
        $equipo = Equipo::where('codigo', 'OMEGA26')->firstOrFail();
        $sesion = Sesion::factory()->create([
            'equipo_id' => $equipo->id,
            'escenario_id' => $equipo->escenario_id,
            'escenario_snapshot' => $equipo->escenario->aSnapshot(),
        ]);
        $sesion->mensajes()->create(['rol' => 'cliente', 'contenido' => 'Hola, soy Laura.']);
        $sesion->mensajes()->create(['rol' => 'alumno', 'contenido' => '¿Cómo gestionan los turnos?']);

        $lista = $this->actingAs($this->docente)->getJson('/api/admin/sesiones');
        $lista->assertOk()
            ->assertJsonPath('data.0.id', $sesion->id)
            ->assertJsonPath('data.0.mensajes_count', 2);

        $detalle = $this->actingAs($this->docente)->getJson("/api/admin/sesiones/{$sesion->id}");
        $detalle->assertOk()
            ->assertJsonCount(2, 'data.mensajes');

        $cerrar = $this->actingAs($this->docente)->postJson("/api/admin/sesiones/{$sesion->id}/finalizar");
        $cerrar->assertOk()
            ->assertJsonPath('data.estado', EstadoSesion::Finalizada->value);
    }
}
