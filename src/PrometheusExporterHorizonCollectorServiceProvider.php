<?php

namespace Vntrungld\PrometheusExporterHorizonCollector;

use Illuminate\Support\ServiceProvider;

class PrometheusExporterHorizonCollectorServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['prometheus-exporter-horizon-collector'];
    }
}
