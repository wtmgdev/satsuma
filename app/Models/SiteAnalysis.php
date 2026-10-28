<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteAnalysis extends Model
{
    protected $fillable = [
        'site_id',
        'type',
        'data',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'score' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The most recent analysis of the given type for a site.
     */
    public static function latestFor(Site $site, string $type): ?self
    {
        return static::query()
            ->where('site_id', $site->id)
            ->where('type', $type)
            ->latest()
            ->first();
    }
}
