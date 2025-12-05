<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

class ManageApiTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'api:manage-tokens
                            {action? : Action to perform (list, revoke, cleanup)}
                            {--token= : Token ID for revoke action}
                            {--tenant= : Filter by tenant ID or slug}
                            {--expired : Only show/cleanup expired tokens}';

    /**
     * The console command description.
     */
    protected $description = 'List, revoke, or cleanup API tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? select(
            label: 'What would you like to do?',
            options: [
                'list' => 'List all tokens',
                'revoke' => 'Revoke a specific token',
                'cleanup' => 'Cleanup expired tokens',
            ],
        );

        return match ($action) {
            'list' => $this->listTokens(),
            'revoke' => $this->revokeToken(),
            'cleanup' => $this->cleanupExpiredTokens(),
            default => $this->listTokens(),
        };
    }

    /**
     * List all tokens.
     */
    protected function listTokens(): int
    {
        $query = PersonalAccessToken::query()
            ->orderBy('created_at', 'desc');

        // Filter by tenant if specified
        $tenantOption = $this->option('tenant');
        if ($tenantOption) {
            $tenant = Tenant::where('id', $tenantOption)
                ->orWhere('slug', $tenantOption)
                ->first();

            if ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }
        }

        // Filter expired if specified
        if ($this->option('expired')) {
            $query->where('expires_at', '<', now());
        }

        $tokens = $query->get();

        if ($tokens->isEmpty()) {
            $this->components->info('No tokens found.');

            return Command::SUCCESS;
        }

        $rows = $tokens->map(function ($token) {
            $tenant = $token->tenant_id
                ? Tenant::find($token->tenant_id)?->name ?? "ID: {$token->tenant_id}"
                : 'All';

            $status = $this->getTokenStatus($token);

            return [
                $token->id,
                $token->name,
                $tenant,
                implode(', ', $token->abilities ?? []),
                $token->last_used_at?->diffForHumans() ?? 'Never',
                $token->expires_at?->format('Y-m-d') ?? 'Never',
                $status,
            ];
        })->toArray();

        table(
            headers: ['ID', 'Name', 'Tenant', 'Abilities', 'Last Used', 'Expires', 'Status'],
            rows: $rows,
        );

        $this->newLine();
        $this->components->info("Total tokens: {$tokens->count()}");

        return Command::SUCCESS;
    }

    /**
     * Revoke a specific token.
     */
    protected function revokeToken(): int
    {
        $tokenId = $this->option('token');

        if (! $tokenId) {
            // Show list and let user select
            $tokens = PersonalAccessToken::all();

            if ($tokens->isEmpty()) {
                $this->components->info('No tokens to revoke.');

                return Command::SUCCESS;
            }

            $options = $tokens->mapWithKeys(function ($token) {
                $status = $this->getTokenStatus($token);

                return [$token->id => "{$token->name} [{$status}]"];
            })->toArray();

            $tokenId = select(
                label: 'Select token to revoke',
                options: $options,
            );
        }

        $token = PersonalAccessToken::find($tokenId);

        if (! $token) {
            $this->components->error("Token with ID {$tokenId} not found.");

            return Command::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Token ID', $token->id);
        $this->components->twoColumnDetail('Name', $token->name);
        $this->components->twoColumnDetail('Created', $token->created_at->format('Y-m-d H:i:s'));
        $this->components->twoColumnDetail('Last Used', $token->last_used_at?->format('Y-m-d H:i:s') ?? 'Never');
        $this->newLine();

        if (! $this->option('no-interaction')) {
            $confirmed = confirm(
                label: 'Are you sure you want to revoke this token?',
                default: false,
            );

            if (! $confirmed) {
                $this->components->warn('Token revocation cancelled.');

                return Command::SUCCESS;
            }
        }

        $token->delete();

        $this->components->success("Token '{$token->name}' has been revoked.");

        return Command::SUCCESS;
    }

    /**
     * Cleanup expired tokens.
     */
    protected function cleanupExpiredTokens(): int
    {
        $expiredTokens = PersonalAccessToken::query()
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredTokens->isEmpty()) {
            $this->components->info('No expired tokens to cleanup.');

            return Command::SUCCESS;
        }

        $this->components->warn("Found {$expiredTokens->count()} expired token(s).");
        $this->newLine();

        foreach ($expiredTokens as $token) {
            $this->line("  - {$token->name} (expired: {$token->expires_at->format('Y-m-d')})");
        }

        $this->newLine();

        if (! $this->option('no-interaction')) {
            $confirmed = confirm(
                label: 'Delete all expired tokens?',
                default: true,
            );

            if (! $confirmed) {
                $this->components->warn('Cleanup cancelled.');

                return Command::SUCCESS;
            }
        }

        $count = PersonalAccessToken::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->components->success("Deleted {$count} expired token(s).");

        return Command::SUCCESS;
    }

    /**
     * Get token status string.
     */
    protected function getTokenStatus(PersonalAccessToken $token): string
    {
        if ($token->expires_at && $token->expires_at->isPast()) {
            return 'Expired';
        }

        if ($token->last_used_at && $token->last_used_at->isAfter(now()->subDay())) {
            return 'Active';
        }

        return 'Valid';
    }
}
