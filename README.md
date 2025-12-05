# FlowOMS

> A multi-tenant Order Management System for Magento 2 stores, built with Laravel 12 and Filament 4.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-4-orange.svg)](https://filamentphp.com)

**Repository**: [https://github.com/florinel-chis/flow-oms](https://github.com/florinel-chis/flow-oms)

## Tech Stack

- **Laravel 12** - PHP framework
- **Filament 4** - Admin panel framework
- **Vite** - Frontend build tool
- **SQLite/MySQL** - Database
- **Pest PHP** - Testing framework
- **Playwright** - E2E testing

## Installation

### Clone Repository

```bash
git clone https://github.com/florinel-chis/flow-oms.git
cd flow-oms
```

### Initial Setup

```bash
# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate Filament assets
php artisan filament:assets

# Setup database
php artisan migrate --seed

# Create admin user
php artisan filament:user
```

## Quick Start

After installation, start the application:

```bash
# Start development server with queue worker and logs
composer dev

# Or use artisan serve only
php artisan serve
```

Visit `http://127.0.0.1:8000/admin` to access the Filament admin panel.

## Development

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
- **REST API** - Sanctum-authenticated API with tenant scoping
- **E2E Testing** - Comprehensive Playwright test suite

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **Issues**: [GitHub Issues](https://github.com/florinel-chis/flow-oms/issues)
- **Documentation**: Check the inline code documentation and Laravel/Filament docs

## License

FlowOMS is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Built With

- [Laravel](https://laravel.com) - The PHP Framework
- [Filament](https://filamentphp.com) - Admin Panel Framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Playwright](https://playwright.dev) - E2E testing framework

---

Made with ❤️ by [Florinel Chis](https://github.com/florinel-chis)
