<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = (string) DB::connection($connection)->getDatabaseName();
        $isMemoryDatabase = $connection === 'sqlite' && $database === ':memory:';

        if (!$isMemoryDatabase && !str_ends_with($database, '_testing')) {
            throw new \RuntimeException(
                "Las pruebas no pueden ejecutarse sobre la base de datos [{$database}]."
            );
        }
    }
}
