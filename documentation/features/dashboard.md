# Dashboard Overview

The FlowOMS Dashboard provides real-time visibility into order operations with customizable filters and actionable widgets.

## Dashboard Layout

![FlowOMS Dashboard](../images/dashboard-overview.png)

### Access

Navigate to the dashboard at:
```
https://your-domain.com/admin/{tenant-slug}/dashboard
```

For example:
```
https://your-domain.com/admin/demo/dashboard
```

## Dashboard Filters

Global filters apply to all widgets on the dashboard:

### Store Filter
```
Options: All Stores, [List of configured Magento stores]
Default: All Stores
```

Filter by specific Magento store to see store-specific metrics.

### Date Range Filter
```
Options:
- Today (default)
- Yesterday
- Last 7 Days
- Last 30 Days
- This Month
```

All widgets refresh based on the selected date range.

### Sales Channel Filter (Coming Soon)
```
Planned: Web, Amazon, eBay, All Channels
```

## Dashboard Widgets

### 1. OMS Stats Overview

Six key performance indicators displayed as cards:

#### Orders
- **Metric**: Total orders placed in selected range
- **Icon**: Shopping cart
- **Color**: Blue (primary)
- **Query**: `COUNT(*) WHERE ordered_at BETWEEN start AND end`

#### Revenue
- **Metric**: Total captured/invoiced revenue
- **Icon**: Dollar sign
- **Color**: Green (success)
- **Query**: `SUM(grand_total) WHERE ordered_at BETWEEN start AND end`

#### AOV (Average Order Value)
- **Metric**: Revenue ÷ Order count
- **Icon**: Calculator
- **Color**: Info (blue)
- **Calculation**: `total_revenue / total_orders`

#### Shipped
- **Metric**: Completed orders with shipments
- **Description**: Shows active shipments in transit
- **Icon**: Check circle
- **Color**: Success (green)
- **Query**: `COUNT(*) WHERE status IN ('complete', 'closed') AND shipped_at IS NOT NULL`

#### Unpaid
- **Metric**: Orders with pending payment
- **Description**: Total outstanding amount
- **Icon**: Credit card
- **Color**: Dynamic based on thresholds
  - Green: ≤ 5 unpaid orders
  - Yellow: 6-10 unpaid orders
  - Red: > 10 unpaid orders
- **Query**: `COUNT(*) WHERE payment_status = 'pending'`

#### Ready to Ship
- **Metric**: Orders ready for fulfillment
- **Description**: Shows urgent orders (< 2 hours to SLA deadline)
- **Icon**: Truck
- **Color**: Warning if > 5 urgent
- **Query**: Uses `Order::readyToShip()` scope

#### Exceptions
- **Metric**: Backorders + Delayed shipments
- **Icon**: Exclamation triangle
- **Color**: Danger (red)
- **Components**:
  - Backorders: `status = 'holded'`
  - Delayed: `estimated_delivery_at < NOW() AND actual_delivery_at IS NULL`

#### SLA Compliance
- **Metric**: Percentage of orders shipped on time
- **Description**: Target threshold from settings
- **Icon**: Clock
- **Color**:
  - Green: ≥ target (default 95%)
  - Yellow: target - 5%
  - Red: < target - 5%
- **Calculation**: `(shipped_on_time / total_with_sla) * 100`

### 2. Unpaid Orders Widget

Interactive table showing orders with pending payment.

**Columns:**
- **Order #**: Clickable increment ID
- **Customer**: Customer name (limited to 20 chars)
- **Age**: Time since order placement
  - Red badge: 5+ days old
  - Yellow badge: 3+ days old
  - Gray badge: < 3 days
- **Amount**: Grand total in USD
- **Payment Method**: Badge showing payment type
- **Last Reminder**: Time since last reminder sent

**Filters:**
- Payment Method (Card, PayPal, Bank Transfer)

