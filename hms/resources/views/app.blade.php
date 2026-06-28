<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hospital Management System — Manage patients, appointments, billing, lab, IPD and more.">
    <title>Hospital Management System</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="root"></div>
</body>
</html>
