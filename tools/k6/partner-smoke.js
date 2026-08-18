import http from 'k6/http';
import { check, group } from 'k6';

const BASE_URL = (__ENV.BASE_URL || 'http://amaccmanagementsystem.test').replace(/\/+$/, '');
const PARTNER_USER = __ENV.PARTNER_USER || '';
const PARTNER_PASS = __ENV.PARTNER_PASS || '';

export const options = {
  vus: 1,
  iterations: 1,
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.95'],
  },
};

function extractCsrf(html) {
  const match = String(html).match(/name="csrf_token"\s+value="([^"]+)"/);
  return match ? match[1] : '';
}

function pageUrl(route) {
  return `${BASE_URL}/index.php?r=${encodeURIComponent(route)}`;
}

function isLoginPage(body) {
  const html = String(body);
  return html.includes('portal-login-card') || html.includes('name="csrf_token"');
}

function loginPartner(jar) {
  const loginGet = http.get(`${BASE_URL}/auth.php?portal=partner`, { jar, tags: { name: 'GET auth partner' } });
  const csrf = extractCsrf(loginGet.body);

  const loginPost = http.post(
    `${BASE_URL}/auth.php?portal=partner`,
    {
      csrf_token: csrf,
      email: PARTNER_USER,
      password: PARTNER_PASS,
    },
    {
      jar,
      redirects: 5,
      tags: { name: 'POST auth partner' },
    }
  );

  const loggedIn = check(loginPost, {
    'partner login succeeds': (res) => res.status >= 200 && res.status < 400 && !isLoginPage(res.body),
  });

  return loggedIn;
}

const partnerPages = [
  { route: 'partner', name: 'Dashboard', marker: 'partner-dash-v2' },
  { route: 'partner_portal', name: 'Portal', marker: 'pp-layout' },
  { route: 'partner_submissions', name: 'Submissions', marker: 'ps-v2' },
  { route: 'partner_timeline', name: 'Activity Timeline', marker: 'partner-timeline-page' },
  { route: 'partner_reports', name: 'Reports', marker: 'partner-reports-page' },
  { route: 'partner_evaluations', name: 'Evaluations', marker: 'partner-evaluations-page' },
  { route: 'partner_settings', name: 'Settings', marker: 'hte-settings' },
];

export default function partnerSmoke() {
  group('public partner login portal', () => {
    const res = http.get(`${BASE_URL}/auth.php?portal=partner`, {
      tags: { name: 'GET public partner login' },
    });

    check(res, {
      'partner login page returns 200': (r) => r.status === 200,
      'partner login page has CSRF field': (r) => extractCsrf(r.body) !== '',
      'partner login page shows HTE portal': (r) => String(r.body).includes('Host Training Establishment'),
    });
  });

  if (!PARTNER_USER || !PARTNER_PASS) {
    check(null, {
      'authenticated partner checks skipped (set PARTNER_USER and PARTNER_PASS)': () => true,
    });
    return;
  }

  const jar = http.cookieJar();

  group('partner authenticated pages', () => {
    if (!loginPartner(jar)) {
      return;
    }

    for (const page of partnerPages) {
      const res = http.get(pageUrl(page.route), {
        jar,
        redirects: 5,
        tags: { name: `GET ${page.route}` },
      });

      check(res, {
        [`${page.name} returns 200`]: (r) => r.status === 200,
        [`${page.name} is not login redirect`]: (r) => !isLoginPage(r.body),
        [`${page.name} contains expected marker`]: (r) => String(r.body).includes(page.marker),
      });
    }
  });
}
