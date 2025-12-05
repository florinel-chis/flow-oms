<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MagentoStore extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'access_token',
        'api_version',
        'sync_enabled',
        'last_sync_at',
        'webhook_secret',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = ['access_token'];

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    public function getApiEndpoint(string $path = ''): string
    {
        $baseUrl = rtrim($this->base_url, '/');
        $path = ltrim($path, '/');

        return "{$baseUrl}/rest/{$this->api_version}".($path ? "/{$path}" : '');
    }
}
