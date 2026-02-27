<template>
  <q-layout view="lHh Lpr lFf">
    <q-header bordered class="bg-white text-dark">
      <q-toolbar>
        <q-toolbar-title>Expense Sharing </q-toolbar-title>
        <q-btn flat label="Dashboard" to="/" />
        <q-btn flat label="Expenses" to="/expenses" />
        <q-btn flat label="Add" to="/add" />

        <q-btn flat round class="q-ml-sm" />
        <q-avatar color="primary" text-color="white" size="32px">
          {{ initials }}
        </q-avatar>

        <q-menu anchor="bottom right" self="top right">
          <div class="q-pa-md" style="min-width: 180px">
            <div class="text-subtitle2">{{ user.name }}</div>
            <div class="text-caption text-grey q-mb-md">{{ user.email }}</div>
            <q-separator class="q-mb-sm" />

            <q-item clickable v-close-popup @click="logout" class="text-negative rounded-borders">
              <q-item-section avatar>
                <q-icon name="logout" size="18px" />
              </q-item-section>
              <q-item-section>Logout</q-item-section>
            </q-item>
          </div>
        </q-menu>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
import { useAuthStore } from 'src/stores/auth'

export default {
  name: 'MainLayout',

  computed: {
    user() {
      const auth = useAuthStore()
      return auth.user || {}
    },

    initials() {
      if (!this.user.name) return '?'
      return this.user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },
  },

  methods: {
    async logout() {
      const auth = useAuthStore()
      await auth.logout()
      this.$router.push('/login')
    },
  },
}
</script>
