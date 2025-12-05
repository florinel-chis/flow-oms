<?php

namespace App\Console\Commands;

use App\Jobs\CancelUnpaidOrderJob;
use App\Jobs\SendUnpaidOrderWarningJob;
use App\Models\Tenant;
use App\Services\UnpaidOrderProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Process unpaid orders to send warnings and cancel orders
 *
 * This command runs on a schedule (every 15 minutes) to:
 * 1. Find orders that have been unpaid for X hours and send warnings
 * 2. Find orders that have been unpaid for Y hours and cancel them
 *
 * Part of the Automated Unpaid Order Management System.
 */
class ProcessUnpaidOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-unpaid
                            {--tenant= : Process only this tenant ID}
                            {--dry-run : Show what would be processed without dispatching jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process unpaid orders and send warnings or cancel orders based on thresholds';

    /**
     * Execute the console command.
     */
    public function handle(UnpaidOrderProcessor $processor): int
    {
        $dryRun = $this->option('dry-run');
        $specificTenantId = $this->option('tenant');

        $this->info('🔍 Processing unpaid orders...');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No jobs will be dispatched');
        }

        // Get tenants to process
        $tenants = $specificTenantId
            ? Tenant::where('id', $specificTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');
            return Command::SUCCESS;
        }

        $totalWarnings = 0;
        $totalCancellations = 0;

        foreach ($tenants as $tenant) {
            $this->processTenant($tenant, $processor, $dryRun, $totalWarnings, $totalCancellations);
        }

        // Summary
        $this->newLine();
        $this->info("✅ Processing complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Tenants Processed', $tenants->count()],
                ['Warnings ' . ($dryRun ? '(would send)' : 'Dispatched'), $totalWarnings],
                ['Cancellations ' . ($dryRun ? '(would send)' : 'Dispatched'), $totalCancellations],
            ]
        );

        Log::info('ProcessUnpaidOrdersCommand completed', [
            'tenants_processed' => $tenants->count(),
            'warnings_dispatched' => $totalWarnings,
            'cancellations_dispatched' => $totalCancellations,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Process a single tenant
     */
    private function processTenant(
        Tenant $tenant,
        UnpaidOrderProcessor $processor,
        bool $dryRun,
        int &$totalWarnings,
        int &$totalCancellations
    ): void {
        $this->line(""); // Blank line
        $this->info("📦 Processing tenant: {$tenant->name} (ID: {$tenant->id})");

        // Check if feature is enabled
        if (!$processor->isEnabled($tenant)) {
            $this->warn("  ⏭️  Skipped - Feature not enabled for this tenant");
            return;
        }

        // Validate configuration
        $validation = $processor->validateConfig($tenant);
        if (!$validation['valid']) {
            $this->error("  ❌ Invalid configuration:");
            foreach ($validation['errors'] as $error) {
                $this->error("     • {$error}");
            }
            return;
        }

        $config = $processor->getConfig($tenant);

        // Process warnings
        $warningCount = $this->processWarnings($tenant, $processor, $config, $dryRun);
        $totalWarnings += $warningCount;

        // Process cancellations
        $cancellationCount = $this->processCancellations($tenant, $processor, $config, $dryRun);
        $totalCancellations += $cancellationCount;

        if ($warningCount === 0 && $cancellationCount === 0) {
            $this->info("  ✓ No unpaid orders to process");
        }
    }

    /**
     * Process warning notifications for a tenant
     */
    private function processWarnings(
        Tenant $tenant,
        UnpaidOrderProcessor $processor,
        array $config,
        bool $dryRun
    ): int {
        $orders = $processor->findOrdersNeedingWarning($tenant);

        if ($orders->isEmpty()) {
            return 0;
        }

        $this->warn("  ⚠️  Found {$orders->count()} order(s) needing warning notification:");

        foreach ($orders as $order) {
            $hoursUnpaid = $processor->calculateHoursUnpaid($order);
            $hoursRemaining = $config['cancellation_threshold_hours'] - $hoursUnpaid;

            $this->line("     • Order #{$order->increment_id} - {$hoursUnpaid}h unpaid ({$hoursRemaining}h until cancellation)");

            if (!$dryRun) {
                SendUnpaidOrderWarningJob::dispatch(
                    $order,
                    $config['warning_endpoint_url'],
                    $hoursUnpaid,
                    $hoursRemaining,
                    $config['warning_threshold_hours'],
                    $config['cancellation_threshold_hours']
                );
            }
        }

        return $orders->count();
    }

    /**
     * Process cancellations for a tenant
     */
    private function processCancellations(
        Tenant $tenant,
        UnpaidOrderProcessor $processor,
        array $config,
        bool $dryRun
    ): int {
        $orders = $processor->findOrdersNeedingCancellation($tenant);

        if ($orders->isEmpty()) {
            return 0;
        }

        $this->error("  🚫 Found {$orders->count()} order(s) to cancel:");

        foreach ($orders as $order) {
            $hoursUnpaid = $processor->calculateHoursUnpaid($order);

            $this->line("     • Order #{$order->increment_id} - {$hoursUnpaid}h unpaid (cancelling now)");

            if (!$dryRun) {
                CancelUnpaidOrderJob::dispatch(
                    $order,
                    $config['cancellation_endpoint_url'],
                    $hoursUnpaid,
                    $config['cancellation_threshold_hours']
                );
            }
        }

        return $orders->count();
    }
}

