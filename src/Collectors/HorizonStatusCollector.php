<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Collectors;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Vntrungld\PrometheusExporter\Collectors\Collector;
use Vntrungld\PrometheusExporter\Prometheus;

class HorizonStatusCollector implements Collector
{
    protected const INACTIVE = -1;

    protected const PAUSED = 0;

    protected const RUNNING = 1;


    /**
     * @inheritDoc
     */
    public function register(Prometheus $prometheus): void
    {
        $prometheus->addGauge('horizon_status')
            ->help('The status of Horizon, -1 = inactive, 0 = paused, 1 = running')
            ->value($this->getStatus());
    }

    /**
     * Get the status of Horizon.
     *
     * @return int
     */
    protected function getStatus()
    {
        if (! $masters = app(MasterSupervisorRepository::class)->all()) {
            return self::INACTIVE;
        }

        $isPaused = collect($masters)
            ->contains(fn ($master) => $master->status === 'paused');

        return $isPaused
            ? self::PAUSED
            : self::RUNNING;
    }
}
