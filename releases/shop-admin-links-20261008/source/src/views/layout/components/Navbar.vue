<script setup lang="ts">
import { computed } from 'vue'
import Breadcrumb from '@/components/Breadcrumb/index.vue'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
const app = useAppStore()
const user = useUserStore()
const initials = computed(() => (user.userInfo.username || 'NG').slice(0,2).toUpperCase())
async function logout() { try { await user.userLogout() } finally { window.location.replace('/login') } }
</script>
<template>
  <header class="admin-navbar">
    <button class="navigation-toggle" @click="app.toggleSideBar" :aria-expanded="app.sidebar.opened" aria-controls="admin-navigation" aria-label="Toggle navigation"><span></span><span></span><span></span></button>
    <Breadcrumb />
    <div class="navbar-actions"><a class="navbar-store" href="/" target="_blank" rel="noopener">View store ↗</a><el-dropdown trigger="click"><button class="account-button"><span class="account-avatar">{{ initials }}</span><span class="account-label">{{ user.userInfo.username || 'Administrator' }}</span><span aria-hidden="true">⌄</span></button><template #dropdown><el-dropdown-menu><el-dropdown-item @click="logout">Sign out</el-dropdown-item></el-dropdown-menu></template></el-dropdown></div>
  </header>
</template>
<style scoped>
.admin-navbar{height:76px;padding:0 28px;display:flex;align-items:center;gap:16px;background:white;border-bottom:1px solid #e3eaee;position:sticky;top:0;z-index:100;background:#ffffffed;backdrop-filter:blur(12px)}.navigation-toggle{width:38px;height:38px;padding:10px;border:1px solid #e2e9ec;background:#fff;border-radius:9px;cursor:pointer;display:flex;flex-direction:column;justify-content:center;gap:4px;flex-shrink:0}.navigation-toggle span{height:2px;background:#344e58;width:100%;border-radius:2px}.navbar-actions{margin-left:auto;display:flex;align-items:center;gap:22px;min-width:0}.navbar-store{font-size:12px;font-weight:600;color:#35786c;white-space:nowrap}.account-button{display:flex;align-items:center;gap:10px;border:0;background:transparent;cursor:pointer;color:#425963;padding:4px;font-size:12px}.account-avatar{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#e5f1ed;color:#285e54;font-size:11px;font-weight:700}.account-label{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.navigation-toggle:focus-visible,.account-button:focus-visible{outline:3px solid #52a793;outline-offset:3px}@media(max-width:700px){.admin-navbar{padding:0 16px;height:64px;gap:10px}.account-label,.navbar-store{display:none}}
</style>
