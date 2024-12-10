<?php

namespace Vntrungld\PrometheusExporterHorizonCollector;

use Vntrungld\PrometheusExporter\Collectors\CollectorSet;

class HorizonCollectorSet implements CollectorSet
{
    /**
     * @inheritDoc
     */
    public function collectors(): array
    {
        return [
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentMasterSupervisorCollector::class,
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentProcessesPerQueueCollector::class,
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentWorkloadCollector::class,
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\FailedJobsPerHourCollector::class,
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\HorizonStatusCollector::class,
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\JobsPerMinuteCollector::class,
            \Vntrungld\PrometheusExporterHorizonCollector\Collectors\RecentJobsCollector::class,
        ];
    }
}