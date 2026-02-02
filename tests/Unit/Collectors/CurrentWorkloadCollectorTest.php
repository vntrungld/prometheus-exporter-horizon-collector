<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\WorkloadRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentWorkloadCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class CurrentWorkloadCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new CurrentWorkloadCollector();

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
            ->with('Current workload of all queues')
            ->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_current_workload')
            ->andReturn($gauge);

        $collector = new CurrentWorkloadCollector();
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

        $collector = new CurrentWorkloadCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_workload_length_for_each_queue(): void
    {
        $workload = [
            ['name' => 'default', 'length' => 100],
            ['name' => 'high', 'length' => 250],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(100, ['default'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(250, ['high'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWorkloadCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_split_queues(): void
    {
        $workload = [
            [
                'name' => 'default',
                'length' => 100,
                'split_queues' => collect([
                    ['name' => 'default:shard1', 'length' => 60],
                    ['name' => 'default:shard2', 'length' => 40],
                ]),
            ],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(60, ['default:shard1'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(40, ['default:shard2'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWorkloadCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_mixed_split_and_regular_queues(): void
    {
        $workload = [
            [
                'name' => 'default',
                'length' => 100,
                'split_queues' => collect([
                    ['name' => 'default:shard1', 'length' => 60],
                ]),
            ],
            ['name' => 'high', 'length' => 50],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(60, ['default:shard1'])->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(50, ['high'])->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWorkloadCollector();
        $collector->register($prometheus);
    }

    public function test_it_sorts_queues_by_name(): void
    {
        $workload = [
            ['name' => 'zebra', 'length' => 1],
            ['name' => 'alpha', 'length' => 2],
        ];

        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn($workload);
        $this->app->instance(WorkloadRepository::class, $repository);

        $callOrder = [];
        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('labels')->andReturnSelf();
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->andReturnUsing(function ($length, $labels) use (&$callOrder, &$gauge) {
            $callOrder[] = $labels[0];
            return $gauge;
        });

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentWorkloadCollector();
        $collector->register($prometheus);

        $this->assertEquals(['alpha', 'zebra'], $callOrder);
    }
}
