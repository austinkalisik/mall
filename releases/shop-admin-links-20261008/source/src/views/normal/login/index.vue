<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
const router = useRouter()
const userStore = useUserStore()
const error = ref(false)
onMounted(async () => {
  try {
    const response = await fetch('/b2c-admin/session.php', { credentials: 'same-origin', cache: 'no-store' })
    if (response.status === 401) { window.location.replace('/login'); return }
    if (!response.ok) throw new Error('Session unavailable')
    const session = await response.json()
    if (session.code !== 200 || typeof session.data?.token !== 'string' || typeof session.data?.username !== 'string') throw new Error('Invalid session')
    userStore.userInfo.token = session.data.token
    userStore.userInfo.username = session.data.username
    userStore.userInfo.password = ''
    await userStore.getUserInfo()
    const destination = sessionStorage.getItem('nextgen-admin-destination') || '/home'
    sessionStorage.removeItem('nextgen-admin-destination')
    await router.replace(destination.startsWith('/') && !destination.startsWith('//') && !destination.startsWith('/login') ? destination : '/home')
  } catch {
    userStore.fedLogout()
    error.value = true
  }
})
</script>
<template>
  <main class="session-loading">
    <img :src="'/brand/Logo.png'" alt="NextGen" width="180">
    <h1>NextGen B2C</h1>
    <p v-if="!error" role="status">Opening your store dashboard…</p>
    <template v-else>
      <p role="alert">We could not open your dashboard. Please sign in again.</p>
      <a href="/login">Return to sign in</a>
    </template>
  </main>
</template>
<style scoped>
.session-loading { min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 16px; padding: 24px; }
a { color: #2563eb; }
</style>
