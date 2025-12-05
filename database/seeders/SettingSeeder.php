<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Ready to Ship Settings
            [
                'group' => 'ready_to_ship',
                'key' => 'payment_statuses',
                'value' => ['paid'],
                'type' => 'json',
                'description' => 'Payment statuses that qualify an order as ready to ship',
            ],
            [
                'group' => 'ready_to_ship',
                'key' => 'order_statuses',
                'value' => ['processing'],
                'type' => 'json',
                'description' => 'Order statuses that qualify an order as ready to ship',
            ],
            [
                'group' => 'ready_to_ship',
                'key' => 'check_shipments',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Exclude orders that already have shipments',
            ],

            // SLA Settings
            [
                'group' => 'sla',
                'key' => 'same_day_shipping_hours',
                'value' => 6,
                'type' => 'integer',
                'description' => 'Hours before SLA breach for same day shipping',
            ],
            [
                'group' => 'sla',
                'key' => 'overnight_shipping_hours',
                'value' => 12,
                'type' => 'integer',
                'description' => 'Hours before SLA breach for overnight shipping',
            ],
            [
                'group' => 'sla',
                'key' => 'express_shipping_hours',
                'value' => 24,
                'type' => 'integer',
                'description' => 'Hours before SLA breach for express shipping',
            ],
            [
                'group' => 'sla',
                'key' => 'standard_shipping_hours',
                'value' => 48,
                'type' => 'integer',
                'description' => 'Hours before SLA breach for standard shipping',
            ],
            [
                'group' => 'sla',
                'key' => 'business_hours_start',
                'value' => 9,
                'type' => 'integer',
                'description' => 'Business day start hour (24h format)',
            ],
            [
                'group' => 'sla',
                'key' => 'business_hours_end',
                'value' => 17,
                'type' => 'integer',
                'description' => 'Business day end hour (24h format)',
            ],
            [
                'group' => 'sla',
                'key' => 'business_days',
                'value' => [1, 2, 3, 4, 5],
                'type' => 'json',
                'description' => 'Business days (1=Monday, 7=Sunday)',
            ],
            [
                'group' => 'sla',
                'key' => 'holidays',
                'value' => [],
                'type' => 'json',
                'description' => 'Holiday dates array (Y-m-d format)',
            ],
            [
                'group' => 'sla',
                'key' => 'operate_24_7',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Operate 24/7 (bypass business hours)',
            ],
            [
                'group' => 'sla',
                'key' => 'breach_warning_hours',
                'value' => 2,
                'type' => 'integer',
                'description' => 'Hours before deadline to trigger warning',
            ],
            [
                'group' => 'sla',
                'key' => 'shipping_method_patterns',
                'value' => [
                    'same_day_shipping_hours' => ['same.*day', 'rush', 'immediate'],
                    'overnight_shipping_hours' => ['overnight', 'next.*day', '1.*day', 'express.*overnight'],
                    'express_shipping_hours' => ['express', '2.*day', 'fedex.*2day', 'priority', 'expedited', 'dhl'],
                    'standard_shipping_hours' => ['.*'],
                ],
                'type' => 'json',
                'description' => 'Regex patterns to match shipping methods to SLA hours',
            ],

            // Dashboard Settings
            [
                'group' => 'dashboard',
                'key' => 'unpaid_warning_threshold',
                'value' => 10,
                'type' => 'integer',
                'description' => 'Number of unpaid orders before showing danger state',
            ],
            [
                'group' => 'dashboard',
                'key' => 'unpaid_info_threshold',
                'value' => 5,
                'type' => 'integer',
                'description' => 'Number of unpaid orders before showing warning state',
            ],
            [
                'group' => 'dashboard',
                'key' => 'target_sla_compliance',
                'value' => 95,
                'type' => 'integer',
                'description' => 'Target SLA compliance percentage',
            ],

            // Notification Settings
            [
                'group' => 'notifications',
                'key' => 'payment_reminder_days',
                'value' => 3,
                'type' => 'integer',
                'description' => 'Days after order before sending payment reminder',
            ],
            [
                'group' => 'notifications',
                'key' => 'delay_notification_hours',
                'value' => 24,
                'type' => 'integer',
                'description' => 'Hours after expected delivery before sending delay notification',
            ],
        ];

        // Seed settings for all existing tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($settings as $setting) {
                Setting::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'group' => $setting['group'],
                        'key' => $setting['key'],
                    ],
                    array_merge($setting, ['tenant_id' => $tenant->id])
                );
            }
        }
    }
}
