<template>
  <q-page class="flex flex-center">
    <q-card style="width: 360px" flat bordered>
      <q-card-section>
        <div class="text-h6">Register</div>
      </q-card-section>

      <q-card-section class="q-gutter-sm">
        <q-input v-model="form.name" label="Name" outlined dense />
        <q-input v-model="form.email" label="Email" type="email" outlined dense />
        <q-input v-model="form.password" label="Password" type="password" outlined dense />
        <q-input
          v-model="form.password_confirmation"
          label="Confirm Password"
          type="password"
          outlined
          dense
        />

        <div v-if="error" class="text-negative text-caption">{{ error }}</div>
      </q-card-section>

      <q-card-actions class="q-px-md q-pb-md column q-gutter-sm">
        <q-btn
          label="Register"
          color="primary"
          unelevated
          class="full-width"
          :loading="loading"
          @click="submit"
        />

        <q-btn flat label="Already have an account? Login" to="/login" class="full-width" />
      </q-card-actions>
    </q-card>
  </q-page>
</template>

<script>
import { useAuthStore } from 'src/stores/auth'

export default {
  name: 'RegisterPage',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
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
        await auth.register(
          this.form.name,
          this.form.email,
          this.form.password,
          this.form.password_confirmation,
        )
        this.$router.push('/')
      } catch (e) {
        this.error = e.response?.data?.message || 'Registration failed.'
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped></style>
