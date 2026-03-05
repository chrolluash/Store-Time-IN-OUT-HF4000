<template>
  <AdminLayout title="Attendance Logs" :subtitle="`Showing logs for ${selected_date}`">
    <div class="summary-bar">
      <div class="sum-item"><span class="sum-num maroon">{{ summary.present }}</span><span class="sum-label">Present</span></div>
      <div class="sum-divider"></div>
      <div class="sum-item"><span class="sum-num yellow">{{ summary.late }}</span><span class="sum-label">Late</span></div>
      <div class="sum-divider"></div>
      <div class="sum-item"><span class="sum-num red">{{ summary.absent }}</span><span class="sum-label">Absent</span></div>
      <div class="sum-divider"></div>
      <div class="sum-item"><span class="sum-num">{{ summary.total }}</span><span class="sum-label">Total</span></div>
    </div>

    <div class="filter-bar">
      <input type="date" v-model="dateFilter" @change="applyFilters" class="date-input" />
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" @input="debouncedSearch" type="text" placeholder="Search name or ID..." />
      </div>
      <select v-model="deptFilter" @change="applyFilters">
        <option value="">All Branches</option>
        <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
      </select>
      <select v-model="positionFilter" @change="applyFilters">
        <option value="">All Positions</option>
        <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
      </select>
      <select v-model="statusFilter" @change="applyFilters">
        <option value="">All Statuses</option>
        <option value="present">Present</option>
        <option value="late">Late</option>
        <option value="absent">Absent</option>
      </select>
    </div>

    <div class="table-wrap">
      <table class="logs-table">
        <thead>
          <tr>
            <th>Employee</th><th>Position</th><th>Branch</th><th>Shift</th>
            <th>Time In</th><th>Break 1</th><th>Lunch</th>
            <th>Break 3</th><th>Time Out</th><th>Hours</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="logs.data.length === 0">
            <td colspan="11" class="empty-row">No logs found for this date.</td>
          </tr>
          <tr v-for="log in logs.data" :key="log.id" class="log-row">
            <td>
              <div class="emp-cell">
                <div class="emp-av">{{ log.full_name?.charAt(0) }}</div>
                <div>
                  <div class="emp-name">{{ log.full_name }}</div>
                  <div class="emp-id">{{ log.id_num }}</div>
                </div>
              </div>
            </td>
            <td><span class="position-badge">{{ log.position }}</span></td>
            <td class="muted-text">{{ log.store_dept }}</td>
            <td><span class="shift-mono">{{ log.shift_from }}–{{ log.shift_to }}</span></td>
            <td><TimeStamp :value="log.time_in" color="green" /></td>
            <td><div class="break-pair"><TimeStamp :value="log.break1_out" label="OUT" color="yellow" /><TimeStamp :value="log.break1_in" label="IN" color="green" /></div></td>
            <td><div class="break-pair"><TimeStamp :value="log.break2_out" label="OUT" color="yellow" /><TimeStamp :value="log.break2_in" label="IN" color="green" /></div></td>
            <td><div class="break-pair"><TimeStamp :value="log.break3_out" label="OUT" color="yellow" /><TimeStamp :value="log.break3_in" label="IN" color="green" /></div></td>
            <td><TimeStamp :value="log.time_out" color="red" /></td>
            <td><span v-if="log.worked_hours" class="hours-badge">{{ log.worked_hours }}h</span><span v-else class="muted-text">—</span></td>
            <td><span class="status-pill" :class="log.status">{{ log.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination" v-if="logs.last_page > 1">
      <button v-for="link in logs.links" :key="link.label" :disabled="!link.url" :class="{ active: link.active }" @click="link.url && router.get(link.url)" v-html="link.label"/>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, defineComponent, h } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  logs: Object,
  summary: Object,
  departments: Array,
  positions: Array,
  filters: Object,
  selected_date: String
})

const search         = ref(props.filters?.search   || '')
const deptFilter     = ref(props.filters?.dept     || '')
const positionFilter = ref(props.filters?.position || '')
const statusFilter   = ref(props.filters?.status   || '')
const dateFilter     = ref(props.filters?.date     || props.selected_date)

