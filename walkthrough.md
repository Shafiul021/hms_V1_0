# HMS Day 1 & Day 2 Week 1 — Walkthrough

## Day 1 Summary

All 5 Day 1 tasks from the [HMS 8-Week Daily Task Tracker](file:///d:/SD3/HMS_V1/HMS_8Week_Daily_Task_Tracker_Monorepo.md) have been completed successfully.

---

## What Was Done

### ✅ Environment Verification

| Tool | Version / Status |
|---|---|
| PHP | 8.2.12 (XAMPP) — Note: PHP 8.3 not available; Laravel 12.x installed instead of 11 as a result |
| Composer | 2.8.9 |
| Node.js | v22.14.0 |
| MySQL | MariaDB 10.4.32 (XAMPP) |
| Redis | Not installed natively — to be handled via Docker (Day 4) |

> [!NOTE]
> The system has PHP 8.2 (via XAMPP), so `laravel/laravel v13` (which requires PHP 8.3) was not installed. Composer auto-selected **Laravel 12.62.0**, which is fully compatible with the project requirements.

### ✅ Laravel Project Created

The monorepo `hms/` project was scaffolded at `d:\SD3\HMS_V1\hms\` with the full Laravel directory structure.

- **Framework**: Laravel Framework 12.62.0
- **Location**: [d:/SD3/HMS_V1/hms/](file:///d:/SD3/HMS_V1/hms/)
- Autoloader generated successfully via `composer dump-autoload`

### ✅ Vite React Plugin Installed

- Installed `@vitejs/plugin-react@4.x` (compatible with Vite 7.3.5 that ships with Laravel)
- Installed `laravel-vite-plugin` (already included in Laravel 12 scaffold)

> [!NOTE]
> `@vitejs/plugin-react@6.x` requires Vite 8+. Version `@4.x` was used instead since the Laravel scaffold ships with Vite 7. This is the correct, stable pairing.

### ✅ vite.config.js Configured

[vite.config.js](file:///d:/SD3/HMS_V1/hms/vite.config.js) was updated to include the React plugin:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

### ✅ Build Verified

`npm run build` completed successfully:

```
vite v7.3.5 building client environment for production...
✓ 55 modules transformed.
public/build/manifest.json             0.33 kB
public/build/assets/app-B_s7eo2p.css  33.79 kB
public/build/assets/app-CIomGrQN.js   46.16 kB
✓ built in 3.49s
```

---

---

## Day 2 — SPA Shell & packages/ Scaffold

### ✅ SPA Shell Created

[app.blade.php](file:///d:/SD3/HMS_V1/hms/resources/views/app.blade.php) created with `@viteReactRefresh` and `@vite` directives. All URLs served by Laravel return this shell; React Router handles client-side navigation.

### ✅ Catch-all Route Added

[routes/web.php](file:///d:/SD3/HMS_V1/hms/routes/web.php) updated with:
```php
Route::get('/{any}', fn () => view('app'))->where('any', '.*');
```
Verified with `php artisan route:list` — `{any}` route registered at `routes/web.php:15`.

### ✅ packages/ Scaffold Created

| Package | Location | Type |
|---|---|---|
| `hms/core` | [packages/hms-core/](file:///d:/SD3/HMS_V1/hms/packages/hms-core/) | PHP — shared enums |
| `hms/notifications` | [packages/hms-notifications/](file:///d:/SD3/HMS_V1/hms/packages/hms-notifications/) | PHP — notification channels |
| `@hms/ui` | [packages/hms-ui/](file:///d:/SD3/HMS_V1/hms/packages/hms-ui/) | JS — shared React components |

---

## Next Up: Day 3

- Register `hms-core` and `hms-notifications` as Composer path repositories
- Register `@hms/ui` in root `package.json` workspaces
- Write `.env` with DB, Redis, Pusher, Mail values
- Configure `config/cors.php` and `config/sanctum.php`

---

## Day 3 — Package Registration & Laravel Config

### ✅ Path Repositories + Composer Packages

[composer.json](file:///d:/SD3/HMS_V1/hms/composer.json) updated with path repositories for `hms-core` and `hms-notifications`. Both installed as dev junctions:
- `hms/core` v dev-main  
- `hms/notifications` v dev-main  
- `laravel/sanctum` v4.3.2  
- `spatie/laravel-permission` v6.25.0 (v8 requires PHP 8.3, v6.25 used on PHP 8.2)

### ✅ npm Workspaces — @hms/ui Registered

[package.json](file:///d:/SD3/HMS_V1/hms/package.json) updated with `"workspaces": ["packages/hms-ui"]`. `npm install` ran successfully.

### ✅ .env Configured

[.env](file:///d:/SD3/HMS_V1/hms/.env) updated with:
- `DB_CONNECTION=mysql`, DB credentials for XAMPP
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`
- `BROADCAST_CONNECTION=pusher`
- Mailtrap SMTP settings
- `PUSHER_*` and `VITE_PUSHER_*` variables
- `SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,...`
- `APP_KEY` generated via `php artisan key:generate`

### ✅ CORS Configured

[config/cors.php](file:///d:/SD3/HMS_V1/hms/config/cors.php): `allowed_origins` set to `localhost`, `localhost:5173`, `127.0.0.1`, `127.0.0.1:5173`. `supports_credentials=true` for Sanctum.

### ✅ Sanctum + Spatie Configured

[config/sanctum.php](file:///d:/SD3/HMS_V1/hms/config/sanctum.php): Stateful domains include `localhost:5173` for Vite dev server.  
[bootstrap/app.php](file:///d:/SD3/HMS_V1/hms/bootstrap/app.php): `statefulApi()` middleware added, Spatie role/permission middleware aliases registered, `routes/api.php` registered.

## Day 4 — Docker Setup

### ✅ Dockerfiles and Configuration Created

All configurations and Dockerfiles required for the containerized environment have been created:
- **PHP 8.3-FPM Dockerfile**: [Dockerfile](file:///d:/SD3/HMS_V1/hms/docker/php/Dockerfile) configured with basic PHP dependencies, required extensions, Composer, and Supervisor.
- **Nginx Dockerfile & Configuration**: [Dockerfile](file:///d:/SD3/HMS_V1/hms/docker/nginx/Dockerfile) and [default.conf](file:///d:/SD3/HMS_V1/hms/docker/nginx/default.conf) setup to map to the `public/` directory and fastcgi_pass to the PHP service.
- **Supervisor Configuration**: [supervisord.conf](file:///d:/SD3/HMS_V1/hms/docker/supervisor/supervisord.conf), [horizon.conf](file:///d:/SD3/HMS_V1/hms/docker/supervisor/horizon.conf), and [scheduler.conf](file:///d:/SD3/HMS_V1/hms/docker/supervisor/scheduler.conf) to manage queues and task scheduling.
- **Docker Compose**: [docker-compose.yml](file:///d:/SD3/HMS_V1/hms/docker-compose.yml) linking `php`, `nginx`, `mysql` (port 3306), `redis` (port 6379), and `supervisor` services.

> [!NOTE]
> Live container startup (`docker-compose up`) and verification tasks were skipped because Docker is not installed on this host environment. However, the configurations are completely implemented and ready for when Docker is available. We will run subsequent database tasks locally using the native XAMPP environment.

---

## Day 5 — Core Migrations

### ✅ Migrations Configured and Executed

The database schema has been successfully updated with the core tables:
- **Users**: Modified the default migration [0001_01_01_000000_create_users_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/0001_01_01_000000_create_users_table.php) to add `$table->softDeletes()`.
- **Patients**: Created [2026_06_25_000001_create_patients_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000001_create_patients_table.php) defining the patient registry with `user_id` FK (unique), `patient_code` (unique string), `dob`, `blood_type` (nullable), `gender`, and soft deletes.
- **Allergies**: Created [2026_06_25_000002_create_allergies_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000002_create_allergies_table.php) linking multiple allergens to a patient with severity ratings and notes.
- **Emergency Contacts**: Created [2026_06_25_000003_create_emergency_contacts_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000003_create_emergency_contacts_table.php) for patient emergency lookup.
- **Doctors**: Created [2026_06_25_000004_create_doctors_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000004_create_doctors_table.php) defining specializations, qualifications, fees, and soft deletes.

### ✅ Schema Integrity Verified

- **Pretend dry-run**: Ran `php artisan migrate --pretend` to inspect correct SQL generation.
- **Migration execution**: Successfully ran `php artisan migrate` on local XAMPP MySQL database.
- **Rollback integrity check**: Successfully ran `php artisan migrate:rollback --step=4` to verify constraint cleanup, and re-migrated to restore database state.

---

## Day 6 — Schedule, Appointment & OPD Migrations

### ✅ Migrations Configured and Executed

All schedules, appointments, and outpatient department (OPD) consultation migrations have been successfully created:
- **Doctor Schedules**: Created [2026_06_25_000005_create_doctor_schedules_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000005_create_doctor_schedules_table.php) defining weekly grids (days 0-6) for doctor availability.
- **Time Slots**: Created [2026_06_25_000006_create_time_slots_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000006_create_time_slots_table.php) dividing doctor schedules into intervals.
- **Appointments**: Created [2026_06_25_000007_create_appointments_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000007_create_appointments_table.php) linking patients, doctors, time slots, statuses (enum), and soft deletes.
- **Appointment Logs**: Created [2026_06_25_000008_create_appointment_logs_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000008_create_appointment_logs_table.php) for state history audits.
- **Diagnoses**: Created [2026_06_25_000009_create_diagnoses_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000009_create_diagnoses_table.php) storing consultation outcomes.
- **Prescriptions**: Created [2026_06_25_000010_create_prescriptions_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000010_create_prescriptions_table.php).
- **Prescription Items**: Created [2026_06_25_000011_create_prescription_items_table.php](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000011_create_prescription_items_table.php), referencing `medicine_id` safely via index to prevent foreign key errors before the pharmacy schema is constructed.

### ✅ Schema Integrity Verified

- **Pretend dry-run**: Ran `php artisan migrate --pretend` to confirm schema structure.
- **Migration execution**: Ran `php artisan migrate` on local MySQL.
- **Rollback validation**: Ran `php artisan migrate:rollback --step=7` successfully, confirming correct foreign key constraints and index teardown. Re-migrated to preserve schema.

---

## Day 7 — IPD, Lab, Pharmacy & Billing Migrations

### ✅ Migrations Configured and Executed

All remaining domain migrations have been successfully created, completing the full HMS database schema:

**IPD Domain:**
- [wards](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000012_create_wards_table.php) — ward types (general, ICU, private), capacity, daily rate
- [beds](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000013_create_beds_table.php) — bed numbers per ward with status enum; unique per ward
- [admissions](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000014_create_admissions_table.php) — patient → bed → doctor assignment with admit/discharge timestamps
- [nursing_notes](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000015_create_nursing_notes_table.php) — chronological nurse notes linked to admission

**Lab Domain:**
- [lab_tests](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000016_create_lab_tests_table.php) — master catalog with unique code and turnaround hours
- [lab_requests](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000017_create_lab_requests_table.php) — doctor orders with FK to appointment, patient, and test
- [lab_results](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000018_create_lab_results_table.php) — file path, technician, abnormal flag

**Pharmacy Domain:**
- [medicines](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000019_create_medicines_table.php) — soft-deletable inventory with stock threshold
- [medicine_batches](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000020_create_medicine_batches_table.php) — batch tracking with expiry date for FIFO dispensing
- [dispensings](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000021_create_dispensings_table.php) — pharmacist fulfilment linked to prescription

**Billing Domain:**
- [bills](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000022_create_bills_table.php) — patient bill with status enum (draft/issued/paid/partial), totals, due date
- [bill_items](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000023_create_bill_items_table.php) — itemized line items with type enum (consultation/lab/bed/medicine)
- [payments](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000024_create_payments_table.php) — payment records with method enum (cash/card/online)
- [prescription_items FK](file:///d:/SD3/HMS_V1/hms/database/migrations/2026_06_25_000025_add_foreign_keys_to_prescription_items.php) — deferred `medicine_id` FK applied now that `medicines` exists

### ✅ Schema Integrity Verified

- All 14 new migrations executed cleanly with `php artisan migrate`
- Full rollback via `php artisan migrate:rollback --step=14` succeeded with no constraint errors
- Re-migrated to restore complete schema

### 📊 Complete Database Schema Summary

| Domain | Tables |
|---|---|
| Auth | `users`, `roles`, `model_has_roles`, `personal_access_tokens`, `sessions` |
| Patient | `patients`, `allergies`, `emergency_contacts` |
| Doctor & Schedule | `doctors`, `doctor_schedules`, `time_slots` |
| Appointments | `appointments`, `appointment_logs` |
| OPD | `diagnoses`, `prescriptions`, `prescription_items` |
| IPD | `wards`, `beds`, `admissions`, `nursing_notes` |
| Lab | `lab_tests`, `lab_requests`, `lab_results` |
| Pharmacy | `medicines`, `medicine_batches`, `dispensings` |
| Billing | `bills`, `bill_items`, `payments` |

---

## Week 2 — Models, Auth & Core API

### Day 8 & Day 9 — Enums & Models Setup

All tasks from Day 8 and Day 9 have been completed successfully.

#### ✅ Domain Enums Created (`packages/hms-core/Enums`)
Shared enums were added to the `hms-core` package:
- [AppointmentStatus.php](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/AppointmentStatus.php): Backed string enum with `pending`, `confirmed`, `in_progress`, `completed`, `cancelled`.
- [BillStatus.php](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/BillStatus.php): Backed string enum with `draft`, `issued`, `partial`, `paid`.
- [BedStatus.php](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/BedStatus.php): Backed string enum with `available`, `occupied`, `maintenance`.
- [LabRequestStatus.php](file:///d:/SD3/HMS_V1/hms/packages/hms-core/Enums/LabRequestStatus.php): Backed string enum with `requested`, `processing`, `completed`.

#### ✅ User Model Updated
- Added `Laravel\Sanctum\HasApiTokens` and `Spatie\Permission\Traits\HasRoles`.
- Defined [patient](file:///d:/SD3/HMS_V1/hms/app/Models/User.php#L48) (`hasOne`) and [doctor](file:///d:/SD3/HMS_V1/hms/app/Models/User.php#L56) (`hasOne`) relationships.

#### ✅ All Core Models Created
All database models have been created with `$fillable` arrays, soft deletes, casts, and relationship methods:
1. **Patient Registry & Info**: [Patient](file:///d:/SD3/HMS_V1/hms/app/Models/Patient.php), [Allergy](file:///d:/SD3/HMS_V1/hms/app/Models/Allergy.php), [EmergencyContact](file:///d:/SD3/HMS_V1/hms/app/Models/EmergencyContact.php)
2. **Doctor & Rostering**: [Doctor](file:///d:/SD3/HMS_V1/hms/app/Models/Doctor.php), [DoctorSchedule](file:///d:/SD3/HMS_V1/hms/app/Models/DoctorSchedule.php), [TimeSlot](file:///d:/SD3/HMS_V1/hms/app/Models/TimeSlot.php)
3. **Appointments**: [Appointment](file:///d:/SD3/HMS_V1/hms/app/Models/Appointment.php), [AppointmentLog](file:///d:/SD3/HMS_V1/hms/app/Models/AppointmentLog.php)
4. **Outpatient (OPD)**: [Diagnosis](file:///d:/SD3/HMS_V1/hms/app/Models/Diagnosis.php), [Prescription](file:///d:/SD3/HMS_V1/hms/app/Models/Prescription.php), [PrescriptionItem](file:///d:/SD3/HMS_V1/hms/app/Models/PrescriptionItem.php)
5. **Inpatient (IPD)**: [Ward](file:///d:/SD3/HMS_V1/hms/app/Models/Ward.php), [Bed](file:///d:/SD3/HMS_V1/hms/app/Models/Bed.php), [Admission](file:///d:/SD3/HMS_V1/hms/app/Models/Admission.php), [NursingNote](file:///d:/SD3/HMS_V1/hms/app/Models/NursingNote.php)
6. **Laboratory**: [LabTest](file:///d:/SD3/HMS_V1/hms/app/Models/LabTest.php), [LabRequest](file:///d:/SD3/HMS_V1/hms/app/Models/LabRequest.php), [LabResult](file:///d:/SD3/HMS_V1/hms/app/Models/LabResult.php)
7. **Pharmacy**: [Medicine](file:///d:/SD3/HMS_V1/hms/app/Models/Medicine.php), [MedicineBatch](file:///d:/SD3/HMS_V1/hms/app/Models/MedicineBatch.php), [Dispensing](file:///d:/SD3/HMS_V1/hms/app/Models/Dispensing.php)
8. **Billing & Payments**: [Bill](file:///d:/SD3/HMS_V1/hms/app/Models/Bill.php), [BillItem](file:///d:/SD3/HMS_V1/hms/app/Models/BillItem.php), [Payment](file:///d:/SD3/HMS_V1/hms/app/Models/Payment.php)

### ✅ Integrity and Validation Verified
- Ran syntax validation checks (`php -l`) on all new PHP classes.
- Verified that Composer autoloads `Hms\Core\Enums\AppointmentStatus` cleanly.
- Added and ran a dedicated PHPUnit test case ([ModelsTest.php](file:///d:/SD3/HMS_V1/hms/tests/Unit/ModelsTest.php)) verifying model instantiation and relationship returns for all 25 models. All tests passed.

---

## Next Up: Day 10
- Create `RolePermissionSeeder` to seed 5 roles (admin, doctor, receptionist, nurse, patient).
- Create `AdminUserSeeder`, `LabTestSeeder`, `WardBedSeeder`, `MedicineSeeder`.
