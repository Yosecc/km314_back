<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\FormControl;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FormControlMobileNormalizationTest extends TestCase
{
    public function test_legacy_form_values_are_normalized_as_string_lists(): void
    {
        $method = new ReflectionMethod(FormControl::class, 'normalizeStringList');
        $controller = new FormControl;

        $this->assertSame(['Proveedor'], $method->invoke($controller, 'Proveedor'));
        $this->assertSame(['Inquilino'], $method->invoke($controller, '["Inquilino"]'));
        $this->assertSame(['lote'], $method->invoke($controller, ['lote']));
        $this->assertSame([], $method->invoke($controller, null));
    }
}
