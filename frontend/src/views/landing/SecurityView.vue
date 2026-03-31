<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <section class="page-hero">
        <div class="container">
          <div class="hero-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            Enterprise-Grade Security
          </div>
          <h1 class="page-title">Your Payroll Data is<br class="br-hide" /> Protected at Every Layer</h1>
          <p class="page-subtitle">We've built Archangel Payroll from the ground up with security as the foundation — not an afterthought.</p>
        </div>
      </section>

      <!-- Certifications -->
      <section class="certs-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Certifications & Compliance</h2>
            <p class="section-sub">Our security posture is validated by independent third-party auditors and industry-recognized certifications.</p>
          </div>
          <div class="certs-grid">
            <div class="cert-card" v-for="cert in certifications" :key="cert.id">
              <div class="cert-icon" :class="`cert-icon--${cert.color}`">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
              </div>
              <div class="cert-name">{{ cert.name }}</div>
              <div class="cert-desc">{{ cert.desc }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Security Pillars -->
      <section class="pillars-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">How We Protect Your Data</h2>
          </div>
          <div class="pillars-grid">

            <div class="pillar-card" v-for="pillar in pillars" :key="pillar.id">
              <div class="pillar-header">
                <div class="pillar-icon" :class="`pillar-icon--${pillar.color}`">
                  <component :is="'svg'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24" v-html="pillar.iconPath" />
                </div>
                <h3 class="pillar-title">{{ pillar.title }}</h3>
              </div>
              <ul class="pillar-list">
                <li v-for="item in pillar.items" :key="item">{{ item }}</li>
              </ul>
            </div>

          </div>
        </div>
      </section>

      <!-- Incident Response -->
      <section class="incident-section">
        <div class="container">
          <div class="incident-layout">
            <div class="incident-text">
              <h2>Security Incident Response</h2>
              <p>If a security incident is detected that may affect your data, we commit to notifying affected customers within 72 hours of discovery — well ahead of most regulatory requirements.</p>
              <p>Our dedicated Security Operations Center monitors the platform 24/7/365 using automated threat detection, anomaly analysis, and human review.</p>
              <div class="incident-stats">
                <div class="i-stat">
                  <div class="i-stat-num">99.99%</div>
                  <div class="i-stat-label">Uptime SLA</div>
                </div>
                <div class="i-stat">
                  <div class="i-stat-num">&lt;72h</div>
                  <div class="i-stat-label">Breach Notification</div>
                </div>
                <div class="i-stat">
                  <div class="i-stat-num">24/7</div>
                  <div class="i-stat-label">SOC Monitoring</div>
                </div>
              </div>
            </div>
            <div class="incident-visual">
              <div class="soc-card">
                <div class="soc-header">
                  <div class="soc-dot soc-dot--green"></div>
                  <span>Security Operations Center</span>
                </div>
                <div class="soc-metric" v-for="m in socMetrics" :key="m.label">
                  <div class="soc-metric-label">{{ m.label }}</div>
                  <div class="soc-metric-bar">
                    <div class="soc-metric-fill" :style="{ width: m.value + '%', background: m.color }"></div>
                  </div>
                  <div class="soc-metric-value" :style="{ color: m.color }">{{ m.display }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Responsible Disclosure -->
      <section class="disclosure-section">
        <div class="container">
          <div class="disclosure-card">
            <div class="disclosure-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="30" height="30">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
            </div>
            <div class="disclosure-text">
              <h3>Responsible Disclosure Program</h3>
              <p>Found a security vulnerability? We encourage responsible disclosure. Report security issues to our security team and we'll work with you to address them promptly. Researchers who report valid vulnerabilities are eligible for our bug bounty program.</p>
            </div>
            <a href="mailto:security@archangelpayroll.com" class="disclosure-btn">Report a Vulnerability</a>
          </div>
        </div>
      </section>

    </main>
    <LandingFooter />
  </div>
</template>

<script setup>
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

const certifications = [
  { id: 1, name: 'SOC 2 Type II', desc: 'Annual third-party audit of security, availability, and confidentiality controls.', color: 'blue' },
  { id: 2, name: 'ISO 27001', desc: 'International standard for information security management systems.', color: 'green' },
  { id: 3, name: 'PCI DSS Level 1', desc: 'Highest level of compliance for payment card data security.', color: 'orange' },
  { id: 4, name: 'GDPR Compliant', desc: 'Full compliance with EU General Data Protection Regulation requirements.', color: 'blue' },
  { id: 5, name: 'CCPA Compliant', desc: 'California Consumer Privacy Act compliance for data rights.', color: 'green' },
  { id: 6, name: 'HIPAA Ready', desc: 'Capable of supporting HIPAA-compliant benefit administration workflows.', color: 'orange' },
]

const pillars = [
  {
    id: 1, title: 'Encryption', color: 'blue',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />',
    items: ['AES-256 encryption at rest', 'TLS 1.3 for all data in transit', 'End-to-end encrypted API communications', 'Encrypted database backups', 'Hardware security modules (HSM) for key management'],
  },
  {
    id: 2, title: 'Access Control', color: 'green',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z" />',
    items: ['Multi-factor authentication (MFA) required', 'Role-based access controls (RBAC)', 'Single sign-on (SSO) with SAML 2.0', 'Session timeout and auto-logout', 'IP allowlisting for admin access'],
  },
  {
    id: 3, title: 'Infrastructure', color: 'orange',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z" />',
    items: ['Hosted on AWS with multi-region redundancy', 'Web Application Firewall (WAF) protection', 'DDoS mitigation and traffic filtering', 'Automated vulnerability scanning', 'Immutable audit logs for all actions'],
  },
  {
    id: 4, title: 'Employee Security', color: 'blue',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
    items: ['Background checks for all employees', 'Security awareness training quarterly', 'Least-privilege access policies', 'Endpoint detection and response (EDR)', 'Strict vendor security assessments'],
  },
]

const socMetrics = [
  { label: 'Threat Detection', value: 99, display: '99%', color: '#2DB34A' },
  { label: 'Uptime', value: 99.99, display: '99.99%', color: '#0047AB' },
  { label: 'Incident Response', value: 95, display: '< 15 min', color: '#F5A623' },
]
</script>

<style scoped>
.container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 clamp(1rem, 4vw, 2rem); }

.page-hero {
  padding-top: calc(clamp(3rem, 8vw, 5rem) + 72px);
  padding-bottom: clamp(2rem, 5vw, 4rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 65% 55% at 15% 40%, rgba(74, 144, 217, 0.32) 0%, transparent 65%),
    linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white; text-align: center;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: rgba(245, 166, 35, 0.18); border: 1px solid rgba(245, 166, 35, 0.4);
  color: #F5A623; padding: 0.45rem 1rem; border-radius: 9999px;
  font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;
}
.page-title { font-size: clamp(2rem, 6vw, 3.2rem); font-weight: 800; margin-bottom: 1.25rem; line-height: 1.15; letter-spacing: -0.02em; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.2rem); opacity: 0.88; line-height: 1.7; font-weight: 300; max-width: 600px; margin: 0 auto; }
.br-hide { display: none; }
@media (min-width: 768px) { .br-hide { display: block; } }

