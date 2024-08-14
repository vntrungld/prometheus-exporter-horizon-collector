<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class CurrentMasterSupervisorCollector implements Collector
{
    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $prometheus->addGauge('horizon_master_supervisors')
            ->help('The number of master supervisors')
            ->value(count(app(MasterSupervisorRepository::class)->all()));
    }
}
