<template>
  <div class="admin-shell">
    <aside class="sidebar" :class="{ collapsed: sidebarCollapsed }">

      <!-- HEADER -->
      <div class="sidebar-header">
        <!-- Expanded: logo + text + toggle -->
        <template v-if="!sidebarCollapsed">
          <div class="logo-mark">
            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="40" height="40" rx="6" fill="#6B0000"/>
              <text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle"
                font-family="serif" font-size="12" font-weight="900" fill="#FFFFFF"
                letter-spacing="0.5">GUESS</text>
            </svg>
          </div>
          <div class="logo-text-wrap" v-if="!sidebarCollapsed">
            <span class="logo-text">STORE IN/OUT</span>
            <span class="logo-sub">Attendance System</span>
          </div>
          <button class="collapse-btn" @click="toggleSidebar(true)" title="Collapse">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="6" x2="21" y2="6"/>
              <line x1="3" y1="12" x2="21" y2="12"/>
              <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
          </button>
        </template>

        <!-- Collapsed: just the toggle button -->
        <template v-else>
          <button class="collapse-btn centered" @click="toggleSidebar(false)" title="Expand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="6" x2="21" y2="6"/>
              <line x1="3" y1="12" x2="21" y2="12"/>
              <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
          </button>
        </template>
      </div>

      <!-- NAV -->
      <nav class="sidebar-nav">
        <div class="nav-tooltip-wrap">
          <Link :href="route('admin.dashboard')" class="nav-item" :class="{ active: isActive('admin.dashboard') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="3" width="7" height="7" rx="1"/>
              <rect x="3" y="14" width="7" height="7" rx="1"/>
              <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span v-if="!sidebarCollapsed">Dashboard</span>
          </Link>
          <span v-if="sidebarCollapsed" class="nav-tooltip">Dashboard</span>
        </div>
        <div class="nav-tooltip-wrap">
          <Link :href="route('admin.employees.index')" class="nav-item" :class="{ active: isActive('admin.employees') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="9" cy="7" r="4"/>
              <path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
              <path d="M16 3.13a4 4 0 010 7.75M21 21v-2a4 4 0 00-3-3.87"/>
            </svg>
            <span v-if="!sidebarCollapsed">Employees</span>
          </Link>
          <span v-if="sidebarCollapsed" class="nav-tooltip">Employees</span>
        </div>
        <div class="nav-tooltip-wrap">
          <Link :href="route('admin.logs.index')" class="nav-item" :class="{ active: isActive('admin.logs') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
              <rect x="9" y="3" width="6" height="4" rx="1"/>
              <path d="M9 12h6M9 16h4"/>
            </svg>
            <span v-if="!sidebarCollapsed">Attendance Logs</span>
          </Link>
          <span v-if="sidebarCollapsed" class="nav-tooltip">Attendance Logs</span>
        </div>
      </nav>

      <!-- FOOTER -->
      <div class="sidebar-footer">
        <!-- Expanded: full admin badge with logout -->
        <div v-if="!sidebarCollapsed" class="admin-badge">
          <div class="admin-avatar">{{ adminInitial }}</div>
          <div class="admin-info">
            <div class="admin-name">{{ $page.props.auth.user.name }}</div>
            <div class="admin-role">Administrator</div>
          </div>
          <Link :href="route('logout')" method="post" as="button" class="logout-btn" title="Logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
          </Link>
        </div>

        <!-- Collapsed: logout icon with hover tooltip showing admin info -->
        <div v-else class="collapsed-footer">
          <div class="logout-tooltip-wrap" @mouseenter="showLogoutTooltip = true" @mouseleave="showLogoutTooltip = false">
            <Link :href="route('logout')" method="post" as="button" class="logout-btn-solo" title="Logout">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
              </svg>
            </Link>
          </div>
          <Teleport to="body">
            <div class="logout-tooltip-fixed" v-if="showLogoutTooltip && sidebarCollapsed">
              <div class="tooltip-avatar">{{ adminInitial }}</div>
              <div class="tooltip-info">
                <div class="tooltip-name">{{ $page.props.auth.user.name }}</div>
                <div class="tooltip-role">{{ $page.props.auth.user.email }}</div>
              </div>
            </div>
          </Teleport>
        </div>
      </div>

    </aside>

    <div class="main-area">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">{{ title }}</h1>
          <p class="page-sub" v-if="subtitle">{{ subtitle }}</p>
        </div>
        <div class="topbar-right">
          <div class="live-time">{{ currentTime }}</div>
          <slot name="topbar-actions" />
        </div>
      </header>

      <div v-if="flash.success" class="flash flash-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        {{ flash.success }}
      </div>
      <div v-if="flash.error" class="flash flash-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ flash.error }}
      </div>

      <main class="content-area"><slot /></main>

      <footer class="admin-footer">
        <div class="admin-footer-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="footer-fp-icon"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
          <span>Powered by <a href="https://hfsecurity.cn/" target="_blank" class="footer-link">HFSecurity</a> Biometric Technology</span>
        </div>
        <div class="admin-footer-right">
          © 2026 · Made with <strong>Vue.js</strong> &amp; <strong>Laravel</strong> · <strong>Lance Tinoco</strong>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

