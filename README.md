# FlowOMS SaaS

A multi-tenant Order Management System for Magento 2 stores, built with Laravel 12 and Filament 4.

## Tech Stack

- **Laravel 12** - PHP framework
- **Filament 4** - Admin panel framework
- **Vite** - Frontend build tool
- **SQLite/MySQL** - Database
- **Pest PHP** - Testing framework
- **Playwright** - E2E testing

## Quick Start

### 🚀 One-Command Start

```bash
./start.sh
```

This will:
- Check for existing processes and stop them
- Find an available port (starting with 8000)
- Start Laravel server, queue worker, logs, and Vite
- Display URLs for accessing the application

Press `Ctrl+C` to stop all services.

### 🛑 Stop Application

```bash
./stop.sh
```

### 📊 Check Status

```bash
./status.sh
```

### 🔄 Restart Application

```bash
./restart.sh
```

## Initial Setup

First time setup:

```bash
# Install dependencies and configure
composer setup

# Start the application
./start.sh
```

Visit `http://127.0.0.1:8000/admin` to access the Filament admin panel.

## Application Management Scripts

Four shell scripts are provided for managing the application:

| Script | Purpose |
|--------|---------|
| `start.sh` | Start all services (Laravel, Queue, Logs, Vite) |
| `stop.sh` | Stop all services gracefully |
| `restart.sh` | Restart all services |
| `status.sh` | Show status of all services and ports |

See [SCRIPTS.md](SCRIPTS.md) for detailed documentation.

## Development

### Running the Application

```bash
# Start all services
./start.sh

# Or use composer directly (no auto port detection)
composer dev
```

### Syncing Magento Orders

```bash
# Initial sync - pull last 30 days
php artisan magento:sync-orders --truncate --backfill --sync

# Incremental sync
php artisan magento:sync-orders --sync

# Test single order
php artisan magento:sync-order 12345
```

### Running Tests

```bash
# Unit/Feature tests
composer test
# Or
./vendor/bin/pest

# E2E tests
npm run test:e2e

# Watch E2E tests
npm run test:e2e:watch
```

## Key Features

- **Multi-tenant architecture** - Single database with tenant_id scoping
- **Magento 2 integration** - Real-time order sync via REST API
- **Two-phase sync** - Raw data storage + normalized transformation
- **Automated scheduler** - 30min/daily/weekly sync jobs
- **Queue processing** - Background job handling
- **Filament admin** - Modern, reactive admin interface
- **Real-time logs** - Built-in log tailer

## Documentation

- [SCRIPTS.md](SCRIPTS.md) - Application management scripts
- [CLAUDE.md](CLAUDE.md) - Development guide for Claude Code
- [Plan](/.claude/plans/sassy-zooming-tome.md) - Magento sync implementation plan

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. Laravel provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
