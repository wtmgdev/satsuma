<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function orgMember(string $role = 'owner'): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->members()->attach($user->id, ['role' => $role]);

    return [$user, $organization];
}

it('creates a personal workspace on registration', function () {
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'jane@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->organizations)->toHaveCount(1)
        ->and($user->organizations->first()->name)->toBe("Jane Doe's Workspace")
        ->and($user->roleIn($user->organizations->first()))->toBe('owner');
});

it('creates an organization with the creator as owner', function () {
    [$user] = orgMember();

    $this->actingAs($user)
        ->post('/organizations', ['name' => 'New Agency'])
        ->assertRedirect('/dashboard');

    $organization = Organization::where('name', 'New Agency')->first();

    expect($organization)->not->toBeNull()
        ->and($user->roleIn($organization))->toBe('owner');
});

it('switches the current organization', function () {
    [$user, $organization] = orgMember();
    $other = Organization::createWithOwner($user, 'Other Org');

    $this->actingAs($user)
        ->post('/organizations/switch', ['organization_id' => $other->id])
        ->assertRedirect();

    $response = $this->actingAs($user)->get('/dashboard');

    expect($response->inertiaProps()['currentOrganization']['id'])->toBe($other->id);
});

it('shows the organization settings page with members', function () {
    [$user, $organization] = orgMember();
    $member = User::factory()->create();
    $organization->members()->attach($member->id, ['role' => 'member']);

    $response = $this->actingAs($user)->get('/settings/organization');

    $props = $response->inertiaProps();

    expect($props['organization']['name'])->toBe($organization->name)
        ->and(collect($props['members'])->pluck('role')->all())->toContain('owner', 'member')
        ->and($props['canManageMembers'])->toBeTrue();
});

it('adds an existing user as a member', function () {
    [$user, $organization] = orgMember();
    $invitee = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/organization/members', [
            'email' => $invitee->email,
            'role' => 'member',
        ])
        ->assertRedirect();

    expect($organization->roleFor($invitee))->toBe('member');
});

it('rejects adding a member by an unknown email', function () {
    [$user] = orgMember();

    $this->actingAs($user)
        ->post('/settings/organization/members', [
            'email' => 'ghost@example.test',
            'role' => 'member',
        ])
        ->assertSessionHasErrors('email');
});

it('forbids members from managing members', function () {
    [$user, $organization] = orgMember('member');
    $invitee = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/organization/members', [
            'email' => $invitee->email,
            'role' => 'member',
        ])
        ->assertForbidden();

    expect($organization->roleFor($invitee))->toBeNull();
});

it('prevents removing the organization owner', function () {
    [$owner, $organization] = orgMember();
    $admin = User::factory()->create();
    $organization->members()->attach($admin->id, ['role' => 'admin']);

    $this->actingAs($admin)
        ->delete("/settings/organization/members/{$owner->id}")
        ->assertForbidden();

    expect($organization->roleFor($owner))->toBe('owner');
});

it('updates and removes a member', function () {
    [$owner, $organization] = orgMember();
    $member = User::factory()->create();
    $organization->members()->attach($member->id, ['role' => 'member']);

    $this->actingAs($owner)
        ->patch("/settings/organization/members/{$member->id}", ['role' => 'admin'])
        ->assertRedirect();

    expect($organization->roleFor($member))->toBe('admin');

    $this->actingAs($owner)
        ->delete("/settings/organization/members/{$member->id}")
        ->assertRedirect();

    expect($organization->roleFor($member))->toBeNull();
});

it('renames the organization', function () {
    [$user, $organization] = orgMember();

    $this->actingAs($user)
        ->patch('/settings/organization', ['name' => 'Renamed Org'])
        ->assertRedirect();

    expect($organization->refresh()->name)->toBe('Renamed Org');
});

it('authenticates a user with an organization', function () {
    $user = User::factory()->create([
        'email' => 'seeded@shoal.test',
        'password' => Hash::make('password'),
    ]);
    Organization::createWithOwner($user, 'Seeded Org');

    $this->post('/login', [
        'email' => 'seeded@shoal.test',
        'password' => 'password',
    ])->assertRedirect('/dashboard');
});
