<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All web requests are routed to the React SPA shell. React Router handles
| client-side navigation. API routes are defined in routes/api.php.
|
*/

Route::get('/{any}', fn () => view('app'))->where('any', '.*');
