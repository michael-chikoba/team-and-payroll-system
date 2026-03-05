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
        <p>&copy; {{ new Date().getFullYear() }} Archangel Payroll. All rights reserved.</p>
        <div class="footer-badges">
          <span class="badge">🔒 SOC 2 Certified</span>
          <span class="badge">✓ GDPR Compliant</span>
          <span class="badge">⭐ 99.9% Uptime</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  background: #1a1a1a;
  color: white;
  padding: clamp(2rem, 5vw, 4rem) 0 clamp(1.5rem, 3vw, 2rem);
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.footer-section h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.footer-section h4 {
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.footer-section p {
  color: #9ca3af;
  line-height: 1.7;
  margin-bottom: 1rem;
  font-size: 0.95rem;
}

.footer-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-section ul li {
  margin-bottom: 0.75rem;
}

.footer-section a {
  color: #9ca3af;
  text-decoration: none;
  transition: color 0.3s;
  font-size: 0.95rem;
}

.footer-section a:hover {
  color: white;
}

.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.social-links a {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  text-decoration: none;
}

.social-links a:hover {
  background: #667eea;
  transform: translateY(-3px);
}

.footer-bottom {
  border-top: 1px solid #374151;
  padding-top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: space-between;
  align-items: center;
  color: #9ca3af;
  text-align: center;
}

@media (min-width: 768px) {
  .footer-bottom {
    flex-direction: row;
  }
}

.footer-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
}

.badge {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
}
</style>