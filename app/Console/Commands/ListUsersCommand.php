<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:list
                            {--with-tenants : Include tenant information}
                            {--email= : Filter by email}
                            {--tenant= : Filter by tenant slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users in the system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = User::query();

        // Apply filters
        if ($email = $this->option('email')) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($tenantSlug = $this->option('tenant')) {
            $query->whereHas('tenants', fn ($q) => $q->where('slug', $tenantSlug));
        }

        // Load relationships if requested
        if ($this->option('with-tenants')) {
            $query->with('tenants');
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');

            return self::SUCCESS;
        }

        // Prepare table data
        $headers = ['ID', 'Name', 'Email', 'Created'];

        if ($this->option('with-tenants')) {
            $headers[] = 'Tenants';
        }

        $rows = $users->map(function ($user) {
            $row = [
                $user->id,
                $user->name,
                $user->email,
                $user->created_at->format('Y-m-d H:i:s'),
            ];

            if ($this->option('with-tenants')) {
                $tenants = $user->tenants->map(fn ($t) => "{$t->name} ({$t->slug})")->join(', ') ?: 'None';
                $row[] = $tenants;
            }

            return $row;
        });

        $this->table($headers, $rows);

        $this->info("Total users: {$users->count()}");

        return self::SUCCESS;
    }
}
