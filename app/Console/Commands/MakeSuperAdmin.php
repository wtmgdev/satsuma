<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'satsuma:super-admin {email} {--revoke}';

    protected $description = 'Grant or revoke internal admin (Filament/Horizon) access for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');

        $user->forceFill(['is_super_admin' => ! $revoke])->save();

        $this->info($revoke
            ? "{$user->email} is no longer a superadmin."
            : "{$user->email} is now a superadmin.");

        return self::SUCCESS;
    }
}
