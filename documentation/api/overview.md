# API Overview

FlowOMS provides a RESTful API for programmatic access to order, invoice, and shipment data.

## Base URL

```
https://your-domain.com/api/v1
```

## Authentication

All API endpoints require **Sanctum token authentication**.

### Creating API Tokens

```bash
# Via Artisan command
php artisan api:token-manage
```

See [API Authentication](../integrations/api-authentication.md) for details.

### Making Authenticated Requests

Include your token in the `Authorization` header:

```http
Authorization: Bearer 1|abcdefghijk1234567890...
Accept: application/json
```

**Example with cURL:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://your-domain.com/api/v1/orders
```

## Rate Limiting

**Limit:** 60 requests per minute per token

### Rate Limit Headers

Every response includes rate limit information:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1733321400
```

### Exceeding Rate Limit

```json
{
  "success": false,
  "error_code": "RATE_LIMIT_EXCEEDED",
  "message": "Too Many Attempts.",
  "status": 429
}
```

## Tenant Isolation

All API requests are **automatically scoped** to the tenant associated with your API token.

- You can only access data for your tenant
- No cross-tenant data leakage
- Enforced at authentication layer

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    "orders": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7,
      "from": 1,
      "to": 15
    }
  },
  "message": null,
  "status": 200
}
```

### Error Response

```json
{
  "success": false,
  "error_code": "ORDER_NOT_FOUND",
  "message": "No order found with increment ID: 000000123",
  "status": 404
}
```

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `UNAUTHORIZED` | 401 | Missing or invalid API token |
| `FORBIDDEN` | 403 | Token lacks required permissions |
| `ORDER_NOT_FOUND` | 404 | Order not found |
| `INVOICE_NOT_FOUND` | 404 | Invoice not found |
| `SHIPMENT_NOT_FOUND` | 404 | Shipment not found |
| `VALIDATION_ERROR` | 422 | Invalid request parameters |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_ERROR` | 500 | Server error |

## Pagination

List endpoints support pagination with these parameters:

### Query Parameters

| Parameter | Default | Max | Description |
|-----------|---------|-----|-------------|
| `page` | 1 | - | Page number |
| `per_page` | 15 | 100 | Results per page |

### Pagination Response

```json
{
  "pagination": {
    "current_page": 2,
    "per_page": 15,
    "total": 145,
    "last_page": 10,
    "from": 16,
    "to": 30
  }
}
```

## Filtering

Most list endpoints support filtering via query parameters.

### Common Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `status` | `?status=processing` | Filter by status |
| `payment_status` | `?payment_status=paid` | Filter by payment status |
| `customer_email` | `?customer_email=john@example.com` | Filter by customer email (partial match) |
| `date_from` | `?date_from=2024-01-01` | Filter from date (ISO 8601) |
| `date_to` | `?date_to=2024-12-31` | Filter to date (ISO 8601) |

### Example

```bash
GET /api/v1/orders?status=processing&date_from=2024-01-01
```

## Including Relationships

Use the `include` parameter to load related data:

```bash
# Include order items
GET /api/v1/orders?include=items

# Include multiple relationships
GET /api/v1/orders?include=items,shipments,invoices
```

## Sorting

Results are sorted by default (typically by date, newest first).

## Date Formats

All dates use **ISO 8601** format:

```
2024-01-15T10:30:00Z
2024-01-15T10:30:00+00:00
```

**Examples:**
- `ordered_at`: `2024-01-15T14:32:10Z`
- `shipped_at`: `2024-01-16T09:15:00Z`

## HTTP Methods

| Method | Purpose |
|--------|---------|
| `GET` | Retrieve resources |
| `POST` | Create resources or submit data |
| `PATCH` | Update partial resources |
| `DELETE` | Delete resources (limited use) |

## Content Type

Always include the `Accept` header:

```http
Accept: application/json
```

For POST/PATCH requests, also include:

```http
Content-Type: application/json
```

## API Versioning

Current version: **v1**

All endpoints are prefixed with `/api/v1/`

Future versions will use `/api/v2/`, etc.

## Health Check

Check API availability (no authentication required):

```bash
GET /api/health
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

**Rate Limit:** 300 requests per minute

## Available Endpoints

### Orders

```
GET    /api/v1/orders
GET    /api/v1/orders/{increment_id}
```

See [Orders API](orders-api.md)

### Invoices

```
GET    /api/v1/invoices
GET    /api/v1/invoices/{increment_id}
```

See [Invoices API](invoices-api.md)

### Shipments

```
PATCH  /api/v1/shipments/{magento_shipment_id}/delivery
```

### Webhooks

```
POST   /api/v1/webhooks/shipment-status
```

See [Webhooks](webhooks.md)

## Common Patterns

### Fetching a Single Resource

```bash
GET /api/v1/orders/000000123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "increment_id": "000000123",
      "customer_name": "John Doe",
      "grand_total": 99.99,
      ...
    }
  }
}
```

### Fetching a List with Filters

```bash
GET /api/v1/orders?status=processing&per_page=25&page=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 50,
      "last_page": 2
    }
  }
}
```

### Creating/Updating a Resource

```bash
POST /api/v1/webhooks/shipment-status
Content-Type: application/json

{
  "tracking_number": "1Z999AA10123456784",
  "status": "delivered",
  "delivered_at": "2024-01-15T14:30:00Z"
}
```

## Security Best Practices

1. **Use HTTPS Only** - Never send tokens over HTTP
2. **Store Tokens Securely** - Use environment variables
3. **Rotate Tokens Regularly** - Change tokens every 30-90 days
4. **Monitor API Usage** - Track unusual activity
5. **Limit Token Scope** - Use read-only tokens when possible

## API Logging

All API requests are logged in the `api_logs` table:

**Logged Data:**
- Tenant ID
- User ID
- HTTP method
- Endpoint
- Query parameters
- Response status
- Duration (ms)
- IP address

View logs in Filament admin or via Artisan commands.

## Testing the API

### With cURL

```bash
# Create token
TOKEN=$(php artisan api:token-manage --user=1 --tenant=1 --name="Test")

# Test endpoint
curl -H "Authorization: Bearer $TOKEN" \
     -H "Accept: application/json" \
     https://your-domain.com/api/v1/orders
```

### With Postman

1. Create new request
2. Set method: `GET`
3. Set URL: `https://your-domain.com/api/v1/orders`
4. Add header: `Authorization: Bearer YOUR_TOKEN`
5. Add header: `Accept: application/json`
6. Send request

### With JavaScript (Fetch)

```javascript
const response = await fetch('https://your-domain.com/api/v1/orders', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json',
  }
});

const data = await response.json();
```

### With PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com',
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Accept' => 'application/json',
    ]
]);

$response = $client->get('/api/v1/orders');
$data = json_decode($response->getBody(), true);
```

## Further Reading

- [API Authentication](../integrations/api-authentication.md) - Token generation and management
- [Orders API](orders-api.md) - Order endpoints reference
- [Invoices API](invoices-api.md) - Invoice endpoints reference
- [Webhooks](webhooks.md) - Webhook integration
