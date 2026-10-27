<?php

namespace App\Models;

use App\ConnectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'url',
        'api_token',
        'wp_version',
        'php_version',
        'connection_status',
        'last_ping_at',
    ];

    protected $attributes = [
        'connection_status' => ConnectionStatus::Offline->value,
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'connection_status' => ConnectionStatus::class,
            'last_ping_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plugins(): HasMany
    {
        return $this->hasMany(SitePlugin::class);
    }

    public function themes(): HasMany
    {
        return $this->hasMany(SiteTheme::class);
    }
}
