# Invoices API

API endpoints for retrieving invoice data.

## Authentication

All endpoints require a valid Sanctum API token. See [API Authentication](../integrations/api-authentication.md).

## Endpoints

### List Invoices

Retrieve a paginated list of invoices for your tenant.

```http
GET /api/v1/invoices
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `state` | string | No | Filter by invoice state (`paid`, `open`, `canceled`) |
| `order_id` | integer | No | Filter by internal order ID |
| `customer_email` | string | No | Filter by customer email (partial match) |
| `date_from` | string | No | Filter invoices from this date (ISO 8601) |
| `date_to` | string | No | Filter invoices until this date (ISO 8601) |
| `per_page` | integer | No | Results per page (default: 15, max: 100) |
| `page` | integer | No | Page number (default: 1) |
| `include` | string | No | Relationships to include: `items` (comma-separated) |

#### Invoice States

- `paid` - Invoice paid in full
- `open` - Invoice issued but not yet paid
- `canceled` - Invoice cancelled

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "https://your-domain.com/api/v1/invoices?state=paid&per_page=25"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "invoices": [
      {
        "id": 1,
        "increment_id": "100000123",
        "magento_invoice_id": 456,
        "order": {
          "id": 1,
          "increment_id": "000000123"
        },
        "magento_store": {
          "id": 1,
          "name": "Main Store"
        },
        "state": "paid",
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "subtotal": "89.99",
        "tax_amount": "7.20",
        "shipping_amount": "5.00",
        "discount_amount": "0.00",
        "grand_total": "102.19",
        "invoiced_at": "2024-01-15T14:40:00Z",
        "created_at": "2024-01-15T14:42:00Z",
        "updated_at": "2024-01-15T14:42:00Z"
      },
      ...
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 89,
      "last_page": 4,
      "from": 1,
      "to": 25
    }
  },
  "message": null,
  "status": 200
}
```

### Get Single Invoice

Retrieve details for a specific invoice by increment ID.

