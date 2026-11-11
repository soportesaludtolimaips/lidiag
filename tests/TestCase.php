<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Salvaguarda: aborta si las pruebas no apuntan a la BD de test aislada,
     * para no ejecutar nunca contra la base real de producción.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $db = (string) config('database.connections.mysql.database');
        if ($db !== '' && $db !== 'lidiag_test') {
            $this->fail("Las pruebas deben usar la BD 'lidiag_test', no '{$db}'. Revisa phpunit.xml/.env.testing.");
        }
    }
}
