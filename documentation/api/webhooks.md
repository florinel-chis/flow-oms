# Webhooks

Webhook endpoints for receiving real-time updates from external systems.

## Overview

FlowOMS provides webhook endpoints for:
- Shipment status updates from carriers
- Delivery confirmations
- Tracking updates

## Authentication

All webhook endpoints require a valid Sanctum API token. See [API Authentication](../integrations/api-authentication.md).

## Endpoints

### Shipment Status Webhook

Receive shipment status updates from carrier tracking systems.

```http
POST /api/v1/webhooks/shipment-status
```

#### Request Headers

```http
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
Accept: application/json
```

#### Request Body

```json
{
  "tracking_number": "1Z999AA10123456784",
  "carrier_code": "ups",
  "status": "delivered",
  "delivered_at": "2024-01-16T14:30:00Z",
  "signature": "John Doe",
  "delivery_notes": "Left at front door",
  "delivery_photo_url": "https://carrier.com/photos/12345.jpg",
  "estimated_delivery_at": "2024-01-17T18:00:00Z"
}
```

#### Request Parameters

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `tracking_number` | string | Yes | Shipment tracking number (max 255 chars) |
| `carrier_code` | string | Yes | Carrier code: `ups`, `fedex`, `usps`, `dhl`, etc. (max 50 chars) |
| `status` | string | Yes | Shipment status (see statuses below) |
| `delivered_at` | string | No | Delivery timestamp (ISO 8601) - required if status is `delivered` |
| `signature` | string | No | Delivery signature name (max 255 chars) |
| `delivery_notes` | string | No | Delivery notes (max 1000 chars) |
| `delivery_photo_url` | string | No | URL to delivery photo (max 500 chars) |
| `estimated_delivery_at` | string | No | Updated delivery estimate (ISO 8601) |

#### Shipment Statuses

| Status | Description |
|--------|-------------|
| `pending` | Shipment created, not yet in carrier system |
| `in_transit` | Package in transit to destination |
| `out_for_delivery` | Out for delivery today |
| `delivered` | Successfully delivered |
| `exception` | Delivery exception (weather, address issue, etc.) |
| `returned` | Package returned to sender |
| `canceled` | Shipment cancelled |

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "shipment_id": 1,
    "tracking_number": "1Z999AA10123456784",
    "status": "delivered",
    "previous_status": "out_for_delivery",
    "order_number": "000000123",
    "updated_at": "2024-01-16T14:35:00Z"
  },
  "message": "Shipment status updated successfully",
  "status": 200
}
```

#### Error Response: Validation Error (422)

```json
{
  "success": false,
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "status": [
      "The selected status is invalid."
    ],
    "delivered_at": [
      "The delivered at field is required when status is delivered."
    ]
  },
  "message": "Validation failed",
  "status": 422
}
```

#### Error Response: Shipment Not Found (404)

```json
{
  "success": false,
  "error_code": "SHIPMENT_NOT_FOUND",
  "message": "No shipment found with tracking number: 1Z999AA10123456784",
  "status": 404
}
```

### Update Shipment Delivery

Update delivery information for a shipment.

```http
PATCH /api/v1/shipments/{magento_shipment_id}/delivery
```

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `magento_shipment_id` | integer | Yes | Magento shipment entity ID |

#### Request Body

```json
{
  "delivered_at": "2024-01-16T14:30:00Z",
  "signature": "John Doe",
  "notes": "Left at front door",
  "photo_url": "https://carrier.com/photos/12345.jpg"
}
```

## Usage Examples

### Example: UPS Delivery Webhook

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tracking_number": "1Z999AA10123456784",
    "carrier_code": "ups",
    "status": "delivered",
    "delivered_at": "2024-01-16T14:30:00Z",
    "signature": "John Doe"
  }' \
  https://your-domain.com/api/v1/webhooks/shipment-status
```

### Example: FedEx In Transit Update

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tracking_number": "123456789012",
    "carrier_code": "fedex",
    "status": "in_transit",
    "estimated_delivery_at": "2024-01-17T18:00:00Z"
  }' \
  https://your-domain.com/api/v1/webhooks/shipment-status
```

### Example: USPS Exception

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tracking_number": "9400111899562157166547",
    "carrier_code": "usps",
    "status": "exception",
    "delivery_notes": "Unable to deliver - business closed"
  }' \
  https://your-domain.com/api/v1/webhooks/shipment-status
```

### Example: JavaScript (Node.js)

```javascript
const axios = require('axios');

async function updateShipmentStatus() {
  try {
    const response = await axios.post(
      'https://your-domain.com/api/v1/webhooks/shipment-status',
      {
        tracking_number: '1Z999AA10123456784',
        carrier_code: 'ups',
        status: 'delivered',
        delivered_at: '2024-01-16T14:30:00Z',
        signature: 'John Doe'
      },
      {
        headers: {
          'Authorization': 'Bearer YOUR_TOKEN',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      }
    );

    console.log('Shipment updated:', response.data);
  } catch (error) {
    console.error('Error:', error.response.data);
  }
}
```

