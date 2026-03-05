<template>
  <AdminLayout title="Dashboard" :subtitle="today">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
        <div class="stat-body"><div class="stat-value">{{ stats.total_employees }}</div><div class="stat-label">Total Employees</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon white"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="stat-body"><div class="stat-value">{{ stats.present_today }}</div><div class="stat-label">Present Today</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon yellow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-body"><div class="stat-value">{{ stats.late_today }}</div><div class="stat-label">Late Today</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        <div class="stat-body"><div class="stat-value">{{ stats.absent_today }}</div><div class="stat-label">Absent Today</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
        <div class="stat-body"><div class="stat-value">{{ stats.on_break }}</div><div class="stat-label">On Break Now</div></div>
      </div>
      <div class="stat-card" :class="{ 'stat-alert': stats.unenrolled_count > 0 }">
        <div class="stat-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3"/></svg></div>
        <div class="stat-body"><div class="stat-value">{{ stats.unenrolled_count }}</div><div class="stat-label">Not Enrolled</div></div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Today's Activity</h2>
          <Link :href="route('admin.logs.index')" class="card-link">View all logs →</Link>
        </div>
        <div class="activity-list">
          <div v-if="recent_activity.length === 0" class="empty-state">No activity recorded today.</div>
          <div v-for="item in recent_activity" :key="item.id_num" class="activity-row">
            <div class="act-avatar">{{ item.full_name?.charAt(0) }}</div>
            <div class="act-info">
              <div class="act-name">{{ item.full_name }}</div>
              <div class="act-dept">{{ item.store_dept }}</div>
            </div>
            <div class="act-times">
              <span class="time-tag in" v-if="item.time_in">IN {{ item.time_in }}</span>
              <span class="time-tag out" v-if="item.time_out">OUT {{ item.time_out }}</span>
            </div>
            <div class="act-status" :class="item.status">{{ item.status }}</div>
          </div>
        </div>
      </div>

      <div class="right-col">
        <div class="card">
          <div class="card-header"><h2 class="card-title">7-Day Trend</h2></div>
          <div class="trend-chart">
            <div v-for="day in trend" :key="day.date" class="trend-bar-group">
              <div class="trend-bars">
                <div class="trend-bar present" :style="{ height: barHeight(day.present) + 'px' }" :title="`Present: ${day.present}`"></div>
                <div class="trend-bar late" :style="{ height: barHeight(day.late) + 'px' }" :title="`Late: ${day.late}`"></div>
              </div>
              <div class="trend-label">{{ day.date }}</div>
            </div>
          </div>
          <div class="trend-legend">
            <span class="legend-dot present"></span>Present
            <span class="legend-dot late" style="margin-left:12px"></span>Late
          </div>
        </div>

        <div class="card" v-if="unenrolled.length > 0">
          <div class="card-header">
            <h2 class="card-title">⚠ Needs Enrollment</h2>
            <Link :href="route('admin.employees.index')" class="card-link">Manage →</Link>
          </div>
          <div class="unenrolled-list">
            <div v-for="emp in unenrolled" :key="emp.id" class="unenrolled-row">
              <div class="u-name">{{ emp.full_name }}</div>
              <div class="u-dept">{{ emp.store_dept }}</div>
              <button class="enroll-btn" @click="openEnrollModal(emp)">Enroll</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Enroll Modal ── -->
    <Teleport to="body">
      <transition name="fade">
        <div class="modal-overlay" v-if="enrollModal.open" @click.self="closeEnrollModal">
          <div class="modal-panel">
            <div class="modal-header">
              <div>
                <div class="modal-title">Enroll: {{ enrollModal.employee?.full_name }}</div>
                <div class="modal-sub">ID: {{ enrollModal.employee?.id_num }} · {{ enrollModal.employee?.store_dept }}</div>
              </div>
              <button class="modal-close" @click="closeEnrollModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
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
import { ref, computed, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({ stats: Object, recent_activity: Array, trend: Array, unenrolled: Array, today: String })

const maxVal = computed(() => Math.max(...props.trend.flatMap(d => [d.present, d.late]), 1))
function barHeight(val) { return Math.round((val / maxVal.value) * 80) }

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
  router.reload()
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
  matchScanned.value = scannedTemplate; matchQueue.value = queue; runNextMatch()
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
.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #ffffff; border: 1px solid #e8d0d2; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 14px; transition: box-shadow 0.2s; }
.stat-card:hover { box-shadow: 0 2px 8px rgba(86,28,36,0.1); }
.stat-card.stat-alert { border-color: rgba(251,146,60,0.4); }
.stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.green  { background: #dcfce7; color: #16a34a; }
.stat-icon.white  { background: #fde8e9; color: #6D2932; }
.stat-icon.yellow { background: #fef9c3; color: #ca8a04; }
.stat-icon.red    { background: #fee2e2; color: #dc2626; }
.stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.stat-icon.orange { background: #ffedd5; color: #ea580c; }
.stat-value { font-size: 26px; font-weight: 700; color: #3E0703; line-height: 1; }
.stat-label { font-size: 11px; color: #64748b; margin-top: 4px; font-weight: 500; }

.dashboard-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
@media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }

.card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.card-title { font-size: 14px; font-weight: 600; color: #3E0703; margin: 0; }
.card-link { font-size: 12px; color: #64748b; text-decoration: none; }
.card-link:hover { color: #8C1007; }

.activity-list { display: flex; flex-direction: column; gap: 10px; }
.empty-state { font-size: 13px; color: #64748b; text-align: center; padding: 24px 0; }
.activity-row { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; background: #f8fafc; border: 1px solid #f1f5f9; }
.act-avatar { width: 34px; height: 34px; border-radius: 50%; background: #8C1007; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
.act-info { flex: 1; min-width: 0; }
.act-name { font-size: 13px; font-weight: 600; color: #1a1a1a; }
.act-dept { font-size: 11px; color: #64748b; }
.act-times { display: flex; gap: 6px; flex-shrink: 0; }
.time-tag { font-size: 11px; font-family: 'JetBrains Mono', monospace; padding: 3px 7px; border-radius: 4px; }
.time-tag.in  { background: #dcfce7; color: #16a34a; }
.time-tag.out { background: #fee2e2; color: #dc2626; }
.act-status { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; text-transform: capitalize; flex-shrink: 0; }
.act-status.present { background: #dcfce7; color: #16a34a; }
.act-status.late    { background: #fef9c3; color: #ca8a04; }
.act-status.absent  { background: #fee2e2; color: #dc2626; }

.right-col { display: flex; flex-direction: column; gap: 20px; }
.trend-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 100px; gap: 8px; margin-bottom: 12px; }
.trend-bar-group { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
.trend-bars { display: flex; align-items: flex-end; gap: 2px; height: 80px; }
.trend-bar { width: 10px; border-radius: 3px 3px 0 0; min-height: 2px; transition: height 0.4s ease; }
.trend-bar.present { background: #16a34a; }
.trend-bar.late    { background: #F6E05E; }
.trend-label { font-size: 9px; color: #64748b; white-space: nowrap; }
.trend-legend { display: flex; align-items: center; font-size: 11px; color: #64748b; }
.legend-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
.legend-dot.present { background: #16a34a; }
.legend-dot.late    { background: #F6E05E; }

.unenrolled-list { display: flex; flex-direction: column; gap: 8px; }
.unenrolled-row { display: flex; align-items: center; gap: 10px; padding: 8px 10px; background: #fff5f5; border: 1px solid #fca5a5; border-radius: 8px; }
.u-name { font-size: 13px; color: #3E0703; font-weight: 500; flex: 1; }
.u-dept { font-size: 11px; color: #64748b; }
.enroll-btn { font-size: 11px; font-weight: 600; color: #fff; padding: 4px 12px; border: none; border-radius: 6px; cursor: pointer; background: #8C1007; font-family: inherit; transition: background 0.2s; }
.enroll-btn:hover { background: #6D2932; }

/* ── Modal ── */
.modal-overlay { position: fixed; inset: 0; background: rgba(13,3,5,0.8); display: flex; align-items: flex-start; justify-content: center; z-index: 500; backdrop-filter: blur(4px); padding: 24px; overflow-y: auto; }
.modal-panel { background: #f8fafc; border-radius: 20px; width: 100%; max-width: 1140px; margin: auto; display: flex; flex-direction: column; box-shadow: 0 25px 60px rgba(0,0,0,0.35); }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; background: #3E0703; border-radius: 16px 16px 0 0; flex-shrink: 0; }
.modal-title { font-size: 16px; font-weight: 700; color: #fff; }
.modal-sub   { font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 2px; }
.modal-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: none; color: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
.modal-close:hover { background: rgba(255,255,255,0.1); color: #fff; }
.modal-close svg { width: 16px; height: 16px; }
.modal-body { padding: 16px 20px; overflow-y: auto; }

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
.btn-primary { display: inline-flex; align-items: center; gap: 7px; background: #8C1007; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s; }
.btn-primary:hover { background: #6D2932; }
.btn-ghost { padding: 9px 16px; background: none; border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; font-size: 13px; cursor: pointer; font-family: inherit; transition: all 0.18s; }
.btn-ghost:hover { border-color: #8C1007; color: #8C1007; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>