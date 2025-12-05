<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnpaidOrderNotificationCleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->warn('⚠️  This will delete ALL unpaid order notifications from the database.');

        if (! $this->command->confirm('Are you sure you want to continue?', false)) {
            $this->command->info('Cleanup cancelled.');
            return;
        }

        $count = DB::table('unpaid_order_notifications')->count();

        if ($count === 0) {
            $this->command->info('No notifications to delete.');
            return;
        }

        DB::table('unpaid_order_notifications')->truncate();

        $this->command->info("✅ Deleted {$count} notification(s).");
        $this->command->info('The unpaid_order_notifications table is now empty.');
    }
}