```http
GET /api/v1/invoices/{increment_id}
```

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `increment_id` | string | Yes | Magento invoice increment ID (e.g., "100000123") |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "https://your-domain.com/api/v1/invoices/100000123"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "invoice": {
      "id": 1,
      "increment_id": "100000123",
      "magento_invoice_id": 456,
      "order": {
        "id": 1,
        "increment_id": "000000123"
      },
      "magento_store": {
        "id": 1,
        "name": "Main Store"
      },
      "state": "paid",
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "subtotal": "89.99",
      "tax_amount": "7.20",
      "shipping_amount": "5.00",
      "discount_amount": "0.00",
      "grand_total": "102.19",
      "invoiced_at": "2024-01-15T14:40:00Z",
      "created_at": "2024-01-15T14:42:00Z",
      "updated_at": "2024-01-15T14:42:00Z",
      "items": [
        {
          "id": 1,
          "sku": "WIDGET-001",
          "name": "Premium Widget",
          "qty": 2,
          "price": "44.99",
          "row_total": "89.98",
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

### Include Invoice Items

```bash
GET /api/v1/invoices?include=items
```

**Response includes:**
- Invoice items (SKU, name, quantity, prices)

**Note:** The single invoice endpoint (`GET /api/v1/invoices/{increment_id}`) always includes items.

## Filtering Examples

### By Invoice State

Get all paid invoices:
```bash
GET /api/v1/invoices?state=paid
```

Get all open (unpaid) invoices:
```bash
GET /api/v1/invoices?state=open
```

### By Order ID

Get all invoices for a specific order:
```bash
GET /api/v1/invoices?order_id=1
```

### By Customer Email

Find invoices for a specific customer:
```bash
GET /api/v1/invoices?customer_email=john@example.com
```

**Note:** This performs a partial match (LIKE search).

### By Date Range

Get invoices from a specific date range:
```bash
GET /api/v1/invoices?date_from=2024-01-01&date_to=2024-01-31
```

### Combined Filters

Get paid invoices for January:
```bash
GET /api/v1/invoices?state=paid&date_from=2024-01-01&date_to=2024-01-31
```

## Pagination Examples

### First Page (15 results)

```bash
GET /api/v1/invoices
```

### Second Page

```bash
GET /api/v1/invoices?page=2
```

### Custom Page Size

Get 50 results per page:
```bash
GET /api/v1/invoices?per_page=50
```

## Error Responses

### Invoice Not Found (404)

```json
{
  "success": false,
  "error_code": "INVOICE_NOT_FOUND",
  "message": "No invoice found with increment ID: 100000999",
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

### Invoice Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Internal database ID |
| `increment_id` | string | Magento invoice number (e.g., "100000123") |
| `magento_invoice_id` | integer | Magento entity ID |
| `order` | object | Related order (id, increment_id) |
| `magento_store` | object | Store details (id, name) |
| `state` | string | Invoice state (`paid`, `open`, `canceled`) |
| `customer_name` | string | Customer full name |
| `customer_email` | string | Customer email address |
| `subtotal` | string | Subtotal amount |
| `tax_amount` | string | Tax amount |
| `shipping_amount` | string | Shipping cost |
| `discount_amount` | string | Discount amount |
| `grand_total` | string | Final invoice total |
| `invoiced_at` | string | Invoice creation timestamp (ISO 8601) |
| `created_at` | string | Record creation timestamp |
| `updated_at` | string | Record update timestamp |

### Invoice Item Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Internal database ID |
| `sku` | string | Product SKU |
| `name` | string | Product name |
| `qty` | integer | Quantity invoiced |
| `price` | string | Unit price |
| `row_total` | string | Line total (qty × price) |
| `created_at` | string | Record creation timestamp |
| `updated_at` | string | Record update timestamp |

## Use Cases

### Revenue Reporting

```bash
# Get all paid invoices for a date range
GET /api/v1/invoices?state=paid&date_from=2024-01-01&date_to=2024-01-31&per_page=100

# Calculate:
# - Total revenue
# - Average invoice value
# - Payment collection rate
```

### Accounts Receivable

```bash
# Get all open (unpaid) invoices
GET /api/v1/invoices?state=open&include=items

# For each invoice:
# - Check invoice age
# - Send payment reminder
# - Calculate outstanding amount
```

### Customer Account History

```bash
# Get all invoices for a customer
GET /api/v1/invoices?customer_email=john@example.com&include=items

# Display:
# - All invoices
# - Payment history
# - Outstanding balance
```

### Order-Invoice Reconciliation

```bash
# Get invoices for a specific order
GET /api/v1/invoices?order_id=1&include=items

# Verify:
# - All order items are invoiced
# - Invoice totals match order total
# - Payment is captured
```

### Financial Analytics

```bash
# Get paid invoices by date range
GET /api/v1/invoices?state=paid&date_from=2024-01-01&per_page=100

# Analyze:
# - Revenue by day/week/month
# - Average time to payment
# - Payment method trends
# - Top customers
```

## Relationship to Orders

### Invoice Lifecycle

```
Order Placed → Invoice Created → Invoice Paid
```

1. **Order Placed**: Customer places order
2. **Invoice Created**: Invoice issued in Magento
3. **Invoice Paid**: Payment captured

### Getting Order Details from Invoice

```bash
# Get invoice with order relationship
GET /api/v1/invoices/100000123

# Response includes order.increment_id
# Use it to fetch full order:
GET /api/v1/orders/000000123
```

### Getting Invoices from Order

```bash
# Get order with invoices included
GET /api/v1/orders/000000123?include=invoices
```

## Best Practices

1. **Use State Filters** - Filter by `state` to reduce response size
2. **Include Items Wisely** - Only include items when needed
3. **Date Range Queries** - Use date filters for reporting
4. **Cache Results** - Cache invoice lists to reduce API calls
5. **Handle Pagination** - Process large datasets page by page
6. **Monitor Rate Limits** - Track X-RateLimit headers

## Common Questions

### Q: How do I find unpaid invoices?

```bash
GET /api/v1/invoices?state=open
```

### Q: How do I get invoice items?

```bash
GET /api/v1/invoices/{increment_id}
# Items are always included in single invoice responses

# Or for list:
GET /api/v1/invoices?include=items
```

### Q: How do I find invoices for an order?

```bash
# Option 1: Filter by order_id
GET /api/v1/invoices?order_id=1

# Option 2: Get order with invoices
GET /api/v1/orders/000000123?include=invoices
```

### Q: How do I calculate total revenue?

```bash
# Get all paid invoices
GET /api/v1/invoices?state=paid&date_from=2024-01-01&date_to=2024-01-31&per_page=100

# Sum all grand_total values
```

## Further Reading

- [API Overview](overview.md) - Authentication, pagination, errors
- [Orders API](orders-api.md) - Order endpoints
- [API Authentication](../integrations/api-authentication.md) - Token management
- [Order Management](../features/order-management.md) - Order and invoice workflows
