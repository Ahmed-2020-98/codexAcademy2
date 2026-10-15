<?php

use App\Http\Controllers\Api\GoogleFormsWebhookController;
use App\Http\Controllers\Api\SchedulerController;
use Illuminate\Support\Facades\Route;

Route::post('internal/scheduler', SchedulerController::class)
    ->middleware('throttle:5,1');

// Google Forms → CRM intake (see docs/google-forms.md). Throttled and secret-protected.
Route::post('webhooks/google-forms', GoogleFormsWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.google-forms');
