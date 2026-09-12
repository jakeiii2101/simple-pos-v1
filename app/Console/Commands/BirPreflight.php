<?php

namespace App\Console\Commands;

use App\Support\SystemReadinessService;
use Illuminate\Console\Command;

class BirPreflight extends Command
{
    protected $signature = 'bir:preflight {--production : Fail when a blocking readiness check does not pass}';

    protected $description = 'Run the final SniperPOS BIR operational readiness checks';

    public function handle(SystemReadinessService $service): int
    {
        $readiness = $service->inspect();
        $this->components->info('SniperPOS BIR operational preflight');

        foreach ($readiness['checks'] as $check) {
            $state = $check['passed'] ? 'PASS' : strtoupper($check['severity']);
            $this->line(sprintf('[%s] %s', $state, $check['label']));
            if (! $check['passed']) {
                $this->line('  '.$check['remediation']);
            }
        }

        if ($readiness['release_ready']) {
            $this->components->info('All blocking checks passed. Manual BIR/RDO and device verification is still required.');

            return self::SUCCESS;
        }

        $this->components->error($readiness['blocking_failures'].' blocking check(s) failed.');

        return $this->option('production') ? self::FAILURE : self::SUCCESS;
    }
}
