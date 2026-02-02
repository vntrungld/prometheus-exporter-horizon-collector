<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Feature;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\HorizonCollectorSet;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class CollectorIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockAllRepositories(): void
    {
        // Mock MasterSupervisorRepository
        $masterSupervisorRepo = Mockery::mock(MasterSupervisorRepository::class);
        $masterSupervisorRepo->shouldReceive('all')->andReturn([
            (object) ['name' => 'supervisor-1', 'status' => 'running'],
        ]);
        $this->app->instance(MasterSupervisorRepository::class, $masterSupervisorRepo);

        // Mock WorkloadRepository
        $workloadRepo = Mockery::mock(WorkloadRepository::class);
        $workloadRepo->shouldReceive('get')->andReturn([
            ['name' => 'default', 'length' => 100, 'wait' => 5.0, 'processes' => 3],
            ['name' => 'high', 'length' => 50, 'wait' => 1.0, 'processes' => 5],
        ]);
        $this->app->instance(WorkloadRepository::class, $workloadRepo);

        // Mock JobRepository
        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->shouldReceive('countRecentlyFailed')->andReturn(2);
        $jobRepo->shouldReceive('countRecent')->andReturn(100);
        $this->app->instance(JobRepository::class, $jobRepo);

        // Mock MetricsRepository
        $metricsRepo = Mockery::mock(MetricsRepository::class);
        $metricsRepo->shouldReceive('jobsProcessedPerMinute')->andReturn(50);
        $this->app->instance(MetricsRepository::class, $metricsRepo);
    }

    public function test_all_collectors_can_be_instantiated_and_registered(): void
    {
        $this->mockAllRepositories();

        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('value')->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        foreach ($collectors as $collectorClass) {
            $collector = $this->app->make($collectorClass);
            $collector->register($prometheus);
        }

        // If we get here without exceptions, all collectors work
        $this->assertTrue(true);
    }

    public function test_collectors_are_instantiable_via_service_container(): void
    {
        $collectorSet = new HorizonCollectorSet();
        $collectors = $collectorSet->collectors();

        foreach ($collectors as $collectorClass) {
            $collector = $this->app->make($collectorClass);

            $this->assertInstanceOf(
                \Vntrungld\PrometheusExporter\Collectors\Collector::class,
                $collector,
                "Failed to instantiate {$collectorClass} via service container"
            );
        }
    }

    public function test_collector_set_can_be_resolved_from_container(): void
    {
        $collectorSet = $this->app->make(HorizonCollectorSet::class);

        $this->assertInstanceOf(HorizonCollectorSet::class, $collectorSet);
        $this->assertCount(8, $collectorSet->collectors());
    }

    public function test_all_collectors_register_correct_metric_names(): void
    {
        $this->mockAllRepositories();

        $expectedMetricNames = [
            'horizon_master_supervisors',
            'horizon_current_processes',
            'horizon_current_workload',
            'horizon_current_wait',
            'horizon_failed_jobs_per_hour',
            'horizon_status',
            'horizon_jobs_per_minute',
            'horizon_recent_jobs',
        ];

        $registeredMetrics = [];

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('value')->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->andReturnUsing(function ($name) use (&$registeredMetrics, $gauge) {
                $registeredMetrics[] = $name;
                return $gauge;
            });

        $collectorSet = new HorizonCollectorSet();
        foreach ($collectorSet->collectors() as $collectorClass) {
            $collector = $this->app->make($collectorClass);
            $collector->register($prometheus);
        }

        sort($expectedMetricNames);
        sort($registeredMetrics);

        $this->assertEquals($expectedMetricNames, $registeredMetrics);
    }

    public function test_collectors_handle_empty_repository_data_gracefully(): void
    {
        // Mock all repositories to return empty data
        $masterSupervisorRepo = Mockery::mock(MasterSupervisorRepository::class);
        $masterSupervisorRepo->shouldReceive('all')->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $masterSupervisorRepo);

        $workloadRepo = Mockery::mock(WorkloadRepository::class);
        $workloadRepo->shouldReceive('get')->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $workloadRepo);

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->shouldReceive('countRecentlyFailed')->andReturn(0);
        $jobRepo->shouldReceive('countRecent')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobRepo);

        $metricsRepo = Mockery::mock(MetricsRepository::class);
        $metricsRepo->shouldReceive('jobsProcessedPerMinute')->andReturn(0);
        $this->app->instance(MetricsRepository::class, $metricsRepo);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('value')->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collectorSet = new HorizonCollectorSet();
        foreach ($collectorSet->collectors() as $collectorClass) {
            $collector = $this->app->make($collectorClass);
            $collector->register($prometheus);
        }

        // If we get here without exceptions, collectors handle empty data gracefully
        $this->assertTrue(true);
    }
}
