<template>
  <AdminLayout title="Employees" subtitle="Manage staff and fingerprint enrollment">

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" placeholder="Search by name, ID, or department..." @input="debouncedSearch" />
      </div>
      <select v-model="deptFilter" @change="applyFilters">
        <option value="">All Branches</option>
        <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
      </select>
      <select v-model="positionFilter" @change="applyFilters">
        <option value="">All Positions</option>
        <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
      </select>
      <button class="btn-primary ml-auto" @click="openFormModal(null)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Employee
      </button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table class="emp-table">
        <thead>
          <tr>
            <th>Employee</th><th>ID Number</th><th>Position</th><th>Branch</th>
            <th>Shift</th><th>Enrollment</th><th>Today</th><th>Since</th><th>Updated</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="employees.data.length === 0">
            <td colspan="9" class="empty-row">No employees found.</td>
          </tr>
          <tr v-for="emp in employees.data" :key="emp.id" class="emp-row">
            <td>
              <div class="emp-cell">
                <div class="emp-avatar">{{ emp.full_name.charAt(0) }}</div>
                <span class="emp-name">{{ emp.full_name }}</span>
              </div>
            </td>
            <td><span class="mono">{{ emp.id_num }}</span></td>
            <td><span class="position-badge">{{ emp.position }}</span></td>
            <td class="muted-text">{{ emp.store_dept }}</td>
            <td><span class="shift-badge">{{ emp.shift_from }} – {{ emp.shift_to }}</span></td>
            <td>
              <span class="enroll-badge" :class="emp.enrollment_status">
                <span class="dot"></span>{{ enrollLabel(emp.enrollment_status) }}
              </span>
            </td>
            <td><span class="status-pill" :class="emp.today_status">{{ todayLabel(emp.today_status) }}</span></td>
            <td class="muted-text">{{ emp.created_at }}</td>
            <td class="muted-text">{{ emp.updated_at }}</td>
            <td>
              <div class="row-actions">
                <Link :href="route('admin.employees.logs', emp.id)" class="act-btn logs" title="View Logs">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                </Link>
                <button class="act-btn enroll" title="Enroll Fingerprint" @click="openEnrollModal(emp)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
                </button>
                <button class="act-btn edit" title="Edit" @click="openFormModal(emp)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="act-btn delete" @click="confirmDelete(emp)" title="Delete">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination" v-if="employees.last_page > 1">
      <button v-for="link in employees.links" :key="link.label" :disabled="!link.url" :class="{ active: link.active }" @click="link.url && router.get(link.url)" v-html="link.label"/>
    </div>

    <!-- ── Delete Confirm Modal ── -->
    <Teleport to="body">
      <div class="modal-overlay" v-if="deleteTarget" @click.self="deleteTarget = null">
        <div class="modal-sm">
          <div class="modal-icon">⚠</div>
          <h3>Delete Employee?</h3>
          <p>This will permanently delete <strong>{{ deleteTarget.full_name }}</strong> and all their attendance logs.</p>
          <div class="modal-actions">
            <button class="btn-ghost" @click="deleteTarget = null">Cancel</button>
            <button class="btn-danger" @click="doDelete">Delete</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Form Modal (Create / Edit) ── -->
    <Teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="formModal.open" @click.self="closeFormModal">
          <div class="modal-panel form-panel">
            <div class="modal-header">
              <div>
                <div class="modal-title">{{ formModal.employee ? 'Edit Employee' : 'Add Employee' }}</div>
                <div class="modal-sub">{{ formModal.employee ? formModal.employee.full_name : 'Fill in the employee details below' }}</div>
              </div>
              <button class="modal-close" @click="closeFormModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <!-- Basic Info -->
              <div class="form-card">
                <div class="form-section-title">Basic Information</div>
                <div class="form-row">
                  <div class="form-group">
                    <label>ID Number <span class="req">*</span></label>
                    <input v-model="empForm.id_num" type="text" placeholder="e.g. EMP-0001" :class="{ error: empForm.errors.id_num }" :readonly="!!formModal.employee" :style="formModal.employee ? 'opacity:0.5;cursor:not-allowed;background:#f3f4f6' : ''" />
                    <div class="field-error" v-if="empForm.errors.id_num">{{ empForm.errors.id_num }}</div>
                    <div class="field-error" v-if="formModal.employee" style="color:#64748b;font-size:10px">ID cannot be changed after creation</div>
                  </div>
                  <div class="form-group">
                    <label>Full Name <span class="req">*</span></label>
                    <input v-model="empForm.full_name" type="text" placeholder="Last, First Middle" :class="{ error: empForm.errors.full_name }" />
                    <div class="field-error" v-if="empForm.errors.full_name">{{ empForm.errors.full_name }}</div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Position <span class="req">*</span></label>
                    <select v-model="empForm.position" :class="{ error: empForm.errors.position }">
                      <option value="" disabled>Select position...</option>
                      <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
                    </select>
                    <div class="field-error" v-if="empForm.errors.position">{{ empForm.errors.position }}</div>
                  </div>
                  <div class="form-group">
                    <label>Store / Branch <span class="req">*</span></label>
                    <select v-model="empForm.store_dept" :class="{ error: empForm.errors.store_dept }">
                      <option value="" disabled>Select branch...</option>
                      <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                    </select>
                    <div class="field-error" v-if="empForm.errors.store_dept">{{ empForm.errors.store_dept }}</div>
                  </div>
                </div>
              </div>
              <!-- Shift -->
              <div class="form-card">
                <div class="form-section-title">Shift Schedule</div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Shift Start <span class="req">*</span></label>
                    <div class="time-picker">
                      <select v-model="shiftFromH"><option v-for="h in hours" :key="h" :value="h">{{ h }}</option></select>
                      <span class="colon">:</span>
                      <select v-model="shiftFromM"><option v-for="m in minutes" :key="m" :value="m">{{ m }}</option></select>
                      <select v-model="shiftFromAmpm"><option value="AM">AM</option><option value="PM">PM</option></select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Shift End <span class="req">*</span></label>
                    <div class="time-picker">
                      <select v-model="shiftToH"><option v-for="h in hours" :key="h" :value="h">{{ h }}</option></select>
                      <span class="colon">:</span>
                      <select v-model="shiftToM"><option v-for="m in minutes" :key="m" :value="m">{{ m }}</option></select>
                      <select v-model="shiftToAmpm"><option value="AM">AM</option><option value="PM">PM</option></select>
                    </div>
                  </div>
                </div>
                <div class="shift-preview" v-if="empForm.shift_from && empForm.shift_to">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  Shift: {{ displayTime(empForm.shift_from) }} → {{ displayTime(empForm.shift_to) }} ({{ shiftDuration }})
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-ghost" @click="closeFormModal">Cancel</button>
              <button class="btn-primary" :disabled="empForm.processing" @click="submitForm">
                {{ empForm.processing ? 'Saving...' : (formModal.employee ? 'Save Changes' : 'Create Employee') }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ── Enroll Modal ── -->
    <Teleport to="body">
      <transition name="fade">
        <div class="modal-overlay enroll-overlay" v-if="enrollModal.open" @click.self="closeEnrollModal">
          <div class="modal-panel enroll-panel">
            <div class="modal-header">
              <div>
                <div class="modal-title">Enroll: {{ enrollModal.employee?.full_name }}</div>
                <div class="modal-sub">ID: {{ enrollModal.employee?.id_num }} · {{ enrollModal.employee?.store_dept }}</div>
              </div>
              <button class="modal-close" @click="closeEnrollModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body enroll-body">
              <div class="enroll-grid">
                <!-- Left: Scanner -->
                <div class="scanner-card">
                  <div class="scanner-title">HF4000 Scanner</div>
                  <div class="conn-bar">
                    <div class="conn-dot" :class="connected ? 'connected' : 'disconnected'"></div>
                    <span>{{ connected ? 'Scanner Connected' : 'Disconnected' }}</span>
                    <button v-if="!connected" class="conn-btn" @click="connectScanner">Connect</button>
                    <button v-else class="conn-btn disconnect" @click="disconnectScanner">Disconnect</button>
                  </div>
                  <div class="fp-preview-wrap">
                    <img :src="previewImg" class="fp-preview" id="enrollPreview" />
                    <div class="fp-overlay" v-if="scanning">
                      <div class="scan-pulse"></div>
                      <div class="scan-text">{{ scanMessage }}</div>
                    </div>
                  </div>
                  <div class="scanner-status" :class="statusClass">{{ statusMessage }}</div>
                  <div class="log-box" ref="logWrap"><pre>{{ log }}</pre></div>
                </div>
                <!-- Right: Cards -->
                <div class="enrollment-cards">
                  <div class="fp-row" v-for="fp in fpSlots" :key="fp.slot">
                    <div class="fp-card" :class="{ enrolled: fp.has, active: activeSlot === fp.slot }">
                      <div class="fp-card-header">
                        <div class="fp-icon" :class="{ active: fp.has }">
                          <svg viewBox="0 0 32 32" fill="none"><path d="M16 4C11 4 7 8 7 13c0 3 1 6 2 9l1 4h12l1-4c1-3 2-6 2-9 0-5-4-9-9-9z" stroke="currentColor" stroke-width="1.5"/><path d="M13 14c0-1.657 1.343-3 3-3s3 1.343 3 3-1.343 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </div>
                        <div class="fp-card-info">
                          <div class="fp-card-title">{{ fp.label }}</div>
                          <div class="fp-card-sub">{{ fp.sub }}</div>
                        </div>
                        <div class="fp-status-badge" :class="fp.has ? 'enrolled' : 'empty'">
                          {{ fp.has ? '✓ Enrolled' : 'Not enrolled' }}
                        </div>
                      </div>
                      <div class="fp-card-actions">
                        <button class="btn-capture" :disabled="!connected || scanning" @click="startEnroll(fp.slot)" :class="{ active: activeSlot === fp.slot && scanning }">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
                          {{ activeSlot === fp.slot && scanning ? 'Scanning...' : (fp.has ? 'Re-enroll' : 'Enroll Now') }}
                        </button>
                        <button v-if="fp.has" class="btn-clear" @click="clearFingerprint(fp.slot)">Clear</button>
                      </div>
                    </div>
                    <div class="tpl-box" :class="{ filled: fp.has }">
                      <div class="tpl-box-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10h8M8 14h5"/></svg>
                        FP{{ fp.slot }} Template
                      </div>
                      <div v-if="fp.tpl" class="tpl-box-data">{{ fp.tpl }}</div>
                      <div v-else class="tpl-box-empty">No template stored yet</div>
                    </div>
                  </div>

                  <!-- Summary -->
                  <div class="status-summary" :class="enrollStatus">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0">
                      <template v-if="enrollStatus==='complete'"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></template>
                      <template v-else><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></template>
                    </svg>
                    <div>
                      <div class="summary-title">{{ enrollSummaryTitle }}</div>
                      <div class="summary-sub">{{ enrollSummarySub }}</div>
                    </div>
                  </div>

                  <!-- Match Test -->
                  <div class="match-test-card">
                    <div class="match-test-header">
                      <div class="match-test-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><path d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
                      </div>
                      <div>
                        <div class="match-test-title">Match Test</div>
                        <div class="match-test-sub">Scan your finger to verify enrollment worked</div>
                      </div>
                    </div>
                    <button class="btn-test-scan" :disabled="!connected || testScanning || (!localHasFp1 && !localHasFp2)" @click="startMatchTest" :class="{ active: testScanning }">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
                      {{ testScanning ? 'Scanning...' : 'Start Match Test' }}
                    </button>
                    <transition name="fade">
                      <div v-if="matchResult !== null" class="match-result" :class="matchResult.matched ? 'success' : 'fail'">
                        <div class="match-result-icon">
                          <svg v-if="matchResult.matched" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        </div>
                        <div>
                          <div class="match-result-title">{{ matchResult.matched ? '✓ Match Successful!' : '✗ No Match' }}</div>
                          <div class="match-result-sub">{{ matchResult.message }}</div>
                        </div>
                      </div>
                    </transition>
                  </div>

                  <div class="enroll-footer">
                    <button class="btn-ghost" @click="closeEnrollModal">Close</button>
                    <button v-if="enrollStatus === 'complete'" class="btn-primary" @click="closeEnrollModal">Done ✓</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({ employees: Object, departments: Array, positions: Array, filters: Object })

// ── Table / filters ──────────────────────────────────────
const search         = ref(props.filters?.search || '')
const deptFilter     = ref(props.filters?.dept || '')
const positionFilter = ref(props.filters?.position || '')
const deleteTarget   = ref(null)

let searchTimer = null
function debouncedSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilters, 350) }
function applyFilters() {
  router.get(route('admin.employees.index'), { search: search.value || undefined, dept: deptFilter.value || undefined, position: positionFilter.value || undefined }, { preserveState: true, replace: true })
}
function enrollLabel(s) { return { complete: 'Enrolled', partial: 'Partial', none: 'Not Enrolled' }[s] || s }
function todayLabel(s)  { return { present: 'Present', late: 'Late', absent: 'Absent', half_day: 'Half Day', no_log: '—' }[s] || s }
function confirmDelete(emp) { deleteTarget.value = emp }
function doDelete() { router.delete(route('admin.employees.destroy', deleteTarget.value.id), { onSuccess: () => { deleteTarget.value = null } }) }

