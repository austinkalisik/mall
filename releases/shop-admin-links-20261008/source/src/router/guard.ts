import router from '@/router/index'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import { useUserStore } from '@/stores/user'
import usePermissionStore from '@/stores/permission'

// Whitelist path without login
const whiteList = ['/login']
// Configure the route pre-guard function (executed every time the route jumps)
router.beforeEach((to, from, next) => {
  NProgress.start()
  const userStore = useUserStore()
  const permissionStore = usePermissionStore()
  if (userStore.userInfo.token) {
    if (to.path === '/login') {
      // Access while logged inloginJump directly to the homepage
      next({ path: '/' })
      NProgress.done()
    } else {
      if (permissionStore.addRouters.length === 0) {
        // When there is no dynamic routing in the login state, according tomenusGenerate dynamic routes
        permissionStore.generateRoutes({
          menus: userStore.userInfo.menus,
          username: userStore.userInfo.username,
        })
        permissionStore.addRouters.forEach(route => {
          router.addRoute(route)
        })
        next({ ...to, replace: true })
      } else {
        next()
      }
    }
  } else {
    if (whiteList.indexOf(to.path) !== -1) {
      // Whitelist paths are allowed when not logged in
      next()
    } else {
      // Non-whitelist path jumps to the login page when not logged in
      sessionStorage.setItem('nextgen-admin-destination', to.fullPath)
      next('/login')
      NProgress.done()
    }
  }
})

// Configure route post function guard function
router.afterEach(() => {
  NProgress.done()
})
