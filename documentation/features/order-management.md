# Order Management

Complete guide to managing orders in FlowOMS.

## Overview

FlowOMS provides a centralized interface for managing orders synchronized from Magento 2 stores. Orders flow through multiple statuses and payment states throughout their lifecycle.

## Order Lifecycle

```
Pending → Processing → Complete
   ↓          ↓           ↓
Holded    Canceled     Closed
```

### Order Statuses

| Status | Description | Next Actions |
|--------|-------------|--------------|
| **Pending** | New order awaiting payment | Send payment reminder, Cancel |
| **Processing** | Payment received, ready to fulfill | Create shipment, Hold |
| **Complete** | Order shipped and delivered | Close, Refund |
| **Holded** | On hold due to backorder/fraud | Resume, Cancel |
| **Canceled** | Order cancelled | Restock inventory |
| **Closed** | Completed and archived | None |

### Payment Statuses

| Status | Description | Color |
|--------|-------------|-------|
| **Pending** | Awaiting payment | Yellow |
| **Paid** | Payment captured | Green |
| **Partially Paid** | Partial payment received | Blue |
| **Failed** | Payment failed | Red |
| **Refunded** | Payment refunded | Gray |

## Accessing Orders

### Navigation

1. Log into FlowOMS admin panel
2. Navigate to **Orders** → **Orders**
3. View list of all orders for your tenant

### URL Structure

```
https://your-domain.com/admin/{tenant-slug}/orders
```

## Order List View

### Columns

The order list displays:

**Order #** - Magento increment ID
- Searchable
- Sortable
- Primary color
- Bold weight

**Store** - Magento store name
- Badge format
- Filterable

**Customer Name** - Full name
- Searchable
- Wraps on mobile

**Customer Email** - Email address
- Searchable
- Copyable
- Email icon

**Status** - Order status
- Badge with color coding:
  - Yellow: Pending
  - Blue: Processing
  - Green: Complete
  - Red: Canceled
  - Gray: Holded

**Payment** - Payment status
- Badge with color coding

**Payment Method** - Payment type
- Examples: PayPal, Stripe, Check/Money Order

**Grand Total** - Order total in USD
- Sortable
- Right-aligned

**Ordered At** - Timestamp
- DateTime format
- Sortable

### Filters

Apply filters to narrow down orders:

#### Status Filter
```
Multiple selection:
- Pending
- Processing
- Complete
- Canceled
- On Hold
```

#### Payment Status Filter
```
- Pending
- Paid
- Partially Paid
- Failed
- Refunded
```

#### Payment Method Filter
```
- Check/Money Order
- Bank Transfer
- Cash on Delivery
- PayPal Express
- Authorize.net
- Stripe
```

#### Store Filter
```
Select from configured Magento stores
```

#### Date Range Filter
```
Ordered From: [Date Picker]
Ordered Until: [Date Picker]
```

### Sorting

Default sort: **Ordered At** (descending - newest first)

Click column headers to sort by:
- Order #
- Status
- Grand Total
- Ordered At

### Search

Global search across:
- Order increment ID
- Customer name
- Customer email

## Order Detail View

Click any order to view full details.

### Order Information

**Order #** - Magento increment ID (read-only)
**Magento Order ID** - Internal Magento entity ID (read-only)
**Status** - Editable dropdown
**Payment Status** - Editable dropdown

### Customer Information

**Customer Name** - Read-only
**Customer Email** - Read-only, email format

### Pricing

All pricing fields are read-only:

**Subtotal** - Pre-tax total
**Tax Amount** - Sales tax
**Shipping Amount** - Shipping cost
**Discount Amount** - Applied discounts
**Grand Total** - Final total

### Related Records

**Order Items** - Line items in the order
**Shipments** - Tracking and delivery info
**Invoices** - Payment records

## Order Items

Each order contains one or more items:

### Item Details
- **SKU** - Product identifier
- **Name** - Product name
- **Qty Ordered** - Quantity ordered
- **Qty Shipped** - Quantity shipped
- **Qty Invoiced** - Quantity invoiced
- **Price** - Unit price
- **Row Total** - Line total (qty × price)

### Item Statuses
- **Pending** - Awaiting fulfillment
- **Shipped** - Fulfilled and shipped
- **Backordered** - Out of stock
- **Canceled** - Cancelled

## Shipments

View all shipments for an order:

### Shipment Fields
- **Tracking Number** - Carrier tracking ID
- **Carrier** - Shipping carrier (UPS, FedEx, USPS)
- **Status** - Current shipment status
- **Shipped At** - Ship date
- **Estimated Delivery** - Expected delivery date
- **Actual Delivery** - Confirmed delivery date

