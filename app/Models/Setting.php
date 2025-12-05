<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting value by group and key for the current tenant
     *
     * @param  string  $group  Setting group (e.g., 'ready_to_ship')
     * @param  string  $key  Setting key (e.g., 'payment_statuses')
     * @param  mixed  $default  Default value if setting not found
     * @param  Tenant|int|null  $tenant  Explicit tenant (optional, uses current tenant if null)
     * @return mixed
     *
     * @throws \RuntimeException if no tenant context available
     */
    public static function get(string $group, string $key, $default = null, Tenant|int|null $tenant = null)
    {
        $tenant = static::resolveTenant($tenant);

        if (! $tenant) {
            throw new \RuntimeException(
                'Setting::get() requires tenant context. '.
                'Pass explicit tenant parameter when calling from console/queue, '.
                'or ensure Filament tenant is set in web requests.'
            );
        }

        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        // Cache key format: settings:{tenant_id}:{group}:{key}
        $cacheKey = "settings:{$tenantId}:{$group}:{$key}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($group, $key, $default, $tenantId) {
            $setting = static::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            return $setting ? $setting->getValue() : $default;
        });
    }

    /**
     * Set a setting value by group and key for the current tenant
     *
     * @param  string  $group  Setting group
     * @param  string  $key  Setting key
     * @param  mixed  $value  Setting value
     * @param  string  $type  Value type (string, json, boolean, integer)
     * @param  string|null  $description  Optional description
     * @param  Tenant|int|null  $tenant  Explicit tenant (optional)
     * @return Setting
     *
     * @throws \RuntimeException if no tenant context available
     */
    public static function set(string $group, string $key, $value, string $type = 'string', ?string $description = null, Tenant|int|null $tenant = null)
    {
        $tenant = static::resolveTenant($tenant);

        if (! $tenant) {
            throw new \RuntimeException(
                'Setting::set() requires tenant context. '.
                'Pass explicit tenant parameter when calling from console/queue.'
            );
        }

        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        $setting = static::withoutGlobalScope('tenant')->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]
        );

        // Clear cache for this setting
        $cacheKey = "settings:{$tenantId}:{$group}:{$key}";
        Cache::forget($cacheKey);

        return $setting;
    }

    /**
     * Clear all cached settings for a tenant
     */
    public static function clearCache(Tenant|int|null $tenant = null): void
    {
        $tenant = static::resolveTenant($tenant);

        if (! $tenant) {
            return;
        }

        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        // Clear all settings cache for this tenant
        Cache::flush(); // In production, use a more targeted approach with cache tags
    }

    /**
     * Resolve tenant from parameter or current Filament context
     */
    protected static function resolveTenant(Tenant|int|null $tenant = null): Tenant|int|null
    {
        return $tenant ?? Filament::getTenant();
    }

    /**
     * Get the typed value based on the type field
     */
    public function getValue()
    {
        return match ($this->type) {
            'json' => is_string($this->value) ? json_decode($this->value, true) : $this->value,
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            default => $this->value,
        };
    }

    /**
     * Clear cache when settings are updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function (Setting $setting) {
            if ($setting->tenant_id) {
                $cacheKey = "settings:{$setting->tenant_id}:{$setting->group}:{$setting->key}";
                Cache::forget($cacheKey);
            }
        });

        static::deleted(function (Setting $setting) {
            if ($setting->tenant_id) {
                $cacheKey = "settings:{$setting->tenant_id}:{$setting->group}:{$setting->key}";
                Cache::forget($cacheKey);
            }
        });
    }
}