### Example: PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com',
]);

$response = $client->post('/api/v1/webhooks/shipment-status', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
    'json' => [
        'tracking_number' => '1Z999AA10123456784',
        'carrier_code' => 'ups',
        'status' => 'delivered',
        'delivered_at' => '2024-01-16T14:30:00Z',
        'signature' => 'John Doe',
    ],
]);

$data = json_decode($response->getBody(), true);
```

## Webhook Workflow

### Status Update Flow

```
1. Carrier detects status change
   ↓
2. Carrier sends webhook to FlowOMS
   ↓
3. FlowOMS validates request
   ↓
4. FlowOMS finds shipment by tracking number
   ↓
5. FlowOMS updates shipment status
   ↓
6. If delivered, FlowOMS dispatches ShipmentDelivered event
   ↓
7. Event triggers customer notification email
   ↓
8. FlowOMS returns success response
```

### Event Triggers

When a shipment is marked as delivered:

1. **ShipmentDelivered Event** is dispatched
2. **Customer Notification** email is sent
3. **Order Status** may be updated to "complete"
4. **SLA Metrics** are updated
5. **Dashboard Stats** are refreshed

## Carrier Integration

### Setting Up Carrier Webhooks

#### AfterShip

1. Log into AfterShip dashboard
2. Navigate to **Settings** → **Webhooks**
3. Add webhook URL: `https://your-domain.com/api/v1/webhooks/shipment-status`
4. Set authentication header: `Authorization: Bearer YOUR_TOKEN`
5. Select events: Delivery, Exception, Status Update

#### UPS

1. Log into UPS Developer Portal
2. Create webhook subscription
3. Set endpoint: `https://your-domain.com/api/v1/webhooks/shipment-status`
4. Add authentication token
5. Subscribe to tracking events

#### FedEx

1. Log into FedEx Developer Resource Center
2. Configure webhook notifications
3. Set callback URL
4. Add bearer token authentication

### Carrier Code Mapping

| Carrier | Code | Common Tracking Pattern |
|---------|------|-------------------------|
| UPS | `ups` | 1Z999AA10123456784 |
| FedEx | `fedex` | 123456789012 |
| USPS | `usps` | 9400111899562157166547 |
| DHL | `dhl` | 1234567890 |
| Amazon | `amazon` | TBA123456789000 |

## Security

### Authentication

Webhooks require valid API tokens:
- Create dedicated webhook token
- Limit token scope to webhook endpoints only
- Rotate tokens regularly
- Monitor webhook activity

### Validation

FlowOMS validates:
- Token authentication
- Request payload structure
- Shipment exists in database
- Shipment belongs to token's tenant

### Logging

All webhook requests are logged:
- Timestamp
- Tracking number
- Status update
- Tenant ID
- Success/failure
- Error messages

## Error Handling

### Retry Logic

If webhook delivery fails:
- Carrier should retry with exponential backoff
- Suggested: 1 min, 5 min, 15 min, 1 hour

### Idempotency

Webhooks can be sent multiple times safely:
- Status updates are idempotent
- Duplicate "delivered" updates are ignored
- Last update wins for status changes

## Best Practices

1. **Use Dedicated Tokens** - Create separate tokens for webhook integrations
2. **Implement Retry Logic** - Retry failed webhook deliveries
3. **Validate Payloads** - Ensure required fields are present
4. **Log Everything** - Keep detailed logs of all webhook activity
5. **Monitor Failures** - Set up alerts for failed webhook deliveries
6. **Test Webhooks** - Test with staging/sandbox environments first
7. **Handle Idempotency** - Don't rely on webhooks being sent only once

## Testing Webhooks

### Using cURL

```bash
# Test shipment status update
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tracking_number": "TEST123456789",
    "carrier_code": "ups",
    "status": "in_transit"
  }' \
  https://your-domain.com/api/v1/webhooks/shipment-status
```

### Using Postman

1. Create new POST request
2. Set URL: `https://your-domain.com/api/v1/webhooks/shipment-status`
3. Add headers:
   - `Authorization: Bearer YOUR_TOKEN`
   - `Content-Type: application/json`
4. Add JSON body
5. Send request

### Mock Webhook Tool

Create a simple webhook sender for testing:

```bash
# webhook-test.sh
#!/bin/bash

curl -X POST \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d @webhook-payload.json \
  https://your-domain.com/api/v1/webhooks/shipment-status
```

## Further Reading

- [API Overview](overview.md) - Authentication and error handling
- [Orders API](orders-api.md) - Related orders
- [API Authentication](../integrations/api-authentication.md) - Token management
- [Order Management](../features/order-management.md) - Shipment workflows
