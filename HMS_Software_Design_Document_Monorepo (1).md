# Hospital Management System — Software Design Document

**Version:** 2.0 | **Date:** June 2026
**Stack:** Laravel 11 (REST API) + React 18 (SPA via Vite) + MySQL 8
**Structure:** Unified Monorepo — single `hms/` repository

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [System Architecture](#2-system-architecture)
3. [Database Design](#3-database-design)
4. [REST API Design](#4-rest-api-design)
5. [Backend Code Structure](#5-backend-code-structure)
6. [Frontend Code Structure](#6-frontend-code-structure)
7. [Shared Packages](#7-shared-packages)
8. [Docker & Infrastructure](#8-docker--infrastructure)
9. [Step-by-Step Development Task List](#9-step-by-step-development-task-list)
10. [Security Design](#10-security-design)
11. [Key Commands Reference](#11-key-commands-reference)
12. [Appendix — Status Flows & Enums](#12-appendix--status-flows--enums)

---

## 1. Introduction

This Software Design Document (SDD) defines the complete technical blueprint for building the Hospital Management System as a **unified monorepo**. Laravel 11 and React 18 live in a single repository. The React SPA is compiled by Vite and served through Laravel — there is no separate frontend project.

The document is organized to be followed sequentially. Each section builds on the previous one — from environment setup to final deployment via Docker and Terraform-managed infrastructure.

### 1.1 Purpose

- Serve as the authoritative technical reference for the HMS development team
- Define all design decisions before implementation begins, reducing ambiguity
- Provide a step-by-step task checklist organized by phase and sprint
- Specify all database tables, API endpoints, component structures, Docker services, and IaC

### 1.2 Scope

This document covers the full-stack HMS consisting of:

- **Laravel 11** REST API backend with Sanctum authentication, RBAC, queued jobs, real-time broadcasting, and PDF generation
- **React 18** SPA living inside `resources/js/`, compiled by Vite and served through the Laravel `app.blade.php` SPA shell
- **MySQL 8** relational database with a normalized, fully indexed schema
- **Shared internal packages** for domain constants, notification logic, and reusable UI components
- **Docker** containerized local development and production deployment (PHP-FPM, Nginx, MySQL, Redis, Supervisor)
- **Terraform** infrastructure-as-code for production and staging environments

### 1.3 Audience

Full-stack developers, technical leads, QA engineers, DevOps engineers, and database administrators involved in building and deploying the HMS.

### 1.4 Monorepo Philosophy

The unified monorepo approach means:

- **One repository** — all code, infrastructure, and configuration lives in `hms/`
- **One `package.json`** — React dependencies are installed at the monorepo root alongside Laravel
- **One `vite.config.js`** — Vite is configured as a Laravel plugin, not a standalone dev server
- **Shared packages** — domain enums, notification logic, and UI primitives are extracted into `packages/` and referenced by both backend and frontend
- **Atomic commits** — a single commit can touch API, frontend, migrations, and infrastructure together

---

## 2. System Architecture

### 2.1 Architecture Overview

The HMS uses a **decoupled architecture within a unified monorepo**. Laravel serves the React SPA shell via `app.blade.php`, and all subsequent data fetching happens exclusively via authenticated REST API calls over HTTPS. The frontend and backend share the same repository but communicate only through the API.

```
Browser (React SPA — loaded from resources/js/)
  → Vite bundles assets into public/build/
    → app.blade.php serves the SPA shell
      → React makes HTTPS REST API calls
        → Laravel Application (routes/api.php)
          → MySQL (data)
          → Redis (cache / queue)
          → Pusher (realtime broadcast)
          → Storage (lab results / PDFs)
```

> No Blade views are used for application data — only `app.blade.php` (SPA shell), `views/pdf/invoice.blade.php` (DomPDF), and `views/emails/` (mail templates).

### 2.2 Request Lifecycle

1. User visits the domain — Nginx serves `public/index.php`
2. Laravel returns `app.blade.php`, which loads Vite-compiled React assets
3. React Router takes over; user actions trigger Axios API calls
4. Laravel API receives the request, passes it through Sanctum auth middleware
5. Role/permission middleware (Spatie) checks authorization
6. Controller invokes a Service class containing business logic
7. Service queries the database via Eloquent ORM
8. Response is serialized as JSON through an API Resource class
9. Heavy tasks (emails, PDF generation) are dispatched to Redis queue
10. Real-time events broadcast to React frontend via Pusher WebSocket

### 2.3 Technology Stack

| Layer / Concern | Technology |
|---|---|
| Backend Framework | Laravel 11 (PHP 8.3) |
| Authentication | Laravel Sanctum (stateless SPA token auth) |
| Authorization / RBAC | spatie/laravel-permission (roles + permissions) |
| ORM | Eloquent with relationships, scopes, observers |
| Queue & Jobs | Laravel Horizon — Redis-backed queue dashboard |
| Realtime / WebSocket | Laravel Echo + Pusher Channels |
| PDF Generation | barryvdh/laravel-dompdf |
| Activity Logging | spatie/laravel-activitylog |
| Mail | Laravel Mailable classes + Blade email templates |
| Frontend Framework | React 18 compiled by Vite (Laravel plugin) |
| Client Routing | react-router-dom v6 |
| Global State | Zustand (lightweight store) |
| Server State / Cache | @tanstack/react-query v5 |
| HTTP Client | Axios with interceptors |
| Forms & Validation | react-hook-form + Zod schema validation |
| Charts | Recharts (admin analytics dashboard) |
| UI Components | Headless UI (@headlessui/react) |
| Realtime (client) | laravel-echo + pusher-js (initialized in `resources/js/lib/echo.js`) |
| Shared Enums | hms-core (internal Composer package) |
| Shared Notifications | hms-notifications (internal Composer package) |
| Shared UI Primitives | hms-ui (internal npm package) |
| Database | MySQL 8 — relational, 3NF normalized |
| Cache / Queue Driver | Redis 7 |
| Containerization | Docker (PHP-FPM, Nginx, MySQL, Redis, Supervisor) |
| Web Server | Nginx (reverse proxy + static asset serving) |
| Process Manager | Supervisor (Horizon + scheduler) |
| Infrastructure as Code | Terraform (production + staging environments) |

---

## 3. Database Design

### 3.1 Design Principles

- **Third Normal Form (3NF)** — no partial or transitive dependencies
- All primary keys are **unsigned BIGINT** with auto-increment
- Foreign keys with `ON DELETE CASCADE` on child records; `RESTRICT` on master data
- **Soft deletes** (`deleted_at`) on all critical domain tables
- Every FK column is indexed; frequently queried columns have composite indexes
- `created_at`, `updated_at` timestamps on all tables
- `created_by`, `updated_by` on transactional tables

### 3.2 Table Definitions by Domain

#### Users & Access Control

| Table | Key Columns | Notes |
|---|---|---|
| `users` | id, name, email, password, email_verified_at, deleted_at | Sanctum token source. Role assigned via model_has_roles. Soft delete. |
| `roles` | id, name, guard_name | Seeded: admin, doctor, receptionist, nurse, patient |
| `model_has_roles` | role_id, model_type, model_id | Polymorphic pivot. Links user to role(s). |
| `personal_access_tokens` | tokenable_id, token, abilities | Laravel Sanctum tokens. One per user session. |

#### Patient Domain

```
patients            id | user_id (FK) | patient_code | dob | blood_type | gender | deleted_at
allergies           id | patient_id (FK) | allergen | severity | notes
emergency_contacts  id | patient_id (FK) | name | relationship | phone
medical_histories   id | patient_id (FK) | recorded_at | condition | notes | doctor_id (FK)
```

#### Doctor & Schedule Domain

```
doctors             id | user_id (FK) | specialization | qualification | fee | deleted_at
doctor_schedules    id | doctor_id (FK) | day_of_week | is_active
time_slots          id | doctor_schedule_id (FK) | start_time | end_time | is_blocked
```

#### Appointment Domain

```
appointments        id | patient_id (FK) | doctor_id (FK) | slot_id (FK) | date
                       | status (enum: pending|confirmed|in_progress|completed|cancelled)
                       | booked_by | notes | created_at | updated_at | deleted_at
appointment_logs    id | appointment_id (FK) | old_status | new_status | changed_by | created_at
```

#### OPD — Consultation Domain

```
diagnoses           id | appointment_id (FK) | doctor_id (FK) | patient_id (FK)
                       | icd_code | description | notes | diagnosed_at
prescriptions       id | appointment_id (FK) | doctor_id (FK) | patient_id (FK) | notes
prescription_items  id | prescription_id (FK) | medicine_id (FK) | dosage | frequency | duration
```

#### IPD — Inpatient Domain

```
wards               id | name | type (general|icu|private) | capacity | daily_rate | created_at
beds                id | ward_id (FK) | bed_number | status (available|occupied|maintenance)
admissions          id | patient_id (FK) | bed_id (FK) | doctor_id (FK)
                       | admitted_at | discharged_at | reason | notes
nursing_notes       id | admission_id (FK) | nurse_id (FK) | note | recorded_at
```

#### Lab Domain

```
lab_tests           id | name | code | price | turnaround_hours | created_at
lab_requests        id | appointment_id (FK) | doctor_id (FK) | patient_id (FK)
                       | test_id (FK) | status (requested|processing|completed) | requested_at
lab_results         id | lab_request_id (FK) | technician_id (FK)
                       | result_file | notes | result_at | is_abnormal
```

#### Pharmacy Domain

```
medicines           id | name | generic_name | unit | price | stock_threshold | deleted_at
medicine_batches    id | medicine_id (FK) | batch_no | quantity | expiry_date | created_at
dispensings         id | prescription_id (FK) | pharmacist_id (FK) | dispensed_at | notes
```

#### Billing Domain

```
bills               id | patient_id (FK) | appointment_id (FK) | status (draft|issued|paid|partial)
                       | total_amount | paid_amount | due_date | issued_at
bill_items          id | bill_id (FK) | item_type (consultation|lab|bed|medicine)
                       | description | quantity | unit_price | total
payments            id | bill_id (FK) | amount | method (cash|card|online)
                       | reference_no | paid_at | recorded_by
```

---

## 4. REST API Design

### 4.1 API Conventions

- **Base URL:** `https://your-domain.com/api`
- All requests and responses use JSON (`Content-Type: application/json`)
- **Authentication:** Bearer token in `Authorization` header (Laravel Sanctum)
- Successful responses: `200 OK`, `201 Created`, `204 No Content`
- Error responses: `422` Unprocessable (validation), `401` Unauthorized, `403` Forbidden, `404` Not Found, `500` Server Error
- Paginated list responses include: `data[]`, `meta.current_page`, `meta.total`, `meta.last_page`
- Date format: ISO 8601 (`YYYY-MM-DD` or `YYYY-MM-DDTHH:mm:ssZ`)

> **Note:** All routes except `/auth/register` and `/auth/login` require a valid Sanctum Bearer token. Role checking is enforced via Spatie permission middleware on each route group.

### 4.2 Complete API Endpoint Reference

#### Authentication

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `POST` | `/api/auth/register` | Public | Patient self-registration. Returns user + token. |
| `POST` | `/api/auth/login` | Public | Login for all roles. Returns user + token. |
| `POST` | `/api/auth/logout` | All | Revoke current access token. |
| `GET` | `/api/auth/me` | All | Return authenticated user with role and profile. |

#### Patients

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/patients` | Admin, Receptionist | Paginated patient list with search. |
| `GET` | `/api/patients/{id}` | Admin, Doctor, Receptionist | Patient profile + summary. |
| `POST` | `/api/patients` | Admin, Receptionist | Create patient record manually. |
| `PATCH` | `/api/patients/{id}` | Admin, Receptionist | Update patient profile. |
| `GET` | `/api/patients/{id}/history` | Doctor, Admin | Full medical history log. |
| `GET` | `/api/patients/{id}/prescriptions` | Doctor, Patient | All prescriptions. |
| `GET` | `/api/patients/{id}/lab-results` | Doctor, Patient | All lab results. |
| `GET` | `/api/patients/{id}/bills` | Admin, Patient | Billing history. |

#### Doctors

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/doctors` | All | List all active doctors with specialization. |
| `GET` | `/api/doctors/{id}` | All | Doctor profile, schedule, and fee. |
| `GET` | `/api/doctors/{id}/slots` | All | `?date=YYYY-MM-DD` — Available time slots. |
| `POST` | `/api/doctors` | Admin | Create doctor profile + user account. |
| `PATCH` | `/api/doctors/{id}/schedule` | Admin, Doctor | Update weekly schedule. |

#### Appointments

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/appointments` | Admin, Doctor, Receptionist | Paginated list with filters. |
| `POST` | `/api/appointments` | Patient, Receptionist | Book appointment (doctor + slot + date). |
| `GET` | `/api/appointments/{id}` | All | Appointment detail with status history. |
| `PATCH` | `/api/appointments/{id}/status` | Doctor, Receptionist, Admin | Advance status in workflow. |
| `DELETE` | `/api/appointments/{id}` | Patient, Admin | Cancel appointment (soft delete). |

#### OPD — Prescriptions & Diagnoses

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `POST` | `/api/diagnoses` | Doctor | Record diagnosis for an appointment. |
| `POST` | `/api/prescriptions` | Doctor | Create prescription with medicine items. |
| `GET` | `/api/prescriptions/{id}` | Doctor, Patient, Pharmacist | Prescription detail. |
| `POST` | `/api/lab-requests` | Doctor | Order lab test(s) for patient. |
| `PATCH` | `/api/lab-results/{id}` | Lab Tech | Upload result file and notes. |
| `GET` | `/api/lab-results/{id}` | Doctor, Patient | View lab result and file. |

#### IPD — Wards, Beds, Admissions

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/wards` | Admin, Doctor, Nurse | List wards with bed availability. |
| `GET` | `/api/wards/{id}/beds` | Admin, Nurse | All beds in a ward with status. |
| `POST` | `/api/admissions` | Admin, Doctor | Admit patient to a bed. |
| `PATCH` | `/api/admissions/{id}/discharge` | Doctor, Admin | Discharge patient, free bed. |
| `POST` | `/api/admissions/{id}/notes` | Nurse | Add daily nursing note. |
| `GET` | `/api/admissions/{id}/notes` | Doctor, Nurse | View all nursing notes. |

#### Billing & Payments

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `POST` | `/api/bills/generate` | Admin, Receptionist | Auto-generate bill from appointment. |
| `GET` | `/api/bills/{id}` | Admin, Patient, Receptionist | Bill detail with itemized breakdown. |
| `POST` | `/api/payments` | Admin, Receptionist | Record a payment (partial or full). |
| `GET` | `/api/bills/{id}/pdf` | Admin, Patient | Download invoice as PDF. |

#### Lab Tests & Pharmacy

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/lab-tests` | Admin, Doctor | Master list of available lab tests. |
| `GET` | `/api/medicines` | Admin, Pharmacist | Paginated medicine inventory. |
| `POST` | `/api/medicines` | Admin | Add new medicine to inventory. |
| `PATCH` | `/api/medicines/{id}/stock` | Admin, Pharmacist | Update stock level. |
| `POST` | `/api/dispensings` | Pharmacist | Record prescription fulfillment. |

#### Admin & Analytics

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `GET` | `/api/admin/stats` | Admin | Dashboard KPIs: patients, revenue, appointments. |
| `GET` | `/api/admin/appointments/trend` | Admin | Monthly appointment volume chart data. |
| `GET` | `/api/admin/revenue/trend` | Admin | Monthly revenue chart data. |
| `GET` | `/api/admin/bed-occupancy` | Admin | Current ward/bed occupancy rates. |
| `GET` | `/api/admin/activity-log` | Admin | Paginated Spatie activity log. |
| `GET` | `/api/admin/users` | Admin | All staff accounts with roles. |
| `POST` | `/api/admin/users` | Admin | Create staff account with role. |
| `PATCH` | `/api/admin/users/{id}/role` | Admin | Change user role. |

---

## 5. Backend Code Structure

The entire Laravel backend lives under `app/` at the monorepo root.

### 5.1 Directory Layout

```
app/
  Console/Commands/
    GenerateDailyBills.php
  Events/
    AppointmentStatusChanged.php
    LabResultUploaded.php
  Http/
    Controllers/Api/
      Auth/AuthController.php
      Admin/
        DashboardController.php
        UserController.php
      PatientController.php
      DoctorController.php
      AppointmentController.php
      DiagnosisController.php
      PrescriptionController.php
      LabRequestController.php
      LabResultController.php
      WardController.php
      AdmissionController.php
      NursingNoteController.php
      BillingController.php
      PaymentController.php
      MedicineController.php
      DispensingController.php
    Middleware/
    Requests/                    # FormRequest classes per action
    Resources/                   # JsonResource classes per model
  Jobs/
    SendAppointmentEmail.php
    LowStockAlert.php
  Mail/
    AppointmentStatusMail.php    # Mailable class — uses views/emails/appointment-status.blade.php
  Models/
    User.php
    Patient.php
    Doctor.php
    Appointment.php
    Ward.php / Bed.php / Admission.php
    LabTest.php / LabRequest.php / LabResult.php
    Medicine.php / MedicineBatch.php
    Bill.php / BillItem.php / Payment.php
  Observers/
    AppointmentObserver.php
    PatientObserver.php
  Policies/
    AppointmentPolicy.php
    BillPolicy.php
    LabResultPolicy.php
  Services/
    AppointmentService.php
    BillingService.php
    DoctorService.php
    PatientService.php
```

### 5.2 Controller Pattern

Controllers are **thin**. They validate (via FormRequest), call a Service, and return a Resource. No business logic in controllers.

```php
class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request, AppointmentService $service)
    {
        $appointment = $service->book($request->validated(), auth()->user());
        return new AppointmentResource($appointment);  // 201
    }
}
```

### 5.3 Service Pattern

Services contain all business logic: slot conflict checking, auto-billing, status transitions, notifications. They are injected into controllers via Laravel's IoC container.

```php
class AppointmentService
{
    public function book(array $data, $bookedBy): Appointment
    {
        // 1. Check slot conflict
        // 2. DB transaction: create appointment + log
        // 3. Dispatch SendAppointmentEmail job
        // 4. Return appointment
    }
}
```

### 5.4 Mail Pattern

Mailable classes in `app/Mail/` are separate from Jobs. The Job dispatches the Mailable; the Mailable renders the Blade email template.

```php
// app/Mail/AppointmentStatusMail.php
class AppointmentStatusMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Appointment Status Update');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment-status');
    }
}

// app/Jobs/SendAppointmentEmail.php
class SendAppointmentEmail implements ShouldQueue
{
    public function handle(): void
    {
        Mail::to($this->appointment->patient->user->email)
            ->send(new AppointmentStatusMail($this->appointment));
    }
}
```

### 5.5 API Resource Pattern

Resources shape the JSON output and never expose passwords or internal implementation details.

```php
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'status'  => $this->status,
            'date'    => $this->appointment_date->format('Y-m-d'),
            'doctor'  => new DoctorResource($this->whenLoaded('doctor')),
            'patient' => new PatientResource($this->whenLoaded('patient')),
        ];
    }
}
```

---

## 6. Frontend Code Structure

The entire React 18 SPA lives inside `resources/js/`. Vite (configured as a Laravel plugin) compiles it into `public/build/`. The SPA is served by `resources/views/app.blade.php`.

### 6.1 Directory Layout

```
resources/
  js/
    api/                         # Axios instance + per-resource API functions
      axios.js                   # Base URL, interceptors, 401 handler → logout
      auth.js
      appointments.js
      patients.js
      doctors.js
      lab.js
      billing.js
      ipd.js
      pharmacy.js
      admin.js
    components/
      layout/
        Layout.jsx
        Sidebar.jsx              # Role-aware navigation
        TopBar.jsx               # Notification bell, logout
      ui/
        Button.jsx / Input.jsx / Badge.jsx
        Modal.jsx / Table.jsx / Pagination.jsx
        Toast.jsx                # Toast notifications
        Skeleton.jsx             # Loading skeletons
        EmptyState.jsx           # Empty list states
        ConfirmDialog.jsx        # Confirm/cancel dialogs
    features/
      auth/
        LoginPage.jsx
        RegisterPage.jsx
      admin/
        Dashboard.jsx
        KpiCards.jsx
        RevenueChart.jsx / AppointmentTrendChart.jsx / BedOccupancyChart.jsx
        UserManagement.jsx
        ActivityLog.jsx
      appointments/
        AppointmentList.jsx
        BookAppointment.jsx
        StatusBadge.jsx
      patients/
        PatientList.jsx
        PatientDetail.jsx        # Tabs: Profile | History | Appointments | Bills
      doctor/
        ConsultationView.jsx
        DoctorSchedule.jsx
      ipd/
        WardMap.jsx
        AdmissionForm.jsx
        NursingNotes.jsx
      lab/
        LabQueue.jsx
        UploadResult.jsx
        ResultViewer.jsx
      billing/
        BillDetail.jsx           # Pay modal + PDF download
      pharmacy/
        Inventory.jsx
        DispensePrescription.jsx
      patient/
        PatientDashboard.jsx     # Role dashboard for patients
      receptionist/
        ReceptionistDashboard.jsx
      nurse/
        NurseDashboard.jsx
      profile/
        EditProfile.jsx
        ChangePassword.jsx
    hooks/
      useAuth.js
      useAppointments.js
      useNotifications.js        # Pusher channel subscriptions
    lib/
      echo.js                    # Laravel Echo + Pusher initialization (imported once in App.jsx)
    router/
      AppRouter.jsx
      ProtectedRoute.jsx         # Role guard
    store/
      authStore.js               # Zustand persist
      notificationStore.js
    utils/
      formatDate.js
      formatCurrency.js
    App.jsx                      # Root — QueryClient, BrowserRouter, Echo import
    app.js                       # Vite entry point
  css/
    app.css                      # @tailwind base / components / utilities
  views/
    app.blade.php                # SPA shell — loads Vite assets via @vite directive
    pdf/
      invoice.blade.php          # DomPDF invoice template
    emails/
      appointment-status.blade.php
```

### 6.2 Vite Configuration (Laravel Plugin)

```js
// vite.config.js (monorepo root)
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        react(),
    ],
});
```

### 6.3 SPA Shell (app.blade.php)

```html
<!-- resources/views/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hospital Management System</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="root"></div>
</body>
</html>
```

### 6.4 Laravel Echo Initialization

Echo and Pusher are initialized once in a dedicated `lib/echo.js` file and imported in `App.jsx`. This avoids scattering Pusher config across the codebase.

```js
// resources/js/lib/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

