<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Receipt printing</div>
            <h1 class="sniper-title mt-1">Printer Settings</h1>
            <p class="sniper-subtitle">Adjust receipt width, inner padding, and text size for different thermal receipt printers.</p>
        </div>
        <span class="sniper-badge-navy">{{ $paperWidthMm }} mm paper</span>
    </div>

    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        SniperPOS sizes the printed receipt content to the width selected here. In the browser or operating-system print dialog, also select the matching physical paper size for your printer and use 100% scale with browser headers and footers disabled.
    </div>

    @if (session('success'))
        <div class="sniper-alert-success mb-6">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <div class="space-y-6">
            <section class="sniper-card p-5 sm:p-6">
                <div class="sniper-section-header -mx-5 -mt-5 mb-5 sm:-mx-6 sm:-mt-6">
                    <h2 class="font-heading text-base font-bold text-sniper-navy">Paper Width</h2>
                    <p class="mt-1 text-xs text-sniper-slate">Choose a common receipt roll or enter a custom width from 40 to 120 mm.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($presets as $preset)
                        <button
                            type="button"
                            wire:click="usePreset({{ $preset }})"
                            @class([
                                'rounded-xl border px-4 py-3 text-left transition',
                                'border-sniper-red bg-red-50 ring-2 ring-sniper-red/10' => $paperWidthMm === $preset,
                                'border-slate-200 bg-white hover:border-slate-300' => $paperWidthMm !== $preset,
                            ])
                        >
                            <span class="block font-heading text-lg font-bold text-sniper-navy">{{ $preset }} mm</span>
                            <span class="mt-1 block text-xs text-sniper-slate">
                                {{ match($preset) { 58 => '2-inch thermal', 76 => '3-inch / 76 mm', 80 => 'Standard 80 mm', 112 => 'Wide receipt', default => 'Receipt roll' } }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 max-w-xs">
                    <x-input-label for="paper-width-mm" value="Custom paper width (mm)" />
                    <x-text-input id="paper-width-mm" wire:model.live.debounce.300ms="paperWidthMm" type="number" min="40" max="120" step="1" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('paperWidthMm')" class="mt-2" />
                </div>
            </section>

            <section class="sniper-card p-5 sm:p-6">
                <div class="sniper-section-header -mx-5 -mt-5 mb-5 sm:-mx-6 sm:-mt-6">
                    <h2 class="font-heading text-base font-bold text-sniper-navy">Receipt Layout</h2>
                    <p class="mt-1 text-xs text-sniper-slate">Fine-tune the printable area without changing any invoice data.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="content-padding-mm" value="Inner padding (mm)" />
                        <x-text-input id="content-padding-mm" wire:model.live.debounce.300ms="contentPaddingMm" type="number" min="0" max="10" step="1" class="mt-1.5 block w-full" />
                        <p class="mt-1.5 text-xs text-sniper-slate">Use 2–3 mm for narrow 58 mm printers and 3–5 mm for 80 mm printers.</p>
                        <x-input-error :messages="$errors->get('contentPaddingMm')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="font-size-px" value="Base text size (px)" />
                        <x-text-input id="font-size-px" wire:model.live.debounce.300ms="fontSizePx" type="number" min="8" max="16" step="1" class="mt-1.5 block w-full" />
                        <p class="mt-1.5 text-xs text-sniper-slate">Smaller paper usually works best at 10–11 px; 80 mm works well at 11–13 px.</p>
                        <x-input-error :messages="$errors->get('fontSizePx')" class="mt-2" />
                    </div>
                </div>

                <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <input wire:model.live="showLogo" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red">
                    <span>
                        <span class="block text-sm font-semibold text-sniper-navy">Print SniperPOS logo</span>
                        <span class="mt-1 block text-xs text-sniper-slate">Turn this off for basic monochrome printers if image printing is slow or unclear.</span>
                    </span>
                </label>
            </section>

            <div class="flex justify-end">
                <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">Save Printer Settings</x-primary-button>
            </div>
        </div>

        <aside class="sniper-card h-fit overflow-hidden p-5 sm:p-6">
            <div class="sniper-section-header -mx-5 -mt-5 mb-5 sm:-mx-6 sm:-mt-6">
                <h2 class="font-heading text-base font-bold text-sniper-navy">Receipt Preview</h2>
                <p class="mt-1 text-xs text-sniper-slate">Relative preview of the current width and spacing.</p>
            </div>

            <div class="overflow-x-auto rounded-xl bg-slate-100 p-4">
                <div
                    class="mx-auto bg-white text-slate-900 shadow-sm"
                    style="width:min(100%, {{ max(190, min(454, $paperWidthMm * 3.78)) }}px);padding:{{ $contentPaddingMm }}mm;font-size:{{ $fontSizePx }}px;"
                >
                    @if ($showLogo)
                        <div class="text-center font-heading text-lg font-extrabold text-sniper-navy">SniperPOS</div>
                    @endif
                    <div class="text-center text-[0.85em] text-slate-500">Precision in Every Sale.</div>
                    <div class="my-3 border-t border-dashed border-slate-400"></div>
                    <div class="flex justify-between gap-2"><span>Sample Item × 2</span><span>₱100.00</span></div>
                    <div class="mt-2 flex justify-between gap-2 font-bold"><span>Total</span><span>₱100.00</span></div>
                    <div class="my-3 border-t border-dashed border-slate-400"></div>
                    <div class="text-center text-[0.85em] text-slate-500">Thank you for your purchase!</div>
                </div>
            </div>
        </aside>
    </form>
</div>
