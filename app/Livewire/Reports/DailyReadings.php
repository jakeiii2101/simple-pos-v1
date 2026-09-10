<?php

namespace App\Livewire\Reports;

use App\Models\DailyClosing;
use App\Support\DailyReadingService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DailyReadings extends Component
{
    public string $businessDate = '';

    public string $notes = '';

    public string $authorizationPassword = '';

    public bool $confirmed = false;

    public function mount(): void
    {
        $this->businessDate = now()->toDateString();
    }

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(), 403);
    }

    public function createZReading(DailyReadingService $service): void
    {
        $validated = $this->validate([
            'businessDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'authorizationPassword' => ['required', 'current_password'],
            'confirmed' => ['accepted'],
        ], [
            'authorizationPassword.current_password' => 'The administrator password is incorrect.',
            'confirmed.accepted' => 'Confirm the permanent daily closing before continuing.',
        ]);

        $closing = $service->close(Carbon::parse($validated['businessDate']), auth()->user(), $validated['notes'] ?? null);

        $this->reset(['notes', 'authorizationPassword', 'confirmed']);
        session()->flash('success', 'Z-reading '.$closing->reading_number.' was created successfully.');
    }

    public function render(DailyReadingService $service)
    {
        $validDate = validator(['date' => $this->businessDate], ['date' => ['required', 'date_format:Y-m-d']])->passes();
        $date = $validDate ? Carbon::parse($this->businessDate) : now();

        return view('livewire.reports.daily-readings', [
            'snapshot' => $service->snapshot($date),
            'existingClosing' => DailyClosing::query()->with('closedBy')->whereDate('business_date', $date)->first(),
            'closings' => DailyClosing::query()->with('closedBy')->latest('business_date')->limit(31)->get(),
        ]);
    }
}
