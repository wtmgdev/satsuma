<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'credits_balance',
        'credits_allowance',
        'credits_unlimited',
        'credits_reset_at',
    ];

    protected function casts(): array
    {
        return [
            'credits_balance' => 'integer',
            'credits_allowance' => 'integer',
            'credits_unlimited' => 'boolean',
            'credits_reset_at' => 'datetime',
        ];
    }

    public static function createWithOwner(User $user, string $name): self
    {
        return DB::transaction(function () use ($user, $name) {
            $organization = static::create([
                'name' => $name,
                'slug' => static::uniqueSlug($name),
            ]);

            $organization->members()->attach($user->id, ['role' => 'owner']);

            return $organization;
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'org_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function roleFor(User $user): ?string
    {
        $role = $this->members()->where('users.id', $user->id)->first()?->pivot?->role;

        return is_string($role) ? $role : null;
    }
}