// ── Form Modal ───────────────────────────────────────────
const formModal = ref({ open: false, employee: null })
const hours   = ['1','2','3','4','5','6','7','8','9','10','11','12']
const minutes = ['00','05','10','15','20','25','30','35','40','45','50','55']

function to24(h, m, ampm) {
  let hour = parseInt(h)
  if (ampm === 'AM' && hour === 12) hour = 0
  if (ampm === 'PM' && hour !== 12) hour += 12
  return `${String(hour).padStart(2,'0')}:${String(m).padStart(2,'0')}`
}
function from24(t) {
  if (!t) return { h: '8', m: '00', ampm: 'AM' }
  // Already 12hr e.g. "8:00 AM" or "12:00 PM"
  const match12 = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i)
  if (match12) {
    const m = match12[2].padStart(2,'0')
    // snap to nearest 5-min step in minutes array
    const snapped = String(Math.round(parseInt(m)/5)*5).padStart(2,'0')
    const validM = ['00','05','10','15','20','25','30','35','40','45','50','55']
    return { h: match12[1], m: validM.includes(snapped) ? snapped : '00', ampm: match12[3].toUpperCase() }
  }
  // 24hr e.g. "08:00"
  const [hStr, mStr] = t.split(':')
  let h = parseInt(hStr)
  const ampm = h >= 12 ? 'PM' : 'AM'
  if (h === 0) h = 12; else if (h > 12) h -= 12
  const rawM = (mStr || '00').slice(0, 2).padStart(2,'0')
  const snapped = String(Math.round(parseInt(rawM)/5)*5).padStart(2,'0')
  const validM = ['00','05','10','15','20','25','30','35','40','45','50','55']
  return { h: String(h), m: validM.includes(snapped) ? snapped : '00', ampm }
}
function displayTime(t) { const { h, m, ampm } = from24(t); return `${h}:${m} ${ampm}` }
function toBackend24(t) {
  if (!t) return '08:00'
  const match12 = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i)
  if (match12) return to24(match12[1], match12[2], match12[3].toUpperCase())
  return t.slice(0, 5) // already HH:MM
}

