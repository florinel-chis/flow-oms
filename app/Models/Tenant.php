<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model implements HasAvatar, HasCurrentTenantLabel, HasName
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subscription_tier',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function magentoStores(): HasMany
    {
        return $this->hasMany(MagentoStore::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->settings['avatar_url'] ?? null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Active Workspace';
    }
}