export default echo;
```

### 6.5 Role-Based Route Guard

```jsx
// resources/js/router/ProtectedRoute.jsx
const ProtectedRoute = ({ roles, children }) => {
    const { user } = useAuthStore();
    if (!user) return <Navigate to="/login" />;
    if (roles && !roles.includes(user.role)) return <Navigate to="/unauthorized" />;
    return children;
};
```

### 6.6 API Query Pattern (TanStack Query)

```js
// resources/js/hooks/useAppointments.js
export const useAppointments = (filters) =>
    useQuery({
        queryKey: ['appointments', filters],
        queryFn:  () => getAppointments(filters)
    });

export const useBookAppointment = () =>
    useMutation({
        mutationFn: bookAppointment,
        onSuccess:  () => queryClient.invalidateQueries(['appointments'])
    });
```

### 6.7 Axios Interceptor

```js
// resources/js/api/axios.js
api.interceptors.request.use(config => {
    const token = useAuthStore.getState().token;
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

api.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401) {
            useAuthStore.getState().logout();
            window.location.href = '/login';
        }
        return Promise.reject(err);
    }
);
```

---

## 7. Shared Packages

The `packages/` directory at the monorepo root contains three internal packages shared between the Laravel backend and the React frontend.

### 7.1 Directory Layout

```
packages/
  hms-core/                      # Shared domain constants and enums (PHP)
    Enums/
      AppointmentStatus.php
      BillStatus.php
      BedStatus.php
      LabRequestStatus.php
    composer.json                # {"name": "hms/core", "autoload": {"psr-4": {"Hms\\Core\\": ""}}}
  hms-notifications/             # Reusable notification channel logic (PHP)
    Channels/
      PusherChannel.php
    Templates/
      AppointmentMailTemplate.php
    composer.json
  hms-ui/                        # Shared React component library (JS)
    src/
      StatusBadge.jsx
      PatientCodeChip.jsx
      index.js
    package.json                 # {"name": "@hms/ui", "main": "src/index.js"}
