<template>
  <div class="kiosk-shell" :class="{ dark: darkMode }">

    <!-- Header -->
    <header class="kiosk-header">
      <div class="kiosk-logo">
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 220 50'%3E%3Ctext x='50%25' y='38' text-anchor='middle' font-family='Georgia%2C Times New Roman%2C serif' font-size='40' font-weight='bold' fill='white' letter-spacing='4'%3EGUESS%3C/text%3E%3C/svg%3E" style="height:30px;width:auto;" alt="GUESS" />
      </div>
      <div class="kiosk-header-center">
        <div class="kiosk-brand">STORE TIME IN AND OUT</div>
        <div class="kiosk-sub">HF4000</div>
      </div>
      <div style="display:flex;align-items:center;gap:20px;">
        <button class="dark-toggle" @click="darkMode = !darkMode" :title="darkMode ? 'Light Mode' : 'Dark Mode'">
          <svg v-if="!darkMode" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        </button>
        <div class="kiosk-clock">
          <div class="clock-time">{{ currentTime }}</div>
          <div class="clock-date">{{ currentDate }}</div>
        </div>
      </div>
    </header>

    <!-- Main -->
    <main class="kiosk-main">

      <!-- Result overlay -->
      <transition name="result-fade">
        <div v-if="step === 'result'" class="result-overlay">
          <div class="result-card" :class="result.status_type">
            <!-- Colored top strip with icon cut into it -->
            <div class="result-top-strip" :class="result.status_type">
              <div class="result-strip-icon" :class="result.status_type">
                <svg v-if="result.status_type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </div>
            </div>

            <!-- Avatar -->
            <div class="result-avatar-wrap">
              <div class="result-avatar-pulse" v-if="result.status_type === 'success'"></div>
              <div class="result-avatar">{{ result.full_name?.charAt(0) }}</div>
            </div>

            <div class="result-name">{{ result.full_name }}</div>
            <div class="result-meta">{{ result.position }} · {{ result.store_dept }}</div>

            <template v-if="result.status_type === 'success'">
              <div class="result-badges-row">
                <div class="result-status-badge" :class="result.attendance_status">{{ result.attendance_status || 'Present' }}</div>
                <div class="result-action-pill" :class="result.action">{{ actionLabel(result.action) }}</div>
              </div>
              <div class="result-time">{{ result.logged_at }}</div>
              <div class="result-shift">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Shift: {{ result.shift_from }} – {{ result.shift_to }}
              </div>
            </template>
            <template v-else>
              <div class="result-error-msg">{{ result.message }}</div>
            </template>
          </div>

          <div class="reset-bar"><div class="reset-progress" :style="{ width: resetProgress + '%' }"></div></div>
          <div class="reset-hint">Returning in {{ resetCountdown }}s...</div>
          <button class="result-dismiss-btn" @click="dismissResult">Dismiss</button>
        </div>
      </transition>

      <!-- Action selection -->
      <div class="step-section" v-if="step === 'select'">
        <div class="select-label">Select Action</div>
        <div class="kiosk-top-grid">

          <!-- Left placeholder hidden -->
          <div class="logs-card logs-card--hidden"></div>

          <!-- Center: Actions + Breaks + Today's Logs -->
          <div class="center-col">

            <!-- Time In / Out -->
            <div class="action-outer">
              <div class="main-actions-row">
                <button class="action-btn time-in"
                  :class="{ locked: isLocked('time_in') }"
                  :disabled="isLocked('time_in')"
                  @click="openScanModal('time_in')">
                  <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                  </div>
                  <div class="action-name">Time In</div>
                  <div class="action-desc">{{ isLocked('time_in') ? 'Not available' : 'Start of shift' }}</div>
                </button>
                <button class="action-btn time-out"
                  :class="{ locked: isLocked('time_out') }"
                  :disabled="isLocked('time_out')"
                  @click="openScanModal('time_out')">
                  <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  </div>
                  <div class="action-name">Time Out</div>
                  <div class="action-desc">{{ isLocked('time_out') ? 'Not available' : 'End of shift' }}</div>
                </button>
              </div>
            </div>

            <!-- Breaks -->
            <div class="breaks-outer">
              <div class="breaks-section-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
                Breaks
              </div>

              <!-- Row 1: All Outs -->
              <div class="breaks-flat-row">
                <button v-for="n in 3" :key="'out'+n"
                  class="break-btn break-out"
                  :class="{ locked: isLocked(breaks[(n-1)*2].action) }"
                  :disabled="isLocked(breaks[(n-1)*2].action)"
                  @click="openScanModal(breaks[(n-1)*2].action)">
                  <div class="break-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
                  </div>
                  <span class="break-name">{{ breaks[(n-1)*2].label }}</span>
                </button>
              </div>

              <!-- Divider -->
              <div class="breaks-row-divider"><span>Back from Break</span></div>

              <!-- Row 2: All Ins -->
              <div class="breaks-flat-row">
                <button v-for="n in 3" :key="'in'+n"
                  class="break-btn break-in"
                  :class="{ locked: isLocked(breaks[(n-1)*2+1].action) }"
                  :disabled="isLocked(breaks[(n-1)*2+1].action)"
                  @click="openScanModal(breaks[(n-1)*2+1].action)">
                  <div class="break-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
                  </div>
                  <span class="break-name">{{ breaks[(n-1)*2+1].label }}</span>
                </button>
              </div>
            </div>

            <!-- ✅ TODAY'S LOGS — now inside center-col to match action card width -->
            <div class="logs-bottom">
              <div class="logs-bottom-header">
                <div class="logs-card-title">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                  Today's Logs
                </div>
                <div class="logs-count">{{ filteredLogs.length }}</div>
                <div class="logs-bottom-filters">
                  <div class="logs-search-wrap logs-search-wrap--inline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input v-model="logSearch" type="text" placeholder="Search name or ID..." class="logs-search" />
                  </div>
                  <select v-model="logActionFilter" class="logs-filter-select">
                    <option value="">All Actions</option>
                    <option value="time_in">Time In</option>
                    <option value="time_out">Time Out</option>
                    <option value="break1_out">B1 Out</option>
                    <option value="break1_in">B1 In</option>
                    <option value="break2_out">B2 Out</option>
                    <option value="break2_in">B2 In</option>
                    <option value="break3_out">B3 Out</option>
                    <option value="break3_in">B3 In</option>
                  </select>
                  <select v-model="logPositionFilter" class="logs-filter-select">
                    <option value="">All Positions</option>
                    <option value="Store Head">Store Head</option>
                    <option value="Store Manager">Store Manager</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Sales Personnel">Sales Personnel</option>
                  </select>
                  <select v-model="logStoreFilter" class="logs-filter-select">
                    <option value="">All Stores</option>
                    <option value="SM MOA">SM MOA</option>
                    <option value="SM Megamall">SM Megamall</option>
                    <option value="SM North EDSA">SM North EDSA</option>
                    <option value="Glorietta">Glorietta</option>
                    <option value="Robinsons Manila">Robinsons Manila</option>
                    <option value="SM Grand Central">SM Grand Central</option>
                    <option value="SM Fairview">SM Fairview</option>
                    <option value="SM Southmall">SM Southmall</option>
                    <option value="ATC">ATC</option>
                    <option value="Festival Mall">Festival Mall</option>
                    <option value="Ali Mall">Ali Mall</option>
                  </select>
                </div>
              </div>
              <div class="logs-bottom-list" ref="logsListRef">
                <div v-if="filteredLogs.length === 0" class="logs-empty-inline">No logs yet today</div>
                <transition-group name="log-slide" tag="div" class="logs-inline-entries">
                  <div v-for="entry in paginatedLogs" :key="entry.id" class="log-entry">
                    <div class="log-avatar">{{ entry.full_name?.charAt(0) }}</div>
                    <div class="log-info">
                      <div class="log-name">{{ entry.full_name }}</div>
                      <div class="log-meta">{{ entry.position }} · {{ entry.store_dept }}</div>
                      <div class="log-time">{{ entry.time }}</div>
                    </div>
                    <div class="log-action-pill" :class="entry.action">{{ actionLabel(entry.action) }}</div>
                  </div>
                </transition-group>
              </div>
              <div class="logs-bottom-pagination" v-if="totalPages > 1">
                <button class="page-btn" @click="logsPage--" :disabled="logsPage === 1">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <span class="page-info">{{ logsPage }} / {{ totalPages }}</span>
                <button class="page-btn" @click="logsPage++" :disabled="logsPage === totalPages">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
              </div>
            </div>

          </div> <!-- end center-col -->

          <!-- Right: Terminal mini card + My Logs -->
          <div class="right-col">

            <!-- Terminal mini card -->
            <div class="terminal-mini">
              <div class="terminal-mini-bar">
                <div class="t-bar-top">
                  <span class="t-dot red"></span><span class="t-dot yellow"></span><span class="t-dot green"></span>
                  <span class="t-title">Scanner Status</span>
                </div>
                <div class="t-bar-bottom">
                  <div class="t-conn" :class="connected ? 'online' : 'offline'">
                    <span class="t-conn-dot"></span>{{ connected ? 'Connected' : 'Disconnected' }}
                  </div>
                  <div class="t-bar-actions">
                    <button class="t-toggle" :class="{ active: connected }" @click="connected ? disconnectScanner() : connectScanner()" :title="connected ? 'Disconnect' : 'Connect'">
                      <span class="t-toggle-thumb"></span>
                    </button>
                    <button class="t-clear" @click="statusLog = ''">Clear</button>
                  </div>
                </div>
              </div>
              <div class="terminal-mini-body" ref="logWrap">
                <pre>{{ statusLog }}</pre>
              </div>
            </div>

            <!-- My Logs -->
            <div class="mylogs-card">
              <div class="mylogs-header">
                <div class="mylogs-title">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                  My Logs
                </div>
              </div>

              <!-- Search state -->
              <div class="mylogs-search-wrap" v-if="!myLogsEmployee">
                <div class="mylogs-search-input-wrap">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  <input v-model="myLogsSearch" type="text" placeholder="Search name or ID..." class="mylogs-search" @input="searchEmployees" />
                </div>
                <div class="mylogs-suggestions" v-if="myLogsSuggestions.length">
                  <div v-for="emp in myLogsSuggestions" :key="emp.id" class="mylogs-suggestion" @click="selectEmployee(emp)">
                    <div class="suggestion-avatar">{{ emp.full_name.charAt(0) }}</div>
                    <div class="suggestion-info">
                      <div class="suggestion-name">{{ emp.full_name }}</div>
                      <div class="suggestion-meta">{{ emp.id_num }} · {{ emp.store_dept }}</div>
                    </div>
                  </div>
                </div>
                <div class="mylogs-hint" v-if="myLogsSearch.length > 0 && myLogsSuggestions.length === 0 && !myLogsSearching">No employees found.</div>
                <div class="mylogs-hint" v-if="myLogsSearch.length === 0">Type your name or ID to view your attendance history.</div>
              </div>

              <!-- Employee found -->
              <template v-if="myLogsEmployee">

                <!-- Employee header -->
                <div class="mylogs-emp-header">
                  <div class="mylogs-emp-avatar">{{ myLogsEmployee.full_name.charAt(0) }}</div>
                  <div class="mylogs-emp-info">
                    <div class="mylogs-emp-name">{{ myLogsEmployee.full_name }}</div>
                    <div class="mylogs-emp-meta">{{ myLogsEmployee.store_dept }}</div>
                  </div>
                  <button class="mylogs-clear-btn" @click="clearMyLogs" title="Search again">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>

                <!-- Stats row — simple pills -->
                <div class="mylogs-stats-row">
                  <div class="ml-stat-pill total">{{ myLogsSummary.total }} <span>Total</span></div>
                  <div class="ml-stat-pill present">{{ myLogsSummary.present }} <span>Present</span></div>
                  <div class="ml-stat-pill late">{{ myLogsSummary.late }} <span>Late</span></div>
                  <div class="ml-stat-pill absent">{{ myLogsSummary.absent }} <span>Absent</span></div>
                </div>

                <!-- Simple date filter -->
                <div class="mylogs-simple-filter">
                  <div class="mylogs-filter-row" style="width:100%;display:flex;gap:6px;align-items:center;">
                    <input type="date" v-model="myLogsFrom" @change="applyMyLogsFilters" class="mylogs-date-input" title="From" />
                    <span class="mylogs-date-sep">→</span>
                    <input type="date" v-model="myLogsTo" @change="applyMyLogsFilters" class="mylogs-date-input" title="To" />
                  </div>
                  <div class="mylogs-filter-row" style="width:100%;display:flex;gap:6px;align-items:center;margin-top:6px;">
                    <select v-model="myLogsStatus" @change="applyMyLogsFilters" class="mylogs-select">
                      <option value="">All Statuses</option>
                      <option value="present">Present</option>
                      <option value="late">Late</option>
                      <option value="absent">Absent</option>
                    </select>
                    <button v-if="myLogsFrom || myLogsTo || myLogsStatus" class="mylogs-clear-filter" @click="clearMyLogsFilters">Clear</button>
                  </div>
                </div>

                <!-- Log list -->
                <div class="mylogs-list">
                  <div v-if="myLogsLoading" class="mylogs-loading">Loading...</div>
                  <div v-else-if="myLogsData.length === 0" class="mylogs-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                    No logs found.
                  </div>
                  <div v-else>
                    <div v-for="log in myLogsData" :key="log.id" class="mylogs-entry">
                      <div class="mylogs-entry-top">
                        <span class="mylogs-date">{{ log.date_label }}</span>
                        <span class="mylogs-status-pill" :class="log.status">{{ log.status }}</span>
                      </div>
                      <div class="mylogs-times">
                        <span class="mlt in">IN {{ log.time_in || '—' }}</span>
                        <span class="mlt out">OUT {{ log.time_out || '—' }}</span>
                      </div>
                      <div class="mylogs-breaks" v-if="log.break1_out || log.break2_out || log.break3_out">
                        <template v-for="n in 3" :key="n">
                          <span v-if="log[`break${n}_out`]" class="mlt-break">
                            B{{ n }}: {{ log[`break${n}_out`] }} – {{ log[`break${n}_in`] || '...' }}
                          </span>
                        </template>
                      </div>
                    </div>
                    <div class="mylogs-pagination" v-if="myLogsTotalPages > 1">
                      <button class="page-btn" @click="myLogsPage--; fetchMyLogs()" :disabled="myLogsPage === 1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                      </button>
                      <span class="page-info">{{ myLogsPage }} / {{ myLogsTotalPages }}</span>
                      <button class="page-btn" @click="myLogsPage++; fetchMyLogs()" :disabled="myLogsPage === myLogsTotalPages">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>

          </div> <!-- end right-col -->

        </div> <!-- end kiosk-top-grid -->

      </div> <!-- end step-section -->
    </main>

    <!-- Scan Modal -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div class="scan-modal-overlay" v-if="scanModal.open" @click.self="closeScanModal">
          <div class="scan-modal">
            <div class="scan-modal-header">
              <div class="scan-modal-title-wrap">
                <div class="scan-modal-action-icon">
                  <svg v-if="scanModal.action === 'time_in'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                  <svg v-else-if="scanModal.action === 'time_out'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z"/></svg>
                </div>
                <div>
                  <div class="scan-modal-title">{{ actionLabel(scanModal.action) }}</div>
                  <div class="scan-modal-sub">Place your finger on the scanner</div>
                </div>
              </div>
              <button class="scan-modal-close" @click="closeScanModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="scan-modal-body">
              <div class="scan-modal-grid">
                <!-- Scanner -->
                <div class="scan-modal-scanner">
                  <div class="conn-bar">
                    <div class="conn-dot" :class="connected ? 'connected' : 'disconnected'"></div>
                    <span>{{ connected ? 'Scanner Connected' : 'Disconnected' }}</span>
                    <button v-if="!connected" class="conn-btn" @click="connectScanner">Connect</button>
                    <button v-else class="conn-btn disconnect" @click="disconnectScanner">Disconnect</button>
                  </div>
                  <div class="fp-preview-wrap">
                    <img :src="previewImg" class="fp-preview" />
                    <div class="fp-overlay" v-if="scanning">
                      <div class="scan-pulse"></div>
                      <div class="scan-text">{{ scanMessage }}</div>
                    </div>
                  </div>
                  <div class="scanner-status" :class="statusClass">{{ statusMessage }}</div>
                  <button class="btn-start-scan" @click="triggerCapture" :disabled="!connected || scanning" :class="{ scanning }">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
                    {{ scanning ? 'Scanning...' : 'Start Scan' }}
                  </button>
                  <div class="modal-log-box" ref="modalLogWrap"><pre>{{ log }}</pre></div>
                </div>
                <!-- Instructions -->
                <div class="scan-modal-instruct">
                  <div class="instruct-fp-icon">
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="fp-anim-svg">
                      <path class="fp-arc fp-arc-1" d="M15 40c0-13.8 11.2-25 25-25s25 11.2 25 25" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                      <path class="fp-arc fp-arc-2" d="M20 40c0-11 9-20 20-20s20 9 20 20" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                      <path class="fp-arc fp-arc-3" d="M26 40c0-7.7 6.3-14 14-14s14 6.3 14 14c0 3-.5 6.2-1.5 9" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                      <path class="fp-arc fp-arc-4" d="M32 40c0-4.4 3.6-8 8-8s8 3.6 8 8c0 4-1 8.5-3 12" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                      <path class="fp-arc fp-arc-5" d="M40 36v4c0 5-1.5 10-4 14" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                      <path class="fp-arc fp-arc-6" d="M10 55c2-5 3-10 3-15" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                      <path class="fp-arc fp-arc-6" d="M70 55c-2-5-3-10-3-15" stroke="#8C1007" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                    </svg>
                  </div>
                  <div class="instruct-heading">Scan Your Fingerprint</div>
                  <div class="instruct-body">Place your index finger flat on the scanner pad and hold still until scanning is complete.</div>
                  <div class="instruct-action-badge" :class="scanModal.action">{{ actionLabel(scanModal.action) }}</div>
                  <div class="instruct-time">{{ currentTime }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Footer -->
    <footer class="kiosk-footer">
      <div class="kiosk-footer-left">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="footer-fp-icon"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
        <span>Powered by <a href="https://hfsecurity.cn/" target="_blank" class="footer-link">HFSecurity</a> Biometric Technology</span>
      </div>
      <div class="kiosk-footer-right">
        © 2026 · Made with <strong>Vue.js</strong> &amp; <strong>Laravel</strong> · <strong>Lance Tinoco</strong>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const darkMode = ref(false)

const allowedActions = ref(null)

function isLocked(action) {
  if (allowedActions.value === null) return false
  return !allowedActions.value.includes(action)
}

const currentTime = ref('')
const currentDate = ref('')
let clockTimer = null
function updateClock() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

const breaks = [
  { action: 'break1_out', label: 'Break 1 Out', cls: 'break-out' },
  { action: 'break1_in',  label: 'Break 1 In',  cls: 'break-in'  },
  { action: 'break2_out', label: 'Break 2 Out', cls: 'break-out' },
  { action: 'break2_in',  label: 'Break 2 In',  cls: 'break-in'  },
  { action: 'break3_out', label: 'Break 3 Out', cls: 'break-out' },
  { action: 'break3_in',  label: 'Break 3 In',  cls: 'break-in'  },
]

function actionLabel(action) {
  return {
    time_in: 'Time In', time_out: 'Time Out',
    break1_out: 'Break 1 Out', break1_in: 'Break 1 In',
    break2_out: 'Break 2 Out', break2_in: 'Break 2 In',
    break3_out: 'Break 3 Out', break3_in: 'Break 3 In',
  }[action] || action
}

const BLANK_IMG     = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAkCAYAAABIdFAMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAHhJREFUeNo8zjsOxCAMBFB/KEAUFFR0Cbng3nQPw68ArZdAlOZppPFIBhH5EAB8b+Tlt9MYQ6i1BuqFaq1CKSVcxZ2Acs6406KUgpt5/LCKuVgz5BDCSb13ZO99ZOdcZGvt4mJjzMVKqcha68iIePB86GAiOv8CDADlIUQBs7MD3wAAAABJRU5ErkJggg=='
const previewImg    = ref(BLANK_IMG)
const connected     = ref(false)
const scanning      = ref(false)
const scanMessage   = ref('')
const statusMessage = ref('Connecting to scanner...')
const statusClass   = ref('idle')
const log           = ref('')
const statusLog     = ref('Scanner ready.\n')
const logWrap       = ref(null)
const modalLogWrap  = ref(null)

const scanModal      = ref({ open: false, action: null })
const terminalOpen   = ref(false)
const step           = ref('select')
const result         = ref(null)
const resetCountdown = ref(3)
const resetProgress  = ref(100)
let resetTimer       = null
let matchQueue       = []
let scannedTemplate  = ''
let pendingMatch     = null

const todayLogs         = ref([])
const logSearch         = ref('')
const logActionFilter   = ref('')
const logPositionFilter = ref('')
const logStoreFilter    = ref('')
const logsListRef       = ref(null)

const filteredLogs = computed(() => {
  let list = todayLogs.value
  if (logSearch.value.trim()) {
    const q = logSearch.value.toLowerCase()
    list = list.filter(e => e.full_name?.toLowerCase().includes(q))
  }
  if (logActionFilter.value)   list = list.filter(e => e.action === logActionFilter.value)
  if (logPositionFilter.value) list = list.filter(e => e.position === logPositionFilter.value)
  if (logStoreFilter.value)    list = list.filter(e => e.store_dept === logStoreFilter.value)
  return list
})

const logsPage    = ref(1)
const logsPerPage = 5
const totalPages  = computed(() => Math.max(1, Math.ceil(filteredLogs.value.length / logsPerPage)))
const paginatedLogs = computed(() => {
  const start = (logsPage.value - 1) * logsPerPage
  return filteredLogs.value.slice(start, start + logsPerPage)
})
watch([logSearch, logActionFilter, logPositionFilter, logStoreFilter], () => { logsPage.value = 1 })

async function fetchTodayLogs() {
  try {
    const res = await axios.get('/kiosk/today-logs')
    todayLogs.value = res.data.logs
  } catch (e) {}
}

function addLog(msg) {
  log.value += `[${new Date().toLocaleTimeString()}] ${msg}\n`
  nextTick(() => {
    if (logWrap.value)      logWrap.value.scrollTop      = logWrap.value.scrollHeight
    if (modalLogWrap.value) modalLogWrap.value.scrollTop = modalLogWrap.value.scrollHeight
  })
}
function addStatus(msg) {
  statusLog.value += `[${new Date().toLocaleTimeString()}] ${msg}\n`
  nextTick(() => { if (logWrap.value) logWrap.value.scrollTop = logWrap.value.scrollHeight })
}

function openScanModal(action) {
  previewImg.value = BLANK_IMG
  scanning.value = false
  matchQueue = []; pendingMatch = null; scannedTemplate = ''
  statusMessage.value = connected.value ? 'Ready — press Start Scan.' : 'Connect scanner first.'
  statusClass.value = connected.value ? 'success' : 'idle'
  scanModal.value = { open: true, action }
  if (connected.value) setTimeout(triggerCapture, 400)
}

function closeScanModal() {
  scanModal.value.open = false
  scanning.value = false
  previewImg.value = BLANK_IMG
  matchQueue = []; pendingMatch = null; scannedTemplate = ''
  try {
    if (window.ws && window.ws.readyState === WebSocket.OPEN)
      window.ws.send('{"cmd":"stop","data1":"","data2":""}')
  } catch (e) {}
}

function showResult(data) {
  scanModal.value.open = false
  result.value = data
  step.value = 'result'

  if (Array.isArray(data.allowed_actions)) {
    allowedActions.value = data.allowed_actions
  }

  if (data.status_type === 'success') {
    addLog(`✓ ${data.full_name} — ${actionLabel(data.action)} at ${data.logged_at}`)
  } else {
    addLog(`✗ ${data.full_name} — ${data.message}`)
  }

  resetCountdown.value = 3
  resetProgress.value  = 100
  let elapsed = 0
  resetTimer = setInterval(() => {
    elapsed += 100
    resetProgress.value  = 100 - (elapsed / 3000) * 100
    resetCountdown.value = Math.ceil((3000 - elapsed) / 1000)
    if (elapsed >= 3000) {
      clearInterval(resetTimer)
      step.value = 'select'
      result.value = null
      scanning.value = false
      previewImg.value = BLANK_IMG
      allowedActions.value = null
    }
  }, 100)
}

function dismissResult() {
  clearInterval(resetTimer)
  step.value = 'select'
  result.value = null
  scanning.value = false
  previewImg.value = BLANK_IMG
  allowedActions.value = null
}

function showScanError(msg) {
  statusMessage.value = msg
  statusClass.value   = 'error'
  scanning.value      = false
  addLog('✗ ' + msg)
  setTimeout(() => {
    if (scanModal.value.open) {
      statusMessage.value = 'Ready — press Start Scan.'
      statusClass.value   = connected.value ? 'success' : 'idle'
    }
  }, 3000)
}

function triggerCapture() {
  if (!connected.value || scanning.value) return
  scanning.value = true
  matchQueue = []; pendingMatch = null; scannedTemplate = ''
  statusMessage.value = 'Place your finger on the scanner...'
  statusClass.value   = 'scanning'
  addLog('Waiting for finger...')
  try { window.ws.send('{"cmd":"capture","data1":"","data2":""}') } catch (e) { addLog('Scanner send error.') }
}

function matchAgainstDB(tpl) {
  scannedTemplate     = tpl
  statusMessage.value = 'Identifying...'
  statusClass.value   = 'scanning'
  addLog('Identifying...')
  axios.get('/kiosk/templates').then(res => {
    matchQueue = res.data.templates
    addLog('Comparing fingerprint...')
    runNextMatch()
  }).catch(() => showScanError('Could not load templates. Try again.'))
}

function runNextMatch() {
  if (!matchQueue.length) { showScanError('Fingerprint not recognized.'); return }
  const entry = matchQueue.shift()
  window.ws.send(JSON.stringify({ cmd: 'setdata', data1: entry.template, data2: '' }))
  window.ws.send(JSON.stringify({ cmd: 'setdata', data1: '', data2: scannedTemplate }))
  window.ws.send(JSON.stringify({ cmd: 'match', data1: '', data2: '' }))
  pendingMatch = entry
}

let hooked = false
function hookProcessJs() {
  if (hooked || !window.ws) return
  hooked = true
  const prev = window.ws.onmessage
  window.ws.onmessage = function (evt) {
    if (prev) prev.call(this, evt)
    try { handleMsg(eval('(' + evt.data + ')')) } catch (e) {}
  }
  window.ws.onclose = function () {
    connected.value = false; scanning.value = false; hooked = false
    statusMessage.value = 'Scanner disconnected.'
    statusClass.value   = 'idle'
    addStatus('Scanner disconnected.')
  }
}

function handleMsg(obj) {
  switch (obj.workmsg) {
    case 2:
      if (scanModal.value.open) {
        scanning.value     = true
        scanMessage.value  = 'Scanning...'
        statusMessage.value = 'Scanning fingerprint...'
        statusClass.value   = 'scanning'
        addLog('Finger detected, hold still...')
      }
      break
    case 3: break
    case 5:
      if (scanModal.value.open) {
        if (obj.retmsg == 1) {
          const tpl = obj.data2 || obj.data1
          if (tpl && tpl !== 'null' && tpl !== '') {
            addLog('Template captured. Matching...')
            matchAgainstDB(tpl)
          } else {
            showScanError('No template. Try again.')
          }
        } else {
          showScanError('Capture failed. Try again.')
        }
        scanning.value = false
      }
      break
    case 7:
      if (obj.image && obj.image !== 'null')
        previewImg.value = 'data:image/png;base64,' + obj.image
      break
    case 8:
      if (scanModal.value.open) { showScanError('Timed out. Try again.'); scanning.value = false }
      break
    case 9: {
      const score = parseInt(obj.retmsg)
      const entry = pendingMatch
      if (!entry) break
      if (score >= 30) {
        pendingMatch = null
        addLog('Match found. Recording attendance...')
        axios.post('/kiosk/log', { employee_id: entry.employee_id, action: scanModal.value.action })
          .then(res => {
            scanning.value = false
            fetchTodayLogs()
            showResult({
              ...res.data,
              action: scanModal.value.action,
            })
          })
          .catch(() => { showScanError('Server error. Try again.'); scanning.value = false })
      } else {
        pendingMatch = null
        runNextMatch()
      }
      break
    }
  }
}

function waitForConnection() {
  addStatus('Connecting to scanner...')
  const check = setInterval(() => {
    if (!window.ws) return
    const state = window.ws.readyState
    if (state === WebSocket.OPEN) {
      clearInterval(check); connected.value = true
      statusMessage.value = 'Scanner connected.'; statusClass.value = 'success'
      addStatus('Scanner connected ✓'); hookProcessJs()
    } else if (state === WebSocket.CONNECTING) {
      clearInterval(check)
      window.ws.addEventListener('open',  () => { connected.value = true; statusClass.value = 'success'; addStatus('Scanner connected ✓'); hookProcessJs() }, { once: true })
      window.ws.addEventListener('error', () => { connected.value = false; statusClass.value = 'error'; addLog('Connection error.') }, { once: true })
    } else {
      clearInterval(check); statusMessage.value = 'Click Connect.'; statusClass.value = 'idle'
      addStatus('Scanner not found. Click Connect.')
    }
  }, 200)
  setTimeout(() => {
    clearInterval(check)
    if (!connected.value) { statusMessage.value = 'Click Connect.'; addStatus('Could not connect. Is the scanner app running?') }
  }, 12000)
}

function connectScanner() {
  hooked = false; addStatus('Connecting to scanner...')
  if (window.$ && $('#host_connect').length) {
    $('#host_connect').trigger('click'); setTimeout(waitForConnection, 300)
  } else {
    const wsUrl = `ws://${window.location.hostname}:21187/fps`
    window.ws = new WebSocket(wsUrl)
    window.ws.onopen    = () => { connected.value = true;  statusClass.value = 'success'; statusMessage.value = 'Connected.'; addStatus('Scanner connected ✓'); hookProcessJs() }
    window.ws.onclose   = () => { connected.value = false; statusClass.value = 'idle';    statusMessage.value = 'Disconnected.'; addStatus('Scanner disconnected.'); hooked = false }
    window.ws.onerror   = () => {                          statusClass.value = 'error';   statusMessage.value = 'Connection failed.'; addStatus('Connection failed.') }
    window.ws.onmessage = (evt) => { try { handleMsg(eval('(' + evt.data + ')')) } catch (e) {} }
  }
}

function disconnectScanner() {
  if (window.$ && $('#host_close').length) $('#host_close').trigger('click')
  else window.ws?.close()
}

// ── My Logs ─────────────────────────────────────────────────────────────────
const myLogsSearch      = ref('')
const myLogsSuggestions = ref([])
const myLogsSearching   = ref(false)
const myLogsEmployee    = ref(null)
const myLogsData        = ref([])
const myLogsSummary     = ref({ total: 0, present: 0, late: 0, absent: 0 })
const myLogsLoading     = ref(false)
const myLogsFrom        = ref('')
const myLogsTo          = ref('')
const myLogsStatus      = ref('')
const myLogsPage        = ref(1)
const myLogsTotalPages  = ref(1)

let myLogsSearchTimer = null
function searchEmployees() {
  myLogsSuggestions.value = []
  clearTimeout(myLogsSearchTimer)
  const q = myLogsSearch.value.trim()
  if (q.length < 2) return
  myLogsSearchTimer = setTimeout(async () => {
    myLogsSearching.value = true
    try {
      const res = await axios.get('/kiosk/employees/search', { params: { q } })
      myLogsSuggestions.value = res.data.employees
    } catch (e) {}
    myLogsSearching.value = false
  }, 300)
}

async function selectEmployee(emp) {
  myLogsEmployee.value    = emp
  myLogsSearch.value      = ''
  myLogsSuggestions.value = []
  myLogsPage.value        = 1
  myLogsFrom.value        = ''
  myLogsTo.value          = ''
  myLogsStatus.value      = ''
  await fetchMyLogs()
}

async function fetchMyLogs() {
  if (!myLogsEmployee.value) return
  myLogsLoading.value = true
  try {
    const res = await axios.get(`/kiosk/employees/${myLogsEmployee.value.id}/logs`, {
      params: {
        from:   myLogsFrom.value   || undefined,
        to:     myLogsTo.value     || undefined,
        status: myLogsStatus.value || undefined,
        page:   myLogsPage.value,
      }
    })
    myLogsData.value       = res.data.logs
    myLogsSummary.value    = res.data.summary
    myLogsTotalPages.value = res.data.last_page
  } catch (e) {}
  myLogsLoading.value = false
}

function applyMyLogsFilters() { myLogsPage.value = 1; fetchMyLogs() }

function clearMyLogsFilters() {
  myLogsFrom.value = ''; myLogsTo.value = ''; myLogsStatus.value = ''
  myLogsPage.value = 1; fetchMyLogs()
}

function clearMyLogs() {
  myLogsEmployee.value = null; myLogsSearch.value = ''; myLogsSuggestions.value = []
  myLogsData.value = []; myLogsSummary.value = { total: 0, present: 0, late: 0, absent: 0 }
  myLogsFrom.value = ''; myLogsTo.value = ''; myLogsStatus.value = ''
}

let logsTimer = null
onMounted(() => {
  updateClock()
  clockTimer = setInterval(updateClock, 1000)
  waitForConnection()
  fetchTodayLogs()
  logsTimer = setInterval(fetchTodayLogs, 30000)
})
onUnmounted(() => {
  clearInterval(clockTimer)
  clearInterval(resetTimer)
  clearInterval(logsTimer)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

.kiosk-shell { height: 100vh; background: #F4F4F5; display: flex; flex-direction: column; font-family: 'Sora', sans-serif; overflow: hidden; }

.kiosk-header { background: #3E0703; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; position: relative; flex-shrink: 0; }
.kiosk-logo img { height: 30px; width: auto; }
.kiosk-brand { font-size: 14px; font-weight: 800; color: #fff; letter-spacing: 2px; text-transform: uppercase; }
.kiosk-sub { font-size: 9px; color: rgba(255,255,255,0.5); letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }
.kiosk-header-center { position: absolute; left: 50%; transform: translateX(-50%); text-align: center; }
.clock-time { font-family: 'JetBrains Mono', monospace; font-size: 22px; font-weight: 700; color: #fff; letter-spacing: 2px; }
.clock-date { font-size: 11px; color: rgba(255,255,255,0.55); margin-top: 2px; text-align: right; }

.kiosk-main { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 16px 20px; overflow-y: auto; min-height: 0; }
.step-section { width: 100%; max-width: 1100px; padding-bottom: 8px; }

/* ── Select Action label ── */
.select-label { font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }

/* ── Top grid: center (actions+breaks+logs) + right (terminal+mylogs) ── */
.kiosk-top-grid { display: grid; grid-template-columns: 1fr 280px; gap: 12px; width: 100%; align-items: stretch; }
.logs-card--hidden { display: none; }

/* ── Center column — actions, breaks, AND today's logs ── */
.center-col { display: flex; flex-direction: column; gap: 10px; }

/* ── Action buttons ── */
.action-outer { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.main-actions-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.action-btn { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 26px 10px; border-radius: 12px; border: 2px solid #f1f5f9; background: #fafafa; cursor: pointer; transition: all 0.18s; font-family: 'Sora', sans-serif; width: 100%; }
.action-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
.action-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.action-icon svg { width: 24px; height: 24px; }
.action-name { font-size: 14px; font-weight: 700; color: #1a1a1a; }
.action-desc { font-size: 11px; color: #9ca3af; }
.action-btn.time-in  .action-icon { background: #dcfce7; color: #16a34a; }
.action-btn.time-in:hover:not(:disabled) { border-color: #16a34a; }
.action-btn.time-out .action-icon { background: #fee2e2; color: #dc2626; }
.action-btn.time-out:hover:not(:disabled) { border-color: #dc2626; }

/* ── Breaks ── */
.breaks-outer { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.breaks-section-label { display: flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.breaks-section-label svg { width: 13px; height: 13px; }
.breaks-flat-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
.breaks-row-divider { display: flex; align-items: center; gap: 8px; margin: 10px 0; }
.breaks-row-divider::before, .breaks-row-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
.breaks-row-divider span { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px; white-space: nowrap; }
.break-btn { display: flex; flex-direction: column; align-items: center; gap: 7px; padding: 14px 6px; border-radius: 11px; border: 1.5px solid #f1f5f9; background: #fafafa; cursor: pointer; transition: all 0.18s; font-family: 'Sora', sans-serif; width: 100%; }
.break-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.07); }
.break-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; }
.break-icon svg { width: 16px; height: 16px; }
.break-name { font-size: 10px; font-weight: 700; color: #1a1a1a; text-align: center; }
.break-btn.break-out .break-icon { background: #fef9c3; color: #ca8a04; }
.break-btn.break-out:hover:not(:disabled) { border-color: #ca8a04; background: #fefce8; }
.break-btn.break-in  .break-icon { background: #dbeafe; color: #2563eb; }
.break-btn.break-in:hover:not(:disabled)  { border-color: #2563eb; background: #eff6ff; }

.action-btn.locked, .break-btn.locked { opacity: 0.28; cursor: not-allowed; filter: grayscale(0.5); }
.action-btn.locked:hover, .break-btn.locked:hover { transform: none !important; box-shadow: none !important; }

/* ── Today's Logs — now inside center-col, matches action card width ── */
.logs-bottom { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
.logs-bottom-header { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
.logs-bottom-filters { display: flex; align-items: center; gap: 6px; margin-left: auto; flex-wrap: wrap; }
.logs-search-wrap--inline { position: relative; }
.logs-search-wrap--inline svg { position: absolute; left: 8px; top: 50%; transform: translateY(-50%); width: 12px; height: 12px; color: #9ca3af; }
.logs-search-wrap--inline .logs-search { width: 160px; padding: 5px 6px 5px 24px; }
.logs-bottom-list { overflow: hidden; width: 100%; }
.logs-inline-entries { display: grid; grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); }
.logs-empty-inline { text-align: center; color: #9ca3af; font-size: 12px; padding: 20px 24px; }
.logs-bottom-pagination { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 7px 14px; border-top: 1px solid #f1f5f9; }

/* ── Shared log card styles ── */
.logs-card-title { display: flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }
.logs-card-title svg { width: 13px; height: 13px; color: #8C1007; }
.logs-count { font-size: 11px; font-weight: 700; background: #fde8e9; color: #8C1007; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
.logs-filter-select { background: #f9fafb; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 22px 5px 8px; font-size: 10px; font-family: inherit; color: #1a1a1a; outline: none; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 5px center; background-size: 11px; background-color: #f9fafb; }
.logs-filter-select:focus { border-color: #8C1007; }
.logs-search { background: #f9fafb; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 11px; font-family: inherit; color: #1a1a1a; outline: none; }
.logs-search:focus { border-color: #8C1007; }
.log-entry { display: flex; flex-direction: column; gap: 4px; padding: 10px 14px; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; min-width: 0; transition: background 0.15s; }
.log-entry:hover { background: #fafafa; }
.log-entry:last-child { border-right: none; }
.log-avatar { width: 28px; height: 28px; border-radius: 50%; background: #3E0703; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.log-info { flex: 1; min-width: 0; }
.log-name { font-size: 11px; font-weight: 600; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.log-meta { font-size: 9px; color: #6b7280; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.log-time { font-size: 9px; color: #9ca3af; font-family: 'JetBrains Mono', monospace; margin-top: 1px; }
.log-action-pill { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 10px; white-space: nowrap; align-self: flex-start; }
.log-action-pill.time_in    { background: #dcfce7; color: #16a34a; }
.log-action-pill.time_out   { background: #fee2e2; color: #dc2626; }
.log-action-pill.break1_out,.log-action-pill.break2_out,.log-action-pill.break3_out { background: #fef9c3; color: #ca8a04; }
.log-action-pill.break1_in,.log-action-pill.break2_in,.log-action-pill.break3_in   { background: #dbeafe; color: #2563eb; }
.page-btn { width: 24px; height: 24px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.page-btn:hover:not(:disabled) { background: #fde8e9; border-color: #8C1007; }
.page-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.page-btn svg { width: 12px; height: 12px; color: #374151; }
.page-info { font-size: 11px; font-weight: 600; color: #6b7280; font-family: 'JetBrains Mono', monospace; }

/* ── Right column ── */
.right-col { display: flex; flex-direction: column; gap: 10px; overflow: visible; align-self: stretch; }

/* ── Terminal mini card ── */
.terminal-mini { background: #0f0f0f; border-radius: 14px; overflow: hidden; border: 1px solid #1e1e1e; flex-shrink: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; max-height: 200px; }
.terminal-mini-bar { display: flex; flex-direction: column; gap: 5px; padding: 8px 12px; background: #1a1a1a; border-bottom: 1px solid #2a2a2a; flex-shrink: 0; }
.t-bar-top { display: flex; align-items: center; gap: 5px; }
.t-bar-bottom { display: flex; align-items: center; justify-content: space-between; }
.t-bar-actions { display: flex; align-items: center; gap: 6px; }
.t-title { font-size: 10px; color: #555; font-family: 'JetBrains Mono', monospace; margin-left: 2px; }
.t-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.t-dot.red { background: #ff5f57; } .t-dot.yellow { background: #febc2e; } .t-dot.green { background: #28c840; }
.t-conn { display: flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 600; font-family: 'JetBrains Mono', monospace; }
.t-conn.online { color: #28c840; } .t-conn.offline { color: #f87171; }
.t-conn-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.t-conn.online .t-conn-dot { background: #28c840; animation: blink 2s infinite; }
.t-conn.offline .t-conn-dot { background: #f87171; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
.t-toggle { position: relative; width: 36px; height: 20px; border-radius: 20px; border: none; cursor: pointer; transition: background 0.25s ease; background: #dc2626; padding: 0; flex-shrink: 0; }
.t-toggle.active { background: #2563eb; }
.t-toggle-thumb { position: absolute; top: 3px; left: 3px; width: 14px; height: 14px; border-radius: 50%; background: #fff; transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); display: block; box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
.t-toggle.active .t-toggle-thumb { transform: translateX(16px); }
.t-clear { background: none; border: none; color: #555; font-size: 10px; cursor: pointer; font-family: inherit; }
.t-clear:hover { color: #fff; }
.terminal-mini-body { padding: 10px 12px; height: 110px; overflow-y: auto; flex-shrink: 0; }
.terminal-mini-body pre { margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 9px; color: #4ade80; line-height: 1.6; white-space: pre-wrap; }

/* ── Scan Modal ── */
.scan-modal-overlay { position: fixed; inset: 0; background: rgba(10,2,4,0.78); display: flex; align-items: center; justify-content: center; z-index: 600; backdrop-filter: blur(6px); padding: 24px; }
.scan-modal { background: #f8fafc; border-radius: 20px; width: 100%; max-width: 820px; box-shadow: 0 30px 80px rgba(0,0,0,0.45); overflow: hidden; }
.scan-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 28px; background: #3E0703; }
.scan-modal-title-wrap { display: flex; align-items: center; gap: 14px; }
.scan-modal-action-icon { width: 44px; height: 44px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.scan-modal-action-icon svg { width: 22px; height: 22px; stroke: #fff; }
.scan-modal-title { font-size: 18px; font-weight: 700; color: #fff; }
.scan-modal-sub { font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 2px; }
.scan-modal-close { width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.25); background: none; color: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.18s; flex-shrink: 0; }
.scan-modal-close:hover { background: rgba(255,255,255,0.15); color: #fff; }
.scan-modal-close svg { width: 14px; height: 14px; }
.scan-modal-body { padding: 20px 28px 28px; }
.scan-modal-grid { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: stretch; }
.scan-modal-scanner { display: flex; flex-direction: column; gap: 10px; }
.conn-bar { display: flex; align-items: center; gap: 8px; font-size: 11px; padding: 7px 12px; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; }
.conn-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.conn-dot.connected { background: #16a34a; animation: blink 2s infinite; }
.conn-dot.disconnected { background: #9ca3af; }
.conn-bar span { flex: 1; color: #1a1a1a; font-weight: 500; }
.conn-btn { font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 5px; border: 1px solid #561C24; background: #8C1007; color: #fff; cursor: pointer; font-family: inherit; }
.conn-btn.disconnect { border-color: #dc2626; background: #fee2e2; color: #dc2626; }
.fp-preview-wrap { position: relative; background: #1a1a2e; border: 1px solid #2d2d44; border-radius: 10px; overflow: hidden; aspect-ratio: 3/2; display: flex; align-items: center; justify-content: center; }
.fp-preview { width: 100%; height: 100%; object-fit: contain; image-rendering: pixelated; }
.fp-overlay { position: absolute; inset: 0; background: rgba(13,3,5,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; }
.scan-pulse { width: 44px; height: 44px; border-radius: 50%; border: 2px solid #fff; animation: pulse 1.4s ease-in-out infinite; }
@keyframes pulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.3);opacity:0.2} }
.scan-text { font-size: 12px; color: #E8D8C4; font-weight: 600; }
.scanner-status { font-size: 11px; font-weight: 500; padding: 7px 10px; border-radius: 7px; text-align: center; }
.scanner-status.idle     { background: #f3f4f6; color: #6b7280; }
.scanner-status.success  { background: #dcfce7; color: #16a34a; }
.scanner-status.scanning { background: #fef9c3; color: #ca8a04; }
.scanner-status.error    { background: #fee2e2; color: #dc2626; }
.btn-start-scan { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; background: #8C1007; border: 1px solid #6D2932; border-radius: 8px; color: #fff; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.18s; }
.btn-start-scan svg { width: 14px; height: 14px; }
.btn-start-scan:hover:not(:disabled) { background: #6D2932; }
.btn-start-scan.scanning { background: #ca8a04; border-color: #a16207; }
.btn-start-scan:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-log-box { background: #0f0f0f; border-radius: 6px; padding: 8px 10px; height: 90px; overflow-y: auto; }
.modal-log-box pre { margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 9px; color: #4ade80; line-height: 1.6; white-space: pre-wrap; }
.scan-modal-instruct { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 36px 24px; text-align: center; min-height: 340px; }
.instruct-fp-icon { width: 90px; height: 90px; border-radius: 50%; background: #fde8e9; display: flex; align-items: center; justify-content: center; }
.instruct-heading { font-size: 18px; font-weight: 700; color: #1a1a1a; }
.instruct-body { font-size: 12px; color: #64748b; line-height: 1.6; max-width: 240px; }
.instruct-action-badge { font-size: 14px; font-weight: 700; padding: 8px 20px; border-radius: 20px; }
.instruct-action-badge.time_in    { background: #dcfce7; color: #16a34a; }
.instruct-action-badge.time_out   { background: #fee2e2; color: #dc2626; }
.instruct-action-badge.break1_out,.instruct-action-badge.break2_out,.instruct-action-badge.break3_out { background: #fef9c3; color: #ca8a04; }
.instruct-action-badge.break1_in,.instruct-action-badge.break2_in,.instruct-action-badge.break3_in   { background: #dbeafe; color: #2563eb; }
.instruct-time { font-family: 'JetBrains Mono', monospace; font-size: 28px; font-weight: 700; color: #3E0703; letter-spacing: 1px; }

/* ── Result overlay ── */
.result-overlay { position: fixed; inset: 0; background: rgba(8,2,4,0.92); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; z-index: 700; backdrop-filter: blur(10px); }
.result-card { position: relative; display: flex; flex-direction: column; align-items: center; gap: 12px; background: #fff; border-radius: 28px; padding: 0 88px 48px; box-shadow: 0 24px 80px rgba(0,0,0,0.5); text-align: center; min-width: 440px; overflow: hidden; animation: card-pop 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes card-pop { 0%{opacity:0;transform:scale(0.88) translateY(16px)} 100%{opacity:1;transform:scale(1) translateY(0)} }
.result-top-strip { width: 100%; height: 90px; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 0; margin-bottom: 8px; flex-shrink: 0; }
.result-top-strip.success { background: linear-gradient(135deg, #16a34a 0%, #4ade80 100%); }
.result-top-strip.error   { background: linear-gradient(135deg, #b91c1c 0%, #f87171 100%); }
.result-strip-icon { width: 54px; height: 54px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; transform: translateY(50%); box-shadow: 0 2px 10px rgba(0,0,0,0.12); flex-shrink: 0; }
.result-strip-icon svg { width: 22px; height: 22px; stroke-width: 2.5; }
.result-strip-icon.success svg { stroke: #16a34a; }
.result-strip-icon.error   svg { stroke: #dc2626; }
.result-avatar-wrap { position: relative; display: flex; align-items: center; justify-content: center; margin-top: 22px; }
.result-avatar-pulse { position: absolute; width: 88px; height: 88px; border-radius: 50%; border: 2px solid #16a34a; animation: avatar-pulse 1.6s ease-out infinite; opacity: 0; }
@keyframes avatar-pulse { 0%{transform:scale(0.85);opacity:0.6} 100%{transform:scale(1.4);opacity:0} }
.result-avatar { width: 78px; height: 78px; border-radius: 50%; background: #6D2932; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; position: relative; z-index: 1; }
.result-name { font-size: 24px; font-weight: 700; color: #1a1a1a; margin-top: 2px; }
.result-meta { font-size: 11px; color: #9ca3af; }
.result-badges-row { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.result-status-badge { font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
.result-status-badge.present { background: #dcfce7; color: #16a34a; }
.result-status-badge.late    { background: #fef9c3; color: #ca8a04; }
.result-status-badge.absent  { background: #fee2e2; color: #dc2626; }
.result-action-pill { padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.result-action-pill.time_in    { background: #dcfce7; color: #16a34a; }
.result-action-pill.time_out   { background: #fee2e2; color: #dc2626; }
.result-action-pill.break1_out,.result-action-pill.break2_out,.result-action-pill.break3_out { background: #fef9c3; color: #ca8a04; }
.result-action-pill.break1_in,.result-action-pill.break2_in,.result-action-pill.break3_in   { background: #dbeafe; color: #2563eb; }
.result-time { font-family: 'JetBrains Mono', monospace; font-size: 30px; font-weight: 700; color: #1a1a1a; letter-spacing: 2px; line-height: 1; margin: 4px 0; }
.result-shift { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #6b7280; background: #f9f0f1; border: 1px solid #e8d0d2; padding: 5px 12px; border-radius: 8px; }
.result-shift svg { width: 12px; height: 12px; color: #8C1007; }
.result-error-msg { font-size: 13px; color: #dc2626; font-weight: 600; max-width: 260px; line-height: 1.5; background: #fee2e2; padding: 10px 18px; border-radius: 10px; }
.reset-bar { width: 340px; height: 2px; background: rgba(255,255,255,0.12); border-radius: 2px; overflow: hidden; }
.reset-progress { height: 100%; background: rgba(255,255,255,0.5); border-radius: 2px; transition: width 0.1s linear; }
.reset-hint { font-size: 11px; color: rgba(255,255,255,0.3); letter-spacing: 0.5px; }
.result-dismiss-btn { padding: 7px 22px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.45); font-size: 11px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.18s; letter-spacing: 0.5px; }
.result-dismiss-btn:hover { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.8); border-color: rgba(255,255,255,0.3); }

/* ── Footer ── */
.kiosk-footer { background: #F4F4F5; border-top: 1px solid #e8e8ea; padding: 10px 40px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.kiosk-footer-left { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #9ca3af; }
.footer-fp-icon { width: 14px; height: 14px; color: #9ca3af; }
.kiosk-footer-right { font-size: 11px; color: #9ca3af; }
.kiosk-footer-right strong { color: #6b7280; font-weight: 600; }
.footer-link { color: #8C1007; font-weight: 600; text-decoration: none; }
.footer-link:hover { text-decoration: underline; }

/* ── Transitions ── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-fade-enter-from { opacity: 0; transform: scale(0.96) translateY(8px); }
.modal-fade-leave-to   { opacity: 0; transform: scale(1.02); }
.result-fade-enter-active { transition: opacity 0.25s ease; }
.result-fade-leave-active { transition: opacity 0.2s ease; }
.result-fade-enter-from, .result-fade-leave-to { opacity: 0; }
.log-slide-enter-active { animation: log-fade-in 0.25s ease both; }
.log-slide-leave-active { animation: log-fade-out 0.2s ease both; position: absolute; }
.log-slide-move { transition: transform 0.3s ease; }
@keyframes log-fade-in  { 0%{opacity:0;transform:scale(0.95) translateY(6px)} 100%{opacity:1;transform:scale(1) translateY(0)} }
@keyframes log-fade-out { 0%{opacity:1;transform:scale(1)}                    100%{opacity:0;transform:scale(0.95)} }

/* ── Fingerprint animation ── */
.fp-anim-svg { width: 56px; height: 56px; }
.fp-arc { stroke-dasharray: 60; stroke-dashoffset: 60; animation: fp-draw 2.4s ease forwards infinite; }
.fp-arc-1 { stroke-dasharray: 80; stroke-dashoffset: 80; animation-delay: 0s; }
.fp-arc-2 { stroke-dasharray: 65; stroke-dashoffset: 65; animation-delay: 0.2s; }
.fp-arc-3 { stroke-dasharray: 60; stroke-dashoffset: 60; animation-delay: 0.4s; }
.fp-arc-4 { stroke-dasharray: 50; stroke-dashoffset: 50; animation-delay: 0.6s; }
.fp-arc-5 { stroke-dasharray: 20; stroke-dashoffset: 20; animation-delay: 0.8s; }
.fp-arc-6 { stroke-dasharray: 20; stroke-dashoffset: 20; animation-delay: 1s; }
@keyframes fp-draw {
  0%   { stroke-dashoffset: var(--dash, 60); opacity: 0; }
  10%  { opacity: 1; }
  50%  { stroke-dashoffset: 0; opacity: 1; }
  75%  { stroke-dashoffset: 0; opacity: 0.3; }
  100% { stroke-dashoffset: 0; opacity: 0; }
}

/* ── Dark toggle ── */
.dark-toggle { width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
.dark-toggle:hover { background: rgba(255,255,255,0.18); color: #fff; }
.dark-toggle svg { width: 15px; height: 15px; }

/* ── Dark mode ── */
.kiosk-shell.dark { background: #13151a; }
.kiosk-shell.dark .kiosk-main { background: #13151a; }
.kiosk-shell.dark .kiosk-header { background: #1c1f26; border-bottom: 1px solid #2a2d35; }
.kiosk-shell.dark .action-outer, .kiosk-shell.dark .breaks-outer { background: #1c1f26; border-color: #2a2d35; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
.kiosk-shell.dark .action-btn { background: #23262f; border-color: #2e323c; }
.kiosk-shell.dark .action-btn:hover:not(:disabled) { background: #2a2d38; box-shadow: 0 4px 14px rgba(0,0,0,0.3); }
.kiosk-shell.dark .action-name { color: #e8eaf0; }
.kiosk-shell.dark .action-desc { color: #565b6b; }
.kiosk-shell.dark .action-btn.time-in  .action-icon { background: #0f2e1a; color: #4ade80; }
.kiosk-shell.dark .action-btn.time-out .action-icon { background: #2e1010; color: #f87171; }
.kiosk-shell.dark .break-btn { background: #23262f; border-color: #2e323c; }
.kiosk-shell.dark .break-btn:hover:not(:disabled) { background: #2a2d38; }
.kiosk-shell.dark .break-name { color: #e8eaf0; }
.kiosk-shell.dark .break-btn.break-out .break-icon { background: #2e2510; color: #fbbf24; }
.kiosk-shell.dark .break-btn.break-in  .break-icon { background: #101828; color: #60a5fa; }
.kiosk-shell.dark .select-label { color: #565b6b; }
.kiosk-shell.dark .logs-bottom { background: #1c1f26; border-color: #2a2d35; }
.kiosk-shell.dark .logs-bottom-header { border-color: #2a2d35; }
.kiosk-shell.dark .log-entry { border-color: #2a2d35; }
.kiosk-shell.dark .log-entry:hover { background: #23262f; }
.kiosk-shell.dark .log-name { color: #e8eaf0; }
.kiosk-shell.dark .log-meta { color: #565b6b; }
.kiosk-shell.dark .log-time { color: #464b5a; }
.kiosk-shell.dark .log-avatar { background: #5c1515; }
.kiosk-shell.dark .logs-bottom-pagination { border-color: #2a2d35; }
.kiosk-shell.dark .logs-filter-select { background-color: #23262f; border-color: #2e323c; color: #e8eaf0; }
.kiosk-shell.dark .logs-search { background: #23262f; border-color: #2e323c; color: #e8eaf0; }
.kiosk-shell.dark .logs-card-title { color: #9da3b4; }
.kiosk-shell.dark .logs-count { background: #2e1010; color: #f87171; }
.kiosk-shell.dark .breaks-section-label { color: #565b6b; }
.kiosk-shell.dark .breaks-row-divider::before, .kiosk-shell.dark .breaks-row-divider::after { background: #2a2d35; }
.kiosk-shell.dark .breaks-row-divider span { color: #464b5a; }
.kiosk-shell.dark .kiosk-footer { background: #13151a; border-color: #2a2d35; }
.kiosk-shell.dark .kiosk-footer-left, .kiosk-shell.dark .kiosk-footer-right { color: #464b5a; }
.kiosk-shell.dark .kiosk-footer-right strong { color: #6b7280; }
.kiosk-shell.dark .footer-fp-icon { color: #464b5a; }
.kiosk-shell.dark .footer-link { color: #c0392b; }
.kiosk-shell.dark .page-btn { background: #23262f; border-color: #2e323c; }
.kiosk-shell.dark .page-btn:hover:not(:disabled) { background: #2e1010; border-color: #8C1007; }
.kiosk-shell.dark .page-btn svg { color: #9da3b4; }
.kiosk-shell.dark .page-info { color: #565b6b; }

/* ── My Logs Panel ── */
.mylogs-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; display: flex; flex-direction: column; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: visible; position: relative; flex: 1; min-height: 0; }
.mylogs-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.mylogs-title { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }
.mylogs-title svg { width: 14px; height: 14px; color: #8C1007; }
.mylogs-search-wrap { padding: 12px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.mylogs-search-input-wrap { position: relative; }
.mylogs-search-input-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: #9ca3af; }
.mylogs-search { width: 100%; background: #f9fafb; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px 8px 30px; font-size: 12px; font-family: inherit; color: #1a1a1a; outline: none; }
.mylogs-search:focus { border-color: #8C1007; background: #fff; }
.mylogs-suggestions { display: flex; flex-direction: column; gap: 2px; }
.mylogs-suggestion { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; border: 1px solid #f1f5f9; cursor: pointer; transition: all 0.15s; }
.mylogs-suggestion:hover { background: #fde8e9; border-color: #e8d0d2; }
.suggestion-avatar { width: 30px; height: 30px; border-radius: 50%; background: #3E0703; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.suggestion-name { font-size: 12px; font-weight: 600; color: #1a1a1a; }
.suggestion-meta { font-size: 10px; color: #6b7280; font-family: 'JetBrains Mono', monospace; margin-top: 1px; }
.mylogs-hint { font-size: 11px; color: #9ca3af; text-align: center; padding: 20px 10px; }
.mylogs-emp-header { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.mylogs-emp-avatar { width: 36px; height: 36px; border-radius: 50%; background: #8C1007; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; flex-shrink: 0; }
.mylogs-emp-info { flex: 1; min-width: 0; }
.mylogs-emp-name { font-size: 13px; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mylogs-emp-meta { font-size: 10px; color: #6b7280; margin-top: 1px; }
.mylogs-clear-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #9ca3af; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.15s; }
.mylogs-clear-btn:hover { border-color: #dc2626; background: #fee2e2; color: #dc2626; }
.mylogs-clear-btn svg { width: 12px; height: 12px; }
.mylogs-summary { display: flex; align-items: center; justify-content: space-around; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; background: #fafafa; flex-shrink: 0; }
.mylogs-stat { text-align: center; }
.mylogs-stat-num { font-size: 18px; font-weight: 800; color: #1a1a1a; line-height: 1; }
.mylogs-stat-num.green  { color: #16a34a; }
.mylogs-stat-num.yellow { color: #ca8a04; }
.mylogs-stat-num.red    { color: #dc2626; }
.mylogs-stat-label { font-size: 9px; color: #64748b; margin-top: 2px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
.mylogs-stat-div { width: 1px; height: 24px; background: #e2e8f0; }
.mylogs-filters { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
.mylogs-filter-row { display: flex; align-items: center; gap: 6px; }
.mylogs-date-input { flex: 1; background: #f9fafb; border: 1px solid #e2e8f0; border-radius: 7px; padding: 5px 7px; font-size: 11px; font-family: 'JetBrains Mono', monospace; color: #1a1a1a; outline: none; min-width: 0; }
.mylogs-date-input:focus { border-color: #8C1007; }
.mylogs-date-sep { font-size: 11px; color: #9ca3af; font-weight: 600; flex-shrink: 0; }
.mylogs-select { flex: 1; background: #f9fafb; border: 1px solid #e2e8f0; border-radius: 7px; padding: 5px 24px 5px 7px; font-size: 11px; font-family: inherit; color: #1a1a1a; outline: none; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 6px center; background-size: 11px; background-color: #f9fafb; }
.mylogs-select:focus { border-color: #8C1007; }
.mylogs-clear-filter { padding: 5px 10px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 7px; color: #dc2626; font-size: 10px; font-weight: 600; font-family: inherit; cursor: pointer; white-space: nowrap; flex-shrink: 0; }
.mylogs-clear-filter:hover { background: #fecaca; }
.mylogs-list { overflow-y: auto; padding: 6px 0; flex: 1; min-height: 0; max-height: 240px; }
.mylogs-loading { text-align: center; color: #9ca3af; font-size: 12px; padding: 32px 0; }
.mylogs-empty { text-align: center; color: #9ca3af; font-size: 11px; padding: 32px 16px; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.mylogs-empty svg { width: 28px; height: 28px; color: #d1d5db; }
.mylogs-entry { padding: 10px 14px; border-bottom: 1px solid #f9fafb; transition: background 0.15s; }
.mylogs-entry:hover { background: #fdf5f5; }
.mylogs-entry:last-child { border-bottom: none; }
.mylogs-entry-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
.mylogs-date { font-size: 11px; font-weight: 600; color: #374151; }
.mylogs-status-pill { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.4px; }
.mylogs-status-pill.present { background: #dcfce7; color: #16a34a; }
.mylogs-status-pill.late    { background: #fef9c3; color: #ca8a04; }
.mylogs-status-pill.absent  { background: #fee2e2; color: #dc2626; }
.mylogs-times { display: flex; gap: 6px; flex-wrap: wrap; }
.mylogs-breaks { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
.mlt { font-family: 'JetBrains Mono', monospace; font-size: 10px; padding: 2px 6px; border-radius: 4px; }
.mlt.in  { background: #dcfce7; color: #16a34a; }
.mlt.out { background: #fee2e2; color: #dc2626; }
.mlt-break { font-family: 'JetBrains Mono', monospace; font-size: 9px; padding: 2px 6px; border-radius: 4px; background: #fef9c3; color: #ca8a04; }
.mylogs-pagination { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 14px; border-top: 1px solid #f1f5f9; }

/* dark my logs */
.kiosk-shell.dark .mylogs-card { background: #1e2128; border-color: #2e323c; border-radius: 16px; }
.kiosk-shell.dark .mylogs-header { border-color: #2e323c; }
.kiosk-shell.dark .mylogs-title { color: #d1d5db; }
.kiosk-shell.dark .mylogs-search { background: #23262f; border-color: #2e323c; color: #e5e7eb; }
.kiosk-shell.dark .mylogs-search:focus { background: #1e2128; }
.kiosk-shell.dark .mylogs-suggestion { border-color: #2e323c; background: #23262f; }
.kiosk-shell.dark .mylogs-suggestion:hover { background: #3E0703; border-color: #6D2932; }
.kiosk-shell.dark .suggestion-name { color: #e5e7eb; }
.kiosk-shell.dark .mylogs-emp-header { border-color: #2e323c; }
.kiosk-shell.dark .mylogs-emp-name { color: #e5e7eb; }
.kiosk-shell.dark .mylogs-clear-btn { background: #23262f; border-color: #2e323c; }
.kiosk-shell.dark .mylogs-summary { background: #23262f; border-color: #2e323c; }
.kiosk-shell.dark .mylogs-stat-num { color: #e5e7eb; }
.kiosk-shell.dark .mylogs-stat-div { background: #2e323c; }
.kiosk-shell.dark .mylogs-filters { border-color: #2e323c; }
.kiosk-shell.dark .mylogs-date-input { background: #23262f; border-color: #2e323c; color: #e5e7eb; }
.kiosk-shell.dark .mylogs-select { background-color: #23262f; border-color: #2e323c; color: #e5e7eb; }
.kiosk-shell.dark .mylogs-entry { border-color: #23262f; }
.kiosk-shell.dark .mylogs-entry:hover { background: rgba(140,16,7,0.08); }
.kiosk-shell.dark .mylogs-date { color: #d1d5db; }
/* ── Simplified My Logs ── */
.mylogs-stats-row { display: flex; gap: 4px; padding: 10px 12px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.ml-stat-pill { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 1px; padding: 6px 4px; border-radius: 8px; font-size: 15px; font-weight: 800; line-height: 1; }
.ml-stat-pill span { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; opacity: 0.7; }
.ml-stat-pill.total   { background: #f3f4f6; color: #374151; }
.ml-stat-pill.present { background: #dcfce7; color: #16a34a; }
.ml-stat-pill.late    { background: #fef9c3; color: #ca8a04; }
.ml-stat-pill.absent  { background: #fee2e2; color: #dc2626; }
.mylogs-simple-filter { display: flex; flex-direction: column; gap: 6px; padding: 8px 12px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.mylogs-simple-filter .mylogs-select { flex: 1; }
.mylogs-simple-filter .mylogs-clear-filter { padding: 4px 8px; font-size: 11px; }
.kiosk-shell.dark .ml-stat-pill.total   { background: #23262f; color: #9da3b4; }
.kiosk-shell.dark .ml-stat-pill.present { background: #0f2e1a; color: #4ade80; }
.kiosk-shell.dark .ml-stat-pill.late    { background: #2e2510; color: #fbbf24; }
.kiosk-shell.dark .ml-stat-pill.absent  { background: #2e1010; color: #f87171; }
.kiosk-shell.dark .mylogs-simple-filter { border-color: #2e323c; }
</style>