// In /var/www/archangel/payments/frontend/src/router/routes/auth.js
export default [
  // Top-level route for /auth (redirects to login, no layout)
  {
    path: '/auth',
    name: 'AuthHome',
    redirect: '/auth/login',
    meta: { guestOnly: true }
  },
  // Standalone auth routes without parent layout
  {
    path: '/auth/login',
    name: 'Login',
    component: () => import('../../views/auth/Login.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/auth/register',
    name: 'Register',
    component: () => import('../../views/auth/Register.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/auth/forgot-password',
    name: 'ForgotPassword',
    component: () => import('../../views/auth/ForgotPassword.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/auth/reset-password/:token',
    name: 'ResetPassword',
    component: () => import('../../views/auth/ResetPassword.vue'),
    meta: { guestOnly: true }
  }
]