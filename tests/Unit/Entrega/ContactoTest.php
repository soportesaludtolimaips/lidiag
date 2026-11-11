<?php

namespace Tests\Unit\Entrega;

use App\Services\Entrega\Contacto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Normalización y enmascarado de los datos de contacto del paciente.
 */
class ContactoTest extends TestCase
{
    #[DataProvider('celularesValidos')]
    public function test_reconoce_un_celular_colombiano_en_distintos_formatos(string $entrada): void
    {
        $this->assertSame('573001234567', Contacto::celular($entrada));
    }

    public static function celularesValidos(): array
    {
        return [
            'diez digitos' => ['3001234567'],
            'con 57' => ['573001234567'],
            'con +57 y espacios' => ['+57 300 123 4567'],
            'con 0057' => ['00573001234567'],
            'con guiones' => ['300-123-4567'],
        ];
    }

    #[DataProvider('celularesInvalidos')]
    public function test_descarta_lo_que_no_es_un_celular(?string $entrada): void
    {
        $this->assertNull(Contacto::celular($entrada));
    }

    public static function celularesInvalidos(): array
    {
        return [
            'nulo' => [null],
            'vacio' => [''],
            'fijo de 7 digitos' => ['2765432'],
            'fijo de bogota' => ['6012345678'],
            'muy corto' => ['300123'],
            'muy largo' => ['30012345678'],
            'texto' => ['no tiene'],
        ];
    }

    public function test_usa_el_primer_candidato_que_sea_celular(): void
    {
        $this->assertSame('573109876543', Contacto::celular('2765432', null, '310 987 6543', '3001234567'));
        $this->assertNull(Contacto::celular('2765432', '6012345678'));
    }

    public function test_valida_y_normaliza_el_correo(): void
    {
        $this->assertSame('maria@example.com', Contacto::correo('  Maria@Example.com '));
        $this->assertNull(Contacto::correo('no-es-correo'));
        $this->assertNull(Contacto::correo(null));
        $this->assertNull(Contacto::correo(''));
    }

    public function test_enmascara_el_destino_sin_mostrar_el_dato_completo(): void
    {
        $this->assertSame('+57 3** *** 4567', Contacto::enmascararCelular('573001234567'));
        $this->assertSame('m***@example.com', Contacto::enmascararCorreo('maria@example.com'));
        $this->assertStringNotContainsString('3001234', Contacto::enmascararCelular('573001234567'));
        $this->assertStringNotContainsString('aria', Contacto::enmascararCorreo('maria@example.com'));
    }

    public function test_primer_nombre_solo_con_formato_dicom(): void
    {
        $this->assertSame('Maria', Contacto::primerNombre('RIOS PEREZ^MARIA FERNANDA^^^'));
        $this->assertSame('Juan', Contacto::primerNombre('LOPEZ^JUAN'));
        // Sin '^' no se adivina cuál es el nombre.
        $this->assertNull(Contacto::primerNombre('BATENERO CASTRO MHIA VICTORIA'));
        $this->assertNull(Contacto::primerNombre(null));
        $this->assertNull(Contacto::primerNombre('LOPEZ^^^'));
    }
}
