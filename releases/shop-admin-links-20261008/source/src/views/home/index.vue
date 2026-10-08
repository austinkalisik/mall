<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import OverviewAnalytics from './OverviewAnalytics.vue'
import { useUserStore } from '@/stores/user'
import { getProductListAPI } from '@/apis/product'
import { getOrderListAPI } from '@/apis/order'
import type { OmsOrder } from '@/types/order'
const analytics = ref<InstanceType<typeof OverviewAnalytics>>()
const user = useUserStore()
const loading = ref(false)
const updated = ref('')
const failure = ref(false)
const counts = ref<(number | null)[]>([null, null, null, null])
const orders = ref<OmsOrder[]>([])
const ordersAvailable = ref(false)
const cards = [
  { label: 'Total products', description: 'Your retail catalogue', route: '/pms/product', icon: 'product' },
  { label: 'Published products', description: 'Available in your store', route: '/pms/product', icon: 'product-list' },
  { label: 'Total orders', description: 'All recorded store orders', route: '/oms/order', icon: 'order' },
  { label: 'Awaiting dispatch', description: 'Paid orders ready to fulfil', route: '/oms/order', icon: 'order' },
]
const displayName = computed(() => user.userInfo.username?.split('@')[0] || 'administrator')
const statuses = ['Awaiting payment', 'Awaiting dispatch', 'Shipped', 'Completed', 'Closed', 'Invalid']
async function refresh() {
  if (loading.value) return
  analytics.value?.refresh()
  loading.value = true
  failure.value = false
  const results = await Promise.allSettled([
    getProductListAPI({ pageNum: 1, pageSize: 1 }),
    getProductListAPI({ pageNum: 1, pageSize: 1, publishStatus: 1 }),
    getOrderListAPI({ pageNum: 1, pageSize: 6 }),
    getOrderListAPI({ pageNum: 1, pageSize: 1, status: 1 }),
  ])
  counts.value = results.map(result => result.status === 'fulfilled' && Number.isFinite(result.value.data.total) ? result.value.data.total : null)
  const recent = results[2]
  ordersAvailable.value = recent?.status === 'fulfilled'
  orders.value = recent?.status === 'fulfilled' ? recent.value.data.list as OmsOrder[] : []
  failure.value = results.some(result => result.status === 'rejected')
  updated.value = new Intl.DateTimeFormat('en-PG', { timeZone: 'Pacific/Port_Moresby', hour: '2-digit', minute: '2-digit' }).format(new Date())
  loading.value = false
}
function date(value: string) { return value ? value.slice(0, 10) : '—' }
onMounted(refresh)
</script>
<template>
  <div class="overview-page" :aria-busy="loading">
    <header class="overview-heading">
      <div><p class="eyebrow">NEXTGEN / STORE OPERATIONS</p><h1>Your store at a glance.</h1><p>Welcome back, {{ displayName }}. Here is what is happening across your retail store.</p></div>
      <button class="refresh-button" @click="refresh" :disabled="loading">{{ loading ? 'Refreshing…' : 'Refresh data' }}</button>
    </header>
    <div class="data-status" role="status"><span class="status-dot"></span> Live store records <span v-if="updated">· Last checked {{ updated }} PNG time</span></div>
    <p v-if="failure" class="data-warning" role="alert">Some data could not be loaded. Check your connection and access permissions, then refresh.</p>
    <div class="metric-grid">
      <router-link v-for="(card, index) in cards" :key="card.label" :to="card.route" class="metric-card">
        <div class="metric-top"><span>{{ card.label }}</span><svg-icon :icon-class="card.icon" /></div>
        <strong>{{ loading ? '…' : counts[index] === null ? '—' : counts[index]?.toLocaleString('en-PG') }}</strong>
        <p>{{ counts[index] === null && !loading ? 'Data unavailable' : card.description }}</p>
      </router-link>
    </div>
    <div class="overview-grid">
      <section class="dashboard-panel">
        <div class="panel-heading"><div><h2>Latest orders</h2><p>The most recent orders returned by your store.</p></div><router-link to="/oms/order">View orders →</router-link></div>
        <div v-if="loading" class="empty-state" role="status">Loading your orders…</div>
        <div v-else-if="!ordersAvailable" class="empty-state">Order records are unavailable. Refresh to try again.</div>
        <div v-else-if="orders.length === 0" class="empty-state"><svg-icon icon-class="order" /><h3>Your next order starts here.</h3><p>Orders will appear when they are recorded in your store.</p><router-link to="/pms/product">Manage your catalogue →</router-link></div>
        <div v-else class="order-table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody><tr v-for="order in orders" :key="order.id"><td>{{ order.orderSn || order.id }}</td><td>{{ order.memberUsername || 'Customer' }}</td><td>{{ date(order.createTime) }}</td><td><span class="order-status">{{ statuses[order.status] || 'Unknown' }}</span></td><td><router-link :to="{ path: '/oms/orderDetail', query: { id: order.id } }">Open →</router-link></td></tr></tbody></table></div>
      </section>
      <aside class="dashboard-panel quick-actions"><p class="eyebrow">YOUR WORKSPACE</p><h2>Make your next move.</h2><router-link to="/pms/addProduct"><span>Add a product<small>Build your retail catalogue</small></span><span>↗</span></router-link><router-link to="/oms/order"><span>Manage orders<small>Review and fulfil customer orders</small></span><span>↗</span></router-link><router-link to="/settings/branding"><span>Store branding<small>Update your logo and contact details</small></span><span>↗</span></router-link><a href="/" target="_blank" rel="noopener"><span>Visit your store<small>See the customer experience</small></span><span>↗</span></a></aside>
    </div>
    <OverviewAnalytics ref="analytics" />
    <footer class="overview-footer">NextGen Technology · Papua New Guinea <span>Retail administration</span></footer>
  </div>
