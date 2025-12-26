<?php

namespace Yousefkadah\Pelecard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Yousefkadah\Pelecard\PelecardServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            PelecardServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup Pelecard config
        $app['config']->set('pelecard.terminal', 'test_terminal');
        $app['config']->set('pelecard.user', 'test_user');
        $app['config']->set('pelecard.password', 'test_password');
        $app['config']->set('pelecard.environment', 'sandbox');
    }
}