### Shipment Statuses
```
Pending → In Transit → Out for Delivery → Delivered
            ↓
        Exception
```

### Tracking Actions
- View tracking details
- Update tracking status
- Notify customer
- Print shipping label

## Invoices

View payment records:

### Invoice Fields
- **Invoice #** - Magento invoice increment ID
- **State** - Invoice state (Open, Paid, Canceled)
- **Grand Total** - Invoice amount
- **Created At** - Invoice creation date

### Invoice Items
List of items included in the invoice with quantities and prices.

## Bulk Actions

Select multiple orders to perform bulk operations:

### Delete Bulk Action
Remove multiple orders (use with caution)

## Order Operations

### Creating Shipments

1. Open order detail view
2. Navigate to **Shipments** tab
3. Click **Create Shipment**
4. Select items to ship
5. Enter tracking information
6. Save shipment

### Processing Refunds

1. Open order detail view
2. Navigate to **Invoices** tab
3. Select invoice to refund
4. Click **Create Credit Memo**
5. Select items and amounts
6. Process refund

### Sending Notifications

**Payment Reminders:**
- Automatically sent for unpaid orders
- Manual trigger from Unpaid Orders widget

**Shipment Notifications:**
- Sent when shipment is created
- Include tracking number and carrier

**Delay Notifications:**
- Sent for delayed shipments
- Include apology and updated delivery estimate

## Order Synchronization

### From Magento to FlowOMS

Orders are automatically synchronized from Magento:

```bash
# Manual sync
php artisan magento:sync-orders --sync

# Automated (runs every 30 minutes)
Schedule::command('magento:sync-orders --sync')
    ->everyThirtyMinutes();
```

### Sync Status

Each order shows sync metadata:
- **Synced At** - Last sync timestamp
- **Magento Order ID** - Source order ID
- **Magento Store** - Source store

### Handling Sync Failures

View failed syncs in **Magento Order Syncs** resource:
1. Navigate to **Magento** → **Order Syncs**
2. Filter by **Status: Failed**
3. View error message
4. Retry transformation

## Multi-Tenant Isolation

All orders are automatically scoped to the current tenant:

```php
// Orders are filtered by tenant_id
Order::all(); // Only returns current tenant's orders
```

**Benefits:**
- No cross-tenant data leakage
- Automatic filtering in queries
- Secure multi-tenant architecture

## Performance Considerations

### Indexing

Key indexes for fast queries:
```sql
INDEX (tenant_id, status)
INDEX (tenant_id, ordered_at)
INDEX (tenant_id, payment_status)
INDEX (magento_order_id)
```

### Eager Loading

Avoid N+1 queries by eager loading:
```php
Order::with(['items', 'shipments', 'magentoStore'])->get();
```

### Pagination

Default: 10 orders per page
Options: 10, 25, 50, 100

## API Access

Orders are available via REST API:

```bash
GET /api/v1/orders
GET /api/v1/orders/{id}
GET /api/v1/orders/{id}/items
GET /api/v1/orders/{id}/shipments
```

See [Orders API](../api/orders-api.md) for details.

## Best Practices

1. **Monitor Payment Status**: Review unpaid orders daily
2. **Track Shipments**: Update tracking info promptly
3. **Use Bulk Actions**: Process multiple orders efficiently
4. **Filter Effectively**: Use filters to focus on actionable orders
5. **Keep Data Synced**: Run regular Magento syncs
6. **Review Exceptions**: Address backordered and delayed orders

## Troubleshooting

### Order Not Syncing

**Issue**: Order exists in Magento but not in FlowOMS

**Solutions**:
```bash
# Sync specific order
php artisan magento:sync-order 000000123

# Check sync status
php artisan magento:sync-status
```

### Incorrect Order Data

**Issue**: Order data doesn't match Magento

**Solution**:
1. Check **Magento Order Syncs** for errors
2. Retry transformation
3. Verify Magento API token permissions

### Missing Shipment Info

**Issue**: Shipment created in Magento but not showing

**Solution**:
```bash
# Sync shipments
php artisan magento:sync-shipments --store-id=1
```

## Further Reading

- [Dashboard Overview](dashboard.md) - Real-time order metrics
- [SLA Monitoring](sla-monitoring.md) - Track shipping deadlines
- [Magento Integration](../integrations/magento-integration.md) - Sync configuration
- [Orders API](../api/orders-api.md) - REST API reference
