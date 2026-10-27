<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgMembership extends Model
{
    public const ROLE_RANKS = [
        'member' => 1,
        'admin' => 2,
        'owner' => 3,
    ];

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atLeast(string $role): bool
    {
        return (self::ROLE_RANKS[$this->role] ?? 0) >= (self::ROLE_RANKS[$role] ?? 99);
    }
}
