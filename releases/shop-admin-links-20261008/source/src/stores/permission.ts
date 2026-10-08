import { defineStore } from 'pinia'
import { shallowRef } from 'vue'
import { asyncRouterMap, constantRouterMap } from '@/router/index'
import type { UmsMenu } from '@/types/menu'
import type { RouteRecordExt } from '@/types/router'

//Determine whether you have permission to access the menu
function hasPermission(menus: UmsMenu[], route: RouteRecordExt) {
  if (route.name) {
    const currMenu = getMenu(route.name as string, menus)
    if (currMenu != null) {
      //Set the menu's title, icon, and visibility
      if (currMenu.title != null && currMenu.title !== '' && !/[\u3400-\u9fff]/.test(currMenu.title)) {
        route.meta!.title = currMenu.title
      }
      if (currMenu.icon != null && currMenu.title !== '') {
        route.meta!.icon = currMenu.icon
      }
      if (currMenu.hidden != null) {
        route.hidden = currMenu.hidden !== 0
      }
      if (currMenu.sort != null) {
        route.sort = currMenu.sort
      }
      return true
    } else {
      route.sort = 0
      if (route.hidden !== undefined && route.hidden === true) {
        route.sort = -1
        return true
      } else {
        return false
      }
    }
  } else {
    return true
  }
}

// Get menu based on route name
function getMenu(name: string, menus: UmsMenu[]) {
  return menus.find(menu => name === menu.name) || null
}

// Sort menu
function sortRouters(accessedRouters: RouteRecordExt[]) {
  accessedRouters.forEach(router => {
    if (router.children && router.children.length > 0) {
      router.children.sort((a, b) => compare(a, b))
    }
  })
  accessedRouters.sort((a, b) => compare(a, b))
}

// Descending comparison function
function compare(a: RouteRecordExt, b: RouteRecordExt) {
  if (a.sort && b.sort) {
    return b.sort - a.sort
  } else {
    return 0
  }
}

export const usePermissionStore = defineStore('permission', () => {
  // All routes, static routes+dynamic routing
  const routers = shallowRef(constantRouterMap)
  // Dynamic routing with permission access
  const addRouters = shallowRef<RouteRecordExt[]>([])
  // Is it in test mode?
  const testMode = false

  // Generate an accessible routing table
  const generateRoutes = (data: { menus: UmsMenu[]; username: string }) => {
    const { menus, username } = data
    // Clone route records so one account cannot remove another account's links.
    const cloneRoute = (route: RouteRecordExt): RouteRecordExt => ({ ...route, meta: { ...route.meta },
      children: route.children?.map(child => cloneRoute(child as RouteRecordExt)) } as RouteRecordExt)
    const candidates = asyncRouterMap.map(cloneRoute)
    const accessedRouters = candidates.filter(v => {
      // In test modeadminAccount directly returns to all menus
      if (testMode && username === 'admin') return true
      if (hasPermission(menus, v)) {
        if (v.children && v.children.length > 0) {
          v.children = v.children.filter(child => {
            if (hasPermission(menus, child)) {
              return child
            }
            return false
          })
          return v
        } else {
          return v
        }
      }
      return false
    })
    //Sort menu
    sortRouters(accessedRouters)
    addRouters.value = accessedRouters
    routers.value = constantRouterMap.concat(accessedRouters)
  }

  return {
    routers,
    addRouters,
    generateRoutes,
  }
})

// Default export maintains compatibility
export default usePermissionStore
