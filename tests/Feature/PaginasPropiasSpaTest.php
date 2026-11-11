<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Cableado de las páginas propias de LIDiag (Consentimientos, logo por sede y otras plataformas) en la SPA del equipo.
 *
 * Desde la fusión con el servidor (2026-11-11) las páginas internas se sirven con el shell de la SPA (`layouts.app` sin
 * sección `content`) y las pinta Vue Router. Si alguna vez se vuelve a copiar en bloque la versión del equipo, estas
 * pruebas avisan de que se perdieron las rutas del servidor, las rutas de Vue Router o los enlaces del menú.
 */
class PaginasPropiasSpaTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{0: string, 1: string}> [ruta, permiso]
     */
    public static function paginas(): array
    {
        return [
            'consentimientos generar' => ['/consentimientos-generar', 'consentimientos.generar'],
            'consentimientos firmas del médico' => ['/consentimientos-medico', 'consentimientos.firmar_medico'],
            'consentimientos asignación y versiones' => ['/consentimientos-plantillas-config', 'consentimientos.configurar'],
            'consentimientos dashboard' => ['/consentimientos-dashboard', 'consentimientos.estadisticas'],
            'logo y encabezado por sede' => ['/config-sede-logo', 'config.sedes.edit'],
            'otras plataformas' => ['/otras-plataformas.listar-estudio-enviar', 'otrasPlataformas.index'],
        ];
    }

    private function usuario(?string $permiso = null): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Usuario SPA',
            'email' => 'spa_'.uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'password_changed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $usuario = User::findOrFail($id);

        if ($permiso !== null) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            $usuario->givePermissionTo($permiso);
        }

        return $usuario;
    }

    #[DataProvider('paginas')]
    public function test_con_el_permiso_la_pagina_responde_con_el_shell_de_la_spa(string $ruta, string $permiso): void
    {
        $this->withoutVite();

        $respuesta = $this->actingAs($this->usuario($permiso))->get($ruta);

        $this->assertSame(200, $respuesta->getStatusCode(), 'Redirige a: '.$respuesta->headers->get('Location'));
        $respuesta->assertSee('<app-layout', false);
    }

    #[DataProvider('paginas')]
    public function test_sin_el_permiso_o_sin_sesion_la_pagina_no_se_muestra(string $ruta, string $permiso): void
    {
        $this->withoutVite();

        $this->actingAs($this->usuario())->get($ruta)->assertForbidden();

        auth()->logout();
        $this->get($ruta)->assertRedirect();
    }

    public function test_la_pagina_publica_de_firma_sigue_fuera_de_la_spa(): void
    {
        $this->withoutVite();

        $this->get('/firma/consentimiento/token-de-prueba')
            ->assertOk()
            ->assertSee('consentimiento-firma-component', false);
    }

    #[DataProvider('paginas')]
    public function test_vue_router_y_el_menu_de_la_spa_conocen_la_pagina(string $ruta, string $permiso): void
    {
        $router = (string) file_get_contents(base_path('resources/js/router/routes.js'));
        $menu = (string) file_get_contents(base_path('resources/js/layouts/AppSidebar.vue'));

        $this->assertStringContainsString('path: "'.$ruta.'"', $router);
        $this->assertStringContainsString($permiso, $menu.$router);

        // Todas las páginas con menú propio enlazan a su ruta (la de otras plataformas se abre solo por dirección).
        if ($ruta !== '/otras-plataformas.listar-estudio-enviar') {
            $this->assertStringContainsString('to="'.$ruta.'"', $menu);
        }
    }
}
