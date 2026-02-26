<template>
  <q-layout view="lHh Lpr lFf">
    <q-header bordered class="bg-white text-dark">
      <q-toolbar>
        <q-toolbar-title>Expense Sharing </q-toolbar-title>
        <q-btn flat label="Dashboard" to="/" />
        <q-btn flat label="Expenses" to="/expenses" />
        <q-btn flat label="Add" to="/add" />
        <q-btn flat label="Logout" @click="logout" />
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
import { useAuthStore } from 'src/stores/auth'

export default defineComponent({
  name: 'MainLayout',

  methods: {
    async logout() {
      const auth = useAuthStore()
      await auth.logout()
      this.$router.push('/login')
    },
  },
})
</script>
