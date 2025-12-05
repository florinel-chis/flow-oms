<?php

namespace App\Mail;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlaBreachNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: "Update on Your Order #{$this->order->increment_id}",
        );
    }

    public function content(): Content
    {
        $slaDeadline = $this->order->sla_deadline ? Carbon::parse($this->order->sla_deadline) : null;
        $isBreached = $slaDeadline && $slaDeadline->isPast();

        return new Content(
            markdown: 'emails.sla-breach-notification',
            with: [
                'order' => $this->order,
                'customerName' => $this->order->customer_name,
                'orderNumber' => $this->order->increment_id,
                'orderTotal' => number_format($this->order->grand_total, 2),
                'currency' => $this->order->currency ?? 'USD',
                'shippingMethod' => $this->order->shipping_method,
                'slaDeadline' => $slaDeadline?->format('M d, Y H:i'),
                'isBreached' => $isBreached,
                'urgency' => $isBreached ? 'breached' : 'at-risk',
            ],
        );
    }
}
