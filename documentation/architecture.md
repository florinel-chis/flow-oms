# System Architecture

FlowOMS is built with a modern Laravel architecture following SOLID principles and clean code practices.

## Technology Stack

### Backend
- **Laravel 12** - PHP framework with modern features
- **Filament 4** - Admin panel with Livewire components
- **Sanctum** - API authentication with tenant-scoped tokens
- **SQLite/MySQL** - Database with full-text search support

### Frontend
- **Livewire** - Reactive UI components
- **Alpine.js** - Minimal JavaScript framework
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Fast build tool

### Testing
- **Pest PHP** - Unit and feature testing
- **Playwright** - End-to-end browser testing

## Architectural Patterns

### Multi-Tenancy Pattern

FlowOMS uses **single-database multi-tenancy** with tenant_id scoping:

```php
// Global scope automatically filters queries
Order::all(); // Only returns current tenant's orders

// BelongsToTenant trait
class Order extends Model
{
    use BelongsToTenant;
}
```

**Benefits:**
- Simple database management
- Cost-effective scaling
- Easy backups and migrations
- Shared codebase updates

### Two-Phase Data Sync

Magento order synchronization uses a two-phase approach:

**Phase 1: Raw Data Storage**
```
Magento API → MagentoOrderSync (raw JSON)
```
- Stores complete API response as JSON
- Preserves all data from Magento
- Enables reprocessing if needed

**Phase 2: Normalized Transformation**
```
MagentoOrderSync → Orders, OrderItems, Shipments, Invoices
```
- Transforms into application models
- Applies business logic
- Creates relationships

**Benefits:**
- Never lose data
- Can reprocess if logic changes
- Debugging and auditing
- Historical data preservation

### Service Layer Architecture

```
Controller → Service → Repository/Model → Database
```

**Key Services:**
- `OrderSyncService` - Magento order synchronization
- `EmailNotificationService` - Email dispatch
- `SlaCalculatorService` - SLA deadline calculation
- `UnpaidOrderProcessor` - Payment reminder logic

### Queue Architecture

Background job processing with Laravel Horizon:

```php
// Jobs
SyncMagentoOrdersJob
SendUnpaidOrderWarningJob
MonitorSlaBreachesJob
CancelUnpaidOrderJob
```

**Queue Configuration:**
- Default: Synchronous (development)
- Production: Redis with Horizon
- Retry logic with exponential backoff
- Job monitoring and failure tracking

## Database Schema

### Core Tables

**Tenants & Users**
```
tenants (id, name, slug, created_at)
users (id, name, email, password)
tenant_user (tenant_id, user_id) - pivot
```

**Magento Integration**
```
magento_stores (id, tenant_id, name, base_url, api_token)
magento_order_syncs (id, tenant_id, magento_order_id, raw_data, status)
magento_products (id, tenant_id, magento_product_id, sku, name)
```

**Order Management**
```
orders (id, tenant_id, magento_store_id, increment_id, status, payment_status, grand_total, ordered_at)
order_items (id, tenant_id, order_id, sku, name, qty, price)
shipments (id, tenant_id, order_id, carrier_code, tracking_number, status)
invoices (id, tenant_id, order_id, increment_id, state, grand_total)
invoice_items (id, tenant_id, invoice_id, order_item_id, qty)
```

**Notifications & SLA**
```
unpaid_order_notifications (id, tenant_id, order_id, type, sent_at)
settings (id, tenant_id, group, key, value) - configurable thresholds
api_logs (id, tenant_id, user_id, method, endpoint, status, duration)
```

### Relationships

```
Tenant
  ├── hasMany Users
  ├── hasMany MagentoStores
  └── hasMany Orders

Order
  ├── belongsTo Tenant
  ├── belongsTo MagentoStore
  ├── hasMany OrderItems
  ├── hasMany Shipments
  └── hasMany Invoices

MagentoOrderSync
  ├── belongsTo Tenant
  ├── belongsTo MagentoStore
  └── hasOne Order (normalized)
```

## Security Architecture

### Authentication & Authorization

**Admin Panel:**
- Session-based authentication
- Filament's built-in auth
- Tenant-based access control

**API:**
- Sanctum token authentication
- Tenant-scoped tokens
- Rate limiting (60 req/min)
- API request logging

### Tenant Isolation

**Global Scopes:**
```php
// Automatically applied to all tenant-aware models
protected static function booted()
{
    static::addGlobalScope(new TenantScope);
}
```

**Middleware:**
- `InitializeTenancyByDomain` - Set tenant from URL
- `LogApiRequest` - Track API usage per tenant

## Performance Optimizations

### Database Queries

```php
// Eager loading to avoid N+1
Order::with(['items', 'shipments', 'magentoStore'])->get();

// Query optimization with indexes
Schema::table('orders', function (Blueprint $table) {
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'ordered_at']);
});
```

### Caching Strategy

- **Config caching**: `php artisan config:cache`
- **Route caching**: `php artisan route:cache`
- **View caching**: Blade template compilation
- **Model caching**: Coming soon (Redis)

### Asset Optimization

- Vite bundling and minification
- CSS/JS lazy loading
- Filament's optimized asset delivery
- Image optimization (WebP format)

## Deployment Architecture

### Recommended Stack

```
┌─────────────────┐
│  Load Balancer  │
└────────┬────────┘
         │
    ┌────┴────┐
    │   Web   │ Laravel (PHP-FPM + Nginx)
    │ Servers │
    └────┬────┘
         │
    ┌────┴────┐
    │ Database│ MySQL/PostgreSQL
    └─────────┘
         │
    ┌────┴────┐
    │  Queue  │ Redis + Horizon
    └─────────┘
```

### Environment Separation

- **Local**: SQLite + sync queue
- **Staging**: MySQL + Redis
- **Production**: MySQL + Redis + Horizon + CDN

## Extension Points

### Adding New Integrations

1. Create contract interface in `app/Contracts/`
2. Implement adapter in `app/Services/`
3. Register in service provider
4. Add configuration to `.env`

### Adding New Features

1. Database migration
2. Eloquent model with `BelongsToTenant`
3. Filament resource for admin UI
4. API endpoints (optional)
5. Tests (unit + feature)

## Monitoring & Logging

### Application Logs

```php
Log::channel('daily')->info('Order synced', [
    'tenant_id' => $tenant->id,
    'order_id' => $order->id,
]);
```

### API Logging

All API requests automatically logged:
- Request method, endpoint, parameters
- Response status, duration
- User and tenant identification
- Stored in `api_logs` table

### Queue Monitoring

- Laravel Horizon dashboard
- Job metrics and failure tracking
- Real-time queue monitoring
- Automatic retry logic

## Further Reading

- [Multi-Tenancy Implementation](features/multi-tenancy.md)
- [Magento Integration Details](integrations/magento-integration.md)
- [API Documentation](api/overview.md)
