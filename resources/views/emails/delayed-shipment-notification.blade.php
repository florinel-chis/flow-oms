<x-mail::message>
# Shipping Delay Update

Hello {{ $customerName }},

We wanted to update you on the delivery status of your order **#{{ $orderNumber }}**.

## Delay Notice

Your shipment has been delayed and is running approximately **{{ $delayDays }} day(s)** behind schedule.

## Shipment Details

- **Order Number**: #{{ $orderNumber }}
- **Tracking Number**: {{ $trackingNumber }}
- **Carrier**: {{ $carrierTitle }}
- **Original Estimated Delivery**: {{ $estimatedDelivery }}

@if($trackingUrl)
<x-mail::button :url="$trackingUrl">
Track Shipment
</x-mail::button>
@endif

## What's Next?

We're actively monitoring your shipment and working with the carrier to resolve the delay. You'll receive another update once we have more information or when your package is delivered.

If you have any questions or concerns, please don't hesitate to contact our support team.

We sincerely apologize for any inconvenience this may cause.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
