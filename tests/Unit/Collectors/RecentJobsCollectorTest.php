<?php

namespace Vntrungld\PrometheusExporterHorizonCollector\Tests\Unit\Collectors;

use Laravel\Horizon\Contracts\JobRepository;
use Mockery;
use Vntrungld\PrometheusExporter\MetricTypes\Gauge;
use Vntrungld\PrometheusExporter\Prometheus;
use Vntrungld\PrometheusExporterHorizonCollector\Collectors\RecentJobsCollector;
use Vntrungld\PrometheusExporterHorizonCollector\Tests\TestCase;

class RecentJobsCollectorTest extends TestCase
{
    public function test_it_implements_collector_interface(): void
    {
        $collector = new RecentJobsCollector();

        $this->assertInstanceOf(
            \Vntrungld\PrometheusExporter\Collectors\Collector::class,
            $collector
        );
    }

    public function test_it_registers_gauge_with_correct_name_and_help(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecent')->once()->andReturn(0);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')
            ->once()
            ->with('The number of recent jobs')
            ->andReturnSelf();
        $gauge->shouldReceive('value')->once()->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')
            ->once()
            ->with('horizon_recent_jobs')
            ->andReturn($gauge);

        $collector = new RecentJobsCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_zero_recent_jobs(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecent')->once()->andReturn(0);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(0)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new RecentJobsCollector();
        $collector->register($prometheus);
    }

    public function test_it_records_recent_jobs_count(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecent')->once()->andReturn(500);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(500)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new RecentJobsCollector();
        $collector->register($prometheus);
    }

    public function test_it_handles_large_job_counts(): void
    {
        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('countRecent')->once()->andReturn(1000000);
        $this->app->instance(JobRepository::class, $repository);

        $gauge = Mockery::mock(Gauge::class);
        $gauge->shouldReceive('help')->andReturnSelf();
        $gauge->shouldReceive('value')->once()->with(1000000)->andReturnSelf();

        $prometheus = Mockery::mock(Prometheus::class);
        $prometheus->shouldReceive('addGauge')->andReturn($gauge);

        $collector = new RecentJobsCollector();
        $collector->register($prometheus);
    }
}
