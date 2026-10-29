<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

it('renders the internal admin users list page', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_super_admin' => true])->save();
    User::factory()->count(3)->create();

    $this->actingAs($user);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords(User::all());
});

it('forbids non-superadmins from the admin panel and allows superadmins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();

    $user->forceFill(['is_super_admin' => true])->save();

    $this->actingAs($user)->get('/admin')->assertOk();
});
