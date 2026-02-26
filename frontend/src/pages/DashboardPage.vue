<template>
  <q-page padding>
    <!-- Summary -->
    <div class="row q-gutter-md q-mb-lg">
      <q-card flat bordered class="col">
        <q-card-section>
          <div class="text-caption text-grey">Total Balance</div>
          <div
            class="text-h5 text-weight-bold"
            :class="balance.total_balance >= 0 ? 'text-positive' : 'text-negative'"
          >
            {{ formatAmount(balance.total_balance) }}
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="col">
        <q-card-section>
          <div class="text-caption text-grey">Owed To You</div>
          <div class="text-h5 text-positive">{{ formatAmount(balance.total_owed_to_you) }}</div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="col">
        <q-card-section>
          <div class="text-caption text-grey">You Owe</div>
          <div class="text-h5 text-negative">{{ formatAmount(balance.total_you_owe) }}</div>
        </q-card-section>
      </q-card>
    </div>

    <!--  friends who owe me-->
    <div class="text-subtitle1 q-mb-sm">Owed To You</div>
    <q-list bordered separator class="q-mb-lg rounded-borders">
      <q-item v-if="balance.owed_to_you.length === 0">
        <q-item-section class="text-grey">Nobody owes you anything</q-item-section>
      </q-item>

      <q-item v-for="entry in balance.owed_to_you" :key="entry.user.id">
        <q-item-section>
          {{ entry.user.name }}
        </q-item-section>
        <q-item-section side class="text-positive text-weight-medium">
          +{{ formatAmount(entry.amount) }}
        </q-item-section>
      </q-item>
    </q-list>

    <!--  friends  I owe-->
    <div class="text-subtitle1 q-mb-sm">You Owe</div>
    <q-list bordered separator class="q-mb-lg rounded-borders">
      <q-item v-if="balance.you_owe.length === 0">
        <q-item-section class="text-grey">You don't owe anyone</q-item-section>
      </q-item>

      <q-item v-for="entry in balance.you_owe" :key="entry.user.id">
        <q-item-section>
          {{ entry.user.name }}
        </q-item-section>
        <q-item-section side class="text-negative text-weight-medium">
          <span> -{{ formatAmount(entry.amount) }} </span>
          <q-btn
            unelevated
            dense
            size="sm"
            color="primary"
            label="Pay"
            @click="openSettle(entry)"
          />
        </q-item-section>
      </q-item>
    </q-list>

    <!-- Payment dialog box -->
    <q-dialog v-model="dialog.open">
      <q-card style="min-width: 320px">
        <q-card-section>
          <div class="text-h6">Pay {{ dialog.friend?.name }}</div>
          <div class="text-caption text-grey"></div>
          You owe {{ formatAmount(dialog.maxAmount) }}
        </q-card-section>

        <q-card-section class="q-gutter-sm">
          <q-input
            v-model.number="dialog.amount"
            label="Amount"
            type="number"
            outlined
            dense
            :hint="`Max: ${formatAmount(dialog.maxAmount)}`"
            :rules="[
              (val) => !!val || 'Amount is required',
              (val) => val > 0 || 'Amount must be greater than 0',
              (val) => val <= dialog.maxAmount || `Cannot exceed ${formatAmount(dialog.maxAmount)}`,
            ]"
          />

          <q-input v-model="dialog.notes" label="Notes(optional)" outlined dense autogrow />
          <div v-if="dialog.error" class="text-negative text-caption">
            {{ dialog.error }}
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup :disable="dialog.loading" />
          <q-btn
            unelevated
            label="Pay"
            color="primary"
            :loading="dialog.loading"
            :disable="!dialog.amount || dialog.amount <= 0 || dialog.amount > dialog.maxAmount"
            @click="submitPayment"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { api } from 'src/boot/axios'
export default {
  name: 'DashboardPage',
  data() {
    return {
      balance: {
        total_balance: 0,
        total_owed_to_you: 0,
        total_you_owe: 0,
        owed_to_you: [],
        you_owe: [],
      },
      dialog: {
        open: false,
        friend: null,
        amount: null,
        maxAmount: 0,
        notes: '',
        loading: false,
        error: null,
      },
    }
  },

  async mounted() {
    await this.fetchBalance()
  },

  methods: {
    async fetchBalance() {
      const res = await api.get('api/balance')
      this.balance = res.data.data
    },

    openSettle(entry) {
      this.dialog = {
        open: true,
        friend: entry.user,
        amount: entry.amount,
        maxAmount: entry.amount,
        notes: '',
        loading: false,
        error: null,
      }
    },

    async submitPayment() {
      this.dialog.error = null
      this.dialog.loading = true

      try {
        await api.post('/api/payments', {
          creditor_id: this.dialog.friend.id,
          amount: this.dialog.amount,
          notes: this.dialog.notes || null,
        })
        this.dialog.open = false
        await this.fetchBalance() //refresh dashboard
      } catch (e) {
        this.dialog.error = e.response?.data?.message || 'Payment failed.'
      } finally {
        this.dialog.loading = false
      }
    },

    formatAmount(val) {
      return new Intl.NumberFormat('en-Us', {
        style: 'currency',
        currency: 'INR',
      }).format(val ?? 0)
    },
  },
}
</script>

<style lang="scss" scoped></style>