defineProps({ title: String, subtitle: String })

const page = usePage()
const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
const showLogoutTooltip = ref(false)
const currentTime = ref('')
const flash = computed(() => page.props.flash || {})
const adminInitial = computed(() => (page.props.auth?.user?.name || 'A').charAt(0).toUpperCase())

function isActive(routeName) { return route().current(routeName + '*') }
function toggleSidebar(val) { sidebarCollapsed.value = val; localStorage.setItem('sidebar_collapsed', val) }

let timer
onMounted(() => {
  const tick = () => { currentTime.value = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }
  tick(); timer = setInterval(tick, 1000)
})
onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
* { box-sizing: border-box; }

.admin-shell { display: flex; min-height: 100vh; background: #F4F4F5; color: #1a1a1a; font-family: 'Sora', sans-serif; }

/* Sidebar */
.sidebar { width: 240px; background: #3E0703; border-right: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; transition: width 0.25s ease; position: sticky; top: 0; height: 100vh; overflow: hidden; }
.sidebar.collapsed { width: 64px; }

/* Header */
.sidebar-header { display: flex; align-items: center; gap: 10px; padding: 16px 14px; border-bottom: 1px solid rgba(255,255,255,0.12); min-height: 72px; }
.logo-mark svg { width: 36px; height: 36px; flex-shrink: 0; }
.logo-text-wrap { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.logo-text { font-size: 12px; font-weight: 800; color: #FFFFFF; letter-spacing: 1.5px; white-space: nowrap; text-transform: uppercase; }
.logo-sub  { font-size: 9px; color: rgba(255,255,255,0.55); letter-spacing: 0.5px; text-transform: uppercase; margin-top: 2px; }

.collapse-btn { background: none; border: none; color: rgba(255,255,255,0.55); cursor: pointer; padding: 4px; display: flex; transition: color 0.2s; flex-shrink: 0; margin-left: auto; }
.collapse-btn svg { width: 18px; height: 18px; }
.collapse-btn:hover { color: #FFFFFF; }

/* Collapsed header: toggle button takes full width centered */
.collapse-btn.centered { margin: 0 auto; padding: 6px; }

/* Nav */
.sidebar-nav { flex: 1; padding: 16px 8px; display: flex; flex-direction: column; gap: 4px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: rgba(255,255,255,0.75); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.18s; white-space: nowrap; overflow: hidden; }
.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.1); color: #FFFFFF; }
.nav-item.active { background: rgba(255,255,255,0.15); color: #FFFFFF; border-left: none; box-shadow: inset 3px 0 0 rgba(255,255,255,0.8); }
.nav-tooltip-wrap { position: relative; }
.nav-tooltip { position: absolute; left: 56px; top: 50%; transform: translateY(-50%); background: #1a1a1a; color: #ffffff; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 7px; white-space: nowrap; pointer-events: none; opacity: 0; transition: opacity 0.15s; z-index: 100; border: 1px solid rgba(255,255,255,0.1); }
.nav-tooltip-wrap:hover .nav-tooltip { opacity: 1; }
.sidebar.collapsed .nav-item { justify-content: center; padding: 10px 0; width: 44px; margin: 0 auto; border-radius: 10px; }
.sidebar.collapsed .nav-item.active { border-left: none; border-bottom: none; padding-left: 0; background: rgba(255,255,255,0.18); box-shadow: 0 0 0 1px rgba(255,255,255,0.25); }

/* Footer */
.sidebar-footer { padding: 12px 8px; border-top: 1px solid rgba(255,255,255,0.12); }
.admin-badge { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 8px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.12); }
.admin-avatar { width: 32px; height: 32px; border-radius: 50%; background: #8C1007; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; border: 1px solid #9E1A1A; }
.admin-info { flex: 1; min-width: 0; }
.admin-name { font-size: 12px; font-weight: 600; color: #FFFFFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-role { font-size: 10px; color: rgba(255,255,255,0.6); margin-top: 1px; }
.logout-btn { background: none; border: none; color: rgba(255,255,255,0.6); cursor: pointer; padding: 4px; display: flex; transition: color 0.2s; flex-shrink: 0; }
.logout-btn svg { width: 15px; height: 15px; }
.logout-btn:hover { color: #fca5a5; }

/* Collapsed footer: logout icon centered */
.logout-tooltip-wrap { position: relative; display: flex; justify-content: center; }
.logout-tooltip-fixed { position: fixed; bottom: 20px; left: 76px; background: #2d0a08; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; width: 220px; z-index: 9999; white-space: nowrap; box-shadow: 0 8px 24px rgba(0,0,0,0.35); animation: fadeIn 0.15s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateX(-4px); } to { opacity: 1; transform: translateX(0); } }
.tooltip-avatar { width: 38px; height: 38px; border-radius: 50%; background: #8C1007; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.15); }
.tooltip-name { font-size: 13px; font-weight: 700; color: #ffffff; }
.tooltip-role { font-size: 11px; color: rgba(255,255,255,0.55); margin-top: 3px; }
.tooltip-hint { font-size: 10px; color: #dc2626; margin-top: 4px; }
.collapsed-footer { display: flex; justify-content: center; }
.logout-btn-solo { background: none; border: none; color: rgba(255,255,255,0.55); cursor: pointer; padding: 8px; display: flex; border-radius: 8px; transition: all 0.2s; }
.logout-btn-solo svg { width: 18px; height: 18px; }
.logout-btn-solo:hover { color: #fca5a5; background: rgba(255,255,255,0.08); }

/* Main */
.main-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar { display: flex; align-items: center; justify-content: space-between; padding: 18px 32px; border-bottom: 1px solid rgba(255,255,255,0.15); background: #3E0703; position: sticky; top: 0; z-index: 10; }
.page-title { font-size: 18px; font-weight: 700; color: #FFFFFF; margin: 0; }
.page-sub { font-size: 12px; color: rgba(255,255,255,0.65); margin: 2px 0 0; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.live-time { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #FFFFFF; letter-spacing: 1px; background: rgba(255,255,255,0.18); padding: 5px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); font-weight: 700; }

.flash { display: flex; align-items: center; gap: 10px; padding: 12px 32px; font-size: 13px; font-weight: 500; }
.flash svg { width: 16px; height: 16px; flex-shrink: 0; }
.flash-success { background: rgba(72,187,120,0.08); color: #68D391; border-bottom: 1px solid rgba(72,187,120,0.2); }
.flash-error   { background: rgba(252,129,129,0.08); color: #FC8181; border-bottom: 1px solid rgba(252,129,129,0.2); }

.content-area { padding: 32px; flex: 1; background: #F4F4F5; }

.admin-footer { background: #F4F4F5; border-top: 1px solid #e8e8ea; padding: 10px 32px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.admin-footer-left { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #9ca3af; }
.admin-footer-right { font-size: 11px; color: #9ca3af; }
.admin-footer-right strong { color: #6b7280; font-weight: 600; }
.footer-fp-icon { width: 14px; height: 14px; color: #9ca3af; }
.footer-link { color: #8C1007; font-weight: 600; text-decoration: none; }
.footer-link:hover { text-decoration: underline; }
</style>