```

### 7.2 Registering Local Packages

**PHP packages** — add path repositories to the root `composer.json`:

```json
{
  "repositories": [
    { "type": "path", "url": "./packages/hms-core" },
    { "type": "path", "url": "./packages/hms-notifications" }
  ],
  "require": {
    "hms/core": "@dev",
    "hms/notifications": "@dev"
  }
}
```

**JS package** — add a workspace entry to the root `package.json`:

```json
{
  "workspaces": ["packages/hms-ui"]
}
```

Then import in React:

```js
import { StatusBadge } from '@hms/ui';
```

### 7.3 hms-core Enum Example

```php
// packages/hms-core/Enums/AppointmentStatus.php
namespace Hms\Core\Enums;

enum AppointmentStatus: string
{
    case Pending    = 'pending';
    case Confirmed  = 'confirmed';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
}
```

---

## 8. Docker & Infrastructure

### 8.1 Docker Services

All services are defined in `docker-compose.yml` at the monorepo root. Production overrides live in `docker-compose.prod.yml`.

```
docker/
  php/
    Dockerfile               # PHP 8.3-FPM + required extensions
    php.ini                  # memory_limit, upload_max_filesize, etc.
  nginx/
    Dockerfile
    default.conf             # fastcgi_pass php:9000; serves public/ and API
  redis/
    redis.conf
  mysql/
    my.cnf
  supervisor/
    horizon.conf             # php artisan horizon process
    scheduler.conf           # php artisan schedule:run every minute
