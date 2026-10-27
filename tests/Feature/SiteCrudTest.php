<?php

use App\Jobs\SyncSiteStatusJob;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function userWithOrg(string $role = 'owner'): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->members()->attach($user->id, ['role' => $role]);

    return [$user, $organization];
}

it('creates a site with a generated token and queues the first scan', function () {
    Queue::fake();
    [$user, $organization] = userWithOrg();
    $client = Client::factory()->for($organization)->create();

    $response = $this->actingAs($user)->post('/sites', [
        'client_id' => $client->id,
        'name' => 'Acme Blog',
        'url' => 'https://acme.test',
    ]);

    $site = Site::where('url', 'https://acme.test')->first();

    expect($site)->not->toBeNull()
        ->and($site->organization_id)->toBe($organization->id)
        ->and($site->client_id)->toBe($client->id)
        ->and($site->api_token)->not->toBeEmpty()
        ->and($response->headers->get('Location'))->toContain("/sites/{$site->id}");

    Queue::assertPushed(SyncSiteStatusJob::class);
});

it('still creates the site when the queue backend is down', function () {
    Illuminate\Support\Facades\Queue::shouldReceive('connection')
        ->andThrow(new RuntimeException('Connection refused'));
    Illuminate\Support\Facades\Exceptions::fake();

    [$user, $organization] = userWithOrg();

    $response = $this->actingAs($user)->post('/sites', [
        'name' => 'Acme Blog',
        'url' => 'https://acme.test',
    ]);

    expect(Site::where('url', 'https://acme.test')->exists())->toBeTrue()
        ->and($response->headers->get('Location'))->toEndWith('/sites/1');

    Illuminate\Support\Facades\Exceptions::assertReported(RuntimeException::class);
});

it('still responds when a scan is requested while the queue is down', function () {
    [$user, $organization] = userWithOrg();
    $site = Site::factory()->for($organization)->create();

    Illuminate\Support\Facades\Queue::shouldReceive('connection')
        ->andThrow(new RuntimeException('Connection refused'));
    Illuminate\Support\Facades\Exceptions::fake();

    $this->actingAs($user)
        ->post("/sites/{$site->id}/scan")
        ->assertRedirect();

    Illuminate\Support\Facades\Exceptions::assertReported(RuntimeException::class);
});

it('validates duplicate site urls', function () {
    [$user, $organization] = userWithOrg();
    Site::factory()->for($organization)->create(['url' => 'https://acme.test']);

    $this->actingAs($user)
        ->post('/sites', ['name' => 'Other', 'url' => 'https://acme.test'])
        ->assertSessionHasErrors('url');
});

it('updates a site without regenerating the token when blank', function () {
    [$user, $organization] = userWithOrg();
    $site = Site::factory()->for($organization)->create();

    $this->actingAs($user)
        ->put("/sites/{$site->id}", [
            'name' => 'Renamed',
            'url' => $site->url,
            'api_token' => '',
        ])
        ->assertRedirect();

    $site->refresh();

    expect($site->name)->toBe('Renamed')
        ->and($site->api_token)->not->toBeEmpty();
});

it('deletes a site', function () {
    [$user, $organization] = userWithOrg();
    $site = Site::factory()->for($organization)->create();

    $this->actingAs($user)
        ->delete("/sites/{$site->id}")
        ->assertRedirect('/sites');

    expect(Site::find($site->id))->toBeNull();
});

it('forbids members from deleting sites', function () {
    [$user, $organization] = userWithOrg('member');
    $site = Site::factory()->for($organization)->create();

    $this->actingAs($user)
        ->delete("/sites/{$site->id}")
        ->assertForbidden();

    expect(Site::find($site->id))->not->toBeNull();
});

it('hides other organizations sites from the index', function () {
    [$user, $organization] = userWithOrg();
    Site::factory()->for($organization)->create(['name' => 'Mine']);
    Site::factory()->create(['name' => 'Theirs']);

    $response = $this->actingAs($user)->get('/sites');

    expect(collect($response->inertiaProps()['sites'])->pluck('name')->all())->toBe(['Mine']);
});

it('creates and updates a client', function () {
    [$user] = userWithOrg();

    $this->actingAs($user)
        ->post('/clients', ['name' => 'Acme Inc', 'email' => 'billing@acme.test'])
        ->assertRedirect('/clients');

    $client = Client::where('name', 'Acme Inc')->first();
    expect($client)->not->toBeNull();

    $this->actingAs($user)
        ->put("/clients/{$client->id}", ['name' => 'Acme Renamed'])
        ->assertRedirect('/clients');

    expect($client->refresh()->name)->toBe('Acme Renamed');
});

it('deleting a client nullifies its sites instead of deleting them', function () {
    [$user, $organization] = userWithOrg();
    $client = Client::factory()->for($organization)->create();
    $site = Site::factory()->for($organization)->for($client)->create();

    $this->actingAs($user)
        ->delete("/clients/{$client->id}")
        ->assertRedirect('/clients');

    expect($site->refresh()->client_id)->toBeNull();
});

it('serves the agent plugin for download', function () {
    [$user] = userWithOrg();

    $this->actingAs($user)
        ->get('/agent-plugin/download')
        ->assertOk()
        ->assertDownload('shoal-agent.php');
});
