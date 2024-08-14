<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\JobRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class RecentJobsCollector implements Collector
{
    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $prometheus->addGauge('horizon_recent_jobs')
            ->help('The number of recent jobs')
            ->value(app(JobRepository::class)->countRecent());
    }
}
