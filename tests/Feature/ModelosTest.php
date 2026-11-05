<?php

namespace Tests\Feature;

use App\Enums\Dificultad;
use App\Enums\EstadoSesion;
use App\Enums\RolMensaje;
use App\Models\Equipo;
use App\Models\Escenario;
use App\Models\Sesion;
use App\Services\Equipos\GeneradorCodigoEquipo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_el_escenario_y_los_equipos_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $clinica = Escenario::firstWhere('nombre', 'Sistema de turnos para una clínica');

        $this->assertNotNull($clinica);
        $this->assertSame(Dificultad::Intermedia, $clinica->dificultad);
        $this->assertIsArray($clinica->necesidades);
        $this->assertSame('nec-1', $clinica->necesidades[0]['id']);
        $this->assertSame($clinica->id, Equipo::buscarPorCodigo('OMEGA26')->escenario_id);
    }

    public function test_el_codigo_de_equipo_se_normaliza_y_se_busca_sin_importar_mayusculas(): void
    {
        $equipo = Equipo::factory()->create(['codigo' => ' omega 26 ']);

        $this->assertSame('OMEGA26', $equipo->codigo);
        $this->assertTrue($equipo->is(Equipo::buscarPorCodigo('Omega26')));
        $this->assertNull(Equipo::buscarPorCodigo('NOEXISTE'));
    }

    public function test_el_generador_crea_codigos_legibles_y_unicos(): void
    {
        $generador = new GeneradorCodigoEquipo;

        $codigo = $generador->generar('Equipo Ñandú');

        $this->assertMatchesRegularExpression('/^NANDU-[A-HJKMNP-Z2-9]{3}$/', $codigo);
        $this->assertNotSame($codigo, $generador->generar('Equipo Ñandú'));
    }

    public function test_la_sesion_usa_uuid_y_valida_su_token(): void
    {
        $sesion = Sesion::factory()->make();
        $token = $sesion->generarToken();
        $sesion->save();

        $this->assertTrue(str($sesion->uuid)->isUuid());
        $this->assertSame('uuid', $sesion->getRouteKeyName());
        $this->assertSame(64, strlen($sesion->token_hash));
        $this->assertNotSame($token, $sesion->token_hash); // se guarda el hash, no el token
        $this->assertTrue($sesion->verificarToken($token));
        $this->assertFalse($sesion->verificarToken('token-incorrecto'));
        $this->assertArrayNotHasKey('token_hash', $sesion->toArray());
    }

    public function test_la_sesion_guarda_snapshot_y_se_puede_finalizar(): void
    {
        $sesion = Sesion::factory()->create();

        $this->assertSame($sesion->escenario->nombre, $sesion->escenario_snapshot['nombre']);
        $this->assertTrue($sesion->estaActiva());

        $sesion->finalizar();

        $this->assertSame(EstadoSesion::Finalizada, $sesion->fresh()->estado);
        $this->assertNotNull($sesion->fresh()->fin);
    }

    public function test_los_mensajes_se_ordenan_cronologicamente(): void
    {
        $sesion = Sesion::factory()->create();
        $sesion->mensajes()->create(['rol' => RolMensaje::Cliente, 'contenido' => 'Hola, soy el cliente.']);
        $sesion->mensajes()->create(['rol' => RolMensaje::Alumno, 'contenido' => '¿Cuál es el problema?']);

        $roles = $sesion->mensajes()->get()->pluck('rol');

        $this->assertSame([RolMensaje::Cliente, RolMensaje::Alumno], $roles->all());
    }

    public function test_borrar_un_equipo_es_logico_y_conserva_sus_sesiones(): void
    {
        $sesion = Sesion::factory()->create();

        $sesion->equipo->delete();

        $this->assertSoftDeleted('equipos', ['id' => $sesion->equipo_id]);
        $this->assertNotNull($sesion->fresh()->equipo); // la relación incluye equipos borrados
    }
}
