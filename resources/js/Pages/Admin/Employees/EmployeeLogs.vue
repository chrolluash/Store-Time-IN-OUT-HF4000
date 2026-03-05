<template>
  <AdminLayout :title="employee.full_name" :subtitle="`Attendance History · ${employee.store_dept}`">

    <!-- Employee Card -->
    <div class="emp-header-card">
      <div class="emp-avatar-lg">{{ employee.full_name.charAt(0) }}</div>
      <div class="emp-header-info">
        <div class="emp-header-name">{{ employee.full_name }}</div>
        <div class="emp-header-meta">
          <span class="meta-pill position">{{ employee.position }}</span>
          <span class="meta-pill branch">{{ employee.store_dept }}</span>
          <span class="meta-pill shift">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ employee.shift_from }} – {{ employee.shift_to }}
          </span>
          <span class="meta-pill" :class="'enroll-' + employee.enrollment_status">
            <span class="dot"></span>{{ enrollLabel(employee.enrollment_status) }}
          </span>
        </div>
      </div>
      <div class="emp-summary-stats">
        <div class="stat-item">
          <div class="stat-num">{{ summary.total }}</div>
          <div class="stat-label">Total Days</div>
        </div>
        <div class="stat-div"></div>
        <div class="stat-item">
          <div class="stat-num green">{{ summary.present }}</div>
          <div class="stat-label">Present</div>
        </div>
        <div class="stat-div"></div>
        <div class="stat-item">
          <div class="stat-num yellow">{{ summary.late }}</div>
          <div class="stat-label">Late</div>
        </div>
        <div class="stat-div"></div>
        <div class="stat-item">
          <div class="stat-num red">{{ summary.absent }}</div>
          <div class="stat-label">Absent</div>
        </div>
      </div>
    </div>

    <!-- Filter + Action Bar -->
    <div class="filter-bar">
      <!-- From date -->
      <div class="date-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <input type="date" v-model="fromFilter" @change="applyFilters" class="date-input" title="From" />
      </div>
      <span class="date-sep">→</span>
      <!-- To date -->
      <div class="date-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <input type="date" v-model="toFilter" @change="applyFilters" class="date-input" title="To" />
      </div>
      <!-- Status -->
      <div class="filter-group">
        <select v-model="statusFilter" @change="applyFilters">
          <option value="">All Statuses</option>
          <option value="present">Present</option>
          <option value="late">Late</option>
          <option value="absent">Absent</option>
        </select>
      </div>
      <button v-if="hasFilters" class="btn-clear-filter" @click="clearFilters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear
      </button>
      <div class="bar-spacer"></div>
      <Link :href="route('admin.employees.index')" class="btn-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Employees
      </Link>
      <a :href="exportUrl" class="btn-export">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
      </a>
    </div>

    <!-- Logs Table -->
    <div class="table-wrap">
      <table class="logs-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Status</th>
            <th>Time In</th>
            <th>Break 1</th>
            <th>Break 2</th>
            <th>Break 3</th>
            <th>Time Out</th>
            <th>Hours</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="logs.data.length === 0">
            <td colspan="8" class="empty-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
              No logs found for this range.
            </td>
          </tr>
          <tr v-for="log in logs.data" :key="log.id" class="log-row">
            <td><div class="date-label">{{ log.date_label }}</div></td>
            <td><span class="status-pill" :class="log.status">{{ log.status }}</span></td>
            <td><TimeStamp :value="log.time_in" color="green" /></td>
            <td>
              <div class="break-pair">
                <TimeStamp :value="log.break1_out" label="OUT" color="yellow" />
                <TimeStamp :value="log.break1_in"  label="IN"  color="green" />
              </div>
            </td>
            <td>
              <div class="break-pair">
                <TimeStamp :value="log.break2_out" label="OUT" color="yellow" />
                <TimeStamp :value="log.break2_in"  label="IN"  color="green" />
              </div>
            </td>
            <td>
              <div class="break-pair">
                <TimeStamp :value="log.break3_out" label="OUT" color="yellow" />
                <TimeStamp :value="log.break3_in"  label="IN"  color="green" />
              </div>
            </td>
            <td><TimeStamp :value="log.time_out" color="red" /></td>
            <td>
              <span v-if="log.worked_hours" class="hours-badge">{{ log.worked_hours }}</span>
              <span v-else class="muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" v-if="logs.last_page > 1">
      <button v-for="link in logs.links" :key="link.label"
        :disabled="!link.url" :class="{ active: link.active }"
        @click="link.url && router.get(link.url)"
        v-html="link.label" />
    </div>

  </AdminLayout>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  employee: Object,
  logs:     Object,
  summary:  Object,
  filters:  Object,
})

