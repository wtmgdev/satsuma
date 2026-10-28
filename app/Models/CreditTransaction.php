<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'credits',
        'quantity',
        'cost_estimate',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'quantity' => 'integer',
            'cost_estimate' => 'decimal:4',
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
}
