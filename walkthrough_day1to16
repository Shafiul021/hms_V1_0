# 🏥 HMS_V1 — Complete Walkthrough (Days 1–16)

---

## Day 1 — Environment Setup & Laravel Scaffold

| Tool | Version / Status |
|---|---|
| PHP | 8.2.12 (XAMPP) |
| Composer | 2.8.9 |
| Node.js | v22.14.0 |
| MySQL | MariaDB 10.4.32 (XAMPP) |
| Laravel | 12.62.0 (selected due to PHP 8.2; Laravel 13 requires PHP 8.3) |

- **Laravel scaffolded** at `d:\SD3\HMS_V1\hms\`
- **Vite + React plugin** installed (`@vitejs/plugin-react@4.x`, compatible with Vite 7.3.5)
- **`vite.config.js`** updated with React + Tailwind + Laravel plugin
- `npm run build` completed successfully — 55 modules, 3.49s

---

## Day 2 — SPA Shell & packages/ Scaffold

- **SPA Shell** ([app.blade.php](file:///d:/SD3/HMS_V1/hms/resources/views/app.blade.php)) with `@viteReactRefresh` and `@vite` directives
- **Catch-all route** in [routes/web.php](file:///d:/SD3/HMS_V1/hms/routes/web.php): React Router handles all client-side navigation
- **Monorepo packages scaffolded:**

| Package | Path | Type |
|---|---|---|
| `hms/core` | `packages/hms-core/` | PHP — shared enums |
| `hms/notifications` | `packages/hms-notifications/` | PHP — notification channels |
| `@hms/ui` | `packages/hms-ui/` | JS — shared React components |

---

## Day 3 — Package Registration & Laravel Config

- **Composer path repositories** registered for `hms-core` and `hms-notifications` in [composer.json](file:///d:/SD3/HMS_V1/hms/composer.json)
- **Packages installed**: `laravel/sanctum` v4.3.2, `spatie/laravel-permission` v6.25.0
- **npm Workspaces** — `@hms/ui` registered in [package.json](file:///d:/SD3/HMS_V1/hms/package.json)
- **[.env](file:///d:/SD3/HMS_V1/hms/.env)** configured: MySQL, Redis, Pusher, Mailtrap SMTP, Sanctum stateful domains
- **CORS** ([config/cors.php](file:///d:/SD3/HMS_V1/hms/config/cors.php)): `localhost`, `localhost:5173`, `127.0.0.1:5173`, `supports_credentials=true`
- **Sanctum + Spatie** configured in [bootstrap/app.php](file:///d:/SD3/HMS_V1/hms/bootstrap/app.php): `statefulApi()`, role/permission middleware aliases, `routes/api.php` registered

---

## Day 4 — Docker Setup

> [!NOTE]
> Docker is not installed on this host (Windows/XAMPP). All Docker configs are fully implemented and ready; local XAMPP used for all DB tasks going forward.

- **PHP 8.3-FPM Dockerfile** (`docker/php/Dockerfile`) with extensions, Composer, Supervisor
- **Nginx Dockerfile & config** (`docker/nginx/`) mapping to `public/` directory
- **Supervisor configs** (`supervisord.conf`, `horizon.conf`, `scheduler.conf`) for queue/scheduler management
- **Docker Compose** ([docker-compose.yml](file:///d:/SD3/HMS_V1/hms/docker-compose.yml)): `php`, `nginx`, `mysql` (3306), `redis` (6379), `supervisor`

---

## Day 5 — Core Migrations

Migrations created and executed on XAMPP MySQL:

| Migration | Table | Key Details |
|---|---|---|
| `000000_create_users_table` | `users` | Added `softDeletes()` |
| `000001` | `patients` | `patient_code` (unique), `dob`, `blood_type`, `gender`, soft deletes |
| `000002` | `allergies` | FK to `patients`, severity, notes |
| `000003` | `emergency_contacts` | FK to `patients`, phone |
| `000004` | `doctors` | specialization, qualification, fee, soft deletes |

✅ `migrate`, `migrate --pretend`, and `migrate:rollback --step=4` all verified clean

---

## Day 6 — Schedule, Appointment & OPD Migrations

| Migration | Table |
|---|---|
| `000005` | `doctor_schedules` — weekly grids (days 0–6) |
| `000006` | `time_slots` — 30-min intervals per schedule |
| `000007` | `appointments` — patient+doctor+slot, status enum, soft deletes, `booked_by` |
| `000008` | `appointment_logs` — state-change audit trail |
| `000009` | `diagnoses` — ICD code, description, notes |
| `000010` | `prescriptions` — linked to appointment+doctor+patient |
| `000011` | `prescription_items` — `medicine_id` indexed (FK deferred until Day 7) |

✅ `migrate`, pretend, and rollback verified

---

## Day 7 — IPD, Lab, Pharmacy & Billing Migrations

**IPD Domain:**
- `wards` — ward type, capacity, daily rate
- `beds` — bed number per ward, status enum (available/occupied/maintenance)
- `admissions` — patient→bed→doctor with admit/discharge timestamps
- `nursing_notes` — chronological nurse notes per admission

**Lab Domain:**
- `lab_tests` — master catalog (unique code, turnaround hours)
- `lab_requests` — doctor orders linking appointment + patient + test
- `lab_results` — result file path, technician, is_abnormal flag

**Pharmacy Domain:**
- `medicines` — soft-deletable inventory, stock threshold
- `medicine_batches` — batch tracking with expiry (FIFO)
- `dispensings` — pharmacist fulfilment per prescription

**Billing Domain:**
- `bills` — status enum (draft/issued/partial/paid), totals, due date
- `bill_items` — type enum (consultation/lab/bed/medicine)
- `payments` — method enum (cash/card/online)
- `prescription_items` FK — deferred `medicine_id` FK applied

### 📊 Complete Database Schema

| Domain | Tables |
|---|---|
| Auth | `users`, `roles`, `model_has_roles`, `personal_access_tokens` |
| Patient | `patients`, `allergies`, `emergency_contacts` |
| Doctor & Schedule | `doctors`, `doctor_schedules`, `time_slots` |
| Appointments | `appointments`, `appointment_logs` |
| OPD | `diagnoses`, `prescriptions`, `prescription_items` |
| IPD | `wards`, `beds`, `admissions`, `nursing_notes` |
| Lab | `lab_tests`, `lab_requests`, `lab_results` |
| Pharmacy | `medicines`, `medicine_batches`, `dispensings` |
| Billing | `bills`, `bill_items`, `payments` |

---

## Days 8 & 9 — Enums & Models

### Shared Enums (`packages/hms-core/Enums/`)
- [AppointmentStatus](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/AppointmentStatus.php): `pending`, `confirmed`, `in_progress`, `completed`, `cancelled`
- [BillStatus](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/BillStatus.php): `draft`, `issued`, `partial`, `paid`
- [BedStatus](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/BedStatus.php): `available`, `occupied`, `maintenance`
- [LabRequestStatus](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/LabRequestStatus.php): `requested`, `processing`, `completed`

### All 25 Eloquent Models Created
All models have `$fillable`, `$casts`, soft deletes (where applicable), and full relationship methods:

| Group | Models |
|---|---|
| Patient Registry | [Patient](file:///d:/SD3/HMS_V1/hms/app/Models/Patient.php), [Allergy](file:///d:/SD3/HMS_V1/hms/app/Models/Allergy.php), [EmergencyContact](file:///d:/SD3/HMS_V1/hms/app/Models/EmergencyContact.php) |
| Doctor & Rostering | [Doctor](file:///d:/SD3/HMS_V1/hms/app/Models/Doctor.php), [DoctorSchedule](file:///d:/SD3/HMS_V1/hms/app/Models/DoctorSchedule.php), [TimeSlot](file:///d:/SD3/HMS_V1/hms/app/Models/TimeSlot.php) |
| Appointments | [Appointment](file:///d:/SD3/HMS_V1/hms/app/Models/Appointment.php), [AppointmentLog](file:///d:/SD3/HMS_V1/hms/app/Models/AppointmentLog.php) |
| OPD | [Diagnosis](file:///d:/SD3/HMS_V1/hms/app/Models/Diagnosis.php), [Prescription](file:///d:/SD3/HMS_V1/hms/app/Models/Prescription.php), [PrescriptionItem](file:///d:/SD3/HMS_V1/hms/app/Models/PrescriptionItem.php) |
| IPD | [Ward](file:///d:/SD3/HMS_V1/hms/app/Models/Ward.php), [Bed](file:///d:/SD3/HMS_V1/hms/app/Models/Bed.php), [Admission](file:///d:/SD3/HMS_V1/hms/app/Models/Admission.php), [NursingNote](file:///d:/SD3/HMS_V1/hms/app/Models/NursingNote.php) |
| Lab | [LabTest](file:///d:/SD3/HMS_V1/hms/app/Models/LabTest.php), [LabRequest](file:///d:/SD3/HMS_V1/hms/app/Models/LabRequest.php), [LabResult](file:///d:/SD3/HMS_V1/hms/app/Models/LabResult.php) |
| Pharmacy | [Medicine](file:///d:/SD3/HMS_V1/hms/app/Models/Medicine.php), [MedicineBatch](file:///d:/SD3/HMS_V1/hms/app/Models/MedicineBatch.php), [Dispensing](file:///d:/SD3/HMS_V1/hms/app/Models/Dispensing.php) |
| Billing | [Bill](file:///d:/SD3/HMS_V1/hms/app/Models/Bill.php), [BillItem](file:///d:/SD3/HMS_V1/hms/app/Models/BillItem.php), [Payment](file:///d:/SD3/HMS_V1/hms/app/Models/Payment.php) |

✅ [ModelsTest.php](file:///d:/SD3/HMS_V1/hms/tests/Unit/ModelsTest.php) — all 25 model instantiation + relationship tests passed

---

## Days 10–12 — Auth, Seeders & Mail Templates

- **RolePermissionSeeder**: Seeds 5 roles — `admin`, `doctor`, `receptionist`, `nurse`, `patient`
- **AdminUserSeeder**: Creates default admin account
- **LabTestSeeder, WardBedSeeder, MedicineSeeder**: Reference data seeded
- **Authentication API**: [AuthController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/Auth/AuthController.php) — `register`, `login`, `logout`, `me` endpoints with Sanctum tokens
- **Mail Templates**: Queued appointment confirmation/cancellation emails via `SendAppointmentEmail` job
- **PatientObserver**: Auto-generates `patient_code` (format: `HMS-YYYY-NNNNN`) on the `creating` event

---

## Day 13 — Routes, Controllers & Service Stubs

- **Billing Controller Realignment**: Renamed `BillController` → `BillingController` to match the SDD
- **Service Layer Stubs**: Created stubs for `AppointmentService`, `BillingService`, `DoctorService`, `PatientService`
- **Full API Routing Surface** ([api.php](file:///d:/SD3/HMS_V1/hms/routes/api.php)): 16 controllers mapped under Sanctum + Spatie role middleware

---

## Day 14 — AppointmentService, Events & Horizon

- **Laravel Horizon** installed with `--ignore-platform-reqs` (Windows/XAMPP compatibility)
- **Queued Job & Events**: `SendAppointmentEmail` job + broadcastable `AppointmentStatusChanged` and `LabResultUploaded` events
- **[AppointmentService](file:///d:/SD3/HMS_V1/hms/app/Services/AppointmentService.php)**: Booking conflict detection, slot availability checks, status transition state machine

---

## Day 15 — Patient & Doctor Controllers

### DoctorObserver — Auto Schedule Seeding
[DoctorObserver](file:///d:/SD3/HMS_V1/hms/app/Observers/DoctorObserver.php) fires on `created`:
- Seeds Monday–Friday schedule (days 1–5)
- Generates 16 × 30-minute time slots per day (09:00–17:00)

### PatientService
- `create()` / `update()` — manage User account + Patient profile + Allergies + Emergency Contacts inside DB transactions
- `getMedicalHistory()` — aggregates diagnoses, prescriptions, lab results, admissions into one response

### DoctorService
- `getAvailableSlots()` — filters active schedule slots by date/day-of-week, excluding blocked slots and slots with live (non-cancelled) appointments
- `updateSchedule()` — toggles schedule active state, manages slot blocking

### Controllers & Validation
- [PatientController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/PatientController.php): `index`, `store`, `show`, `update`, `destroy`, `history`, `prescriptions`, `labResults`, `bills`
- [DoctorController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/DoctorController.php): `index`, `show`, `store`, `slots`, `updateSchedule`
- [AppointmentController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/AppointmentController.php): `index`, `store`, `show`, `updateStatus`, `destroy`

### Test Results — Day 15
```
PASS  Tests\Unit\AppointmentServiceTest
PASS  Tests\Feature\Day15ControllersTest
  ✓ patient list only accessible by authorized roles
  ✓ can manually create patient
  ✓ can update patient
  ✓ can view patient medical history
  ✓ admin can create doctor and auto seed schedule via observer
  ✓ can query doctor available slots
  ✓ can book and update appointment status

