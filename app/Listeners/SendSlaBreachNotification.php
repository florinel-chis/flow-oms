<?php

namespace App\Listeners;

use App\Events\SlaBreached;
use App\Events\SlaBreachImminent;
use App\Models\Setting;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSlaBreachNotification
{
    public function __construct(
        private EmailNotificationService $emailService
    ) {}

    /**
     * Handle SLA breach imminent event.
     */
    public function handleImminent(SlaBreachImminent $event): void
    {
        $order = $event->order;

        // Send email notification to customer
        $this->emailService->sendSlaBreachNotification($order);

        // Also send webhook if configured
        $webhookUrl = Setting::get('notifications', 'sla_webhook_url', null, $order->tenant_id);

        if (!$webhookUrl) {
            Log::info('SLA breach imminent email sent, no webhook URL configured', [
                'order_id' => $order->increment_id,
                'tenant_id' => $order->tenant_id,
            ]);

            return;
        }

        $payload = [
            'event' => 'sla.breach.imminent',
            'timestamp' => now()->toIso8601String(),
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'grand_total' => $order->grand_total,
                'currency_code' => $order->currency_code,
                'shipping_method' => $order->shipping_method,
                'ordered_at' => $order->ordered_at?->toIso8601String(),
            ],
            'sla' => [
                'deadline' => $order->sla_deadline?->toIso8601String(),
                'hours_until_breach' => $order->sla_deadline?->diffInHours(now(), false),
                'minutes_until_breach' => $order->sla_deadline?->diffInMinutes(now(), false),
            ],
        ];

        $this->sendWebhook($webhookUrl, $payload, 'imminent', $order->increment_id);
    }

    /**
     * Handle SLA breached event.
     */
    public function handleBreached(SlaBreached $event): void
    {
        $order = $event->order;

        // Send email notification to customer
        $this->emailService->sendSlaBreachNotification($order);

        // Also send webhook if configured
        $webhookUrl = Setting::get('notifications', 'sla_webhook_url', null, $order->tenant_id);

        if (!$webhookUrl) {
            Log::info('SLA breached email sent, no webhook URL configured', [
                'order_id' => $order->increment_id,
                'tenant_id' => $order->tenant_id,
            ]);

            return;
        }

        $payload = [
            'event' => 'sla.breach.occurred',
            'timestamp' => now()->toIso8601String(),
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'grand_total' => $order->grand_total,
                'currency_code' => $order->currency_code,
                'shipping_method' => $order->shipping_method,
                'ordered_at' => $order->ordered_at?->toIso8601String(),
            ],
            'sla' => [
                'deadline' => $order->sla_deadline?->toIso8601String(),
                'hours_overdue' => abs($order->sla_deadline?->diffInHours(now(), false) ?? 0),
                'breached_at' => now()->toIso8601String(),
            ],
        ];

        $this->sendWebhook($webhookUrl, $payload, 'breached', $order->increment_id);
    }

    /**
     * Send webhook notification with retry logic.
     */
    private function sendWebhook(string $url, array $payload, string $type, string $orderId): void
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("SLA breach notification sent successfully ({$type})", [
                    'order_id' => $orderId,
                    'status_code' => $response->status(),
                ]);
            } else {
                Log::warning("SLA breach notification failed ({$type})", [
                    'order_id' => $orderId,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("SLA breach notification exception ({$type})", [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