const shiftFromH    = ref('8');  const shiftFromM    = ref('00'); const shiftFromAmpm = ref('AM')
const shiftToH      = ref('5');  const shiftToM      = ref('00'); const shiftToAmpm   = ref('PM')

const empForm = useForm({ id_num: '', full_name: '', position: '', store_dept: '', shift_from: '08:00', shift_to: '17:00' })

watch([shiftFromH, shiftFromM, shiftFromAmpm], () => { empForm.shift_from = to24(shiftFromH.value, shiftFromM.value, shiftFromAmpm.value) })
watch([shiftToH, shiftToM, shiftToAmpm],       () => { empForm.shift_to   = to24(shiftToH.value, shiftToM.value, shiftToAmpm.value) })

const shiftDuration = computed(() => {
  if (!empForm.shift_from || !empForm.shift_to) return ''
  const [fh, fm] = empForm.shift_from.split(':').map(Number)
  const [th, tm] = empForm.shift_to.split(':').map(Number)
  let mins = (th*60+tm) - (fh*60+fm); if (mins < 0) mins += 1440
  return `${Math.floor(mins/60)}h${mins%60 > 0 ? ` ${mins%60}m` : ''}`
})

function openFormModal(emp) {
  empForm.reset()
  empForm.clearErrors()
  if (emp) {
    empForm.id_num = emp.id_num; empForm.full_name = emp.full_name
    empForm.position = emp.position; empForm.store_dept = emp.store_dept
    empForm.shift_from = toBackend24(emp.shift_from); empForm.shift_to = toBackend24(emp.shift_to)
    const f = from24(emp.shift_from), t = from24(emp.shift_to)
    shiftFromH.value = f.h; shiftFromM.value = f.m; shiftFromAmpm.value = f.ampm
    shiftToH.value = t.h; shiftToM.value = t.m; shiftToAmpm.value = t.ampm
  } else {
    shiftFromH.value = '8'; shiftFromM.value = '00'; shiftFromAmpm.value = 'AM'
    shiftToH.value = '5'; shiftToM.value = '00'; shiftToAmpm.value = 'PM'
  }
  formModal.value = { open: true, employee: emp || null }
}
function closeFormModal() { formModal.value.open = false; empForm.reset(); empForm.clearErrors() }

