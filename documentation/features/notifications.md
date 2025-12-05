# Email Notifications

Automated email notification system for order management and customer communication.

## Overview

FlowOMS sends automated email notifications for:
- Unpaid order reminders
- Shipment tracking updates
- SLA breach alerts
- Delayed delivery notifications
- Order status changes

## Notification Types

### 1. Unpaid Order Reminders

**Trigger**: Orders with pending payment status

**Schedule:**
- Day 3: First reminder
- Day 7: Second reminder
- Day 14: Final reminder + cancel warning

**Recipients**: Customer email from order

**Template**: `unpaid-order-reminder.blade.php`

**Content:**
```
Subject: Payment Reminder - Order #000000123

Hi [Customer Name],

We noticed that payment is still pending for your order #000000123
placed on [Order Date].

Order Total: $[Grand Total]

Please complete your payment to avoid cancellation:
[Payment Link]

Questions? Contact us at support@your-store.com
```

**Actions in Email:**
- Pay Now button
- View Order button
- Contact Support link

### 2. Shipment Notifications

**Trigger**: Shipment created for order

**Recipients**: Customer email

**Template**: `shipment-notification.blade.php`

**Content:**
```
Subject: Your Order Has Shipped - #000000123

Hi [Customer Name],

Great news! Your order #000000123 has shipped.

Carrier: [Carrier Name]
Tracking #: [Tracking Number]
Estimated Delivery: [Date]

Track your shipment:
[Tracking Link]

Items Shipped:
- [Product Name] (Qty: [Quantity])
- [Product Name] (Qty: [Quantity])
```

### 3. Delayed Shipment Notifications

**Trigger**: Shipment past estimated delivery date

**Recipients**: Customer email

**Template**: `delayed-shipment-notification.blade.php`

**Content:**
```
Subject: Delivery Update - Order #000000123

Hi [Customer Name],

We apologize for the delay with your order #000000123.

Original Est. Delivery: [Original Date]
Updated Est. Delivery: [New Date]

Tracking #: [Tracking Number]
Current Status: [Status]

We're working with [Carrier] to resolve this. You'll receive
updates as we have them.

As an apology, here's a 10% discount on your next order: [Code]
```

### 4. SLA Breach Alerts

**Trigger**: Order misses SLA deadline

**Recipients**: Internal team (operations, warehouse)

**Template**: `sla-breach-notification.blade.php`

**Content:**
```
Subject: SLA Breach Alert - Order #000000123

Order #000000123 has breached its SLA deadline.

Customer: [Customer Name]
Ordered: [Date Time]
SLA Deadline: [Deadline]
Breached By: [Duration]

Current Status: [Status]
Payment Status: [Payment Status]

Action Required: Expedite fulfillment and shipping.

View Order: [Admin Link]
```

### 5. Order Status Change

**Trigger**: Order status updated

**Recipients**: Customer email

**Template**: `order-status-change.blade.php`

**Available Statuses:**
- Processing → "Your order is being prepared"
- Complete → "Your order is complete"
- Holded → "Your order is on hold"
- Canceled → "Your order has been canceled"

## Email Configuration

### Mail Settings

Configure in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@flowoms.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Supported Mail Services

**SMTP**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

**Mailgun**
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.your-domain.com
MAILGUN_SECRET=key-...
```

**SendGrid**
```env
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=SG.xxx
```

**Amazon SES**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=us-east-1
```

## Email Templates

Templates located in `resources/views/emails/`

### Template Structure

```blade
{{-- resources/views/emails/unpaid-order-reminder.blade.php --}}
@component('mail::message')
# Payment Reminder

Hi {{ $order->customer_name }},

We noticed that payment is still pending for your order #{{ $order->increment_id }}.

**Order Total:** ${{ number_format($order->grand_total, 2) }}

@component('mail::button', ['url' => $paymentUrl])
Pay Now
@endcomponent

Questions? Contact us at support@your-store.com

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

### Template Variables

Available in all email templates:

**Order Object:**
```php
$order->increment_id        // Order number
$order->customer_name       // Customer name
$order->customer_email      // Customer email
$order->grand_total         // Order total
$order->status              // Order status
$order->payment_status      // Payment status
$order->ordered_at          // Order date
```

**Shipment Object:**
```php
$shipment->tracking_number  // Tracking number
$shipment->carrier_code     // Carrier code
$shipment->status           // Shipment status
$shipment->estimated_delivery_at  // Est. delivery
```

### Customizing Templates

1. Locate template in `resources/views/emails/`
2. Edit HTML and blade syntax
3. Preview changes (see Testing section)
4. Deploy updates

## Notification Service

### EmailNotificationService

Core service handling email dispatch:

```php
// app/Services/EmailNotificationService.php

class EmailNotificationService
{
    public function sendUnpaidOrderReminder(Order $order): void
    {
        Mail::to($order->customer_email)
            ->send(new UnpaidOrderReminderMail($order));
    }

    public function sendShipmentNotification(Order $order, Shipment $shipment): void
    {
        Mail::to($order->customer_email)
            ->send(new ShipmentNotificationMail($order, $shipment));
    }

