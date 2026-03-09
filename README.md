# STORE TIME IN/OUT + Attendance Management System

A biometric attendance system for retail store staff built with Laravel, Inertia.js, and Vue 3. Employees clock in and out using an HFSecurity HF4000 fingerprint scanner at a dedicated kiosk terminal. Admins manage employees, monitor attendance, and export logs through a separate admin panel.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Vue 3 (Composition API) + Inertia.js |
| Styling | Scoped CSS (custom, no Tailwind) |
| Fonts | Sora (UI), JetBrains Mono (timestamps/codes) |
| Auth | Laravel Breeze (session-based) |
| Database | MySQL |
| Scanner | HFSecurity HF4000 via WebSocket (ws://127.0.0.1:21187/fps) |

---

## Project Structure

```
app/
  Http/Controllers/
    Admin/
      DashboardController.php       -- Admin dashboard stats and activity feed
      EmployeeController.php        -- Employee CRUD + fingerprint enrollment endpoints
      EmployeeLogsController.php    -- Per-employee attendance history + CSV export
      LogController.php             -- Daily attendance log viewer (all employees)
    KioskController.php             -- Public kiosk: fingerprint matching, time logging

resources/js/
  Layouts/
    AdminLayout.vue                 -- Sidebar + topbar shell for all admin pages
  Pages/
    Admin/
      Employees/
        Index.vue                   -- Employee list, create/edit modal, enroll modal
        EmployeeLogs.vue            -- Per-employee attendance history page
      Logs/
        Index.vue                   -- Daily attendance log table
      Dashboard.vue                 -- Admin dashboard
    Auth/
      (Laravel Breeze auth pages — login, register, etc.)
    Kiosk/
      Index.vue                     -- Public kiosk terminal UI

public/
  js/
    process.js                      -- HFSecurity HF4000 scanner SDK (provided by manufacturer)
    jquery.min.js                   -- Required dependency of process.js

routes/
  web.php                           -- All application routes

database/
  migrations/
    create_employees_table.php      -- employees table schema
    migration_add_position.php      -- adds position column to employees
  seeders/
    DatabaseSeeder.php              -- Creates default admin user
```

---

## Database Schema

### `employees`

| Column | Type | Notes |
|---|---|---|
| id | bigint | Primary key |
| id_num | varchar(50) | Unique employee ID e.g. EMP-001 |
| full_name | varchar(255) | Format: Last, First Middle |
| position | varchar(255) | Store Head, Store Manager, Cashier, Sales Personnel |
| store_dept | varchar(255) | Branch name e.g. SM MOA |
| shift_from | time | Stored as HH:MM:SS in 24hr |
| shift_to | time | Stored as HH:MM:SS in 24hr |
| fingerprint_1 | longtext (nullable) | Base64 HF4000 template — left index finger |
| fingerprint_2 | longtext (nullable) | Base64 HF4000 template — right index finger |
| created_at | timestamp | |
| updated_at | timestamp | |

### `attendance_logs`

| Column | Type | Notes |
|---|---|---|
| id | bigint | Primary key |
| id_num | varchar | References employee id_num |
| date_today | date | The working date |
| time_in | timestamp (nullable) | Clock-in time |
| time_out | timestamp (nullable) | Clock-out time |
| break1_out | timestamp (nullable) | Break 1 start |
| break1_in | timestamp (nullable) | Break 1 end |
| break2_out | timestamp (nullable) | Break 2 start (Lunch) |
| break2_in | timestamp (nullable) | Break 2 end |
| break3_out | timestamp (nullable) | Break 3 start |
| break3_in | timestamp (nullable) | Break 3 end |
| status | varchar | present, late, absent |
| worked_hours | varchar (nullable) | Computed on read, not stored |
| overtime_minutes | int (nullable) | |
| next_action | varchar (nullable) | Computed property from model |

> All timestamp columns are stored in UTC. The app timezone is set in `config/app.php` and applied on read via `->setTimezone(config('app.timezone'))`.

---

## Models

### `Employee`

Key computed properties:

- `enrollment_status` — returns `complete` (both fingerprints), `partial` (one fingerprint), or `none`
- `todayLog()` — `hasOne` relationship to today's AttendanceLog

### `AttendanceLog`

Key computed properties:

- `next_action` — determines what action the employee should take next based on the current state of their log (time_in, breaks, time_out)
- Relationships: `belongsTo Employee` via `id_num`

---

## Routes

### Public (no auth)

| Method | URI | Name | Description |
|---|---|---|---|
| GET | /kiosk | kiosk.index | Kiosk terminal UI |
| GET | /kiosk/templates | kiosk.templates | Returns all employee fingerprint templates for client-side matching |
| GET | /kiosk/today-logs | kiosk.today-logs | Returns today's logs for the kiosk sidebar |
| POST | /kiosk/log | kiosk.log | Records a punch action (time_in, time_out, break1_out, etc.) |

### Admin (auth + verified middleware)

| Method | URI | Name | Description |
|---|---|---|---|
| GET | /admin | admin.dashboard | Dashboard |
| GET | /admin/employees | admin.employees.index | Employee list |
| POST | /admin/employees | admin.employees.store | Create employee |
| GET | /admin/employees/{id}/edit | admin.employees.edit | Edit form |
| PUT | /admin/employees/{id} | admin.employees.update | Update employee |
| DELETE | /admin/employees/{id} | admin.employees.destroy | Delete employee |
| GET | /admin/employees/{id}/enroll | admin.employees.enroll | Enrollment UI |
| GET | /admin/employees/{id}/logs | admin.employees.logs | Employee attendance history |
| GET | /admin/employees/{id}/logs/export | admin.employees.logs.export | Download CSV |
| GET | /admin/logs | admin.logs.index | Daily attendance log |

### Fingerprint (CSRF exempt)

| Method | URI | Name | Description |
|---|---|---|---|
| POST | /admin/employees/{id}/fingerprint | admin.employees.fingerprint.save | Save fingerprint template |
| DELETE | /admin/employees/{id}/fingerprint | admin.employees.fingerprint.clear | Clear fingerprint template |
| POST | /admin/employees/{id}/fingerprint/test | admin.employees.fingerprint.test | Server-side template match test |
| GET | /admin/employees/{id}/fingerprint/get | admin.employees.fingerprint.get | Retrieve stored template for client-side match |

> Fingerprint routes are CSRF-exempt because the HF4000 WebSocket client posts data directly from the browser without going through Inertia's form handling.

---

## Kiosk Flow

The kiosk is a public-facing terminal, typically displayed on a dedicated screen at the store entrance.

### Fingerprint Matching (client-side)

1. On load, the kiosk fetches all employee fingerprint templates from `/kiosk/templates`
2. When an employee places their finger, the HF4000 scanner captures a template via WebSocket
3. The captured template is matched against all stored templates client-side using the scanner's built-in `match` command
4. On a successful match, the kiosk identifies the employee and determines the next valid action

### Action Buttons

Actions available on the kiosk:

- Time In
- Time Out
- Break 1 Out / Break 1 In
- Break 2 Out / Break 2 In
- Break 3 Out / Break 3 In

The backend returns an `allowed_actions` array with every response. The frontend uses this to lock buttons that are not valid for the current employee's state. For example, if an employee has already timed out, all buttons are locked. If they are currently on Break 1, only `break1_in` is available.

### Scan Modal

When an action button is clicked, a full-screen modal opens and automatically triggers the scanner. The employee places their finger, the system matches, and posts the punch to `/kiosk/log`. A result card is displayed for 3 seconds showing:

- Green card: successful punch with employee info, action taken, and time
- Red card: error with reason (e.g. already timed out, wrong action)

After 3 seconds (or manual dismiss), the modal closes and buttons reset to the locked state based on the returned `allowed_actions`.

### Today's Logs Panel

A collapsible panel on the kiosk shows a live feed of today's attendance entries, searchable by name or ID, with pagination. It auto-refreshes after each successful punch.

---

## Admin Panel

### Layout (`AdminLayout.vue`)

- Collapsible sidebar (state persisted in `localStorage`)
- Sticky topbar with live clock (updates every second)
- Sidebar collapses to icon-only mode with hover tooltips
- Admin user badge in sidebar footer with logout button
- Flash message support (success/error) above main content
- Footer with HFSecurity credit and tech stack attribution

### Dashboard (`DashboardController`)

Stats shown:

- Total Employees
- Enrolled (both fingerprints present)
- Present Today
- Late Today
- Absent Today
- Currently On Break

Also includes:

- Recent Activity feed (last 10 punches today, ordered by `updated_at`)
- 7-day attendance trend chart (present vs late per day)
- Unenrolled employees list (up to 5, with quick-enroll button)

### Employee Management (`EmployeeController`)

The employee list (`Index.vue`) handles three modals in one page:

**Create/Edit Modal**

Fields: ID Number, Full Name, Position, Store/Branch, Shift Start, Shift End

Shift time uses a custom 12-hour picker (hour, minute, AM/PM dropdowns). Internally the form stores and submits in `HH:MM` 24hr format for Laravel validation (`date_format:H:i`). On load when editing, the 12hr display string from the controller is parsed back into picker state via `from24()`.

Key functions:
- `to24(h, m, ampm)` — converts picker values to `HH:MM`
- `from24(t)` — parses either `"8:00 AM"` or `"08:00"` into `{ h, m, ampm }` for picker state
- `toBackend24(t)` — converts any format back to `HH:MM` before storing in the form
- `shiftDuration` (computed) — calculates and displays shift length in hours/minutes

**Delete Confirm Modal**

Simple confirmation before permanently deleting an employee and all their logs.

**Fingerprint Enrollment Modal**

Connects to the HF4000 scanner via WebSocket. Supports:

- Enrolling Finger 1 (left index) and Finger 2 (right index) independently
- Re-enrolling (overwriting) an existing template
- Clearing individual fingerprints
- Match test: captures a live scan and matches it against stored templates client-side
- Fingerprint image preview (live feed from scanner during capture)
- Scanner log terminal (shows timestamped events)

Enrollment status badge: `complete` (both enrolled), `partial` (one enrolled), `none`.

### Attendance Logs (`LogController`)

Filtered by date (defaults to today). Filters available:

- Date picker
- Search by name or ID
- Branch
- Position
- Status (present/late/absent)

Table columns: Employee, Position, Branch, Shift, Time In, Break 1, Break 2 (Lunch), Break 3, Time Out, Hours Worked, Status.

`worked_hours` is computed on read: `time_out - time_in` minus the duration of all completed break pairs.

### Employee Logs (`EmployeeLogsController`)

Per-employee attendance history at `/admin/employees/{id}/logs`.

- Employee header card with name, position, branch, shift, enrollment status
- All-time summary stats (total days, present, late, absent) — always reflects full history regardless of active filters
- Date range filter (from/to) and status filter
- Clear filters button (appears only when filters are active)
- Paginated logs table (20 per page) ordered by date descending
- Export CSV button — downloads filtered results as a CSV file named `logs_{FullName}_{YYYYMMDD}.csv`

---

## Time Formatting

All times are stored in UTC in the database. The following formatting rules apply throughout:

- **Display to users:** `g:i A` format (e.g. `8:05 AM`, `12:00 PM`) via Carbon with app timezone applied
- **Shift times (stored):** `H:i` 24hr (e.g. `08:00`, `21:00`) — stored as MySQL `time` columns
- **Shift times (displayed):** Formatted to `g:i A` in all controllers before sending to Vue
- **Form submission:** Shift times are always submitted in `H:i` 24hr — the custom time picker converts AM/PM selections before posting

App timezone is set in `config/app.php`. Change `APP_TIMEZONE` in `.env` to match your local timezone (e.g. `Asia/Manila`).

---

## Setup Instructions

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

### Installation

```bash
git clone <repo>
cd store-inout
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `.env`:

```
DB_DATABASE=store_inout
DB_USERNAME=root
DB_PASSWORD=

APP_TIMEZONE=Asia/Manila
```

Run migrations and seed:

```bash
php artisan migrate
php artisan db:seed
```

Build assets:

```bash
npm run dev       # development
npm run build     # production
```

Default admin credentials (from `DatabaseSeeder`):

```
Email:    admin@example.com
Password: admin1234
```

---

## HF4000 Scanner Integration

### Initial Setup

Before the scanner can communicate with the web application, the HF4000 SDK must be installed on the kiosk machine:

1. Run `setup.exe` from the HF4000 SDK package. This installs the necessary drivers and starts the local WebSocket server that the browser communicates with.
2. Once installed, verify the scanner is working by opening the manufacturer's default HTML demo app included in the SDK. Test a fingerprint capture there first before using the application — this confirms the scanner is properly detected, the WebSocket server is running, and the device is functional.
3. The WebSocket server must be running in the background whenever the kiosk is in use. If the kiosk shows "Disconnected", check that the SDK service is active on the machine.

### How It Works

The HF4000 communicates over a local WebSocket server at `ws://127.0.0.1:21187/fps` using a proprietary message protocol. The scanner SDK (`public/js/process.js`, provided by the manufacturer) must be loaded on the kiosk page alongside its jQuery dependency.

The Vue kiosk component does not open its own WebSocket connection. Instead it hooks into the existing `window.ws` instance created by `process.js` via `hookProcessJs()`, which intercepts `window.ws.onmessage` to handle scanner events within Vue's reactive state.

### WebSocket Message Types (`workmsg`)

| workmsg | Meaning |
|---|---|
| 2 | Ready — waiting for finger placement |
| 3 | Finger lifted |
| 5 | Capture complete (used for match test) |
| 6 | Enrollment complete — `data1` contains the Base64 template |
| 7 | Image captured — `image` contains Base64 PNG |
| 8 | Timeout |
| 9 | Match result — `retmsg` contains score (0–100) |

### Commands sent to scanner

| Command | Purpose |
|---|---|
| `{"cmd":"enrol"}` | Start enrollment capture |
| `{"cmd":"capture"}` | Capture finger for match test |
| `{"cmd":"setdata","data1":"<tpl>"}` | Load template 1 for comparison |
| `{"cmd":"setdata","data2":"<tpl>"}` | Load template 2 for comparison |
| `{"cmd":"match"}` | Run comparison, returns score via workmsg 9 |
| `{"cmd":"stop"}` | Cancel current operation |

A match score of 30 or above is considered a successful match. This threshold is applied both in the kiosk (for clocking in/out) and in the enrollment match test.

---

## Known Positions and Branches

These are hardcoded constants in both `EmployeeController` and `LogController`. To add more, update the `POSITIONS` and `DEPARTMENTS` arrays in both controllers.

**Positions:** Store Head, Store Manager, Cashier, Sales Personnel

---

## Potential Improvements

- Summary stats on the employee logs page (total hours worked across a date range)
- Overtime calculation and display
- Late threshold configuration per employee or branch (currently hardcoded in model)
- Bulk CSV import for employees
- Real-time dashboard updates via Laravel Echo / Pusher
- Role-based access (branch manager vs super admin)
- Printable daily attendance sheet
- Mobile-responsive kiosk layout for tablet mounting
- Audit log for admin actions (who edited/deleted what)
- Automatic absent marking via scheduled Laravel command at end of day