.section-header { text-align: center; margin-bottom: 3rem; }
.section-title { font-size: clamp(1.75rem, 4vw, 2.4rem); font-weight: 800; color: #001a5e; letter-spacing: -0.02em; margin-bottom: 0.75rem; }
.section-sub { color: #4b5563; font-size: 1rem; line-height: 1.7; max-width: 560px; margin: 0 auto; }

/* Certs */
.certs-section { padding: clamp(3rem, 7vw, 5rem) 0; background: white; }
.certs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr)); gap: 1.25rem; }
.cert-card { background: #f8fafc; border: 1px solid #dde3ee; border-radius: 14px; padding: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; transition: all 0.25s; }
.cert-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,71,171,0.1); }
.cert-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.cert-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.cert-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }
.cert-icon--orange { background: rgba(245, 166, 35, 0.12); color: #d18b00; }
.cert-name { font-size: 1rem; font-weight: 800; color: #001a5e; }
.cert-desc { font-size: 0.88rem; color: #4b5563; line-height: 1.65; }

/* Pillars */
.pillars-section { padding: clamp(3rem, 7vw, 5rem) 0; background: #f0f4f8; }
.pillars-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 460px), 1fr)); gap: 1.5rem; }
.pillar-card { background: white; border-radius: 14px; padding: 1.75rem; border: 1px solid #dde3ee; }
.pillar-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
.pillar-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pillar-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.pillar-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }
.pillar-icon--orange { background: rgba(245, 166, 35, 0.12); color: #d18b00; }
.pillar-title { font-size: 1.1rem; font-weight: 800; color: #001a5e; }
.pillar-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.6rem; }
.pillar-list li { display: flex; gap: 0.5rem; font-size: 0.9rem; color: #374151; line-height: 1.5; align-items: flex-start; }
.pillar-list li::before { content: '✓'; color: #2DB34A; font-weight: 700; flex-shrink: 0; }

/* Incident */
.incident-section { padding: clamp(3rem, 7vw, 5rem) 0; background: white; }
.incident-layout { display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: center; }
@media (min-width: 992px) { .incident-layout { grid-template-columns: 1fr 1fr; } }
.incident-text h2 { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 800; color: #001a5e; margin-bottom: 1.25rem; letter-spacing: -0.02em; }
.incident-text p { color: #4b5563; line-height: 1.75; margin-bottom: 1rem; font-size: 0.95rem; }
.incident-stats { display: flex; gap: 2rem; margin-top: 2rem; flex-wrap: wrap; }
.i-stat-num { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 800; color: #0047AB; }
.i-stat-label { font-size: 0.82rem; color: #6b7280; font-weight: 500; margin-top: 0.2rem; }

.soc-card { background: #001a5e; border-radius: 16px; padding: 1.75rem; color: white; }
.soc-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.75rem; font-size: 0.88rem; font-weight: 600; opacity: 0.9; }
.soc-dot { width: 10px; height: 10px; border-radius: 50%; }
.soc-dot--green { background: #2DB34A; box-shadow: 0 0 8px rgba(45,179,74,0.6); }
.soc-metric { display: grid; grid-template-columns: 1fr 1fr auto; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.soc-metric:last-child { margin-bottom: 0; }
.soc-metric-label { font-size: 0.82rem; opacity: 0.8; }
.soc-metric-bar { background: rgba(255,255,255,0.1); border-radius: 9999px; height: 6px; overflow: hidden; }
.soc-metric-fill { height: 100%; border-radius: 9999px; transition: width 1s ease; }
.soc-metric-value { font-size: 0.82rem; font-weight: 700; text-align: right; white-space: nowrap; }

/* Disclosure */
.disclosure-section { padding: clamp(2.5rem, 5vw, 4rem) 0; background: #f0f4f8; }
.disclosure-card { background: white; border-radius: 16px; padding: clamp(1.5rem, 4vw, 2.5rem); border: 1px solid #dde3ee; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.disclosure-icon { width: 60px; height: 60px; background: rgba(245,166,35,0.12); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #d18b00; flex-shrink: 0; }
.disclosure-text { flex: 1; min-width: 220px; }
.disclosure-text h3 { font-size: 1.1rem; font-weight: 700; color: #001a5e; margin-bottom: 0.5rem; }
.disclosure-text p { font-size: 0.9rem; color: #4b5563; line-height: 1.65; }
.disclosure-btn { display: inline-flex; align-items: center; padding: 0.75rem 1.75rem; background: linear-gradient(135deg, #0047AB, #4A90D9); color: white; border-radius: 9999px; font-weight: 700; font-size: 0.9rem; text-decoration: none; white-space: nowrap; transition: opacity 0.2s; }
.disclosure-btn:hover { opacity: 0.88; }
</style>