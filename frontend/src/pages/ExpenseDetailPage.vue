<template>
  <q-page padding>
    <!-- Loading -->
    <div v-if="loading" class="flex flex-center q-mt-xl">
      <q-spinner size="40px" />
    </div>

    <template v-else-if="expense">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <q-btn flat round dense icon="arrow_back" class="q-mr-sm" @click="$router.back()" />
        <div>
          <div class="text-h6">{{ expense.name }}</div>
          <div class="text-caption text-grey">
            Paid by {{ expense.paid_by.name }} · {{ formatDate(expense.created_at) }}
          </div>
        </div>
      </div>

      <!-- Your Summary -->
      <q-card flat bordered class="q-mb-lg">
        <q-card-section>
          <div class="text-subtitle2 q-mb-sm">Your Summary</div>
          <div class="row q-gutter-md">
            <div class="col">
              <div class="text-caption text-grey">You Owe</div>
              <div class="text-h6 text-negative">
                {{ formatAmount(summary.you_owe) }}
              </div>
            </div>

            <div class="col">
              <div class="text-caption text-grey">Owed To You</div>
              <div class="text-h6 text-positive">
                {{ formatAmount(summary.owed_to_you) }}
              </div>
            </div>

            <div class="col">
              <div class="text-caption text-grey">Net</div>
              <div
                class="text-h6 text-weight-bold"
                :class="summary.net >= 0 ? 'text-positive' : 'text-negative'"
              >
                {{ formatAmount(summary.net) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Participants -->
      <div class="text-subtitle2 q-mb-sm">Participants</div>
      <div class="row q-gutter-sm q-mb-lg">
        <q-chip v-for="p in expense.participants" :key="p.id" outline color="primary" icon="person">
          {{ p.name }}
        </q-chip>
      </div>

      <!-- Items -->
      <div class="text-subtitle2 q-mb-sm">Items</div>
      <q-list bordered separator class="rounded-borders q-mb-lg">
        <q-expansion-item
          v-for="item in expenseItems"
          :key="item.id"
          :label="item.name"
          :caption="
            item.type === 'assigned' ? `Assigned to ${item.assigned_to.name}` : 'Split equally'
          "
        >
          <template #header>
            <q-item-section>
              <q-item-label>{{ item.name }}</q-item-label>
              <q-item-label caption>
                {{
                  item.type === 'assigned'
                    ? `Assigned to ${item.assigned_to.name}`
                    : 'Split equally'
                }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-medium">
                {{ formatAmount(item.amount) }}
              </q-item-label>
            </q-item-section>
          </template>

          <!-- Splits for this item -->
          <q-list dense class="bg-grey-1">
            <q-item v-for="split in item.splits" :key="split.id">
              <q-item-section>
                <q-item-label class="text-caption">
                  {{ split.debtor.name }} owes {{ split.creditor.name }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label class="text-caption text-negative">
                  {{ formatAmount(split.amount) }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="!item.splits.length">
              <q-item-section>
                <q-item-label class="text-caption text-grey">
                  No splits — assigned to payer
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-expansion-item>
      </q-list>

      <!-- Totals breakdown -->
      <q-card flat bordered>
        <q-card-section>
          <div class="row justify-between q-mb-xs">
            <span class="text-grey">Subtotal</span>
            <span>{{ formatAmount(subtotal) }}</span>
          </div>
          <div class="row justify-between q-mb-xs">
            <span class="text-grey">Tax</span>
            <span>{{ formatAmount(expense.tax) }}</span>
          </div>
          <div class="row justify-between q-mb-xs">
            <span class="text-grey">Tip</span>
            <span>{{ formatAmount(expense.tip) }}</span>
          </div>
          <q-separator class="q-my-sm" />
          <div class="row justify-between">
            <span class="text-weight-bold">Total</span>
            <span class="text-weight-bold">{{ formatAmount(expense.amount) }}</span>
          </div>
        </q-card-section>
      </q-card>
    </template>

    <!-- Error -->
    <div v-else class="text-center text-grey q-mt-xl">
      Expense not found or you do not have access.
    </div>
  </q-page>
</template>

<script>
import { api } from 'src/boot/axios'

export default {
  name: 'ExpenseDetailPage',

  data() {
    return {
      expense: null,
      summary: {
        you_owe: 0,
        owed_to_you: 0,
        net: 0,
      },
      loading: true,
    }
  },

  computed: {
    // All items except the virtual Tax & Tip item
    expenseItems() {
      if (!this.expense) return []
      return this.expense.items.filter((i) => i.name !== 'Tax & Tip')
    },

    subtotal() {
      if (!this.expense) return 0
      return this.expenseItems.reduce((sum, i) => sum + Number(i.amount), 0)
    },
  },

  async mounted() {
    await this.fetchExpense()
  },

  methods: {
    async fetchExpense() {
      this.loading = true
      try {
        const res = await api.get(`/api/expenses/${this.$route.params.id}`)
        this.expense = res.data.data.expense
        this.summary = res.data.data.your_summary
      } catch {
        this.expense = null
      } finally {
        this.loading = false
      }
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