Tests: 20 passed (75 assertions)
```

---

## Day 16 — OPD & Lab Controllers

### FormRequest Validations
- **[StoreDiagnosisRequest](file:///d:/SD3/HMS_V1/hms/app/Http/Requests/StoreDiagnosisRequest.php)**: `appointment_id` (required, exists), `icd_code` (nullable, max 20), `description` (required), `notes`, `diagnosed_at`
- **[StorePrescriptionRequest](file:///d:/SD3/HMS_V1/hms/app/Http/Requests/StorePrescriptionRequest.php)**: `appointment_id`, `notes`, nested `items[]` (`medicine_id`, `dosage`, `frequency`, `duration`)
- **[StoreLabRequestRequest](file:///d:/SD3/HMS_V1/hms/app/Http/Requests/StoreLabRequestRequest.php)**: `appointment_id` + `test_id` (required, exist)
- **[UpdateLabResultRequest](file:///d:/SD3/HMS_V1/hms/app/Http/Requests/UpdateLabResultRequest.php)**: `result_file` (nullable, max 10MB, pdf/jpg/png), `notes`, `is_abnormal`, `result_at`

### JSON Resources
- **[LabResultResource](file:///d:/SD3/HMS_V1/hms/app/Http/Resources/LabResultResource.php)**: All fields + `download_url` (30-min temporary signed URL via `URL::temporarySignedRoute()`)

### OpdService
[OpdService](file:///d:/SD3/HMS_V1/hms/app/Services/OpdService.php):
- `createDiagnosis()` — auto-fills `patient_id`/`doctor_id` from appointment
- `createPrescription()` — atomic `DB::transaction` for prescription + all items
- `createLabRequest()` — sets `status = requested`, timestamps
- `uploadLabResult()` — stores file to `private` disk, uses `updateOrCreate`, transitions lab request to `completed`
- `getResultFilePath()` — resolves absolute path for file streaming

### Controllers
- **[DiagnosisController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/DiagnosisController.php)**: `store()`
- **[PrescriptionController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/PrescriptionController.php)**: `store()` + `show()` (eager-loads all relations)
- **[LabRequestController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/LabRequestController.php)**: `store()`
- **[LabResultController](file:///d:/SD3/HMS_V1/hms/app/Http/Controllers/Api/LabResultController.php)**: `update()` (file upload), `show()`, `download()` (streamed behind `middleware('signed')`)

### Test Results — Day 16
```
PASS  Tests\Feature\Day16ControllersTest
  ✓ doctor can create diagnosis
  ✓ non doctor cannot create diagnosis
  ✓ diagnosis requires appointment id and description
  ✓ doctor can create prescription with items
  ✓ prescription requires at least one item
  ✓ can view prescription
  ✓ doctor can create lab request
  ✓ lab request requires valid test id
  ✓ nurse can upload lab result
  ✓ can view lab result
  ✓ patient cannot upload lab result
  ✓ download returns 404 when no file

Tests: 12 passed (35 assertions)
```

---

## 📊 Overall Progress

| Phase | Days | Status |
|---|---|---|
| Infrastructure & Setup | 1–4 | ✅ Complete |
| Database Schema (25 tables) | 5–7 | ✅ Complete |
| Enums, Models & Tests | 8–9 | ✅ Complete |
| Auth, Seeders & Mail | 10–12 | ✅ Complete |
| Routes & Service Stubs | 13 | ✅ Complete |
| AppointmentService & Events | 14 | ✅ Complete |
| Patient, Doctor & Appointment Controllers | 15 | ✅ Complete |
| OPD & Lab Controllers | 16 | ✅ Complete |
| IPD, Billing & Admin Controllers | 17+ | 🔜 Next |
