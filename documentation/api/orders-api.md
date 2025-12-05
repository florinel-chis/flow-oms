# Orders API

API endpoints for retrieving order data.

## Authentication

All endpoints require a valid Sanctum API token. See [API Authentication](../integrations/api-authentication.md).

## Endpoints

### List Orders

Retrieve a paginated list of orders for your tenant.

```http
GET /api/v1/orders
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | Filter by order status |
| `payment_status` | string | No | Filter by payment status |
| `customer_email` | string | No | Filter by customer email (partial match) |
| `date_from` | string | No | Filter orders from this date (ISO 8601) |
| `date_to` | string | No | Filter orders until this date (ISO 8601) |
| `per_page` | integer | No | Results per page (default: 15, max: 100) |
| `page` | integer | No | Page number (default: 1) |
| `include` | string | No | Relationships to include: `items`, `shipments`, `invoices` (comma-separated) |

#### Order Statuses

- `pending` - New order, awaiting payment
- `processing` - Payment received, ready to fulfill
- `complete` - Order fulfilled and delivered
- `canceled` - Order cancelled
- `holded` - On hold (backorder, fraud check, etc.)
- `closed` - Completed and archived

#### Payment Statuses

- `pending` - Awaiting payment
- `paid` - Payment captured
- `partially_paid` - Partial payment received
- `failed` - Payment failed
- `refunded` - Payment refunded

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "https://your-domain.com/api/v1/orders?status=processing&per_page=25"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "increment_id": "000000123",
        "magento_order_id": 123,
        "magento_store": {
          "id": 1,
          "name": "Main Store"
        },
        "status": "processing",
        "payment_status": "paid",
        "payment_method": "paypal_express",
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "subtotal": "89.99",
        "tax_amount": "7.20",
        "shipping_amount": "5.00",
        "discount_amount": "0.00",
        "grand_total": "102.19",
        "ordered_at": "2024-01-15T14:32:10Z",
        "shipped_at": null,
        "sla_deadline": "2024-01-16T14:32:10Z",
        "created_at": "2024-01-15T14:35:00Z",
        "updated_at": "2024-01-15T14:35:00Z"
      },
      ...
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 145,
      "last_page": 6,
      "from": 1,
      "to": 25
    }
  },
  "message": null,
  "status": 200
}
```

### Get Single Order

Retrieve details for a specific order by increment ID.

