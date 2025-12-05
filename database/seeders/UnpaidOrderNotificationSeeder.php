<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnpaidOrderNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Generating test unpaid order notifications...');

        // Get tenant and unpaid orders
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->command->error('No tenant found. Please run TenantSeeder first.');

            return;
        }

        // Get any orders to use for demo notifications (prefer unpaid, but use any if needed)
        $unpaidOrders = Order::where('tenant_id', $tenant->id)
            ->where('payment_status', 'pending')
            ->limit(20)
            ->get();

        // If not enough unpaid orders, use any orders for demo purposes
        if ($unpaidOrders->count() < 20) {
            $this->command->warn("Only {$unpaidOrders->count()} unpaid orders found. Using any available orders for demo notifications...");

            $additionalOrders = Order::where('tenant_id', $tenant->id)
                ->whereNotIn('id', $unpaidOrders->pluck('id'))
                ->limit(20 - $unpaidOrders->count())
                ->get();

            if ($additionalOrders->isEmpty() && $unpaidOrders->isEmpty()) {
                $this->command->error('No orders found. Please run OrderSeeder first.');
                return;
            }

            $unpaidOrders = $unpaidOrders->merge($additionalOrders);
            $this->command->info("Using {$unpaidOrders->count()} orders total for demo notifications.");
        }

        $this->command->info("Using {$unpaidOrders->count()} orders for notifications.");

        $notifications = [];
        $now = now();
        $totalOrders = $unpaidOrders->count();

        // Calculate distribution based on available orders
        $warningCount = (int) ($totalOrders * 0.5); // 50% warnings
        $cancellationCount = (int) ($totalOrders * 0.25); // 25% cancellations
        $failedWarningCount = (int) ($totalOrders * 0.15); // 15% failed warnings
        $failedCancellationCount = $totalOrders - $warningCount - $cancellationCount - $failedWarningCount; // Rest as failed cancellations

        $currentIndex = 0;

        // Scenario 1: Successful warnings
        $this->command->info("Creating {$warningCount} successful warning notifications...");
        foreach ($unpaidOrders->slice($currentIndex, $warningCount) as $index => $order) {
            $currentIndex++;
            $hoursUnpaid = 24 + ($index * 2); // 24, 26, 28... hours
            $triggeredAt = $now->copy()->subHours(rand(1, 48));

            $notifications[] = [
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'notification_type' => 'warning',
                'triggered_at' => $triggeredAt,
                'hours_unpaid' => $hoursUnpaid,
                'endpoint_url' => 'https://api.example.com/webhooks/order-warning',
                'payload' => json_encode($this->buildWarningPayload($order, $hoursUnpaid, $triggeredAt)),
                'response_status' => 200,
                'response_body' => json_encode(['status' => 'success', 'message' => 'Notification received']),
                'sent_successfully' => true,
                'retry_count' => 0,
                'last_retry_at' => null,
                'error_message' => null,
                'created_at' => $triggeredAt,
                'updated_at' => $triggeredAt,
            ];
        }

        // Scenario 2: Successful cancellations
        $this->command->info("Creating {$cancellationCount} successful cancellation notifications...");
        foreach ($unpaidOrders->slice($currentIndex, $cancellationCount) as $index => $order) {
            $currentIndex++;
            $hoursUnpaid = 72 + ($index * 6); // 72, 78, 84... hours
            $triggeredAt = $now->copy()->subHours(rand(1, 24));

            $notifications[] = [
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'notification_type' => 'cancellation',
                'triggered_at' => $triggeredAt,
                'hours_unpaid' => $hoursUnpaid,
                'endpoint_url' => 'https://api.example.com/webhooks/order-cancelled',
                'payload' => json_encode($this->buildCancellationPayload($order, $hoursUnpaid, $triggeredAt)),
                'response_status' => 200,
                'response_body' => json_encode(['status' => 'success', 'message' => 'Order cancellation processed']),
                'sent_successfully' => true,
                'retry_count' => 0,
                'last_retry_at' => null,
                'error_message' => null,
                'created_at' => $triggeredAt,
                'updated_at' => $triggeredAt,
            ];
        }

        // Scenario 3: Failed warnings - 4xx errors
        $this->command->info("Creating {$failedWarningCount} failed warning notifications (4xx errors)...");
        $failureScenarios = [
            ['status' => 400, 'error' => 'Bad Request: Invalid payload format'],
            ['status' => 401, 'error' => 'Unauthorized: Invalid API key'],
            ['status' => 404, 'error' => 'Not Found: Endpoint does not exist'],
        ];

        foreach ($unpaidOrders->slice($currentIndex, $failedWarningCount) as $index => $order) {
            $currentIndex++;
            $scenario = $failureScenarios[$index % count($failureScenarios)];
            $hoursUnpaid = 30 + ($index * 5);
            $triggeredAt = $now->copy()->subHours(rand(1, 72));

            $notifications[] = [
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'notification_type' => 'warning',
                'triggered_at' => $triggeredAt,
                'hours_unpaid' => $hoursUnpaid,
                'endpoint_url' => 'https://api.example.com/webhooks/order-warning',
                'payload' => json_encode($this->buildWarningPayload($order, $hoursUnpaid, $triggeredAt)),
                'response_status' => $scenario['status'],
                'response_body' => json_encode(['error' => $scenario['error']]),
                'sent_successfully' => false,
                'retry_count' => 3,
                'last_retry_at' => $triggeredAt->copy()->addMinutes(90),
                'error_message' => $scenario['error'],
                'created_at' => $triggeredAt,
                'updated_at' => $triggeredAt->copy()->addMinutes(90),
            ];
        }

        // Scenario 4: Failed cancellations - 5xx errors with retries
        $this->command->info("Creating {$failedCancellationCount} failed cancellation notifications (5xx errors)...");
        $serverErrors = [
            ['status' => 500, 'error' => 'Internal Server Error: Database connection failed', 'retries' => 2],
            ['status' => 503, 'error' => 'Service Unavailable: Maintenance mode', 'retries' => 1],
        ];

        foreach ($unpaidOrders->slice($currentIndex, $failedCancellationCount) as $index => $order) {
            $scenario = $serverErrors[$index % count($serverErrors)];
            $hoursUnpaid = 75 + ($index * 5);
            $triggeredAt = $now->copy()->subHours(rand(1, 48));

            $notifications[] = [
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'notification_type' => 'cancellation',
                'triggered_at' => $triggeredAt,
                'hours_unpaid' => $hoursUnpaid,
                'endpoint_url' => 'https://api.example.com/webhooks/order-cancelled',
                'payload' => json_encode($this->buildCancellationPayload($order, $hoursUnpaid, $triggeredAt)),
                'response_status' => $scenario['status'],
                'response_body' => json_encode(['error' => $scenario['error'], 'timestamp' => $triggeredAt->toIso8601String()]),
                'sent_successfully' => false,
                'retry_count' => $scenario['retries'],
                'last_retry_at' => $triggeredAt->copy()->addMinutes(30 * $scenario['retries']),
                'error_message' => $scenario['error'],
                'created_at' => $triggeredAt,
                'updated_at' => $triggeredAt->copy()->addMinutes(30 * $scenario['retries']),
            ];
        }

        // Insert all notifications
        DB::table('unpaid_order_notifications')->insert($notifications);

        $this->command->info('✅ Created '.count($notifications).' test notifications:');
        $this->command->line("   - {$warningCount} successful warnings (200 OK)");
        $this->command->line("   - {$cancellationCount} successful cancellations (200 OK)");
        $this->command->line("   - {$failedWarningCount} failed warnings (4xx errors)");
        $this->command->line("   - {$failedCancellationCount} failed cancellations (5xx errors)");
        $this->command->newLine();
        $this->command->info('💡 View at: Admin Panel → Automation → Notification Monitoring');
        $this->command->info('🗑️  Cleanup: php artisan db:seed --class=UnpaidOrderNotificationCleanupSeeder');
    }

    private function buildWarningPayload(Order $order, float $hoursUnpaid, $triggeredAt): array
    {
        return [
            'event_type' => 'order_cancellation_warning',
            'timestamp' => $triggeredAt->toIso8601String(),
            'tenant' => [
                'id' => $order->tenant_id,
                'name' => $order->tenant->name ?? 'Demo Company',
            ],
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'magento_order_id' => $order->magento_order_id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'grand_total' => (float) $order->grand_total,
                'currency_code' => $order->currency_code,
                'ordered_at' => $order->ordered_at->toIso8601String(),
                'hours_unpaid' => $hoursUnpaid,
            ],
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
            ],
            'warning' => [
                'threshold_hours' => 24,
                'cancellation_hours' => 72,
                'hours_remaining' => max(0, 72 - $hoursUnpaid),
                'message' => 'This order will be automatically cancelled if payment is not received within '.max(0, 72 - $hoursUnpaid).' hours.',
            ],
        ];
    }

    private function buildCancellationPayload(Order $order, float $hoursUnpaid, $triggeredAt): array
    {
        return [
            'event_type' => 'order_cancelled',
            'timestamp' => $triggeredAt->toIso8601String(),
            'tenant' => [
                'id' => $order->tenant_id,
                'name' => $order->tenant->name ?? 'Demo Company',
            ],
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'magento_order_id' => $order->magento_order_id,
                'status' => 'canceled',
                'payment_status' => 'failed',
                'grand_total' => (float) $order->grand_total,
                'currency_code' => $order->currency_code,
                'ordered_at' => $order->ordered_at->toIso8601String(),
                'cancelled_at' => $triggeredAt->toIso8601String(),
                'hours_unpaid' => $hoursUnpaid,
            ],
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
            ],
            'cancellation' => [
                'reason' => 'automatic_unpaid_timeout',
                'threshold_hours' => 72,
                'message' => "Order automatically cancelled due to non-payment after {$hoursUnpaid} hours.",
            ],
        ];
    }
}