    public function sendDelayedShipmentNotification(Order $order, Shipment $shipment): void
    {
        Mail::to($order->customer_email)
            ->send(new DelayedShipmentMail($order, $shipment));
    }

    public function sendSlaBreachAlert(Order $order): void
    {
        $recipients = Setting::get('sla', 'breach_notification_emails', []);

        foreach ($recipients as $email) {
            Mail::to($email)
                ->send(new SlaBreachAlertMail($order));
        }
    }
}
```

## Automated Sending

### Queue Jobs

Emails are dispatched via queue jobs:

```php
// app/Jobs/SendPaymentReminderJob.php
dispatch(new SendPaymentReminderJob($order));

// app/Jobs/SendShipmentNotificationJob.php
dispatch(new SendShipmentNotificationJob($order, $shipment));
```

### Scheduled Commands

Daily processing of unpaid orders:

```php
// routes/console.php
Schedule::command('unpaid:process')->daily();
```

**Command Flow:**
1. Find orders with payment_status = 'pending'
2. Check days since order placed
3. Send appropriate reminder (3, 7, or 14 days)
4. Log notification in `unpaid_order_notifications` table
5. Cancel orders after 14 days (configurable)

## Notification History

### Tracking Sent Notifications

Table: `unpaid_order_notifications`

**Fields:**
- `order_id` - Related order
- `type` - Notification type (reminder, warning, canceled)
- `sent_at` - Timestamp
- `recipient_email` - Email address
- `status` - sent, failed, bounced

### Viewing History

In Filament admin:
1. Navigate to order detail
2. View **Notifications** tab
3. See all sent notifications

**API Access:**
```bash
GET /api/v1/orders/{id}/notifications
```

## Testing Emails

### Local Testing with Mailtrap

1. Sign up at https://mailtrap.io
2. Get SMTP credentials
3. Configure `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

4. Send test email:
```bash
php artisan tinker

$order = Order::first();
Mail::to('test@example.com')->send(new UnpaidOrderReminderMail($order));
```

5. Check inbox at Mailtrap

### Preview in Browser

```bash
php artisan serve

# Visit:
http://localhost:8000/email-preview/unpaid-order-reminder
http://localhost:8000/email-preview/shipment-notification
```

## Notification Settings

### Configuration via Settings

```php
// Unpaid order settings
Setting::set('notifications', 'unpaid_reminder_days', [3, 7, 14]);
Setting::set('notifications', 'auto_cancel_after_days', 14);

// SLA breach settings
Setting::set('notifications', 'send_sla_alerts', true);
Setting::set('sla', 'breach_notification_emails', [
    'warehouse@example.com',
    'ops@example.com'
]);

// Delay notifications
Setting::set('notifications', 'send_delay_alerts', true);
Setting::set('notifications', 'delay_compensation_code', 'SORRY10');
```

### Per-Tenant Configuration

Each tenant can customize:
- Email from address
- Notification schedules
- Alert recipients
- Email templates (future)

## Rate Limiting

Prevent email flooding:

```php
// Max 10 emails per minute per type
RateLimiter::for('payment-reminders', function ($order) {
    return Limit::perMinute(10)->by('payment-reminder');
});
```

## Error Handling

### Failed Email Delivery

Logged in `failed_jobs` table:

**Common Failures:**
- Invalid recipient email
- SMTP connection timeout
- Quota exceeded
- Bounced email

**Retry Logic:**
```php
// Retry 3 times with exponential backoff
SendPaymentReminderJob::dispatch($order)
    ->onQueue('emails')
    ->retry(3)
    ->backoff([60, 300, 900]); // 1min, 5min, 15min
```

### Monitoring Failed Emails

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job-id}

# Retry all
php artisan queue:retry all
```

## Best Practices

1. **Test Before Production**
   - Use Mailtrap for testing
   - Verify all template variables
   - Check mobile rendering

2. **Personalize Messages**
   - Use customer name
   - Include order details
   - Provide clear actions

3. **Monitor Deliverability**
   - Track bounce rates
   - Check spam scores
   - Use authenticated domains

4. **Respect Unsubscribes**
   - Honor opt-out requests
   - Maintain suppression list
   - Comply with CAN-SPAM

5. **Queue Emails**
   - Never send synchronously
   - Use job queues
   - Monitor queue health

## Troubleshooting

### Emails Not Sending

**Check:**
1. Queue worker running: `php artisan queue:work`
2. Mail configuration in `.env`
3. Failed jobs table: `php artisan queue:failed`
4. Laravel logs: `storage/logs/laravel.log`

### Emails Going to Spam

**Solutions:**
1. Configure SPF record
2. Set up DKIM signing
3. Use reputable mail service
4. Avoid spam trigger words
5. Include unsubscribe link

### Template Errors

**Debug:**
```bash
# Clear compiled views
php artisan view:clear

# Check for syntax errors
php artisan view:cache
```

## Further Reading

- [Dashboard Overview](dashboard.md) - Notification widgets
- [Order Management](order-management.md) - Order workflows
- [SLA Monitoring](sla-monitoring.md) - SLA breach alerts
- [Configuration](../configuration.md) - Mail settings