function submitForm() {
  const opts = { preserveScroll: true, onSuccess: () => { closeFormModal() }, onError: (e) => console.log(e) }
  if (formModal.value.employee) {
    empForm.transform(data => ({ full_name: data.full_name, position: data.position, store_dept: data.store_dept, shift_from: data.shift_from, shift_to: data.shift_to })).put(route('admin.employees.update', formModal.value.employee.id), opts)
  } else {
    empForm.post(route('admin.employees.store'), opts)
  }
}

// ── Enroll Modal ─────────────────────────────────────────
const enrollModal   = ref({ open: false, employee: null })
const connected     = ref(false)
const scanning      = ref(false)
const activeSlot    = ref(null)
const scanMessage   = ref('')
const statusMessage = ref('Connecting to scanner...')
const statusClass   = ref('idle')
const log           = ref('System ready.\n')
const logWrap       = ref(null)
const BLANK_IMG     = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAkCAYAAABIdFAMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAHhJREFUeNo8zjsOxCAMBFB/KEAUFFR0Cbng3nQPw68ArZdAlOZppPFIBhH5EAB8b+Tlt9MYQ6i1BuqFaq1CKSVcxZ2Acs6406KUgpt5/LCKuVgz5BDCSb13ZO99ZOdcZGvt4mJjzMVKqcha68iIePB86GAiOv8CDADlIUQBs7MD3wAAAABJRU5ErkJggg=='
const previewImg    = ref(BLANK_IMG)
const localHasFp1   = ref(false)
const localHasFp2   = ref(false)
const storedTpl1    = ref('')
const storedTpl2    = ref('')
const testScanning  = ref(false)
const matchResult   = ref(null)
const pendingMatchFinger = ref(null)
const matchQueue    = ref([])
const matchScanned  = ref('')

const fpSlots = computed(() => [
  { slot: 1, label: 'Left Index Finger',  sub: 'Fingerprint 1 (Primary)',   has: localHasFp1.value, tpl: storedTpl1.value },
  { slot: 2, label: 'Right Index Finger', sub: 'Fingerprint 2 (Secondary)', has: localHasFp2.value, tpl: storedTpl2.value },
])
const enrollStatus       = computed(() => localHasFp1.value && localHasFp2.value ? 'complete' : localHasFp1.value || localHasFp2.value ? 'partial' : 'none')
const enrollSummaryTitle = computed(() => ({ complete: '✓ Both fingerprints enrolled', partial: '⚠ Only one fingerprint enrolled', none: '✗ No fingerprints enrolled yet' }[enrollStatus.value]))
const enrollSummarySub   = computed(() => ({ complete: 'Employee can now use the time-in kiosk.', partial: 'Enroll the second finger for backup identification.', none: 'Enroll both fingers to enable attendance tracking.' }[enrollStatus.value]))

function addLog(msg) {
  log.value += `[${new Date().toLocaleTimeString()}] ${msg}\n`
  nextTick(() => { if (logWrap.value) logWrap.value.scrollTop = logWrap.value.scrollHeight })
}

let hooked = false
function hookProcessJs() {
  if (hooked || !window.ws) return
  hooked = true
  const orig = window.ws.onmessage
  window.ws.onmessage = function(evt) {
    if (orig) orig.call(this, evt)
    try { if (enrollModal.value.open) handleEnrollMsg(eval('(' + evt.data + ')')) } catch(e) {}
  }
  window.ws.onclose = function() {
    connected.value = false; scanning.value = false; hooked = false
    statusMessage.value = 'Scanner disconnected.'; statusClass.value = 'idle'; addLog('Disconnected.')
  }
  addLog('Hooked into process.js WebSocket.')
}

