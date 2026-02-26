<template>
  <q-page class="flex flex-center">
    <q-card style="width: 360px" flat bordered>
      <q-card-section>
        <div class="text-h6">Login</div>
      </q-card-section>

      <q-card-section class="q-gutter-sm">
        <q-input v-model="form.email" label="Email" outlined dense />
        <q-input v-model="form.password" label="Password" type="password" outlined dense />
        <div v-if="error" class="text-negative text-caption">{{ error }}</div>
      </q-card-section>

      <q-card-actions class="q-px-md q-pb-mmd q-gutter-sm">
        <q-btn
          label="Login"
          color="primary"
          unelevated
          class="full-width"
          :loading="loading"
          @click="submit"
        />
        <q-btn flat label="Don't have an account? Register" to="/register" class="full-width" />
      </q-card-actions>
    </q-card>
  </q-page>
</template>

<script>
import { useAuthStore } from 'src/stores/auth'
export default {
  name: 'LoginPage',
  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      loading: false,
      error: null,
    }
  },

  methods: {
    async submit() {
      this.error = null
      this.loading = true
      try {
        const auth = useAuthStore()
        await auth.login(this.form.email, this.form.password)
        this.$router.push('/')
      } catch (e) {
        this.error = e.response?.data?.message || 'Login failed.'
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped></style>
