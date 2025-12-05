<?php

namespace App\Services;

use App\Mail\DelayedShipmentNotification;
use App\Mail\SlaBreachNotification;
use App\Mail\UnpaidOrderReminder;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Send unpaid order reminder to customer
     */
    public function sendUnpaidOrderReminder(Order $order): bool
    {
        try {
            if (! $order->customer_email) {
                Log::warning('Cannot send unpaid order reminder - no customer email', [
                    'order_id' => $order->id,
                    'increment_id' => $order->increment_id,
                ]);

                return false;
            }

            Mail::to($order->customer_email)
                ->send(new UnpaidOrderReminder($order));

            Log::info('Unpaid order reminder sent', [
                'order_id' => $order->id,
                'increment_id' => $order->increment_id,
                'customer_email' => $order->customer_email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send unpaid order reminder', [
                'order_id' => $order->id,
                'increment_id' => $order->increment_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send SLA breach notification to customer
     */
    public function sendSlaBreachNotification(Order $order): bool
    {
        try {
            if (! $order->customer_email) {
                Log::warning('Cannot send SLA breach notification - no customer email', [
                    'order_id' => $order->id,
                    'increment_id' => $order->increment_id,
                ]);

                return false;
            }

            Mail::to($order->customer_email)
                ->send(new SlaBreachNotification($order));

            Log::info('SLA breach notification sent', [
                'order_id' => $order->id,
                'increment_id' => $order->increment_id,
                'customer_email' => $order->customer_email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SLA breach notification', [
                'order_id' => $order->id,
                'increment_id' => $order->increment_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send delayed shipment notification to customer
     */
    public function sendDelayedShipmentNotification(Shipment $shipment): bool
    {
        try {
            $order = $shipment->order;

            if (! $order || ! $order->customer_email) {
                Log::warning('Cannot send delayed shipment notification - no customer email', [
                    'shipment_id' => $shipment->id,
                    'order_id' => $shipment->order_id,
                ]);

                return false;
            }

            Mail::to($order->customer_email)
                ->send(new DelayedShipmentNotification($shipment));

            Log::info('Delayed shipment notification sent', [
                'shipment_id' => $shipment->id,
                'order_id' => $order->id,
                'increment_id' => $order->increment_id,
                'customer_email' => $order->customer_email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send delayed shipment notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send bulk notifications for multiple orders
     *
     * @param  \Illuminate\Support\Collection  $orders
     */
    public function sendBulkUnpaidReminders($orders): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($orders as $order) {
            if ($this->sendUnpaidOrderReminder($order)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send bulk SLA breach notifications
     *
     * @param  \Illuminate\Support\Collection  $orders
     */
    public function sendBulkSlaBreachNotifications($orders): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($orders as $order) {
            if ($this->sendSlaBreachNotification($order)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send bulk delayed shipment notifications
     *
     * @param  \Illuminate\Support\Collection  $shipments
     */
    public function sendBulkDelayedShipmentNotifications($shipments): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($shipments as $shipment) {
            if ($this->sendDelayedShipmentNotification($shipment)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
