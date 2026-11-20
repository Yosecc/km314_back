<?php

namespace Tests\Unit;

use App\Filament\Resources\EmployeeResource;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EmployeeResourceFileOrderTest extends TestCase
{
    public function test_files_follow_the_configured_order_without_losing_existing_data(): void
    {
        $state = [
            'record-20' => [
                'id' => 20,
                'name' => 'Documento B',
                'file' => 'documento-b.pdf',
                'is_required' => false,
            ],
            'record-10' => [
                'id' => 10,
                'name' => 'Documento A',
                'file' => 'documento-a.pdf',
            ],
            'record-30' => [
                'id' => 30,
                'name' => 'Documento anterior',
                'file' => 'documento-anterior.pdf',
            ],
        ];

        $configured = [
            [
                'name' => 'Documento B',
                'is_required' => true,
                'is_required_fecha_vencimiento' => false,
            ],
            [
                'name' => 'Documento C',
                'is_required' => false,
                'is_required_fecha_vencimiento' => true,
            ],
            [
                'name' => 'Documento A',
                'is_required' => false,
                'is_required_fecha_vencimiento' => false,
            ],
        ];

        $method = new ReflectionMethod(EmployeeResource::class, 'orderConfiguredFiles');
        $method->setAccessible(true);

        $result = $method->invoke(null, $state, $configured);

        $this->assertSame(
            ['Documento B', 'Documento C', 'Documento A', 'Documento anterior'],
            array_values(array_column($result, 'name')),
        );
        $this->assertSame('documento-b.pdf', $result['record-20']['file']);
        $this->assertTrue($result['record-20']['is_required']);
        $this->assertFalse($result['record-20']['is_historical']);
        $this->assertTrue($result['record-30']['is_historical']);
        $this->assertFalse($result['record-30']['is_required']);
        $this->assertFalse($result['record-30']['is_required_fecha_vencimiento']);
        $this->assertArrayHasKey('record-10', $result);
        $this->assertArrayHasKey('record-30', $result);
    }

    public function test_historical_files_are_not_updated(): void
    {
        $method = new ReflectionMethod(EmployeeResource::class, 'prepareFileForSave');
        $method->setAccessible(true);

        $result = $method->invoke(null, [
            'name' => 'Documento anterior',
            'file' => 'documento-anterior.pdf',
            'is_required' => false,
            'is_required_fecha_vencimiento' => false,
            'is_historical' => true,
        ]);

        $this->assertNull($result);
    }

    public function test_configured_file_metadata_is_not_sent_to_the_model(): void
    {
        $method = new ReflectionMethod(EmployeeResource::class, 'prepareFileForSave');
        $method->setAccessible(true);

        $result = $method->invoke(null, [
            'name' => 'Documento A',
            'file' => 'documento-a.pdf',
            'is_required' => true,
            'is_required_fecha_vencimiento' => true,
            'is_historical' => false,
        ]);

        $this->assertSame([
            'name' => 'Documento A',
            'file' => 'documento-a.pdf',
        ], $result);
    }
}
