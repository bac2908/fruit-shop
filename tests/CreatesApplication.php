<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing')) {
            $connection = (string) config('database.default');
            $database = (string) config("database.connections.{$connection}.database");
            $isSafeMysqlDatabase = $connection === 'mysql'
                && preg_match('/(^|[_-])test(ing)?($|[_-])/i', $database) === 1;
            $isSafeInMemoryDatabase = $connection === 'sqlite' && $database === ':memory:';

            if (! $isSafeMysqlDatabase && ! $isSafeInMemoryDatabase) {
                throw new \RuntimeException(
                    "Refusing to run tests against database [{$database}] on connection [{$connection}]. "
                    .'Use a dedicated database whose name contains test/testing.'
                );
            }
        }

        return $app;
    }
}
