<?php

namespace App\Providers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Observers\PatientObserver;
use App\Observers\DoctorObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-generate HMS-YYYY-XXXXX patient_code on create
        Patient::observe(PatientObserver::class);

        // Auto-seed doctor schedule and slots on create
        Doctor::observe(DoctorObserver::class);
    }
}
