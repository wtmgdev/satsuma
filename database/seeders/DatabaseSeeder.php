<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@satsuma.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->forceFill(['is_super_admin' => true])->save();

        if ($admin->organizations()->count() === 0) {
            Organization::createWithOwner($admin, 'Satsuma')
                ->forceFill(['credits_unlimited' => true])->save();
        }

        $this->call(DemoSeeder::class);
    }
}
