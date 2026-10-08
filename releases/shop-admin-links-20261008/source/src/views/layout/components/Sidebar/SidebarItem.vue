<script lang="ts" setup>
import type { RouteRecordExt } from '@/types/router'
import { computed, type PropType } from 'vue'

// Define component name
defineOptions({
  name: 'SidebarItem'
})

// definitionprops
const props = defineProps({
  // Route to generate menu
  routes: {
    type: Array as PropType<RouteRecordExt[]>
  },
  // Control the style of a first-level menu with only one submenu
  isNest: {
    type: Boolean,
    default: false
  }
})

// Filter out the routes that need to be displayed
const filteredRoutes = computed(() => {
  return props.routes!.filter(item => !item.hidden && item.children)
})

// Filter out sub-routes that need to be displayed
const getFilteredChildren = (children: RouteRecordExt[]) => {
  return children.filter(child => !child.hidden)
}

function linkPath(parent: string, child: string) {
  if (child.startsWith('/') || /^https?:\/\//.test(child)) return child
  return `${parent.replace(/\/$/, '')}/${child.replace(/^\//, '')}`
}
const onlyChild = (children: RouteRecordExt[]) => getFilteredChildren(children)[0]!

// Determine whether there is only one sub-route under the route
const hasOneShowingChildren = (children: RouteRecordExt[]) => {
  const showingChildren = children.filter(item => {
    return !item.hidden
  })
  if (showingChildren.length === 1) {
    return true
  }
  return false
}
</script>

<template>
  <div class="menu-wrapper">
    <template v-for="item in filteredRoutes">
      <!-- A first-level menu with only one submenu -->
      <router-link
        v-if="item.children && hasOneShowingChildren(item.children) && !onlyChild(item.children).children && !item.alwaysShow"
        :to="linkPath(item.path, onlyChild(item.children).path)" :key="onlyChild(item.children).name">
        <el-menu-item :index="linkPath(item.path, onlyChild(item.children).path)"
          :class="{ 'submenu-title-noDropdown': !isNest }">
          <svg-icon v-if="onlyChild(item.children).meta && onlyChild(item.children).meta?.icon"
            :icon-class="onlyChild(item.children).meta?.icon">
          </svg-icon>
          <template #title>
            <span v-if="onlyChild(item.children).meta && onlyChild(item.children).meta?.title">{{ onlyChild(item.children).meta?.title
              }}</span>
          </template>
        </el-menu-item>
      </router-link>
      <!-- A first-level menu with multiple submenus -->
      <el-sub-menu v-else :index="item.name as string || item.path" :key="item.name">
        <!-- First level menu -->
        <template #title>
          <svg-icon v-if="item.meta && item.meta.icon" :icon-class="item.meta.icon"></svg-icon>
          <span v-if="item.meta && item.meta.title">{{ item.meta.title }}</span>
        </template>
        <!-- submenu -->
        <template v-for="child in getFilteredChildren(item.children!)">
          <sidebar-item :is-nest="true" class="nest-menu" v-if="child.children && child.children.length > 0"
            :routes="[child]" :key="child.path"></sidebar-item>
          <!-- Submenu with external link function -->
          <a v-else-if="child.path.startsWith('http')" v-bind:href="child.path" target="_blank" rel="noopener noreferrer" :key="child.name">
            <el-menu-item :index="linkPath(item.path, child.path)">
              <svg-icon v-if="child.meta && child.meta.icon" :icon-class="child.meta.icon"></svg-icon>
              <template #title>
                <span v-if="child.meta && child.meta.title">{{ child.meta.title }}</span>
              </template>
            </el-menu-item>
          </a>
          <!-- Normal submenu -->
          <router-link v-else :to="linkPath(item.path, child.path)" :key="'route-' + (child.name as string)">
            <el-menu-item :index="linkPath(item.path, child.path)">
              <svg-icon v-if="child.meta && child.meta.icon" :icon-class="child.meta.icon"></svg-icon>
              <template #title>
                <span v-if="child.meta && child.meta.title">{{ child.meta.title }}</span>
              </template>
            </el-menu-item>
          </router-link>
        </template>
      </el-sub-menu>

    </template>
  </div>
</template>
