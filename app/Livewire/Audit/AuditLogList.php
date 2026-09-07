<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AuditLogList extends Component
{
    public string $search = '';

    public string $action = '';

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->action = '';
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when(trim($this->search) !== '', function ($query): void {
                $term = trim($this->search);

                $query->where(function ($query) use ($term): void {
                    $query->where('description', 'like', '%'.$term.'%')
                        ->orWhere('action', 'like', '%'.$term.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->latest('id')
            ->limit(200)
            ->get();

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('livewire.audit.audit-log-list', [
            'logs' => $logs,
            'actions' => $actions,
        ]);
    }
}
