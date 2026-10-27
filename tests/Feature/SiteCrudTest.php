<?php

use App\Jobs\SyncSiteStatusJob;
use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('creates a site with a generated token and queues the first scan', function () {
    Queue::fake();
    $user = User::factory()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)->post('/sites', [
        'client_id' => $client->id,
        'name' => 'Acme Blog',
        'url' => 'https://acme.test',
    ]);

    $site = Site::where('url', 'https://acme.test')->first();

    expect($site)->not->toBeNull()
        ->and($site->client_id)->toBe($client->id)
        ->and($site->api_token)->not->toBeEmpty()
        ->and($response->headers->get('Location'))->toContain("/sites/{$site->id}");

    Queue::assertPushed(SyncSiteStatusJob::class);
});

it('validates duplicate site urls', function () {
    $user = User::factory()->create();
    Site::factory()->create(['url' => 'https://acme.test']);

    $this->actingAs($user)
        ->post('/sites', ['name' => 'Other', 'url' => 'https://acme.test'])
        ->assertSessionHasErrors('url');
});

it('updates a site without regenerating the token when blank', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create();

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
    $user = User::factory()->create();
    $site = Site::factory()->create();

    $this->actingAs($user)
        ->delete("/sites/{$site->id}")
        ->assertRedirect('/sites');

    expect(Site::find($site->id))->toBeNull();
});

it('creates and updates a client', function () {
    $user = User::factory()->create();

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
    $user = User::factory()->create();
    $site = Site::factory()->create();

    $this->actingAs($user)
        ->delete("/clients/{$site->client_id}")
        ->assertRedirect('/clients');

    expect($site->refresh()->client_id)->toBeNull();
});

it('serves the agent plugin for download', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/agent-plugin/download')
        ->assertOk()
        ->assertDownload('shoal-agent.php');
});
