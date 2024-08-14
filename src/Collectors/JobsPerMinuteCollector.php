<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\MetricsRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class JobsPerMinuteCollector implements Collector
{
    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $prometheus->addGauge('horizon_jobs_per_minute')
            ->help('The number of jobs per minute')
            ->value(app(MetricsRepository::class)->jobsProcessedPerMinute());
    }
}
