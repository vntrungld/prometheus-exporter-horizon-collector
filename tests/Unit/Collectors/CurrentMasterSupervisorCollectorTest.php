<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\CurrentMasterSupervisorCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class CurrentMasterSupervisorCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new CurrentMasterSupervisorCollector();

        $this->assertInstanceOf(
            \Vntrungld\PrometheusExporter\Collectors\Collector::class,
            $collector
        );
    }

    public function test_it_registers_gauge_with_correct_name(): void
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->once()->with('The number of master supervisors')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_master_supervisors')
            ->andReturn($gauge);

        $collector = new CurrentMasterSupervisorCollector();
        $collector->register($prometheus);
    }

    public function test_it_counts_zero_supervisors_when_none_exist(): void
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentMasterSupervisorCollector();
        $collector->register($prometheus);
    }

    public function test_it_counts_multiple_supervisors(): void
    {
        $supervisors = [
            (object) ['name' => 'supervisor-1'],
            (object) ['name' => 'supervisor-2'],
            (object) ['name' => 'supervisor-3'],
        ];

        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn($supervisors);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(3)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new CurrentMasterSupervisorCollector();
        $collector->register($prometheus);
    }
}
