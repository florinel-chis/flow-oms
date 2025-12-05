<?php

use App\Jobs\MonitorSlaBreachesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ==========================================
// Magento Order Sync - Tiered Schedule
// ==========================================

// Incremental sync every 30 minutes (last 24 hours)
// Keeps dashboard near real-time without excessive API calls
Schedule::command('magento:sync-orders --days=1')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Daily deep sync (last 7 days)
// Ensures complete week history and catches any missed updates
Schedule::command('magento:sync-orders --days=7')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

// Weekly safety net (last 30 days)
// Comprehensive backfill to catch any data gaps
Schedule::command('magento:sync-orders --days=30')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// ==========================================
// Unpaid Order Automation
// ==========================================

// Process unpaid orders every 15 minutes
// Sends warning notifications and cancels orders based on tenant thresholds
Schedule::command('orders:process-unpaid')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// ==========================================
// SLA Monitoring
// ==========================================

// Monitor SLA breaches every 15 minutes
// Detects imminent breaches (< 2 hours) and actual breaches
// Sends notifications via webhook to external systems
Schedule::job(new MonitorSlaBreachesJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
