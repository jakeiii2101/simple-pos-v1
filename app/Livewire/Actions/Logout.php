<?php

namespace App\Livewire\Actions;

use App\Support\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        $user = Auth::guard('web')->user();

        if ($user !== null) {
            Audit::record(
                'auth.logout',
                $user,
                'User logged out successfully.',
            );
        }

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
