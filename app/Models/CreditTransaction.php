<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'site_id',
        'action',
        'credits',
        'quantity',
        'cost_estimate',
        'description',
        'metered',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'quantity' => 'integer',
            'cost_estimate' => 'decimal:4',
            'metered' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Only actions that consume credits (excludes resets, grants, expiry).
     */
    public function scopeUsage(Builder $query): Builder
    {
        return $query->whereIn('action', array_keys(config('credits.costs')));
    }
}
