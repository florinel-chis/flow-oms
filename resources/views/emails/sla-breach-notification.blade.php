<x-mail::message>
# Order Update

Hello {{ $customerName }},

We wanted to inform you about your order **#{{ $orderNumber }}**.

@if($isBreached)
## Shipping Delay Notice

We sincerely apologize, but your order has experienced a delay and missed its scheduled shipping deadline of {{ $slaDeadline }}.

Our team is working diligently to expedite your shipment and get your order to you as quickly as possible.
@else
## Important Shipping Update

Your order is scheduled to ship by {{ $slaDeadline }}, and we wanted to keep you informed of its progress.

We're taking extra steps to ensure your order ships on time.
@endif

## Order Details

- **Order Number**: #{{ $orderNumber }}
- **Total**: {{ $currency }} {{ $orderTotal }}
- **Shipping Method**: {{ $shippingMethod }}
- **SLA Deadline**: {{ $slaDeadline }}

<x-mail::button :url="config('app.url')">
Track Your Order
</x-mail::button>

@if($isBreached)
As a gesture of goodwill, we'll be reaching out separately about compensation for this delay.
@endif

We appreciate your patience and understanding.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
