// In /var/www/archangel/payments/frontend/src/router/routes/public.js
export default [
  {
    path: '/',
    redirect: '/auth'  // Change this from '/auth/login' to '/auth'
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('../../views/Unauthorized.vue')
  }
]