// In /var/www/archangel/payments/frontend/src/router/routes/auth.js
export default [
  {
    path: '/auth',
    component: () => import('../../layouts/AuthLayout.vue'),
    meta: { guestOnly: true },
    children: [
      // Add this empty path route for /auth
      {
        path: '',
        name: 'AuthHome', 
        redirect: '/auth/login'
      },
      {
        path: 'login',
        name: 'Login',
        component: () => import('../../views/auth/Login.vue')
      },
      {
        path: 'register',
        name: 'Register',
        component: () => import('../../views/auth/Register.vue')
      },
      {
        path: 'forgot-password',
        name: 'ForgotPassword',
        component: () => import('../../views/auth/ForgotPassword.vue')
      },
      {
        path: 'reset-password/:token',
        name: 'ResetPassword',
        component: () => import('../../views/auth/ResetPassword.vue')
      }
    ]
  }
]