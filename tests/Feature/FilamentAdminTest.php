<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

it('renders the internal admin users list page', function () {
    $user = User::factory()->create();
    User::factory()->count(3)->create();

    $this->actingAs($user);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords(User::all());
});
