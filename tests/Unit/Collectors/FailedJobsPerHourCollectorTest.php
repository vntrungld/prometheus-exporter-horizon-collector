<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\JobRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\FailedJobsPerHourCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class FailedJobsPerHourCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new FailedJobsPerHourCollector();

        $this->assertInstanceOf(
            \Vntrungld\PrometheusExporter\Collectors\Collector::class,
            $collector
        );
    }

    public function test_it_registers_gauge_with_correct_name_and_help(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecentlyFailed')->once()->andReturn(0);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')
            ->once()
            ->with('The number of recently failed jobs')
            ->andReturnSelf();
        $gauge->shouldReceive('value')->once()->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_failed_jobs_per_hour')
            ->andReturn($gauge);

        $collector = new FailedJobsPerHourCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_zero_failed_jobs(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecentlyFailed')->once()->andReturn(0);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new FailedJobsPerHourCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_multiple_failed_jobs(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecentlyFailed')->once()->andReturn(42);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(42)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new FailedJobsPerHourCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_large_failure_counts(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecentlyFailed')->once()->andReturn(999999);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(999999)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new FailedJobsPerHourCollector();
        $collector->register($prometheus);
    }
}
