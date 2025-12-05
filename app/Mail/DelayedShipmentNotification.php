<?php

namespace App\Mail;

use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DelayedShipmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Shipment $shipment
    ) {}

    public function envelope(): Envelope
    {
        $order = $this->shipment->order;

        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: "Shipping Delay Update for Order #{$order->increment_id}",
        );
    }

    public function content(): Content
    {
        $order = $this->shipment->order;
        $estimatedDelivery = $this->shipment->estimated_delivery_at
            ? Carbon::parse($this->shipment->estimated_delivery_at)
            : null;

        $delayDays = $estimatedDelivery && $estimatedDelivery->isPast()
            ? now()->diffInDays($estimatedDelivery)
            : 0;

        return new Content(
            markdown: 'emails.delayed-shipment-notification',
            with: [
                'shipment' => $this->shipment,
                'order' => $order,
                'customerName' => $order->customer_name,
                'orderNumber' => $order->increment_id,
                'trackingNumber' => $this->shipment->tracking_number,
                'carrierCode' => $this->shipment->carrier_code,
                'carrierTitle' => $this->shipment->carrier_title,
                'estimatedDelivery' => $estimatedDelivery?->format('M d, Y'),
                'delayDays' => $delayDays,
                'trackingUrl' => $this->shipment->getTrackingUrlAttribute(),
            ],
        );
    }
}