let timer = null
function debouncedSearch() { clearTimeout(timer); timer = setTimeout(applyFilters, 350) }
function applyFilters() {
  router.get(route('admin.logs.index'), {
    date:     dateFilter.value     || undefined,
    search:   search.value         || undefined,
    dept:     deptFilter.value     || undefined,
    position: positionFilter.value || undefined,
    status:   statusFilter.value   || undefined,
  }, { preserveState: true, replace: true })
}

const TimeStamp = defineComponent({
  props: { value: String, color: String, label: String },
  setup(p) {
    return () => p.value
      ? h('span', { class: `ts ts-${p.color}`, style: 'font-family: JetBrains Mono, monospace; font-size: 11px;' }, [(p.label ? p.label + ' ' : '') + p.value])
      : h('span', { class: 'muted-text', style: 'font-size:11px' }, '—')
  }
})
</script>

<style scoped>
.summary-bar { display: flex; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 24px; margin-bottom: 20px; width: fit-content; gap: 24px; }
.sum-item { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.sum-num  { font-size: 28px; font-weight: 700; color: #1a1a1a; line-height: 1; }
.sum-num.maroon { color: #6D2932; }
.sum-num.yellow { color: #ca8a04; }
.sum-num.red    { color: #dc2626; }
.sum-label { font-size: 11px; color: #64748b; font-weight: 500; }
.sum-divider { width: 1px; height: 36px; background: #e2e8f0; }

.filter-bar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.date-input { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px; color: #1a1a1a; font-size: 13px; font-family: 'JetBrains Mono', monospace; }
.date-input:focus { outline: none; border-color: #8C1007; }
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; color: #9ca3af; }
.search-wrap input { width: 100%; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px 9px 32px; color: #1a1a1a; font-size: 13px; font-family: inherit; }
.search-wrap input:focus { outline: none; border-color: #8C1007; }
.search-wrap input::placeholder { color: #bbb; }
select { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 32px 9px 12px; color: #1a1a1a; font-size: 13px; font-family: inherit; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; }
select:focus { outline: none; border-color: #8C1007; }

.table-wrap { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; overflow-x: auto; }
.logs-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.logs-table th { padding: 10px 14px; text-align: left; font-size: 10px; font-weight: 700; color: #561C24; text-transform: uppercase; letter-spacing: 0.7px; background: #f9f0f1; border-bottom: 1px solid #e8d0d2; white-space: nowrap; }
.logs-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #1a1a1a; vertical-align: middle; }
.log-row:last-child td { border-bottom: none; }
.log-row:hover td { background: #fdf5f5; }
.empty-row { text-align: center; color: #9ca3af; padding: 40px 0 !important; }

.emp-cell { display: flex; align-items: center; gap: 9px; }
.emp-av { width: 30px; height: 30px; border-radius: 50%; background: #6D2932; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0; }
.emp-name { font-size: 13px; font-weight: 600; color: #1a1a1a; }
.emp-id   { font-size: 10px; color: #9ca3af; font-family: 'JetBrains Mono', monospace; }
.muted-text { color: #6b7280; font-size: 11px; }
.shift-mono { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #6b7280; }
.break-pair { display: flex; flex-direction: column; gap: 3px; }

.position-badge { font-size: 11px; font-weight: 600; color: #6D2932; background: #fde8e9; padding: 3px 8px; border-radius: 4px; white-space: nowrap; }

:deep(.ts) { display: block; padding: 2px 6px; border-radius: 4px; }
:deep(.ts-green)  { background: #dcfce7; color: #16a34a; }
:deep(.ts-yellow) { background: #fef9c3; color: #ca8a04; }
:deep(.ts-red)    { background: #fee2e2; color: #dc2626; }

.hours-badge { font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 600; color: #2563eb; background: #dbeafe; padding: 2px 7px; border-radius: 4px; }

.status-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
.status-pill.present { background: #dcfce7; color: #16a34a; }
.status-pill.late    { background: #fef9c3; color: #ca8a04; }
.status-pill.absent  { background: #fee2e2; color: #dc2626; }

.pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; }
.pagination button { background: #ffffff; border: 1px solid #e2e8f0; color: #6b7280; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; font-family: inherit; transition: all 0.18s; }
.pagination button:disabled { opacity: 0.3; cursor: default; }
.pagination button.active { background: #8C1007; color: #ffffff; border-color: #8C1007; font-weight: 600; }
.pagination button:not(:disabled):hover { border-color: #8C1007; color: #8C1007; }
</style>