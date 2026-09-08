<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_pwa_files_exist_and_manifest_is_valid(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('service-worker.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/simple-pos-icon.svg'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));

        $manifest = json_decode(
            file_get_contents(public_path('manifest.webmanifest')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('/', $manifest['id']);
        $this->assertSame('SniperPOS', $manifest['name']);
        $this->assertSame('SniperPOS', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('#0F2747', $manifest['theme_color']);
        $this->assertSame('any', $manifest['orientation']);
        $this->assertNotEmpty($manifest['icons']);

        $iconsBySize = collect($manifest['icons'])->keyBy('sizes');

        $this->assertSame('/icons/icon-192.png', $iconsBySize['192x192']['src']);
        $this->assertSame('image/png', $iconsBySize['192x192']['type']);
        $this->assertSame('/icons/icon-512.png', $iconsBySize['512x512']['src']);
        $this->assertSame('image/png', $iconsBySize['512x512']['type']);
    }

    public function test_service_worker_does_not_cache_authenticated_pages(): void
    {
        $serviceWorker = file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString("event.request.method !== 'GET'", $serviceWorker);
        $this->assertStringContainsString("requestUrl.origin !== self.location.origin", $serviceWorker);
        $this->assertStringContainsString("const CACHE_NAME = 'sniperpos-v2'", $serviceWorker);
        $this->assertStringContainsString("const OFFLINE_URL = '/offline.html'", $serviceWorker);
        $this->assertStringContainsString("event.request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString('fetch(event.request).catch(() => caches.match(OFFLINE_URL))', $serviceWorker);
        $this->assertStringContainsString("requestUrl.pathname.startsWith('/build/')", $serviceWorker);
        $this->assertStringContainsString("requestUrl.pathname.startsWith('/icons/')", $serviceWorker);
        $this->assertStringContainsString("requestUrl.pathname === '/manifest.webmanifest'", $serviceWorker);
        $this->assertStringNotContainsString("    '/',\n", $serviceWorker);
    }

    public function test_offline_fallback_explains_that_business_data_is_not_cached(): void
    {
        $offline = file_get_contents(public_path('offline.html'));

        $this->assertStringContainsString('SniperPOS', $offline);
        $this->assertStringContainsString('sales, reports, receipts', $offline);
        $this->assertStringContainsString('are not stored for offline viewing', $offline);
        $this->assertStringContainsString('Try Again', $offline);
    }
}
