<?php

use Illuminate\Foundation\Inspiring;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('database:backup')->dailyAt('02:00')->withoutOverlapping();


Artisan::command('sniperpos:make-platform-owner {email}', function (string $email) {
    $user = User::query()
        ->where('email', strtolower(trim($email)))
        ->where('role', User::ROLE_ADMIN)
        ->where('status', User::STATUS_ACTIVE)
        ->first();

    if ($user === null) {
        $this->error('An active administrator with that email was not found.');
        return 1;
    }

    DB::transaction(function () use ($user): void {
        User::query()->update(['is_platform_owner' => false]);
        $user->update(['is_platform_owner' => true]);
    });

    $this->info('Platform owner set to '.$user->email.'. Only this account can approve new business accounts.');

    return 0;
})->purpose('Set the one SniperPOS platform owner who can approve customer accounts');
