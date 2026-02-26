<template>
  <q-page padding>
    <!-- Summary -->
    <div class="row q-gutter-md q-md-lg">
      <q-card flat bordered class="col">
        <q-card-section>
          <div class="text-caption text-grey">Total Balance</div>
          <div
            class="text-h5 text-weight-bold"
            :class="balance.total_balance >= 0 ? 'text-positive' : 'text-negative'"
          >
            {{ formateAmount(balance.total_balance) }}
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="col">
        <q-card-section>
          <div class="text-caption text-grey">Owed To You</div>
          <div class="text-h5 text-positive">{{ formateAmount(balance.total_owed_to_you) }}</div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="col">
        <q-card-section>
          <div class="text-caption text-grey">You Owe</div>
          <div class="text-h5 text-positive">{{ formateAmount(balance.total_you_owe) }}</div>
        </q-card-section>
      </q-card>
    </div>

    <!--  friends who owe me-->
    <div class="text-subtitile1 q-md-sm">Owed To You</div>
    <q-list bordered separator class="q-md-lg rounded-borders">
      <q-item v-if="balance.owed_to_you.length === 0">
        <q-item-section class="text-grey">Nobody owes you anything</q-item-section>
      </q-item>

      <q-item v-for="entry in balance.owed_to_you" :key="entry.user.id">
        <q-item-section>
          {{ entry.user.name }}
        </q-item-section>
        <q-item-section side class="text-positive text-weight-medium">
          +{{ formateAmount(entry.amount) }}
        </q-item-section>
      </q-item>
    </q-list>

    <!--  friends  I owe-->
    <div class="text-subtitile1 q-md-sm">You Owe</div>
    <q-list bordered separator class="q-md-lg rounded-borders">
      <q-item v-if="balance.you_owe.length === 0">
        <q-item-section class="text-grey">You don't owe anyone</q-item-section>
      </q-item>

      <q-item v-for="entry in balance.you_owe" :key="entry.user.id">
        <q-item-section>
          {{ entry.user.name }}
        </q-item-section>
        <q-item-section side class="text-negative text-weight-medium">
          -{{ formateAmount(entry.amount) }}
        </q-item-section>
      </q-item>
    </q-list>
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

    formateAmount(val) {
      return new Intl.NumberFormat('en-Us', {
        style: 'currency',
        currency: 'USD',
      }).format(val ?? 0)
    },
  },
}
</script>

<style lang="scss" scoped></style>
