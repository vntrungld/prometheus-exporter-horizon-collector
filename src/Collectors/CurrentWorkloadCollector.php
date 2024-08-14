<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\WorkloadRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class CurrentWorkloadCollector implements Collector
{
    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $gauge = $prometheus->addGauge('horizon_current_workload')
            ->labels(['queue'])
            ->help('Current workload of all queues');

        collect(app(WorkloadRepository::class)->get())
            ->sortBy('name')
            ->values()
            ->each(function ($workload) use ($gauge) {
                $gauge->value($workload['processes'], [$workload['name']]);
            })
            ->toArray();
    }
}
