<?php

namespace App\Livewire\Settings;

use App\Support\Audit;
use App\Support\DatabaseBackupService;
use App\Support\SystemReadinessService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class SystemReadiness extends Component
{
    public string $authorizationPassword = '';

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(), 403);
    }

    public function createBackup(DatabaseBackupService $service): void
    {
        $this->validate([
            'authorizationPassword' => ['required', 'current_password'],
        ], ['authorizationPassword.current_password' => 'The administrator password is incorrect.']);

        try {
            $filename = $service->create();
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('backup', 'Backup failed. Confirm that the database dump utility is installed and storage is writable.');

            return;
        }

        Audit::record('database.backup.created', null, 'Administrator created a private database backup.', ['filename' => $filename]);
        $this->authorizationPassword = '';
        session()->flash('success', 'Database backup created: '.$filename);
    }

    public function render(SystemReadinessService $service)
    {
        return view('livewire.settings.system-readiness', ['readiness' => $service->inspect()]);
    }
}
