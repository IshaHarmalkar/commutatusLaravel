<template>
  <q-page padding>
    <div class="text-h6" q-md-md>New Expense</div>
    <q-input v-model="form.name" label="Expense Name" outlined dense class="q-md-sm" />

    <q-select
      v-model="form.participant_ids"
      :options="userOptions"
      option-value="id"
      option-label="name"
      emit-value
      map-options
      multiple
      use-chips
      label="Participants"
      outlined
      dense
      use-input
      input-debounce="300"
      class="q-mb-sm"
      @filter="searchUsers"
    />

    <div class="text-subtitle2 q-mb-xs">Items</div>
    <div
      v-for="(item, index) in form.items"
      :key="index"
      class="row q-gutter-sm q-mb-sm items-center"
    >
      <q-input v-model="item.name" label="Item name" outlined dense class="col" />
      <q-input
        v-model.number="item.amount"
        label="Amount"
        type="number"
        outlined
        dense
        class="col-2"
      />

      <q-select
        v-model="item.type"
        :options="['equal', 'assigned']"
        label="Split"
        outlined
        dense
        class="col-2"
      />

      <q-select
        v-if="item.type === 'assigned'"
        v-model="item.assigned_to_id"
        :options="assignableUsers"
        option-value="id"
        option-label="name"
        emit-value
        map-options
        label="Assign to"
        outlined
        dense
        class="col-2"
      />

      <q-btn flat round dense icon="close" color="negative" @click="removeItem(index)" />
    </div>
    <q-btn flat label="+ Add Item" @click="addItem" class="q-mb-md" />

    <div class="row q-gutter-sm q-mb-md">
      <q-input v-model.number="form.tax" label="Tax" type="number" outlined dense class="col" />

      <q-input v-model.number="form.tip" label="Tip" type="number" outlined dense class="col" />
    </div>

    <div class="text-subtitle2 q-mb-md">
      Total: <strong>{{ formatAmount(computedTotal) }}</strong>
    </div>

    <div v-if="error" class="text-negative text-caption q-mb-sm">
      {{ error }}
    </div>

    <q-btn label="Create Expense" color="primary" unelevated :loading="loading" @click="submit" />
  </q-page>
</template>

<script>
import { api } from 'src/boot/axios'
import { useAuthStore } from 'src/stores/auth'
export default {
  name: 'AddExpensePage',

  data() {
    return {
      form: {
        name: '',
        participant_ids: [],
        items: [{ name: '', amount: null, type: 'equal', assigned_to_id: null }],
        tax: 0,
        tip: 0,
      },
      userOptions: [],
      loading: false,
      error: null,
    }
  },

  computed: {
    computedTotal() {
      const itemSum = this.form.items.reduce((sum, i) => sum + (i.amount || 0), 0)
      return itemSum + (this.form.tax || 0) + (this.form.tip || 0)
    },

    assignableUsers() {
      const auth = useAuthStore()
      const participants = this.userOptions.filter((u) => this.form.participant_ids.includes(u.id))

      const combined = [auth.user, ...participants]
      return Array.from(
        new Map(
          combined.map((u) => [
            u.id,
            { ...u, name: u.id === auth.user.id ? 'Me (' + u.name + ')' : u.name },
          ]),
        ).values(),
      )
    },
  },

  methods: {
    async loadInitialUsers() {
      try {
        const res = await api.get('/api/users/search')
        this.userOptions = res.data.data
      } catch (e) {
        console.error('Failed to load users', e)
      }
    },

    addItem() {
      this.form.items.push({ name: '', amount: null, type: 'equal', assigned_to_id: null })
    },

    removeItem(index) {
      this.form.items.splice(index, 1)
    },

    async searchUsers(val, update, abort) {
      if (val === '') {
        update(async () => {
          await this.loadInitialUsers()
        })
        return
      }

      if (val.length < 2) {
        abort()
        return
      }
      try {
        const auth = useAuthStore()
        const res = await api.get('/api/users/search', { params: { q: val } })
        /*   update(() => {
          this.userOptions = res.data.data.filter((u) => u.id !== auth.user.id)
        }) */

        update(() => {
          const searchedUsers = res.data.data.filter((u) => u.id !== auth.user.id)

          // Create a combined list: New search results + anyone already selected
          const currentlySelected = this.userOptions.filter((u) =>
            this.form.participant_ids.includes(u.id),
          )

          const allAvailable = [...currentlySelected, ...searchedUsers]

          this.userOptions = Array.from(new Map(allAvailable.map((u) => [u.id, u])).values())
        })
      } catch {
        abort()
      }
    },

    formatAmount(val) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'INR',
      }).format(val ?? 0)
    },

    async submit() {
      this.error = null
      this.loading = true
      try {
        await api.post('/api/expense', {
          name: this.form.name,
          amount: this.computedTotal,
          tax: this.form.tax || 0,
          tip: this.form.tip || 0,
          participant_ids: this.form.participant_ids,
          items: this.form.items,
        })
        this.$router.push('/')
      } catch (e) {
        this.error =
          e.response?.data?.message ||
          Object.values(e.response?.data?.errors || {})
            .flat()
            .join(' ') ||
          'Failed to create expense.'
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped></style>
