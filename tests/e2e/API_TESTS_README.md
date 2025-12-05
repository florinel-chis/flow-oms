# REST API Tests

This document describes how to set up and run the REST API endpoint tests using Playwright.

## Overview

The API tests cover:
- ✅ **GET /api/v1/orders** - List orders with filtering and pagination
- ✅ **GET /api/v1/orders/{increment_id}** - Get single order details
- ✅ **GET /api/v1/invoices** - List invoices with filtering and pagination
- ✅ **GET /api/v1/invoices/{increment_id}** - Get single invoice details
- ✅ **POST /api/v1/webhooks/shipment-status** - Receive shipment status updates

## Prerequisites

### 1. Create an API Token

The API tests require a valid Sanctum API token. Create one using Laravel Tinker:

```bash
php artisan tinker
```

Then run:

```php
$user = User::where('email', 'test@example.com')->first();
$token = $user->createToken('e2e-test-token', ['*'], null, 1);
echo $token->plainTextToken;
```

Copy the generated token (it will look like: `1|abcdef123456...`)

### 2. Set Environment Variable

Add the token to your environment:

**Option A: Create .env.test file** (recommended)
```bash
# tests/e2e/.env.test
API_TOKEN=1|your_token_here
APP_URL=http://127.0.0.1:8000
```

**Option B: Export in terminal**
```bash
export API_TOKEN="1|your_token_here"
export APP_URL="http://127.0.0.1:8000"
```

### 3. Ensure Seeded Data

The tests expect data to exist. Run seeders if needed:

```bash
php artisan db:seed
```

Make sure you have:
- At least a few orders with different statuses
- At least a few invoices with different states
- At least one shipment with a tracking number

## Running the Tests

### Run All API Tests

```bash
npm run test:e2e -- api-endpoints.spec.ts
```

### Run Specific Test Suites

**Orders endpoints only:**
```bash
npm run test:e2e -- api-endpoints.spec.ts -g "Orders Endpoints"
```

**Invoices endpoints only:**
```bash
npm run test:e2e -- api-endpoints.spec.ts -g "Invoices Endpoints"
```

**Webhook endpoints only:**
```bash
npm run test:e2e -- api-endpoints.spec.ts -g "Webhook Endpoints"
```

### Run with UI Mode (Debugging)

```bash
npx playwright test api-endpoints.spec.ts --ui
```

## Test Coverage

### Orders Endpoint Tests

| Test | Description |
|------|-------------|
| ✅ List orders | Tests GET /api/v1/orders returns paginated list |
| ✅ Pagination | Tests per_page and page query parameters |
| ✅ Filter by status | Tests status filter (pending, processing, complete, etc.) |
| ✅ Filter by payment_status | Tests payment_status filter (paid, pending, etc.) |
| ✅ Include relationships | Tests include=items,shipments,invoices parameter |
| ✅ Get single order | Tests GET /api/v1/orders/{increment_id} |
| ✅ 404 for non-existent order | Tests error response for invalid increment_id |
| ✅ Reject unauthenticated | Tests 401 response without token |

### Invoices Endpoint Tests

| Test | Description |
|------|-------------|
| ✅ List invoices | Tests GET /api/v1/invoices returns paginated list |
| ✅ Pagination | Tests per_page and page query parameters |
| ✅ Filter by state | Tests state filter (paid, open, canceled) |
| ✅ Include items | Tests include=items parameter |
| ✅ Get single invoice | Tests GET /api/v1/invoices/{increment_id} |
| ✅ 404 for non-existent invoice | Tests error response for invalid increment_id |
| ✅ Reject unauthenticated | Tests 401 response without token |

### Webhook Endpoint Tests

| Test | Description |
|------|-------------|
| ✅ Accept valid status update | Tests POST with valid shipment data |
| ✅ Accept delivered status | Tests delivery with signature and notes |
| ✅ Validate required fields | Tests 422 response for missing fields |
| ✅ 404 for non-existent tracking | Tests error for invalid tracking number |
| ✅ Validate status values | Tests 422 for invalid status enum |
| ✅ Reject unauthenticated | Tests 401 response without token |

### Response Format Tests

| Test | Description |
|------|-------------|
| ✅ Consistent success format | Validates { success: true, data: {...} } |
| ✅ Consistent error format | Validates { success: false, error: {...} } |
| ✅ Rate limiting | Tests 429 response when rate limit exceeded |

## Example API Responses

### Success Response
```json
{
  "success": true,
  "data": {
    "orders": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 145,
      "last_page": 10,
      "from": 1,
      "to": 15
    }
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ORDER_NOT_FOUND",
    "message": "No order found with the specified ID."
  }
}
```

## Troubleshooting

### Tests are skipped

If you see "Skipping API tests - no API_TOKEN provided":
- Check that API_TOKEN environment variable is set
- Ensure the token is valid and not expired
- Try creating a new token

### All tests fail with 401

- Token may be expired or revoked
- Token may not have correct tenant_id set
- Generate a new token

### Tests fail with "Shipment not found"

- Ensure you have seeded data with shipments
- Run: `php artisan db:seed`
- Check that shipments have tracking numbers

### Rate limit tests don't work

- Rate limiting is set to 60 requests/minute by default
- Test runs 70 requests rapidly to trigger rate limit
- If rate limit is higher in your config, test may not fail as expected

## CI/CD Integration

To run API tests in CI/CD pipelines:

1. Generate a long-lived token in deployment
2. Store as secret: `API_TOKEN`
3. Run tests with token:

```yaml
- name: Run API Tests
  env:
    API_TOKEN: ${{ secrets.API_TOKEN }}
    APP_URL: http://localhost:8000
  run: npm run test:e2e -- api-endpoints.spec.ts
```

## Security Notes

- ⚠️ **Never commit API tokens** to version control
- ⚠️ API tokens should be tenant-scoped for proper isolation
- ⚠️ Use separate tokens for testing vs production
- ⚠️ Rotate tokens regularly
- ⚠️ Revoke tokens after test runs in CI/CD

## Related Files

- **API Tests**: `tests/e2e/api-endpoints.spec.ts`
- **API Helpers**: `tests/e2e/utils/apiHelpers.ts`
- **Controllers**: `app/Http/Controllers/Api/V1/`
- **Resources**: `app/Http/Resources/Api/V1/`
- **Routes**: `routes/api.php`
