<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Feature;

use Vntrungld\PrometheusExporterHorizonCollector\PrometheusExporterHorizonCollectorServiceProvider;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered(): void
    {
        $loadedProviders = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(
            PrometheusExporterHorizonCollectorServiceProvider::class,
            $loadedProviders
        );
    }

    public function test_service_provider_provides_correct_services(): void
    {
        $provider = new PrometheusExporterHorizonCollectorServiceProvider($this->app);
        $provides = $provider->provides();

        $this->assertIsArray($provides);
        $this->assertContains('prometheus-exporter-horizon-collector', $provides);
    }

    public function test_boot_method_runs_without_errors(): void
    {
        $provider = new PrometheusExporterHorizonCollectorServiceProvider($this->app);

        // Should not throw any exceptions
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_register_method_runs_without_errors(): void
    {
        $provider = new PrometheusExporterHorizonCollectorServiceProvider($this->app);

        // Should not throw any exceptions
        $provider->register();

        $this->assertTrue(true);
    }
}
