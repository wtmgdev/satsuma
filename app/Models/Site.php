<?php

namespace App\Models;

use App\ConnectionStatus;
use App\ScanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'client_id',
        'name',
        'url',
        'api_token',
        'wp_version',
        'php_version',
        'connection_status',
        'scan_status',
        'last_ping_at',
        'last_scan_at',
    ];

    protected $attributes = [
        'connection_status' => ConnectionStatus::Offline->value,
        'scan_status' => ScanStatus::Idle->value,
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'connection_status' => ConnectionStatus::class,
            'scan_status' => ScanStatus::class,
            'last_ping_at' => 'datetime',
            'last_scan_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
