# HMS — 8-Week Daily Task Tracker

**Project:** Hospital Management System
**Stack:** Laravel 11 (REST API) + React 18 (SPA via Vite) + MySQL 8
**Structure:** Unified Monorepo — single `hms/` repository
**Duration:** 8 Weeks · 56 Days · 280 Tasks

> **How to use:** Work day by day in order. Check off each task with `- [x]` as you complete it. Never skip a day — each phase depends on the previous. All commands run from the `hms/` monorepo root unless stated otherwise.

---

## Table of Contents

- [Week 1 — Monorepo Setup & Database Migrations](#week-1--monorepo-setup--database-migrations)
- [Week 2 — Models, Auth & Core API](#week-2--models-auth--core-api)
- [Week 3 — OPD, Lab & IPD Modules](#week-3--opd-lab--ipd-modules)
- [Week 4 — React Foundation (resources/js/)](#week-4--react-foundation-resourcesjs)
- [Week 5 — Patient, Doctor & IPD Features](#week-5--patient-doctor--ipd-features)
- [Week 6 — Realtime, Dashboard & Charts](#week-6--realtime-dashboard--charts)
- [Week 7 — Polish & Pre-Deployment](#week-7--polish--pre-deployment)
- [Week 8 — Docker, Infrastructure & Go Live](#week-8--docker-infrastructure--go-live)

---

## Week 1 — Monorepo Setup & Database Migrations

> **Goal:** Scaffold the single `hms/` monorepo, configure Vite + React inside Laravel, initialise shared packages, spin up Docker, and create every database table.

---

### Day 1 — Monorepo Scaffold

**Phase:** Environment

- [ ] Install PHP 8.3, Composer, Node 20, MySQL 8, Redis
- [ ] Verify all tools: `php -v`, `composer -V`, `node -v`, `mysql --version`, `redis-cli ping`
- [ ] Create single Laravel 11 project: `composer create-project laravel/laravel hms`
- [ ] Install Vite React plugin at monorepo root: `npm install --save-dev @vitejs/plugin-react laravel-vite-plugin`
- [ ] Configure `vite.config.js` — use `laravel()` plugin with input `['resources/css/app.css', 'resources/js/app.js']`

---

### Day 2 — SPA Shell & packages/ Scaffold

**Phase:** Environment

- [ ] Create `resources/views/app.blade.php` — SPA shell with `@viteReactRefresh` and `@vite` directives
- [ ] Add catch-all route in `routes/web.php`: `Route::get('/{any}', fn() => view('app'))->where('any', '.*')`
- [ ] Create `packages/hms-core/` directory with `composer.json` (`name: hms/core`) and stub `Enums/` folder
- [ ] Create `packages/hms-notifications/` directory with `composer.json` (`name: hms/notifications`) and stub folders
- [ ] Create `packages/hms-ui/` directory with `package.json` (`name: @hms/ui`) and `src/index.js` stub

---

### Day 3 — Package Registration & Laravel Config

**Phase:** Environment

- [ ] Add path repositories for `hms-core` and `hms-notifications` to root `composer.json`; run `composer require hms/core:@dev hms/notifications:@dev`
- [ ] Add `"workspaces": ["packages/hms-ui"]` to root `package.json`; run `npm install`
- [ ] Write `.env` file with DB, Redis, Pusher, Mail values and `VITE_PUSHER_APP_KEY` / `VITE_PUSHER_APP_CLUSTER`
- [ ] Configure `config/cors.php` to allow SPA origin (localhost, localhost:5173, production domain)
- [ ] Configure `config/sanctum.php` stateful domains; write `bootstrap/app.php` with Sanctum middleware and role aliases

---

### Day 4 — Docker Setup

**Phase:** Environment

- [ ] Create `docker/php/Dockerfile` — PHP 8.3-FPM with all required extensions
- [ ] Create `docker/nginx/Dockerfile` and `docker/nginx/default.conf` — fastcgi_pass to php:9000, `try_files` SPA fallback
- [ ] Create `docker/supervisor/horizon.conf` and `docker/supervisor/scheduler.conf`
- [ ] Write `docker-compose.yml` — php, nginx, mysql, redis, supervisor services
- [ ] Run `docker-compose up -d` and verify all 5 containers are running

---

### Day 5 — Core Migrations

**Phase:** Database

- [ ] Add `softDeletes()` to the default users migration
- [ ] Create `patients` migration (`user_id` FK, `patient_code`, `dob`, `blood_type`, `gender`, `deleted_at`)
- [ ] Create `allergies` migration (`patient_id` FK, `allergen`, `severity`)
- [ ] Create `emergency_contacts` migration (`patient_id` FK, `name`, `relationship`, `phone`)
- [ ] Create `doctors` migration (`user_id` FK, `specialization`, `qualification`, `fee`, `deleted_at`)

---

### Day 6 — Schedule, Appointment & OPD Migrations

**Phase:** Database

- [ ] Create `doctor_schedules` migration (`doctor_id` FK, `day_of_week`, `is_active`)
- [ ] Create `time_slots` migration (`doctor_schedule_id` FK, `start_time`, `end_time`, `is_blocked`)
- [ ] Create `appointments` migration (`patient_id`, `doctor_id`, `slot_id` FKs, `status` enum, `deleted_at`)
- [ ] Create `appointment_logs` migration (`appointment_id` FK, `old_status`, `new_status`, `changed_by`)
- [ ] Create `diagnoses`, `prescriptions`, and `prescription_items` migrations

---

### Day 7 — IPD, Lab, Pharmacy & Billing Migrations

**Phase:** Database

- [ ] Create `wards` + `beds` migrations; create `admissions` + `nursing_notes` migrations
- [ ] Create `lab_tests` + `lab_requests` + `lab_results` migrations
- [ ] Create `medicines` + `medicine_batches` + `dispensings` migrations
- [ ] Create `bills` + `bill_items` + `payments` migrations
- [ ] Run `docker-compose exec php php artisan migrate` — verify all tables created without errors

---

## Week 2 — Models, Auth & Core API

> **Goal:** Complete all models and shared enums, set up authentication, seed roles, scaffold all controllers, and implement core services.

---

### Day 8 — hms-core Enums & Core Models

**Phase:** Backend

- [x] Write `packages/hms-core/Enums/AppointmentStatus.php` — backed enum with Pending, Confirmed, InProgress, Completed, Cancelled
- [x] Write `packages/hms-core/Enums/BillStatus.php`, `BedStatus.php`, `LabRequestStatus.php`
- [x] Edit `app/Models/User.php` — add `HasApiTokens`, `HasRoles`, `patient`/`doctor` relationships
- [x] Create `app/Models/Patient.php` — `$fillable`, `SoftDeletes`, all `hasMany` relationships
- [x] Create `app/Models/Doctor.php` — `$fillable`, `SoftDeletes`, `schedules`/`appointments` relationships

---

### Day 9 — Remaining Models

**Phase:** Backend

- [x] Create `app/Models/Appointment.php` — `$fillable`, `$casts`, all `belongsTo`/`hasMany` relationships; cast `status` to `AppointmentStatus` enum from hms-core
- [x] Create `app/Models/Bill.php` — `$fillable`, `$casts`; cast `status` to `BillStatus` enum from hms-core
- [x] Create models: `Allergy`, `EmergencyContact`, `DoctorSchedule`, `TimeSlot`, `AppointmentLog`
- [x] Create models: `Diagnosis`, `Prescription`, `PrescriptionItem`, `Ward`, `Bed`, `Admission`, `NursingNote`
- [x] Create models: `LabTest`, `LabRequest`, `LabResult`, `Medicine`, `MedicineBatch`, `Dispensing`, `BillItem`, `Payment`

---

### Day 10 — Seeders

**Phase:** Auth

- [x] Create `RolePermissionSeeder` — seed 5 roles: admin, doctor, receptionist, nurse, patient
- [x] Create `AdminUserSeeder` — create `admin@hms.com` / `password123`
- [x] Create `LabTestSeeder` — seed default tests (CBC, UA, FBS, LFT, RFT)
- [x] Create `WardBedSeeder` — seed General / ICU / Private wards with beds (use `BedStatus` enum from hms-core)
- [x] Create `MedicineSeeder` — seed 20 sample medicines

---

### Day 11 — Seed & Auth Requests

**Phase:** Auth

- [x] Update `DatabaseSeeder` to call all seeders in the correct dependency order
- [x] Run `php artisan db:seed` — verify roles, admin, and sample data created
- [x] Create `Auth/RegisterRequest` — name, email, password, password_confirmation rules
- [x] Create `Auth/LoginRequest` — email and password rules
- [x] Create `UserResource` — return id, name, email, role (never password)

---

### Day 12 — AuthController, Mail & PatientObserver

**Phase:** Auth

- [x] Create `Api/Auth/AuthController` with `register()`, `login()`, `logout()`, `me()` methods
- [x] Create `app/Mail/AppointmentStatusMail.php` Mailable class
- [x] Create `resources/views/emails/appointment-status.blade.php` Blade email template
- [x] Create `app/Observers/PatientObserver.php` — `created()` generates `HMS-YYYY-XXXXX` patient_code
- [x] Write auth routes in `routes/api.php` — public register/login, protected logout/me; test all four endpoints

---

### Day 13 — All Controllers, Requests & Resources

**Phase:** API

- [x] Create all 16 API controllers with `php artisan make:controller Api/NameController --api`
- [x] Create all `FormRequest` classes with `php artisan make:request`
- [x] Create all `JsonResource` classes with `php artisan make:resource`
- [x] Write complete `routes/api.php` with all role-protected route groups
- [x] Create `app/Services/` directory; create stub files for `AppointmentService`, `BillingService`, `DoctorService`, `PatientService`

---

### Day 14 — AppointmentService, BillingService & Horizon

**Phase:** Service

- [x] Write `AppointmentService::book()` — slot conflict check + DB transaction + dispatch `SendAppointmentEmail` job
- [x] Write `AppointmentService::updateStatus()` — validate transitions using `AppointmentStatus` enum + broadcast `AppointmentStatusChanged` event
- [x] Create `SendAppointmentEmail` queued Job — dispatches `AppointmentStatusMail` via `Mail::to()->send()`
- [x] Create `AppointmentStatusChanged` and `LabResultUploaded` broadcastable Events
- [x] Run `docker-compose exec php php artisan horizon:install`; verify Supervisor is running `php artisan horizon` inside container (Installed on local host environment since Docker is not present in local dev)

---

## Week 3 — OPD, Lab & IPD Modules

> **Goal:** Implement every backend controller, test the full API, write policies and broadcasting channels.

---

### Day 15 — Patient & Doctor Controllers

**Phase:** API

- [x] Implement `PatientController` index/show/store/update — use `PatientService`; gate by role
- [x] Implement `DoctorController` index/show/store and `slots()` endpoint
- [x] Write slot availability logic — filter by date, `day_of_week`, not blocked, not already booked
- [x] Implement `AppointmentController::store()` — calls `AppointmentService::book()`
- [x] Implement `AppointmentController::updateStatus()` — calls `AppointmentService::updateStatus()`

---

### Day 16 — OPD & Lab Controllers

**Phase:** API

- [x] Implement `DiagnosisController::store()` — doctor only, linked to appointment
- [x] Implement `PrescriptionController::store()` and `show()`
- [x] Implement `LabRequestController::store()` — doctor orders tests; status defaults to `LabRequestStatus::Requested`
- [x] Implement `LabResultController::update()` — upload file to `storage/app/private/lab-results/`, set status completed, fire `LabResultUploaded` event
- [x] Add secure file download to `LabResultController` — return signed temporary URL

---

### Day 17 — IPD Controllers

**Phase:** API

- [x] Implement `WardController::index()`, `show()`, and `beds()` — return beds with `BedStatus` enum values
- [x] Implement `AdmissionController::store()` — assign bed, set bed status to `BedStatus::Occupied`
- [x] Implement `AdmissionController::discharge()` — set `discharged_at`, free bed to `BedStatus::Available`
- [x] Implement `NursingNoteController::store()` and `index()`
- [x] Test all IPD endpoints — admit, discharge, add nursing note, confirm bed status updates

---

### Day 18 — Billing Controllers & PDF

**Phase:** API

- [x] Write `BillingService::generate()` — aggregate consultation fee + lab fees + bed days × ward rate + medicine cost
- [x] Write `BillingService::recordPayment()` — update `paid_amount`; set status to `BillStatus::Paid` if fully paid
- [x] Implement `BillingController::generate()`, `show()`, `downloadPdf()`
- [x] Create PDF invoice Blade template `resources/views/pdf/invoice.blade.php` — HMS branding, itemized table, total, payment status
- [x] Implement `PaymentController::store()` — call `BillingService::recordPayment()`

---

### Day 19 — Pharmacy & Admin Controllers

**Phase:** API

- [ ] Implement `MedicineController` index/store/updateStock
- [ ] Implement `DispensingController::store()` — FIFO batch deduction by expiry date
- [ ] Create `LowStockAlert` job — dispatch when stock falls below `stock_threshold`; email admin
- [ ] Implement `DashboardController` — stats, appointmentTrend, revenueTrend, bedOccupancy endpoints
- [ ] Implement `Admin/UserController` — list staff, create account, change role

---

### Day 20 — Broadcasting, Policies & Full API Test

**Phase:** API

- [ ] Write `routes/channels.php` — authorize patient, doctor, admin private channels
- [ ] Create Policies: `AppointmentPolicy`, `BillPolicy`, `LabResultPolicy`; register in `AuthServiceProvider`
- [ ] Create `AppointmentObserver` — log every status change to `appointment_logs`
- [ ] Write `routes/channels.php` broadcasting auth route; verify Pusher auth endpoint works
- [ ] Full API test round: register → login → book appointment → generate bill → pay → PDF download

---

### Day 21 — API Hardening & Backend Tests

**Phase:** API

- [ ] Fix any failing endpoints from Day 20 round trip
- [ ] Add eager loading (`with()`) to all controllers to eliminate N+1 queries
- [ ] Add rate limiting to `/auth/login` (5 attempts/minute)
- [ ] Add form validation error messages to all `FormRequest` classes
- [ ] Write `php artisan test` for Auth module — register, login, logout, wrong-role 403

---

## Week 4 — React Foundation (resources/js/)

> **Goal:** Build the entire React SPA inside `resources/js/` — packages, Axios, Zustand, Echo lib, routing, layout, and login.

---

### Day 22 — React Package Installation

**Phase:** React

- [ ] Install React packages from monorepo root: `npm install axios react-router-dom @tanstack/react-query zustand`
- [ ] Install: `react-hook-form`, `zod`, `@hookform/resolvers`, `recharts`, `@headlessui/react`, `lucide-react`
- [ ] Install: `laravel-echo`, `pusher-js`
- [ ] Install and configure Tailwind CSS with `postcss` and `autoprefixer`
- [ ] Write `resources/css/app.css` with `@tailwind base/components/utilities`; verify `npm run dev` compiles without errors

---

### Day 23 — Axios, Stores & API Files

**Phase:** React

- [ ] Write `resources/js/api/axios.js` — base URL `/api`, Bearer token interceptor, 401 → logout handler
- [ ] Write `resources/js/store/authStore.js` — Zustand persist store with `setAuth` and `logout`
- [ ] Write `resources/js/store/notificationStore.js` — `add`, `markRead`, `clear`, `unreadCount`
- [ ] Write `resources/js/api/auth.js` — `register`, `login`, `logout`, `getMe`
- [ ] Write `resources/js/api/doctors.js` — `getDoctors`, `getDoctor`, `getDoctorSlots`

---

### Day 24 — Remaining API Files

**Phase:** React

- [ ] Write `resources/js/api/appointments.js` — `getAppointments`, `bookAppointment`, `updateStatus`, `cancel`
- [ ] Write `resources/js/api/patients.js` — `getPatients`, `getPatient`, `createPatient`, `updatePatient`
- [ ] Write `resources/js/api/lab.js` — `getLabRequests`, `createLabRequest`, `uploadResult`, `getResult`
- [ ] Write `resources/js/api/billing.js` — `getBill`, `generateBill`, `recordPayment`, `downloadPdf`
- [ ] Write `resources/js/api/ipd.js`, `resources/js/api/pharmacy.js`, `resources/js/api/admin.js`

---

### Day 25 — lib/echo.js & Router

**Phase:** React

- [ ] Write `resources/js/lib/echo.js` — import Echo and Pusher, initialize Echo with `VITE_PUSHER_*` env vars, export instance
- [ ] Write `resources/js/router/ProtectedRoute.jsx` — check user and role from Zustand, redirect if unauthorized
- [ ] Write `resources/js/router/AppRouter.jsx` — all routes with `ProtectedRoute` wrappers per role, including role dashboard routes
- [ ] Write `resources/js/components/layout/Sidebar.jsx` — role-aware navigation with `NavLink`
- [ ] Write `resources/js/components/layout/TopBar.jsx` — notification bell + user name + logout

---

### Day 26 — Login, Register & App Root

**Phase:** React

- [ ] Write `resources/js/components/layout/Layout.jsx` — Sidebar + TopBar + `<Outlet />`
- [ ] Write `resources/js/features/auth/LoginPage.jsx` — `react-hook-form` + Zod + error handling
- [ ] Write `resources/js/features/auth/RegisterPage.jsx` — patient self-registration form
- [ ] Write `resources/js/App.jsx` — `QueryClientProvider` + `BrowserRouter` + `AppRouter`; import `./lib/echo` at top
- [ ] Write `resources/js/app.js` — Vite entry point; `import './bootstrap'` and `import App from './App'`; `ReactDOM.createRoot`

---

### Day 27 — Shared UI Components & hms-ui Package

**Phase:** React

- [ ] Create `resources/js/components/ui/Button.jsx`, `Input.jsx`, `Badge.jsx`, `Modal.jsx`, `Table.jsx`, `Pagination.jsx`
- [ ] Create `resources/js/components/ui/Toast.jsx`, `Skeleton.jsx`, `EmptyState.jsx`, `ConfirmDialog.jsx`
- [ ] Create `resources/js/utils/formatDate.js` and `formatCurrency.js` helpers
- [ ] Write `packages/hms-ui/src/StatusBadge.jsx` and `PatientCodeChip.jsx`; export both from `packages/hms-ui/src/index.js`
- [ ] Test that `import { StatusBadge } from '@hms/ui'` resolves correctly in a feature component

---

### Day 28 — Appointment Pages

**Phase:** React

- [ ] Write `resources/js/features/appointments/AppointmentList.jsx` — table with status filter badges
- [ ] Write `resources/js/features/appointments/BookAppointment.jsx` — 3-step wizard
- [ ] Wire Step 1: fetch doctors with `useQuery`, display selectable cards
- [ ] Wire Step 2: fetch slots by doctor + date, display time grid
- [ ] Wire Step 3: confirm + `useMutation` bookAppointment → success redirect; verify flow end-to-end

---

## Week 5 — Patient, Doctor & IPD Features

> **Goal:** Build all feature pages — patients, consultation, IPD, lab, billing, pharmacy, and role dashboards.

---

### Day 29 — Patient Pages

**Phase:** Feature

- [ ] Write `resources/js/features/patients/PatientList.jsx` — search input + paginated table
- [ ] Write `resources/js/features/patients/PatientDetail.jsx` — tab layout with 4 tabs
- [ ] Tab 1: Profile — patient info, blood type, allergies, emergency contacts; render `PatientCodeChip` from `@hms/ui`
- [ ] Tab 2: Appointments — list patient appointments with `StatusBadge` from `@hms/ui`
- [ ] Tab 3: Prescriptions — list prescriptions with medicine items

---

### Day 30 — Doctor Consultation View

**Phase:** Feature

- [ ] Tab 4: Bills — list bills with status, paid/total amounts, PDF download button
- [ ] Write `resources/js/features/doctor/ConsultationView.jsx` — diagnosis + prescription forms side by side
- [ ] Add lab order form to `ConsultationView` — select from lab tests dropdown
- [ ] Write `resources/js/features/doctor/DoctorSchedule.jsx` — weekly grid, toggle slots blocked/active
- [ ] Test full OPD flow: login as doctor → open appointment → write diagnosis → add prescription → order lab

---

### Day 31 — IPD Pages

**Phase:** Feature

- [ ] Write `resources/js/features/ipd/WardMap.jsx` — grid of colored bed squares per ward (green/red/gray)
- [ ] Click occupied bed → show patient name in tooltip using `ConfirmDialog` or popover
- [ ] Write `resources/js/features/ipd/AdmissionForm.jsx` — select patient, doctor, ward, available bed
- [ ] Write `resources/js/features/ipd/NursingNotes.jsx` — chronological list + add note form at bottom
- [ ] Write `resources/js/features/nurse/NurseDashboard.jsx` — ward occupancy summary + quick links to WardMap and NursingNotes

---

### Day 32 — Lab Pages

**Phase:** Feature

- [ ] Write `resources/js/features/lab/LabQueue.jsx` — list pending requests for lab tech
- [ ] Write `resources/js/features/lab/UploadResult.jsx` — file input + notes textarea + abnormal toggle
- [ ] Write `resources/js/features/lab/ResultViewer.jsx` — result notes, file download link, red `is_abnormal` flag
- [ ] Test lab flow: doctor orders test → lab tech uploads result → doctor sees result
- [ ] Add red `Badge` highlighting for abnormal results in `ResultViewer`

---

### Day 33 — Billing & Pharmacy Pages

**Phase:** Feature

- [ ] Write `resources/js/features/billing/BillDetail.jsx` — itemized table, KPI cards (total/paid/balance)
- [ ] Add payment modal using `Modal.jsx` — amount, method dropdown, submit mutation, optimistic update
- [ ] Add PDF download button — blob response → `<a>` click download
- [ ] Write `resources/js/features/pharmacy/Inventory.jsx` — medicine table with search + low-stock red highlight
- [ ] Add `Toast` notification on all mutation success/error states in billing and pharmacy

---

### Day 34 — Pharmacy Dispense & Admin Pages

**Phase:** Feature

- [ ] Write `resources/js/features/pharmacy/DispensePrescription.jsx` — show items, confirm availability, submit
- [ ] Write `resources/js/features/admin/UserManagement.jsx` — list staff, create account modal, change role
- [ ] Write `resources/js/features/admin/ActivityLog.jsx` — paginated Spatie activity log table
- [ ] Write `resources/js/features/profile/EditProfile.jsx` and `ChangePassword.jsx` — available to all roles
- [ ] Full patient flow test: register → book → consult → lab → bill → pay → PDF; fix any broken steps

---

### Day 35 — Role Dashboards & Error Handling

**Phase:** Feature

- [ ] Write `resources/js/features/patient/PatientDashboard.jsx` — upcoming appointments + pending bills
- [ ] Write `resources/js/features/receptionist/ReceptionistDashboard.jsx` — today's schedule + quick book button
- [ ] Update `AppRouter` to route each role to their own dashboard component on login
- [ ] Add `ErrorBoundary` component to catch React render errors gracefully
- [ ] Test all pages in mobile viewport (375px) — fix any overflow issues; audit console for React warnings

---

## Week 6 — Realtime, Dashboard & Charts

> **Goal:** Complete Pusher realtime notifications, build admin analytics dashboard, and write all tests.

---

### Day 36 — useNotifications Hook & Realtime Setup

**Phase:** Realtime

- [ ] Write `resources/js/hooks/useNotifications.js` — import `echo` from `lib/echo.js`; subscribe to private patient/doctor/admin channels
- [ ] Listen to `AppointmentStatusChanged` on patient private channel — push to `notificationStore`
- [ ] Listen to `LabResultUploaded` on doctor private channel — push to `notificationStore`
- [ ] Call `useNotifications()` inside `App.jsx` (wrap in a child component that has access to auth store)
- [ ] Write `resources/js/hooks/useAuth.js` and `resources/js/hooks/useAppointments.js` — TanStack Query wrappers

---

### Day 37 — Notification UI & Realtime Testing

**Phase:** Realtime

- [ ] Build notification dropdown in `TopBar.jsx` — recent notifications list, read/unread state
- [ ] Add red badge with unread count to notification bell icon using `notificationStore.unreadCount`
- [ ] Add `markRead` on notification item click
- [ ] Test realtime: confirm appointment as receptionist → patient sees live `Toast` notification (2 browser tabs)
- [ ] Test lab realtime: upload result as lab tech → doctor sees live notification (2 browser tabs)

---

### Day 38 — Admin Dashboard Components

**Phase:** Admin

- [ ] Write `resources/js/features/admin/KpiCards.jsx` — 4 metric cards (patients, appointments, revenue, beds)
- [ ] Write `resources/js/features/admin/AppointmentTrendChart.jsx` — Recharts `LineChart`, monthly trend with `ResponsiveContainer`
- [ ] Write `resources/js/features/admin/RevenueChart.jsx` — Recharts `BarChart`, monthly revenue with currency `Tooltip` formatter
- [ ] Write `resources/js/features/admin/BedOccupancyChart.jsx` — Recharts `PieChart`, available/occupied/maintenance breakdown
- [ ] Write `resources/js/features/admin/Dashboard.jsx` — compose all chart components with `useQuery`

---

### Day 39 — Dashboard Data & Chart Polish

**Phase:** Admin

- [ ] Verify `DashboardController::stats()` returns correct aggregate numbers
- [ ] Verify `appointmentTrend` and `revenueTrend` return correct monthly groupings
- [ ] All charts use `ResponsiveContainer width="100%" height={250}` — test at multiple viewport widths
- [ ] Seed realistic demo data: 50 patients, 10 doctors, 200 appointments, 50 bills via `DemoDataSeeder`
- [ ] Test admin dashboard with seeded data — verify all chart numbers are correct

---

### Day 40 — Feature Tests

**Phase:** Test

- [ ] Write `tests/Feature/Auth/AuthTest.php` — register, login, logout, wrong-role 403
- [ ] Write `tests/Feature/AppointmentTest.php` — book, confirm, in_progress, complete, cancel, double-book 422
- [ ] Write `tests/Feature/BillingTest.php` — generate bill, record payment, partial, full, PDF download
- [ ] Write `tests/Feature/LabTest.php` — request test, upload result, view result
- [ ] Write `tests/Feature/IpdTest.php` — admit patient, discharge, add nursing note

---

### Day 41 — Unit Tests & Role Testing

**Phase:** Test

- [ ] Write `tests/Unit/AppointmentServiceTest.php` — `book()`, slot conflict, invalid status transition
- [ ] Write `tests/Unit/BillingServiceTest.php` — `generate()`, `recordPayment()`, status update to Paid
- [ ] Run `docker-compose exec php php artisan test --parallel` — all tests must pass before proceeding
- [ ] Manual test: log in as each of the 5 roles — verify correct dashboard and pages are visible
- [ ] Manual test: verify all 403 role guards — try accessing admin routes as patient role

---

### Day 42 — Performance & Backend Review

**Phase:** Test

- [ ] Add missing database indexes on high-traffic query columns (appointment date, bill status, patient_id)
- [ ] Run `php artisan route:cache`, `config:cache`, `view:cache` inside Docker container
- [ ] Test queue is working — check Horizon dashboard at `/horizon` inside container
- [ ] Final backend code review — ensure no business logic in controllers, all logic in Services
- [ ] Verify all hms-core enum casts are working on Appointment and Bill models

---

## Week 7 — Polish & Pre-Deployment

> **Goal:** Finishing touches, search/filter enhancements, doctor dashboard, E2E test, and pre-deployment hardening.

---

### Day 43 — Search, Filters & PDF Polish

**Phase:** Feature

- [ ] Add debounced search to `PatientList.jsx` — debounced input → API query param
- [ ] Add date range filter to `AppointmentList.jsx`
- [ ] Add print-friendly CSS to `resources/views/pdf/invoice.blade.php`
- [ ] Add `ConfirmDialog` to all destructive actions (cancel appointment, discharge patient, delete)
- [ ] Add `Toast` notifications for all remaining mutation success/error states

---

### Day 44 — Booking Enhancements & Doctor Dashboard

**Phase:** Feature

- [ ] Receptionist: add "Book on behalf" flow — select patient from dropdown then book
- [ ] Add appointment notes field to `BookAppointment.jsx` wizard
- [ ] Write `resources/js/features/doctor/DoctorDashboard.jsx` — today's appointments + pending lab results
- [ ] Update `AppRouter` to include `DoctorDashboard` as the doctor role's default route
- [ ] Add patient medical history tab with diagnosis timeline in `PatientDetail.jsx`

---

### Day 45 — hms-notifications Package & Mail Review

**Phase:** Feature

- [ ] Implement `packages/hms-notifications/Channels/PusherChannel.php` — reusable Pusher broadcast logic
- [ ] Implement `packages/hms-notifications/Templates/AppointmentMailTemplate.php` — shared mail template helper
- [ ] Refactor `AppointmentStatusChanged` event to use `PusherChannel` from hms-notifications
- [ ] Test email delivery via Mailtrap — confirm appointment status emails arrive with correct content
- [ ] Stress test queue: dispatch 50 emails at once — confirm Horizon processes all within Docker container

---

### Day 46 — Cross-Browser & Accessibility Testing

**Phase:** Test

- [ ] Cross-browser test: Chrome, Firefox, Edge — fix any rendering differences
- [ ] Run Lighthouse audit — aim for Performance ≥ 90, Accessibility ≥ 85
- [ ] Fix accessibility issues: missing `aria-label`, contrast ratios, focus states on modals
- [ ] Test `npm run build` produces clean `public/build/` output with no errors
- [ ] Verify `app.blade.php` SPA shell loads assets correctly from `public/build/manifest.json`

---

### Day 47 — E2E Browser Test (Laravel Dusk)

**Phase:** Test

- [ ] Install Laravel Dusk: `composer require --dev laravel/dusk`; run `php artisan dusk:install`
- [ ] Write `tests/Browser/PatientJourneyTest.php` — full lifecycle: register → book → consult → lab → pay → PDF download
- [ ] Run Dusk inside Docker: `docker-compose exec php php artisan dusk`
- [ ] Fix any Dusk failures — update selectors and wait conditions as needed
- [ ] Document Dusk setup in `README.md` — how to run E2E tests locally

---

### Day 48 — Security & Pre-Deploy Hardening

**Phase:** Test

- [ ] Update CORS `allowed_origins` to include production domain
- [ ] Set `APP_DEBUG=false` in production `.env` — test that 500 error responses are safe (no stack traces)
- [ ] Confirm all `storage/` file paths and `php artisan storage:link` work inside Docker
- [ ] Review all API responses — ensure no internal error details leak to clients
- [ ] Confirm `VITE_*` variables are the only `.env` values exposed to the browser bundle

---

### Day 49 — Cleanup & Pre-Deploy Commit

**Phase:** Test

- [ ] Run `php artisan migrate:fresh --seed` inside Docker on a clean DB — verify everything works from scratch
- [ ] Remove all `console.log()`, `dd()`, and `var_dump()` debug statements from codebase
- [ ] Double-check all `.env` variables are documented in `.env.example` (including `VITE_PUSHER_*`)
- [ ] Run full test suite one final time: `docker-compose exec php php artisan test --parallel`
- [ ] Final git commit — tag as `v1.0-pre-deploy` and push to `main`

---

## Week 8 — Docker, Infrastructure & Go Live

> **Goal:** Build production Docker images, provision infrastructure with Terraform, deploy, configure SSL, and go live.

---

### Day 50 — docker-compose.prod.yml & Production Images

**Phase:** Deploy

- [ ] Write `docker-compose.prod.yml` — production overrides: no volume mounts, build from Dockerfiles, expose only port 443
- [ ] Update `docker/php/Dockerfile` — run `npm run build` as a build step so `public/build/` is baked into the image
- [ ] Build production images: `docker-compose -f docker-compose.prod.yml build`
- [ ] Create production `.env` with real DB, Pusher, Mail, `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Verify Supervisor `horizon.conf` and `scheduler.conf` are included in the PHP image

---

### Day 51 — Terraform Provision

**Phase:** Deploy

- [ ] Write `infrastructure/terraform/environments/production/main.tf` — compute (EC2/Droplet), managed MySQL, managed Redis, VPC, security groups
- [ ] Write `infrastructure/terraform/environments/staging/main.tf` — smaller instance tier
- [ ] Run `cd infrastructure/terraform/environments/production && terraform init && terraform plan`
- [ ] Run `terraform apply` — provision VPS, managed DB, Redis; note output IPs and connection strings
- [ ] Update production `.env` with Terraform output values (DB host, Redis host, server IP)

---

### Day 52 — Server Setup & Deploy Script

**Phase:** Deploy

- [ ] Run `bash infrastructure/scripts/setup-server.sh` on the provisioned server — installs Docker, Docker Compose, Nginx, Certbot
- [ ] Copy `docker-compose.prod.yml` and `.env` to server
- [ ] Run production containers: `docker-compose -f docker-compose.prod.yml up -d`
- [ ] Run migrations and seed roles: `docker-compose exec php php artisan migrate --force && php artisan db:seed --class=RolePermissionSeeder`
- [ ] Run caches: `docker-compose exec php php artisan config:cache route:cache view:cache`

---

### Day 53 — Nginx, SSL & Scheduler

**Phase:** Deploy

- [ ] Copy `infrastructure/nginx/hms.conf` to `/etc/nginx/sites-available/` on server; enable site
- [ ] Install SSL: `sudo certbot --nginx -d your-domain.com` — verify HTTPS is working
- [ ] Test auto-renewal: `sudo certbot renew --dry-run`
- [ ] Verify Supervisor inside Docker is running both `horizon` and `scheduler` processes: `docker-compose exec supervisor supervisorctl status`
- [ ] Test that `php artisan schedule:run` fires correctly inside the supervisor container

---

### Day 54 — backup-db.sh & deploy.sh

**Phase:** Deploy

- [ ] Finalise `infrastructure/scripts/deploy.sh` — `git pull`, `composer install --no-dev`, `npm ci && npm run build`, `migrate --force`, `config:cache`, `supervisorctl restart all`
- [ ] Finalise `infrastructure/scripts/backup-db.sh` — `mysqldump` piped to gzip + S3 upload via `aws s3 cp`
- [ ] Schedule backup: add `backup-db.sh` to server crontab (daily at 02:00)
- [ ] Run a test deploy: push a minor change → run `deploy.sh` → verify zero-downtime update
- [ ] Verify Horizon dashboard is accessible at `https://your-domain.com/horizon` (admin only)

---

### Day 55 — Production Smoke Testing

**Phase:** Deploy

- [ ] Full production smoke test: visit `https://your-domain.com` — login as admin; navigate all pages
- [ ] Book appointment as patient — verify confirmation email arrives via production mail server
- [ ] Upload lab result as lab tech — verify doctor receives real-time Pusher notification in production
- [ ] Generate bill → record payment → download PDF invoice — verify PDF renders correctly in production
- [ ] Check Horizon dashboard — verify queue workers are processing jobs and no failures

---

### Day 56 — Go Live 🚀

**Phase:** Deploy

- [ ] Create 5 demo accounts (one per role) with clear credentials; seed realistic demo data
- [ ] Write `README.md` — monorepo structure overview, Docker setup steps, role credentials, how to run tests
- [ ] Set up basic server monitoring — uptime check on `/api/auth/me`, disk usage alert, Docker container health check
- [ ] Tag final release: `git tag v1.0 && git push origin v1.0`
- [ ] **Project complete — HMS monorepo is live!** 🎉

---

## Quick Reference

### Progress Overview

| Week | Days | Focus | Tasks |
|---|---|---|---|
| Week 1 | Day 1–7 | Monorepo Setup & DB Migrations | 35 |
| Week 2 | Day 8–14 | Models, Auth & Core API | 35 |
| Week 3 | Day 15–21 | OPD, Lab & IPD Modules | 35 |
| Week 4 | Day 22–28 | React Foundation (resources/js/) | 35 |
| Week 5 | Day 29–35 | Patient, Doctor & IPD Features | 35 |
| Week 6 | Day 36–42 | Realtime, Dashboard & Charts | 35 |
| Week 7 | Day 43–49 | Polish & Pre-Deployment | 35 |
| Week 8 | Day 50–56 | Docker, Infrastructure & Go Live | 35 |
| **Total** | **56 days** | | **280 tasks** |

---

### All Artisan Commands

```bash
# Run all artisan commands inside Docker
docker-compose exec php php artisan <command>

# Package install
composer require laravel/sanctum spatie/laravel-permission \
  spatie/laravel-activitylog barryvdh/laravel-dompdf laravel/horizon

# Register local packages (in composer.json repositories, then:)
composer require hms/core:@dev hms/notifications:@dev

# Publish configs
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Make classes
php artisan make:controller Api/NameController --api
php artisan make:request StoreNameRequest
php artisan make:resource NameResource
php artisan make:mail NameMail
php artisan make:job JobName
php artisan make:event EventName
php artisan make:observer NameObserver --model=Name
php artisan make:policy NamePolicy --model=Name
php artisan make:model Name -mf

# Queue & Horizon
php artisan horizon:install
php artisan horizon          # handled by Supervisor in Docker
php artisan queue:work

# Testing
php artisan test --parallel
php artisan dusk             # E2E browser tests

# Production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
php artisan storage:link
```

---

### All npm Commands

```bash
# All npm commands run from the monorepo root (hms/)

# Install all packages (once)
npm install

# Dev server — Vite HMR, runs alongside Docker php service
npm run dev

# Production build — outputs to public/build/
npm run build

# Preview production build locally
npm run preview
```

---

### Docker Commands

```bash
# Start all services
docker-compose up -d

# Run commands inside containers
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
docker-compose exec php npm run build
docker-compose exec supervisor supervisorctl status

# View logs
docker-compose logs -f php
docker-compose logs -f supervisor

# Stop services
docker-compose down

# Production
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d
```

---

### Terraform Commands

```bash
cd infrastructure/terraform/environments/production

terraform init
terraform plan
terraform apply
terraform destroy   # staging teardown only
```

---

### Key File Locations (Monorepo)

| What | Where |
|---|---|
| Laravel backend | `app/` |
| React SPA | `resources/js/` |
| SPA shell | `resources/views/app.blade.php` |
| Email templates | `resources/views/emails/` |
| PDF template | `resources/views/pdf/invoice.blade.php` |
| Shared PHP enums | `packages/hms-core/Enums/` |
| Shared notification logic | `packages/hms-notifications/` |
| Shared React components | `packages/hms-ui/src/` |
| Echo initialization | `resources/js/lib/echo.js` |
| Docker service configs | `docker/` |
| Terraform IaC | `infrastructure/terraform/` |
| Deploy scripts | `infrastructure/scripts/` |
| Production Nginx config | `infrastructure/nginx/hms.conf` |
| Vite config | `vite.config.js` (monorepo root) |
| API routes | `routes/api.php` |
| Web catch-all route | `routes/web.php` |

---

### Status Flow Reference

```
# Appointments (hms-core AppointmentStatus enum)
pending → confirmed → in_progress → completed
               ↘              ↘
           cancelled       cancelled

# Bills (hms-core BillStatus enum)
draft → issued → partial → paid

# Beds (hms-core BedStatus enum)
available | occupied | maintenance

# Lab Requests (hms-core LabRequestStatus enum)
requested → processing → completed
```

---

### Role Access Summary

| Role | Dashboard | Key Access |
|---|---|---|
| `admin` | `features/admin/Dashboard.jsx` | Everything — staff management, analytics, all modules |
| `doctor` | `features/doctor/DoctorDashboard.jsx` | Own appointments, consultation, prescriptions, lab orders |
| `receptionist` | `features/receptionist/ReceptionistDashboard.jsx` | Appointments, patient management, billing |
| `nurse` | `features/nurse/NurseDashboard.jsx` | Ward management, bed map, nursing notes |
| `patient` | `features/patient/PatientDashboard.jsx` | Book appointments, view own records, download bills |

---

*HMS — 8-Week Daily Task Tracker · Version 2.0 · Unified Monorepo · Laravel 11 + React 18 + MySQL 8*
