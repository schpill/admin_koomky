<?php

return [
    'slow_request_threshold_ms' => (int) env('SLOW_REQUEST_THRESHOLD_MS', 500),
    'failed_jobs_alert_threshold' => (int) env('FAILED_JOBS_ALERT_THRESHOLD', 10),
];
