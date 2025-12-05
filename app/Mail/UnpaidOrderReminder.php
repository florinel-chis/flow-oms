<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnpaidOrderReminder extends Mailable
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
            subject: "Payment Reminder for Order #{$this->order->increment_id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.unpaid-order-reminder',
            with: [
                'order' => $this->order,
                'customerName' => $this->order->customer_name,
                'orderNumber' => $this->order->increment_id,
                'orderTotal' => number_format($this->order->grand_total, 2),
                'currency' => $this->order->currency ?? 'USD',
                'paymentMethod' => $this->order->payment_method,
                'orderedAt' => $this->order->ordered_at->format('M d, Y'),
            ],
        );
    }
}
