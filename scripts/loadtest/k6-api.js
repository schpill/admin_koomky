import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    api_load: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 100),
      duration: __ENV.DURATION || '2m',
    },
  },
  thresholds: {
    'http_req_failed{kind:api}': ['rate<0.01'],
    'http_req_duration{kind:api}': ['p(95)<200'],
  },
};

const baseUrl = __ENV.BASE_URL || 'http://localhost:6680/api/v1';
const token = __ENV.BEARER_TOKEN || '';

function authHeaders() {
  return {
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
}

export function setup() {
  const health = http.get(`${baseUrl}/health`, {
    headers: authHeaders(),
    tags: { kind: 'probe', endpoint: 'health' },
  });

  check(health, {
    'health endpoint returns 200': (response) => response.status === 200,
  });

  if (!token) {
    throw new Error('BEARER_TOKEN is required for load testing protected API endpoints.');
  }

  return null;
}

export default function () {
  const dashboard = http.get(`${baseUrl}/dashboard`, {
    headers: authHeaders(),
    tags: { kind: 'api', endpoint: 'dashboard' },
  });

  check(dashboard, {
    'dashboard endpoint returns 200': (response) => response.status === 200,
  });

  sleep(1);
}