const fromFilter   = ref(props.filters?.from   || '')
const toFilter     = ref(props.filters?.to     || '')
const statusFilter = ref(props.filters?.status || '')

const hasFilters = computed(() => fromFilter.value || toFilter.value || statusFilter.value)

const exportUrl = computed(() => {
  const params = new URLSearchParams()
  if (fromFilter.value)   params.set('from',   fromFilter.value)
  if (toFilter.value)     params.set('to',     toFilter.value)
  if (statusFilter.value) params.set('status', statusFilter.value)
  const qs = params.toString()
  return route('admin.employees.logs.export', props.employee.id) + (qs ? '?' + qs : '')
})

function applyFilters() {
  router.get(route('admin.employees.logs', props.employee.id), {
    from:   fromFilter.value   || undefined,
    to:     toFilter.value     || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

function clearFilters() {
  fromFilter.value = ''; toFilter.value = ''; statusFilter.value = ''
  applyFilters()
}

function enrollLabel(s) {
  return { complete: 'Enrolled', partial: 'Partial', none: 'Not Enrolled' }[s] || s
}

const TimeStamp = defineComponent({
  props: { value: String, color: String, label: String },
  setup(p) {
    return () => p.value
      ? h('span', { class: `ts ts-${p.color}` }, [(p.label ? p.label + ' ' : '') + p.value])
      : h('span', { class: 'muted' }, '—')
  }
})
</script>

<style scoped>
/* ── Employee header card ── */
.emp-header-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px 24px; display: flex; align-items: center; gap: 18px; margin-bottom: 20px; flex-wrap: wrap; }
.emp-avatar-lg { width: 54px; height: 54px; border-radius: 50%; background: #8C1007; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; flex-shrink: 0; border: 2px solid rgba(140,16,7,0.2); }
.emp-header-info { flex: 1; min-width: 200px; }
.emp-header-name { font-size: 18px; font-weight: 700; color: #1a1a1a; margin-bottom: 8px; }
.emp-header-meta { display: flex; flex-wrap: wrap; gap: 6px; }
.meta-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
.meta-pill.position { background: #fde8e9; color: #6D2932; }
.meta-pill.branch   { background: #f1f5f9; color: #475569; }
.meta-pill.shift    { background: #f1f5f9; color: #475569; font-family: 'JetBrains Mono', monospace; }
.meta-pill.shift svg { width: 11px; height: 11px; }
.meta-pill.enroll-complete { background: #dcfce7; color: #16a34a; }
.meta-pill.enroll-partial  { background: #fef9c3; color: #ca8a04; }
.meta-pill.enroll-none     { background: #fee2e2; color: #dc2626; }
.meta-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* ── Summary stats ── */
.emp-summary-stats { display: flex; align-items: center; gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 20px; margin-left: auto; }
.stat-item { text-align: center; }
.stat-num { font-size: 22px; font-weight: 800; color: #1a1a1a; line-height: 1; }
.stat-num.green  { color: #16a34a; }
.stat-num.yellow { color: #ca8a04; }
.stat-num.red    { color: #dc2626; }
.stat-label { font-size: 10px; color: #64748b; margin-top: 3px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-div { width: 1px; height: 28px; background: #e2e8f0; }

/* ── Filter + action bar ── */
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.bar-spacer { flex: 1; min-width: 16px; }
.date-wrap { position: relative; display: flex; align-items: center; }
.date-wrap svg { position: absolute; left: 10px; width: 14px; height: 14px; color: #8C1007; pointer-events: none; z-index: 1; }
.date-input { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px 8px 32px; color: #1a1a1a; font-size: 13px; font-family: 'JetBrains Mono', monospace; cursor: pointer; transition: border-color 0.18s; }
.date-input:focus { outline: none; border-color: #8C1007; }
.date-sep { font-size: 14px; color: #9ca3af; font-weight: 600; }
.filter-group select { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 32px 8px 12px; color: #1a1a1a; font-size: 13px; font-family: inherit; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; transition: border-color 0.18s; }
.filter-group select:focus { outline: none; border-color: #8C1007; }
.btn-clear-filter { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #fff5f5; border: 1px solid #fca5a5; border-radius: 8px; color: #dc2626; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.18s; }
.btn-clear-filter svg { width: 12px; height: 12px; }
.btn-clear-filter:hover { background: #fee2e2; }
.btn-back { display: inline-flex; align-items: center; gap: 6px; color: #64748b; font-size: 13px; font-weight: 500; text-decoration: none; padding: 7px 14px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; transition: all 0.18s; font-family: inherit; }
.btn-back svg { width: 14px; height: 14px; }
.btn-back:hover { border-color: #8C1007; color: #8C1007; }
.btn-export { display: inline-flex; align-items: center; gap: 6px; background: #8C1007; color: #fff; font-size: 13px; font-weight: 600; padding: 7px 14px; border-radius: 8px; border: 1px solid #6D2932; text-decoration: none; transition: background 0.18s; font-family: inherit; }
.btn-export svg { width: 14px; height: 14px; }
.btn-export:hover { background: #6D2932; }

/* ── Table ── */
.table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; overflow-x: auto; }
.logs-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.logs-table th { padding: 10px 16px; text-align: left; font-size: 10px; font-weight: 700; color: #561C24; text-transform: uppercase; letter-spacing: 0.7px; background: #f9f0f1; border-bottom: 1px solid #e8d0d2; white-space: nowrap; }
.logs-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.log-row:last-child td { border-bottom: none; }
.log-row:hover td { background: #fdf5f5; }
.empty-row { text-align: center; color: #9ca3af; padding: 48px 0 !important; }
.empty-row svg { width: 32px; height: 32px; display: block; margin: 0 auto 8px; color: #d1d5db; }
.date-label { font-size: 12px; font-weight: 600; color: #1a1a1a; white-space: nowrap; }
.break-pair { display: flex; flex-direction: column; gap: 3px; }
.status-pill { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
.status-pill.present { background: #dcfce7; color: #16a34a; }
.status-pill.late    { background: #fef9c3; color: #ca8a04; }
.status-pill.absent  { background: #fee2e2; color: #dc2626; }
:deep(.ts) { display: inline-block; padding: 2px 7px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
:deep(.ts-green)  { background: #dcfce7; color: #16a34a; }
:deep(.ts-yellow) { background: #fef9c3; color: #ca8a04; }
:deep(.ts-red)    { background: #fee2e2; color: #dc2626; }
.hours-badge { font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 600; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 4px; }
.muted { color: #9ca3af; font-size: 12px; }

/* ── Pagination ── */
.pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; }
.pagination button { background: #fff; border: 1px solid #e2e8f0; color: #6b7280; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; font-family: inherit; transition: all 0.18s; }
.pagination button:disabled { opacity: 0.3; cursor: default; }
.pagination button.active { background: #8C1007; color: #fff; border-color: #8C1007; font-weight: 600; }
.pagination button:not(:disabled):hover { border-color: #8C1007; color: #8C1007; }
</style>