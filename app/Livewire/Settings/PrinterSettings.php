<?php

namespace App\Livewire\Settings;

use App\Models\PrinterSetting;
use App\Support\Audit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PrinterSettings extends Component
{
    public int $paperWidthMm = PrinterSetting::DEFAULT_PAPER_WIDTH_MM;

    public int $contentPaddingMm = PrinterSetting::DEFAULT_CONTENT_PADDING_MM;

    public int $fontSizePx = PrinterSetting::DEFAULT_FONT_SIZE_PX;

    public bool $showLogo = true;

    public function mount(): void
    {
        $setting = PrinterSetting::current();

        $this->paperWidthMm = $setting->paper_width_mm;
        $this->contentPaddingMm = $setting->content_padding_mm;
        $this->fontSizePx = $setting->font_size_px;
        $this->showLogo = $setting->show_logo;
    }

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    public function usePreset(int $width): void
    {
        abort_unless(in_array($width, PrinterSetting::presetWidths(), true), 422);

        $this->paperWidthMm = $width;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'paperWidthMm' => ['required', 'integer', 'min:40', 'max:120'],
            'contentPaddingMm' => ['required', 'integer', 'min:0', 'max:10'],
            'fontSizePx' => ['required', 'integer', 'min:8', 'max:16'],
            'showLogo' => ['required', 'boolean'],
        ]);

        $setting = PrinterSetting::query()->firstOrNew();
        $before = $setting->exists
            ? $setting->only(['paper_width_mm', 'content_padding_mm', 'font_size_px', 'show_logo'])
            : null;

        $setting->fill([
            'paper_width_mm' => $validated['paperWidthMm'],
            'content_padding_mm' => $validated['contentPaddingMm'],
            'font_size_px' => $validated['fontSizePx'],
            'show_logo' => $validated['showLogo'],
        ]);
        $setting->save();

        Audit::record(
            'printer.settings.updated',
            $setting,
            'Receipt printer settings updated.',
            [
                'before' => $before,
                'after' => $setting->only([
                    'paper_width_mm',
                    'content_padding_mm',
                    'font_size_px',
                    'show_logo',
                ]),
            ],
        );

        session()->flash('success', 'Printer settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.settings.printer-settings', [
            'presets' => PrinterSetting::presetWidths(),
        ]);
    }
}