function handleEnrollMsg(obj) {
  switch (obj.workmsg) {
    case 2: scanMessage.value = 'Place finger...'; statusMessage.value = 'Place finger on scanner...'; addLog('Waiting for finger...'); break
    case 3: addLog('Lift finger.'); break
    case 5:
      if (testScanning.value) {
        testScanning.value = false
        const tpl = obj.retmsg == 1 ? (obj.data2 || obj.data1) : null
        if (tpl && tpl !== 'null') { addLog('Captured. Comparing...'); handleMatchTestResult(tpl) }
        else { matchResult.value = { matched: false, message: 'Capture failed. Try again.' }; statusClass.value = 'error' }
      }
      break
    case 6:
      if (obj.retmsg == 1 && obj.data1 && obj.data1 !== 'null') {
        addLog('Enrolled! Saving...'); statusMessage.value = 'Saving...'; statusClass.value = 'success'; scanning.value = false
        saveTemplate(activeSlot.value, obj.data1)
      } else { addLog('Failed.'); statusMessage.value = 'Failed. Try again.'; statusClass.value = 'error'; scanning.value = false; activeSlot.value = null }
      break
    case 7:
      if (obj.image && obj.image !== 'null') { previewImg.value = 'data:image/png;base64,' + obj.image; addLog('Image updated.') }
      break
    case 8:
      addLog('Timed out.'); statusMessage.value = 'Timed out. Try again.'; statusClass.value = 'error'; scanning.value = false; activeSlot.value = null
      if (testScanning.value) { testScanning.value = false; matchResult.value = { matched: false, message: 'Timed out.' } }
      break
    case 9: {
      const score = parseInt(obj.retmsg), finger = pendingMatchFinger.value
      addLog(`Finger ${finger} score: ${score}`)
      if (score >= 30) {
        testScanning.value = false; pendingMatchFinger.value = null
        matchResult.value = { matched: true, message: `Finger ${finger} matched! Score: ${score}/100` }
        statusMessage.value = `Match! Score: ${score}`; statusClass.value = 'success'
      } else { pendingMatchFinger.value = null; runNextMatch() }
      break
    }
  }
}

function waitForConnection() {
  addLog('Waiting for scanner...')
  const check = setInterval(() => {
    if (!window.ws) return
    if (window.ws.readyState === WebSocket.OPEN) { clearInterval(check); connected.value = true; statusMessage.value = 'Ready to enroll.'; statusClass.value = 'success'; addLog('Scanner connected.'); hookProcessJs() }
    else if (window.ws.readyState === WebSocket.CONNECTING) {
      window.ws.addEventListener('open', () => { clearInterval(check); connected.value = true; statusMessage.value = 'Ready to enroll.'; statusClass.value = 'success'; hookProcessJs() }, { once: true })
    }
  }, 200)
  setTimeout(() => { clearInterval(check); if (!connected.value) { statusMessage.value = 'Click Connect.'; statusClass.value = 'idle'; addLog('Auto-connect timed out.') } }, 8000)
}

function connectScanner() {
  hooked = false; addLog('Connecting...')
  if (window.$ && $('#host_connect').length) { $('#host_connect').trigger('click'); setTimeout(waitForConnection, 300) }
  else {
    window.ws = new WebSocket('ws://127.0.0.1:21187/fps')
    window.ws.onopen    = () => { connected.value = true; statusClass.value = 'success'; statusMessage.value = 'Connected.'; hookProcessJs() }
    window.ws.onclose   = () => { connected.value = false; statusClass.value = 'idle'; statusMessage.value = 'Disconnected.' }
    window.ws.onerror   = () => { statusClass.value = 'error'; statusMessage.value = 'Connection failed.' }
    window.ws.onmessage = (evt) => { try { handleEnrollMsg(eval('(' + evt.data + ')')) } catch(e) {} }
  }
}
function disconnectScanner() {
  if (window.$ && $('#host_close').length) $('#host_close').trigger('click')
  else window.ws?.close()
}

function openEnrollModal(emp) {
  log.value = 'System ready.\n'; previewImg.value = BLANK_IMG
  scanning.value = false; activeSlot.value = null; testScanning.value = false; matchResult.value = null
  statusMessage.value = 'Connecting to scanner...'; statusClass.value = 'idle'
  localHasFp1.value = !!emp.has_fp1; localHasFp2.value = !!emp.has_fp2
  storedTpl1.value = emp.fingerprint_1 || ''; storedTpl2.value = emp.fingerprint_2 || ''
  enrollModal.value = { open: true, employee: emp }
  nextTick(() => waitForConnection())
}
function closeEnrollModal() {
  enrollModal.value.open = false
  router.reload({ only: ['employees'] })
}

function startEnroll(finger) {
  if (!connected.value || scanning.value) return
  scanning.value = true; activeSlot.value = finger; scanMessage.value = 'Place finger...'
  statusMessage.value = `Place ${finger === 1 ? 'LEFT' : 'RIGHT'} index finger.`; statusClass.value = 'scanning'
  addLog(`Enrolling finger ${finger}...`)
  const nameEl = document.getElementById('userName')
  if (nameEl) nameEl.value = enrollModal.value.employee?.full_name
  if (typeof window.EnrollTemplate === 'function') window.EnrollTemplate()
  else window.ws.send('{"cmd":"enrol","data1":"","data2":""}')
}

async function saveTemplate(finger, template) {
  try {
    const xsrf = document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='))?.split('=')[1]
    const res = await axios.post(route('admin.employees.fingerprint.save', enrollModal.value.employee.id), { finger, template }, { headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } })
    if (res.data.success) {
      if (finger === 1) { localHasFp1.value = true; storedTpl1.value = template }
      if (finger === 2) { localHasFp2.value = true; storedTpl2.value = template }
      activeSlot.value = null; statusMessage.value = `Finger ${finger} saved ✓`; statusClass.value = 'success'; addLog(`Finger ${finger} saved ✓`)
    }
  } catch (err) { activeSlot.value = null; statusMessage.value = 'Save failed.'; statusClass.value = 'error'; addLog('Save failed: ' + err.message) }
}

async function clearFingerprint(finger) {
  if (!confirm(`Clear fingerprint ${finger}?`)) return
  try {
    await axios.delete(route('admin.employees.fingerprint.clear', enrollModal.value.employee.id), { data: { finger } })
    if (finger === 1) { localHasFp1.value = false; storedTpl1.value = '' }
    if (finger === 2) { localHasFp2.value = false; storedTpl2.value = '' }
    previewImg.value = BLANK_IMG; addLog(`Finger ${finger} cleared.`)
  } catch (err) { addLog('Clear failed: ' + err.message) }
}

