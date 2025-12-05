# API Authentication

FlowOMS uses Laravel Sanctum for API authentication with tenant-scoped access tokens.

## Overview

- **Method**: Bearer token authentication
- **Scope**: Tenant-isolated tokens
- **Rate Limit**: 60 requests per minute
- **Format**: `Authorization: Bearer {token}`

## Generating API Tokens

### Via Artisan Command

```bash
php artisan api:token-manage

# Follow prompts:
# 1. Select user
# 2. Enter token name
# 3. Select tenant
# 4. Copy the generated token
```

### Via Tinker

```bash
php artisan tinker
```

```php
$user = User::where('email', 'admin@example.com')->first();
$token = $user->createToken(
    name: 'Mobile App',
    abilities: ['*'],
    tenantId: 1
);

echo $token->plainTextToken;
// Output: 1|abcdefghijk1234567890...
```

### Via Code

```php
use App\Models\User;

$user = User::find(1);
$token = $user->createToken(
    name: 'Integration API',
    abilities: ['*'],  // All abilities
    tenantId: $user->tenant_id
)->plainTextToken;
```

## Making Authenticated Requests

### cURL Example

```bash
curl -H "Authorization: Bearer 1|abcdefghijk..." \
     -H "Accept: application/json" \
     https://your-flowoms.com/api/v1/orders
```

### JavaScript (Fetch)

```javascript
const response = await fetch('https://your-flowoms.com/api/v1/orders', {
  headers: {
    'Authorization': 'Bearer 1|abcdefghijk...',
    'Accept': 'application/json',
  }
});
const data = await response.json();
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-flowoms.com',
    'headers' => [
        'Authorization' => 'Bearer 1|abcdefghijk...',
        'Accept' => 'application/json',
    ]
]);

$response = $client->get('/api/v1/orders');
$orders = json_decode($response->getBody(), true);
```

## Tenant Isolation

### How It Works

Tokens are **automatically scoped** to a tenant:

```php
// Token is created with tenant_id
PersonalAccessToken::create([
    'tokenable_id' => $user->id,
    'tenant_id' => 1,
    'name' => 'API Token',
    'token' => hash('sha256', $plainTextToken),
]);
```

### Automatic Filtering

All queries are automatically filtered by tenant:

```php
// In LogApiRequest middleware
$tenantId = $request->user()->currentAccessToken()->tenant_id;

// All subsequent queries only return data for this tenant
Order::all(); // Returns only orders for tenant 1
```

### Security

- ✅ Users can only access their tenant's data
- ✅ No cross-tenant data leakage
- ✅ Enforced at middleware level
- ✅ Applies to all API endpoints

## Token Management

### Listing Tokens

```bash
php artisan api:token-manage --list
```

```php
// Via code
$user->tokens()->get();
```

### Revoking Tokens

```bash
php artisan api:token-manage --revoke

# Or revoke specific token
php artisan api:token-manage --revoke --token-id=5
```

```php
// Via code
$user->tokens()->where('id', 5)->delete();

// Revoke current token
$request->user()->currentAccessToken()->delete();

// Revoke all user tokens
$user->tokens()->delete();
```

### Token Expiration

Tokens don't expire by default. To add expiration:

```php
$expiresAt = now()->addDays(30);

$token = $user->createToken(
    name: 'Temporary Token',
    abilities: ['*'],
    tenantId: 1,
    expiresAt: $expiresAt
);
```

## Abilities (Permissions)

### Default: Full Access

```php
$token = $user->createToken('Full Access', ['*'], $tenantId);
```

### Restricted Access (Future)

```php
// Read-only token
$token = $user->createToken('Read Only', [
    'orders:read',
    'invoices:read'
], $tenantId);

// Check in controller
if ($request->user()->tokenCan('orders:read')) {
    // Allow read
}
```

## Rate Limiting

### Default Limits

- **60 requests per minute** per token
- Configured in `bootstrap/app.php`

### Headers

Response includes rate limit headers:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

### Exceeding Limits

```json
{
  "message": "Too Many Attempts.",
  "status": 429
}
```

### Custom Limits

Edit `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->throttleApi('120,1'); // 120 requests per minute
})
```

## API Logging

All API requests are logged to `api_logs` table:

```php
ApiLog::create([
    'tenant_id' => $tenantId,
    'user_id' => $user->id,
    'method' => 'GET',
    'endpoint' => '/api/v1/orders',
    'parameters' => json_encode($request->all()),
    'status_code' => 200,
    'duration_ms' => 45,
    'ip_address' => $request->ip(),
]);
```

### Viewing Logs

```bash
# Check recent API activity
php artisan api:logs --limit=50

# Check for specific user
php artisan api:logs --user-id=1

# Check failed requests
php artisan api:logs --status=400
```

## Error Responses

### Unauthenticated (401)

```json
{
  "message": "Unauthenticated.",
  "status": 401
}
```

**Cause**: Missing or invalid token

### Forbidden (403)

```json
{
  "message": "This action is unauthorized.",
  "status": 403
}
```

**Cause**: Token lacks required ability

### Too Many Requests (429)

```json
{
  "message": "Too Many Attempts.",
  "status": 429
}
```

**Cause**: Rate limit exceeded

## Security Best Practices

### 1. Token Storage

❌ **Never** commit tokens to version control
❌ **Never** expose tokens in client-side code
✅ Store securely in environment variables
✅ Use secrets management in production

### 2. Token Rotation

```php
// Rotate token every 30 days
if ($token->created_at->addDays(30)->isPast()) {
    $oldToken->delete();
    $newToken = $user->createToken('Rotated Token', ['*'], $tenantId);
}
```

### 3. HTTPS Only

**Always** use HTTPS in production:

```php
// Force HTTPS
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

### 4. Monitor Usage

- Track unusual API activity
- Alert on failed auth attempts
- Monitor rate limit hits

## Testing Authentication

### Test Token

```bash
# Generate test token
TOKEN=$(php artisan api:token-manage --user=1 --tenant=1 --name="Test Token")

# Test request
curl -H "Authorization: Bearer $TOKEN" \
     https://your-flowoms.com/api/v1/orders
```

### Validate Token

```php
use Laravel\Sanctum\PersonalAccessToken;

$token = PersonalAccessToken::findToken($plainTextToken);

if ($token && $token->can('orders:read')) {
    echo "Valid token for tenant: " . $token->tenant_id;
}
```

## Further Reading

- [API Overview](../api/overview.md)
- [Orders API](../api/orders-api.md)
- [Rate Limiting Configuration](../configuration.md#rate-limiting)
