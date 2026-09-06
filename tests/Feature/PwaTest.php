<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_pwa_files_exist_and_manifest_is_valid(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('service-worker.js'));
        $this->assertFileExists(public_path('icons/simple-pos-icon.svg'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));

        $manifest = json_decode(
            file_get_contents(public_path('manifest.webmanifest')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Simple POS', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertNotEmpty($manifest['icons']);

        $iconsBySize = collect($manifest['icons'])->keyBy('sizes');

        $this->assertSame('/icons/icon-192.png', $iconsBySize['192x192']['src']);
        $this->assertSame('image/png', $iconsBySize['192x192']['type']);
        $this->assertSame('/icons/icon-512.png', $iconsBySize['512x512']['src']);
        $this->assertSame('image/png', $iconsBySize['512x512']['type']);
    }

    public function test_service_worker_only_caches_get_requests(): void
    {
        $serviceWorker = file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString("event.request.method !== 'GET'", $serviceWorker);
        $this->assertStringContainsString("requestUrl.origin !== self.location.origin", $serviceWorker);
        $this->assertStringContainsString("'/icons/icon-192.png'", $serviceWorker);
        $this->assertStringContainsString("'/icons/icon-512.png'", $serviceWorker);
    }
}
