<?php

namespace App\Jobs;

use App\Events\SlaBreached;
use App\Events\SlaBreachImminent;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitorSlaBreachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Process each tenant's orders with their specific settings
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->checkImminentBreaches($tenant);
            $this->checkActualBreaches($tenant);
        }
    }

    /**
     * Check for orders approaching SLA deadline.
     */
    private function checkImminentBreaches(Tenant $tenant): void
    {
        $warningHours = Setting::get('sla', 'breach_warning_hours', 2, $tenant->id);

        $imminentOrders = Order::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('shipped_at')
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<=', now()->addHours($warningHours))
            ->where('sla_deadline', '>', now())
            ->where('sla_breached', false)
            ->get();

        foreach ($imminentOrders as $order) {
            event(new SlaBreachImminent($order));
        }
    }

    /**
     * Check for orders that have breached SLA deadline.
     */
    private function checkActualBreaches(Tenant $tenant): void
    {
        $breachedOrders = Order::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('shipped_at')
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<=', now())
            ->where('sla_breached', false)
            ->get();

        foreach ($breachedOrders as $order) {
            $order->update(['sla_breached' => true]);
            event(new SlaBreached($order));
        }
    }
}
