<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\WorkloadRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentWaitCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class CurrentWaitCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new CurrentWaitCollector();

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
        $gauge->shouldReceive('labels')
            ->once()
            ->with(['queue'])
            ->andReturnSelf();
        $gauge->shouldReceive('help')
            ->once()
            ->with('Current wait time of all queues')
            ->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_current_wait')
            ->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_empty_workload(): void
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([]);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldNotReceive('value');

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_wait_time_for_each_queue(): void
    {
        $workload = [
            ['name' => 'default', 'wait' => 5.5],
            ['name' => 'high', 'wait' => 0.2],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(5.5, ['default'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0.2, ['high'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_split_queues(): void
    {
        $workload = [
            [
                'name' => 'default',
                'wait' => 10,
                'split_queues' => collect([
                    ['name' => 'default:shard1', 'wait' => 6.0],
                    ['name' => 'default:shard2', 'wait' => 4.0],
                ]),
            ],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(6.0, ['default:shard1'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(4.0, ['default:shard2'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_mixed_split_and_regular_queues(): void
    {
        $workload = [
            [
                'name' => 'default',
                'wait' => 10,
                'split_queues' => collect([
                    ['name' => 'default:shard1', 'wait' => 6.0],
                ]),
            ],
            ['name' => 'high', 'wait' => 1.5],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(6.0, ['default:shard1'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(1.5, ['high'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);
    }

    public function test_it_sorts_queues_by_name(): void
    {
        $workload = [
            ['name' => 'zebra', 'wait' => 1.0],
            ['name' => 'alpha', 'wait' => 2.0],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $callOrder = [];
        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->andReturnUsing(function ($wait, $labels) use (&$callOrder, &$gauge) {
            $callOrder[] = $labels[0];
            return $gauge;
        });

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);

        $this->assertEquals(['alpha', 'zebra'], $callOrder);
    }

    public function test_it_handles_zero_wait_time(): void
    {
        $workload = [
            ['name' => 'default', 'wait' => 0],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0, ['default'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWaitCollector();
        $collector->register($prometheus);
    }
}
