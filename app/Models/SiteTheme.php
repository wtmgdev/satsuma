<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteTheme extends Model
{
    /** @use HasFactory<\Database\Factories\SiteThemeFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'version',
        'is_active',
        'update_available',
        'new_version',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'update_available' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