function startMatchTest() {
  if (!connected.value || testScanning.value) return
  testScanning.value = true; matchResult.value = null
  statusMessage.value = 'Place finger for match test...'; statusClass.value = 'scanning'; addLog('Match test — scan finger...')
  if (typeof window.GetTemplate === 'function') window.GetTemplate()
  else window.ws.send('{"cmd":"capture","data1":"","data2":""}')
}

function handleMatchTestResult(scannedTemplate) {
  const queue = []
  if (localHasFp1.value) queue.push({ finger: 1 })
  if (localHasFp2.value) queue.push({ finger: 2 })
  if (!queue.length) { testScanning.value = false; matchResult.value = { matched: false, message: 'No fingerprints enrolled.' }; return }
  matchScanned.value = scannedTemplate; matchQueue.value = queue
  runNextMatch()
}

function runNextMatch() {
  if (!matchQueue.value.length) {
    testScanning.value = false; matchResult.value = { matched: false, message: 'Did not match any enrolled finger.' }
    statusMessage.value = 'No match.'; statusClass.value = 'error'; return
  }
  const { finger } = matchQueue.value.shift()
  addLog(`Testing finger ${finger}...`)
  axios.get(route('admin.employees.fingerprint.get', enrollModal.value.employee.id) + '?finger=' + finger)
    .then(res => {
      const tpl = res.data.template
      if (!tpl) { runNextMatch(); return }
      window.ws.send(JSON.stringify({ cmd: 'setdata', data1: tpl, data2: '' }))
      window.ws.send(JSON.stringify({ cmd: 'setdata', data1: '', data2: matchScanned.value }))
      window.ws.send(JSON.stringify({ cmd: 'match', data1: '', data2: '' }))
      pendingMatchFinger.value = finger
    })
    .catch(() => runNextMatch())
}
</script>

