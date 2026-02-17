# Monitoring Setup (Prometheus + Grafana)

This project ships with a monitoring stack for application and infrastructure observability:

- Prometheus
- Grafana
- PostgreSQL exporter
- Redis exporter
- Node exporter

## 1. Services

Development (`docker-compose.yml`) adds:

- `prometheus` on `http://localhost:9090`
- `grafana` on `http://localhost:3001`
- `postgres-exporter` on `:9187`
- `redis-exporter` on `:9121`
- `node-exporter` on `:9100`

Production (`docker-compose.prod.yml`) includes the same services with resource limits.

## 2. Metrics Endpoint

The Laravel app exposes Prometheus metrics at:

- `GET /metrics`

The endpoint returns Prometheus text exposition format:

`Content-Type: text/plain; version=0.0.4; charset=UTF-8`

### HTTP Metrics (middleware)

- `http_requests_total{method,path,status}`
- `http_request_duration_seconds` (histogram)
- `http_request_size_bytes` (histogram)

### Application Metrics

- `koomky_active_users_total`
- `koomky_invoices_generated_total`
- `koomky_campaigns_sent_total`
- `koomky_queue_jobs_waiting{queue}`
- `koomky_queue_jobs_processed_total{queue,status}`
- `koomky_emails_sent_total{type}`

## 3. Prometheus Config

Files:

- `docker/prometheus/prometheus.yml`
- `docker/prometheus/alerts.yml`

Scrape targets:

- app metrics via `nginx:80/metrics`
- `postgres-exporter:9187`
- `redis-exporter:9121`
- `node-exporter:9100`

Alert rules include:

- error rate > 5% (5m)
- p95 latency > 500ms (5m)
- queue depth > 1000 (5m)
- disk usage > 80% (10m)
- db connections > 80 (5m)

## 4. Grafana Provisioning

Provisioned files:

- Datasource: `docker/grafana/provisioning/datasources/datasource.yml`
- Dashboards provider: `docker/grafana/provisioning/dashboards/dashboard.yml`
- Alerting rules: `docker/grafana/provisioning/alerting/alerts.yml`
- Dashboards JSON:
  - `docker/grafana/dashboards/application-overview.json`
  - `docker/grafana/dashboards/business-metrics.json`
  - `docker/grafana/dashboards/infrastructure.json`
  - `docker/grafana/dashboards/database.json`
  - `docker/grafana/dashboards/queue.json`

## 5. Quick Start

```bash
docker compose up -d
```

Then open:

- Prometheus: `http://localhost:9090`
- Grafana: `http://localhost:3001`

## 6. UI Access

The dashboard sidebar includes a direct Grafana link (`/settings` area shortcut).
