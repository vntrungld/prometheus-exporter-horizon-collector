<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\WorkloadRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentProcessesPerQueueCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class CurrentProcessesPerQueueCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new CurrentProcessesPerQueueCollector();

        $this->assertInstanceOf(
            \Vntrungld\PrometheusExporter\Collectors\Collector::class,
            $collector
        );
    }

    public function test_it_registers_gauge_with_correct_name_and_labels(): void
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')
            ->once()
            ->with('Current processes of all queues')
            ->andReturnSelf();
        $gauge->shouldReceive('labels')
            ->once()
            ->with(['queue'])
            ->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_current_processes')
            ->andReturn($gauge);

        $collector = new CurrentProcessesPerQueueCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_empty_workload(): void
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldNotReceive('value');

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentProcessesPerQueueCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_process_count_for_each_queue(): void
    {
        $workload = [
            ['name' => 'default', 'processes' => 5],
            ['name' => 'high', 'processes' => 10],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(5, ['default'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(10, ['high'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentProcessesPerQueueCollector();
        $collector->register($prometheus);
    }

    public function test_it_sorts_queues_by_name(): void
    {
        $workload = [
            ['name' => 'zebra', 'processes' => 1],
            ['name' => 'alpha', 'processes' => 2],
            ['name' => 'beta', 'processes' => 3],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $callOrder = [];
        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('value')->andReturnUsing(function ($processes, $labels) use (&$callOrder, &$gauge) {
            $callOrder[] = $labels[0];
            return $gauge;
        });

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentProcessesPerQueueCollector();
        $collector->register($prometheus);

        $this->assertEquals(['alpha', 'beta', 'zebra'], $callOrder);
    }
}
