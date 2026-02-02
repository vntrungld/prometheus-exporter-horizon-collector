# Prometheus Exporter Horizon Collector

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A Laravel package that collects metrics from [Laravel Horizon](https://laravel.com/docs/horizon) and exposes them in Prometheus format. Works seamlessly with the [prometheus-exporter](https://github.com/vntrungld/prometheus-exporter) package.

## Features

- Automatic metric collection from Laravel Horizon
- 8 specialized collectors covering queue status, performance, and health
- Per-queue metrics with label-based differentiation
- Support for split queue configurations
- Graceful handling of empty data
- Zero configuration required

## Requirements

- PHP 7.4+
- Laravel 6.x, 7.x, 8.x, 9.x, 10.x, or 11.x
- Laravel Horizon 3.x, 4.x, or 5.x
- [vntrungld/prometheus-exporter](https://github.com/vntrungld/prometheus-exporter) ~1.0

## Installation

Install the package via Composer:

```bash
composer require vntrungld/prometheus-exporter-horizon-collector
```

The package will be auto-discovered by Laravel.

## Configuration

Add the `HorizonCollectorSet` to your prometheus-exporter configuration:

```php
// config/prometheus-exporter.php
'collector_sets' => [
    \Vntrungld\PrometheusExporterHorizonCollector\HorizonCollectorSet::class,
],
```

That's it! The metrics will now be available at your Prometheus exporter endpoint.

## Available Metrics

The package exposes 8 Prometheus gauge metrics:

### Queue Health Metrics

| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `horizon_status` | Gauge | - | Horizon status: -1 (inactive), 0 (paused), 1 (running) |
| `horizon_master_supervisors` | Gauge | - | Number of active master supervisors |

### Queue Performance Metrics

| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `horizon_current_processes` | Gauge | `queue` | Number of worker processes per queue |
| `horizon_current_workload` | Gauge | `queue` | Current queue depth (pending jobs) per queue |
| `horizon_current_wait` | Gauge | `queue` | Current wait time in seconds per queue |

### Job Statistics Metrics

| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `horizon_jobs_per_minute` | Gauge | - | Jobs processed per minute (throughput) |
| `horizon_recent_jobs` | Gauge | - | Number of recently processed jobs |
| `horizon_failed_jobs_per_hour` | Gauge | - | Number of recently failed jobs |

## Example Prometheus Output

```prometheus
# HELP horizon_status The status of Horizon
# TYPE horizon_status gauge
horizon_status 1

# HELP horizon_master_supervisors The number of master supervisors
# TYPE horizon_master_supervisors gauge
horizon_master_supervisors 2

# HELP horizon_current_processes Current processes of all queues
# TYPE horizon_current_processes gauge
horizon_current_processes{queue="default"} 4
horizon_current_processes{queue="emails"} 2

# HELP horizon_current_workload Current workload of all queues
# TYPE horizon_current_workload gauge
horizon_current_workload{queue="default"} 150
horizon_current_workload{queue="emails"} 25

# HELP horizon_current_wait Current wait time of all queues
# TYPE horizon_current_wait gauge
horizon_current_wait{queue="default"} 5
horizon_current_wait{queue="emails"} 2

# HELP horizon_jobs_per_minute The number of jobs per minute
# TYPE horizon_jobs_per_minute gauge
horizon_jobs_per_minute 120

# HELP horizon_recent_jobs The number of recent jobs
# TYPE horizon_recent_jobs gauge
horizon_recent_jobs 5000

# HELP horizon_failed_jobs_per_hour The number of recently failed jobs
# TYPE horizon_failed_jobs_per_hour gauge
horizon_failed_jobs_per_hour 3
```

## Horizon Status Values

The `horizon_status` metric reports the overall health of Horizon:

| Value | Status | Description |
|-------|--------|-------------|
| `1` | Running | All supervisors are running normally |
| `0` | Paused | At least one supervisor is paused |
| `-1` | Inactive | No master supervisors are running |

## Architecture

```
HorizonCollectorSet
├── CurrentMasterSupervisorCollector  → MasterSupervisorRepository
├── HorizonStatusCollector            → MasterSupervisorRepository
├── CurrentProcessesPerQueueCollector → WorkloadRepository
├── CurrentWorkloadCollector          → WorkloadRepository
├── CurrentWaitCollector              → WorkloadRepository
├── JobsPerMinuteCollector            → MetricsRepository
├── RecentJobsCollector               → JobRepository
└── FailedJobsPerHourCollector        → JobRepository
```

Each collector:
1. Implements the `Collector` interface
2. Receives dependencies via Laravel's service container
3. Queries Horizon repositories for data
4. Registers a Prometheus gauge metric

## Example Grafana Queries

### Queue Backlog Alert

```promql
horizon_current_workload{queue="default"} > 1000
```

### Processing Rate

```promql
rate(horizon_jobs_per_minute[5m])
```

### Failure Rate

```promql
horizon_failed_jobs_per_hour / horizon_recent_jobs * 100
```

### Wait Time by Queue

```promql
max(horizon_current_wait) by (queue)
```

### Horizon Health Check

```promql
horizon_status == 1
```

## Testing

Run the test suite:

```bash
composer test
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit
```

The package includes comprehensive tests:

- Unit tests for all 8 collectors
- Unit tests for the collector set
- Feature tests for service provider integration
- Integration tests for full collector lifecycle

## Project Structure

```
src/
├── PrometheusExporterHorizonCollectorServiceProvider.php
├── HorizonCollectorSet.php
└── Collectors/
    ├── CurrentMasterSupervisorCollector.php
    ├── CurrentProcessesPerQueueCollector.php
    ├── CurrentWaitCollector.php
    ├── CurrentWorkloadCollector.php
    ├── FailedJobsPerHourCollector.php
    ├── HorizonStatusCollector.php
    ├── JobsPerMinuteCollector.php
    └── RecentJobsCollector.php

tests/
├── TestCase.php
├── Feature/
│   ├── CollectorIntegrationTest.php
│   └── ServiceProviderTest.php
└── Unit/
    ├── HorizonCollectorSetTest.php
    └── Collectors/
        └── [Individual collector tests]
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`composer test`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [Lam Duc Trung](https://github.com/vntrungld)

## Related Packages

- [vntrungld/prometheus-exporter](https://github.com/vntrungld/prometheus-exporter) - Base Prometheus exporter for Laravel
- [laravel/horizon](https://github.com/laravel/horizon) - Laravel Horizon queue monitoring
