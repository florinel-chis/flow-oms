# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

FlowOMS SaaS - A multi-tenant Order Management System built with Laravel 12 and Filament 4. The system connects to Magento 2 stores, enriches order data with third-party integrations (shipping tracking, email providers), and provides operational dashboards for e-commerce teams.

## Development Commands

```bash
# Full development environment (server, queue, logs, vite)
composer dev

# Setup fresh project
composer setup

# Run tests
composer test
# Or directly:
./vendor/bin/pest
./vendor/bin/pest --filter=TestName    # Single test
./vendor/bin/pest tests/Unit           # Specific directory

# Code formatting
./vendor/bin/pint

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Filament admin user
php artisan filament:user

# Queue worker (development)
php artisan queue:work
# Production with Horizon
php artisan horizon
```

## Architecture

### Multi-Tenancy Pattern
- Single database with `tenant_id` column on all tenant-aware tables
- Filament's built-in tenancy with slug-based URLs: `/admin/{tenant-slug}/orders`
- Global scopes via `BelongsToTenant` trait auto-filter queries
- Tenant scoping middleware applied to all Filament requests

### Directory Structure (Target)
```
app/
├── Contracts/           # Adapter interfaces (Email, Shipping, Magento)
├── DTOs/               # Data Transfer Objects for API responses
├── Enums/              # OrderStatus, PaymentStatus, ShipmentStatus
├── Events/             # Domain events (OrderSynced, SlaBreachImminent)
├── Filament/
│   ├── Pages/          # Custom pages including Dashboard
│   ├── Resources/      # CRUD resources (Order, MagentoStore, etc.)
│   └── Widgets/        # Dashboard widgets (KPIs, order panels)
├── Jobs/               # Queue jobs (SyncOrders, SendNotification)
├── Models/
│   └── Concerns/       # Traits like BelongsToTenant
└── Services/
    ├── Magento/        # MagentoApiClient, OrderSyncService
    ├── Email/          # EmailAdapterFactory + adapters
    └── Shipping/       # ShippingAdapterFactory + adapters
```

### Adapter Pattern
External integrations use the adapter pattern for flexibility:
- **Email**: `EmailAdapterInterface` with SendGrid, Mailchimp, Postmark implementations
- **Shipping**: `ShippingAdapterInterface` with AfterShip, UPS, DHL, FedEx implementations
- Adapters resolved via factories based on tenant configuration

### Database Entities
```
tenants, users, tenant_user (pivot)
magento_stores, magento_sync_logs
orders, order_items, shipments
integrations, notification_templates, notification_history
webhook_endpoints, webhook_deliveries
sla_policies, sla_breaches
```

## Key Technical Decisions

| Area | Choice |
|------|--------|
| Multi-tenancy | Single DB with tenant_id |
| Queue | Laravel Horizon + Redis |
| Real-time | Polling / Laravel Reverb |
| Testing | Pest PHP |
| Admin Panel | Filament 4.x |

## Planned Artisan Commands

```bash
# Order synchronization
php artisan magento:sync-orders        # Incremental sync
php artisan magento:sync-orders --full # Full sync

# Tracking
php artisan tracking:sync

# SLA management
php artisan sla:recalculate
php artisan sla:monitor
```

## Implementation Phases

See `docs/tasks/README.md` for detailed task breakdown. Priority order:
1. Foundation & Multi-Tenancy (P0)
2. Magento Integration (P0)
3. Dashboard & UI (P0)
4. Email Integration (P0-P1)
5. Shipping & Tracking (P1)
6. Webhooks & Events (P2)
7. SLA Management (P1)
8. Testing (P0)
9. Deployment (P0-P1)

## Testing

Tests use in-memory SQLite (configured in `phpunit.xml`):
```bash
./vendor/bin/pest                      # All tests
./vendor/bin/pest --parallel           # Parallel execution
./vendor/bin/pest --coverage           # With coverage
```

## Filament Resources

Filament resources auto-discovered from `app/Filament/Resources`. Key resources to implement:
- `OrderResource` - Order management with status workflows
- `MagentoStoreResource` - Store connection with credential encryption
- `IntegrationResource` - Third-party service configuration
- `SlaPolicyResource` - SLA rule configuration

## Specialized Agents

Use these agents for specific tasks (invoke via Task tool):

| Agent | Use For |
|-------|---------|
| `magento-api-expert` | Magento 2 REST API integration, order sync, webhooks, searchCriteria queries |
| `filament-builder` | Filament resources, pages, widgets, tables, forms, dashboard components |
| `laravel-architect` | Architectural decisions, service design, jobs, events, database schema |
| `integration-builder` | Email/shipping adapters, webhook systems, third-party integrations |
| `test-writer` | Pest PHP tests, unit tests, feature tests, testing tenant isolation |

## Skills

Skills are auto-loaded by agents and provide domain-specific patterns:

| Skill | Provides |
|-------|----------|
| `magento2-rest-api` | Magento API patterns, searchCriteria, DTOs, authentication |
| `laravel-multi-tenancy` | BelongsToTenant trait, tenant scoping, Filament tenancy |
| `adapter-pattern` | Interface contracts, factory pattern, email/shipping adapters |
| `filament4-patterns` | Dashboard widgets, table widgets, resources, forms, actions |

## MCP Servers

### Magento API MCP

The `magento-api` MCP server provides offline access to Magento 2 REST API documentation:

```bash
pip install magento-api-mcp

# Tools: search_endpoints, get_endpoint_details, list_tags, search_schemas, get_schema
```

### Filament Docs MCP

The `filament-docs` MCP server provides offline access to Filament 4 documentation:

```bash
cd tools/filament-docs-mcp && pip install -e .

# Tools available:
# - search_docs: Full-text search across Filament docs
# - get_doc_section: Get complete documentation by ID
# - list_packages: List all packages (forms, tables, actions, etc.)
# - search_code_examples: Find PHP code examples
# - get_component_docs: Get docs for TextInput, Table, etc.
# - get_filament4_migration_guide: Get Filament 4 upgrade info
```

Configuration in `.claude/settings.json`.

## Key Documentation

- Filament 4: https://filamentphp.com/docs
- Laravel 12: https://laravel.com/docs/12.x
- Pest PHP: https://pestphp.com/docs
- Magento 2 REST API: https://developer.adobe.com/commerce/webapi/rest/
