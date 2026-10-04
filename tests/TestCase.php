<?php

namespace Tests;

use App\Support\AccountContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('accounts')) {
            app(AccountContext::class)->set(
                DB::table('accounts')->where('slug', 'sniperpos-legacy')->value('id')
            );
        }
    }

    protected function tearDown(): void
    {
        if (app()->bound(AccountContext::class)) {
            app(AccountContext::class)->clear();
        }

        parent::tearDown();
    }
}
