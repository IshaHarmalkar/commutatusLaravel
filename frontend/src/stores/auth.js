import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    initialized: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user,
  },

  actions: {
    async register(name, email, password, password_confirmation) {
      await api.get('sanctum/csrf-cookie')
      await api.post('/register', { name, email, password, password_confirmation })
      await this.fetchUser()
    },

    async login(email, password) {
      await api.get('/sanctum/csrf-cookie')
      await api.post('/login', { email, password })
      await this.fetchUser()
    },

    async logout() {
      await api.post('/logout')
      this.user = null
      this.initialized = false
      localStorage.removeItem('user')
    },

    async fetchUser() {
      const res = await api.get('/api/user')
      this.user = res.data
      localStorage.setItem('user', JSON.stringify(res.data))
    },

    async init() {
      //refresh -> refech the user if cookie still valid
      if (this.initialized) return
      if (this.user) {
        try {
          await this.fetchUser()
        } catch {
          //session expired
          this.user = null
          localStorage.removeItem('user')
        }
      }
      this.initialized = true
    },
  },
})
