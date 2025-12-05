<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Sla\SlaCalculatorService;
use Illuminate\Console\Command;

class RecalculateSlaDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:recalculate {--all : Recalculate all orders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate SLA deadlines for orders missing them';

    /**
     * Execute the console command.
     */
    public function handle(SlaCalculatorService $calculator): int
    {
        $this->info('Starting SLA deadline recalculation...');

        $query = Order::query()->whereNotNull('ordered_at');

        if (!$this->option('all')) {
            $query->whereNull('sla_deadline');
            $this->info('Recalculating only orders without SLA deadlines');
        } else {
            $this->info('Recalculating ALL orders');
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found to recalculate.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        $successCount = 0;
        $failureCount = 0;

        foreach ($orders as $order) {
            try {
                $deadline = $calculator->calculateDeadline($order);
                if ($deadline) {
                    $order->update(['sla_deadline' => $deadline]);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to calculate SLA for order {$order->increment_id}: {$e->getMessage()}");
                $failureCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recalculation complete:");
        $this->info("  - Success: {$successCount} orders");
        if ($failureCount > 0) {
            $this->warn("  - Failed: {$failureCount} orders");
        }

        return Command::SUCCESS;
    }
}
