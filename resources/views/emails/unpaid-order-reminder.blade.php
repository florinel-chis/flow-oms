<x-mail::message>
# Payment Reminder

Hello {{ $customerName }},

We noticed that your order **#{{ $orderNumber }}** placed on {{ $orderedAt }} is still awaiting payment.

## Order Details

- **Order Number**: #{{ $orderNumber }}
- **Total Amount**: {{ $currency }} {{ $orderTotal }}
- **Payment Method**: {{ $paymentMethod }}

To complete your purchase, please submit your payment at your earliest convenience.

<x-mail::button :url="config('app.url')">
Complete Payment
</x-mail::button>

If you have already made the payment, please disregard this email. Your order will be processed shortly.

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
