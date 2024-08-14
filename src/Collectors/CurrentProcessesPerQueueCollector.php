<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\WorkloadRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class CurrentProcessesPerQueueCollector implements Collector
{
    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $gauge = $prometheus->addGauge('horizon_current_processes')
            ->help('Current processes of all queues')
            ->labels(['queue']);

        collect(app(WorkloadRepository::class)->get())
            ->sortBy('name')
            ->values()
            ->each(function (array $workload) use ($gauge) {
                $gauge->value($workload['processes'], [$workload['name']]);
            });
    }
}
