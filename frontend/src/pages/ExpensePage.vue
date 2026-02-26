<template>
  <q-page padding>
    <div class="text-h6 q-mb-md">My Expenses</div>

    <q-list bordered separator class="rounded-borders">
      <q-item v-if="expenses.length === 0">
        <q-item-section class="text-grey">No expenses yet</q-item-section>
      </q-item>

      <q-item
        v-for="expense in expenses"
        :key="expense.id"
        clickable
        @click="$router.push(`/expenses/${expense.id}`)"
      >
        <q-item-section>
          <q-item-label>{{ expense.name }}</q-item-label>
          <q-item-label caption>
            Paid by {{ expense.paid_by.name }} . {{ formatDate(expense.created_at) }}
          </q-item-label>
        </q-item-section>

        <q-item-section side>
          <q-item-label class="text-weight-medium">
            {{ formatAmount(expense.amount) }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </q-page>
</template>

<script>
import { api } from 'src/boot/axios'
export default {
  name: 'ExpensePage',
  data() {
    return {
      expenses: [],
    }
  },

  async mounted() {
    await this.fetchExpenses()
  },

  methods: {
    async fetchExpenses() {
      const res = await api.get('/api/expenses')
      this.expenses = res.data.data
    },

    formatAmount(val) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(val ?? 0)
    },

    formatDate(val) {
      return new Date(val).toLocaleDateString()
    },
  },
}
</script>

<style lang="scss" scoped></style>
