<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileUiTest extends TestCase
{
    public function test_primary_layouts_are_mobile_pwa_ready(): void
    {
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $guestLayout = file_get_contents(resource_path('views/layouts/guest.blade.php'));
        $landingPage = file_get_contents(resource_path('views/welcome.blade.php'));

        foreach ([$appLayout, $guestLayout, $landingPage] as $layout) {
            $this->assertStringContainsString('width=device-width, initial-scale=1, viewport-fit=cover', $layout);
            $this->assertStringContainsString('/manifest.webmanifest', $layout);
            $this->assertStringContainsString('/icons/icon-192.png', $layout);
        }
    }

    public function test_application_navigation_has_mobile_drawer_and_desktop_sidebar_states(): void
    {
        $navigation = file_get_contents(resource_path('views/livewire/layout/navigation.blade.php'));

        $this->assertStringContainsString('lg:hidden', $navigation);
        $this->assertStringContainsString("open ? 'translate-x-0' : '-translate-x-full'", $navigation);
        $this->assertStringContainsString('lg:translate-x-0', $navigation);
        $this->assertStringContainsString('overflow-y-auto', $navigation);
    }

    public function test_pos_stacks_before_wide_desktop_breakpoint(): void
    {
        $pos = file_get_contents(resource_path('views/livewire/pos/sale-terminal.blade.php'));

        $this->assertStringContainsString('xl:grid-cols-[minmax(0,1fr)_420px]', $pos);
        $this->assertStringContainsString('flex flex-col gap-3 sm:flex-row', $pos);
        $this->assertStringContainsString('sm:grid-cols-[1fr_1fr_auto]', $pos);
    }

    public function test_tables_have_horizontal_scroll_fallback(): void
    {
        $styles = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('.sniper-table-wrap', $styles);
        $this->assertStringContainsString('overflow-x-auto', $styles);
    }
}
