<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Vntrungld\PrometheusExporterHorizonCollector\PrometheusExporterHorizonCollectorServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use MockeryPHPUnitIntegration;

    protected function getPackageProviders($app): array
    {
        return [
            PrometheusExporterHorizonCollectorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('prometheus-exporter.namespace', 'test');
    }
}
