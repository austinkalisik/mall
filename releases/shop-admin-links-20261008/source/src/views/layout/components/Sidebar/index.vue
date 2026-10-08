<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SidebarItem from './SidebarItem.vue'
import { useAppStore } from '@/stores/app'
import usePermissionStore from '@/stores/permission'
const appStore = useAppStore()
const permissionStore = usePermissionStore()
const route = useRoute()
const routes = computed(() => {
  const permissions = permissionStore.routers.find(item => item.path === '/ums')
  return permissionStore.routers.filter(item => item.path !== '/ums').map(item => item.path === '/settings' && permissions
    ? { ...item, children: [...(item.children || []), { ...permissions, alwaysShow: true }] } : item)
    .sort((a,b) => Number(a.path === '/settings') - Number(b.path === '/settings'))
})
const isCollapse = computed(() => !appStore.sidebar.opened && appStore.device !== 'mobile')
</script>
<template>
  <aside aria-label="Store administration navigation" id="admin-navigation">
    <router-link class="sidebar-brand" to="/home" aria-label="NextGen overview"><img :src="'/brand/Logo.png'" alt="NextGen"><span v-if="!isCollapse">STORE ADMINISTRATION</span><b v-else>NG</b></router-link>
    <div class="sidebar-scroll"><el-menu mode="vertical" :default-active="route.path" :collapse="isCollapse" :unique-opened="true" background-color="#172e38" text-color="#bfced3" active-text-color="#fff"><sidebar-item :routes="routes" /></el-menu></div>
    <a class="sidebar-store-link" href="/" target="_blank" rel="noopener" :aria-label="isCollapse ? 'Visit store' : undefined">{{ isCollapse ? '↗' : 'Visit your store ↗' }}</a>
  </aside>
</template>