```http
GET /api/v1/orders/{increment_id}
```

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `increment_id` | string | Yes | Magento order increment ID (e.g., "000000123") |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "https://your-domain.com/api/v1/orders/000000123"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1,
      "increment_id": "000000123",
      "magento_order_id": 123,
      "magento_store": {
        "id": 1,
        "name": "Main Store"
      },
      "status": "processing",
      "payment_status": "paid",
      "payment_method": "paypal_express",
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "subtotal": "89.99",
      "tax_amount": "7.20",
      "shipping_amount": "5.00",
      "discount_amount": "0.00",
      "grand_total": "102.19",
      "ordered_at": "2024-01-15T14:32:10Z",
      "shipped_at": null,
      "sla_deadline": "2024-01-16T14:32:10Z",
      "created_at": "2024-01-15T14:35:00Z",
      "updated_at": "2024-01-15T14:35:00Z",
      "items": [
        {
          "id": 1,
          "sku": "WIDGET-001",
          "name": "Premium Widget",
          "qty_ordered": 2,
          "qty_shipped": 0,
          "qty_invoiced": 2,
          "qty_canceled": 0,
          "price": "44.99",
          "row_total": "89.98",
          "created_at": "2024-01-15T14:35:00Z",
          "updated_at": "2024-01-15T14:35:00Z"
        }
      ],
      "shipments": [],
      "invoices": [
        {
          "id": 1,
          "increment_id": "100000123",
          "magento_invoice_id": 456,
          "state": "paid",
          "grand_total": "102.19",
          "invoiced_at": "2024-01-15T14:40:00Z",
          "created_at": "2024-01-15T14:42:00Z",
          "updated_at": "2024-01-15T14:42:00Z"
        }
      ]
    }
  },
  "message": null,
  "status": 200
}
```

## Including Relationships

### Include Order Items

```bash
GET /api/v1/orders?include=items
```

**Response includes:**
- Order items (SKU, name, quantities, prices)

### Include Shipments

```bash
GET /api/v1/orders?include=shipments
```

**Response includes:**
- Shipment records (tracking, carrier, status, delivery dates)

### Include Invoices

```bash
GET /api/v1/orders?include=invoices
```

**Response includes:**
- Invoice records (invoice #, state, amounts, dates)

### Include Multiple Relationships

```bash
GET /api/v1/orders?include=items,shipments,invoices
```

**Note:** The single order endpoint (`GET /api/v1/orders/{increment_id}`) always includes all relationships.

## Filtering Examples

### By Order Status

Get all processing orders:
```bash
GET /api/v1/orders?status=processing
```

### By Payment Status

Get all unpaid orders:
```bash
GET /api/v1/orders?payment_status=pending
```

### By Customer Email

Find orders for a specific customer:
```bash
GET /api/v1/orders?customer_email=john@example.com
```

**Note:** This performs a partial match (LIKE search).

### By Date Range

Get orders from a specific date range:
```bash
GET /api/v1/orders?date_from=2024-01-01&date_to=2024-01-31
```

### Combined Filters

Get paid processing orders from January:
```bash
GET /api/v1/orders?status=processing&payment_status=paid&date_from=2024-01-01&date_to=2024-01-31
```

## Pagination Examples

### First Page (15 results)

```bash
GET /api/v1/orders
```

### Second Page

```bash
GET /api/v1/orders?page=2
```

### Custom Page Size

Get 50 results per page:
```bash
GET /api/v1/orders?per_page=50
```

### Navigate Pages

```json
{
  "pagination": {
    "current_page": 2,
    "last_page": 10
  }
}
```

To get next page:
```bash
GET /api/v1/orders?page=3
```

## Error Responses

### Order Not Found (404)

```json
{
  "success": false,
  "error_code": "ORDER_NOT_FOUND",
  "message": "No order found with increment ID: 000000999",
  "status": 404
}
```

### Unauthorized (401)

```json
{
  "success": false,
  "error_code": "UNAUTHORIZED",
  "message": "API access requires tenant-scoped token.",
  "status": 401
}
```

### Rate Limit Exceeded (429)

```json
{
  "success": false,
  "error_code": "RATE_LIMIT_EXCEEDED",
  "message": "Too Many Attempts.",
  "status": 429
}
```

## Data Structure

### Order Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Internal database ID |
| `increment_id` | string | Magento order number (e.g., "000000123") |
| `magento_order_id` | integer | Magento entity ID |
| `magento_store` | object | Store details (id, name) |
| `status` | string | Order status |
| `payment_status` | string | Payment status |
| `payment_method` | string | Payment method code |
| `customer_name` | string | Customer full name |
| `customer_email` | string | Customer email address |
| `subtotal` | string | Subtotal amount |
| `tax_amount` | string | Tax amount |
| `shipping_amount` | string | Shipping cost |
| `discount_amount` | string | Discount amount |
| `grand_total` | string | Final total |
| `ordered_at` | string | Order placement timestamp (ISO 8601) |
| `shipped_at` | string\|null | Shipment timestamp |
| `sla_deadline` | string\|null | SLA deadline timestamp |
| `created_at` | string | Record creation timestamp |
| `updated_at` | string | Record update timestamp |

### Order Item Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Internal database ID |
| `sku` | string | Product SKU |
| `name` | string | Product name |
| `qty_ordered` | integer | Quantity ordered |
| `qty_shipped` | integer | Quantity shipped |
| `qty_invoiced` | integer | Quantity invoiced |
| `qty_canceled` | integer | Quantity canceled |
| `price` | string | Unit price |
| `row_total` | string | Line total (qty × price) |
| `created_at` | string | Record creation timestamp |
| `updated_at` | string | Record update timestamp |

## Use Cases

### Monitor Unpaid Orders

```bash
# Get all unpaid orders
GET /api/v1/orders?payment_status=pending

# Process each order
# - Send payment reminder
# - Check order age
# - Auto-cancel if > 14 days
```

### Track Ready to Ship Orders

```bash
# Get paid processing orders
GET /api/v1/orders?status=processing&payment_status=paid&include=items

# For each order:
# - Check if all items in stock
# - Generate packing slip
# - Assign to picker
```

### SLA Monitoring

```bash
# Get orders approaching SLA deadline
GET /api/v1/orders?status=processing&date_from=2024-01-15

# For each order:
# - Check sla_deadline
# - If < 2 hours, mark as urgent
# - If past deadline, escalate
```

### Customer Order Lookup

```bash
# Find all orders for a customer
GET /api/v1/orders?customer_email=john@example.com&include=items,shipments
```

### Reporting

```bash
# Get all completed orders for January
GET /api/v1/orders?status=complete&date_from=2024-01-01&date_to=2024-01-31&per_page=100

# Calculate:
# - Total revenue
# - Average order value
# - Fulfillment times
```

## Best Practices

1. **Use Pagination** - Don't request all records at once
2. **Filter When Possible** - Reduce response size with filters
3. **Include Relationships Wisely** - Only include what you need
4. **Handle Errors Gracefully** - Check HTTP status codes
5. **Cache Responses** - Cache data to reduce API calls
6. **Monitor Rate Limits** - Track X-RateLimit headers

## Further Reading

- [API Overview](overview.md) - Authentication, pagination, errors
- [Invoices API](invoices-api.md) - Invoice endpoints
- [Order Management](../features/order-management.md) - Order workflows
- [API Authentication](../integrations/api-authentication.md) - Token management
