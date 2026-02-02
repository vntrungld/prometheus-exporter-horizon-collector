<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit;

use Vntrungld\PrometheusExporter\Collectors\CollectorSet;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentMasterSupervisorCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentProcessesPerQueueCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentWaitCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentWorkloadCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\FailedJobsPerHourCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\HorizonStatusCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\JobsPerMinuteCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\RecentJobsCollector;
use Vntrungld\PrometheusExporterHorizonCollector\HorizonCollectorSet;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class HorizonCollectorSetTest extends TestCase
{
    public function test_it_implements_collector_set_interface(): void
    {
        $collectorSet = new HorizonCollectorSet();

        $this->assertInstanceOf(CollectorSet::class, $collectorSet);
    }

    public function test_it_returns_array_of_collectors(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        $this->assertIsArray($collectors);
    }

    public function test_it_contains_all_horizon_collectors(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        $expectedCollectors = [
            CurrentMasterSupervisorCollector::class,
            CurrentProcessesPerQueueCollector::class,
            CurrentWorkloadCollector::class,
            CurrentWaitCollector::class,
            FailedJobsPerHourCollector::class,
            HorizonStatusCollector::class,
            JobsPerMinuteCollector::class,
            RecentJobsCollector::class,
        ];

        foreach ($expectedCollectors as $expectedCollector) {
            $this->assertContains(
                $expectedCollector,
                $collectors,
                "Expected collector {$expectedCollector} not found in HorizonCollectorSet"
            );
        }
    }

    public function test_it_returns_exactly_eight_collectors(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        $this->assertCount(8, $collectors);
    }

    public function test_all_collectors_are_valid_class_names(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        foreach ($collectors as $collector) {
            $this->assertTrue(
                class_exists($collector),
                "Collector class {$collector} does not exist"
            );
        }
    }

    public function test_all_collectors_implement_collector_interface(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        foreach ($collectors as $collectorClass) {
            $interfaces = class_implements($collectorClass);
            $this->assertContains(
                \Vntrungld\PrometheusExporter\Collectors\Collector::class,
                $interfaces,
                "Collector {$collectorClass} does not implement Collector interface"
            );
        }
    }

    public function test_collectors_are_unique(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        $uniqueCollectors = array_unique($collectors);

        $this->assertCount(
            count($uniqueCollectors),
            $collectors,
            'HorizonCollectorSet contains duplicate collectors'
        );
    }
}