```

**docker-compose.yml (development)**

```yaml
services:
  php:
    build: ./docker/php
    volumes:
      - .:/var/www/html
    depends_on: [mysql, redis]

  nginx:
    build: ./docker/nginx
    ports: ["80:80", "443:443"]
    volumes:
      - .:/var/www/html
    depends_on: [php]

  mysql:
    image: mysql:8
    environment:
      MYSQL_DATABASE: hms
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data

  supervisor:
    build: ./docker/php
    command: supervisord -c /etc/supervisor/conf.d/
    volumes:
      - .:/var/www/html
      - ./docker/supervisor:/etc/supervisor/conf.d
    depends_on: [php, redis]

volumes:
  mysql_data:
  redis_data:
```

### 8.2 Nginx Configuration

```nginx
# docker/nginx/default.conf
server {
    listen 80;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 8.3 Infrastructure as Code (Terraform)

```
infrastructure/
  terraform/
    environments/
      production/
        main.tf              # VPS/EC2, managed DB, Redis, networking
        variables.tf
        outputs.tf
      staging/
        main.tf
    modules/
      compute/               # EC2 / DigitalOcean Droplet
      database/              # RDS MySQL or managed DB
      cache/                 # ElastiCache Redis
      networking/            # VPC, subnets, security groups
  scripts/
    deploy.sh                # git pull, composer install, npm run build, migrate, restart
    setup-server.sh          # nginx, php-fpm, supervisor install on a fresh VPS
    backup-db.sh             # mysqldump + S3 upload
  nginx/
    hms.conf                 # Production Nginx server block
```

**deploy.sh (key steps)**

```bash
#!/bin/bash
set -e
cd /var/www/hms
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart all
```

---

## 9. Step-by-Step Development Task List

> Work through this table row by row. Each row is an atomic task. Do not skip ahead — database and auth must exist before any feature module.

### Phase 0 — Environment & Monorepo Setup

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 0.1 | Install PHP 8.3, Composer, Node 20, MySQL 8, Redis | Verify: `php -v`, `composer -V`, `node -v`, `mysql -V`, `redis-cli ping` |
| 0.2 | Create unified Laravel 11 monorepo | `composer create-project laravel/laravel hms` — this is the single project root |
| 0.3 | Install all Laravel packages | Sanctum, spatie/permission, spatie/activitylog, dompdf, horizon |
| 0.4 | Install Vite + React inside Laravel | `npm install` at root; install react, @vitejs/plugin-react, laravel-vite-plugin |
| 0.5 | Configure `vite.config.js` | Use `laravel-vite-plugin` with input `['resources/css/app.css', 'resources/js/app.js']` |
| 0.6 | Create `resources/views/app.blade.php` | SPA shell with `@vite` and `@viteReactRefresh` directives. Catch-all route in `web.php`. |
| 0.7 | Install all React/JS packages | axios, react-router-dom, @tanstack/react-query, zustand, react-hook-form, zod, recharts, @headlessui/react, laravel-echo, pusher-js |
| 0.8 | Scaffold `resources/js/` structure | Create all directories: api/, components/, features/, hooks/, lib/, router/, store/, utils/ |
| 0.9 | Initialize `packages/` directory | Create `packages/hms-core`, `packages/hms-notifications`, `packages/hms-ui` with stubs |
| 0.10 | Register local packages | Add path repositories to root `composer.json`. Add workspace to root `package.json`. |
| 0.11 | Configure `.env` (DB, Redis, Mail, Pusher, Vite) | `DB_DATABASE=hms`, `QUEUE_CONNECTION=redis`, `BROADCAST_DRIVER=pusher`, `VITE_PUSHER_*` keys |
| 0.12 | Configure CORS in Laravel | `config/cors.php`: allow SPA origin, supports credentials |
| 0.13 | Set up Git repo and `.gitignore` | `.env` must be in `.gitignore`. Create `main` and `develop` branches. |
| 0.14 | Set up Docker for local dev | Build `docker-compose.yml` with php, nginx, mysql, redis, supervisor services. `docker-compose up -d`. |

### Phase 1 — Authentication

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 1.1 | Install and configure Sanctum | `php artisan vendor:publish --provider=SanctumServiceProvider`. Add `HasApiTokens` to User. |
| 1.2 | Create and run users migration | Fields: id, name, email, password, email_verified_at, deleted_at, timestamps |
| 1.3 | Set up Spatie roles and permissions | `php artisan vendor:publish --provider=PermissionServiceProvider`. Run migrations. |
| 1.4 | Seed 5 roles | `php artisan db:seed --class=RolePermissionSeeder`. Roles: admin, doctor, receptionist, nurse, patient. |
| 1.5 | Create `AuthController` | `register()`, `login()`, `logout()`, `me()`. All return JSON. |
| 1.6 | Define auth routes in `api.php` | `POST /register`, `/login`, `/logout`, `GET /me`. No auth middleware on register/login. |
| 1.7 | Create `RegisterRequest` + `LoginRequest` | Validate email unique, password min:8, required fields. |
| 1.8 | Create `UserResource` | Return id, name, email, role. Never return password. |
| 1.9 | Build React login page | `resources/js/features/auth/LoginPage.jsx`. POST to `/auth/login`. Store token in Zustand. |
| 1.10 | Build Axios interceptor | `resources/js/api/axios.js`. Attach Bearer token. On 401, clear store and redirect to `/login`. |
| 1.11 | Build role-based router | `resources/js/router/ProtectedRoute.jsx`. Each dashboard route guarded by required role. |
| 1.12 | Test auth end-to-end | Register → login → get token → call `/me` → logout → token revoked. |

### Phase 2 — Patient Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 2.1 | Create `patients` migration | id, user_id (FK), patient_code (unique), dob, blood_type, gender, deleted_at |
| 2.2 | Create `allergies` + `emergency_contacts` migrations | FK to patient_id. `ON DELETE CASCADE`. |
| 2.3 | Create `medical_histories` migration | FK to patient_id and doctor_id. |
| 2.4 | Create Patient, Allergy, EmergencyContact models | Relationships: Patient hasMany Allergies, hasMany EmergencyContacts, belongsTo User. |
| 2.5 | Define `AppointmentStatus` enum in hms-core | `packages/hms-core/Enums/AppointmentStatus.php`. Register via composer path repo. |
| 2.6 | Auto-generate `patient_code` on registration | Observer: `PatientObserver@created` generates HMS-YYYY-XXXXX. |
| 2.7 | Create `PatientService` | Methods: `create()`, `update()`, `getMedicalHistory()`. |
| 2.8 | Create `PatientController` (index, show, store, update) | All JSON via `PatientResource`. Gate by role. |
| 2.9 | React: Patient list page (Admin/Receptionist) | `features/patients/PatientList.jsx`. Table with search, pagination. |
| 2.10 | React: Patient detail page | `features/patients/PatientDetail.jsx`. Tabs: Profile \| Medical History \| Appointments \| Bills. |

### Phase 3 — Doctor & Schedule Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 3.1 | Create `doctors` migration | id, user_id (FK), specialization, qualification, fee, deleted_at |
| 3.2 | Create `doctor_schedules` migration | id, doctor_id (FK), day_of_week (0–6), is_active |
| 3.3 | Create `time_slots` migration | id, doctor_schedule_id (FK), start_time, end_time, is_blocked |
| 3.4 | Seed weekly slots on doctor creation | Observer creates Mon–Fri schedule with 30-min slots on doctor creation. |
| 3.5 | Create `DoctorController` | index, show, store, slots endpoint (`?date=`). Use `DoctorService`. |
| 3.6 | Slots endpoint logic | Filter slots: date, day_of_week match, not blocked, not already booked. |
| 3.7 | React: Doctor list | `features/doctor/` — Cards with name, specialization, fee. |
| 3.8 | React: Doctor schedule management (Admin + Doctor) | Weekly grid UI. Toggle slots blocked/available. |

### Phase 4 — Appointment Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 4.1 | Create `appointments` migration | All fields per schema. Status enum with default `pending`. |
| 4.2 | Create `appointment_logs` migration | Tracks every status change with actor. |
| 4.3 | `AppointmentService: book()` | Check slot available, create record, dispatch `SendAppointmentEmail` job, log status. |
| 4.4 | `AppointmentService: updateStatus()` | Validate allowed transitions. Update status, log it, dispatch notification. |
| 4.5 | Create `AppointmentStatusMail` Mailable | `app/Mail/AppointmentStatusMail.php`. Uses `views/emails/appointment-status.blade.php`. |
| 4.6 | Create `SendAppointmentEmail` Job | Queued job — dispatches `AppointmentStatusMail` via `Mail::to()->send()`. |
| 4.7 | Create `AppointmentController` | index, store, show, updateStatus, destroy. Guard by role. |
| 4.8 | React: Booking flow (Patient) | Step 1: pick doctor. Step 2: pick date. Step 3: pick slot. Step 4: confirm. |
| 4.9 | React: Appointment management (Receptionist) | `features/receptionist/ReceptionistDashboard.jsx`. Table with status filter and quick actions. |
| 4.10 | React: Doctor's daily schedule view | `features/doctor/ConsultationView.jsx`. Today's appointments. Click to open patient record. |
| 4.11 | Configure Laravel Horizon | `php artisan horizon:install`. Supervisor `horizon.conf` auto-runs it in Docker. |

### Phase 5 — OPD Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 5.1 | Create `diagnoses` migration and model | FK to appointment, doctor, patient. `icd_code` nullable. |
| 5.2 | Create `prescriptions` + `prescription_items` migrations | Cascade delete items on prescription delete. |
| 5.3 | `DiagnosisController` + `PrescriptionController` | Only doctors can create. Patients can only read their own. |
| 5.4 | React: Doctor consultation view | Forms for diagnosis + prescription side by side in `features/doctor/ConsultationView.jsx`. |
| 5.5 | React: Prescription detail (Patient view) | Read-only. Show medicine name, dosage, frequency, duration. |

### Phase 6 — Lab Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 6.1 | Create `lab_tests`, `lab_requests`, `lab_results` migrations | Seed default lab tests (CBC, Urinalysis, Blood Glucose, etc.). |
| 6.2 | Define `LabRequestStatus` enum in hms-core | `packages/hms-core/Enums/LabRequestStatus.php`. |
| 6.3 | `LabRequestController: store` | Doctor creates request. Status defaults to `requested`. |
| 6.4 | `LabResultController: update` | Lab technician uploads file and enters notes. Status → `completed`. |
| 6.5 | File upload handling | Store in `storage/app/private/lab-results/`. Return signed URL to authorized users. |
| 6.6 | Broadcast `LabResultUploaded` event | After result uploaded, broadcast to doctor and patient via Pusher. |
| 6.7 | React: Lab queue (Lab Tech view) | `features/lab/LabQueue.jsx`. Pending requests list. Click to upload result. |
| 6.8 | React: Lab result viewer (Doctor + Patient) | `features/lab/ResultViewer.jsx`. Notes, file download, `is_abnormal` flag highlighted. |

### Phase 7 — IPD Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 7.1 | Create `wards`, `beds` migrations | Seed 3 wards (General, ICU, Private) with beds. |
| 7.2 | Define `BedStatus` enum in hms-core | `packages/hms-core/Enums/BedStatus.php`. |
| 7.3 | Create `admissions`, `nursing_notes` migrations | Cascade nursing_notes on admission delete. |
| 7.4 | `AdmissionController: store` | Admit patient — assign bed, set status=occupied. Create admission record. |
| 7.5 | `AdmissionController: discharge` | Set `discharged_at`, free bed (status=available). |
| 7.6 | `NursingNoteController: store, index` | Nurse can add notes. Doctor and nurse can view. |
| 7.7 | React: Ward/Bed map (Admin + Nurse) | `features/ipd/WardMap.jsx`. Visual grid. Green=available, Red=occupied. |
| 7.8 | React: Admission form | `features/ipd/AdmissionForm.jsx`. Select patient, doctor, ward, bed. |
| 7.9 | React: Nursing notes timeline | `features/ipd/NursingNotes.jsx`. Chronological list. Add note form at bottom. |
| 7.10 | React: Nurse dashboard | `features/nurse/NurseDashboard.jsx`. Quick access to ward map and admissions. |

### Phase 8 — Billing Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 8.1 | Create `bills`, `bill_items`, `payments` migrations | Index on patient_id, status. |
| 8.2 | Define `BillStatus` enum in hms-core | `packages/hms-core/Enums/BillStatus.php`. |
| 8.3 | `BillingService: generateBill()` | Aggregate: consultation fee + lab fees + bed days × ward rate + medicine cost. |
| 8.4 | `BillingService: recordPayment()` | Create payment record. Update `paid_amount`. If `paid_amount >= total`, set `status=paid`. |
| 8.5 | `BillingController` (generate, show, pay, pdf) | Authorize: Admin/Receptionist generate; patients view own. |
| 8.6 | Create PDF invoice Blade template | `resources/views/pdf/invoice.blade.php`. Logo, patient info, itemized table, total. |
| 8.7 | PDF endpoint: `GET /api/bills/{id}/pdf` | Generate with DomPDF, stream as `application/pdf`. |
| 8.8 | React: Bill detail page | `features/billing/BillDetail.jsx`. Itemized table. Pay button + PDF download. |
| 8.9 | React: Payment modal | Amount field, payment method selector. Optimistic update via TanStack Query. |

### Phase 9 — Pharmacy Module

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 9.1 | Create `medicines`, `medicine_batches`, `dispensings` migrations | Seed 20 sample medicines. |
| 9.2 | `MedicineController` (index, store, updateStock) | Admin manages inventory. Low stock check on every update. |
| 9.3 | Low stock alert job | `LowStockAlert` job → email admin when stock < threshold. |
| 9.4 | `DispensingController: store` | Record fulfillment. Deduct from `medicine_batches` (FIFO by expiry). |
| 9.5 | React: Pharmacy inventory page | `features/pharmacy/Inventory.jsx`. Table with stock level alerts. |
| 9.6 | React: Dispense prescription view | `features/pharmacy/DispensePrescription.jsx`. Confirm availability and submit. |

### Phase 10 — Real-Time & Notifications

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 10.1 | Configure Laravel Echo on backend | `BROADCAST_DRIVER=pusher`. Set Pusher credentials in `.env` and `VITE_PUSHER_*` for frontend. |
| 10.2 | Create broadcastable events | `AppointmentStatusChanged`, `LabResultUploaded` in `app/Events/`. |
| 10.3 | Initialize Laravel Echo in `lib/echo.js` | `resources/js/lib/echo.js`. Import Pusher, initialize Echo instance, export. |
| 10.4 | Import Echo in `App.jsx` | `import './lib/echo'` at the top of `resources/js/App.jsx`. |
| 10.5 | Subscribe to channels in `useNotifications.js` | `resources/js/hooks/useNotifications.js`. Private channels per user. Toast on event. |
| 10.6 | Notification bell component | `components/layout/TopBar.jsx`. Badge with unread count. Dropdown with recent notifications. |
| 10.7 | Store notifications in DB | `php artisan notifications:table`. Mark as read API endpoint. |

### Phase 11 — Admin Dashboard

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 11.1 | Create `/api/admin/stats` endpoint | Return: total patients, doctors, appointments today, revenue this month. |
| 11.2 | Create trend endpoints | Monthly appointment count and revenue for last 12 months. |
| 11.3 | React: KPI cards component | `features/admin/KpiCards.jsx`. 4 cards: Patients, Appointments, Revenue, Bed Occupancy. |
| 11.4 | React: Appointment trend chart | `features/admin/AppointmentTrendChart.jsx`. Recharts `LineChart`. |
| 11.5 | React: Revenue trend chart | `features/admin/RevenueChart.jsx`. Recharts `BarChart`. |
| 11.6 | React: Bed occupancy chart | Recharts `PieChart`. Available vs Occupied across all wards. |
| 11.7 | React: Activity log page | `features/admin/ActivityLog.jsx`. Paginated Spatie activity log. |
| 11.8 | React: User management page | `features/admin/UserManagement.jsx`. List staff. Create account. Change role. |
| 11.9 | React: Patient dashboard | `features/patient/PatientDashboard.jsx`. Upcoming appointments, recent bills, lab results. |
| 11.10 | React: Receptionist dashboard | `features/receptionist/ReceptionistDashboard.jsx`. Today's appointments, quick booking access. |

### Phase 12 — Testing

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 12.1 | Write Feature tests for Auth | `tests/Feature/Auth/AuthTest.php` — register, login, logout, 403. |
| 12.2 | Write Feature tests for Appointments | `tests/Feature/AppointmentTest.php` — book, status flow, double-book prevention. |
| 12.3 | Write Feature tests for Billing | `tests/Feature/BillingTest.php` — generate, pay, PDF download. |
| 12.4 | Write Feature tests for Lab | `tests/Feature/LabTest.php` — request, upload, view. |
| 12.5 | Write Feature tests for IPD | `tests/Feature/IpdTest.php` — admit, discharge, nursing notes. |
| 12.6 | Write Unit tests for Services | `tests/Unit/AppointmentServiceTest.php`, `BillingServiceTest.php`. |
| 12.7 | Role-based access tests | Assert 403 when wrong role hits protected endpoint. |
| 12.8 | E2E browser test (Laravel Dusk) | `tests/Browser/PatientJourneyTest.php` — full lifecycle: register → book → consult → lab → pay. |
| 12.9 | React: Smoke test all pages | Manually navigate every route for all 5 roles. Check for console errors. |

### Phase 13 — Deployment

#### Option A: Docker (Recommended)

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 13.1 | Build production Docker images | `docker-compose -f docker-compose.prod.yml build` |
| 13.2 | Configure production `.env` | `APP_ENV=production`, `APP_DEBUG=false`, real DB/Redis/Pusher credentials |
| 13.3 | Run migrations via Docker | `docker-compose exec php php artisan migrate --force` |
| 13.4 | Seed production data | `docker-compose exec php php artisan db:seed --class=RolePermissionSeeder` |
| 13.5 | Build frontend assets | `npm run build` produces `public/build/` — included in the PHP Docker image |
| 13.6 | Verify Supervisor processes | `docker-compose exec supervisor supervisorctl status` — horizon and scheduler must be RUNNING |
| 13.7 | Set up SSL | Certbot or load-balancer termination for `your-domain.com` |
| 13.8 | Final health check | Test all roles end-to-end. Confirm emails, PDFs, realtime, queues working. |

#### Option B: Terraform + VPS (IaC)

| # | Task | Details / Acceptance Criteria |
|---|---|---|
| 13.9 | Provision infrastructure | `cd infrastructure/terraform/environments/production && terraform apply` |
| 13.10 | Run setup script on fresh VPS | `bash infrastructure/scripts/setup-server.sh` — installs nginx, php-fpm, supervisor |
| 13.11 | Deploy application | `bash infrastructure/scripts/deploy.sh` — pulls code, installs deps, builds assets, migrates |
| 13.12 | Schedule database backups | `bash infrastructure/scripts/backup-db.sh` via cron — mysqldump + S3 upload |
| 13.13 | Configure Nginx production block | `infrastructure/nginx/hms.conf` — production server block with SSL |

---

## 10. Security Design

### 10.1 Authentication & Authorization

- All API routes (except register/login) require a valid Sanctum Bearer token
- Tokens are scoped to the authenticated user and revoked on logout
- Role-based middleware (`role:doctor`) applied at the route group level — not inside controllers
- Policies (`PatientPolicy`, `BillPolicy`, `LabResultPolicy`) enforce resource-level ownership checks
- Domain enums from `hms-core` are used for all status validations — no magic strings

### 10.2 Data Validation

- All incoming data validated by `FormRequest` classes before reaching controllers
- String fields are sanitized against XSS. SQL injection prevented by Eloquent parameterized queries
- File uploads (lab results) stored in private storage — never publicly accessible. Signed temporary URLs used for access

### 10.3 Environment Security

- All secrets stored in `.env` — never committed to version control
- `VITE_*` prefixed variables are the only `.env` values exposed to the browser bundle
- Production `.env` has `APP_DEBUG=false` and `APP_ENV=production`
- Database user for the application has only `SELECT`, `INSERT`, `UPDATE`, `DELETE` — no `DROP` or `CREATE`
- Rate limiting applied to `/auth/login` (5 attempts per minute)
- Docker: PHP-FPM runs as a non-root user; MySQL and Redis are not exposed on host ports in production

---

## 11. Key Commands Reference

### 11.1 Laravel (Backend)

```bash
# Install all packages
composer require laravel/sanctum spatie/laravel-permission \
  spatie/laravel-activitylog barryvdh/laravel-dompdf laravel/horizon

# Publish configs
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"

# Migrations
php artisan migrate
php artisan migrate:fresh --seed   # development reset

# Queue & Horizon
php artisan horizon                # run in dev (Supervisor handles this in Docker)
php artisan queue:work             # basic worker

# Make common classes
php artisan make:controller Api/AppointmentController --api
php artisan make:request StoreAppointmentRequest
php artisan make:resource AppointmentResource
php artisan make:mail AppointmentStatusMail
php artisan make:job SendAppointmentEmail
php artisan make:event AppointmentStatusChanged
php artisan make:observer AppointmentObserver --model=Appointment
php artisan make:policy AppointmentPolicy --model=Appointment

# Testing
php artisan test --parallel

# Production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear    # clear all caches
```

### 11.2 React / Vite (Frontend — run from monorepo root)

```bash
# Install all packages (run once at monorepo root)
npm install

# Dev server (Vite HMR — runs alongside php artisan serve or Docker)
npm run dev

# Production build — outputs to public/build/
npm run build

# Preview production build locally
npm run preview
```

### 11.3 Docker

```bash
# Start all services (development)
docker-compose up -d

# Run artisan commands inside container
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed

# Build and run frontend inside container
docker-compose exec php npm run build

# View logs
docker-compose logs -f php
docker-compose logs -f supervisor

# Stop all services
docker-compose down

# Production build
docker-compose -f docker-compose.prod.yml up -d --build
```

### 11.4 Terraform

```bash
cd infrastructure/terraform/environments/production

terraform init
terraform plan
terraform apply

# Tear down staging
cd ../staging && terraform destroy
```

---

## 12. Appendix — Status Flows & Enums

### 12.1 Appointment Status Flow

```
pending  →  confirmed  →  in_progress  →  completed
                 ↘                 ↘
               cancelled          cancelled

Rules:
  pending     → confirmed    (by Receptionist or Admin)
  confirmed   → in_progress  (by Doctor, when patient arrives)
  in_progress → completed    (by Doctor, after consultation)
  pending/confirmed → cancelled (by Patient, Receptionist, or Admin)
```

Defined in `packages/hms-core/Enums/AppointmentStatus.php`.

### 12.2 Bill Status Flow

```
draft  →  issued  →  partial  →  paid

  draft   : auto-generated, not yet sent to patient
  issued  : patient can view and pay
  partial : at least one payment made, balance remains
  paid    : paid_amount >= total_amount
```

Defined in `packages/hms-core/Enums/BillStatus.php`.

### 12.3 Bed Status Enum

| Value | Meaning |
|---|---|
| `available` | Bed is empty and bookable |
| `occupied` | Patient currently admitted |
| `maintenance` | Temporarily out of service |

Defined in `packages/hms-core/Enums/BedStatus.php`.

### 12.4 Lab Request Status Enum

| Value | Meaning |
|---|---|
| `requested` | Doctor ordered test, waiting for lab |
| `processing` | Lab received sample, running test |
| `completed` | Result uploaded |

Defined in `packages/hms-core/Enums/LabRequestStatus.php`.

### 12.5 Blood Type Enum

```
A+  A-  B+  B-  AB+  AB-  O+  O-
```

### 12.6 Payment Method Enum

```
cash  |  card  |  online
```

### 12.7 Ward Type Enum

```
general  |  icu  |  private
```

### 12.8 Environment Variables Reference

```env
APP_NAME="Hospital Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql                  # Docker service name; use 127.0.0.1 for bare VPS
DB_PORT=3306
DB_DATABASE=hms
DB_USERNAME=hms_user
DB_PASSWORD=your_secure_password

QUEUE_CONNECTION=redis
REDIS_HOST=redis               # Docker service name; use 127.0.0.1 for bare VPS
REDIS_PORT=6379

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=ap1

# Vite exposes these to the browser bundle
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@hms.com
MAIL_FROM_NAME="HMS System"

FILESYSTEM_DISK=local
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,your-domain.com
```

### 12.9 Role Capabilities Summary

| Role | Capabilities |
|---|---|
| `admin` | Full system access — manages all staff, views analytics, manages all modules |
| `doctor` | Views own appointments, writes diagnoses/prescriptions, orders and views lab tests |
| `receptionist` | Books appointments, confirms/cancels, assists with billing and payments |
| `nurse` | Manages IPD nursing notes, views admissions and ward occupancy |
| `patient` | Self-registers, books appointments, views own records, bills, lab results |

### 12.10 Monorepo Root File Map

```
hms/                               ← unified monorepo root
├─ app/                            ← Laravel backend
├─ resources/
│  ├─ js/                          ← React 18 SPA
│  ├─ css/app.css                  ← Tailwind
│  └─ views/
│     ├─ app.blade.php             ← SPA shell
│     ├─ pdf/invoice.blade.php     ← DomPDF template
│     └─ emails/                   ← Mail Blade templates
├─ packages/
│  ├─ hms-core/                    ← shared PHP enums
│  ├─ hms-notifications/           ← shared notification logic
│  └─ hms-ui/                      ← shared React components
├─ docker/                         ← container definitions
├─ infrastructure/
│  ├─ terraform/                   ← IaC for prod + staging
│  ├─ scripts/                     ← deploy.sh, setup-server.sh, backup-db.sh
│  └─ nginx/hms.conf               ← production Nginx block
├─ database/
│  ├─ migrations/
│  ├─ seeders/
│  └─ factories/
├─ tests/
│  ├─ Feature/
│  ├─ Unit/
│  └─ Browser/                     ← Laravel Dusk E2E
├─ routes/api.php
├─ bootstrap/app.php
├─ composer.json                   ← includes hms-core + hms-notifications path repos
├─ package.json                    ← includes hms-ui workspace
├─ vite.config.js                  ← laravel-vite-plugin + react plugin
├─ tailwind.config.js
├─ docker-compose.yml
├─ docker-compose.prod.yml
└─ .env
```

---

*HMS Software Design Document — Version 2.0 — June 2026*
*Laravel 11 + React 18 + MySQL 8 — Unified Monorepo*
