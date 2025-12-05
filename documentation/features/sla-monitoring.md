# SLA Monitoring

Service Level Agreement (SLA) tracking for order fulfillment and shipping.

## Overview

FlowOMS automatically calculates and monitors SLA deadlines for orders to ensure timely fulfillment. The system tracks:
- Order-to-ship deadlines
- Shipment delivery estimates
- Compliance percentages
- Breach notifications

## SLA Calculation

### Automatic Deadline Assignment

When an order is synced from Magento, FlowOMS calculates the SLA deadline:

```php
// app/Services/Sla/SlaCalculatorService.php
public function calculateDeadline(Order $order): Carbon
{
    // Default: 24 hours from order placement
    return $order->ordered_at->addHours(24);
}
```

### Factors Affecting SLA

Deadlines can be adjusted based on:
- **Shipping Method**: Express vs Standard shipping
- **Order Priority**: High-priority orders get shorter deadlines
- **Product Type**: Custom or made-to-order items
- **Store Configuration**: Per-store SLA rules

### Custom SLA Rules

Configure SLA rules per tenant or store:

```php
// Example: Express shipping = 12 hours
if ($order->shipping_method === 'express') {
    return $order->ordered_at->addHours(12);
}

// Example: Standard shipping = 48 hours
if ($order->shipping_method === 'standard') {
    return $order->ordered_at->addHours(48);
}
```

## SLA Tracking

### Order Fields

Each order tracks SLA-related timestamps:

```php
orders table:
- sla_deadline (datetime) - Target ship date
- shipped_at (datetime) - Actual ship date
- ordered_at (datetime) - Order placement time
```

### SLA Status

Orders are classified as:

**On Track** - `shipped_at IS NULL AND sla_deadline > NOW()`
- Order not yet shipped
- Still within SLA deadline
- Color: Green/Info

**Urgent** - `shipped_at IS NULL AND sla_deadline BETWEEN NOW() AND NOW() + 2 HOURS`
- Order approaching deadline
- Less than 2 hours remaining
- Color: Warning/Yellow

**Breached** - `shipped_at IS NULL AND sla_deadline < NOW()`
- Order not shipped by deadline
- SLA violated
- Color: Danger/Red

**Compliant** - `shipped_at IS NOT NULL AND shipped_at <= sla_deadline`
- Order shipped on time
- SLA met
- Color: Success/Green

**Non-Compliant** - `shipped_at IS NOT NULL AND shipped_at > sla_deadline`
- Order shipped late
- SLA missed
- Color: Warning

## Dashboard Integration

### SLA Compliance Stat

The dashboard displays overall SLA compliance:

```
SLA: 94.5%
Target: 95%
```

**Calculation:**
```php
$slaCompliance = ($shippedOnTime / $totalWithSla) * 100;
```

**Color Coding:**
- Green: ≥ target (95%)
- Yellow: target - 5% to target (90-95%)
- Red: < target - 5% (< 90%)

### Ready to Ship Widget

Shows urgent orders approaching deadline:

```
Ready to Ship: 28
5 urgent (< 2 hours to deadline)
```

### SLA Monitoring Page

Dedicated page at `/admin/{tenant}/sla-shipping-monitor` shows:

#### SLA Shipping Stats Widget
- **On Track**: Orders within SLA
- **At Risk**: < 2 hours to deadline
- **Breached**: Missed SLA deadline
- **Avg Time to Ship**: Average fulfillment time

#### SLA Shipping Orders Widget
Table of orders with SLA status:
- Order #
- Customer
- Ordered at
- SLA deadline
- Time remaining / Overdue
- Status badge
- Priority

## Recalculating SLA Deadlines

### Manual Recalculation

Run command to recalculate all SLA deadlines:

```bash
php artisan sla:recalculate

# Specific store
php artisan sla:recalculate --store-id=1

# Dry run (preview changes)
php artisan sla:recalculate --dry-run
```

### When to Recalculate

- After changing SLA rules
- After correcting order priorities
- After bulk status changes
- During data migration

### Automatic Updates

SLA deadlines are automatically updated when:
- Order priority changes
- Shipping method changes
- Order is rescheduled

## SLA Notifications

### Breach Alerts

Automated notifications when SLA is breached:

**Recipients:**
- Warehouse manager
- Operations team
- Escalation contacts

**Email Template:**
```
Subject: SLA Breach Alert - Order #000000123

Order #000000123 has missed its SLA deadline.

Customer: John Doe
Ordered: 2024-01-15 10:00 AM
Deadline: 2024-01-16 10:00 AM
Current Time: 2024-01-16 11:30 AM
Overdue By: 1 hour 30 minutes

Action Required: Expedite fulfillment and shipping.
```

### Warning Notifications

Alerts sent when orders become urgent:

