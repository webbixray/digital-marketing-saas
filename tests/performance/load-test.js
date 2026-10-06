import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '1m', target: 50 },   // Ramp up to 50 users
        { duration: '3m', target: 100 },  // Stay at 100 users
        { duration: '1m', target: 0 },    // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'],      // 95% requests < 500ms
        http_req_failed: ['rate<0.01'],      // Error rate < 1%
        'checks': ['rate>0.95'],             // 95% checks pass
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const API_KEY = __ENV.API_KEY || '';

export default function () {
    const params = {
        headers: {
            'Content-Type': 'application/json',
            ...(API_KEY && { 'Authorization': `Bearer ${API_KEY}` }),
        },
    };

    // Test 1: Health check (public endpoint)
    const healthRes = http.get(`${BASE_URL}/up`, params);
    check(healthRes, {
        'health check is OK': (r) => r.status === 200,
    });

    // Test 2: API health check
    const apiHealthRes = http.get(`${BASE_URL}/api/health`, params);
    check(apiHealthRes, {
        'api health check is OK': (r) => r.status === 200,
    });

    // Test 3: API endpoints with auth
    if (API_KEY) {
        const res = http.get(`${BASE_URL}/api/v1/dashboard`, params);
        check(res, {
            'authenticated dashboard access': (r) => r.status === 200,
        });
    }

    // Test 4: Search endpoint
    const searchRes = http.get(`${BASE_URL}/api/v1/search?q=test`, params);
    check(searchRes, {
        'search endpoint responds': (r) => r.status < 500,
    });

    // Test 5: Metrics endpoint
    const metricsRes = http.get(`${BASE_URL}/api/v1/metrics`, params);
    check(metricsRes, {
        'metrics endpoint responds': (r) => r.status < 500,
    });

    sleep(1);
}

// Smoke test function for CI
export function handleSummary(data) {
    return {
        'stdout': textSummary(data),
        'stderr': textSummary(data),
        'results.json': JSON.stringify(data),
    };
}

function textSummary(data) {
    const { checks } = data;
    const passRate = ((checks.passes ?? 0) / (checks.passes ?? 0 + checks.fails ?? 1)) * 100;
    return `Load test results: ${passRate.toFixed(2)}% checks passed\n`;
}