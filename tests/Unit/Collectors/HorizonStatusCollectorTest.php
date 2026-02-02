<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\HorizonStatusCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class HorizonStatusCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new HorizonStatusCollector();

        $this->assertInstanceOf(
            \Vntrungld\PrometheusExporter\Collectors\Collector::class,
            $collector
        );
    }

    public function test_it_registers_gauge_with_correct_name_and_help(): void
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')
            ->once()
            ->with('The status of Horizon, -1 = inactive, 0 = paused, 1 = running')
            ->andReturnSelf();
        $gauge->shouldReceive('value')->once()->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_status')
            ->andReturn($gauge);

        $collector = new HorizonStatusCollector();
        $collector->register($prometheus);
    }

    public function test_it_returns_inactive_when_no_master_supervisors(): void
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn([]);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(-1)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new HorizonStatusCollector();
        $collector->register($prometheus);
    }

    public function test_it_returns_inactive_when_null_master_supervisors(): void
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn(null);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(-1)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new HorizonStatusCollector();
        $collector->register($prometheus);
    }

    public function test_it_returns_running_when_all_supervisors_are_running(): void
    {
        $supervisors = [
            (object) ['name' => 'supervisor-1', 'status' => 'running'],
            (object) ['name' => 'supervisor-2', 'status' => 'running'],
        ];

        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn($supervisors);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(1)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new HorizonStatusCollector();
        $collector->register($prometheus);
    }

    public function test_it_returns_paused_when_any_supervisor_is_paused(): void
    {
        $supervisors = [
            (object) ['name' => 'supervisor-1', 'status' => 'running'],
            (object) ['name' => 'supervisor-2', 'status' => 'paused'],
        ];

        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn($supervisors);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new HorizonStatusCollector();
        $collector->register($prometheus);
    }

    public function test_it_returns_paused_when_all_supervisors_are_paused(): void
    {
        $supervisors = [
            (object) ['name' => 'supervisor-1', 'status' => 'paused'],
            (object) ['name' => 'supervisor-2', 'status' => 'paused'],
        ];

        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->once()->andReturn($supervisors);
        $this->app->instance(MasterSupervisorRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new HorizonStatusCollector();
        $collector->register($prometheus);
    }
}
