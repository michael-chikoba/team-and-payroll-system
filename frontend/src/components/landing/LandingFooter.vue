<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h3>Archangel Payroll</h3>
          <p>Modern payroll management for modern businesses.</p>
          <div class="social-links">
            <a href="#" aria-label="Twitter">𝕏</a>
            <a href="#" aria-label="LinkedIn">in</a>
            <a href="#" aria-label="Facebook">f</a>
          </div>
        </div>
        <div class="footer-section">
          <h4>Product</h4>
          <ul>
            <li><router-link to="/features">Features</router-link></li>
            <li><router-link to="/solutions">Solutions</router-link></li>
            <li><router-link to="/pricing">Pricing</router-link></li>
            <li><router-link to="/contact">Request Demo</router-link></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Company</h4>
          <ul>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Press</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Legal</h4>
          <ul>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Terms of Service</a></li>
            <li><a href="#">Security</a></li>
            <li><a href="#">Compliance</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} Archangel Payroll. All rights reserved.</p>
        <div class="footer-badges">
          <span class="badge">🔒 SOC 2 Certified</span>
          <span class="badge">✓ GDPR Compliant</span>
          <span class="badge">⭐ 99.9% Uptime</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear()
</script>

<!-- =====================================================
     LIGHT THEME  —  Primary: #0F7ADB  |  Secondary: #F04E37
     Footer always dark-surface to anchor the page —
     surface color matched to the navbar's dark complement.
     ===================================================== -->
<style scoped>

/* ── Shell ─────────────────────────────────────────────────── */
.footer {
  /* Same deep navy used in landing-light.css footer section */
  background: #f2f4f6;
  color: #F0F4F8;
  padding: clamp(3rem, 6vw, 5rem) 0 clamp(1.5rem, 3vw, 2.5rem);
  position: relative;
  overflow: hidden;
}

/* Rainbow-gradient top rule — azure blue → coral — ties to both brand colors */
.footer::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(
    90deg,
    transparent 0%,
    #0F7ADB 30%,
    #F04E37 70%,
    transparent 100%
  );
}

/* ── Grid ──────────────────────────────────────────────────── */
.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr));
  gap: clamp(2rem, 4vw, 3rem);
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

/* ── Brand column ──────────────────────────────────────────── */
.footer-section h3 {
  font-size: 1.35rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  letter-spacing: -0.015em;

  /* Brand gradient — azure → light azure */
  background: linear-gradient(135deg, #0F7ADB 0%, #5CC8FF 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ── Column headings ───────────────────────────────────────── */
.footer-section h4 {
  font-size: 0.78rem;
  font-weight: 700;
  margin-bottom: 1.1rem;
  color: #6B7280;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

/* ── Body text ─────────────────────────────────────────────── */
.footer-section p {
  color: #6B7280;
  line-height: 1.7;
  margin-bottom: 1rem;
  font-size: 0.93rem;
}

/* ── Link lists ────────────────────────────────────────────── */
.footer-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-section ul li { margin-bottom: 0.6rem; }

.footer-section a,
.footer-section ul li a {
  color: #6B7280;
  text-decoration: none;
  font-size: 0.93rem;
  transition: color 0.2s;
}

.footer-section a:hover,
.footer-section ul li a:hover { color: #E5E7EB; }

/* ── Social icons ──────────────────────────────────────────── */
.social-links {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.social-links a {
  width: 36px;
  height: 36px;

  /* Rounded square, not circle — matches the navbar CTA's border-radius family */
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);

  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: #9CA3AF !important;
  font-size: 0.88rem;
  transition: all 0.22s;
}

.social-links a:hover {
  /* Fill with primary blue on hover */
  background: #0F7ADB;
  border-color: #0F7ADB;
  color: #fff !important;
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(15, 122, 219, 0.35);
}

/* ── Bottom bar ────────────────────────────────────────────── */
.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  padding-top: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: space-between;
  align-items: center;
  color: #4B5563;
  text-align: center;
  font-size: 0.88rem;
}

@media (min-width: 768px) { .footer-bottom { flex-direction: row; } }

/* ── Compliance badges ─────────────────────────────────────── */
.footer-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  justify-content: center;
}

.badge {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.35rem 0.9rem;
  border-radius: 9999px;
  font-size: 0.78rem;
  color: #6B7280;
  letter-spacing: 0.02em;
}
</style>