<style scoped>
/* ── Filter bar ── */
.filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
.ml-auto { margin-left: auto; }
.search-wrap { flex: 1; position: relative; max-width: 400px; }
.search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #64748b; }
.search-wrap input { width: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px 9px 36px; color: #3E0703; font-size: 13px; font-family: inherit; }
.search-wrap input:focus { outline: none; border-color: #8C1007; }
.search-wrap input::placeholder { color: #64748b; }
select { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 32px 9px 14px; color: #1a1a1a; font-size: 13px; font-family: inherit; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; }
select:focus { outline: none; border-color: #8C1007; }

/* ── Table ── */
.table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; overflow-x: auto; }
.emp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.emp-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.7px; background: #EEEEEE; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.emp-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #3E0703; vertical-align: middle; }
.emp-row:last-child td { border-bottom: none; }
.emp-row:hover td { background: rgba(107,0,0,0.05); }
.empty-row { text-align: center; color: #64748b; padding: 40px 0 !important; }
.emp-cell { display: flex; align-items: center; gap: 10px; }
.emp-avatar { width: 32px; height: 32px; border-radius: 50%; background: #8C1007; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
.emp-name { font-weight: 600; color: #1a1a1a; }
.mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #64748b; }
.muted-text { color: #6b7280; font-size: 12px; }
.position-badge { font-size: 11px; font-weight: 600; color: #6D2932; background: #fde8e9; padding: 3px 8px; border-radius: 4px; white-space: nowrap; }
.shift-badge { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #64748b; background: #EEEEEE; padding: 3px 8px; border-radius: 4px; }
.enroll-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
.enroll-badge .dot { width: 6px; height: 6px; border-radius: 50%; }
.enroll-badge.complete { background: #dcfce7; color: #16a34a; } .enroll-badge.complete .dot { background: #16a34a; }
.enroll-badge.partial  { background: #fef9c3; color: #ca8a04; } .enroll-badge.partial .dot  { background: #ca8a04; }
.enroll-badge.none     { background: #fee2e2; color: #dc2626; } .enroll-badge.none .dot     { background: #dc2626; }
.status-pill { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
.status-pill.present { background: #dcfce7; color: #16a34a; }
.status-pill.late    { background: #fef9c3; color: #ca8a04; }
.status-pill.absent  { background: #fee2e2; color: #dc2626; }
.status-pill.no_log  { background: transparent; color: #64748b; }
.row-actions { display: flex; gap: 6px; }
.act-btn { width: 30px; height: 30px; border-radius: 6px; border: 1px solid transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; background: none; transition: all 0.18s; }
.act-btn svg { width: 14px; height: 14px; }
.act-btn.logs   { color: #2563eb; border-color: rgba(37,99,235,0.25); text-decoration: none; } .act-btn.logs:hover   { background: #dbeafe; border-color: #2563eb; }
.act-btn.enroll { color: #6D2932; border-color: rgba(109,41,50,0.3); } .act-btn.enroll:hover { background: #fde8e9; border-color: #6D2932; }
.act-btn.edit   { color: #2563eb; border-color: rgba(37,99,235,0.25); } .act-btn.edit:hover   { background: #dbeafe; border-color: #2563eb; }
.act-btn.delete { color: #dc2626; border-color: rgba(220,38,38,0.25); } .act-btn.delete:hover { background: #fee2e2; border-color: #dc2626; }

/* ── Pagination ── */
.pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; }
.pagination button { background: #fff; border: 1px solid #e2e8f0; color: #64748b; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; font-family: inherit; }
.pagination button:disabled { opacity: 0.3; cursor: default; }
.pagination button.active { background: #8C1007; color: #fff; border-color: #6B0000; font-weight: 600; }

/* ── Overlay ── */
.modal-overlay { position: fixed; inset: 0; background: rgba(13,3,5,0.8); display: flex; align-items: center; justify-content: center; z-index: 500; backdrop-filter: blur(4px); padding: 24px; overflow-y: auto; }
.enroll-overlay { align-items: flex-start; }

/* ── Delete modal ── */
.modal-sm { background: #fff; border-radius: 16px; padding: 36px 32px; width: 380px; text-align: center; flex-shrink: 0; }
.modal-icon { font-size: 32px; margin-bottom: 12px; }
.modal-sm h3 { font-size: 18px; color: #1a1a1a; margin: 0 0 8px; }
.modal-sm p { font-size: 13px; color: #64748b; margin: 0 0 24px; line-height: 1.6; }
.modal-sm strong { color: #3E0703; }
.modal-actions { display: flex; gap: 10px; justify-content: center; }

/* ── Modal panel (form + enroll) ── */
.modal-panel { background: #f8fafc; border-radius: 16px; width: 100%; display: flex; flex-direction: column; box-shadow: 0 25px 60px rgba(0,0,0,0.35); }
.form-panel   { max-width: 680px; margin: auto; }
.enroll-panel { max-width: 1140px; margin: auto; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; background: #3E0703; border-radius: 16px 16px 0 0; flex-shrink: 0; }
.modal-title { font-size: 16px; font-weight: 700; color: #fff; }
.modal-sub   { font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 2px; }
.modal-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: none; color: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
.modal-close:hover { background: rgba(255,255,255,0.1); color: #fff; }
.modal-close svg { width: 16px; height: 16px; }
.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; }
.modal-footer { padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; }

/* ── Form inside modal ── */
.form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; }
.form-section-title { font-size: 11px; font-weight: 700; color: #6D2932; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 7px; margin-bottom: 14px; }
.form-group:last-child { margin-bottom: 0; }
label { font-size: 12px; font-weight: 600; color: #64748b; }
.req { color: #dc2626; }
.form-card input[type="text"], .form-card select { background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 14px; color: #1a1a1a; font-size: 13px; font-family: inherit; width: 100%; box-sizing: border-box; }
.form-card select { appearance: none; padding-right: 32px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; cursor: pointer; }
.form-card input:focus, .form-card select:focus { outline: none; border-color: #8C1007; }
.form-card input.error, .form-card select.error { border-color: #dc2626; }
.field-error { font-size: 11px; color: #dc2626; }
.time-picker { display: flex; align-items: center; gap: 4px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; }
.time-picker:focus-within { border-color: #8C1007; }
.time-picker select { background: none; border: none; outline: none; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #1a1a1a; cursor: pointer; padding: 2px; appearance: none; text-align: center; background-image: none; width: auto; }
.time-picker select:first-child { width: 28px; }
.time-picker select:nth-child(3) { width: 34px; }
.time-picker select:last-child { width: 40px; font-family: inherit; font-size: 11px; font-weight: 600; color: #8C1007; }
.colon { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 700; color: #64748b; }
.shift-preview { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #6D2932; background: #fde8e9; border: 1px solid #e8d0d2; border-radius: 8px; padding: 9px 12px; margin-top: 4px; }
.shift-preview svg { width: 13px; height: 13px; flex-shrink: 0; }

/* ── Enroll layout ── */
.enroll-body { padding: 16px 20px !important; }
.enroll-grid { display: grid; grid-template-columns: 270px 1fr; gap: 16px; align-items: start; }
.scanner-card { background: #fff; border: 1px solid #e8d0d2; border-radius: 10px; padding: 14px; position: sticky; top: 0; }
.scanner-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
.conn-bar { display: flex; align-items: center; gap: 8px; font-size: 11px; margin-bottom: 10px; padding: 6px 10px; background: #fff; border-radius: 7px; border: 1px solid #e8d0d2; }
.conn-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.conn-dot.connected    { background: #16a34a; animation: blink 2s infinite; }
.conn-dot.disconnected { background: #9ca3af; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
.conn-bar span { flex: 1; color: #3E0703; }
.conn-btn { font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 4px; border: 1px solid #561C24; background: #8C1007; color: #fff; cursor: pointer; font-family: inherit; }
.conn-btn.disconnect { border-color: #dc2626; background: #fee2e2; color: #dc2626; }
.fp-preview-wrap { position: relative; background: #1a1a2e; border: 1px solid #2d2d44; border-radius: 8px; overflow: hidden; margin-bottom: 10px; aspect-ratio: 3/2; display: flex; align-items: center; justify-content: center; }
.fp-preview { width: 100%; height: 100%; object-fit: contain; image-rendering: pixelated; }
.fp-overlay { position: absolute; inset: 0; background: rgba(13,3,5,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; }
.scan-pulse { width: 38px; height: 38px; border-radius: 50%; border: 2px solid #fff; animation: pulse 1.4s ease-in-out infinite; }
@keyframes pulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.3);opacity:0.2} }
.scan-text { font-size: 11px; color: #E8D8C4; font-weight: 600; }
.scanner-status { font-size: 11px; font-weight: 500; padding: 7px 10px; border-radius: 6px; margin-bottom: 8px; text-align: center; }
.scanner-status.idle     { background: #f3f4f6; color: #6b7280; }
.scanner-status.success  { background: #dcfce7; color: #16a34a; }
.scanner-status.scanning { background: #fef9c3; color: #ca8a04; }
.scanner-status.error    { background: #fee2e2; color: #dc2626; }
.log-box { background: #0f0f0f; border-radius: 4px; padding: 8px 10px; height: 110px; overflow-y: auto; }
.log-box pre { margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 9px; color: #4ade80; line-height: 1.6; white-space: pre-wrap; }

.enrollment-cards { display: flex; flex-direction: column; gap: 12px; }
.fp-row { display: flex; gap: 10px; }
.fp-row .fp-card { flex: 1; }
.fp-row .tpl-box { width: 240px; flex-shrink: 0; }

.fp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; transition: border-color 0.25s; }
.fp-card.enrolled { border-color: #86efac; }
.fp-card.active   { border-color: #8C1007; box-shadow: 0 0 0 2px rgba(140,16,7,0.08); }
.fp-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.fp-icon { width: 40px; height: 40px; background: #f3f4f6; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #9ca3af; flex-shrink: 0; transition: all 0.25s; }
.fp-icon svg { width: 22px; height: 22px; }
.fp-icon.active { background: #fde8e9; color: #6D2932; }
.fp-card-info { flex: 1; }
.fp-card-title { font-size: 13px; font-weight: 700; color: #1a1a1a; }
.fp-card-sub   { font-size: 11px; color: #6b7280; margin-top: 1px; }
.fp-status-badge { font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 20px; flex-shrink: 0; }
.fp-status-badge.enrolled { background: #dcfce7; color: #16a34a; }
.fp-status-badge.empty    { background: #fee2e2; color: #dc2626; }
.fp-card-actions { display: flex; gap: 8px; }
.btn-capture { flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px; padding: 10px; background: #8C1007; border: 1px solid #6D2932; border-radius: 7px; color: #fff; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.18s; }
.btn-capture svg { width: 14px; height: 14px; }
.btn-capture:hover:not(:disabled) { background: #6D2932; }
.btn-capture.active { background: #ca8a04; border-color: #a16207; }
.btn-capture:disabled { opacity: 0.5; cursor: not-allowed; background: #C7B7A3; border-color: #C7B7A3; }
.btn-clear { padding: 10px 14px; background: #fff5f5; border: 1px solid #fca5a5; border-radius: 7px; color: #dc2626; font-size: 12px; font-family: inherit; cursor: pointer; }
.btn-clear:hover { background: #fee2e2; }

.tpl-box { background: #0f1117; border: 1px solid #1e2030; border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.tpl-box.filled { border-color: #2a3a2e; }
.tpl-box-label { display: flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; flex-shrink: 0; }
.tpl-box-label svg { width: 11px; height: 11px; color: #4b5563; }
.tpl-box-data { font-family: 'JetBrains Mono', monospace; font-size: 8.5px; color: #4ade80; line-height: 1.5; word-break: break-all; overflow-y: auto; flex: 1; min-height: 60px; max-height: 120px; }
.tpl-box-empty { font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #374151; font-style: italic; text-align: center; flex: 1; display: flex; align-items: center; justify-content: center; min-height: 60px; }

.status-summary { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; border: 1px solid; }
.status-summary.complete { background: #dcfce7; border-color: #86efac; color: #16a34a; }
.status-summary.partial  { background: #fef9c3; border-color: #fde047; color: #ca8a04; }
.status-summary.none     { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
.summary-title { font-size: 12px; font-weight: 700; }
.summary-sub   { font-size: 11px; opacity: 0.7; margin-top: 1px; }

.match-test-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; flex-direction: column; gap: 12px; }
.match-test-header { display: flex; align-items: center; gap: 12px; }
.match-test-icon { width: 36px; height: 36px; border-radius: 8px; background: #fde8e9; color: #8C1007; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.match-test-icon svg { width: 18px; height: 18px; }
.match-test-title { font-size: 13px; font-weight: 700; color: #3E0703; }
.match-test-sub { font-size: 11px; color: #64748b; margin-top: 1px; }
.btn-test-scan { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 10px; background: #8C1007; border: 1px solid #6D2932; border-radius: 7px; color: #fff; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.18s; }
.btn-test-scan svg { width: 14px; height: 14px; }
.btn-test-scan:hover:not(:disabled) { background: #6D2932; }
.btn-test-scan:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-test-scan.active { background: #ca8a04; border-color: #a16207; }
.match-result { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 8px; border: 1px solid; }
.match-result.success { background: #dcfce7; border-color: #86efac; color: #16a34a; }
.match-result.fail    { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
.match-result-icon { width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.6); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.match-result-icon svg { width: 16px; height: 16px; }
.match-result-title { font-size: 12px; font-weight: 700; }
.match-result-sub { font-size: 11px; opacity: 0.8; margin-top: 1px; }

.enroll-footer { display: flex; justify-content: flex-end; gap: 10px; }

/* ── Shared buttons ── */
.btn-primary { display: inline-flex; align-items: center; gap: 7px; background: #8C1007; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s; text-decoration: none; }
.btn-primary svg { width: 14px; height: 14px; }
.btn-primary:hover:not(:disabled) { background: #6D2932; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-ghost { padding: 9px 16px; background: none; border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; font-size: 13px; cursor: pointer; font-family: inherit; transition: all 0.18s; }
.btn-ghost:hover { border-color: #8C1007; color: #8C1007; }
.btn-danger { padding: 9px 20px; background: rgba(252,129,129,0.1); border: 1px solid rgba(252,129,129,0.3); border-radius: 8px; color: #FC8181; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-danger:hover { background: rgba(252,129,129,0.2); }

/* ── Transitions ── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>