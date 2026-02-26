const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requireAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('pages/DashboardPage.vue') },

      { path: 'expenses', name: 'expenses', component: () => import('pages/ExpensePage.vue') },

      { path: 'add', name: 'add', component: () => import('pages/AddExpensePage.vue') },
    ],
  },

  {
    path: '/',
    component: () => import('layouts/GuestLayout.vue'),
    children: [
      {
        path: 'login', // accessible at /login
        name: 'login',
        component: () => import('pages/LoginPage.vue'),
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('pages/RegisterPage.vue'),
      },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
