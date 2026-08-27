/**
 * Live capacity test for ama-ojtportal.com (public GET flows only).
 *
 * Stages (STAGE=soft|medium|stress):
 *   soft   -> ramp to 25 VUs
 *   medium -> ramp to 100 VUs
 *   stress -> ramp to 200 VUs
 *
 * Pass: Soft/Medium failed<5% p95<3s checks>95%; Stress p95<5s.
 *
 *   $env:K6_WEB_DASHBOARD="true"
 *   k6 run -e STAGE=soft tools/k6/live-capacity.js
 *   .\tools\k6\run-live-capacity.ps1
 */
import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'https://ama-ojtportal.com').replace(/\/+$/, '');
const STAGE = String(__ENV.STAGE || 'soft').toLowerCase();

const BROWSER_HEADERS = {
  'User-Agent':
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
  Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
  'Accept-Language': 'en-US,en;q=0.9',
};

const pageErrorRate = new Rate('page_errors');

const stageProfiles = {
  soft: {
    stages: [
      { duration: '1m', target: 10 },
      { duration: '2m', target: 25 },
      { duration: '1m', target: 25 },
      { duration: '30s', target: 0 },
    ],
    p95: '3000',
  },
  medium: {
    stages: [
      { duration: '1m', target: 50 },
      { duration: '2m', target: 100 },
      { duration: '2m', target: 100 },
      { duration: '30s', target: 0 },
    ],
    p95: '3000',
  },
  stress: {
    stages: [
      { duration: '1m', target: 150 },
      { duration: '2m', target: 200 },
      { duration: '2m', target: 200 },
      { duration: '30s', target: 0 },
    ],
    p95: '5000',
  },
};

if (!stageProfiles[STAGE]) {
  throw new Error(`Unknown STAGE="${STAGE}". Use soft, medium, or stress.`);
}

const profile = stageProfiles[STAGE];

export const options = {
  stages: profile.stages,
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: [`p(95)<${profile.p95}`],
    checks: ['rate>0.95'],
    page_errors: ['rate<0.05'],
  },
  summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
  // Shared hosting is fragile under many parallel connections.
  noConnectionReuse: true,
  discardResponseBodies: false,
  tags: {
    stage: STAGE,
    target: 'ama-ojtportal',
  },
};

const publicPages = [
  {
    name: 'main_auth',
    url: `${BASE_URL}/auth.php`,
    markers: ['Choose Login Portal', 'login-page'],
  },
  {
    name: 'student_portal_login',
    url: `${BASE_URL}/auth.php?portal=student`,
    markers: ['csrf_token', 'portal-login'],
  },
  {
    name: 'partner_portal_login',
    url: `${BASE_URL}/auth.php?portal=partner`,
    markers: ['csrf_token', 'Host Training Establishment'],
  },
];

function markPage(res, page) {
  const body = String(res.body || '');
  const ok = res.status === 200 && page.markers.every((m) => body.includes(m));
  pageErrorRate.add(!ok);
  return ok;
}

export function setup() {
  // Retry briefly — previous stage may leave Hostinger recovering.
  let lastStatus = 0;
  for (let i = 0; i < 5; i++) {
    const probe = http.get(`${BASE_URL}/auth.php?portal=student`, {
      headers: BROWSER_HEADERS,
      tags: { name: 'setup_probe' },
      timeout: '20s',
    });
    lastStatus = probe.status;
    if (probe.status === 200 && String(probe.body || '').includes('csrf_token')) {
      return { baseUrl: BASE_URL, stage: STAGE };
    }
    sleep(3);
  }
  throw new Error(`Setup failed: ${BASE_URL}/auth.php?portal=student returned ${lastStatus}`);
}

export default function liveCapacity() {
  group(`public_browse_${STAGE}`, () => {
    for (const page of publicPages) {
      const res = http.get(page.url, {
        headers: BROWSER_HEADERS,
        tags: { name: page.name, stage: STAGE },
        timeout: '30s',
      });

      check(res, {
        [`${page.name} status 200`]: (r) => r.status === 200,
        [`${page.name} has expected markers`]: (r) =>
          page.markers.every((m) => String(r.body || '').includes(m)),
      });

      markPage(res, page);
      sleep(0.5 + Math.random() * 0.5);
    }
  });

  // Think time 2-4s between cycles (gentler on shared hosting)
  sleep(2 + Math.random() * 2);
}

export function handleSummary(data) {
  const failed = data.metrics.http_req_failed;
  const duration = data.metrics.http_req_duration;
  const checksMetric = data.metrics.checks;
  const p95 = duration && duration.values ? duration.values['p(95)'] : null;
  const failRate = failed && failed.values ? failed.values.rate : null;
  const checkRate = checksMetric && checksMetric.values ? checksMetric.values.rate : null;

  let breached = false;
  const failLines = [];
  for (const [name, metric] of Object.entries(data.metrics || {})) {
    if (metric.thresholds) {
      for (const [thresh, result] of Object.entries(metric.thresholds)) {
        if (result.ok === false) {
          breached = true;
          failLines.push(`THRESHOLD_FAIL ${name}: ${thresh}`);
        }
      }
    }
  }

  const lines = [
    `stage=${STAGE}`,
    `base_url=${BASE_URL}`,
    `http_req_failed_rate=${failRate !== null ? failRate.toFixed(4) : 'n/a'}`,
    `http_req_duration_p95_ms=${p95 !== null ? p95.toFixed(1) : 'n/a'}`,
    `checks_rate=${checkRate !== null ? checkRate.toFixed(4) : 'n/a'}`,
    ...failLines,
    `passed=${breached ? 'false' : 'true'}`,
  ];

  return {
    stdout: `\n=== CAPACITY SUMMARY (${STAGE}) ===\n${lines.join('\n')}\n`,
    'tools/k6/results/last-summary.txt': lines.join('\n') + '\n',
    [`tools/k6/results/${STAGE}-summary.json`]: JSON.stringify(
      {
        stage: STAGE,
        baseUrl: BASE_URL,
        passed: !breached,
        http_req_failed_rate: failRate,
        http_req_duration_p95_ms: p95,
        checks_rate: checkRate,
      },
      null,
      2
    ),
  };
}
