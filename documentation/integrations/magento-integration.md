# Magento 2 Integration

Complete guide to integrating FlowOMS with Magento 2 stores.

## Overview

FlowOMS connects to Magento 2 via the REST API to synchronize:
- Orders and order items
- Invoices and invoice items
- Shipments and tracking information
- Product data (SKUs, names, prices)

## Prerequisites

### Magento Requirements

- Magento 2.4+ (Open Source or Commerce)
- REST API enabled
- Integration access token

### Required Magento Permissions

Your integration token needs access to:
- **Orders**: Read orders, order items, status
- **Invoices**: Read invoice data
- **Shipments**: Read shipment information
- **Products**: Read product catalog (optional)

## Setting Up Magento Integration

### Step 1: Create Integration in Magento

1. Log into **Magento Admin**
2. Navigate to **System** → **Extensions** → **Integrations**
3. Click **Add New Integration**

**Integration Details:**
```
Name: FlowOMS Integration
Email: admin@your-domain.com
Callback URL: (leave empty)
Identity Link URL: (leave empty)
```

### Step 2: Configure API Resources

Go to the **API** tab and select resources:

**Minimum Required:**
- ✅ Sales → Orders
- ✅ Sales → Invoices
- ✅ Sales → Shipments

**Optional (Recommended):**
- ✅ Catalog → Products
- ✅ Customers → Customers

### Step 3: Activate and Get Token

1. Click **Save** and then **Activate**
2. Magento will display the **Access Token**
3. **Copy this token** - it won't be shown again!

```
Example Token: abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
```

### Step 4: Add Store to FlowOMS

In FlowOMS Filament admin:

1. Navigate to **Magento Stores**
2. Click **New Magento Store**
3. Fill in the form:

```
Name: My Magento Store
Base URL: https://magento.example.com
API Token: [paste token from step 3]
Tenant: [select your tenant]
Active: Yes
```

4. Click **Create**

### Step 5: Test Connection

```bash
# Test the connection
php artisan magento:test-connection --store-id=1

# Sync a single order to verify
php artisan magento:sync-order 000000001
```

## Order Synchronization

### Two-Phase Sync Process

FlowOMS uses a **two-phase approach** to ensure no data is lost:

#### Phase 1: Raw Data Storage

```
Magento API → MagentoOrderSync table (raw JSON)
```

The complete API response is stored as JSON:
```php
MagentoOrderSync::create([
    'tenant_id' => $tenant->id,
    'magento_store_id' => $store->id,
    'magento_order_id' => $magentoOrder['entity_id'],
    'increment_id' => $magentoOrder['increment_id'],
    'raw_data' => json_encode($magentoOrder),
    'status' => 'pending',
]);
```

#### Phase 2: Normalization

```
MagentoOrderSync → Orders, OrderItems, Shipments, Invoices
```

The JSON data is transformed into application models:
```php
$order = Order::create([
    'tenant_id' => $sync->tenant_id,
    'magento_store_id' => $sync->magento_store_id,
    'increment_id' => $data['increment_id'],
    'status' => $data['status'],
    'payment_status' => $this->mapPaymentStatus($data),
    'grand_total' => $data['grand_total'],
    'ordered_at' => Carbon::parse($data['created_at']),
]);
```

**Benefits:**
- Never lose data from Magento
- Can reprocess if transformation logic changes
- Easy debugging and auditing
- Historical data preservation

### Sync Commands

#### Full Sync (Initial Setup)

```bash
# Sync last 30 days, truncate existing, and transform
php artisan magento:sync-orders --truncate --backfill --sync
```

Options:
- `--truncate`: Delete existing MagentoOrderSync records
- `--backfill`: Pull last 30 days of orders
- `--sync`: Transform into normalized tables

#### Incremental Sync

```bash
# Sync orders updated since last sync
php artisan magento:sync-orders --sync

# Sync specific store
php artisan magento:sync-orders --store-id=1 --sync

# Dry run (no database changes)
php artisan magento:sync-orders --dry-run
```

#### Single Order Sync

```bash
# Sync specific order by increment ID
php artisan magento:sync-order 000000123
```

### Automated Sync Schedule

Configure in `routes/console.php`:

```php
// Sync every 30 minutes
Schedule::command('magento:sync-orders --sync')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Daily full sync at 2 AM
Schedule::command('magento:sync-orders --backfill --sync')
    ->daily()
    ->at('02:00');
```

## API Endpoints Used

### Orders Endpoint

```http
GET /rest/V1/orders?searchCriteria[filterGroups][0][filters][0][field]=created_at
  &searchCriteria[filterGroups][0][filters][0][value]=2024-01-01
  &searchCriteria[filterGroups][0][filters][0][conditionType]=gteq
  &searchCriteria[pageSize]=100
```

**Response:**
```json
{
  "items": [
    {
      "entity_id": 123,
      "increment_id": "000000123",
      "status": "processing",
      "state": "processing",
      "grand_total": 99.99,
      "items": [...],
      "payment": {...},
      "extension_attributes": {...}
    }
  ],
  "total_count": 1
}
```

### Invoices Endpoint