</template>
<style scoped>
.overview-page{max-width:1440px;margin:auto;padding:36px}.overview-heading{display:flex;justify-content:space-between;align-items:center;gap:24px;margin-bottom:20px}.eyebrow{font-size:11px;font-weight:700;letter-spacing:.15em;color:#557078;margin:0 0 14px}h1{font-size:clamp(26px,3vw,38px);letter-spacing:-.04em;color:#152b33;margin:0 0 12px;font-weight:700}.overview-heading p:not(.eyebrow),.panel-heading p{color:#667982;font-size:14px;line-height:1.6;margin:0}.refresh-button{background:#163c43;color:white;border:0;border-radius:10px;padding:13px 20px;cursor:pointer;white-space:nowrap;font-weight:600}.refresh-button:disabled{opacity:.6;cursor:wait}.data-status{display:flex;align-items:center;flex-wrap:wrap;gap:8px;font-size:12px;color:#667982;margin-bottom:26px}.status-dot{width:7px;height:7px;border-radius:50%;background:#2f8d72}.data-warning{background:#fff4db;color:#71521c;border-radius:10px;padding:14px;font-size:14px}.metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;margin-bottom:28px}.metric-card{display:block;border:1px solid #e2e9ed;background:white;border-radius:16px;padding:24px;transition:transform .2s,box-shadow .2s}.metric-card:hover{transform:translateY(-3px);box-shadow:0 10px 30px #152b330a}.metric-top{display:flex;justify-content:space-between;gap:12px;font-size:13px;color:#536c76;font-weight:600}.metric-top .svg-icon{color:#2e8178;width:20px;height:20px}.metric-card strong{display:block;font-size:38px;letter-spacing:-.04em;color:#19383e;margin:18px 0 10px}.metric-card p{font-size:12px;color:#798891;margin:0}.overview-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:24px}.dashboard-panel{background:#fff;border:1px solid #e2e9ed;border-radius:16px;overflow:hidden}.panel-heading{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:24px;border-bottom:1px solid #edf1f4}h2{font-size:18px;color:#19383e;letter-spacing:-.02em;margin:0 0 8px}.panel-heading a,.empty-state a,td a{color:#24786e;font-size:13px;font-weight:600;white-space:nowrap}.empty-state{text-align:center;padding:64px 24px;color:#798891;font-size:14px;line-height:1.7}.empty-state .svg-icon{width:36px;height:36px;color:#2f8174}.empty-state h3{color:#29434b;font-size:18px;margin-bottom:6px}.order-table-wrap{overflow:auto}table{border-collapse:collapse;width:100%;text-align:left;font-size:13px}th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#7b8b94;background:#f9fbfc}th,td{padding:17px 20px;border-bottom:1px solid #edf1f4;white-space:nowrap}td{color:#314953}.order-status{padding:6px 9px;border-radius:6px;background:#edf6f3;color:#326c60;font-size:11px}.quick-actions{padding:26px;background:#f0f6f4}.quick-actions a{display:flex;justify-content:space-between;padding:19px 0;border-bottom:1px solid #dce7e3;color:#294f4d;font-size:14px;font-weight:600}.quick-actions small{display:block;font-size:12px;font-weight:400;color:#70847e;margin-top:6px}.overview-footer{display:flex;justify-content:space-between;gap:12px;font-size:11px;color:#82929a;padding-top:30px}a:focus-visible,button:focus-visible{outline:3px solid #54afa0;outline-offset:4px}@media(max-width:1200px){.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.overview-grid{grid-template-columns:1fr}}@media(max-width:600px){.overview-page{padding:22px 16px}.overview-heading{align-items:flex-start;flex-direction:column}.metric-grid{gap:10px}.metric-card{padding:17px}.metric-card strong{font-size:30px}.overview-footer{flex-direction:column}.panel-heading{padding:18px}}
</style>
