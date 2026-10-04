<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetPlatformOwner extends Command
{
    protected $signature = 'platform:owner {email : Email address of the trusted SniperPOS platform owner}';

    protected $description = 'Grant platform-owner approval access to an existing SniperPOS user';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        $user = User::withoutGlobalScope('account')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user === null) {
            $this->error('No SniperPOS user was found with that email address.');

            return self::FAILURE;
        }

        User::withoutGlobalScope('account')
            ->where('is_platform_owner', true)
            ->whereKeyNot($user->id)
            ->update(['is_platform_owner' => false]);

        $user->is_platform_owner = true;
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        $this->info('Platform-owner access is now assigned to '.$user->email.'.');
        $this->warn('Only assign this permission to the trusted SniperPOS platform owner.');

        return self::SUCCESS;
    }
}