```http
GET /rest/V1/invoices?searchCriteria[filterGroups][0][filters][0][field]=order_id
  &searchCriteria[filterGroups][0][filters][0][value]=123
```

### Shipments Endpoint

```http
GET /rest/V1/shipments?searchCriteria[filterGroups][0][filters][0][field]=order_id
  &searchCriteria[filterGroups][0][filters][0][value]=123
```

## Data Mapping

### Order Status Mapping

Magento → FlowOMS:
```php
'pending' => 'pending'
'processing' => 'processing'
'complete' => 'complete'
'holded' => 'holded'
'canceled' => 'canceled'
'closed' => 'closed'
```

### Payment Status Mapping

```php
private function mapPaymentStatus(array $orderData): string
{
    // Check if invoiced
    if ($orderData['total_invoiced'] >= $orderData['grand_total']) {
        return 'paid';
    }

    // Check payment method
    $method = $orderData['payment']['method'] ?? '';
    if (in_array($method, ['cashondelivery', 'checkmo'])) {
        return 'pending';
    }

    return 'pending';
}
```

### Shipment Status Mapping

```php
'pending' => 'pending'
'in_transit' => 'in_transit'
'out_for_delivery' => 'out_for_delivery'
'delivered' => 'delivered'
'exception' => 'exception'
```

## Error Handling

### Sync Failures

Failures are tracked in `MagentoOrderSync`:

```php
$sync->update([
    'status' => 'failed',
    'error_message' => $exception->getMessage(),
    'failed_at' => now(),
]);
```

View failed syncs in Filament:
- Navigate to **Magento Order Syncs**
- Filter by **Status: Failed**
- View error details
- Retry transformation

### Retry Logic

```bash
# Retry all failed syncs
php artisan magento:retry-failed-syncs

# Retry specific sync
php artisan magento:retry-sync --sync-id=123
```

### Common Issues

#### Invalid API Token
```
Error: Unauthorized (401)
Solution: Regenerate token in Magento
```

#### Rate Limiting
```
Error: Too Many Requests (429)
Solution: Add delay between requests
```

#### Missing Permissions
```
Error: Forbidden (403)
Solution: Check API resource permissions
```

## Webhooks (Optional)

### Magento to FlowOMS Webhooks

For real-time updates, configure Magento webhooks:

**Magento Extension Required**: Custom module or third-party webhook extension

**Webhook URL:**
```
POST https://your-flowoms.com/api/v1/webhooks/shipment-status
```

**Payload:**
```json
{
  "event": "shipment.track.update",
  "order_id": "000000123",
  "tracking_number": "1Z999AA10123456784",
  "carrier": "ups",
  "status": "delivered",
  "delivered_at": "2024-01-15T10:30:00Z"
}
```

## Monitoring & Logging

### Sync Logs

View sync activity:
```bash
tail -f storage/logs/laravel.log | grep "MagentoSync"
```

### Metrics to Monitor

- **Sync duration**: Should complete in < 5 minutes
- **Success rate**: Aim for > 99%
- **Failed transformations**: Investigate immediately
- **API response times**: Monitor Magento performance

### Sync Dashboard Widget

FlowOMS dashboard shows:
- Last sync time
- Orders synchronized today
- Failed syncs requiring attention
- Sync status per store

## Advanced Configuration

### Custom Field Mapping

Edit `app/Services/Magento/Parsers/OrderItemParser.php`:

```php
public function parse(array $item): array
{
    return [
        'sku' => $item['sku'],
        'name' => $item['name'],
        'qty_ordered' => $item['qty_ordered'],
        'price' => $item['price'],

        // Add custom field
        'custom_attribute' => $item['extension_attributes']['custom_field'] ?? null,
    ];
}
```

### Multi-Store Setup

Configure multiple Magento stores:

```php
// Store 1: US Store
MagentoStore::create([
    'name' => 'US Store',
    'base_url' => 'https://us.example.com',
    'api_token' => 'token_1',
]);

// Store 2: EU Store
MagentoStore::create([
    'name' => 'EU Store',
    'base_url' => 'https://eu.example.com',
    'api_token' => 'token_2',
]);
```

Sync all stores:
```bash
php artisan magento:sync-orders --all-stores --sync
```

## Troubleshooting

### Debug Mode

Enable verbose output:
```bash
php artisan magento:sync-orders --sync --verbose
```

### Check Sync Status

```bash
# View recent syncs
php artisan magento:sync-status

# Check specific store
php artisan magento:sync-status --store-id=1
```

### Reprocess Data

If transformation logic changes:
```bash
# Reprocess all pending syncs
php artisan magento:transform-synced-orders
```

## Best Practices

1. **Start with test store** - Use staging Magento first
2. **Monitor sync logs** - Set up alerts for failures
3. **Schedule off-peak** - Run full syncs during low traffic
4. **Keep tokens secure** - Store in `.env`, never commit
5. **Test before production** - Verify with small dataset first
6. **Regular backups** - Backup database before major changes

## Further Reading

- [Architecture Overview](../architecture.md)
- [API Authentication](api-authentication.md)
- [Order Management](../features/order-management.md)
