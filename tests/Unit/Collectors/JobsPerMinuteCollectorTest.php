<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\MetricsRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\JobsPerMinuteCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class JobsPerMinuteCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new JobsPerMinuteCollector();

        $this->assertInstanceOf(
            \Vntrungld\PrometheusExporter\Collectors\Collector::class,
            $collector
        );
    }

    public function test_it_registers_gauge_with_correct_name_and_help(): void
    {
        $repository = Mockery::mock(MetricsRepository::class);
        $repository->shouldReceive('jobsProcessedPerMinute')->once()->andReturn(0);
        $this->app->instance(MetricsRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')
            ->once()
            ->with('The number of jobs per minute')
            ->andReturnSelf();
        $gauge->shouldReceive('value')->once()->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_jobs_per_minute')
            ->andReturn($gauge);

        $collector = new JobsPerMinuteCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_zero_jobs_per_minute(): void
    {
        $repository = Mockery::mock(MetricsRepository::class);
        $repository->shouldReceive('jobsProcessedPerMinute')->once()->andReturn(0);
        $this->app->instance(MetricsRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new JobsPerMinuteCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_jobs_per_minute(): void
    {
        $repository = Mockery::mock(MetricsRepository::class);
        $repository->shouldReceive('jobsProcessedPerMinute')->once()->andReturn(150);
        $this->app->instance(MetricsRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(150)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new JobsPerMinuteCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_decimal_values(): void
    {
        $repository = Mockery::mock(MetricsRepository::class);
        $repository->shouldReceive('jobsProcessedPerMinute')->once()->andReturn(45.5);
        $this->app->instance(MetricsRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(45.5)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new JobsPerMinuteCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_high_throughput(): void
    {
        $repository = Mockery::mock(MetricsRepository::class);
        $repository->shouldReceive('jobsProcessedPerMinute')->once()->andReturn(10000);
        $this->app->instance(MetricsRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(10000)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new JobsPerMinuteCollector();
        $collector->register($prometheus);
    }
}