**Trigger:** 2 hours before SLA deadline

**Recipients:**
- Fulfillment team
- Shift supervisors

### Digest Reports

Daily SLA summary emails:

```
Subject: Daily SLA Report - 2024-01-16

SLA Compliance: 94.2%
Target: 95%

Breached Today: 3 orders
At Risk: 5 orders
On Track: 42 orders

Top Issues:
- Backorders: 2 orders
- Missing inventory: 1 order
```

## SLA Configuration

### Settings

Configure SLA thresholds via Settings:

```php
// Target SLA compliance percentage
Setting::set('dashboard', 'target_sla_compliance', 95);

// Urgent threshold (hours before deadline)
Setting::set('sla', 'urgent_threshold_hours', 2);

// Breach notification recipients
Setting::set('sla', 'breach_notification_emails', [
    'warehouse@example.com',
    'operations@example.com'
]);
```

### Per-Store Rules

Different stores can have different SLA rules:

```php
MagentoStore::create([
    'name' => 'US Store',
    'sla_hours' => 24, // 24-hour SLA
]);

MagentoStore::create([
    'name' => 'Express Store',
    'sla_hours' => 12, // 12-hour SLA
]);
```

## SLA Monitoring Command

Continuous SLA monitoring via scheduled command:

```php
// routes/console.php
Schedule::command('sla:monitor')->hourly();
```

**Command Actions:**
1. Check for upcoming SLA deadlines
2. Identify breached orders
3. Send notifications
4. Log SLA events
5. Update SLA metrics

## Performance Metrics

### Key Metrics Tracked

**SLA Compliance Rate**
```sql
(Orders shipped on time / Total orders with SLA) × 100
```

**Average Time to Ship**
```sql
AVG(shipped_at - ordered_at)
```

**Breach Rate by Store**
```sql
Breached orders per store / Total orders per store
```

**Breach Rate by Priority**
```sql
Breached high-priority / Total high-priority
```

### Historical Trends

Track SLA compliance over time:
- Daily compliance rates
- Weekly averages
- Monthly trends
- Year-over-year comparison

## Reports

### SLA Compliance Report

**Filters:**
- Date range
- Store
- Priority
- Status

**Columns:**
- Date
- Total orders
- Shipped on time
- Shipped late
- Not yet shipped
- Compliance %

**Export:** CSV, Excel, PDF

### Breach Analysis Report

**Data:**
- Order details
- Breach duration
- Root cause
- Responsible team
- Resolution time

**Use Cases:**
- Identify bottlenecks
- Process improvements
- Team performance
- Training needs

## Best Practices

### Achieving High SLA Compliance

1. **Set Realistic Deadlines**
   - Base SLA on actual fulfillment capacity
   - Consider peak seasons
   - Account for processing time

2. **Monitor in Real-Time**
   - Check dashboard hourly
   - Act on urgent orders immediately
   - Address bottlenecks proactively

3. **Optimize Fulfillment**
   - Batch picking for efficiency
   - Prioritize urgent orders
   - Pre-pack popular items

4. **Automate Notifications**
   - Enable breach alerts
   - Send daily digests
   - Escalate repeated breaches

5. **Analyze Trends**
   - Review weekly reports
   - Identify patterns
   - Implement improvements

### Handling SLA Breaches

1. **Immediate Actions**
   - Notify customer of delay
   - Expedite remaining steps
   - Upgrade shipping if possible

2. **Root Cause Analysis**
   - Identify why SLA was missed
   - Document the issue
   - Implement corrective action

3. **Customer Compensation**
   - Offer discount on next order
   - Waive shipping fee
   - Send apology email

## API Access

SLA data available via API:

```bash
# Get SLA statistics
GET /api/v1/sla/stats

# Get orders by SLA status
GET /api/v1/orders?sla_status=breached
GET /api/v1/orders?sla_status=urgent

# Get SLA report
GET /api/v1/reports/sla?date_from=2024-01-01&date_to=2024-01-31
```

## Troubleshooting

### Incorrect SLA Deadlines

**Issue**: SLA deadlines don't match business rules

**Solution**:
```bash
# Recalculate with current rules
php artisan sla:recalculate
```

### Missing SLA Data

**Issue**: Orders have NULL sla_deadline

**Solution**:
```bash
# Recalculate all orders
php artisan sla:recalculate --all
```

### False Breach Alerts

**Issue**: Breach notifications for on-time orders

**Solution**:
1. Verify system clock is accurate
2. Check timezone configuration
3. Review SLA calculation logic

## Further Reading

- [Dashboard Overview](dashboard.md) - Real-time SLA metrics
- [Order Management](order-management.md) - Order fulfillment
- [Notifications](notifications.md) - Email alerts
- [Configuration](../configuration.md) - SLA settings
