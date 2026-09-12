<?php

namespace App\Support;

use App\Models\BirSetting;
use App\Models\InvoiceSequence;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemReadinessService
{
    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $sequence = InvoiceSequence::query()
            ->where('document_type', InvoiceSequence::TYPE_SALES_INVOICE)
            ->where('is_active', true)
            ->first();
        $numbers = [];
        $malformed = [];

        if ($sequence !== null) {
            foreach (Sale::query()->whereNotNull('invoice_number')->pluck('invoice_number') as $invoice) {
                if (preg_match('/^'.preg_quote($sequence->prefix, '/').'(\d+)$/', $invoice, $matches) === 1) {
                    $numbers[] = (int) $matches[1];
                } else {
                    $malformed[] = $invoice;
                }
            }
        }

        sort($numbers);
        $missing = [];
        if ($sequence !== null) {
            $present = array_flip($numbers);
            $upper = $sequence->current_number;
            for ($number = $sequence->starting_number; $number <= $upper && count($missing) < 100; $number++) {
                if (! isset($present[$number])) {
                    $missing[] = $number;
                }
            }
        }

        $backupFiles = collect(Storage::disk('local')->files('backups'))
            ->reject(fn (string $file): bool => str_ends_with($file, '.sha256'))
            ->sortByDesc(fn (string $file): int => Storage::disk('local')->lastModified($file));
        $latestBackup = $backupFiles->first();

        $birSetting = BirSetting::query()->where('is_active', true)->first();
        $schemaReady = collect([
            'bir_settings', 'invoice_sequences', 'sale_adjustments', 'daily_closings',
            'sale_refunds', 'sale_refund_items', 'audit_logs',
        ])->every(fn (string $table): bool => Schema::hasTable($table));
        $latestBackupChecksumValid = $latestBackup !== null
            && Storage::disk('local')->exists($latestBackup.'.sha256')
            && str_contains(
                Storage::disk('local')->get($latestBackup.'.sha256'),
                hash_file('sha256', Storage::disk('local')->path($latestBackup)),
            );
        $latestBackupAgeHours = $latestBackup === null
            ? null
            : (int) floor((time() - Storage::disk('local')->lastModified($latestBackup)) / 3600);
        $production = app()->environment('production');
        $productionEnvironmentReady = ! $production || (
            ! config('app.debug')
            && str_starts_with((string) config('app.url'), 'https://')
            && (bool) config('session.secure')
        );

        $checks = [
            $this->check('database_schema', 'BIR database schema', $schemaReady, 'Run php artisan migrate --force.'),
            $this->check('active_admin', 'Active administrator', User::query()->where('role', User::ROLE_ADMIN)->where('status', User::STATUS_ACTIVE)->exists(), 'Create or reactivate at least one administrator.'),
            $this->check('bir_identity', 'Registered taxpayer identity', $birSetting !== null && filled($birSetting->registered_name) && filled($birSetting->tin) && filled($birSetting->registered_address) && filled($birSetting->rdo_code), 'Complete and activate BIR Settings.'),
            $this->check('invoice_sequence', 'Invoice sequence', $sequence !== null && ! ($sequence !== null && $numbers !== [] && max($numbers) > $sequence->current_number), 'Configure or correct the active Sales Invoice sequence.'),
            $this->check('invoice_integrity', 'Invoice integrity', $missing === [] && $malformed === [], 'Investigate gaps or malformed invoice numbers; never reuse a number.'),
            $this->check('verified_backup', 'Verified private backup', $latestBackupChecksumValid, 'Create a new backup and verify its SHA-256 checksum.'),
            $this->check('fresh_backup', 'Backup freshness', $latestBackupAgeHours !== null && $latestBackupAgeHours <= 24, 'Create a backup within 24 hours of production release.', 'warning'),
            $this->check('permit_reference', 'BIR permit/reference', $birSetting !== null && filled($birSetting->permit_number) && $birSetting->permit_date !== null, 'Record the final RDO-approved permit/reference before live issuance.', 'warning'),
            $this->check('production_environment', 'Production HTTPS and session security', $productionEnvironmentReady, 'Set APP_DEBUG=false, an HTTPS APP_URL, and SESSION_SECURE_COOKIE=true.'),
        ];
        $blockingFailures = collect($checks)->where('severity', 'blocker')->where('passed', false)->count();

        return [
            'bir_configured' => $birSetting !== null,
            'sequence_configured' => $sequence !== null,
            'sequence' => $sequence,
            'invoice_count' => count($numbers),
            'missing_numbers' => $missing,
            'missing_truncated' => count($missing) === 100,
            'malformed_invoices' => array_slice($malformed, 0, 100),
            'sequence_behind' => $sequence !== null && $numbers !== [] && max($numbers) > $sequence->current_number,
            'latest_backup' => $latestBackup === null ? null : [
                'name' => basename($latestBackup),
                'size' => Storage::disk('local')->size($latestBackup),
                'created_at' => Storage::disk('local')->lastModified($latestBackup),
            ],
            'backup_count' => $backupFiles->count(),
            'latest_backup_checksum_valid' => $latestBackupChecksumValid,
            'latest_backup_age_hours' => $latestBackupAgeHours,
            'checks' => $checks,
            'blocking_failures' => $blockingFailures,
            'warning_failures' => collect($checks)->where('severity', 'warning')->where('passed', false)->count(),
            'release_ready' => $blockingFailures === 0,
        ];
    }

    /** @return array{key:string,label:string,passed:bool,severity:string,remediation:string} */
    private function check(string $key, string $label, bool $passed, string $remediation, string $severity = 'blocker'): array
    {
        return compact('key', 'label', 'passed', 'severity', 'remediation');
    }
}
