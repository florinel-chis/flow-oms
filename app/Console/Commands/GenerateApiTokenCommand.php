<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class GenerateApiTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'api:generate-token
                            {name? : Name/identifier for the token}
                            {--tenant= : Tenant ID or slug to restrict token to a specific tenant}
                            {--expires-in= : Token expiration (30d, 90d, 1y, or never)}
                            {--abilities= : Comma-separated list of abilities (default: shipments:update-delivery)}
                            {--description= : Description of what this token is used for}
                            {--user= : User email to associate the token with (defaults to first admin)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate an API token for external carrier systems';

    /**
     * Default abilities for carrier tokens.
     */
    protected array $defaultAbilities = [
        'shipments:update-delivery',
    ];

    /**
     * Available expiration options.
     */
    protected array $expirationOptions = [
        '30d' => 30,
        '90d' => 90,
        '1y' => 365,
        'never' => null,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->info('API Token Generator');
        $this->newLine();

        // Get or prompt for token name
        $name = $this->argument('name') ?? text(
            label: 'Token name/identifier',
            placeholder: 'e.g., UPS-Carrier-Integration',
            required: true,
            hint: 'A unique identifier for this token',
        );

        // Get or prompt for tenant
        $tenant = $this->resolveTenant();

        // Get or prompt for expiration
        $expiresAt = $this->resolveExpiration();

        // Get abilities
        $abilities = $this->resolveAbilities();

        // Get description
        $description = $this->option('description') ?? text(
            label: 'Description (optional)',
            placeholder: 'e.g., Token for UPS delivery webhook updates',
        );

        // Find user to associate token with
        $user = $this->resolveUser();

        if (! $user) {
            $this->components->error('No user found to associate the token with.');
            $this->newLine();
            $this->info('Create a user first with: php artisan filament:user');

            return Command::FAILURE;
        }

        // Confirm creation
        $this->displayTokenSummary($name, $tenant, $expiresAt, $abilities, $description, $user);

        if (! $this->option('no-interaction')) {
            $confirmed = confirm(
                label: 'Generate this token?',
                default: true,
            );

            if (! $confirmed) {
                $this->components->warn('Token generation cancelled.');

                return Command::SUCCESS;
            }
        }

        // Generate the token
        $token = $user->createToken(
            name: $name,
            abilities: $abilities,
            expiresAt: $expiresAt,
        );

        // Store additional metadata
        $this->storeTokenMetadata(
            token: $token->accessToken,
            tenantId: $tenant?->id,
            description: $description,
            user: $user,
        );

        // Display the token (only shown once!)
        $this->displayToken($token->plainTextToken, $name);

        return Command::SUCCESS;
    }

    /**
     * Resolve the tenant from options or prompt.
     */
    protected function resolveTenant(): ?Tenant
    {
        $tenantOption = $this->option('tenant');

        if ($tenantOption === null) {
            $tenants = Tenant::all();

            if ($tenants->isEmpty()) {
                return null;
            }

            $options = $tenants->mapWithKeys(fn ($t) => [$t->id => "{$t->name} ({$t->slug})"]);
            $options->put('all', 'All tenants (multi-tenant carrier)');

            $choice = select(
                label: 'Restrict to specific tenant?',
                options: $options->all(),
                default: 'all',
            );

            if ($choice === 'all') {
                return null;
            }

            return Tenant::find($choice);
        }

        if ($tenantOption === 'all' || $tenantOption === '') {
            return null;
        }

        // Try to find by ID or slug
        return Tenant::where('id', $tenantOption)
            ->orWhere('slug', $tenantOption)
            ->first();
    }

    /**
     * Resolve expiration from options or prompt.
     */
    protected function resolveExpiration(): ?\DateTimeInterface
    {
        $expiresIn = $this->option('expires-in');

        if (! $expiresIn) {
            $expiresIn = select(
                label: 'Token expiration',
                options: [
                    '30d' => '30 days',
                    '90d' => '90 days',
                    '1y' => '1 year',
                    'never' => 'Never expires',
                ],
                default: '90d',
            );
        }

        $days = $this->expirationOptions[$expiresIn] ?? null;

        return $days ? now()->addDays($days) : null;
    }

    /**
     * Resolve abilities from options.
     */
    protected function resolveAbilities(): array
    {
        $abilitiesOption = $this->option('abilities');

        if ($abilitiesOption) {
            return array_map('trim', explode(',', $abilitiesOption));
        }

        return $this->defaultAbilities;
    }

    /**
     * Resolve the user to associate the token with.
     */
    protected function resolveUser(): ?User
    {
        $email = $this->option('user');

        if ($email) {
            return User::where('email', $email)->first();
        }

        // Find the first user (typically the admin)
        return User::first();
    }

    /**
     * Store additional metadata on the token.
     */
    protected function storeTokenMetadata(
        PersonalAccessToken $token,
        ?int $tenantId,
        ?string $description,
        User $user,
    ): void {
        $token->update([
            'tenant_id' => $tenantId,
            'description' => $description,
            'created_by_name' => $user->name,
            'created_by_email' => $user->email,
        ]);
    }

    /**
     * Display token summary before creation.
     */
    protected function displayTokenSummary(
        string $name,
        ?Tenant $tenant,
        ?\DateTimeInterface $expiresAt,
        array $abilities,
        ?string $description,
        User $user,
    ): void {
        $this->newLine();
        $this->components->twoColumnDetail('Token Name', $name);
        $this->components->twoColumnDetail(
            'Tenant',
            $tenant ? "{$tenant->name} ({$tenant->slug})" : 'All tenants'
        );
        $this->components->twoColumnDetail(
            'Expires',
            $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : 'Never'
        );
        $this->components->twoColumnDetail('Abilities', implode(', ', $abilities));
        $this->components->twoColumnDetail('Description', $description ?: '(none)');
        $this->components->twoColumnDetail('Associated User', "{$user->name} ({$user->email})");
        $this->newLine();
    }

    /**
     * Display the generated token.
     */
    protected function displayToken(string $plainTextToken, string $name): void
    {
        $this->newLine();
        $this->components->success('Token generated successfully!');
        $this->newLine();

        $this->components->warn('IMPORTANT: Copy this token now. It will not be shown again!');
        $this->newLine();

        $this->line('  <fg=green>Bearer Token:</>');
        $this->newLine();
        $this->line("  <fg=yellow>{$plainTextToken}</>");
        $this->newLine();

        $this->components->info('Usage example:');
        $this->newLine();
        $this->line('  curl -X PATCH "https://your-domain.com/api/v1/shipments/95/delivery" \\');
        $this->line('    -H "Authorization: Bearer '.Str::limit($plainTextToken, 20, '...').'" \\');
        $this->line('    -H "Content-Type: application/json" \\');
        $this->line('    -H "Accept: application/json" \\');
        $this->line('    -d \'{"delivered_at": "2024-01-15T14:30:00Z", "signature": "John Doe"}\'');
        $this->newLine();
        $this->components->warn('Note: Use the Magento shipment entity_id (e.g., 95) in the URL, not the tracking number.');
        $this->newLine();
    }
}