**Actions:**
- **Send Reminder**: Email payment reminder to customer
- **Escalate**: Flag high-risk orders for management review

**Bulk Actions:**
- **Send Reminders**: Bulk email to selected orders
- **Cancel & Restock**: Cancel unpaid orders and restore inventory

**Pagination**: 10, 25, or 50 rows per page

**Auto-Refresh**: Every 60 seconds

### 3. Ready to Ship Widget

Orders that are paid and ready for fulfillment.

**Criteria:**
- Payment status: `paid` or `authorized`
- Order status: `processing` or `pending`
- No existing shipment

**Columns:**
- Order #
- Customer
- Items count
- Priority (Standard, High, Express)
- SLA deadline
- Picker assignment

**Actions:**
- Assign to picker
- Print packing slip
- Create shipment
- Batch pick (bulk action)

### 4. Backordered Widget

Orders on hold due to inventory issues.

**Columns:**
- Order #
- SKU
- Qty backordered
- Expected restock date
- Alternate fulfillment options

**Actions:**
- Notify customer
- Suggest alternatives
- Cancel item
- Split shipment

### 5. Delayed Shipments Widget

Shipments past their estimated delivery date.

**Criteria:**
- `estimated_delivery_at < NOW()`
- `actual_delivery_at IS NULL`
- Status NOT IN ('delivered', 'canceled')

**Columns:**
- Order #
- Tracking #
- Carrier
- Days delayed
- Current status
- Est. delivery

**Actions:**
- Update tracking
- Contact carrier
- Notify customer
- Escalate to manager

## Performance Features

### Optimized Queries

The dashboard uses optimized database queries to minimize load:

```php
// Single query with conditional aggregation
$stats = Order::query()
    ->selectRaw('
        COUNT(*) as orders_count,
        COALESCE(SUM(grand_total), 0) as revenue,
        COUNT(CASE WHEN payment_status = "pending" THEN 1 END) as unpaid_count
    ')
    ->first();
```

### Polling Interval

Widgets auto-refresh at different intervals:
- Stats Overview: Every 30 seconds
- Table Widgets: Every 60 seconds

### Eager Loading

Relationships are eager-loaded to prevent N+1 queries:
```php
Order::with(['items', 'shipments', 'magentoStore'])->get();
```

## Configuration

### Threshold Settings

Configure dashboard thresholds via Settings in Filament:

```php
// Dashboard thresholds
Setting::set('dashboard', 'unpaid_warning_threshold', 10);
Setting::set('dashboard', 'unpaid_info_threshold', 5);
Setting::set('dashboard', 'target_sla_compliance', 95);
```

### Widget Visibility

Customize which widgets appear on the dashboard in `app/Filament/Pages/Dashboard.php`:

```php
public function getWidgets(): array
{
    return [
        OmsStatsOverview::class,
        UnpaidOrdersWidget::class,
        ReadyToShipWidget::class,
        BackorderedWidget::class,
        DelayedShipmentsWidget::class,
    ];
}
```

### Layout Customization

```php
public function getColumns(): int|array
{
    return [
        'default' => 1, // Mobile
        'lg' => 2,      // Desktop
    ];
}
```

## Mobile Responsiveness

The dashboard is fully responsive:
- **Mobile**: Single column layout
- **Tablet/Desktop**: Two-column layout
- **Full Width**: Uses `max-content-width: full`

## Best Practices

1. **Filter by Store**: Use store filter when managing multiple Magento stores
2. **Monitor Unpaid**: Check unpaid orders daily to minimize payment failures
3. **Track SLA**: Maintain SLA compliance above target threshold
4. **Address Exceptions**: Prioritize backorders and delayed shipments
5. **Use Bulk Actions**: Process multiple orders efficiently

## Further Reading

- [Order Management](order-management.md) - Detailed order workflows
- [SLA Monitoring](sla-monitoring.md) - SLA calculation and tracking
- [Notifications](notifications.md) - Email notification system
