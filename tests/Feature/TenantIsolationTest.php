<?php

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\Site;
use App\Models\SitePlugin;
use App\Models\SiteTheme;
use App\Models\User;

function memberOf(Organization $organization, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $organization->members()->attach($user->id, ['role' => $role]);

    return $user;
}

it('shows only the current organization on the dashboard', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization);
    $site = Site::factory()->for($organization)->create();
    SitePlugin::factory()->for($site)->create(['update_available' => true]);
    SiteTheme::factory()->for($site)->create(['update_available' => true]);
    Client::factory()->for($organization)->create();

    $other = Organization::factory()->create();
    $otherSite = Site::factory()->for($other)->create();
    SitePlugin::factory()->for($otherSite)->create(['update_available' => true]);
    SiteTheme::factory()->for($otherSite)->create(['update_available' => true]);
    Client::factory()->for($other)->create();

    $props = $this->actingAs($user)->get('/dashboard')->inertiaProps();

    expect(collect($props['sites'])->pluck('id')->all())->toBe([$site->id])
        ->and(collect($props['clients'])->pluck('id')->all())->toBe([$organization->clients->first()->id])
        ->and($props['stats']['totalSites'])->toBe(1)
        ->and($props['stats']['pendingUpdates'])->toBe(2);
});

it('rejects a client from another organization when storing a site', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization);
    $foreignClient = Client::factory()->for(Organization::factory()->create())->create();

    $this->actingAs($user)->post('/sites', [
        'client_id' => $foreignClient->id,
        'name' => 'Acme Blog',
        'url' => 'https://acme-store.test',
    ])->assertSessionHasErrors('client_id');
});

it('rejects a client from another organization when updating a site', function () {
    $organization = Organization::factory()->create();
    $user = memberOf($organization);
    $site = Site::factory()->for($organization)->create();
    $foreignClient = Client::factory()->for(Organization::factory()->create())->create();

    $this->actingAs($user)->put("/sites/{$site->id}", [
        'client_id' => $foreignClient->id,
        'name' => $site->name,
        'url' => $site->url,
    ])->assertSessionHasErrors('client_id');
});

it('never exposes cost estimates on the usage page', function () {
    $organization = Organization::factory()->create(['credits_balance' => 100]);
    $user = memberOf($organization);
    $site = Site::factory()->for($organization)->create();

    CreditTransaction::create([
        'organization_id' => $organization->id,
        'site_id' => $site->id,
        'action' => 'pagespeed_analysis',
        'credits' => -2,
        'quantity' => 2,
        'cost_estimate' => 0.0040,
    ]);

    $props = $this->actingAs($user)->get('/usage')->inertiaProps();

    expect($props)->not->toHaveKey('estimatedCost')
        ->and(collect($props['transactions'])->every(fn ($t) => ! array_key_exists('cost_estimate', $t)))->toBeTrue()
        ->and(collect($props['byAction'])->every(fn ($row) => ! array_key_exists('cost', $row)))->toBeTrue()
        ->and($props['credits']['purchased'])->toBe(0);
});
