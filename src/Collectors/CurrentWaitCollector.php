<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\WorkloadRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class CurrentWaitCollector implements Collector
{
    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $gauge = $prometheus->addGauge('horizon_current_wait')
            ->labels(['queue'])
            ->help('Current wait time of all queues');

        collect(app(WorkloadRepository::class)->get())
            ->sortBy('name')
            ->values()
            ->each(function ($workload) use ($gauge) {
                if (isset($workload['split_queues']) && $workload['split_queues']) {
                    $workload['split_queues']->each(function ($queue) use ($gauge) {
                        $gauge->value($queue['wait'], [$queue['name']]);
                    });

                    return;
                }

                $gauge->value($workload['wait'], [$workload['name']]);
            })
            ->toArray();
    }
}

